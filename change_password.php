<?php
/**
 * Password Change Utility
 * Users can change their own password when logged in
 */

require_once 'php/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Check if user is logged in
$logged_in = !empty($_SESSION['user_id']);
$user_email = $_SESSION['email'] ?? '';
$user_name = $_SESSION['full_name'] ?? '';
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Must be logged in to change password
    if (!$logged_in) {
        $message = 'You must be logged in to change password.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($current_password) || empty($new_password)) {
            $message = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } else {
            try {
                $db = getDB();
                
                // Verify current password
                $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($current_password, $user['password_hash'])) {
                    $message = 'Current password is incorrect.';
                } else {
                    // Update password
                    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                    $stmt->execute([$password_hash, $_SESSION['user_id']]);
                    
                    $message = 'Password changed successfully!';
                    $success = true;
                }
            } catch (Exception $e) {
                $message = 'Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - VoteSecure</title>
    <link rel="icon" type="image/svg+xml" href="images/favicon.svg">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-box" style="max-width: 500px;">
        <div class="auth-logo">
            <img src="images/logo.svg" alt="VoteSecure Logo" class="logo-img">
            <h1>Change Password</h1>
            <?php if ($logged_in): ?>
                <p>Update password for: <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
            <?php else: ?>
                <p>Please login first to change your password</p>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
            <div style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; <?php echo $success ? 'background: #d1fae5; color: #065f46; border: 1px solid #10b981;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #ef4444;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!$logged_in): ?>
            <div style="text-align: center;">
                <p style="margin-bottom: 1rem; color: var(--muted);">You must be logged in to change your password.</p>
                <a href="index.html" class="btn btn-primary">Go to Login</a>
            </div>
        <?php elseif (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" class="form-control" name="current_password" 
                       placeholder="Enter current password" required autofocus>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <input type="password" class="form-control" name="new_password" 
                       placeholder="Min 6 characters" required>
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" 
                       placeholder="Re-enter new password" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">
                Change Password
            </button>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="<?php echo $is_admin ? 'admin.html' : 'dashboard.html'; ?>" style="color: var(--primary); font-size: .9rem;">← Back to Dashboard</a>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align: center; margin-top: 1.5rem;">
            <a href="<?php echo $is_admin ? 'admin.html' : 'dashboard.html'; ?>" class="btn btn-success">Back to Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
