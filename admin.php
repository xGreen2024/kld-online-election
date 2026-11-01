<?php
require_once __DIR__ . '/functions.php';
require_admin();

if (is_post() && post('action') === 'reactivate') {
    csrf_check();
    db()->prepare("UPDATE users SET is_active = 1 WHERE student_id = ? AND role = 'student'")
        ->execute([post('student_id')]);
    log_action('student_reactivated', post('student_id'));
    flash('success', 'Account reactivated.');
    redirect('admin.php');
}

$totalStudents = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalVotes    = (int)db()->query('SELECT COUNT(*) FROM ballots')->fetchColumn();
$turnout       = $totalStudents ? round($totalVotes / $totalStudents * 100, 1) : 0;
$results       = tally_votes();
$students = db()->query("SELECT student_id, fullname, email, has_voted, is_active
                         FROM users WHERE role = 'student' ORDER BY fullname")->fetchAll();
$title = 'Admin Dashboard';
require 'header.php';
?>

    <h1>Admin Dashboard</h1>
    <p class="lead-text">Student Council Election 2026: overview and results</p>

    <?php $vs = voting_state(); ?>
    <div class="alert alert-<?= $vs['state'] === 'open' ? 'success' : ($vs['state'] === 'upcoming' ? 'warning' : 'secondary') ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>Voting is <strong><?= ['open' => 'open', 'upcoming' => 'not started yet', 'closed' => 'closed'][$vs['state']] ?></strong>.</span>
        <a href="admin_settings.php" class="btn btn-sm btn-outline-primary">Change</a>
    </div>

    <div class="tool-grid">
        <a href="admin_settings.php">Voting &amp; results settings</a>
        <a href="admin_candidates.php">Candidates &amp; positions</a>
        <a href="students.php">Manage students</a>
        <a href="admin_roll.php">Voter list</a>
        <a href="admin_announcements.php">Announcements</a>
        <a href="admin_log.php">Activity log</a>
    </div>

    <div class="stat-grid">
        <div class="stat"><div class="num"><?= $totalStudents ?></div><div class="lbl">Registered students</div></div>
        <div class="stat"><div class="num"><?= $totalVotes ?></div><div class="lbl">Votes cast</div></div>
        <div class="stat"><div class="num"><?= e($turnout) ?>%</div><div class="lbl">Turnout</div></div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="my-2">Results</h2>
        <span class="d-flex gap-2">
            <a href="export_results.php" class="btn btn-sm btn-outline-primary">Download CSV</a>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">Print / PDF</button>
        </span>
    </div>
    <?php foreach (POSITIONS as $key => $pos): ?>
        <section class="position">
            <h2><?= e($pos['label']) ?></h2>
            <?php
                $total = array_sum($results[$key]);
                $max   = $results[$key] ? max($results[$key]) : 0;
            ?>
            <?php foreach ($results[$key] as $name => $count):
                $pct = $total ? round($count / $total * 100) : 0; ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <strong><?= e($name) ?><?= ($max > 0 && $count === $max) ? ' 🏆' : '' ?></strong>
                        <span><?= (int)$count ?> vote<?= $count === 1 ? '' : 's' ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="progress"><div class="progress-bar" style="width: <?= $pct ?>%"></div></div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <?php if (roll_is_active()):
        $secs = db()->query("SELECT COALESCE(NULLIF(r.section,''),'No section') AS sec, COUNT(*) AS total, SUM(u.has_voted) AS voted
                             FROM users u LEFT JOIN voter_roll r ON r.student_id = u.student_id
                             WHERE u.role = 'student' GROUP BY sec ORDER BY sec")->fetchAll(); ?>
        <h2>Turnout by section</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Section</th><th>Registered</th><th>Voted</th><th>Turnout</th></tr></thead>
                <tbody>
                <?php foreach ($secs as $sc): $vt = (int)$sc['voted']; $tt = (int)$sc['total']; ?>
                    <tr><td><?= e($sc['sec']) ?></td><td><?= $tt ?></td><td><?= $vt ?></td><td><?= $tt ? round($vt / $tt * 100, 1) : 0 ?>%</td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <h2>Registered students</h2>
    <p><a href="students.php">Search, filter and manage students &raquo;</a></p>
    <div class="table-responsive">
        <table class="table align-middle">
    <thead>
        <tr><th>Student ID</th><th>Name</th><th>Email</th><th>Voted?</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php if (!$students): ?>
        <tr><td colspan="6" class="text-muted">No students registered yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($students as $u):
        $didVote = (bool)$u['has_voted']; ?>
        <tr>
            <td><?= e($u['student_id']) ?></td>
            <td><?= e($u['fullname']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><?= $didVote
                ? '<span class="badge text-bg-success">Voted</span>'
                : '<span class="badge text-bg-secondary">Not yet</span>' ?></td>
        <td><?= $u['is_active']
            ? '<span class="badge text-bg-success">Active</span>'
            : '<span class="badge text-bg-warning">Deactivated</span>' ?></td>
        <td class="text-end">
            <?php if (!$u['is_active']): ?>
                <form method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reactivate">
                    <input type="hidden" name="student_id" value="<?= e($u['student_id']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Reactivate</button>
                </form>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>
        </table>
    </div>

    <p class="text-muted small">Ballots are stored anonymously, so this page shows who voted but not who they voted for.</p>

<?php require 'footer.php'; ?>