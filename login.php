<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: /');
    exit;
}

$sent = isset($_GET['sent']);
$csrf_token = generate_csrf_token();
$page_title = 'Log In — ' . SITE_NAME;

require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <h1>Welcome Back</h1>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    If that email has an active membership, you'll receive a login link shortly. Check your inbox (and spam folder).
                </div>
            <?php endif; ?>

            <p>Enter the email you used to purchase the teaching pack.</p>

            <form action="/send-magic-link.php" method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="you@school.ac.uk">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Send Login Link</button>
            </form>

            <p class="auth-footer-link"><a href="/">Back to main page</a></p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
