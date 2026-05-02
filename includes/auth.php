<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['member_email']) && !empty($_SESSION['member_email']);
}

function require_auth(): void
{
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }

    // Check membership is still active (handles expired annual access)
    require_once __DIR__ . '/db.php';
    $email = $_SESSION['member_email'];
    if (!is_member_active($email)) {
        // Save the expired email for the login page so it can offer renewal
        $expired = $email;
        $_SESSION = [];
        session_regenerate_id(true);
        // Re-open a fresh session and stash a flash message
        $_SESSION['expired_email'] = $expired;
        header('Location: /login.php?expired=1');
        exit;
    }
}

function get_member_email(): ?string
{
    return $_SESSION['member_email'] ?? null;
}

function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
