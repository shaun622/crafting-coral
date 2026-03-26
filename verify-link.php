<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /login.php');
    exit;
}

$member = get_member_by_token($token);

if ($member) {
    // Valid token — log them in
    $_SESSION['member_email'] = $member['email'];

    // Clear token (single use)
    clear_magic_token($member['email']);

    header('Location: /');
    exit;
}

// Invalid or expired token
$page_title = 'Link Expired — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <h1>Link Expired</h1>
            <p>This login link has expired or has already been used. Please request a new one.</p>
            <a href="/login.php" class="btn btn-primary btn-full">Request New Link</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
