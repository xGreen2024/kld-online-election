<?php
require_once __DIR__ . '/functions.php';

$error = '';
$sent = false;

if (is_post()) {
    csrf_check();
    $email = post('email');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        if (send_reset_email($email)) {
            $sent = true;
        } else {
            $error = 'Failed to send reset email. Please try again later.';
        }
    }
}

$title = 'Forgot Password';
$narrow = true;
require 'header.php';
?>

<h1>Forgot password</h1>

<?php if ($sent): ?>
    <div class="alert alert-success">If that email is registered, a reset link has been sent to it. The link works for one hour.</div>
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
