<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}

$plans = [
    'monthly' => ['label' => 'Monthly', 'amount' => 499.00, 'months' => 1],
    'quarterly' => ['label' => 'Quarterly', 'amount' => 1299.00, 'months' => 3],
    'annual' => ['label' => 'Annual', 'amount' => 4499.00, 'months' => 12],
];
$methods = ['GCash', 'Maya', 'Visa', 'Mastercard'];

$planType = sanitize($_POST['plan_type'] ?? '');
$method = sanitize($_POST['payment_method'] ?? '');

if (!isset($plans[$planType]) || !in_array($method, $methods, true)) {
    jsonResponse(['success' => false, 'message' => 'Choose a valid plan and payment method.'], 422);
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
