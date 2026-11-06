<?php
require_once __DIR__ . '/functions.php';

if (is_logged_in()) redirect(home_for_role());

$error = '';

if (is_post()) {
    csrf_check();

    $studentId = trim(post('student_id'));
    $fullname  = trim(post('fullname'));
    $email     = trim(post('email'));
    $password  = post('password');
    $confirm   = post('confirm_password');

    // 1. Enforce official @kld.edu.ph email domain
    if (!is_kld_email($email)) {
        $error = 'Only official @kld.edu.ph email addresses are allowed to register.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (find_user($studentId)) {
        $error = 'A user with this Student ID is already registered.';
    } else {
        // Verify voter roll if active
        if (roll_is_active() && !roll_entry($studentId)) {
            $error = 'Your Student ID is not listed in the official voter roll.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = db()->prepare('INSERT INTO users (student_id, fullname, email, password_hash, role, has_voted, is_active) VALUES (?, ?, ?, ?, ?, 0, 1)');
                $stmt->execute([$studentId, $fullname, strtolower($email), $hash, 'student']);

                log_action('registered', "User $studentId registered.");

                // Auto-login newly registered user
                $_SESSION['logged_in']  = true;
                $_SESSION['student_id'] = $studentId;
                $_SESSION['fullname']   = $fullname;
                $_SESSION['role']       = 'student';

                flash('success', 'Registration successful! Welcome to KLD Online Election.');
                redirect('home.php');
            } catch (Throwable $e) {
                $error = 'Registration failed due to a system error. Please try again.';
            }
        }
    }
}

$title = 'Register';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h2 class="mb-3">Student Registration</h2>
        <p class="text-muted">Register using your official <strong>@kld.edu.ph</strong> email address.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" placeholder="e.g. 2026-12345" value="<?= e(post('student_id')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" placeholder="First Name Last Name" value="<?= e(post('fullname')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">KLD Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="student@kld.edu.ph" value="<?= e(post('email')) ?>" pattern="[a-zA-Z0-9._%+-]+@kld\.edu\.ph$" title="Please enter a valid @kld.edu.ph email address" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register Account</button>
        </form>

        <p class="mt-3 text-center small">
            Already have an account? <a href="index.php">Log in here</a>
        </p>
    </div>
</div>

<?php require 'footer.php'; ?>
