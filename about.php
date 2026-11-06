<?php
require_once __DIR__ . '/functions.php';
$title = 'About';
require 'header.php';
?>

<h1>About the System</h1>
<p class="lead-text">Kolehiyo ng Lungsod ng Dasmariñas - Online Election System</p>

<div class="card p-4 shadow-sm mb-4">
    <h3>Student Council Election 2026</h3>
    <p>This web application is designed to facilitate secure, transparent, and efficient voting for the KLD Online Election.</p>
    <ul>
        <li><strong>Anonymized Voting:</strong> Individual ballot choices are decoupled from student identifiers to ensure vote privacy.</li>
        <li><strong>Real-time Results:</strong> Election turnouts and live candidate statistics are processed dynamically.</li>
    </ul>
</div>

<a href="home.php" class="btn btn-primary">Back to Home</a>

<?php require 'footer.php'; ?>
