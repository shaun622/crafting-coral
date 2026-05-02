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
        plan TEXT DEFAULT "lifetime",
        amount_paid INTEGER DEFAULT 0,
        expires_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    // Migration: add new columns when upgrading from older schema
    $cols = $db->query("PRAGMA table_info(members)");
    $existing = [];
    while ($col = $cols->fetchArray(SQLITE3_ASSOC)) {
        $existing[] = $col['name'];
    }
    if (!in_array('plan', $existing)) {
        $db->exec('ALTER TABLE members ADD COLUMN plan TEXT DEFAULT "lifetime"');
        // All pre-existing members were grandfathered in at the original launch
        // price as lifetime access, so leave them as lifetime.
        $db->exec("UPDATE members SET plan = 'lifetime' WHERE plan IS NULL OR plan = ''");
    }
    if (!in_array('amount_paid', $existing)) {
        $db->exec('ALTER TABLE members ADD COLUMN amount_paid INTEGER DEFAULT 0');
    }
    if (!in_array('expires_at', $existing)) {
        $db->exec('ALTER TABLE members ADD COLUMN expires_at DATETIME DEFAULT NULL');
        // Leave existing rows with NULL expires_at — they're all lifetime now.
    }

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

function create_member(string $email, string $stripe_customer_id, string $stripe_payment_id, string $plan = 'lifetime', int $amount_paid = 0): bool
{
    $db = get_db();
    // Annual: 1 year from now. Lifetime/manual: NULL (never expires).
    $expires_at = $plan === 'annual' ? date('Y-m-d H:i:s', strtotime('+1 year')) : null;

    $stmt = $db->prepare('INSERT OR IGNORE INTO members (email, stripe_customer_id, stripe_payment_id, paid_at, plan, amount_paid, expires_at) VALUES (:email, :cid, :pid, :paid_at, :plan, :amount, :expires_at)');
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $stmt->bindValue(':cid', $stripe_customer_id, SQLITE3_TEXT);
    $stmt->bindValue(':pid', $stripe_payment_id, SQLITE3_TEXT);
    $stmt->bindValue(':paid_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':plan', $plan, SQLITE3_TEXT);
    $stmt->bindValue(':amount', $amount_paid, SQLITE3_INTEGER);
    $stmt->bindValue(':expires_at', $expires_at, $expires_at === null ? SQLITE3_NULL : SQLITE3_TEXT);
    return $stmt->execute() !== false;
}

/**
 * Handle a Stripe checkout for an email that may already be a member.
 * - New email: creates the member with the given plan/amount.
 * - Existing annual member: extends expires_at by 1 year (from current expiry, or now if expired).
 * - Existing lifetime member: no-op (they already have permanent access).
 * - Upgrading annual -> lifetime: switches plan to lifetime, NULLs expires_at.
 */
function record_payment(string $email, string $stripe_customer_id, string $stripe_payment_id, string $plan, int $amount_paid): void
{
    $db = get_db();
    $existing = get_member_by_email($email);

    if (!$existing) {
        create_member($email, $stripe_customer_id, $stripe_payment_id, $plan, $amount_paid);
        return;
    }

    // Idempotency: if this exact payment has already been processed for this
    // member, skip. Prevents the success page + webhook double-extending an
    // annual renewal.
    if (!empty($stripe_payment_id) && ($existing['stripe_payment_id'] ?? '') === $stripe_payment_id) {
        return;
    }

    // Existing lifetime member — nothing to extend, just record the latest payment ref.
    if (($existing['plan'] ?? 'lifetime') === 'lifetime' && $plan === 'annual') {
        // They already have lifetime — no action needed (refund manually if needed).
        return;
    }

    // Upgrade annual -> lifetime
    if ($plan === 'lifetime') {
        $stmt = $db->prepare('UPDATE members SET plan = "lifetime", expires_at = NULL, stripe_customer_id = :cid, stripe_payment_id = :pid, amount_paid = amount_paid + :amount, paid_at = :paid_at WHERE email = :email');
        $stmt->bindValue(':cid', $stripe_customer_id, SQLITE3_TEXT);
        $stmt->bindValue(':pid', $stripe_payment_id, SQLITE3_TEXT);
        $stmt->bindValue(':amount', $amount_paid, SQLITE3_INTEGER);
        $stmt->bindValue(':paid_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
        $stmt->execute();
        return;
    }

    // Annual renewal: extend from later of (current expiry, now) by 1 year.
    $base_ts = strtotime($existing['expires_at'] ?? 'now');
    if ($base_ts === false || $base_ts < time()) {
        $base_ts = time();
    }
    $new_expiry = date('Y-m-d H:i:s', strtotime('+1 year', $base_ts));

    $stmt = $db->prepare('UPDATE members SET expires_at = :exp, stripe_customer_id = :cid, stripe_payment_id = :pid, amount_paid = amount_paid + :amount, paid_at = :paid_at WHERE email = :email');
    $stmt->bindValue(':exp', $new_expiry, SQLITE3_TEXT);
    $stmt->bindValue(':cid', $stripe_customer_id, SQLITE3_TEXT);
    $stmt->bindValue(':pid', $stripe_payment_id, SQLITE3_TEXT);
    $stmt->bindValue(':amount', $amount_paid, SQLITE3_INTEGER);
    $stmt->bindValue(':paid_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->bindValue(':email', strtolower(trim($email)), SQLITE3_TEXT);
    $stmt->execute();
}

/**
 * Returns one of: 'active', 'expired', 'unknown'.
 * Lifetime members and rows with NULL expires_at are always active.
 */
function get_member_status(string $email): string
{
    $member = get_member_by_email($email);
    if (!$member) return 'unknown';
    $expires = $member['expires_at'] ?? null;
    if (empty($expires)) return 'active';
    return strtotime($expires) > time() ? 'active' : 'expired';
}

function is_member_active(string $email): bool
{
    return get_member_status($email) === 'active';
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
        visible INTEGER DEFAULT 1,
        url TEXT DEFAULT NULL
    )');

    // Migration: add url column if upgrading from older schema
    $cols = $db->query("PRAGMA table_info(content_settings)");
    $has_url = false;
    while ($col = $cols->fetchArray(SQLITE3_ASSOC)) {
        if ($col['name'] === 'url') { $has_url = true; break; }
    }
    if (!$has_url) {
        $db->exec('ALTER TABLE content_settings ADD COLUMN url TEXT DEFAULT NULL');
    }

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

function update_content_setting(string $slot, string $title, string $description, string $btn_label, int $visible, ?string $url = null): bool
{
    $db = get_db();
    $stmt = $db->prepare('UPDATE content_settings SET title = :title, description = :desc, btn_label = :btn, visible = :vis, url = :url WHERE slot = :slot');
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $description, SQLITE3_TEXT);
    $stmt->bindValue(':btn', $btn_label, SQLITE3_TEXT);
    $stmt->bindValue(':vis', $visible, SQLITE3_INTEGER);
    $stmt->bindValue(':url', $url !== null && $url !== '' ? $url : null, $url !== null && $url !== '' ? SQLITE3_TEXT : SQLITE3_NULL);
    $stmt->bindValue(':slot', $slot, SQLITE3_TEXT);
    return $stmt->execute() !== false;
}

// --- Pricing ---

function get_paid_member_count(?string $plan = null): int
{
    $db = get_db();
    if ($plan === null) {
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM members WHERE stripe_customer_id LIKE 'cus_%'");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) AS c FROM members WHERE stripe_customer_id LIKE 'cus_%' AND plan = :plan");
        $stmt->bindValue(':plan', $plan, SQLITE3_TEXT);
    }
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    return (int) ($row['c'] ?? 0);
}

function format_price(int $pence): string
{
    return '£' . number_format($pence / 100, 0);
}

/**
 * Returns array of pricing tiers. Annual gets the launch discount until
 * LAUNCH_OFFER_LIMIT annual buyers are reached.
 */
function get_pricing_options(): array
{
    // Annual
    $annual = [
        'plan' => 'annual',
        'name' => 'Digital Teaching Pack',
        'access' => '1 Year Access',
        'amount' => ANNUAL_PRICE,
        'display' => format_price(ANNUAL_PRICE),
        'is_launch' => false,
    ];

    if (LAUNCH_OFFER_ENABLED) {
        $sold = get_paid_member_count('annual');
        $spots_left = LAUNCH_OFFER_LIMIT - $sold;
        if ($spots_left > 0) {
            $annual['amount'] = ANNUAL_LAUNCH_PRICE;
            $annual['display'] = format_price(ANNUAL_LAUNCH_PRICE);
            $annual['is_launch'] = true;
            $annual['spots_left'] = $spots_left;
            $annual['spots_total'] = LAUNCH_OFFER_LIMIT;
            $annual['regular_amount'] = ANNUAL_PRICE;
            $annual['regular_display'] = format_price(ANNUAL_PRICE);
        }
    }

    // Lifetime
    $lifetime = [
        'plan' => 'lifetime',
        'name' => 'Digital Teaching Pack',
        'access' => 'Lifetime Access',
        'amount' => LIFETIME_PRICE,
        'display' => format_price(LIFETIME_PRICE),
        'is_launch' => false,
    ];

    return [
        'annual' => $annual,
        'lifetime' => $lifetime,
    ];
}
