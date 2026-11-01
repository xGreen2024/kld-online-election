<?php
require_once __DIR__ . '/functions.php';
require_student();

if (is_post()) {
    csrf_check();
    $choices = $_SESSION['pending_vote'] ?? null;
    if (!$choices) redirect('vote.php');
    require_voting_open();

    $pdo = db();
    $recorded = false;
    try {
        $pdo->beginTransaction();
        // Only succeeds if they haven't voted, which blocks double votes even on double-clicks
        $st = $pdo->prepare('UPDATE users SET has_voted = 1 WHERE student_id = ? AND has_voted = 0');
        $st->execute([$_SESSION['student_id']]);
        if ($st->rowCount() === 1) {
            $pdo->exec('INSERT INTO ballots () VALUES ()');
            $ballotId = $pdo->lastInsertId();
            $ins = $pdo->prepare('INSERT INTO ballot_choices (ballot_id, position_key, candidate) VALUES (?, ?, ?)');
            foreach ($choices as $pos => $cand) $ins->execute([$ballotId, $pos, $cand]);
            $recorded = true;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
    unset($_SESSION['pending_vote']);

    if ($recorded) {
        // Receipt: shown once. Time is kept in the session only, never stored with the ballot, so votes stay anonymous.
        $me = find_user($_SESSION['student_id']);
        $when = date('F j, Y g:i A');
        $mailed = false;
        if ($me && !empty($me['email'])) {
            $mailed = send_mail(
                $me['email'],
                'Your vote was recorded',
                '<p>Hello ' . e($me['fullname']) . ',</p><p>This confirms that your vote in the <strong>Student Council Election 2026</strong> was recorded on <strong>' . e($when) . '</strong>.</p><p>For your privacy, this email does not include your choices.</p>',
                "Your vote in the Student Council Election 2026 was recorded on $when.\nThis email does not include your choices."
            );
        }
        $_SESSION['vote_receipt'] = ['time' => $when, 'mailed' => $mailed];
    }

    if (!has_voted()) {
        flash('danger', 'Your vote could not be recorded. Please try again.');
        redirect('vote.php');
    }
    redirect('submitted.php');
}

if (!has_voted()) redirect('vote.php');

$receipt = $_SESSION['vote_receipt'] ?? null;
unset($_SESSION['vote_receipt']);
$title = 'Vote Submitted';
require 'header.php';
?>

    <h1>Vote Submitted!</h1>

    <p>
        Thank you for participating in the
        Student Council Election.
    </p>

    <div class="status done">
        <span class="dot"></span>
        <div>
            <strong>Your vote has been recorded.</strong><br>
            <?php if ($receipt): ?>
                Recorded on <?= e($receipt['time']) ?>.
                <?= $receipt['mailed'] ? 'A confirmation was sent to your email.' : '' ?>
            <?php else: ?>
                You have already voted. Your vote cannot be changed.
            <?php endif; ?>
        </div>
    </div>
    <p class="text-muted small mt-3">For your privacy, your choices are not shown here and are not linked to your name.</p>

    <div class="actions">
        <a href="home.php" class="btn btn-primary">Back to dashboard</a>
        <a href="logout.php" class="btn btn-outline-primary">Logout</a>
    </div>

<?php require 'footer.php'; ?>
