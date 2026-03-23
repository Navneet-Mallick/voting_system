<?php
// ── Database Configuration ────────────────────────────────────
// Use environment variables in production (Render), fallback to local values
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'voting_system');

// ── Production Settings ───────────────────────────────────────
// Uncomment these in production environment
// ini_set('display_errors', '0');
// ini_set('log_errors', '1');
// ini_set('error_log', '/path/to/logs/php-error.log');
// ini_set('session.cookie_secure', '1');      // Requires HTTPS
// ini_set('session.cookie_httponly', '1');
// ini_set('session.cookie_samesite', 'Strict');
// ini_set('session.use_only_cookies', '1');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $port = defined('DB_PORT') ? DB_PORT : 3306;
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // In production, log error instead of displaying
            error_log('Database connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed. Please contact administrator.']));
        }
    }
    return $pdo;
}

// Session helper
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: index.html');
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.html');
        exit;
    }
}

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
