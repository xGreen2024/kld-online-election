<?php
require_once __DIR__ . '/functions.php';

$error = '';
$sent  = false;
if (is_post()) {
    csrf_check();
    $email = post('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (time() - (int)($_SESSION['verify_sent_at'] ?? 0) < 60) {
        $error = 'Please wait a minute before asking for another link.';
    } else {
        $_SESSION['verify_sent_at'] = time();
        $st = db()->prepare("SELECT * FROM users WHERE email = ? AND role = 'student' AND email_verified = 0 AND is_active = 1");
        $st->execute([$email]);
        if ($u = $st->fetch()) send_verification($u);
        $sent = true;   // same message whether or not the email exists
    }
}

$title = 'Resend Verification';
$narrow = true;
require 'header.php';
?>

    <h1>Verify your email</h1>

    <?php if ($sent): ?>
        <div class="alert alert-success">If that email belongs to an account that still needs verifying, a new link has been sent. It works for 24 hours.</div>
    <?php else: ?>
        <p>Enter the email you registered with and we'll send a new verification link.</p>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form action="resend_verification.php" method="post">
            <?= csrf_field() ?>
            <label class="form-label mt-2">Registered email</label>
            <input class="form-control" type="email" name="email" autocomplete="email" required>
            <button type="submit" class="btn btn-primary w-100 mt-4">Send verification link</button>
        </form>
    <?php endif; ?>

    <div class="actions"><a href="index.php" class="btn btn-outline-primary">Back to login</a></div>

<?php require 'footer.php'; ?>
