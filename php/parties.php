<?php
// ── parties.php  CRUD for parties ────────────────────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── LIST all parties ──────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $stmt = $db->query('SELECT * FROM parties ORDER BY party_name');
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── GET single party ──────────────────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM parties WHERE party_id = ?');
    $stmt->execute([$id]);
    $party = $stmt->fetch();
    if (!$party) jsonResponse(['success' => false, 'message' => 'Party not found.']);
    jsonResponse(['success' => true, 'data' => $party]);
}

// ── CREATE party (admin only) ─────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $name = trim($_POST['party_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!$name) jsonResponse(['success' => false, 'message' => 'Party name required.']);

    try {
        $stmt = $db->prepare('INSERT INTO parties (party_name, description) VALUES (?, ?)');
        $stmt->execute([$name, $desc]);
        jsonResponse(['success' => true, 'message' => 'Party created.', 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Party name already exists.']);
        }
        jsonResponse(['success' => false, 'message' => 'Database error.']);
    }
}

// ── UPDATE party (admin only) ─────────────────────────────────
if ($method === 'POST' && $action === 'update') {
    requireAdmin();
    $id   = (int)($_POST['party_id'] ?? 0);
    $name = trim($_POST['party_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (!$id || !$name) jsonResponse(['success' => false, 'message' => 'Party ID and name required.']);

    try {
        $stmt = $db->prepare('UPDATE parties SET party_name=?, description=? WHERE party_id=?');
        $stmt->execute([$name, $desc, $id]);
        jsonResponse(['success' => true, 'message' => 'Party updated.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Party name already exists.']);
        }
        jsonResponse(['success' => false, 'message' => 'Database error.']);
    }
}

// ── DELETE party (admin only) ─────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['party_id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid party ID.']);

    // Check if party has candidates
    $check = $db->prepare('SELECT COUNT(*) as cnt FROM candidates WHERE party_id = ?');
    $check->execute([$id]);
    if ($check->fetch()['cnt'] > 0) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete party with existing candidates.']);
    }

    $db->prepare('DELETE FROM parties WHERE party_id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Party deleted.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
