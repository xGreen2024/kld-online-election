<?php
require_once __DIR__ . '/functions.php';
require_student();

if (!is_post()) redirect('vote.php');
csrf_check();
if (has_voted()) redirect('submitted.php');
require_voting_open();

$choices = [];
foreach (POSITIONS as $key => $pos) {
    $value = post($key);
    if (!in_array($value, $pos['candidates'], true)) {
        flash('danger', 'Please select one candidate for each position.');
        redirect('vote.php');
    }
    $choices[$key] = $value;
}

$title = 'Vote Confirmation';
require 'header.php';
?>

<h1>Vote Confirmation</h1>
<p>Please review your vote before submitting.</p>

<div class="transaction card card-body mb-3">
    <?php foreach (POSITIONS as $key => $pos): ?>
        <p class="mb-2">
            <strong><?= e($pos['label']) ?>:</strong>
            <?= e($choices[$key]) ?>
        </p>
    <?php endforeach; ?>
</div>

<p>Once you submit your vote, you cannot change it.</p>

<form action="submitted.php" method="post" class="d-inline">
    <?= csrf_field() ?>
    <?php foreach ($choices as $key => $candidate): ?>
        <input type="hidden" name="<?= e($key) ?>" value="<?= e($candidate) ?>">
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">Confirm Vote</button>
</form>
<a href="vote.php" class="btn btn-outline-primary">Go Back</a>

<?php require 'footer.php'; ?>
