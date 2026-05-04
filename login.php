<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = null;
$expired_email = null;
$no_password_email = null;
$csrf_token = generate_csrf_token();
$prefill_email = '';

// Pick up expired-access flash from require_auth()
if (!empty($_SESSION['expired_email'])) {
    $expired_email = $_SESSION['expired_email'];
    unset($_SESSION['expired_email']);
}

// --- POST handling ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'password';
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    }

    // Step 1: email + password → send code
    if (!$error && $action === 'password') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $prefill_email = $email;

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (empty($password)) {
            $error = 'Please enter your password.';
        } else {
            $status = get_member_status($email);
            if ($status === 'unknown') {
                $error = 'No account found with that email address.';
            } elseif ($status === 'expired') {
                $expired_email = $email;
            } elseif (!member_has_password($email)) {
                $no_password_email = $email;
            } elseif (!verify_member_password($email, $password)) {
                $error = 'Incorrect password. Please try again.';
            } else {
                // Password verified — send a login code and switch to code-entry state
                $code = set_login_code($email);
                if ($code) {
                    send_login_code_email($email, $code);
                    $_SESSION['pending_2fa_email'] = $email;
                    $_SESSION['pending_2fa_started'] = time();
                    header('Location: /login.php');
                    exit;
                } else {
                    $error = 'Could not generate a login code. Please try again.';
                }
            }
        }
    }

    // Step 2: verify code
    if (!$error && $action === 'code' && !empty($_SESSION['pending_2fa_email'])) {
        $email = $_SESSION['pending_2fa_email'];
        $code = preg_replace('/\D+/', '', $_POST['code'] ?? '');

        if (strlen($code) !== 6) {
            $error = 'Please enter the 6-digit code from your email.';
        } else {
            $result = verify_login_code($email, $code);
            switch ($result) {
                case 'ok':
                    clear_login_code($email);
                    unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_started']);
                    $_SESSION['member_email'] = $email;
                    session_regenerate_id(true);
                    header('Location: /');
                    exit;
                case 'wrong':
                    $error = 'Incorrect code. Please check and try again.';
                    break;
                case 'expired':
                    $error = 'That code has expired. Click "Resend code" to get a new one.';
                    break;
                case 'no_code':
                    $error = 'No active code found. Please log in again.';
                    unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_started']);
                    break;
                case 'too_many_attempts':
                    $error = 'Too many incorrect attempts. Please log in again.';
                    unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_started']);
                    break;
            }
        }
    }

    // Resend code
    if (!$error && $action === 'resend' && !empty($_SESSION['pending_2fa_email'])) {
        $email = $_SESSION['pending_2fa_email'];
        $code = set_login_code($email);
        if ($code) {
            send_login_code_email($email, $code);
            $_SESSION['pending_2fa_started'] = time();
        }
        header('Location: /login.php?resent=1');
        exit;
    }

    // Cancel pending 2FA — back to password step
    if ($action === 'cancel') {
        unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_started']);
        header('Location: /login.php');
        exit;
    }
}

// Auto-expire stale 2FA sessions (e.g. user walked away for an hour)
if (!empty($_SESSION['pending_2fa_started']) && time() - $_SESSION['pending_2fa_started'] > 1800) {
    unset($_SESSION['pending_2fa_email'], $_SESSION['pending_2fa_started']);
}

$pending_email = $_SESSION['pending_2fa_email'] ?? null;
$resent = !empty($_GET['resent']);

$page_title = 'Log In — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <?php if ($expired_email): ?>
                <h1>Access Expired</h1>
                <p>Your 1-year access for <strong><?= htmlspecialchars($expired_email) ?></strong> has expired.</p>
                <p>Renew your access below to continue using the teaching pack, or upgrade to lifetime access for a one-time payment.</p>

                <div class="renew-actions">
                    <a href="/stripe-checkout.php?plan=annual&amp;renew=1&amp;email=<?= urlencode($expired_email) ?>" class="btn btn-primary btn-full">
                        Renew 1 Year Access &mdash; <?= format_price(ANNUAL_PRICE) ?>
                    </a>
                    <a href="/stripe-checkout.php?plan=lifetime&amp;email=<?= urlencode($expired_email) ?>" class="btn btn-secondary btn-full">
                        Upgrade to Lifetime &mdash; <?= format_price(LIFETIME_PRICE) ?>
                    </a>
                </div>

                <p class="auth-footer-link"><a href="/login.php">Use a different email</a></p>

            <?php elseif ($no_password_email): ?>
                <h1>Set Up Your Password</h1>
                <p>Your account for <strong><?= htmlspecialchars($no_password_email) ?></strong> doesn't have a password set yet.</p>
                <p><a href="/forgot-password.php" class="btn btn-primary btn-full">Email Me a Setup Link</a></p>
                <p class="auth-footer-link"><a href="/login.php">Use a different email</a></p>

            <?php elseif ($pending_email): ?>
                <h1>Check Your Email</h1>
                <p>We've sent a 6-digit login code to <strong><?= htmlspecialchars($pending_email) ?></strong>. Enter it below to finish logging in.</p>

                <?php if ($resent): ?>
                    <div class="alert alert-success">A new code has been sent. Check your inbox.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="/login.php" method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="code">
                    <div class="form-group">
                        <label for="code">6-digit code</label>
                        <input type="text" id="code" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456" autofocus class="code-input">
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Verify &amp; Log In</button>
                </form>

                <form method="POST" style="margin-top: 16px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="link-button">Didn't get it? Resend code</button>
                </form>

                <form method="POST" style="margin-top: 4px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="link-button">Use a different account</button>
                </form>

            <?php else: ?>
                <h1>Welcome Back</h1>
                <p>Log in with the email and password from your purchase.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="/login.php" method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@school.ac.uk" value="<?= htmlspecialchars($prefill_email) ?>" autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Continue</button>
                </form>

                <p class="auth-footer-link"><a href="/forgot-password.php">Forgot your password?</a></p>
                <p class="auth-footer-link">Don't have an account yet? <a href="/#pricing">View pricing</a></p>
                <p class="auth-footer-link"><a href="/">Back to main page</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
