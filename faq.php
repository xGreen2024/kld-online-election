<?php
require_once __DIR__ . '/functions.php';
$title = 'Help & FAQ';
require 'header.php';
?>

    <h1>Help &amp; FAQ</h1>
    <p class="lead-text">Quick answers about voting in the Student Council Election 2026.</p>

    <div class="accordion" id="faq">
        <?php
        $items = [
            ['How do I vote?', 'Log in, open <strong>View elections</strong>, then choose one candidate for each position. You will get a chance to review your choices before submitting.'],
            ['Can I change my vote?', 'No. Once you submit, your vote is final. Please review it carefully on the confirmation page.'],
            ['Can I vote more than once?', 'No. Each student can vote one time. The system blocks a second vote even if you click twice.'],
            ['Is my vote secret?', 'Yes. Ballots are stored without your name. Not even administrators can see who you voted for, only whether you have voted.'],
            ['I cannot register. What should I do?', 'Only students on the official voter list can register. If your Student ID is rejected, contact the election committee.'],
            ['I did not get the verification email.', 'Check your spam folder, then use <a href="resend_verification.php">Send me a new verification link</a>. The link works for 24 hours.'],
            ['I forgot my password.', 'Use <a href="forgot_password.php">Forgot your password?</a> on the login page. A reset link will be emailed to you and works for one hour.'],
            ['When can I vote?', 'Voting is only possible during the schedule set by the election committee. The dashboard shows when voting opens and closes.'],
            ['When will results be shown?', 'The committee decides whether results are live, shown after voting closes, or hidden. Check the <a href="results.php">results page</a>.'],
            ['Who do I contact for other problems?', 'Please contact the election committee or the system administrators of your school.'],
        ];
        foreach ($items as $i => [$q, $a]): ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $i ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#f<?= $i ?>"><?= e($q) ?></button>
                </h2>
                <div id="f<?= $i ?>" class="accordion-collapse collapse <?= $i ? '' : 'show' ?>" data-bs-parent="#faq">
                    <div class="accordion-body"><?= $a ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="actions">
        <?php if (is_logged_in()): ?>
            <a href="<?= e(home_for_role()) ?>" class="btn btn-primary">Back to dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary">Back to login</a>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>
