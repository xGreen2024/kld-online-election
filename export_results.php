<?php
require_once __DIR__ . '/functions.php';
require_admin();

$results = tally_votes();
log_action('results_exported', 'CSV download');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="election-results-' . date('Y-m-d-His') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");   // so Excel reads UTF-8 correctly
fputcsv($out, ['Position', 'Candidate', 'Votes', 'Percent of votes for position']);
foreach (POSITIONS as $key => $pos) {
    $total = array_sum($results[$key]);
    foreach ($results[$key] as $name => $count) {
        fputcsv($out, [$pos['label'], $name, $count, $total ? round($count / $total * 100, 1) . '%' : '0%']);
    }
}
fputcsv($out, []);
$totalStudents = (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalVotes    = (int)db()->query('SELECT COUNT(*) FROM ballots')->fetchColumn();
fputcsv($out, ['Registered students', $totalStudents]);
fputcsv($out, ['Votes cast', $totalVotes]);
fputcsv($out, ['Turnout', $totalStudents ? round($totalVotes / $totalStudents * 100, 1) . '%' : '0%']);
fputcsv($out, ['Exported', date('Y-m-d H:i:s')]);
fclose($out);
