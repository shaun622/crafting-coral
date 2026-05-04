<?php

require_once __DIR__ . '/config.php';

/**
 * Send a transactional email via Resend. Returns true on success.
 * On failure, logs to error_log() and returns false.
 */
function send_email(string $to, string $subject, string $html): bool
{
    $payload = json_encode([
        'from' => FROM_NAME . ' <' . FROM_EMAIL . '>',
        'reply_to' => 'info@craftingcoral.com',
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . RESEND_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 400) {
        error_log("Resend error ({$code}) sending '{$subject}' to {$to}: {$resp}");
        return false;
    }
    return true;
}

/**
 * Send a 6-digit login verification code to the member.
 */
function send_login_code_email(string $to, string $code): bool
{
    $code_safe = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $minutes = (int) (LOGIN_CODE_TTL / 60);

    $body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin: 0; padding: 0; background-color: #f5f0eb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f0eb; padding: 40px 20px;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0" style="max-width: 480px; width: 100%;">
        <tr><td style="background: #0c3547; padding: 32px 40px; border-radius: 12px 12px 0 0; text-align: center;">
          <img src="https://course.craftingcoral.com/assets/logo.svg" alt="Crafting Coral" height="32" style="height: 32px; filter: brightness(0) invert(1);">
        </td></tr>
        <tr><td style="background: #ffffff; padding: 48px 40px; text-align: center;">
          <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 600; color: #0c3547;">Your login code</h1>
          <p style="margin: 0 0 28px; font-size: 15px; line-height: 1.6; color: #5a7080;">Enter this code to finish logging in to your Crafting Coral teaching pack.</p>
          <div style="display: inline-block; background: #f5f0eb; border-radius: 12px; padding: 24px 36px; font-family: 'Courier New', monospace; font-size: 36px; font-weight: 700; letter-spacing: 12px; color: #0c3547;">
            {$code_safe}
          </div>
          <p style="margin: 28px 0 0; font-size: 13px; color: #8a9baa; line-height: 1.5;">This code expires in {$minutes} minutes.<br>If you didn't try to log in, you can safely ignore this email.</p>
        </td></tr>
        <tr><td style="background: #f5f0eb; padding: 24px 40px; text-align: center; border-top: 1px solid #e8e0d8;">
          <p style="margin: 0; font-size: 12px; color: #8a9baa;">Crafting Coral — Conservation through creativity</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    return send_email($to, 'Your Crafting Coral login code: ' . $code, $body);
}
