<?php
require_once __DIR__ . '/functions.php';
if (is_logged_in()) redirect('home.php');

$errors = [];
$old = ['fullname' => '', 'student_id' => '', 'email' => ''];

if (is_post()) {
    csrf_check();
    $old = ['fullname' => post('fullname'), 'student_id' => post('student_id'), 'email' => post('email')];
    $pass = post('password', false);
    $confirm = post('confirm_password', false);

    if ($old['fullname'] === '' || mb_strlen($old['fullname']) > 100) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $old['student_id'])) $errors[] = 'Student ID may only contain letters, numbers and dashes (3-20 characters).';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $confirm) $errors[] = 'The two passwords do not match.';

    if (!$errors && roll_is_active() && !roll_entry($old['student_id'])) {
        $errors[] = 'That Student ID is not on the official voter list. Please contact the election committee.';
    }

    if (!$errors) {
    try {
        db()->prepare('INSERT INTO users (student_id, fullname, email, password, email_verified) VALUES (?, ?, ?, ?, 0)')
            ->execute([$old['student_id'], $old['fullname'], $old['email'], password_hash($pass, PASSWORD_DEFAULT)]);
        $newUser = find_user($old['student_id']);
        $sent = $newUser ? send_verification($newUser) : false;
        flash($sent ? 'success' : 'warning', $sent
            ? 'Account created! We sent a verification link to your email. Verify it, then log in.'
            : 'Account created, but we could not send the verification email. Use "Send me a new verification link" on the login page, or ask an administrator.');
        redirect('index.php');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {   // duplicate key
            $errors[] = find_user($old['student_id'])
                ? 'That Student ID is already registered.'
                : 'That email is already registered.';
        } else {
            $errors[] = 'Could not save your account. Please try again.';
        }
    }
}
}

$title = 'Registration';
$narrow = true;
require 'header.php';
?>

    <h1>Student Registration</h1>
    <p>Create your election account</p>

    <?php if ($errors): ?>
        <div class="alert alert-danger mb-3">
            <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="register.php" method="post">
        <?= csrf_field() ?>

        <label class="form-label mt-2">Full Name</label>
        <input class="form-control" type="text" name="fullname" value="<?= e($old['fullname']) ?>" maxlength="100" required>

        <label class="form-label mt-3">Student ID</label>
        <input class="form-control" type="text" name="student_id" value="<?= e($old['student_id']) ?>" maxlength="20" required>

        <label class="form-label mt-3">Email</label>
        <input class="form-control" type="email" name="email" value="<?= e($old['email']) ?>" required>

        <label class="form-label mt-3">Password</label>
        <input class="form-control" type="password" name="password" minlength="8" autocomplete="new-password" required>
        <div class="form-text">At least 8 characters.</div>

        <label class="form-label mt-3">Confirm Password</label>
        <input class="form-control" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

        <button type="submit" class="btn btn-primary w-100 mt-4">Register</button>
    </form>

    <p class="mt-4 mb-0">Already have an account? <a href="index.php">Log in</a></p>

<script>
const pw = document.querySelector('[name="password"]');
const cpw = document.querySelector('[name="confirm_password"]');
function checkMatch() {
    cpw.setCustomValidity(pw.value !== cpw.value ? 'Passwords do not match.' : '');
}
pw.addEventListener('input', checkMatch);
cpw.addEventListener('input', checkMatch);
</script>

<?php require 'footer.php'; ?>