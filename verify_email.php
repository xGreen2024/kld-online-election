<?php
require_once __DIR__ . '/functions.php';

$token = $_GET['token'] ?? '';
$token = is_string($token) ? $token : '';

$st = db()->prepare('SELECT user_id FROM email_verifications WHERE token_hash = ? AND expires_at >= NOW()');
$st->execute([hash('sha256', $token)]);
$rec = $st->fetch();

if ($rec) {
    db()->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$rec['user_id']]);
    db()->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$rec['user_id']]);
    flash('success', 'Email verified! You can now log in.');
    redirect('index.php');
}

$title = 'Verify Email';
$narrow = true;
require 'header.php';
?>

    <h1>Verification link problem</h1>
    <div class="alert alert-danger">This verification link is invalid or has expired.</div>
    <div class="actions">
        <a href="resend_verification.php" class="btn btn-primary">Send a new link</a>
        <a href="index.php" class="btn btn-outline-primary">Back to login</a>
    </div>

<?php require 'footer.php'; ?>
