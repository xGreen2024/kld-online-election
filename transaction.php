<?php
require_once __DIR__ . '/functions.php';
require_student();

if (has_voted()) {
    $title = 'Vote Submitted';
    require 'header.php';
    ?>
    <div class="alert alert-success mt-4">
        <h4>Your vote has already been submitted!</h4>
        <p>Thank you for participating in the KLD Student Council Election 2026.</p>
    </div>
    <a href="home.php" class="btn btn-primary">Return to Home</a>
    <?php
    require 'footer.php';
    exit;
}

if (!is_post()) redirect('vote.php');
csrf_check();
require_voting_open();

$choices = $_SESSION['pending_vote'] ?? null;
if (!$choices || !is_array($choices)) {
    flash('danger', 'Your voting session expired. Please cast your vote again.');
    redirect('vote.php');
}

$studentId = $_SESSION['student_id'];
$user = find_user($studentId);

if (!$user) {
    flash('danger', 'User account not found.');
    redirect('vote.php');
}

// Save ballot into database inside a PDO transaction
try {
    db()->beginTransaction();

    // 1. Record individual choices
    $stmt = db()->prepare('INSERT INTO ballot_choices (student_id, position_key, candidate) VALUES (?, ?, ?)');
    foreach ($choices as $posKey => $candidate) {
        $stmt->execute([$studentId, $posKey, $candidate]);
    }

    // 2. Add entry to master ballots table
    db()->prepare('INSERT INTO ballots (student_id, created_at) VALUES (?, NOW())')
        ->execute([$studentId]);

    // 3. Mark user as having voted
    db()->prepare('UPDATE users SET has_voted = 1 WHERE student_id = ?')
        ->execute([$studentId]);

    db()->commit();

    // Clear session vote payload
    unset($_SESSION['pending_vote']);

} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    error_log('Vote recording failed: ' . $e->getMessage());
    flash('danger', 'An error occurred while saving your vote. Please try again.');
    redirect('vote.php');
}

// Log action
log_action('voted', 'Vote successfully recorded.');

$title = 'Vote Submitted';
require 'header.php';
?>

<div class="text-center py-5">
    <div class="mb-4">
        <span class="fs-1 text-success">✓</span>
    </div>
    <h1>Thank You for Voting!</h1>
    <p class="lead-text">Your vote has been securely recorded for the Student Council Election 2026.</p>

    <div class="mt-4">
        <a href="home.php" class="btn btn-primary me-2">Go to Home</a>
        <?php if (results_visible()): ?>
            <a href="results.php" class="btn btn-warning-kld">View Live Results</a>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>
