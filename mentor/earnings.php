<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

$mentorId = (int)$_SESSION['user_id'];
$dashboard = getMentorRevenueDashboardData($conn);
$mentorRow = null;

foreach ($dashboard['mentors'] as $row) {
    if ((int)$row['user_id'] === $mentorId) {
        $mentorRow = $row;
        break;
    }
}

$mentorRow = $mentorRow ?: [
    'active_premium_students' => 0,
    'monthly_revenue' => 0,
    'pending_payout' => 0,
    'active_students' => 0,
    'max_student_capacity' => 10
];

$allTimePool = dbFetchOne($conn, "SELECT COALESCE(SUM(mentor_pool_share), 0) AS total FROM subscription_revenue_allocations");
$allTimeAssignments = dbFetchOne(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mentor_assignments ma
     JOIN student_subscriptions ss ON ss.user_id = ma.student_id
        AND ss.status = 'active'
        AND (ss.expires_at IS NULL OR ss.expires_at > NOW())
     WHERE ma.status = 'active'"
);
$allTimePerStudent = (int)($allTimeAssignments['total'] ?? 0) > 0
    ? (float)$allTimePool['total'] / (int)$allTimeAssignments['total']
    : 0;
$totalEarnings = round((int)$mentorRow['active_premium_students'] * $allTimePerStudent, 2);

$pageTitle = 'Mentor Earnings';
$activePage = 'earnings';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Earnings']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Mentor Earnings</h1>
    <p class="text-slate-400">Estimated payouts based on active premium students and active mentor assignments.</p>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="statCard"><p class="text-slate-400 mb-2">Active Premium Students</p><h2 class="text-3xl font-bold"><?= (int)$mentorRow['active_premium_students'] ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Monthly Earnings</p><h2 class="text-3xl font-bold">&#8369;<?= number_format((float)$mentorRow['monthly_revenue'], 2) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Total Earnings</p><h2 class="text-3xl font-bold">&#8369;<?= number_format($totalEarnings, 2) ?></h2></div>
    <div class="statCard"><p class="text-slate-400 mb-2">Pending Payout</p><h2 class="text-3xl font-bold">&#8369;<?= number_format((float)$mentorRow['pending_payout'], 2) ?></h2></div>
</div>

<section class="card">
    <h2 class="sectionTitle mb-4">Capacity Context</h2>
    <div class="grid sm:grid-cols-3 gap-4">
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <p class="text-slate-500 text-sm">Active Students</p>
            <p class="text-2xl font-bold"><?= (int)$mentorRow['active_students'] ?></p>
        </div>
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <p class="text-slate-500 text-sm">Capacity</p>
            <p class="text-2xl font-bold"><?= (int)$mentorRow['max_student_capacity'] ?></p>
        </div>
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <p class="text-slate-500 text-sm">Per Premium Assignment</p>
            <p class="text-2xl font-bold">&#8369;<?= number_format((float)$dashboard['totals']['per_student_share'], 2) ?></p>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>
