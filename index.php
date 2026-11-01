<?php
require_once __DIR__ . '/functions.php';
if (is_logged_in()) redirect(home_for_role());

$error = '';
$sid = '';
$unverified = false;
if (is_post()) {
    csrf_check();
    $sid  = post('student_id');
    $pass = post('password', false);
    $user = find_user($sid);

    if ($user && password_verify($pass, $user['password'])) {
        if (!(int)$user['is_active']) {
            $error = 'This account is deactivated. Please contact an administrator to reactivate it.';
        } elseif ($user['role'] !== 'admin' && !(int)($user['email_verified'] ?? 1)) {
            $error = 'Please verify your email first. We sent a link when you registered.';
            $unverified = true;
        } else {
            session_regenerate_id(true);
            $_SESSION['logged_in']  = true;
            $_SESSION['student_id'] = $user['student_id'];
            $_SESSION['fullname']   = $user['fullname'];
            $_SESSION['role']       = $user['role'] ?? 'student';
            if (!empty($_POST['remember'])) remember_issue((int)$user['id']);

            redirect($_SESSION['role'] === 'admin' ? 'admin.php' : 'home.php');
        }
    } else {
        $error = 'Invalid Student ID or password.';
    }
}

$title = 'Login';
$narrow = true;
require 'header.php';
?>

    <h1>Online Election System</h1>
    <p>Student Council Election 2026</p>

    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?>
        <?php if ($unverified): ?><br><a href="resend_verification.php">Send me a new verification link</a><?php endif; ?></div><?php endif; ?>

    <form action="index.php" method="post">
    <?= csrf_field() ?>

    <label class="form-label mt-2">Student ID</label>
    <input class="form-control" type="text" name="student_id" value="<?= e($sid) ?>" autocomplete="username" required>

    <label class="form-label mt-3">Password</label>
    <input class="form-control" type="password" name="password" autocomplete="current-password" required>
    <div class="text-end mt-1">
        <a href="forgot_password.php" class="small">Forgot your password?</a>
    </div>

    <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
        <label class="form-check-label" for="remember">Keep me logged in on this device (30 days)</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 mt-4">Login</button>
</form>

<p class="mt-4 mb-2">Don't have an account? <a href="register.php">Create one</a></p>
<?php if (results_visible()): ?>
<p class="mb-0"><a href="results.php" class="btn btn-warning-kld btn-sm">See live results</a></p>
<?php endif; ?>

<?php require 'footer.php'; ?>
