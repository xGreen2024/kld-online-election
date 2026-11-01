<?php
require_once __DIR__ . '/functions.php';
require_admin();

if (is_post()) {
    csrf_check();
    $action = post('action');

    if ($action === 'add') {
        $sid = post('student_id');
        if (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $sid)) {
            flash('danger', 'Student ID may only contain letters, numbers and dashes (3-20 characters).');
        } else {
            db()->prepare('INSERT INTO voter_roll (student_id, fullname, section) VALUES (?,?,?)
                           ON DUPLICATE KEY UPDATE fullname = VALUES(fullname), section = VALUES(section)')
                ->execute([$sid, mb_substr(post('fullname'), 0, 100), mb_substr(post('section'), 0, 60)]);
            log_action('roll_entry_added', $sid);
            flash('success', 'Added to the voter list.');
        }

    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM voter_roll WHERE student_id = ?')->execute([post('student_id')]);
        log_action('roll_entry_removed', post('student_id'));
        flash('success', 'Removed from the voter list.');

    } elseif ($action === 'upload') {
        $f = $_FILES['csv'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 1024 * 1024) {
            flash('danger', 'Please choose a CSV file of 1 MB or less.');
        } else {
            $h = fopen($f['tmp_name'], 'r');
            $ins = db()->prepare('INSERT INTO voter_roll (student_id, fullname, section) VALUES (?,?,?)
                                  ON DUPLICATE KEY UPDATE fullname = VALUES(fullname), section = VALUES(section)');
            $ok = $bad = 0;
            db()->beginTransaction();
            while (($row = fgetcsv($h)) !== false && $ok + $bad < 10000) {
                $row = array_map(fn($c) => trim((string)$c, " \t\n\r\0\x0B\xEF\xBB\xBF"), $row);
                if (!$row || $row[0] === '' || strcasecmp($row[0], 'student_id') === 0) continue;
                if (!preg_match('/^[A-Za-z0-9-]{3,20}$/', $row[0])) { $bad++; continue; }
                $ins->execute([$row[0], mb_substr($row[1] ?? '', 0, 100), mb_substr($row[2] ?? '', 0, 60)]);
                $ok++;
            }
            db()->commit();
            fclose($h);
            log_action('roll_uploaded', "$ok added/updated, $bad skipped");
            flash($bad ? 'warning' : 'success', "$ok entries saved" . ($bad ? ", $bad rows skipped (invalid Student ID)." : '.'));
        }
    }
    redirect('admin_roll.php');
}

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 50;
$like = '%' . addcslashes($q, '%_\\') . '%';
$cnt = db()->prepare('SELECT COUNT(*) FROM voter_roll WHERE student_id LIKE ? OR fullname LIKE ? OR section LIKE ?');
$cnt->execute([$like, $like, $like]);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$ls = db()->prepare("SELECT r.*, (u.id IS NOT NULL) AS registered FROM voter_roll r LEFT JOIN users u ON u.student_id = r.student_id
                     WHERE r.student_id LIKE ? OR r.fullname LIKE ? OR r.section LIKE ? ORDER BY r.student_id LIMIT $per OFFSET " . (($page - 1) * $per));
$ls->execute([$like, $like, $like]);
$rows = $ls->fetchAll();
$all = (int)db()->query('SELECT COUNT(*) FROM voter_roll')->fetchColumn();

$title = 'Voter List';
require 'header.php';
?>

    <h1>Voter list</h1>
    <?php if ($all === 0): ?>
        <div class="alert alert-info">The list is empty, so <strong>anyone</strong> can register. Add or upload the official student list to restrict registration to those Student IDs.</div>
    <?php else: ?>
        <div class="alert alert-success">Registration is limited to the <?= $all ?> Student ID<?= $all === 1 ? '' : 's' ?> on this list.</div>
    <?php endif; ?>

    <h2 class="mt-0">Upload a CSV</h2>
    <p class="text-muted">Columns: <code>student_id, fullname, section</code>. A header row is fine. Existing IDs are updated.</p>
    <form method="post" enctype="multipart/form-data" class="d-flex flex-wrap gap-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <input class="form-control" style="max-width:24rem" type="file" name="csv" accept=".csv,text/csv" required>
        <button class="btn btn-primary">Upload</button>
    </form>

    <h2>Add one student</h2>
    <form method="post" class="row g-2">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="col-md-3"><input class="form-control" name="student_id" placeholder="Student ID" maxlength="20" required></div>
        <div class="col-md-4"><input class="form-control" name="fullname" placeholder="Full name (optional)" maxlength="100"></div>
        <div class="col-md-3"><input class="form-control" name="section" placeholder="Section (e.g. BSIS - 204)" maxlength="60"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-primary">Add</button></div>
    </form>

    <h2>Students on the list</h2>
    <form method="get" class="d-flex gap-2 mb-3">
        <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search ID, name or section">
        <button class="btn btn-outline-primary">Search</button>
    </form>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Student ID</th><th>Name</th><th>Section</th><th>Registered?</th><th></th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="5" class="text-muted">Nothing to show.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= e($r['student_id']) ?></td><td><?= e($r['fullname']) ?></td><td><?= e($r['section']) ?></td>
                    <td><?= $r['registered'] ? '<span class="badge text-bg-success">Yes</span>' : '<span class="badge text-bg-secondary">No</span>' ?></td>
                    <td class="text-end">
                        <form method="post" onsubmit="return confirm('Remove from the list?');">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="student_id" value="<?= e($r['student_id']) ?>">
                            <button class="btn btn-sm btn-outline-danger">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <nav class="d-flex gap-2 align-items-center">
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-primary" href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>">Previous</a><?php endif; ?>
            <span class="text-muted small">Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-primary" href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
