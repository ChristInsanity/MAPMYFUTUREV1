<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $platformPercent = (float)($_POST['platform_percent'] ?? 0);
    $mentorPoolPercent = (float)($_POST['mentor_pool_percent'] ?? 0);

    if (updateMentorRevenueSettings($conn, $platformPercent, $mentorPoolPercent)) {
        $message = 'Revenue split updated.';
    } else {
        $error = 'Revenue split must total 100%.';
    }
}

$data = getMentorRevenueDashboardData($conn);

$pageTitle = 'Mentor Revenue';
$activePage = 'mentor_revenue';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Mentor Revenue']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Mentor Revenue</h1>
    <p class="text-slate-400">Premium revenue allocation and mentor pool payout estimates.</p>
</div>

<?php if ($message): ?><div class="mb-6 rounded-2xl border border-green-500 bg-green-500/10 p-4 text-green-200"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-red-200"><?= e($error) ?></div><?php endif; ?>

<div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
    <div class="statCard"><p class="text-slate-400 mb-2">Total Premium Revenue</p><h2 class="text-2xl font-bold">&#8369;<?= number_format($data['totals']['gross_revenue'], 2) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Platform Profit</p><h2 class="text-2xl font-bold">&#8369;<?= number_format($data['totals']['platform_share'], 2) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Mentor Payouts</p><h2 class="text-2xl font-bold">&#8369;<?= number_format($data['totals']['mentor_pool'], 2) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Premium Assignments</p><h2 class="text-2xl font-bold"><?= (int)$data['totals']['active_premium_assignments'] ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Pending Payouts</p><h2 class="text-2xl font-bold">&#8369;<?= number_format($data['totals']['pending_payouts'], 2) ?></h2></div>
</div>

<form method="POST" class="card mb-8">
    <?= csrf_input() ?>
    <h2 class="sectionTitle mb-4">Revenue Split</h2>
    <div class="grid md:grid-cols-[1fr_1fr_auto] gap-4 items-end">
        <label>
            <span class="text-slate-400">Platform share %</span>
            <input type="number" step="0.01" min="0" max="100" name="platform_percent" class="inputStyle mt-2" value="<?= e($data['settings']['platform_percent']) ?>">
        </label>
        <label>
            <span class="text-slate-400">Mentor pool share %</span>
            <input type="number" step="0.01" min="0" max="100" name="mentor_pool_percent" class="inputStyle mt-2" value="<?= e($data['settings']['mentor_pool_percent']) ?>">
        </label>
        <button class="primaryBtn" type="submit">Save Split</button>
    </div>
</form>

<section class="card">
    <h2 class="sectionTitle mb-5">Mentor Earnings</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="text-slate-400 text-sm border-b border-[#334155]">
                <tr>
                    <th class="py-3 pr-4">Mentor</th>
                    <th class="py-3 pr-4">Active Students</th>
                    <th class="py-3 pr-4">Capacity</th>
                    <th class="py-3 pr-4">Monthly Revenue</th>
                    <th class="py-3 pr-4">Pending Payout</th>
                    <th class="py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#334155]">
                <?php foreach ($data['mentors'] as $mentor): ?>
                    <tr>
                        <td class="py-4 pr-4">
                            <p class="font-bold"><?= e($mentor['full_name']) ?></p>
                            <p class="text-sm text-slate-400"><?= e($mentor['specialization'] ?: $mentor['email']) ?></p>
                        </td>
                        <td class="py-4 pr-4"><?= (int)$mentor['active_students'] ?></td>
                        <td class="py-4 pr-4"><?= (int)$mentor['max_student_capacity'] ?></td>
                        <td class="py-4 pr-4">&#8369;<?= number_format((float)$mentor['monthly_revenue'], 2) ?></td>
                        <td class="py-4 pr-4">&#8369;<?= number_format((float)$mentor['pending_payout'], 2) ?></td>
                        <td class="py-4">
                            <span class="badge <?= $mentor['is_available'] ? 'text-green-200 border-green-500/30 bg-green-500/10' : 'text-slate-300 border-slate-500/30 bg-slate-500/10' ?>">
                                <?= $mentor['is_available'] ? 'Available' : 'Unavailable' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($data['mentors']) === 0): ?>
                    <tr><td colspan="6" class="py-4 text-slate-400">No approved mentors yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include '../footer.php'; ?>
