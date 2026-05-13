<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$subscription = getActiveSubscription($conn, $userId);
$plans = [
    ['type' => 'monthly', 'name' => 'Monthly', 'amount' => 499, 'months' => 1, 'tag' => 'Flexible'],
    ['type' => 'quarterly', 'name' => 'Quarterly', 'amount' => 1299, 'months' => 3, 'tag' => 'Most Popular'],
    ['type' => 'annual', 'name' => 'Annual', 'amount' => 4499, 'months' => 12, 'tag' => 'Best Value'],
];
$methods = ['GCash', 'Maya', 'Visa', 'Mastercard'];

$pageTitle = 'Premium Subscription';
$activePage = 'subscription';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Premium']
];
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Premium Access</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Upgrade your learning support</h1>
    <p class="text-slate-400">Premium unlocks mentor enrollment, premium lessons, premium quizzes, mentor feedback, and portfolio reviews.</p>
</div>

<div id="premiumStatus" class="<?= $subscription ? '' : 'hidden' ?> mb-6 rounded-2xl border border-green-500/30 bg-green-500/10 p-4 text-green-200 inline-flex items-center gap-3">
    <i class="fa-solid fa-crown"></i>
    <span>Premium Active<?= $subscription ? ' until ' . e(date('F j, Y', strtotime($subscription['expires_at']))) : '' ?></span>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-8">
    <?php foreach ($plans as $plan): ?>
        <article class="card <?= $plan['type'] === 'quarterly' ? 'border-blue-500/50' : '' ?>">
            <div class="flex items-center justify-between mb-5">
                <h2 class="sectionTitle"><?= e($plan['name']) ?></h2>
                <span class="badge <?= $plan['type'] === 'annual' ? 'text-green-300 border-green-500/30 bg-green-500/10' : 'text-blue-300 border-blue-500/30 bg-blue-500/10' ?>"><?= e($plan['tag']) ?></span>
            </div>
            <p class="text-4xl font-bold mb-1">₱<?= number_format($plan['amount']) ?></p>
            <p class="text-slate-400 mb-5"><?= (int)$plan['months'] ?> month<?= $plan['months'] > 1 ? 's' : '' ?> access</p>
            <button type="button" class="primaryBtn w-full choosePlanBtn" data-plan="<?= e($plan['type']) ?>" data-name="<?= e($plan['name']) ?>" data-amount="<?= (int)$plan['amount'] ?>">
                Select Plan
            </button>
        </article>
    <?php endforeach; ?>
</div>

<section class="card">
    <h2 class="sectionTitle mb-4">Premium Benefits</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm text-slate-300">
        <div><i class="fa-solid fa-users text-blue-300 mr-2"></i>Mentor enrollment</div>
        <div><i class="fa-solid fa-book-open text-blue-300 mr-2"></i>Premium lessons</div>
        <div><i class="fa-solid fa-brain text-blue-300 mr-2"></i>Premium quizzes</div>
        <div><i class="fa-solid fa-comments text-blue-300 mr-2"></i>Mentor feedback</div>
        <div><i class="fa-solid fa-folder-open text-blue-300 mr-2"></i>Portfolio reviews</div>
    </div>
</section>

<div id="paymentModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8">
    <div class="max-w-md mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div id="paymentStep">
            <h2 class="text-2xl font-bold mb-2">Complete Upgrade</h2>
            <p class="text-slate-400 mb-5"><span id="selectedPlanName"></span> plan - ₱<span id="selectedAmount"></span></p>
            <input type="hidden" id="selectedPlanType">
            <div class="grid grid-cols-2 gap-3 mb-5">
                <?php foreach ($methods as $method): ?>
                    <button type="button" class="methodBtn bg-[#020B24] border border-[#334155] rounded-xl p-4 hover:border-blue-500" data-method="<?= e($method) ?>">
                        <?= e($method) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="closePaymentModal()" class="secondaryBtn w-full">Cancel</button>
        </div>
        <div id="processingStep" class="hidden text-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-4xl text-blue-300 mb-4"></i>
            <h2 class="text-xl font-bold">Processing payment simulation</h2>
        </div>
        <div id="successStep" class="hidden text-center py-8">
            <i class="fa-solid fa-circle-check text-5xl text-green-300 mb-4"></i>
            <h2 class="text-2xl font-bold mb-2">Premium Active</h2>
            <p class="text-slate-400 mb-5">Your subscription has been updated.</p>
            <button type="button" onclick="window.location.reload()" class="primaryBtn">Done</button>
        </div>
    </div>
</div>

<script>
const paymentModal = document.getElementById('paymentModal');
document.querySelectorAll('.choosePlanBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('selectedPlanType').value = button.dataset.plan;
        document.getElementById('selectedPlanName').textContent = button.dataset.name;
        document.getElementById('selectedAmount').textContent = Number(button.dataset.amount).toLocaleString();
        paymentModal.classList.remove('hidden');
    });
});

function closePaymentModal() {
    paymentModal.classList.add('hidden');
}

document.querySelectorAll('.methodBtn').forEach(button => {
    button.addEventListener('click', async () => {
        document.getElementById('paymentStep').classList.add('hidden');
        document.getElementById('processingStep').classList.remove('hidden');

        setTimeout(async () => {
            const result = await window.mmfPost('ajax_subscription.php', {
                plan_type: document.getElementById('selectedPlanType').value,
                payment_method: button.dataset.method
            });

            document.getElementById('processingStep').classList.add('hidden');
            if (result.success) {
                document.getElementById('successStep').classList.remove('hidden');
                document.getElementById('premiumStatus').classList.remove('hidden');
            } else {
                alert(result.message || 'Unable to activate premium.');
                document.getElementById('paymentStep').classList.remove('hidden');
            }
        }, 900);
    });
});
</script>

<?php include '../footer.php'; ?>
