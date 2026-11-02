<?php
require_once __DIR__ . '/functions.php';
require_admin();

// Handle Form Submissions (Add Position / Delete Position / Add Candidate)
if (is_post()) {
    csrf_check();
    $action = post('action');

    // Add New Position
    if ($action === 'add_position') {
        $label = post('position_label');
        $key   = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', trim($label)));

        if ($label && $key) {
            try {
                db()->prepare('INSERT INTO positions (position_key, label) VALUES (?, ?)')
                    ->execute([$key, $label]);
                flash('success', "Position '$label' added successfully.");
            } catch (Throwable $e) {
                flash('danger', 'Position already exists or could not be created.');
            }
        }
        redirect('admin_candidates.php');
    }

    // Delete Position (and all its candidates)
    if ($action === 'delete_position') {
        $posKey = post('position_key');
        if ($posKey) {
            try {
                // Delete candidates belonging to this position
                db()->prepare('DELETE FROM candidates WHERE position_key = ?')->execute([$posKey]);
                // Delete the position record
                db()->prepare('DELETE FROM positions WHERE position_key = ?')->execute([$posKey]);

                flash('success', 'Position deleted successfully.');
            } catch (Throwable $e) {
                flash('danger', 'Could not delete position.');
            }
        }
        redirect('admin_candidates.php');
    }

    // Add New Candidate
    if ($action === 'add_candidate') {
        $posKey = post('position_key');
        $name   = post('candidate_name');

        if ($posKey && $name) {
            try {
                db()->prepare('INSERT INTO candidates (position_key, name) VALUES (?, ?)')
                    ->execute([$posKey, $name]);
                flash('success', "Candidate '$name' added successfully.");
            } catch (Throwable $e) {
                flash('danger', 'Could not add candidate.');
            }
        }
        redirect('admin_candidates.php');
    }
}

$positions  = db()->query('SELECT * FROM positions ORDER BY sort_order, id')->fetchAll();
$candidates = candidates_full();

$title = 'Positions & Candidates';
require 'header.php';
?>

<h1>Positions & Candidates</h1>
<p class="lead-text">Manage positions and candidates running in this election.</p>

<?php flash_render(); ?>

<!-- Render Existing Positions & Candidates -->
<?php foreach ($positions as $p): ?>
    <div class="card p-3 mb-3 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="m-0"><?= e($p['label']) ?></h4>
            <!-- Delete Position Form Button -->
            <form action="admin_candidates.php" method="post" onsubmit="return confirm('Are you sure you want to delete this position and all candidates running under it?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_position">
                <input type="hidden" name="position_key" value="<?= e($p['position_key']) ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete Position</button>
            </form>
        </div>

        <?php $cands = $candidates[$p['position_key']] ?? []; ?>
        <?php if (empty($cands)): ?>
            <p class="text-muted small m-0">No candidates added yet for this position.</p>
        <?php else: ?>
            <div class="list-group">
                <?php foreach ($cands as $c): ?>
                    <div class="list-group-item d-flex align-items-center gap-3">
                        <?= candidate_avatar($c, $c['name'], 'cand-avatar-sm') ?>
                        <span class="fw-bold"><?= e($c['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<!-- + Add Position & Candidate Forms -->
<div class="row my-4">
    <!-- Add Position Card -->
    <div class="col-md-6 mb-3">
        <div class="card p-3 shadow-sm">
            <h5 class="card-title">+ Add New Position</h5>
            <form action="admin_candidates.php" method="post" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_position">
                <div class="mb-2">
                    <input type="text" name="position_label" class="form-control" placeholder="e.g. Treasurer" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">+ Save Position</button>
            </form>
        </div>
    </div>

    <!-- Add Candidate Card -->
    <div class="col-md-6 mb-3">
        <div class="card p-3 shadow-sm">
            <h5 class="card-title">+ Add Candidate</h5>
            <form action="admin_candidates.php" method="post" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_candidate">
                
                <div class="mb-2">
                    <select name="position_key" class="form-select" required>
                        <option value="">-- Select Position --</option>
                        <?php foreach ($positions as $pos): ?>
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

<div class="mt-3 mb-5">
    <a href="admin.php" class="btn btn-outline-secondary">← Back to dashboard</a>
</div>

<?php require 'footer.php'; ?>
