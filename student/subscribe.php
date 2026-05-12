<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$subscription = getActiveSubscription($conn, $userId);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = sanitize($_POST['plan'] ?? '');

    if ($plan === '') {
        $error = 'Please select a subscription plan.';
    } else {
        $expiresAt = date('Y-m-d H:i:s', strtotime($plan === 'Premium Yearly' ? '+365 days' : '+30 days'));

        if ($subscription) {
            $success = dbExecute(
                $conn,
                "UPDATE student_subscriptions SET plan = ?, status = 'active', expires_at = ? WHERE subscription_id = ?",
                "ssi",
                [$plan, $expiresAt, $subscription['subscription_id']]
            );
        } else {
            $success = dbExecute(
                $conn,
                "INSERT INTO student_subscriptions (user_id, plan, status, expires_at) VALUES (?, ?, 'active', ?)",
                "iss",
                [$userId, $plan, $expiresAt]
            );
        }

        if ($success) {
            $message = 'Your premium subscription is now active. Enjoy premium lessons and mentor features.';
            $subscription = getActiveSubscription($conn, $userId);
        } else {
            $error = 'Unable to activate premium subscription. Please try again.';
        }
    }
}

$pageTitle = 'Premium Subscription';
$activePage = 'dashboard';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Premium Access</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Upgrade your learning experience</h1>
    <p class="text-slate-400">Unlock premium lessons, mentor-reviewed tasks, and advanced career content for 30 days.</p>
</div>

<?php if ($error): ?>
    <div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-red-200"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="mb-6 rounded-2xl border border-green-500 bg-green-500/10 p-4 text-green-200"><?= e($message) ?></div>
<?php endif; ?>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="card">
        <h2 class="sectionTitle mb-4">Current Plan</h2>
        <?php if ($subscription): ?>
            <p class="text-slate-300 mb-2">Plan: <?= e($subscription['plan']) ?></p>
            <p class="text-slate-400">Expires: <?= e(date('F j, Y', strtotime($subscription['expires_at']))) ?></p>
        <?php else: ?>
            <p class="text-slate-300">You do not have an active premium subscription.</p>
        <?php endif; ?>
    </div>

    <form method="POST" class="card space-y-6">
        <div>
            <label class="block text-slate-400 mb-2">Select a plan</label>
            <select name="plan" class="inputStyle w-full bg-slate-900 border border-slate-700 rounded-2xl p-3" required>
                <option value="">Choose your plan</option>
                <option value="Premium Monthly">Premium Monthly - 30 days</option>
                <option value="Premium Yearly">Premium Yearly - 12 months</option>
            </select>
        </div>

        <div class="space-y-3 text-slate-400 text-sm">
            <p>Premium includes:</p>
            <ul class="list-disc list-inside">
                <li>Premium PDF lessons</li>
                <li>Mentor-reviewed tasks</li>
                <li>Advanced roadmap unlocks</li>
            </ul>
        </div>

        <button class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-2xl font-semibold" type="submit">
            Activate Premium
        </button>
    </form>
</div>

<?php include '../footer.php'; ?>
