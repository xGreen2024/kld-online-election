<?php
require_once __DIR__ . '/functions.php';
require_admin();

if (is_post() && post('action') === 'reactivate') {
    csrf_check();
    db()->prepare("UPDATE users SET is_active = 1 WHERE student_id = ? AND role = 'student'")
        ->execute([post('student_id')]);
    log_action('student_reactivated', post('student_id'));
    flash('success', 'Account reactivated.');
    redirect('admin.php');
}

$totalStudents = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalVotes    = (int)db()->query("SELECT COUNT(*) FROM ballots")->fetchColumn();
$turnout       = $totalStudents ? round($totalVotes / $totalStudents * 100, 1) : 0;
$results       = tally_votes();
$students      = db()->query("SELECT student_id, fullname, email, has_voted, is_active
                             FROM users WHERE role = 'student' ORDER BY fullname")->fetchAll();

$title = 'Admin Dashboard';
require 'header.php';
?>

<h1>Admin Dashboard</h1>
<p class="lead-text">Student Council Election 2026: overview and results</p>

<?php flash_render(); ?>

<?php $vs = voting_state(); ?>
<!-- Original Dashboard Content Continues -->
