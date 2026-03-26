<?php

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

$page_title = 'Payment Cancelled — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <h1>Payment Cancelled</h1>
            <p>No worries — you can try again whenever you're ready.</p>
            <a href="/" class="btn btn-primary btn-full">Back to Teaching Pack</a>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
