<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$session_id = $_GET['session_id'] ?? '';

if (empty($session_id)) {
    header('Location: /');
    exit;
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $session = \Stripe\Checkout\Session::retrieve($session_id);

    if ($session->payment_status === 'paid') {
        $email = strtolower(trim($session->customer_details->email ?? ''));

        if (!empty($email)) {
            // Pull plan/amount from Stripe metadata (set in stripe-checkout.php)
            $customer_id = $session->customer ?? '';
            $payment_intent = $session->payment_intent ?? '';
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

            // Idempotent — if webhook already created/renewed this member, this is a no-op for new records
            // and a duplicate update for renewals. Stripe webhook duplicates are rare; OK for now.
            record_payment($email, $customer_id, $payment_intent, $plan, $amount_paid);

            // Log them in
            $_SESSION['member_email'] = $email;
            session_regenerate_id(true);
        }

        header('Location: /');
        exit;
    }
} catch (\Exception $e) {
    error_log('Stripe success page error: ' . $e->getMessage());
}

// If we get here, something went wrong
$page_title = 'Payment Processing — ' . SITE_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <div class="container">
        <div class="auth-card">
            <h1>Processing Your Payment</h1>
            <p>Your payment is being processed. If you're not redirected automatically, please <a href="/login.php">log in with your email</a> in a few minutes.</p>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
