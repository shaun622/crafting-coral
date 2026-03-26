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

// --- Content Settings ---

function init_content_settings(): void
{
    $db = get_db();
    $db->exec('CREATE TABLE IF NOT EXISTS content_settings (
        slot TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        btn_label TEXT NOT NULL DEFAULT "Download",
        visible INTEGER DEFAULT 1
    )');

    $defaults = [
        ['infographics', 'Display Graphics', 'Infographics, statistics, images and a workshop summary for your classroom.', 'Download Pack'],
        ['video', 'Video Tutorial', 'Theory and practical guidance to help you deliver the workshop with confidence.', 'Download Video'],
        ['presentation', 'Presentation Deck', 'Ready-to-use classroom slides you can present or adapt to your needs.', 'Download Slides'],
        ['module', 'Teaching Module', 'Structured lesson content with learning objectives, activities and assessment ideas.', 'Download Module'],
        ['360-video', '360° Video', 'Take your students on a virtual visit to our coral restoration site.', 'Download Video'],
    ];

    foreach ($defaults as $d) {
        $stmt = $db->prepare('INSERT OR IGNORE INTO content_settings (slot, title, description, btn_label) VALUES (:slot, :title, :desc, :btn)');
        $stmt->bindValue(':slot', $d[0], SQLITE3_TEXT);
        $stmt->bindValue(':title', $d[1], SQLITE3_TEXT);
        $stmt->bindValue(':desc', $d[2], SQLITE3_TEXT);
        $stmt->bindValue(':btn', $d[3], SQLITE3_TEXT);
        $stmt->execute();
    }
}

function get_content_settings(): array
{
    $db = get_db();
    init_content_settings();
    $results = $db->query('SELECT * FROM content_settings ORDER BY rowid');
    $settings = [];
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $settings[$row['slot']] = $row;
    }
    return $settings;
}

function update_content_setting(string $slot, string $title, string $description, string $btn_label, int $visible): bool
{
    $db = get_db();
    $stmt = $db->prepare('UPDATE content_settings SET title = :title, description = :desc, btn_label = :btn, visible = :vis WHERE slot = :slot');
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
    $stmt->bindValue(':btn', $btn_label, SQLITE3_TEXT);
    $stmt->bindValue(':vis', $visible, SQLITE3_INTEGER);
    $stmt->bindValue(':slot', $slot, SQLITE3_TEXT);
    return $stmt->execute() !== false;
}
