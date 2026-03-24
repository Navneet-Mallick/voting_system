<?php
// ── users.php  CRUD for user management (admin) ───────────────
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db     = getDB();

// ── READ all users ────────────────────────────────────────────
if ($method === 'GET' && $action === 'list') {
    requireAdmin();
    $stmt = $db->query(
        "SELECT u.user_id, u.full_name, u.email, u.is_active, u.created_at,
                r.role_name
         FROM users u JOIN roles r ON u.role_id = r.role_id
         ORDER BY u.created_at DESC"
    );
    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

// ── TOGGLE active status ──────────────────────────────────────
if ($method === 'POST' && $action === 'toggle') {
    requireAdmin();
    $uid = (int)($_POST['user_id'] ?? 0);
    if (!$uid) jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
    $db->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?")->execute([$uid]);
    jsonResponse(['success' => true, 'message' => 'User status toggled.']);
}

// ── UPDATE role ───────────────────────────────────────────────
if ($method === 'POST' && $action === 'role') {
    requireAdmin();
    $uid     = (int)($_POST['user_id'] ?? 0);
    $role_id = (int)($_POST['role_id'] ?? 2);
    $db->prepare("UPDATE users SET role_id = ? WHERE user_id = ?")->execute([$role_id, $uid]);
    jsonResponse(['success' => true, 'message' => 'Role updated.']);
}

// ── DELETE user ───────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    requireAdmin();
    $uid = (int)($_POST['user_id'] ?? 0);
    if (!$uid) jsonResponse(['success' => false, 'message' => 'Invalid user ID.']);
    // Prevent deleting own account
    if ($uid === (int)$_SESSION['user_id'])
        jsonResponse(['success' => false, 'message' => 'You cannot delete your own account.']);
    // Delete votes first (FK constraint), then user
    $db->prepare("DELETE FROM votes WHERE user_id = ?")->execute([$uid]);
    $db->prepare("DELETE FROM users WHERE user_id = ?")->execute([$uid]);
    jsonResponse(['success' => true, 'message' => 'User deleted successfully.']);
}

// ── STATS dashboard ───────────────────────────────────────────
if ($method === 'GET' && $action === 'stats') {
    requireAdmin();
    $stats = [];
    $stats['total_users']     = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['total_elections'] = $db->query("SELECT COUNT(*) FROM elections")->fetchColumn();
    $stats['active_elections']= $db->query("SELECT COUNT(*) FROM elections WHERE status='active'")->fetchColumn();
    $stats['total_votes']     = $db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
    jsonResponse(['success' => true, 'data' => $stats]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
