<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$error = null;
$expired_email = null;
$csrf_token = generate_csrf_token();

// Pick up expired-access flash from require_auth()
if (!empty($_SESSION['expired_email'])) {
    $expired_email = $_SESSION['expired_email'];
    unset($_SESSION['expired_email']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($token)) {
        $error = 'Session expired. Please try again.';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $status = get_member_status($email);
        if ($status === 'unknown') {
            $error = 'No account found with that email address.';
        } elseif ($status === 'expired') {
            $expired_email = $email;
        } else {
            // Active — log them in.
            $_SESSION['member_email'] = $email;
            session_regenerate_id(true);
            header('Location: /');
            exit;
        }
    }
}

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
            <?php else: ?>
                <h1>Welcome Back</h1>
                <p>Enter the email you used to purchase the teaching pack.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form action="/login.php" method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@school.ac.uk" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full">Log In</button>
                </form>

                <p class="auth-footer-link">Don't have an account yet? <a href="/#pricing">View pricing</a></p>
                <p class="auth-footer-link"><a href="/">Back to main page</a></p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
