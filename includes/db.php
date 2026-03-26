<?php

require_once __DIR__ . '/config.php';

function get_db(): SQLite3
{
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode=WAL');
    $db->exec('CREATE TABLE IF NOT EXISTS members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        stripe_customer_id TEXT,
        stripe_payment_id TEXT,
        paid_at DATETIME NOT NULL,
        magic_token TEXT,
        magic_token_expires DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    return $db;
}

function get_member_by_email(string $email): array|false
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM members WHERE email = :email');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC) ?: false;
}

function create_member(string $email, string $stripe_customer_id, string $stripe_payment_id): bool
{
    $db = get_db();
    $stmt = $db->prepare('INSERT OR IGNORE INTO members (email, stripe_customer_id, stripe_payment_id, paid_at) VALUES (:email, :cid, :pid, :paid_at)');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $stmt->bindValue(':cid', $stripe_customer_id, SQLITE3_TEXT);
    $stmt->bindValue(':pid', $stripe_payment_id, SQLITE3_TEXT);
    $stmt->bindValue(':paid_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    return $stmt->execute() !== false;
}

function set_magic_token(string $email, string $token, string $expires): bool
{
    $db = get_db();
    $stmt = $db->prepare('UPDATE members SET magic_token = :token, magic_token_expires = :expires WHERE email = :email');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->bindValue(':expires', $expires, SQLITE3_TEXT);
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    return $stmt->execute() !== false;
}

function get_member_by_token(string $token): array|false
{
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM members WHERE magic_token = :token AND magic_token_expires > :now');
    $stmt->bindValue(':token', $token, SQLITE3_TEXT);
    $stmt->bindValue(':now', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC) ?: false;
}

function clear_magic_token(string $email): bool
{
    $db = get_db();
    $stmt = $db->prepare('UPDATE members SET magic_token = NULL, magic_token_expires = NULL WHERE email = :email');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    return $stmt->execute() !== false;
}
