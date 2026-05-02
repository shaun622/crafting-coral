<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

// No session_start here — this is a server-to-server call

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    exit;
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $email = strtolower(trim($session->customer_details->email ?? ''));
    $customer_id = $session->customer ?? '';
    $payment_intent = $session->payment_intent ?? '';

    // Pull plan/amount from metadata set in stripe-checkout.php
    $metadata = $session->metadata ?? null;
    $plan = 'lifetime';
    $amount_paid = (int) ($session->amount_total ?? 0);
    if ($metadata) {
        $meta_plan = $metadata->plan ?? '';
        if (in_array($meta_plan, ['annual', 'lifetime'], true)) {
            $plan = $meta_plan;
        }
        if (!empty($metadata->amount_paid)) {
            $amount_paid = (int) $metadata->amount_paid;
        }
    }

    if (!empty($email)) {
        create_member($email, $customer_id, $payment_intent, $plan, $amount_paid);
    }
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
