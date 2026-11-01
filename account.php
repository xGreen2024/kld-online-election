<?php
require_once __DIR__ . '/functions.php';
require_login();

$me = find_user($_SESSION['student_id']);
if (!$me) redirect('logout.php');

// Seconds left before the person may edit their info again (0 = allowed now)
function info_wait_seconds(int $id): int {
    $st = db()->prepare('SELECT COALESCE(GREATEST(0, 86400 - TIMESTAMPDIFF(SECOND, info_updated_at, NOW())), 0)
                         FROM users WHERE id = ?');
    $st->execute([$id]);
    return (int)$st->fetchColumn();
}
function render_errors(array $list): void {
    if (!$list) return;
    echo '<div class="alert alert-danger mb-3">';
    foreach ($list as $err) echo '<div>' . e($err) . '</div>';
    echo '</div>';
}

$errors = ['info' => [], 'password' => [], 'deactivate' => []];
$old    = ['fullname' => $me['fullname'], 'email' => $me['email']];

if (is_post()) {
    csrf_check();
    $action  = post('action');
    $current = post('current_password', false);
    if (!isset($errors[$action])) redirect('account.php');

    if ($action === 'info') {
        $old = ['fullname' => post('fullname'), 'email' => post('email')];
    }

    if (!password_verify($current, $me['password'])) {
        $errors[$action][] = 'Your current password is incorrect.';

    } elseif ($action === 'info') {
        if ($old['fullname'] === '' || mb_strlen($old['fullname']) > 100) $errors['info'][] = 'Please enter your full name.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors['info'][] = 'Please enter a valid email address.';
        if (!$errors['info'] && $old['fullname'] === $me['fullname'] && strcasecmp($old['email'], $me['email']) === 0) {
            $errors['info'][] = 'You have not changed anything.';
        }

        if (!$errors['info']) {
            try {
                // The 24-hour rule is enforced inside the query itself
                $st = db()->prepare('UPDATE users SET fullname = ?, email = ?, info_updated_at = NOW()
                                     WHERE id = ? AND (info_updated_at IS NULL OR info_updated_at <= NOW() - INTERVAL 24 HOUR)');
                $st->execute([$old['fullname'], $old['email'], $me['id']]);
                if ($st->rowCount() === 1) {
                    $_SESSION['fullname'] = $old['fullname'];
                    flash('success', 'Your information has been updated. You can change it again in 24 hours.');
                    redirect('account.php');
                }
                $errors['info'][] = 'You can only update your information once every 24 hours.';
            } catch (PDOException $e) {
                $errors['info'][] = $e->getCode() === '23000'
                    ? 'That email is already registered.'
                    : 'Could not save your changes. Please try again.';
            }
        }

    } elseif ($action === 'password') {
        $new     = post('new_password', false);
        $confirm = post('confirm_password', false);
        if (strlen($new) < 8) {
            $errors['password'][] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors['password'][] = 'The two passwords do not match.';
        } elseif (password_verify($new, $me['password'])) {
            $errors['password'][] = 'Your new password must be different from the current one.';
        } else {
            db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$me['id']]);
            remember_forget((int)$me['id']);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            redirect('account.php');
        }

    } elseif ($action === 'deactivate') {
        $lastAdmin = $me['role'] === 'admin'
            && (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn() <= 1;

        if (empty($_POST['confirm_deactivate'])) {
            $errors['deactivate'][] = 'Please tick the confirmation box.';
        } elseif ($lastAdmin) {
            $errors['deactivate'][] = 'You are the only active administrator, so this account cannot be deactivated.';
        } else {
            db()->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$me['id']]);
            remember_forget((int)$me['id']);
            db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$me['id']]);

            // Log the user out
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
            }
            session_destroy();
            session_start();
            flash('success', 'Your account has been deactivated.');
            redirect('index.php');
        }
    }
}

$wait = info_wait_seconds((int)$me['id']);
$h = intdiv($wait, 3600);
$m = intdiv($wait % 3600, 60);

$title  = 'Account Settings';
$narrow = true;
require 'header.php';
?>

    <h1>Account settings</h1>

    <h2 class="mt-0">Your information</h2>
    <dl class="info-list">
        <div><dt>Full name</dt><dd><?= e($me['fullname']) ?></dd></div>
        <div><dt>ID</dt><dd><?= e($me['student_id']) ?></dd></div>
        <div><dt>Email</dt><dd><?= e($me['email']) ?></dd></div>
        <div><dt>Account type</dt><dd><?= e(ucfirst($me['role'])) ?></dd></div>
        <div><dt>Member since</dt><dd><?= e(date('F j, Y', strtotime($me['created_at']))) ?></dd></div>
    </dl>

    <hr>

    <h2 class="mt-0">Update information</h2>
    <?php if ($wait > 0): ?>
        <div class="alert alert-warning">
            You changed your information recently. You can update it again in
            <strong><?= $h ?>h <?= $m ?>m</strong>.
        </div>
    <?php else: ?>
        <p class="text-muted">You can change your name and email. After saving, you must wait 24 hours before changing them again. Your ID cannot be changed.</p>
    <?php endif; ?>
    <?php render_errors($errors['info']); ?>

    <form action="account.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="info">
        <fieldset <?= $wait > 0 ? 'disabled' : '' ?>>
            <label class="form-label mt-2">Full name</label>
            <input class="form-control" type="text" name="fullname" value="<?= e($old['fullname']) ?>" maxlength="100" required>

            <label class="form-label mt-3">Email</label>
            <input class="form-control" type="email" name="email" value="<?= e($old['email']) ?>" required>

            <label class="form-label mt-3">Current password</label>
            <input class="form-control" type="password" name="current_password" autocomplete="current-password" required>
            <div class="form-text">Needed to confirm it's you.</div>

            <button type="submit" class="btn btn-primary mt-3">Save changes</button>
        </fieldset>
    </form>

    <hr>

    <h2 class="mt-0">Change password</h2>
    <?php render_errors($errors['password']); ?>

    <form action="account.php" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="password">

        <label class="form-label mt-2">Current password</label>
        <input class="form-control" type="password" name="current_password" autocomplete="current-password" required>

        <label class="form-label mt-3">New password</label>
        <input class="form-control" type="password" name="new_password" minlength="8" autocomplete="new-password" required>
        <div class="form-text">At least 8 characters.</div>

        <label class="form-label mt-3">Confirm new password</label>
        <input class="form-control" type="password" name="confirm_password" minlength="8" autocomplete="new-password" required>

        <button type="submit" class="btn btn-primary mt-3">Update password</button>
    </form>

    <div class="danger-zone">
        <h2 class="mt-0">Deactivate account</h2>
        <p>
            Deactivating logs you out and stops you from logging in. Your account is not erased: your
            voting record is kept, so nobody can vote twice. An administrator can reactivate it.
        </p>
        <?php render_errors($errors['deactivate']); ?>

        <form action="account.php" method="post"
              onsubmit="return confirm('Deactivate your account? You will be logged out.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="deactivate">

            <label class="form-label">Current password</label>
            <input class="form-control" type="password" name="current_password" autocomplete="current-password" required>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="confirm_deactivate" id="confirm_deactivate" value="1">
                <label class="form-check-label" for="confirm_deactivate">
                    I understand I will be logged out and cannot log in until my account is reactivated.
                </label>
            </div>

            <button type="submit" class="btn btn-danger mt-3">Deactivate my account</button>
        </form>
    </div>

<?php require 'footer.php'; ?>