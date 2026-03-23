<?php
// ── votes.php  cast vote + results ───────────────────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── CAST VOTE ─────────────────────────────────────────────────
if ($method === 'POST' && $action === 'cast') {
    requireLogin();
    // Only normal voters can cast votes. Admins can manage data and view logs/results only.
    if (($_SESSION['role'] ?? '') === 'admin') {
        jsonResponse(
            ['success' => false, 'message' => 'Admins cannot cast votes.'],
            403
        );
    }
    $uid = (int)$_SESSION['user_id'];
    $eid = (int)($_POST['election_id'] ?? 0);
    $cid = (int)($_POST['candidate_id'] ?? 0);

    if (!$eid || !$cid)
        jsonResponse(['success' => false, 'message' => 'Invalid election or candidate.']);

    // Check election is active
    $estmt = $db->prepare("SELECT status FROM elections WHERE election_id = ?");
    $estmt->execute([$eid]);
    $el = $estmt->fetch();
    if (!$el || $el['status'] !== 'active')
        jsonResponse(['success' => false, 'message' => 'This election is not currently active.']);

    // Get candidate's position
    $cstmt = $db->prepare("SELECT position_id FROM candidates WHERE candidate_id = ? AND election_id = ?");
    $cstmt->execute([$cid, $eid]);
    $candidate = $cstmt->fetch();
    if (!$candidate)
        jsonResponse(['success' => false, 'message' => 'Candidate does not belong to this election.']);

    $position_id = $candidate['position_id'];

    // Check for double vote in this position
    $vstmt = $db->prepare("SELECT vote_id FROM votes WHERE user_id = ? AND election_id = ? AND position_id = ?");
    $vstmt->execute([$uid, $eid, $position_id]);
    if ($vstmt->fetch())
        jsonResponse(['success' => false, 'message' => 'You have already voted for this position.']);

    $ins = $db->prepare("INSERT INTO votes (user_id, election_id, position_id, candidate_id) VALUES (?, ?, ?, ?)");
    $ins->execute([$uid, $eid, $position_id, $cid]);
    jsonResponse(['success' => true, 'message' => 'Your vote has been cast successfully! 🗳️']);
}

// ── CHECK if user voted ───────────────────────────────────────
if ($method === 'GET' && $action === 'check') {
    requireLogin();
    $uid = (int)$_SESSION['user_id'];
    $eid = (int)($_GET['election_id'] ?? 0);

    $stmt = $db->prepare(
        "SELECT v.vote_id, v.position_id, c.full_name AS candidate_name, p.party_name, pos.position_name
         FROM votes v
         JOIN candidates c ON v.candidate_id = c.candidate_id
         JOIN parties    p ON c.party_id = p.party_id
         JOIN positions pos ON v.position_id = pos.position_id
         WHERE v.user_id = ? AND v.election_id = ?"
    );
    $stmt->execute([$uid, $eid]);
    $votes = $stmt->fetchAll();
    
    // Return array of votes grouped by position
    $votedPositions = [];
    foreach ($votes as $vote) {
        $votedPositions[$vote['position_id']] = $vote;
    }
    
    jsonResponse(['success' => true, 'votes' => $votedPositions]);
}

// ── RESULTS (live tally) ──────────────────────────────────────
if ($method === 'GET' && $action === 'results') {
    $eid = (int)($_GET['election_id'] ?? 0);
    if (!$eid) jsonResponse(['success' => false, 'message' => 'Election ID required.']);

    $stmt = $db->prepare(
        "SELECT c.candidate_id, c.full_name, p.party_name, pos.position_name, pos.position_id,
                COUNT(v.vote_id) AS vote_count
         FROM candidates c
         JOIN parties p ON c.party_id = p.party_id
         JOIN positions pos ON c.position_id = pos.position_id
         LEFT JOIN votes v ON v.candidate_id = c.candidate_id AND v.election_id = ?
         WHERE c.election_id = ?
         GROUP BY c.candidate_id
         ORDER BY pos.display_order, vote_count DESC"
    );
    $stmt->execute([$eid, $eid]);
    $results = $stmt->fetchAll();

    // Group by position
    $byPosition = [];
    foreach ($results as $r) {
        $posId = $r['position_id'];
        if (!isset($byPosition[$posId])) {
            $byPosition[$posId] = [
                'position_id' => $posId,
                'position_name' => $r['position_name'],
                'candidates' => [],
                'total_votes' => 0
            ];
        }
        $byPosition[$posId]['candidates'][] = $r;
        $byPosition[$posId]['total_votes'] += $r['vote_count'];
    }

    // Calculate percentages
    foreach ($byPosition as &$pos) {
        $total = $pos['total_votes'];
        foreach ($pos['candidates'] as &$c) {
            $c['percentage'] = $total > 0 ? round(($c['vote_count'] / $total) * 100, 1) : 0;
        }
    }

    jsonResponse(['success' => true, 'data' => array_values($byPosition)]);
}

// ── PARTY-WISE RESULTS ────────────────────────────────────────
if ($method === 'GET' && $action === 'party_results') {
    $eid = (int)($_GET['election_id'] ?? 0);
    if (!$eid) jsonResponse(['success' => false, 'message' => 'Election ID required.']);

    $stmt = $db->prepare(
        "SELECT p.party_id, p.party_name, COUNT(v.vote_id) AS total_votes
         FROM parties p
         JOIN candidates c ON p.party_id = c.party_id
         LEFT JOIN votes v ON v.candidate_id = c.candidate_id AND v.election_id = ?
         WHERE c.election_id = ?
         GROUP BY p.party_id
         ORDER BY total_votes DESC"
    );
    $stmt->execute([$eid, $eid]);
    $results = $stmt->fetchAll();

    $total = array_sum(array_column($results, 'total_votes'));
    foreach ($results as &$r) {
        $r['percentage'] = $total > 0 ? round(($r['total_votes'] / $total) * 100, 1) : 0;
    }

    jsonResponse(['success' => true, 'data' => $results, 'total_votes' => $total]);
}

// ── INDIVIDUAL CANDIDATE RESULTS ──────────────────────────────
if ($method === 'GET' && $action === 'candidate_results') {
    $eid = (int)($_GET['election_id'] ?? 0);
    if (!$eid) jsonResponse(['success' => false, 'message' => 'Election ID required.']);

    $stmt = $db->prepare(
        "SELECT c.candidate_id, c.full_name, p.party_name, pos.position_name,
                COUNT(v.vote_id) AS vote_count
         FROM candidates c
         JOIN parties p ON c.party_id = p.party_id
         JOIN positions pos ON c.position_id = pos.position_id
         LEFT JOIN votes v ON v.candidate_id = c.candidate_id
         WHERE c.election_id = ?
         GROUP BY c.candidate_id
         ORDER BY vote_count DESC"
    );
    $stmt->execute([$eid]);
    $results = $stmt->fetchAll();

    $total = array_sum(array_column($results, 'vote_count'));
    foreach ($results as &$r) {
        $r['percentage'] = $total > 0 ? round(($r['vote_count'] / $total) * 100, 1) : 0;
    }

    jsonResponse(['success' => true, 'data' => $results, 'total_votes' => $total]);
}

// ── ADMIN: all votes log ──────────────────────────────────────
if ($method === 'GET' && $action === 'log') {
    requireAdmin();
    $eid  = (int)($_GET['election_id'] ?? 0);
    $stmt = $db->prepare(
        "SELECT v.vote_id, u.full_name AS voter_name, c.full_name AS candidate_name,
                p.party_name, pos.position_name, v.voted_at
         FROM votes v
         JOIN users      u ON v.user_id = u.user_id
         JOIN candidates c ON v.candidate_id = c.candidate_id
         JOIN parties    p ON c.party_id = p.party_id
         JOIN positions pos ON v.position_id = pos.position_id
         WHERE v.election_id = ?
         ORDER BY v.voted_at DESC"
    );
    $stmt->execute([$eid]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
