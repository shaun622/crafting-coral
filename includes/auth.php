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

/**
 * Render a labeled password input with a show/hide reveal toggle.
 * $attrs is a raw (already-trusted) attribute string appended to the input,
 * e.g. 'required autocomplete="new-password" minlength="8"'.
 */
function password_field(string $id, string $name, string $label, string $attrs = ''): void
{
    $id_e = htmlspecialchars($id);
    $name_e = htmlspecialchars($name);
    $label_e = htmlspecialchars($label);
    echo <<<HTML
<div class="form-group">
    <label for="{$id_e}">{$label_e}</label>
    <div class="password-wrap">
        <input type="password" id="{$id_e}" name="{$name_e}" {$attrs}>
        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">
            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
    </div>
</div>
HTML;
}
