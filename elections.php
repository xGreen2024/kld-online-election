<?php
require_once __DIR__ . '/functions.php';
require_student();
$voted = has_voted();
$open  = voting_is_open();
$title = 'Elections';
require 'header.php';
?>

    <h1>Available elections</h1>
    <p class="lead-text">Elections you can vote in.</p>

    <?= voting_banner() ?>

    <div class="election">
        <div>
            <h2>Student Council Election 2026</h2>
            <p class="mb-0 text-muted">
                <?= e(implode(', ', array_column(POSITIONS, 'label'))) ?>
            </p>
        </div>
        <?php if ($voted): ?>
            <a href="submitted.php" class="btn btn-outline-primary">View confirmation</a>
        <?php elseif ($open): ?>
            <a href="vote.php" class="btn btn-primary">Cast your vote</a>
        <?php else: ?>
            <button type="button" class="btn btn-primary" disabled>Voting unavailable</button>
        <?php endif; ?>
    </div>

    <div class="actions">
        <a href="candidates.php" class="btn btn-outline-primary">Meet the candidates</a>
        <a href="home.php" class="btn btn-outline-primary">Back to home</a>
    </div>

<?php require 'footer.php'; ?>
