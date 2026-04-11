<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$price = get_current_price();

$product_description = 'Lifetime access to all teaching materials, including future updates.';
if ($price['is_launch']) {
    $product_description .= ' Launch offer — one of the first ' . LAUNCH_OFFER_LIMIT . ' spots.';
}

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'mode' => 'payment',
        'line_items' => [[
            'price_data' => [
                'currency' => STRIPE_CURRENCY,
                'unit_amount' => $price['amount'],
                'product_data' => [
                    'name' => 'Crafting Coral Teaching Pack',
                    'description' => $product_description,
                ],
            ],
            'quantity' => 1,
        ]],
        'customer_creation' => 'always',
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
