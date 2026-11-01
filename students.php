<?php
require_once __DIR__ . '/functions.php';
require_admin();

if (is_post()) {
    csrf_check();
    $action = post('action');
    $sid    = post('student_id');
    $st = db()->prepare("SELECT * FROM users WHERE student_id = ? AND role = 'student'");
    $st->execute([$sid]);
    $u = $st->fetch();

    if (!$u) {
        flash('danger', 'Student not found.');
    } elseif ($action === 'deactivate') {
        db()->prepare('UPDATE users SET is_active = 0 WHERE id = ?')->execute([$u['id']]);
        db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$u['id']]);
        remember_forget((int)$u['id']);
        log_action('student_deactivated', $sid);
        flash('success', "Account $sid deactivated. Their voting record is kept.");
    } elseif ($action === 'reactivate') {
        db()->prepare('UPDATE users SET is_active = 1 WHERE id = ?')->execute([$u['id']]);
        log_action('student_reactivated', $sid);
        flash('success', "Account $sid reactivated.");
    } elseif ($action === 'verify') {
        db()->prepare('UPDATE users SET email_verified = 1 WHERE id = ?')->execute([$u['id']]);
        db()->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$u['id']]);
        log_action('student_verified', $sid);
        flash('success', "Email for $sid marked as verified.");
    } elseif ($action === 'send_reset') {
        $ok = send_reset_email($u['email']);
        log_action('reset_link_sent', $sid);
        flash($ok ? 'success' : 'danger', $ok ? "Password reset link sent to {$u['email']}." : 'Could not send the email (is the account active?).');
    }
    redirect('students.php?' . http_build_query(array_filter(['q' => $_GET['q'] ?? '', 'status' => $_GET['status'] ?? '', 'page' => $_GET['page'] ?? ''])));
}

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 25;

$where = ["role = 'student'"]; $args = [];
if ($q !== '') {
    $where[] = '(student_id LIKE ? OR fullname LIKE ? OR email LIKE ?)';
    $like = '%' . addcslashes($q, '%_\\') . '%';
    array_push($args, $like, $like, $like);
}
switch ($status) {
    case 'active':      $where[] = 'is_active = 1'; break;
    case 'deactivated': $where[] = 'is_active = 0'; break;
    case 'voted':       $where[] = 'has_voted = 1'; break;
    case 'not_voted':   $where[] = 'has_voted = 0'; break;
    case 'unverified':  $where[] = 'email_verified = 0'; break;
    default: $status = 'all';
}
$W = implode(' AND ', $where);

$cnt = db()->prepare("SELECT COUNT(*) FROM users WHERE $W"); $cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page  = min($page, $pages);

$ls = db()->prepare("SELECT student_id, fullname, email, has_voted, is_active, email_verified FROM users WHERE $W ORDER BY fullname LIMIT $per OFFSET " . (($page - 1) * $per));
$ls->execute($args);
$students = $ls->fetchAll();

$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status === 'all' ? '' : $status], $o)));
$title = 'Manage Students';
require 'header.php';
?>

    <h1>Manage students</h1>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search by name, ID or email"></div>
        <div class="col-md-4">
            <select class="form-select" name="status">
                <?php foreach (['all' => 'All students', 'active' => 'Active', 'deactivated' => 'Deactivated', 'voted' => 'Voted', 'not_voted' => 'Not voted yet', 'unverified' => 'Email not verified'] as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Filter</button></div>
    </form>

    <p class="text-muted"><?= $total ?> student<?= $total === 1 ? '' : 's' ?> found.</p>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Student ID</th><th>Name</th><th>Email</th><th>Voted?</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$students): ?><tr><td colspan="6" class="text-muted">No students match.</td></tr><?php endif; ?>
            <?php foreach ($students as $u): ?>
                <tr>
                    <td><?= e($u['student_id']) ?></td>
                    <td><?= e($u['fullname']) ?></td>
                    <td><?= e($u['email']) ?><?= !(int)$u['email_verified'] ? ' <span class="badge text-bg-warning">Unverified</span>' : '' ?></td>
                    <td><?= $u['has_voted'] ? '<span class="badge text-bg-success">Voted</span>' : '<span class="badge text-bg-secondary">Not yet</span>' ?></td>
                    <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-warning">Deactivated</span>' ?></td>
                    <td class="text-end">
                        <form method="post" action="students.php<?= e($qs(['page' => $page > 1 ? $page : ''])) ?>" class="d-inline-flex gap-1 flex-wrap justify-content-end">
                            <?= csrf_field() ?><input type="hidden" name="student_id" value="<?= e($u['student_id']) ?>">
                            <?php if ($u['is_active']): ?>
                                <button name="action" value="send_reset" class="btn btn-sm btn-outline-primary">Send reset link</button>
                                <?php if (!(int)$u['email_verified']): ?>
                                    <button name="action" value="verify" class="btn btn-sm btn-outline-primary">Mark verified</button>
                                <?php endif; ?>
                                <button name="action" value="deactivate" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deactivate this account?');">Deactivate</button>
                            <?php else: ?>
                                <button name="action" value="reactivate" class="btn btn-sm btn-outline-primary">Reactivate</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav class="d-flex gap-2 align-items-center">
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-primary" href="students.php<?= e($qs(['page' => $page - 1])) ?>">Previous</a><?php endif; ?>
            <span class="text-muted small">Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-primary" href="students.php<?= e($qs(['page' => $page + 1])) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
