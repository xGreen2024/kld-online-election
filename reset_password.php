<?php
require_once __DIR__ . '/functions.php';

$token = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($token) ? $token : '';
$hash  = hash('sha256', $token);

$st = db()->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at >= NOW()');
$st->execute([$hash]);
$rec   = $st->fetch();
$valid = (bool)$rec;

$error = '';
if ($valid && is_post()) {
    csrf_check();
    $new     = post('new_password', false);
    $confirm = post('confirm_password', false);
    if (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'The two passwords do not match.';
    } else {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $rec['user_id']]);
        db()->prepare('DELETE FROM password_resets WHERE token_hash = ?')->execute([$hash]);
        remember_forget((int)$rec['user_id']);
        flash('success', 'Password updated.');
        redirect(is_logged_in() ? 'home.php' : 'index.php');
    }
}


$title = 'Set New Password';
$narrow = true;
require 'header.php';
?>

    <h1>Set new password</h1>

    <?php if (!$valid): ?>
        <div class="alert alert-danger">This reset link is invalid or has expired. Request a new one.</div>
        <div class="actions"><a href="forgot_password.php" class="btn btn-primary">Request new link</a></div>
    <?php else: ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>

        <form action="reset_password.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <label class="form-label mt-2">New password</label>
            <input class="form-control" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
            <div class="form-text">At least 8 characters.</div>

            <label class="form-label mt-3">Confirm new password</label>
            <input class="form-control" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

            <button type="submit" class="btn btn-primary w-100 mt-4">Save new password</button>
        </form>
    <?php endif; ?>

<?php require 'footer.php'; ?>
