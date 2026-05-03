<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$sent = false;
$error = null;
$csrf_token = generate_csrf_token();
$prefill_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $token = $_POST['csrf_token'] ?? '';
    $prefill_email = $email;

    if (!verify_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Always behave the same whether or not the email is a member, so
        // attackers can't enumerate accounts. Only generate + send if member exists.
        $member = get_member_by_email($email);
        if ($member) {
            $setup_token = create_setup_token($email, 3600); // 1 hour expiry for self-serve resets
            if ($setup_token) {
                $link = SITE_URL . '/set-password.php?token=' . $setup_token;
                send_password_reset_email($email, $link);
            }
        }
        $sent = true;
    }
}

function send_password_reset_email(string $to, string $link): void
{
    $escaped_link = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
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
          <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 600; color: #0c3547;">Reset your password</h1>
          <p style="margin: 0 0 32px; font-size: 15px; line-height: 1.6; color: #5a7080;">Click the button below to set a new password for your Crafting Coral account.</p>
          <a href="{$escaped_link}" style="display: inline-block; background: #42718f; color: #ffffff; padding: 16px 36px; border-radius: 8px; text-decoration: none; font-size: 16px; font-weight: 600;">Set New Password</a>
          <p style="margin: 32px 0 0; font-size: 13px; color: #8a9baa; line-height: 1.5;">This link expires in 1 hour and can only be used once.<br>If you didn't request a reset, you can safely ignore this email.</p>
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

    $payload = json_encode([
        'from' => FROM_NAME . ' <' . FROM_EMAIL . '>',
        'reply_to' => 'info@craftingcoral.com',
        'to' => [$to],
        'subject' => 'Reset Your Crafting Coral Password',
        'html' => $body,
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 400) {
        error_log('Resend error sending password reset to ' . $to . ': HTTP ' . $http_code . ' — ' . $resp);
    }
}

$page_title = 'Forgot Password — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <h1>Forgot Password</h1>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    If that email matches an account, we've sent a reset link. Check your inbox (and spam folder). The link expires in 1 hour.
                </div>
                <p class="auth-footer-link"><a href="/login.php">Back to login</a></p>
            <?php else: ?>
                <p>Enter the email associated with your account and we'll send you a link to set a new password.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@school.ac.uk" value="<?= htmlspecialchars($prefill_email) ?>" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Send Reset Link</button>
                </form>

                <p class="auth-footer-link"><a href="/login.php">Back to login</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
