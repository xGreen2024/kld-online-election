<?php
require_once __DIR__ . '/functions.php';
require_admin();

$started = election_started();   // once ballots exist, names and the ballot layout are locked
$errors  = [];

function slugify(string $s): string {
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '_', $s), '_'));
    return substr($s !== '' ? $s : 'position', 0, 40);
}

if (is_post()) {
    csrf_check();
    $action = post('action');
    $locked = fn() => flash('danger', 'Voting has already started, so the ballot layout and candidate names can no longer be changed.');

    try {
        if ($action === 'add_position') {
            if ($started) { $locked(); redirect('admin_candidates.php'); }
            $label = post('label');
            if ($label === '' || mb_strlen($label) > 80) throw new RuntimeException('Please enter a position name.');
            $key = slugify($label);
            $n = 2; $base = $key;
            while (db()->query("SELECT COUNT(*) FROM positions WHERE position_key = " . db()->quote($key))->fetchColumn()) $key = substr($base, 0, 36) . '_' . $n++;
            $order = (int)db()->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM positions')->fetchColumn();
            db()->prepare('INSERT INTO positions (position_key, label, sort_order) VALUES (?,?,?)')->execute([$key, $label, $order]);
            log_action('position_added', $label);
            flash('success', 'Position added.');

        } elseif ($action === 'rename_position') {
            $label = post('label');
            if ($label === '' || mb_strlen($label) > 80) throw new RuntimeException('Please enter a position name.');
            db()->prepare('UPDATE positions SET label = ? WHERE position_key = ?')->execute([$label, post('position_key')]);
            log_action('position_renamed', post('position_key') . ' -> ' . $label);
            flash('success', 'Position renamed.');

        } elseif ($action === 'delete_position') {
            if ($started) { $locked(); redirect('admin_candidates.php'); }
            $photos = db()->prepare('SELECT photo FROM candidates WHERE position_key = ?');
            $photos->execute([post('position_key')]);
            foreach ($photos->fetchAll() as $p) delete_candidate_photo($p['photo']);
            db()->prepare('DELETE FROM candidates WHERE position_key = ?')->execute([post('position_key')]);
            db()->prepare('DELETE FROM positions WHERE position_key = ?')->execute([post('position_key')]);
            log_action('position_deleted', post('position_key'));
            flash('success', 'Position deleted.');

        } elseif ($action === 'add_candidate') {
            if ($started) { $locked(); redirect('admin_candidates.php'); }
            $name = post('name');
            if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Please enter the candidate\'s name.');
            $photo = save_candidate_photo($_FILES['photo'] ?? []);
            $order = (int)db()->query('SELECT COALESCE(MAX(sort_order),0)+1 FROM candidates')->fetchColumn();
            try {
                db()->prepare('INSERT INTO candidates (position_key, name, year_section, platform, photo, sort_order) VALUES (?,?,?,?,?,?)')
                    ->execute([post('position_key'), $name, mb_substr(post('year_section'), 0, 60), mb_substr(post('platform', false), 0, 3000), $photo, $order]);
            } catch (PDOException $ex) {
                delete_candidate_photo($photo);
                throw new RuntimeException($ex->getCode() === '23000' ? 'That candidate is already in this position.' : 'Could not save the candidate.');
            }
            log_action('candidate_added', $name . ' (' . post('position_key') . ')');
            flash('success', 'Candidate added.');

        } elseif ($action === 'edit_candidate') {
            $st = db()->prepare('SELECT * FROM candidates WHERE id = ?');
            $st->execute([(int)post('id')]);
            $c = $st->fetch();
            if (!$c) throw new RuntimeException('Candidate not found.');
            $name = post('name');
            if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Please enter the candidate\'s name.');
            $photo = $c['photo'];
            if (!empty($_POST['remove_photo'])) { delete_candidate_photo($photo); $photo = null; }
            $new = save_candidate_photo($_FILES['photo'] ?? []);
            if ($new) { delete_candidate_photo($photo); $photo = $new; }
            try {
                db()->prepare('UPDATE candidates SET name = ?, year_section = ?, platform = ?, photo = ? WHERE id = ?')
                    ->execute([$name, mb_substr(post('year_section'), 0, 60), mb_substr(post('platform', false), 0, 3000), $photo, $c['id']]);
            } catch (PDOException $ex) {
                throw new RuntimeException('Could not save. That name may already be used in this position.');
            }
            log_action('candidate_edited', $name);
            flash('success', 'Candidate updated.');

        } elseif ($action === 'delete_candidate') {
            if ($started) { $locked(); redirect('admin_candidates.php'); }
            $st = db()->prepare('SELECT * FROM candidates WHERE id = ?');
            $st->execute([(int)post('id')]);
            if ($c = $st->fetch()) {
                delete_candidate_photo($c['photo']);
                db()->prepare('DELETE FROM candidates WHERE id = ?')->execute([$c['id']]);
                log_action('candidate_deleted', $c['name']);
                flash('success', 'Candidate deleted.');
            }
        }
        if (!$errors) redirect('admin_candidates.php');
    } catch (RuntimeException $ex) {
        $errors[] = $ex->getMessage();
    }
}

$posns = db()->query('SELECT position_key, label FROM positions ORDER BY sort_order, id')->fetchAll();
$all   = candidates_full();

$title = 'Candidates & Positions';
require 'header.php';
?>

    <h1>Candidates &amp; positions</h1>

    <?php if ($started): ?>
        <div class="alert alert-warning">Voting has started. Candidate names and the ballot layout are locked so results stay accurate. You can still edit photos, year/section and platforms, and rename positions.</div>
    <?php endif; ?>
    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>

    <?php foreach ($posns as $p): $list = $all[$p['position_key']] ?? []; ?>
        <section class="position">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-2">
                <form method="post" class="d-flex gap-2 flex-grow-1">
                    <?= csrf_field() ?><input type="hidden" name="action" value="rename_position"><input type="hidden" name="position_key" value="<?= e($p['position_key']) ?>">
                    <input class="form-control fw-bold" name="label" value="<?= e($p['label']) ?>" maxlength="80" aria-label="Position name">
                    <button class="btn btn-sm btn-outline-primary">Rename</button>
                </form>
                <?php if (!$started): ?>
                    <form method="post" onsubmit="return confirm('Delete this position and all its candidates?');">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete_position"><input type="hidden" name="position_key" value="<?= e($p['position_key']) ?>">
                        <button class="btn btn-sm btn-outline-danger">Delete position</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!$list): ?><p class="text-muted">No candidates yet. A position with no candidates does not appear on the ballot.</p><?php endif; ?>

            <?php foreach ($list as $c): ?>
                <details class="cand-edit">
                    <summary><?= candidate_avatar($c, $c['name'], 'cand-sm') ?> <strong><?= e($c['name']) ?></strong>
                        <?php if ($c['year_section'] !== ''): ?><span class="text-muted"> &middot; <?= e($c['year_section']) ?></span><?php endif; ?></summary>
                    <form method="post" enctype="multipart/form-data" class="mt-3">
                        <?= csrf_field() ?><input type="hidden" name="action" value="edit_candidate"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" value="<?= e($c['name']) ?>" maxlength="100" required>
                        <label class="form-label mt-3">Year and section</label>
                        <input class="form-control" name="year_section" value="<?= e($c['year_section']) ?>" maxlength="60" placeholder="e.g. BSIS - 204">
                        <label class="form-label mt-3">Platform</label>
                        <textarea class="form-control" name="platform" rows="4" maxlength="3000"><?= e($c['platform'] ?? '') ?></textarea>
                        <label class="form-label mt-3">Photo (JPG, PNG or WebP, max 2 MB)</label>
                        <input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        <?php if ($c['photo']): ?>
                            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_photo" id="rp<?= (int)$c['id'] ?>" value="1">
                                <label class="form-check-label" for="rp<?= (int)$c['id'] ?>">Remove current photo</label></div>
                        <?php endif; ?>
                        <button class="btn btn-primary mt-3">Save changes</button>
                    </form>
                    <?php if (!$started): ?>
                        <form method="post" class="mt-2" onsubmit="return confirm('Delete this candidate?');">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete_candidate"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger">Delete candidate</button>
                        </form>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>

            <?php if (!$started): ?>
                <details class="cand-edit add">
                    <summary>+ Add a candidate to <?= e($p['label']) ?></summary>
                    <form method="post" enctype="multipart/form-data" class="mt-3">
                        <?= csrf_field() ?><input type="hidden" name="action" value="add_candidate"><input type="hidden" name="position_key" value="<?= e($p['position_key']) ?>">
                        <label class="form-label">Name</label>
                        <input class="form-control" name="name" maxlength="100" required>
                        <label class="form-label mt-3">Year and section</label>
                        <input class="form-control" name="year_section" maxlength="60" placeholder="e.g. BSIS - 204">
                        <label class="form-label mt-3">Platform</label>
                        <textarea class="form-control" name="platform" rows="4" maxlength="3000"></textarea>
                        <label class="form-label mt-3">Photo (JPG, PNG or WebP, max 2 MB)</label>
                        <input class="form-control" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                        <button class="btn btn-primary mt-3">Add candidate</button>
                    </form>
                </details>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if (!$started): ?>
        <h2>Add a position</h2>
        <form method="post" class="d-flex gap-2">
            <?= csrf_field() ?><input type="hidden" name="action" value="add_position">
            <input class="form-control" name="label" maxlength="80" placeholder="e.g. Treasurer" required>
            <button class="btn btn-primary text-nowrap">Add position</button>
        </form>
    <?php endif; ?>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
