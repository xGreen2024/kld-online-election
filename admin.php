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
$totalVotes    = (int)db()->query("SELECT COUNT(*) FROM ballots")->fetchColumn();
$turnout       = $totalStudents ? round($totalVotes / $totalStudents * 100, 1) : 0;
$results       = tally_votes();
$students      = db()->query("SELECT student_id, fullname, email, has_voted, is_active
                             FROM users WHERE role = 'student' ORDER BY fullname")->fetchAll();

$title = 'Admin Dashboard';
require 'header.php';
?>

<h1>Admin Dashboard</h1>
<p class="lead-text">Student Council Election 2026: overview and results</p>

<?php flash_render(); ?>

<?php $vs = voting_state(); ?>
<div class="card p-3 mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong>Voting Status:</strong> 
            <span class="badge bg-<?= $vs['state'] === 'open' ? 'success' : ($vs['state'] === 'upcoming' ? 'warning' : 'secondary') ?>">
                <?= strtoupper($vs['state']) ?>
            </span>
        </div>
        <a href="admin_settings.php" class="btn btn-outline-primary btn-sm">Manage Settings</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-3 text-center shadow-sm">
            <div class="text-muted small">Total Registered Students</div>
            <div class="fs-2 fw-bold text-primary"><?= $totalStudents ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center shadow-sm">
            <div class="text-muted small">Total Votes Cast</div>
            <div class="fs-2 fw-bold text-success"><?= $totalVotes ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center shadow-sm">
            <div class="text-muted small">Voter Turnout</div>
            <div class="fs-2 fw-bold text-info"><?= $turnout ?>%</div>
        </div>
    </div>
</div>

<h3 class="mt-4 mb-3">Live Election Results</h3>
<?php foreach (POSITIONS as $posKey => $posData): ?>
    <div class="card p-3 mb-3 shadow-sm">
        <h5><?= e($posData['label']) ?></h5>
        <?php 
            $posVotes = $results[$posKey] ?? [];
            $totalPosVotes = array_sum($posVotes);
        ?>
        <?php foreach ($posData['candidates'] as $cand): ?>
            <?php 
                $count = $posVotes[$cand] ?? 0;
                $pct = $totalPosVotes ? round(($count / $totalPosVotes) * 100) : 0;
            ?>
            <div class="mt-2">
                <div class="d-flex justify-content-between small mb-1">
                    <span><strong><?= e($cand) ?></strong></span>
                    <span><?= $count ?> votes (<?= $pct ?>%)</span>
                </div>
                <div class="progress" style="height: 12px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<h3 class="mt-5 mb-3">Registered Students</h3>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Student ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Voted</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $s): ?>
                <tr>
                    <td><?= e($s['student_id']) ?></td>
                    <td><?= e($s['fullname']) ?></td>
                    <td><?= e($s['email']) ?></td>
                    <td>
                        <span class="badge bg-<?= (int)$s['has_voted'] ? 'success' : 'secondary' ?>">
                            <?= (int)$s['has_voted'] ? 'Yes' : 'No' ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-<?= (int)$s['is_active'] ? 'success' : 'danger' ?>">
                            <?= (int)$s['is_active'] ? 'Active' : 'Deactivated' ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!(int)$s['is_active']): ?>
                            <form action="admin.php" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="reactivate">
                                <input type="hidden" name="student_id" value="<?= e($s['student_id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success">Reactivate</button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require 'footer.php'; ?>
