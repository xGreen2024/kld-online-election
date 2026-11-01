<?php
require_once __DIR__ . '/functions.php';
require_student();
$voted = has_voted();
$announcements = [];
try { $announcements = db()->query('SELECT title, body, created_at FROM announcements WHERE is_active = 1 ORDER BY created_at DESC, id DESC LIMIT 3')->fetchAll(); } catch (Throwable $e) {}
$title = 'Dashboard';
require 'header.php';
?>

    <h1>Welcome, <?= e($_SESSION['fullname'] ?? 'Student') ?></h1>
    <p class="lead-text">Student Council Election 2026</p>

    <?= voting_banner() ?>

    <?php foreach ($announcements as $a): ?>
        <div class="announcement">
            <strong><?= e($a['title']) ?></strong>
            <span class="text-muted small"> &middot; <?= e(date('M j, Y', strtotime($a['created_at']))) ?></span>
            <div><?= nl2br(e($a['body'])) ?></div>
        </div>
    <?php endforeach; ?>

    <?php if ($voted): ?>
        <div class="status done">
            <span class="dot"></span>
            <div><strong>Your vote is in.</strong><br>Thank you for taking part in the election.</div>
        </div>
    <?php else: ?>
        <div class="status">
            <span class="dot"></span>
            <div><strong>You haven't voted yet.</strong><br>Open the elections list to cast your vote.</div>
        </div>
    <?php endif; ?>

    <div class="actions">
        <a href="elections.php" class="btn btn-primary">View elections</a>
        <a href="candidates.php" class="btn btn-outline-primary">Meet the candidates</a>
    </div>

    <hr>

    <h2 class="mt-0">How voting works</h2>
    <ol class="steps">
        <li><strong>View elections</strong><span>See which elections are open to you.</span></li>
        <li><strong>Cast your vote</strong><span>Pick one candidate for each position.</span></li>
        <li><strong>View confirmation</strong><span>Check your choices, then submit. Votes can't be changed.</span></li>
    </ol>

<?php require 'footer.php'; ?>
