<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

// Verify CSRF
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    header('Location: /login.php');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));

if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $member = get_member_by_email($email);

    if ($member) {
        // Generate magic link token
        $magic_token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + MAGIC_LINK_EXPIRY);

        set_magic_token($email, $magic_token, $expires);

        // Build magic link
        $link = SITE_URL . '/verify-link.php?token=' . $magic_token;

        // Send email
        $subject = 'Your Crafting Coral Login Link';
        $body = "<!DOCTYPE html><html><body style=\"font-family: Arial, sans-serif; color: #1a1a1a; max-width: 500px; margin: 0 auto;\">"
            . "<div style=\"padding: 30px 20px; text-align: center;\">"
            . "<h2 style=\"color: #0c3547;\">Crafting Coral</h2>"
            . "<p>Click the button below to access your teaching materials:</p>"
            . "<p style=\"margin: 30px 0;\"><a href=\"" . htmlspecialchars($link) . "\" style=\"background: #42718f; color: #fff; padding: 14px 28px; border-radius: 6px; text-decoration: none; display: inline-block;\">Access Your Teaching Pack</a></p>"
            . "<p style=\"font-size: 13px; color: #666;\">This link expires in 1 hour. If you didn't request this, you can safely ignore this email.</p>"
            . "</div></body></html>";

        $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n"
            . "Reply-To: " . FROM_EMAIL . "\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n";

        mail($email, $subject, $body, $headers);
    }
}

// Always redirect with sent=1 regardless of whether email exists
header('Location: /login.php?sent=1');
exit;
