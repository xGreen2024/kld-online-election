<?php
require_once __DIR__ . '/functions.php';
require_student();
if (has_voted()) {
    flash('info', 'You have already voted.');
    redirect('submitted.php');
}
require_voting_open();
$selected = $_SESSION['pending_vote'] ?? [];   
$title = 'Vote';
require 'header.php';
?>

    <h1>Student Council Election</h1>

    <p class="lead-text">Select one candidate for each position, then review before you submit.</p>

    <form action="transaction.php" method="post">
        <?= csrf_field() ?>

        <?php foreach (POSITIONS as $key => $pos): ?>
            <section class="position">
            <h2><?= e($pos['label']) ?></h2>
            <p class="hint">Choose one.</p>

            <?php foreach ($pos['candidates'] as $i => $name): $id = "{$key}_{$i}"; $cand = candidate_by_name($key, $name); ?>
                <div class="candidate form-check">
                    <input class="form-check-input" type="radio" name="<?= e($key) ?>" id="<?= e($id) ?>"
                           value="<?= e($name) ?>"
                           <?= (($selected[$key] ?? '') === $name) ? 'checked' : '' ?>
                           <?= $i === 0 ? 'required' : '' ?>>
                    <label class="form-check-label d-flex align-items-center gap-3" for="<?= e($id) ?>">
                        <?= candidate_avatar($cand, $name, 'cand-sm') ?>
                        <span><?= e($name) ?>
                            <?php if ($cand && $cand['year_section'] !== ''): ?><small class="d-block text-muted fw-normal"><?= e($cand['year_section']) ?></small><?php endif; ?>
                        </span>
                    </label>
                    <?php if ($cand): ?><a href="candidates.php#c<?= (int)$cand['id'] ?>" target="_blank" rel="noopener" class="small ms-1 text-nowrap">View profile</a><?php endif; ?>
                </div>
            <?php endforeach; ?>
            </section>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Review my vote</button>
        <a href="elections.php" class="btn btn-outline-primary">Back to elections</a>
    </form>

<?php require 'footer.php'; ?>
