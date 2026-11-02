<?php
require_once __DIR__ . '/functions.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';

if (!$token) {
    $title = 'Invalid Link';
    require 'header.php';
    echo '<div class="alert alert-danger mt-4">No token provided. Please request a new password reset link.</div>';
    echo '<div class="actions mt-3"><a href="forgot_password.php" class="btn btn-primary">Request new link</a></div>';
    require 'footer.php';
    exit;
}

// Hash incoming raw token to match database token_hash
$tokenHash = hash('sha256', $token);

$stmt = db()->prepare('
    SELECT pr.*, u.id AS user_id, u.email 
    FROM password_resets pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.token_hash = ? AND pr.expires_at > NOW()
');
$stmt->execute([$tokenHash]);
$resetRecord = $stmt->fetch();

if (!$resetRecord) {
    $title = 'Invalid Link';
    require 'header.php';
    echo '<div class="alert alert-danger mt-4">This password reset link is invalid or has expired. Please request a new one.</div>';
    echo '<div class="actions mt-3"><a href="forgot_password.php" class="btn btn-primary">Request new link</a></div>';
    require 'footer.php';
    exit;
}

// Handle Form Submission
if (is_post()) {
    csrf_check();
    $newPassword     = post('new_password');
    $confirmPassword = post('confirm_password');

    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Hash the new password securely
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user's password
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([$hashedPassword, $resetRecord['user_id']]);

        // Remove used token from database
        db()->prepare('DELETE FROM password_resets WHERE user_id = ?')
            ->execute([$resetRecord['user_id']]);

        flash('success', 'Your password has been reset successfully. Please log in.');
        redirect('index.php');
    }
}

$title = 'Set New Password';
$narrow = true;
require 'header.php';
?>

<h1>Set new password</h1>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<form action="reset_password.php" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">

    <label class="form-label mt-2">New password</label>
    <input class="form-control" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
    <div class="form-text">At least 8 characters.</div>

    <label class="form-label mt-3">Confirm new password</label>
    <input class="form-control" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

    <button type="submit" class="btn btn-primary w-100 mt-4">Save new password</button>
</form>

<?php require 'footer.php'; ?>
