<?php
// Public page: no login needed, so anyone can watch the race.
require_once __DIR__ . '/functions.php';

if (!results_visible()) {
    $title = 'Results';
    $narrow = true;
    require 'header.php';
    $mode = setting('results_mode', 'live');
    ?>
    <h1>Results</h1>
    <div class="alert alert-secondary">
        <?= $mode === 'after_close' ? 'Results will be shown after voting closes.' : 'Results are not available right now.' ?>
    </div>
    <div class="actions"><a href="<?= is_logged_in() ? e(home_for_role()) : 'index.php' ?>" class="btn btn-primary">Go back</a></div>
    <?php
    require 'footer.php';
    exit;
}

$results       = tally_votes();
$totalStudents = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalVotes    = (int)db()->query('SELECT COUNT(*) FROM ballots')->fetchColumn();
$turnout       = $totalStudents ? round($totalVotes / $totalStudents * 100, 1) : 0;

$title = 'Live Results';
require 'header.php';
?>

    <?php if (setting('results_mode', 'live') === 'live'): ?><p class="live-pill mb-2">Updates every 10 seconds</p><?php endif; ?>
    <?php if (is_admin() && setting('results_mode', 'live') !== 'live'): ?>
        <div class="alert alert-info">Only admins can see this page right now. Students and visitors cannot.</div>
    <?php endif; ?>
    <h1>Live election results</h1>
    <p class="lead-text">Student Council Election 2026. See who is leading in each position.</p>

    <div class="stat-grid">
        <div class="stat"><div class="num"><?= $totalVotes ?></div><div class="lbl">Votes cast</div></div>
        <div class="stat"><div class="num"><?= $totalStudents ?></div><div class="lbl">Registered students</div></div>
        <div class="stat"><div class="num"><?= e($turnout) ?>%</div><div class="lbl">Turnout</div></div>
    </div>

    <?php foreach (POSITIONS as $key => $pos):
        $counts = $results[$key];
        $total  = array_sum($counts);
        $max    = $counts ? max($counts) : 0;
        $leaders = array_keys(array_filter($counts, fn($n) => $n === $max));
        arsort($counts);
    ?>
        <section class="position race">
            <h2><?= e($pos['label']) ?></h2>

            <?php if ($total === 0): ?>
                <p class="race-status">No votes yet.</p>
            <?php elseif (count($leaders) > 1): ?>
                <p class="race-status">Tied: <?= e(implode(' and ', $leaders)) ?> (<?= (int)$max ?> vote<?= $max === 1 ? '' : 's' ?> each)</p>
            <?php else: ?>
                <p class="race-status leading"><?= e($leaders[0]) ?> is leading</p>
            <?php endif; ?>

            <?php foreach ($counts as $name => $count):
                $pct    = $total ? round($count / $total * 100, 1) : 0;
                $isLead = $total > 0 && $count === $max; ?>
                <div class="race-row<?= $isLead ? ' lead' : '' ?>">
                    <div class="race-name">
                        <?= e($name) ?>
                        <?php if ($isLead && count($leaders) === 1): ?><span class="crown">Leading</span><?php endif; ?>
                    </div>
                    <div class="race-track" role="img" aria-label="<?= e($name) ?>: <?= $pct ?> percent">
                        <div class="race-fill" style="width: <?= $pct ?>%"></div>
                        <div class="race-label"><?= $pct ?>% &middot; <?= (int)$count ?> vote<?= $count === 1 ? '' : 's' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>

    <p class="text-muted small">Ballots are anonymous. This page shows totals only, never who voted for whom. Results are unofficial until announced by the election committee.</p>

    <div class="actions">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">Print / save as PDF</button>
        <?php if (is_admin()): ?><a href="export_results.php" class="btn btn-outline-primary">Download CSV</a><?php endif; ?>
        <?php if (is_logged_in()): ?>
            <a href="<?= e(home_for_role()) ?>" class="btn btn-primary">Back to dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary">Back to login</a>
        <?php endif; ?>
    </div>

<script>setTimeout(function () { location.reload(); }, 10000);</script>

<?php require 'footer.php'; ?>
