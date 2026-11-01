<?php
require_once __DIR__ . '/functions.php';

$error = '';
$sent  = false;
$link  = null;
if (is_post()) {
    csrf_check();
    $email = post('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $link = make_reset_link($email);
        if ($link) {
                    $safe = e($link);
                    send_mail(
                    $email,
                    'Reset your Online Election password',
                    "<p>Hello,</p>
                        <p>We received a request to reset your Online Election password. Click the button below within <strong>one hour</strong>:</p>
                        <p><a href=\"$safe\" style=\"background:#0b6b3a;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none;\">Reset my password</a></p>
                        <p>Or copy this link into your browser:<br>$safe</p>
                        <p>If you didn't ask for this, you can ignore this email.</p>",
                        "Use this link within one hour to set a new password:\n\n$link\n\nIf you didn't ask for this, ignore this email."
    );
}
        $sent = true; 
    }
}

$title = 'Forgot Password';
$narrow = true;
require 'header.php';
?>

    <h1>Forgot password</h1>

    <?php if ($sent): ?>
        <div class="alert alert-success">If that email is registered, a reset link has been sent to it. The link works for one hour.</div>
        <?php if (RESET_DEMO_MODE && $link): ?>
    <div class="alert alert-info">
        Demo mode: <a href="<?= e($link) ?>">Open the reset link</a>
    </div>
<?php endif; ?>
    <?php else: ?>
        <p>Enter the email you registered with and we'll send you a link to set a new password.</p>

        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <form action="forgot_password.php" method="post">
            <?= csrf_field() ?>
            <label class="form-label mt-2">Registered email</label>
            <input class="form-control" type="email" name="email" autocomplete="email" required>
            <button type="submit" class="btn btn-primary w-100 mt-4">Send reset link</button>
        </form>
    <?php endif; ?>

    <div class="actions">
        <?php if (is_logged_in()): ?>
            <a href="home.php" class="btn btn-outline-primary">Back to dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-outline-primary">Back to login</a>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>
