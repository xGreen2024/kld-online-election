<?php
require_once __DIR__ . '/functions.php';
$title  = $title  ?? 'Online Election';
$narrow = $narrow ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - KLD Online Election</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Figtree:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="manifest" href="site.webmanifest">
</head>
<body class="<?= is_admin() ? 'admin-theme' : '' ?>">

<nav class="site-nav">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a class="brand" href="<?= is_logged_in() ? home_for_role() : 'index.php' ?>">
            <img class="brand-logo" src="kld-logo.png" alt="KLD logo"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <span class="brand-logo-fallback" aria-hidden="true">KLD</span>
            <span class="brand-text">
                Kolehiyo ng Lungsod ng Dasmariñas
                <small>Online Election System</small>
            </span>
            <?php if (is_admin()): ?><span class="badge-admin">Admin</span><?php endif; ?>
        </a>

        <div class="d-flex align-items-center gap-2 gap-sm-3">
            <?php $cur = basename($_SERVER['SCRIPT_NAME']); ?>
            <span class="nav-links">
                <a href="candidates.php" class="<?= $cur === 'candidates.php' ? 'active' : '' ?>">Candidates</a>
                <?php if (results_visible()): ?><a href="results.php" class="<?= $cur === 'results.php' ? 'active' : '' ?>">Results</a><?php endif; ?>
            </span>

            <?php if (is_logged_in()): ?>
                <span class="nav-user d-none d-md-inline"><?= e($_SESSION['fullname'] ?? $_SESSION['student_id']) ?></span>
                <div class="dropdown">
                    <button class="burger-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open menu">
                        <svg width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
                            <path d="M3 5h16M3 11h16M3 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end burger-menu">
                        <li><a class="dropdown-item" href="<?= home_for_role() ?>">Home</a></li>
                        <?php if (is_admin()): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Admin tools</h6></li>
                            <li><a class="dropdown-item" href="admin_settings.php">Voting &amp; results settings</a></li>
                            <li><a class="dropdown-item" href="admin_candidates.php">Candidates &amp; positions</a></li>
                            <li><a class="dropdown-item" href="students.php">Manage students</a></li>
                            <li><a class="dropdown-item" href="admin_roll.php">Voter list</a></li>
                            <li><a class="dropdown-item" href="admin_announcements.php">Announcements</a></li>
                            <li><a class="dropdown-item" href="admin_log.php">Activity log</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="account.php">Account settings</a></li>
                        <li><a class="dropdown-item" href="faq.php">Help &amp; FAQ</a></li>
                        <li><a class="dropdown-item" href="about.php">About</a></li>
                        <li><a class="dropdown-item" href="privacy.php">Data privacy</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php">Log out</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container page-card<?= $narrow ? ' narrow' : '' ?>">
<?php flash_render(); ?>