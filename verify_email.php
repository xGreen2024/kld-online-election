<?php
// Enable error display for debugging if needed
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/functions.php';

$rawToken = trim($_GET['token'] ?? '');
$error = '';
$success = '';

if (!empty($rawToken)) {
    try {
        // 1. Hash the incoming URL raw token (sha256)
        $tokenHash = hash('sha256', $rawToken);

        // 2. Locate matching token in database
        $stmt = db()->prepare('
            SELECT ev.*, u.id AS user_id, u.student_id, u.fullname 
            FROM email_verifications ev 
            JOIN users u ON ev.user_id = u.id 
            WHERE ev.token_hash = ? AND ev.expires_at > NOW()
        ');
        $stmt->execute([$tokenHash]);
        $verification = $stmt->fetch();

        if ($verification) {
            // 3. Mark account verified
            $updateUser = db()->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
            $updateUser->execute([$verification['user_id']]);

            // 4. Remove used verification token
            $deleteToken = db()->prepare('DELETE FROM email_verifications WHERE id = ?');
            $deleteToken->execute([$verification['id']]);

            // Log action if logger function exists
            if (function_exists('log_action')) {
                log_action('verified', "User {$verification['student_id']} verified their email.");
            }

            $success = 'Your email has been successfully verified! You may now log in.';
        } else {
            $error = 'This verification link is invalid or has expired.';
        }
    } catch (Throwable $e) {
        $error = 'Verification failed: ' . $e->getMessage();
    }
} else {
    $error = 'No verification token provided.';
}

$title = 'Verify Email';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5 text-center">
        <?php if ($success): ?>
            <div class="card shadow-sm p-4 mt-4">
                <div class="mb-3 text-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                      <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l4.992-5.99a.75.75 0 0 0-.018-1.042z"/>
                    </svg>
                </div>
                <h3>Email Verified!</h3>
                <p class="text-muted"><?= e($success) ?></p>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary w-100">Proceed to Login</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm p-4 mt-4">
                <div class="mb-3 text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                      <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                </div>
                <h3>Verification Link Issue</h3>
                <div class="alert alert-danger me-0 ms-0"><?= e($error) ?></div>
                <div class="d-flex justify-content-center gap-2 mt-2">
                    <a href="register.php" class="btn btn-success">Register / Resend</a>
                    <a href="index.php" class="btn btn-outline-secondary">Back to Login</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>
