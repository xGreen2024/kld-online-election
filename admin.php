<?php
require_once __DIR__ . '/functions.php';
require_admin();

// Handle Account Reactivation
if (is_post() && post('action') === 'reactivate') {
    csrf_check();
    db()->prepare("UPDATE users SET is_active = 1 WHERE student_id = ? AND role = 'student'")
        ->execute([post('student_id')]);
    log_action('student_reactivated', post('student_id'));
    flash('success', 'Account reactivated.');
    redirect('admin.php');
}

// Handle Adding New Positions & Candidates
if (is_post()) {
    csrf_check();
    $action = post('action');

    // Add New Position
    if ($action === 'add_position') {
        $label = post('position_label');
        $key   = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', trim($label)));

        if ($label && $key) {
            db()->prepare('INSERT INTO positions (position_key, label) VALUES (?, ?)')
                ->execute([$key, $label]);
            flash('success', "Position '$label' added successfully.");
        }
        redirect('admin.php');
    }

    // Add New Candidate
    if ($action === 'add_candidate') {
        $posKey = post('position_key');
        $name   = post('candidate_name');

        if ($posKey && $name) {
            db()->prepare('INSERT INTO candidates (position_key, name) VALUES (?, ?)')
                ->execute([$posKey, $name]);
            flash('success', "Candidate '$name' added successfully.");
        }
        redirect('admin.php');
    }
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

<!-- Dynamic Add Position & Candidate UI -->
<div class="row mb-4">
    <!-- Add Position Form -->
    <div class="col-md-6 mb-3">
        <div class="card p-3 shadow-sm">
            <h5 class="card-title">+ Add New Position</h5>
            <form action="admin.php" method="post" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_position">
                <div class="mb-2">
                    <input type="text" name="position_label" class="form-control" placeholder="e.g. Treasurer" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">+ Save Position</button>
            </form>
        </div>
    </div>

    <!-- Add Candidate Form -->
    <div class="col-md-6 mb-3">
        <div class="card p-3 shadow-sm">
            <h5 class="card-title">+ Add Candidate</h5>
            <form action="admin.php" method="post" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_candidate">
                
                <div class="mb-2">
                    <select name="position_key" class="form-select" required>
                        <option value="">-- Select Position --</option>
                        <?php foreach (db()->query('SELECT position_key, label FROM positions')->fetchAll() as $pos): ?>
                            <option value="<?= e($pos['position_key']) ?>"><?= e($pos['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <input type="text" name="candidate_name" class="form-control" placeholder="Full Candidate Name" required>
                </div>

                <button type="submit" class="btn btn-success btn-sm">+ Add Candidate</button>
            </form>
        </div>
    </div>
</div>

<?php $vs = voting_state(); ?>
