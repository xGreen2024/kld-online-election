<?php
// Public page: anyone can read about the candidates.
require_once __DIR__ . '/functions.php';

$all   = candidates_full();
$posns = [];
try { $posns = db()->query('SELECT position_key, label FROM positions ORDER BY sort_order, id')->fetchAll(); } catch (Throwable $e) {}

$title = 'Candidates';
require 'header.php';
?>

    <h1>Meet the candidates</h1>
    <p class="lead-text">Student Council Election 2026. Read about who is running before you vote.</p>

    <?= voting_banner() ?>

    <?php foreach ($posns as $p): $list = $all[$p['position_key']] ?? []; if (!$list) continue; ?>
        <section class="position">
            <h2><?= e($p['label']) ?></h2>
            <div class="cand-grid">
                <?php foreach ($list as $c): ?>
                    <article class="cand-card" id="c<?= (int)$c['id'] ?>">
                        <?= candidate_avatar($c, $c['name'], 'cand-lg') ?>
                        <div>
                            <h3><?= e($c['name']) ?></h3>
                            <?php if ($c['year_section'] !== ''): ?><div class="cand-meta"><?= e($c['year_section']) ?></div><?php endif; ?>
                            <?php if (!empty($c['platform'])): ?>
                                <h4>Platform</h4>
                                <p class="mb-0"><?= nl2br(e($c['platform'])) ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0">No platform has been posted yet.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (!$posns): ?><p class="text-muted">No candidates have been announced yet.</p><?php endif; ?>

    <div class="actions">
        <?php if (is_logged_in() && !is_admin()): ?>
            <a href="elections.php" class="btn btn-primary">Go to elections</a>
        <?php elseif (is_logged_in()): ?>
            <a href="admin.php" class="btn btn-primary">Back to dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary">Back to login</a>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>
