<?php
// ── positions.php  CRUD for positions ────────────────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── LIST all positions ────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    $stmt = $db->query('SELECT * FROM positions ORDER BY display_order, position_name');
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── GET single position ───────────────────────────────────────
if ($method === 'GET' && $action === 'get') {
    $id   = (int)($_GET['id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM positions WHERE position_id = ?');
    $stmt->execute([$id]);
    $pos = $stmt->fetch();
    if (!$pos) jsonResponse(['success' => false, 'message' => 'Position not found.']);
    jsonResponse(['success' => true, 'data' => $pos]);
}

// ── CREATE position (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'create') {
    requireAdmin();
    $name  = trim($_POST['position_name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);

    if (!$name) jsonResponse(['success' => false, 'message' => 'Position name required.']);

    try {
        $stmt = $db->prepare('INSERT INTO positions (position_name, description, display_order) VALUES (?, ?, ?)');
        $stmt->execute([$name, $desc, $order]);
        jsonResponse(['success' => true, 'message' => 'Position created.', 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Position name already exists.']);
        }
        jsonResponse(['success' => false, 'message' => 'Database error.']);
    }
}

// ── UPDATE position (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'update') {
    requireAdmin();
    $id    = (int)($_POST['position_id'] ?? 0);
    $name  = trim($_POST['position_name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $order = (int)($_POST['display_order'] ?? 0);

    if (!$id || !$name) jsonResponse(['success' => false, 'message' => 'Position ID and name required.']);

    try {
        $stmt = $db->prepare('UPDATE positions SET position_name=?, description=?, display_order=? WHERE position_id=?');
        $stmt->execute([$name, $desc, $order, $id]);
        jsonResponse(['success' => true, 'message' => 'Position updated.']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            jsonResponse(['success' => false, 'message' => 'Position name already exists.']);
        }
        jsonResponse(['success' => false, 'message' => 'Database error.']);
    }
}

// ── DELETE position (admin only) ──────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $id = (int)($_POST['position_id'] ?? 0);
    if (!$id) jsonResponse(['success' => false, 'message' => 'Invalid position ID.']);

    // Check if position has candidates
    $check = $db->prepare('SELECT COUNT(*) as cnt FROM candidates WHERE position_id = ?');
    $check->execute([$id]);
    if ($check->fetch()['cnt'] > 0) {
        jsonResponse(['success' => false, 'message' => 'Cannot delete position with existing candidates.']);
    }

    $db->prepare('DELETE FROM positions WHERE position_id = ?')->execute([$id]);
    jsonResponse(['success' => true, 'message' => 'Position deleted.']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
