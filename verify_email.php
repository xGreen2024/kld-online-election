<?php
require_once __DIR__ . '/functions.php';

$rawToken = trim($_GET['token'] ?? '');
$error = '';
$success = '';

if (!empty($rawToken)) {
    // Hash the raw token from the URL to match token_hash stored in database
    $tokenHash = hash('sha256', $rawToken);

    // Fetch matching verification entry that has not expired
    $stmt = db()->prepare('
        SELECT ev.*, u.id AS user_id 
        FROM email_verifications ev 
        JOIN users u ON ev.user_id = u.id 
        WHERE ev.token_hash = ? AND ev.expires_at > NOW()
    ');
    $stmt->execute([$tokenHash]);
    $verification = $stmt->fetch();

    if ($verification) {
        // Mark user as verified
        $updateUser = db()->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
        $updateUser->execute([$verification['user_id']]);

        // Remove used verification record
        $deleteToken = db()->prepare('DELETE FROM email_verifications WHERE id = ?');
        $deleteToken->execute([$verification['id']]);

        flash('success', 'Your email has been successfully verified! You may now log in.');
        redirect('index.php');
    } else {
        $error = 'This verification link is invalid or has expired.';
    }
} else {
    $error = 'No verification token provided.';
}
