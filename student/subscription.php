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
    <p class="text-blue-300 font-semibold mb-2">Upgrade flow</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Activate Premium</h1>
    <p class="text-slate-400">Select a plan, simulate payment, and unlock mentor access, premium lessons, and exclusive quizzes.</p>
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
    <?php foreach ($plans as $index => $plan): ?>
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
    <div class="max-w-xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex justify-between items-center mb-5">
            <div>
                <p class="text-blue-300 font-semibold">Payment simulation</p>
                <h2 class="text-2xl font-bold"><span id="planName"></span> · &#8369;<span id="planAmount"></span></h2>
            </div>
            <button id="closePayment" type="button" class="secondaryBtn px-3 py-2"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="paymentForm" class="space-y-5">
            <?= csrf_input() ?>
            <input type="hidden" name="plan_type" id="planType">

            <div id="methodStep">
                <p class="font-bold mb-3">Step 2 · Choose payment method</p>
                <div class="grid grid-cols-3 gap-3">
                    <?php foreach (['GCash', 'Maya', 'Card'] as $method): ?>
                        <button type="button" class="methodBtn bg-[#020B24] border border-[#334155] rounded-xl p-4 hover:border-blue-500" data-method="<?= e($method) ?>">
                            <i class="fa-solid <?= $method === 'Card' ? 'fa-credit-card' : 'fa-mobile-screen' ?> block text-blue-300 mb-2"></i>
                            <?= e($method) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="payment_method" id="paymentMethod">
            </div>

            <div id="formStep" class="hidden">
                <p class="font-bold mb-3">Step 3 · Payment details</p>
                <div id="walletFields" class="hidden grid gap-3">
                    <input name="mobile_number" class="inputStyle" placeholder="Mobile number">
                    <input name="account_name" class="inputStyle" placeholder="Account name">
                </div>
                <div id="cardFields" class="hidden grid gap-3">
                    <input name="card_number" class="inputStyle" placeholder="Card number">
                    <div class="grid grid-cols-2 gap-3">
                        <input name="expiry" class="inputStyle" placeholder="MM/YY">
                        <input name="cvv" class="inputStyle" placeholder="CVV">
                    </div>
                </div>
                <button type="button" id="confirmPayment" class="primaryBtn w-full mt-4">Confirm Payment</button>
            </div>

            <div id="confirmStep" class="hidden bg-[#020B24] border border-[#334155] rounded-xl p-4">
                <p class="font-bold mb-2">Step 4 · Confirmation</p>
                <p class="text-slate-400 mb-4">This is a simulation only. No real payment gateway will be contacted.</p>
                <button type="submit" class="primaryBtn w-full">Start Processing</button>
            </div>

            <div id="loadingStep" class="hidden text-center py-8">
                <p class="font-bold mb-4">Step 5 · Processing</p>
                <div class="bg-slate-950 h-3 rounded-full overflow-hidden mb-4">
                    <div id="loadingBar" class="h-full bg-blue-500 w-0 transition-all duration-500"></div>
                </div>
                <p class="text-slate-400">Simulating secure payment...</p>
            </div>

            <div id="successStep" class="hidden text-center py-8">
                <i class="fa-solid fa-circle-check text-5xl text-green-300 mb-4"></i>
                <h2 class="text-2xl font-bold mb-2">Premium Activated</h2>
                <p class="text-slate-400 mb-5">Mentor access and premium learning are now active.</p>
                <a href="dashboard.php" class="primaryBtn">Back to Dashboard</a>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('paymentModal');
document.querySelectorAll('.selectPlan').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('planType').value = button.dataset.plan;
        document.getElementById('planName').textContent = button.dataset.name;
        document.getElementById('planAmount').textContent = Number(button.dataset.amount).toLocaleString();
        modal.classList.remove('hidden');
    });
});
document.getElementById('closePayment').addEventListener('click', () => modal.classList.add('hidden'));

document.querySelectorAll('.methodBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('paymentMethod').value = button.dataset.method;
        document.getElementById('methodStep').classList.add('hidden');
        document.getElementById('formStep').classList.remove('hidden');
        document.getElementById('walletFields').classList.toggle('hidden', button.dataset.method === 'Card');
        document.getElementById('cardFields').classList.toggle('hidden', button.dataset.method !== 'Card');
    });
});

document.getElementById('confirmPayment').addEventListener('click', () => {
    document.getElementById('formStep').classList.add('hidden');
    document.getElementById('confirmStep').classList.remove('hidden');
});

document.getElementById('paymentForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    document.getElementById('confirmStep').classList.add('hidden');
    document.getElementById('loadingStep').classList.remove('hidden');
    document.getElementById('loadingBar').style.width = '20%';
    setTimeout(() => document.getElementById('loadingBar').style.width = '65%', 800);
    setTimeout(() => document.getElementById('loadingBar').style.width = '100%', 1800);

    setTimeout(async () => {
        const result = await window.mmfPost('ajax_subscription.php', new FormData(event.currentTarget), true);
        document.getElementById('loadingStep').classList.add('hidden');
        if (result.success) {
            document.getElementById('successStep').classList.remove('hidden');
        } else {
            alert(result.message || 'Unable to activate premium.');
            document.getElementById('formStep').classList.remove('hidden');
        }
    }, 2600);
});
</script>

<?php include '../footer.php'; ?>
