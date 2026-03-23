<?php
// auth.php (handles login, register, logout)
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── REGISTER ─────────────────────────────────────────────────
if ($action === 'register') {
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$name || !$email || !$pass)
        jsonResponse(['success' => false, 'message' => 'All fields are required.']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        jsonResponse(['success' => false, 'message' => 'Invalid email address.']);
    if (strlen($pass) < 6)
        jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);

    $db   = getDB();
    $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch())
        jsonResponse(['success' => false, 'message' => 'Email already registered.']);

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $ins  = $db->prepare(
        'INSERT INTO users (full_name, email, password_hash, role_id) VALUES (?, ?, ?, 2)'
    );
    $ins->execute([$name, $email, $hash]);
    jsonResponse(['success' => true, 'message' => 'Registration successful! Please log in.']);
}

// ── LOGIN ─────────────────────────────────────────────────────
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$email || !$pass) {
        jsonResponse(['success' => false, 'message' => 'Email and password required.']);
    }

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT u.user_id, u.full_name, u.password_hash, u.is_active, r.role_name
         FROM users u JOIN roles r ON u.role_id = r.role_id
         WHERE u.email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid email or password.']);
    }
    if (!$user['is_active']) {
        jsonResponse(['success' => false, 'message' => 'Your account has been deactivated.']);
    }

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $email;
    $_SESSION['role']      = $user['role_name'];

    jsonResponse([
        'success' => true,
        'role'     => $user['role_name'],
        'name'     => $user['full_name'],
        'message'  => 'Login successful!'
    ]);
}

// ── LOGOUT ────────────────────────────────────────────────────
if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out.']);
}

// ── SESSION CHECK ─────────────────────────────────────────────
if ($action === 'check') {
    if (!empty($_SESSION['user_id'])) {
        jsonResponse([
            'loggedIn' => true,
            'name'     => $_SESSION['full_name'],
            'role'     => $_SESSION['role'],
            'user_id'  => $_SESSION['user_id']
        ]);
    }
    jsonResponse(['loggedIn' => false]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
