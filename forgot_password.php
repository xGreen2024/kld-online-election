<?php
require_once __DIR__ . '/functions.php';

if (is_logged_in()) redirect(home_for_role());

$error = '';
$success = '';

if (is_post()) {
    csrf_check();
    $email = trim(post('email'));

    if (!is_kld_email($email)) {
        $error = 'Only official @kld.edu.ph email addresses are recognized.';
    } else {
        if (send_reset_email($email)) {
            $success = 'If that account exists, a password reset link has been sent to your email address.';
        } else {
            $error = 'Could not process password reset request. Please check your email address.';
        }
    }
}

$title = 'Forgot Password';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <h2 class="mb-3">Forgot Password</h2>
        <p class="text-muted">Enter your registered <strong>@kld.edu.ph</strong> email address to receive a password reset link.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <form action="forgot_password.php" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">KLD Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="student@kld.edu.ph" pattern="[a-zA-Z0-9._%+-]+@kld\.edu\.ph$" title="Please enter a valid @kld.edu.ph email address" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>

        <p class="mt-3 text-center small">
            <a href="index.php">← Back to Login</a>
        </p>
    </div>
</div>

<?php require 'footer.php'; ?>
