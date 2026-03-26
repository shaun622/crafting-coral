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
