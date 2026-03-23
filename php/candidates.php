<?php
// ── candidates.php  CRUD for candidates ──────────────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── READ candidates by election ───────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $eid  = (int)($_GET['election_id'] ?? 0);
    $stmt = $db->prepare(
        'SELECT c.*, p.party_name, p.party_logo,
                (SELECT COUNT(*) FROM votes v WHERE v.candidate_id = c.candidate_id) AS total_votes
         FROM candidates c
         JOIN parties p ON c.party_id = p.party_id
         WHERE c.election_id = ?
         ORDER BY c.candidate_id'
    );
    $stmt->execute([$eid]);
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── READ all parties (for dropdowns) ─────────────────────────
if ($method === 'GET' && $action === 'parties') {
    $stmt = $db->query('SELECT * FROM parties ORDER BY party_name');
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── CREATE candidate (admin only) ─────────────────────────────
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $eid      = (int)($_POST['election_id'] ?? 0);
    $party_id = (int)($_POST['party_id'] ?? 0);
    $name     = trim($_POST['full_name'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');

    if (!$eid || !$party_id || !$name)
        jsonResponse(['success' => false, 'message' => 'Election, party, and name required.']);

    $stmt = $db->prepare(
        'INSERT INTO candidates (election_id, party_id, full_name, bio) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$eid, $party_id, $name, $bio]);
    jsonResponse(['success' => true, 'message' => 'Candidate added.', 'id' => $db->lastInsertId()]);
}

// ── UPDATE candidate (admin only) ─────────────────────────────
if ($method === 'POST' && $action === 'update') {
    requireAdmin();
    $cid      = (int)($_POST['candidate_id'] ?? 0);
    $party_id = (int)($_POST['party_id'] ?? 0);
    $name     = trim($_POST['full_name'] ?? '');
    $bio      = trim($_POST['bio'] ?? '');

    if (!$cid || !$party_id || !$name)
        jsonResponse(['success' => false, 'message' => 'All fields required.']);

    $stmt = $db->prepare(
        'UPDATE candidates SET party_id=?, full_name=?, bio=? WHERE candidate_id=?'
    );
    $stmt->execute([$party_id, $name, $bio, $cid]);
    jsonResponse(['success' => true, 'message' => 'Candidate updated.']);
}

// ── DELETE candidate (admin only) ─────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $cid = (int)($_POST['candidate_id'] ?? 0);
    if (!$cid) jsonResponse(['success' => false, 'message' => 'Invalid candidate ID.']);
    $db->prepare('DELETE FROM candidates WHERE candidate_id = ?')->execute([$cid]);
    jsonResponse(['success' => true, 'message' => 'Candidate removed.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
