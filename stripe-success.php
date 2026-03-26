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
            // Create member if webhook hasn't fired yet (race condition fix)
            $customer_id = $session->customer ?? '';
            $payment_intent = $session->payment_intent ?? '';
            create_member($email, $customer_id, $payment_intent);

            // Log them in
            $_SESSION['member_email'] = $email;
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
