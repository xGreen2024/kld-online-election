<?php
require_once __DIR__ . '/functions.php';
require_admin();

if (is_post()) {
    csrf_check();
    $action = post('action');
    if ($action === 'add') {
        $t = post('title'); $b = post('body', false);
        if ($t === '' || mb_strlen($t) > 150 || trim($b) === '' || mb_strlen($b) > 2000) {
            flash('danger', 'Please enter a title (max 150 characters) and a message (max 2000 characters).');
        } else {
            db()->prepare('INSERT INTO announcements (title, body) VALUES (?, ?)')->execute([$t, $b]);
            log_action('announcement_posted', $t);
            flash('success', 'Announcement posted.');
        }
    } elseif ($action === 'toggle') {
        db()->prepare('UPDATE announcements SET is_active = 1 - is_active WHERE id = ?')->execute([(int)post('id')]);
        flash('success', 'Announcement updated.');
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM announcements WHERE id = ?')->execute([(int)post('id')]);
        log_action('announcement_deleted', '#' . (int)post('id'));
        flash('success', 'Announcement deleted.');
    }
    redirect('admin_announcements.php');
}

$rows = db()->query('SELECT * FROM announcements ORDER BY created_at DESC, id DESC')->fetchAll();
$title = 'Announcements';
require 'header.php';
?>

    <h1>Announcements</h1>
    <p class="text-muted">The three newest visible announcements appear on every student's dashboard.</p>

    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <label class="form-label">Title</label>
        <input class="form-control" name="title" maxlength="150" required>
        <label class="form-label mt-3">Message</label>
        <textarea class="form-control" name="body" rows="4" maxlength="2000" required></textarea>
        <button class="btn btn-primary mt-3">Post announcement</button>
    </form>

    <h2>All announcements</h2>
    <?php if (!$rows): ?><p class="text-muted">None yet.</p><?php endif; ?>
    <?php foreach ($rows as $a): ?>
        <div class="announcement <?= $a['is_active'] ? '' : 'opacity-50' ?>">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div><strong><?= e($a['title']) ?></strong>
                    <span class="text-muted small"> &middot; <?= e(fmt_dt($a['created_at'])) ?><?= $a['is_active'] ? '' : ' &middot; hidden' ?></span></div>
                <div class="d-flex gap-1">
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="btn btn-sm btn-outline-primary"><?= $a['is_active'] ? 'Hide' : 'Show' ?></button></form>
                    <form method="post" onsubmit="return confirm('Delete this announcement?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div>
            </div>
            <div><?= nl2br(e($a['body'])) ?></div>
        </div>
    <?php endforeach; ?>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
