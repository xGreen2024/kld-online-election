<?php
require_once __DIR__ . '/functions.php';
$title = 'Data Privacy Policy';
require 'header.php';
?>

    <h1>Data Privacy Policy</h1>

    <p>
        This Online Election System values the privacy
        of its users.
    </p>

    <h2>Information We Collect</h2>

    <p>
        The system may collect information such as
        student name, student ID, email, and password
        for registration and account purposes.
    </p>

    <h2>Use of Information</h2>

    <p>
        The information provided by students is intended
        for the use of the election system and should
        only be handled for its intended purpose.
    </p>

    <h2>Privacy and Security</h2>

    <p>
        User information should be kept secure and
        should not be shared with unauthorized persons.
    </p>

    <h2>Consent</h2>

    <p>
        By using this system, students acknowledge the
        collection and use of their information for the
        purpose of participating in the election.
    </p>

    <div class="actions">
        <?php if (is_logged_in()): ?>
            <a href="home.php" class="btn btn-primary">Back to Dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary">Back to Login</a>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>
