<?php

/**
 * Crafting Coral — Course Subdomain Configuration
 *
 * Copy this file to config.php and fill in your real values:
 *   cp config.example.php config.php
 */

define('SITE_URL', 'https://course.craftingcoral.com');
define('SITE_NAME', 'Crafting Coral — Teaching Pack');
define('MAIN_SITE_URL', 'https://craftingcoral.com');

// Stripe — Get these from https://dashboard.stripe.com/apikeys
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXXXX');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_XXXXXXXXXXXX');
define('STRIPE_WEBHOOK_SECRET', 'whsec_XXXXXXXXXXXX');
define('STRIPE_PRICE_AMOUNT', 10000); // £100.00 in pence
define('STRIPE_CURRENCY', 'gbp');

// Auth
define('MAGIC_LINK_EXPIRY', 3600); // 1 hour in seconds

// Email (Resend) — get API key from https://resend.com
define('FROM_EMAIL', 'course@craftingcoral.com');
define('FROM_NAME', 'Crafting Coral');
define('RESEND_API_KEY', 're_XXXXXXXXXXXX');

// Database
define('DB_PATH', __DIR__ . '/../members.db');
