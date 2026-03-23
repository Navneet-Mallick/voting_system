<?php
// ── elections.php  CRUD for elections ────────────────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── READ all elections (public) ───────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $stmt = $db->query(
        'SELECT e.*, u.full_name AS created_by_name,
                (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.election_id) AS candidate_count,
                (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.election_id) AS vote_count
         FROM elections e JOIN users u ON e.created_by = u.user_id
         ORDER BY e.created_at DESC'
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── READ single election ──────────────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare(
        'SELECT e.*, u.full_name AS created_by_name FROM elections e
         JOIN users u ON e.created_by = u.user_id WHERE e.election_id = ?'
    );
    $stmt->execute([$id]);
    $election = $stmt->fetch();
    if (!$election) jsonResponse(['success' => false, 'message' => 'Election not found.'], 404);
    jsonResponse(['success' => true, 'data' => $election]);
}

// ── CREATE election (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $title      = trim($_POST['title'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';

    if (!$title || !$start_date || !$end_date)
        jsonResponse(['success' => false, 'message' => 'Title, start date, and end date are required.']);
    if ($end_date <= $start_date)
        jsonResponse(['success' => false, 'message' => 'End date must be after start date.']);

    $now    = date('Y-m-d H:i:s');
    $status = ($start_date > $now) ? 'upcoming' : (($end_date < $now) ? 'closed' : 'active');

    $stmt = $db->prepare(
        'INSERT INTO elections (title, description, start_date, end_date, status, created_by)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $desc, $start_date, $end_date, $status, $_SESSION['user_id']]);
    jsonResponse(['success' => true, 'message' => 'Election created.', 'id' => $db->lastInsertId()]);
}

// ── UPDATE election (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'update') {
    requireAdmin();
    $id         = (int)($_POST['election_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $status     = $_POST['status'] ?? 'upcoming';

    if (!$id || !$title || !$start_date || !$end_date)
        jsonResponse(['success' => false, 'message' => 'All fields required.']);

    $stmt = $db->prepare(
        'UPDATE elections SET title=?, description=?, start_date=?, end_date=?, status=?
         WHERE election_id=?'
    );
    $stmt->execute([$title, $desc, $start_date, $end_date, $status, $id]);
    jsonResponse(['success' => true, 'message' => 'Election updated.']);
}

// ── DELETE election (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['election_id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid election ID.']);
    $db->prepare('DELETE FROM elections WHERE election_id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Election deleted.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
