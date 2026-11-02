<?php
const MAIL_HOST      = 'smtp.gmail.com';
const MAIL_PORT      = 587;
const MAIL_USER      = 'onlinekldelection@gmail.com';
const MAIL_PASS      = 'bnrirhdebcquwvgd';
const MAIL_FROM_NAME = 'Online Election';

// Automatically detect host (localhost or live domain)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script   = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$baseUrl  = rtrim($protocol . $host . $script, '/\\');

if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', getenv('APP_BASE_URL') ?: 'https://kld-online-election.onrender.com');
}
