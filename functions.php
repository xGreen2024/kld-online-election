<?php
date_default_timezone_set('Asia/Manila');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ---------- Application Configuration ----------
if (!defined('APP_BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'kld-online-election.onrender.com';
    define('APP_BASE_URL', getenv('APP_BASE_URL') ?: "$protocol://$host");
}

if (!defined('ADMIN_REGISTER_CODE')) {
    define('ADMIN_REGISTER_CODE', getenv('ADMIN_REGISTER_CODE') ?: 'walangkaninbuseng');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

// ---------- Basic helpers ----------
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header("Location: $url"); exit(); }
function is_post(): bool { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function is_logged_in(): bool { return !empty($_SESSION['logged_in']) && !empty($_SESSION['student_id']); }
function require_login(): void { if (!is_logged_in()) redirect('index.php'); }

function post(string $key, bool $trim = true): string {
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    return $trim ? trim($v) : $v;
}

function flash(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): void {
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
}

function fmt_dt(?string $dt): string { return $dt ? date('F j, Y g:i A', strtotime($dt)) : ''; }

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(400);
        exit('Invalid request. Please go back, refresh the page and try again.');
    }
}

// ---------- Settings (stored in the database) ----------
function setting(string $key, ?string $default = null): ?string {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) { /* table not created yet */ }
    }
    return $cache[$key] ?? $default;
}
function set_setting(string $key, ?string $value): void {
    db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)')->execute([$key, $value]);
}

// ---------- Activity log ----------
function log_action(string $action, string $details = ''): void {
    try {
        db()->prepare('INSERT INTO activity_log (actor_id, actor_name, action, details) VALUES (?, ?, ?, ?)')
            ->execute([$_SESSION['student_id'] ?? 'system', $_SESSION['fullname'] ?? '', $action, mb_substr($details, 0, 500)]);
    } catch (Throwable $e) { /* logging must never break the page */ }
}

// ---------- Users and roles (database) ----------
function find_user(string $sid): ?array {
    $st = db()->prepare('SELECT * FROM users WHERE student_id = ?');
    $st->execute([trim($sid)]);
    return $st->fetch() ?: null;
}

function has_voted(): bool {
    $u = find_user($_SESSION['student_id'] ?? '');
    return $u && (int)$u['has_voted'] === 1;
}

function is_admin(): bool {
    if (!is_logged_in()) return false;
    $u = find_user($_SESSION['student_id']);
    return $u && $u['role'] === 'admin';
}
function home_for_role(): string { return is_admin() ? 'admin.php' : 'home.php'; }

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        flash('danger', 'You do not have access to that page.');
        redirect('home.php');
    }
}
// For student-only pages: admins get sent to their own dashboard.
function require_student(): void {
    require_login();
    if (is_admin()) redirect('admin.php');
}

// ---------- Voter list (who is allowed to register) ----------
function roll_is_active(): bool {
    try { return (int)db()->query('SELECT COUNT(*) FROM voter_roll')->fetchColumn() > 0; }
    catch (Throwable $e) { return false; }
}
function roll_entry(string $sid): ?array {
    $st = db()->prepare('SELECT * FROM voter_roll WHERE student_id = ?');
    $st->execute([trim($sid)]);
    return $st->fetch() ?: null;
}

// ---------- Voting window ----------
// state: 'upcoming' | 'open' | 'closed'
function voting_state(): array {
    $enabled = setting('voting_enabled', '1') === '1';
    $start   = setting('voting_start');
    $end     = setting('voting_end');
    $s = $start ? strtotime($start) : null;
    $e = $end   ? strtotime($end)   : null;
    $now = time();

    if (!$enabled)            $state = 'closed';
    elseif ($s && $now < $s)  $state = 'upcoming';
    elseif ($e && $now > $e)  $state = 'closed';
    else                      $state = 'open';
    return ['state' => $state, 'start' => $start, 'end' => $end, 'start_ts' => $s, 'end_ts' => $e, 'enabled' => $enabled];
}
function voting_is_open(): bool { return voting_state()['state'] === 'open'; }

function require_voting_open(): void {
    if (!voting_is_open()) {
        flash('warning', voting_state()['state'] === 'upcoming' ? 'Voting has not started yet.' : 'Voting is closed.');
        redirect('elections.php');
    }
}

function voting_banner(): string {
    $v = voting_state();
    if ($v['state'] === 'upcoming') {
        $when = $v['start_ts'] ? ' on <strong>' . e(fmt_dt($v['start'])) . '</strong> (<span data-countdown="' . $v['start_ts'] . '"></span>)' : '';
        return '<div class="alert alert-warning">Voting has not started. It opens' . $when . '.</div>';
    }
    if ($v['state'] === 'closed') {
        return '<div class="alert alert-secondary">Voting is closed.</div>';
    }
    if ($v['end_ts']) {
        return '<div class="alert alert-success">Voting is open. It closes on <strong>' . e(fmt_dt($v['end']))
             . '</strong> (<span data-countdown="' . $v['end_ts'] . '"></span> left).</div>';
    }
    return '<div class="alert alert-success">Voting is open.</div>';
}

// Who may see the results page: live | after_close | hidden (admins always can)
function results_visible(): bool {
    if (is_admin()) return true;
    $mode = setting('results_mode', 'live');
    if ($mode === 'live') return true;
    if ($mode === 'after_close') return voting_state()['state'] === 'closed';
    return false;
}

// ---------- Positions and candidates (database) ----------
function load_positions(): array {
    $out = [];
    try {
        $ps = db()->query('SELECT position_key, label FROM positions ORDER BY sort_order, id')->fetchAll();
        $cs = db()->query('SELECT position_key, name FROM candidates ORDER BY sort_order, id')->fetchAll();
        foreach ($ps as $p) $out[$p['position_key']] = ['label' => $p['label'], 'candidates' => []];
        foreach ($cs as $c) if (isset($out[$c['position_key']])) $out[$c['position_key']]['candidates'][] = $c['name'];
        // A position with nobody running cannot be voted on
        return array_filter($out, fn($p) => count($p['candidates']) > 0);
    } catch (Throwable $e) {
        // Tables not created yet: fall back to the original hardcoded ballot
        return [
            'president'      => ['label' => 'President',      'candidates' => ['Jamyl Baloro', 'Aaron Balba']],
            'vice_president' => ['label' => 'Vice President', 'candidates' => ['Mark Patlonag', 'Shin Caibigan']],
            'secretary'      => ['label' => 'Secretary',      'candidates' => ['Tristan Juts', 'Russel Salas']],
        ];
    }
}
define('POSITIONS', load_positions());

// Full candidate rows (photo, platform...) grouped by position key
function candidates_full(): array {
    $out = [];
    try {
        foreach (db()->query('SELECT * FROM candidates ORDER BY sort_order, id') as $c) $out[$c['position_key']][] = $c;
    } catch (Throwable $e) {}
    return $out;
}
function candidate_by_name(string $pos, string $name): ?array {
    foreach (candidates_full()[$pos] ?? [] as $c) if ($c['name'] === $name) return $c;
    return null;
}
function candidate_avatar(?array $c, string $name, string $class = ''): string {
    $photo = $c['photo'] ?? '';
    if ($photo && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $photo)) {
        return '<img class="cand-photo ' . e($class) . '" src="uploads/candidates/' . e($photo) . '" alt="Photo of ' . e($name) . '">';
    }
    $initials = '';
    foreach (preg_split('/\s+/', trim($name)) as $w) { if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1)); }
    return '<span class="cand-photo cand-initials ' . e($class) . '" aria-hidden="true">' . e(mb_substr($initials, 0, 2)) . '</span>';
}
function election_started(): bool {
    try { return (int)db()->query('SELECT COUNT(*) FROM ballots')->fetchColumn() > 0; }
    catch (Throwable $e) { return false; }
}

function save_candidate_photo(array $file): ?string {
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) return null;
    if ($err !== UPLOAD_ERR_OK) throw new RuntimeException('The photo could not be uploaded.');
    if ($file['size'] > 2 * 1024 * 1024) throw new RuntimeException('Photo must be 2 MB or smaller.');
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) throw new RuntimeException('Photo must be a JPG, PNG or WebP image.');
    $dir = __DIR__ . '/uploads/candidates';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Could not create the uploads folder.');
    $name = bin2hex(random_bytes(12)) . '.' . $types[$info[2]];   // extension is chosen by us, never by the user
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) throw new RuntimeException('Could not save the photo.');
    return $name;
}
function delete_candidate_photo(?string $name): void {
    if ($name && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $name)) @unlink(__DIR__ . '/uploads/candidates/' . $name);
}

function send_verification(array $user): bool {
    $apiKey = getenv('BREVO_API_KEY');
    
    // Fallback: skip mail processing if environment key is missing
    if (!$apiKey) {
        return true; 
    }

    $email  = $user['email'];
    $userId = (int)$user['id'];

    // Generate a secure token and hash it for storage in email_verifications table
    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

    try {
        db()->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);
        db()->prepare('INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([$userId, $tokenHash, $expiresAt]);
    } catch (Throwable $e) {
        error_log('Failed to save verification token: ' . $e->getMessage());
        return false;
    }

    $verificationLink = APP_BASE_URL . '/verify_email.php?token=' . urlencode($rawToken);

    $payload = json_encode([
        'sender' => [
            'name'  => 'KLD Online Election',
            'email' => 'aaronbalba059@gmail.com'
        ],
        'to' => [
            ['email' => $email]
        ],
        'subject' => 'Verify Your KLD Election Account',
        'htmlContent' => "
            <h2>Welcome to KLD Online Election!</h2>
            <p>Please click the button below to verify your email address and activate your account:</p>
            <p><a href='{$verificationLink}' style='background-color:#4F46E5;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Verify Email Address</a></p>
            <p>Or paste this link into your browser:<br>{$verificationLink}</p>
        "
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS     => $payload
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

function send_reset_email(string $email): bool {
    $apiKey = getenv('BREVO_API_KEY');
    
    $resetLink = make_reset_link($email);
    if (!$resetLink) {
        return false;
    }

    if (!$apiKey) {
        return true; 
    }

    $payload = json_encode([
        'sender' => [
            'name'  => 'KLD Online Election',
            'email' => 'aaronbalba059@gmail.com'
        ],
        'to' => [
            ['email' => $email]
        ],
        'subject' => 'Reset Your Password - KLD Online Election',
        'htmlContent' => "
            <h2>Password Reset Request</h2>
            <p>We received a request to reset your password for your KLD Online Election account.</p>
            <p>Please click the button below to reset your password:</p>
            <p><a href='{$resetLink}' style='background-color:#EF4444;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
            <p>Or paste this link into your browser:<br>{$resetLink}</p>
            <p><small>If you did not request this, you can safely ignore this email.</small></p>
        "
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS     => $payload
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

function make_reset_link(string $email): string {
    $stmt = db()->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user) {
        return '';
    }

    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    try {
        db()->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([(int)$user['id']]);
        db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
            ->execute([(int)$user['id'], $tokenHash, $expiresAt]);
    } catch (Throwable $e) {
        error_log('Failed to save reset token: ' . $e->getMessage());
        return '';
    }

    return APP_BASE_URL . '/reset_password.php?token=' . urlencode($rawToken);
}

const RESET_DEMO_MODE = false;
// ---------- Remember me ----------
const REMEMBER_DAYS = 30;

function remember_issue(int $uid): void {
    $selector  = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $expires   = time() + 86400 * REMEMBER_DAYS;
    db()->exec('DELETE FROM remember_tokens WHERE expires_at < NOW()');
    db()->prepare('INSERT INTO remember_tokens (selector, validator_hash, user_id, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$selector, hash('sha256', $validator), $uid, date('Y-m-d H:i:s', $expires)]);
    setcookie('kld_remember', "$selector:$validator", [
        'expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
    ]);
}
function remember_forget(?int $uid = null): void {
    try {
        if ($uid !== null) {
            db()->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$uid]);
        } elseif (!empty($_COOKIE['kld_remember']) && is_string($_COOKIE['kld_remember'])) {
            $sel = explode(':', $_COOKIE['kld_remember'])[0];
            db()->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$sel]);
        }
    } catch (Throwable $e) {}
    setcookie('kld_remember', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
function remember_login(): void {
    if (is_logged_in() || empty($_COOKIE['kld_remember']) || !is_string($_COOKIE['kld_remember'])) return;
    $parts = explode(':', $_COOKIE['kld_remember']);
    if (count($parts) !== 2) return;
    try {
        $st = db()->prepare('SELECT u.*, rt.id AS token_id, rt.validator_hash
                             FROM remember_tokens rt JOIN users u ON u.id = rt.user_id
                             WHERE rt.selector = ? AND rt.expires_at >= NOW()');
        $st->execute([$parts[0]]);
        $row = $st->fetch();
        if (!$row || !hash_equals($row['validator_hash'], hash('sha256', $parts[1])) || !(int)$row['is_active']) {
            remember_forget();
            return;
        }
        db()->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$row['token_id']]);
        session_regenerate_id(true);
        $_SESSION['logged_in']  = true;
        $_SESSION['student_id'] = $row['student_id'];
        $_SESSION['fullname']   = $row['fullname'];
        $_SESSION['role']       = $row['role'] ?? 'student';
        remember_issue((int)$row['id']);   // rotate the token on every use
    } catch (Throwable $e) { /* table not created yet */ }
}

// ---------- Results ----------
function tally_votes(): array {
    $out = [];
    foreach (POSITIONS as $k => $p) foreach ($p['candidates'] as $c) $out[$k][$c] = 0;
    $rows = db()->query('SELECT position_key, candidate, COUNT(*) AS n
                         FROM ballot_choices GROUP BY position_key, candidate')->fetchAll();
    foreach ($rows as $r) {
        if (isset($out[$r['position_key']][$r['candidate']])) {
            $out[$r['position_key']][$r['candidate']] = (int)$r['n'];
        }
    }
    return $out;
}

function is_kld_email(string $email): bool {
    return (bool) preg_match('/^[a-zA-Z0-9._%+-]+@kld\.edu\.ph$/i', trim($email));
}

remember_login();


