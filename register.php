<?php
require_once __DIR__ . '/functions.php';

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

if (is_logged_in()) redirect(home_for_role());

$error = '';

if (is_post()) {
    csrf_check();

    $studentId = trim(post('student_id'));
    $fullname  = trim(post('fullname'));
    $email     = trim(post('email'));
    $password  = post('password');
    $confirm   = post('confirm_password');

    // 1. Enforce official @kld.edu.ph email domain
    if (!is_kld_email($email)) {
        $error = 'Only official @kld.edu.ph email addresses are allowed to register.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (find_user($studentId)) {
        $error = 'A user with this Student ID is already registered.';
    } else {
        // Verify voter roll if active
        if (roll_is_active() && !roll_entry($studentId)) {
            $error = 'Your Student ID is not listed in the official voter roll.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                // 1. Insert user with email_verified set to 0
                $stmt = db()->prepare('INSERT INTO users (student_id, fullname, email, password, email_verified) VALUES (?, ?, ?, ?, 0)');
                $stmt->execute([$studentId, $fullname, strtolower($email), $hash]);
                
                $userId = db()->lastInsertId();

                // 2. Generate token & SHA-256 hash (matching verify_email.php)
                $rawToken  = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $rawToken);
                $expires   = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmtToken = db()->prepare('INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, ?)');
                $stmtToken->execute([$userId, $tokenHash, $expires]);

                // 3. Send Verification Email via PHPMailer
                $mail = new PHPMailer(true);
                
// Server credentials for Brevo
$mail->isSMTP();
$mail->Host       = 'smtp-relay.brevo.com';
$mail->SMTPAuth   = true;
$mail->Username   = getenv('BREVO_USER'); 
$mail->Password   = getenv('BREVO_API_KEY'); // Matches your BREVO_API_KEY key in Render
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 19320;

// Sender details (Must be a verified sender in Brevo)
$senderEmail = getenv('BREVO_USER');
$mail->setFrom($senderEmail, 'KLD Online Election');
                // Verification Content
                $verifyLink = "https://kld-online-election.onrender.com/verify_email.php?token=" . urlencode($rawToken);

                $mail->isHTML(true);
                $mail->Subject = 'Verify Your KLD Election Account';
                $mail->Body    = "
                    <h3>Welcome to KLD Online Election, " . htmlspecialchars($fullname) . "!</h3>
                    <p>Please click the button below to verify your email address and activate your account:</p>
                    <p><a href='{$verifyLink}' style='background-color:#198754;color:#ffffff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Verify Email Address</a></p>
                    <p>Or copy and paste this link into your browser:<br><a href='{$verifyLink}'>{$verifyLink}</a></p>
                    <br><p>This link expires in 24 hours.</p>
                ";

                $mail->send();

                log_action('registered', "User $studentId registered. Verification email sent.");

                flash('success', 'Registration successful! A verification link has been sent to your email.');
                redirect('index.php');

            } catch (Throwable $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

$title = 'Register';
require 'header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <h2 class="mb-3">Student Registration</h2>
        <p class="text-muted">Register using your official <strong>@kld.edu.ph</strong> email address.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form action="register.php" method="post">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" placeholder="e.g. 2026-12345" value="<?= e(post('student_id')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" placeholder="First Name Last Name" value="<?= e(post('fullname')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">KLD Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="student@kld.edu.ph" value="<?= e(post('email')) ?>" pattern="[a-zA-Z0-9._%+-]+@kld\.edu\.ph$" title="Please enter a valid @kld.edu.ph email address" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register Account</button>
        </form>

        <p class="mt-3 text-center small">
            Already have an account? <a href="index.php">Log in here</a>
        </p>
    </div>
</div>

<?php require 'footer.php'; ?>
