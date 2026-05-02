<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$plan_key = $_GET['plan'] ?? 'annual';
$pricing = get_pricing_options();

if (!isset($pricing[$plan_key])) {
    http_response_code(400);
    echo 'Invalid plan.';
    exit;
}

$tier = $pricing[$plan_key];

if ($plan_key === 'annual') {
    $description = '1 year access to all teaching materials, including future updates released during your access period.';
    if (!empty($tier['is_launch'])) {
        $description .= ' Launch offer — one of the first ' . LAUNCH_OFFER_LIMIT . ' spots.';
    }
    $product_name = 'Crafting Coral Teaching Pack — 1 Year Access';
} else {
    $description = 'Lifetime access to all teaching materials, including all future updates.';
    $product_name = 'Crafting Coral Teaching Pack — Lifetime Access';
}

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'line_items' => [[
            'price_data' => [
                'currency' => STRIPE_CURRENCY,
                'unit_amount' => $tier['amount'],
                'product_data' => [
                    'name' => $product_name,
                    'description' => $description,
                ],
            ],
            'quantity' => 1,
        ]],
        'customer_creation' => 'always',
        'metadata' => [
            'plan' => $plan_key,
            'amount_paid' => (string) $tier['amount'],
            'is_launch' => !empty($tier['is_launch']) ? '1' : '0',
        ],
        'success_url' => SITE_URL . '/stripe-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => SITE_URL . '/stripe-cancel.php',
    ]);

    header('Location: ' . $session->url);
    exit;
} catch (\Exception $e) {
    http_response_code(500);
    echo 'Something went wrong. Please try again or contact hello@craftingcoral.com';
    error_log('Stripe checkout error: ' . $e->getMessage());
}
