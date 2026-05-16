<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$subscription = getActiveSubscription($conn, $userId);
$plans = [
    ['type' => 'monthly', 'name' => 'Monthly', 'amount' => 499, 'months' => 1, 'savings' => 'Flexible monthly access'],
    ['type' => 'three_months', 'name' => '3 Months', 'amount' => 1299, 'months' => 3, 'savings' => 'Save compared with monthly'],
    ['type' => 'annual', 'name' => 'Annual', 'amount' => 4499, 'months' => 12, 'savings' => 'Best value for long-term mentorship'],
];

$pageTitle = 'Premium Subscription';
$activePage = 'subscription';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Premium']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Activate Premium</h1>
</div>

<?php if ($subscription): ?>
<div class="mb-8 card border-green-500/40">
    <div class="flex items-center gap-3 text-green-200">
        <i class="fa-solid fa-crown"></i>
        <strong>Premium Active</strong>
        <span class="text-slate-400">until <?= e(date('F j, Y', strtotime($subscription['expires_at']))) ?></span>
    </div>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-5 mb-8">
    <?php foreach ($plans as $plan): ?>
        <article class="card <?= $plan['type'] === 'three_months' ? 'border-blue-500/60' : '' ?>">
            <div class="flex justify-between items-start gap-3 mb-5">
                <h2 class="sectionTitle"><?= e($plan['name']) ?></h2>
                <?php if ($plan['type'] === 'three_months'): ?><span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10">Popular</span><?php endif; ?>
                <?php if ($plan['type'] === 'annual'): ?><span class="badge text-green-200 border-green-500/30 bg-green-500/10">Best Value</span><?php endif; ?>
            </div>
            <p class="text-4xl font-bold mb-1">&#8369;<?= number_format($plan['amount']) ?></p>
            <p class="text-slate-400 mb-5"><?= e($plan['savings']) ?></p>
            <div class="space-y-2 text-sm text-slate-300 mb-6">
                <p><i class="fa-solid fa-users text-blue-300 mr-2"></i>Mentor access</p>
                <p><i class="fa-solid fa-book-open text-blue-300 mr-2"></i>Premium lessons</p>
                <p><i class="fa-solid fa-brain text-blue-300 mr-2"></i>Exclusive quizzes</p>
            </div>
            <button class="primaryBtn w-full selectPlan" type="button"
                data-plan="<?= e($plan['type']) ?>"
                data-name="<?= e($plan['name']) ?>"
                data-amount="<?= (int)$plan['amount'] ?>">
                Select Plan
            </button>
        </article>
    <?php endforeach; ?>
</div>

<div id="paymentModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
    <div class="max-w-xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6 shadow-2xl shadow-black/40">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h2 class="text-2xl font-bold"><span id="planName"></span> &middot; &#8369;<span id="planAmount"></span></h2>
            </div>
            <button id="closePayment" type="button" class="secondaryBtn px-3 py-2"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="grid grid-cols-5 gap-2 mb-6 text-xs font-bold text-center">
            <span class="checkoutStep isDone">Plan</span>
            <span class="checkoutStep isActive" data-step-dot="method">Method</span>
            <span class="checkoutStep" data-step-dot="details">Details</span>
            <span class="checkoutStep" data-step-dot="processing">Processing</span>
            <span class="checkoutStep" data-step-dot="success">Success</span>
        </div>

        <form id="paymentForm" class="space-y-5">
            <?= csrf_input() ?>
            <input type="hidden" name="plan_type" id="planType">

            <div id="methodStep">
                <p class="font-bold mb-3">Step 2 &middot; Choose payment method</p>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach (['GCash', 'Card', 'Bank'] as $method): ?>
                        <button type="button" class="methodBtn bg-[#020B24] border border-[#334155] rounded-xl p-4 hover:border-blue-500 transition text-left" data-method="<?= e($method) ?>">
                            <i class="fa-solid <?= $method === 'Card' ? 'fa-credit-card' : ($method === 'Bank' ? 'fa-building-columns' : 'fa-mobile-screen') ?> block text-blue-300 mb-2"></i>
                            <?= e($method) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethod">
            </div>

            <div id="formStep" class="hidden">
                <p class="font-bold mb-3">Step 3 &middot; Payment credentials</p>
                <div id="walletFields" class="hidden grid gap-3">
                    <input name="mobile_number" class="inputStyle" placeholder="Mobile number">
                    <input name="account_name" class="inputStyle" placeholder="Account name">
                </div>
                <div id="bankFields" class="hidden grid gap-3">
                    <input name="bank_name" class="inputStyle" placeholder="Bank name">
                    <input name="account_number" class="inputStyle" placeholder="Account number">
                    <input name="bank_account_name" class="inputStyle" placeholder="Account name">
                </div>
                <div id="cardFields" class="hidden grid gap-3">
                    <input name="card_number" class="inputStyle" placeholder="Card number">
                    <div class="grid grid-cols-2 gap-3">
                        <input name="expiry" class="inputStyle" placeholder="MM/YY">
                        <input name="cvv" class="inputStyle" placeholder="CVV">
                    </div>
                </div>
                <p class="text-slate-400 text-sm mt-3">Simulation only. No real payment gateway will be contacted.</p>
                <button type="submit" id="confirmPayment" class="primaryBtn w-full mt-4">PaymentProcessing</button>
            </div>

            <div id="loadingStep" class="hidden text-center py-8">
                <div class="w-14 h-14 rounded-2xl bg-blue-600 mx-auto flex items-center justify-center mb-4 shadow-lg shadow-blue-600/30">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <p class="font-bold mb-4">Step 4 &middot; Processing payment</p>
                <div class="bg-slate-950 h-3 rounded-full overflow-hidden mb-4">
                    <div id="loadingBar" class="h-full bg-blue-500 w-0 transition-all duration-500"></div>
                </div>
                <p class="text-slate-400">Simulating secure activation...</p>
            </div>

            <div id="successStep" class="hidden text-center py-8">
                <i class="fa-solid fa-circle-check text-5xl text-green-300 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Premium Activated Successfully</h2>
                <p class="text-slate-400 mb-5">Mentor access and premium learning are now active.</p>
                <div class="grid grid-cols-2 gap-3">
                    <a href="find_mentors.php" class="primaryBtn">Find Mentor</a>
                    <a href="dashboard.php" class="secondaryBtn">Go Dashboard</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('paymentModal');
const methodStep = document.getElementById('methodStep');
const formStep = document.getElementById('formStep');
const loadingStep = document.getElementById('loadingStep');
const successStep = document.getElementById('successStep');
const closePayment = document.getElementById('closePayment');
const flowOrder = ['method', 'details', 'processing', 'success'];

function setCheckoutStep(step) {
    const activeIndex = flowOrder.indexOf(step);
    document.querySelectorAll('[data-step-dot]').forEach(dot => {
        const dotIndex = flowOrder.indexOf(dot.dataset.stepDot);
        dot.classList.toggle('isActive', dot.dataset.stepDot === step);
        dot.classList.toggle('isDone', dotIndex >= 0 && dotIndex < activeIndex);
    });
}

function resetPaymentFlow() {
    methodStep.classList.remove('hidden');
    formStep.classList.add('hidden');
    loadingStep.classList.add('hidden');
    successStep.classList.add('hidden');
    document.getElementById('walletFields').classList.add('hidden');
    document.getElementById('cardFields').classList.add('hidden');
    document.getElementById('bankFields').classList.add('hidden');
    document.getElementById('loadingBar').style.width = '0%';
    document.getElementById('paymentForm').reset();
    closePayment.disabled = false;
    closePayment.classList.remove('opacity-50', 'pointer-events-none');
    setCheckoutStep('method');
}

document.querySelectorAll('.selectPlan').forEach(button => {
    button.addEventListener('click', () => {
        resetPaymentFlow();
        document.getElementById('planType').value = button.dataset.plan;
        document.getElementById('planName').textContent = button.dataset.name;
        document.getElementById('planAmount').textContent = Number(button.dataset.amount).toLocaleString();
        modal.classList.remove('hidden');
    });
});
closePayment.addEventListener('click', () => modal.classList.add('hidden'));

document.querySelectorAll('.methodBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('paymentMethod').value = button.dataset.method;
        methodStep.classList.add('hidden');
        formStep.classList.remove('hidden');
        document.getElementById('walletFields').classList.toggle('hidden', button.dataset.method !== 'GCash');
        document.getElementById('cardFields').classList.toggle('hidden', button.dataset.method !== 'Card');
        document.getElementById('bankFields').classList.toggle('hidden', button.dataset.method !== 'Bank');
        setCheckoutStep('details');
    });
});

document.getElementById('paymentForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    formStep.classList.add('hidden');
    loadingStep.classList.remove('hidden');
    setCheckoutStep('processing');
    closePayment.disabled = true;
    closePayment.classList.add('opacity-50', 'pointer-events-none');
    document.getElementById('loadingBar').style.width = '20%';
    setTimeout(() => document.getElementById('loadingBar').style.width = '65%', 1000);
    setTimeout(() => document.getElementById('loadingBar').style.width = '100%', 2400);

    const [result] = await Promise.all([
        window.mmfPost('ajax_subscription.php', new FormData(event.currentTarget), true)
            .catch(() => ({success: false, message: 'Unable to activate premium. Please try again.'})),
        new Promise(resolve => setTimeout(resolve, 3000))
    ]);

    loadingStep.classList.add('hidden');
    if (result.success) {
        successStep.classList.remove('hidden');
        setCheckoutStep('success');
    } else {
        alert(result.message || 'Unable to activate premium.');
        closePayment.disabled = false;
        closePayment.classList.remove('opacity-50', 'pointer-events-none');
        formStep.classList.remove('hidden');
        setCheckoutStep('details');
    }
});
</script>

<style>
    .checkoutStep{border:1px solid #334155;background:#020B24;color:#64748b;border-radius:999px;padding:7px 8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .checkoutStep.isActive{border-color:#3B82F6;background:rgba(59,130,246,.14);color:#bfdbfe;}
    .checkoutStep.isDone{border-color:rgba(34,197,94,.35);background:rgba(34,197,94,.09);color:#bbf7d0;}
    @media (max-width:520px){.checkoutStep{font-size:10px;padding:6px 4px;}}
</style>

<?php include '../footer.php'; ?>
