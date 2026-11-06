<?php
require_once __DIR__ . '/functions.php';
$title = 'About Us';
require 'header.php';
?>

    <h1>About Us</h1>

    <p>
        We are a group of college students who created
        this Online Election System as a school project.
    </p>

    <p>
        The system is designed to provide students with
        a simple and convenient way to participate in
        school elections.
    </p>

    <h2>Our Goal</h2>

    <p>
        Our goal is to make the voting process simple,
        organized, and accessible for students.
    </p>

    <div class="actions">
        <?php if (is_logged_in()): ?>
            <a href="home.php" class="btn btn-primary">Back to Dashboard</a>
        <?php else: ?>
            <a href="index.php" class="btn btn-primary">Back to Login</a>
        <?php endif; ?>
    </div>

<?php require 'footer.php'; ?>
