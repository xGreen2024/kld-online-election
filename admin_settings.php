<?php
require_once __DIR__ . '/functions.php';
require_admin();

$errors = [];
if (is_post()) {
    csrf_check();
    $action = post('action');

    if ($action === 'open_now') {
        set_setting('voting_enabled', '1');
        $end = setting('voting_end');
        if ($end && strtotime($end) < time()) { set_setting('voting_end', null); }
        $start = setting('voting_start');
        if ($start && strtotime($start) > time()) { set_setting('voting_start', null); }
        log_action('voting_opened', 'Voting opened manually');
        flash('success', 'Voting is now open.');
        redirect('admin_settings.php');

    } elseif ($action === 'close_now') {
        set_setting('voting_enabled', '0');
        log_action('voting_closed', 'Voting closed manually');
        flash('success', 'Voting is now closed.');
        redirect('admin_settings.php');

    } elseif ($action === 'schedule') {
        $fmt = fn(string $v): ?string => ($v !== '' && ($t = strtotime($v))) ? date('Y-m-d H:i:s', $t) : null;
        $start = $fmt(post('voting_start'));
        $end   = $fmt(post('voting_end'));
        if ($start && $end && strtotime($end) <= strtotime($start)) $errors[] = 'The closing time must be after the opening time.';
        if (!$errors) {
            set_setting('voting_start', $start);
            set_setting('voting_end', $end);
            set_setting('voting_enabled', '1');   // the schedule now decides
            log_action('voting_schedule', 'Start: ' . ($start ?? 'none') . ' | End: ' . ($end ?? 'none'));
            flash('success', 'Voting schedule saved.');
            redirect('admin_settings.php');
        }

    } elseif ($action === 'results_mode') {
        $mode = post('results_mode');
        if (in_array($mode, ['live', 'after_close', 'hidden'], true)) {
            set_setting('results_mode', $mode);
            log_action('results_visibility', "Results mode: $mode");
            flash('success', 'Results visibility updated.');
            redirect('admin_settings.php');
        }
        $errors[] = 'Please choose an option.';
    }
}

$v    = voting_state();
$mode = setting('results_mode', 'live');
$inp  = fn(?string $dt): string => $dt ? date('Y-m-d\TH:i', strtotime($dt)) : '';
$label = ['open' => 'Open', 'upcoming' => 'Not started yet', 'closed' => 'Closed'][$v['state']];

$title = 'Voting & Results Settings';
require 'header.php';
?>

    <h1>Voting &amp; results settings</h1>

    <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $er): ?><div><?= e($er) ?></div><?php endforeach; ?></div><?php endif; ?>

    <h2 class="mt-0">Voting status</h2>
    <p>Current status: <span class="badge text-bg-<?= $v['state'] === 'open' ? 'success' : ($v['state'] === 'upcoming' ? 'warning' : 'secondary') ?>"><?= e($label) ?></span></p>
    <div class="d-flex flex-wrap gap-2">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="open_now">
            <button class="btn btn-primary" <?= $v['state'] === 'open' ? 'disabled' : '' ?>>Open voting now</button></form>
        <form method="post" onsubmit="return confirm('Close voting now? Students will not be able to vote.');"><?= csrf_field() ?><input type="hidden" name="action" value="close_now">
            <button class="btn btn-outline-danger" <?= $v['state'] === 'closed' ? 'disabled' : '' ?>>Close voting now</button></form>
    </div>

    <hr>

    <h2 class="mt-0">Schedule</h2>
    <p class="text-muted">Optional. Voting opens and closes automatically at these times (Philippine time). Leave a box empty for no limit.</p>
    <form method="post" class="row g-3">
        <?= csrf_field() ?><input type="hidden" name="action" value="schedule">
        <div class="col-md-6">
            <label class="form-label">Opens</label>
            <input class="form-control" type="datetime-local" name="voting_start" value="<?= e($inp($v['start'])) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Closes</label>
            <input class="form-control" type="datetime-local" name="voting_end" value="<?= e($inp($v['end'])) ?>">
        </div>
        <div class="col-12"><button class="btn btn-primary">Save schedule</button></div>
    </form>

    <hr>

    <h2 class="mt-0">Who can see the results page?</h2>
    <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="results_mode">
        <?php foreach (['live' => ['Live', 'Everyone can watch the results while voting is going on.'],
                        'after_close' => ['After voting closes', 'Hidden until voting is closed, then public.'],
                        'hidden' => ['Hidden', 'Only admins can see results.']] as $val => [$name, $desc]): ?>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="results_mode" id="rm_<?= $val ?>" value="<?= $val ?>" <?= $mode === $val ? 'checked' : '' ?>>
                <label class="form-check-label" for="rm_<?= $val ?>"><strong><?= e($name) ?></strong><br><span class="text-muted"><?= e($desc) ?></span></label>
            </div>
        <?php endforeach; ?>
        <button class="btn btn-primary mt-2">Save</button>
    </form>

    <div class="actions"><a href="admin.php" class="btn btn-outline-primary">Back to dashboard</a></div>

<?php require 'footer.php'; ?>
