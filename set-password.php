<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error = null;
$member = false;

if (empty($token)) {
    $error = 'Missing setup token.';
} else {
    $member = get_member_by_setup_token($token);
    if (!$member) {
        $error = 'This setup link has expired or already been used. Please contact hello@craftingcoral.com if you need help.';
    }
}

$csrf_token = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $member) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        $error = 'Session expired. Please reload the page.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        set_member_password($member['email'], $password);
        // Auto-login
        $_SESSION['member_email'] = $member['email'];
        session_regenerate_id(true);
        header('Location: /');
        exit;
    }
}

$page_title = 'Set Your Password — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <?php if (!$member): ?>
                <h1>Link Invalid</h1>
                <div class="alert alert-error"><?= htmlspecialchars($error ?? 'Unknown error.') ?></div>
                <p class="auth-footer-link"><a href="/login.php">Back to login</a></p>
            <?php else: ?>
                <h1>Set Your Password</h1>
                <p>Choose a password for <strong><?= htmlspecialchars($member['email']) ?></strong>. You'll use this to log in to your teaching pack.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="auth-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <?php password_field('password', 'password', 'New password', 'required minlength="8" autocomplete="new-password" autofocus'); ?>
                    <?php password_field('confirm', 'confirm', 'Confirm password', 'required minlength="8" autocomplete="new-password"'); ?>
                    <button type="submit" class="btn btn-primary btn-full">Set Password &amp; Log In</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
