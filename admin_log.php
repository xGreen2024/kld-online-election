<?php
require_once __DIR__ . '/functions.php';
require_admin();

$rows = db()->query('SELECT * FROM activity_log ORDER BY id DESC LIMIT 200')->fetchAll();
$title = 'Activity Log';
require 'header.php';
?>

    <h1>Activity log</h1>
    <p class="text-muted">The 200 most recent admin actions.</p>

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Details</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="4" class="text-muted">Nothing logged yet.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="text-nowrap"><?= e(fmt_dt($r['created_at'])) ?></td>
                    <td><?= e($r['actor_name'] !== '' ? $r['actor_name'] : $r['actor_id']) ?> <span class="text-muted small">(<?= e($r['actor_id']) ?>)</span></td>
                    <td><code><?= e($r['action']) ?></code></td>
                    <td><?= e($r['details']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
