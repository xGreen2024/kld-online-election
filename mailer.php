<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mail.php';

function send_mail(string $to, string $subject, string $html, string $text): bool {
    $m = new PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host       = MAIL_HOST;
        $m->SMTPAuth   = true;
        $m->Username   = MAIL_USER;
        $m->Password   = MAIL_PASS;
        $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $m->Port       = MAIL_PORT;
        $m->CharSet    = 'UTF-8';

        $m->setFrom(MAIL_USER, MAIL_FROM_NAME);
        $m->addAddress($to);

        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body    = $html;
        $m->AltBody = $text;

        $m->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $m->ErrorInfo);
        return false;
    }
}