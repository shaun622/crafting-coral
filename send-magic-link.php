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
        $escaped_link = htmlspecialchars($link);
        $body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin: 0; padding: 0; background-color: #f5f0eb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f0eb; padding: 40px 20px;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="max-width: 480px; width: 100%;">

        <!-- Header -->
        <tr><td style="background: #0c3547; padding: 32px 40px; border-radius: 12px 12px 0 0; text-align: center;">
          <img src="https://course.craftingcoral.com/assets/logo.svg" alt="Crafting Coral" height="32" style="height: 32px; filter: brightness(0) invert(1);">
        </td></tr>

        <!-- Body -->
        <tr><td style="background: #ffffff; padding: 48px 40px; text-align: center;">
          <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 600; color: #0c3547;">Welcome back</h1>
          <p style="margin: 0 0 32px; font-size: 15px; line-height: 1.6; color: #5a7080;">Click the button below to access your Crafting Coral teaching materials.</p>
          <a href="{$escaped_link}" style="display: inline-block; background: #42718f; color: #ffffff; padding: 16px 36px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: 600;">Access Your Teaching Pack</a>
          <p style="margin: 32px 0 0; font-size: 13px; color: #8a9baa; line-height: 1.5;">This link expires in 1 hour and can only be used once.</p>
        </td></tr>

        <!-- Footer -->
        <tr><td style="background: #f5f0eb; padding: 24px 40px; text-align: center; border-top: 1px solid #e8e0d8;">
          <p style="margin: 0 0 4px; font-size: 12px; color: #8a9baa;">Crafting Coral — Conservation through creativity</p>
          <p style="margin: 0; font-size: 12px; color: #b0bec5;">If you didn't request this link, you can safely ignore this email.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

        // Send via Resend API
        $resend_data = json_encode([
            'from' => FROM_NAME . ' <' . FROM_EMAIL . '>',
            'reply_to' => 'info@craftingcoral.com',
            'to' => [$email],
            'subject' => $subject,
            'html' => $body,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . RESEND_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $resend_data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

// Always redirect with sent=1 regardless of whether email exists
header('Location: /login.php?sent=1');
exit;
