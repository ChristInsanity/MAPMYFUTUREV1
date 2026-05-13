<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$plans = [
    'monthly' => ['label' => 'Monthly', 'amount' => 499.00, 'months' => 1],
    'three_months' => ['label' => '3 Months', 'amount' => 1299.00, 'months' => 3],
    'quarterly' => ['label' => '3 Months', 'amount' => 1299.00, 'months' => 3],
    'annual' => ['label' => 'Annual', 'amount' => 4499.00, 'months' => 12],
];
$methods = ['GCash', 'Maya', 'Card'];

$planType = sanitize($_POST['plan_type'] ?? '');
$method = sanitize($_POST['payment_method'] ?? '');

if (!isset($plans[$planType]) || !in_array($method, $methods, true)) {
    jsonResponse(['success' => false, 'message' => 'Choose a valid plan and payment method.'], 422);
}

if (in_array($method, ['GCash', 'Maya'], true)) {
    if (sanitize($_POST['mobile_number'] ?? '') === '' || sanitize($_POST['account_name'] ?? '') === '') {
        jsonResponse(['success' => false, 'message' => 'Enter your mobile number and account name.'], 422);
    }
}

if ($method === 'Card') {
    if (sanitize($_POST['card_number'] ?? '') === '' || sanitize($_POST['expiry'] ?? '') === '' || sanitize($_POST['cvv'] ?? '') === '') {
        jsonResponse(['success' => false, 'message' => 'Enter card number, expiry, and CVV.'], 422);
    }
}

$plan = $plans[$planType];
$ok = activatePremiumSubscription($conn, (int)$_SESSION['user_id'], $planType, $plan['amount'], $plan['months'], $method);

jsonResponse([
    'success' => $ok,
    'message' => $ok ? 'Premium Active.' : 'Unable to activate premium.',
    'plan' => $plan['label'],
    'amount' => $plan['amount']
], $ok ? 200 : 500);
?>
