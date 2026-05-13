<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();

$data = getSalesReportData($conn);

$pageTitle = 'Analytics';
$activePage = 'analytics';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Analytics']
];
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Sales + Hiring Reports</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Platform Analytics</h1>
    <p class="text-slate-400">Premium growth, mentor enrollments, employer activity, job postings, and hires.</p>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
    <?php
    $cards = [
        ['Monthly Sales', '&#8369;' . number_format($data['totals']['monthly_revenue'], 2), 'fa-coins'],
        ['Mentor Enrollments', $data['totals']['mentor_enrollments'], 'fa-user-check'],
        ['Employer Subscriptions', $data['totals']['employer_subscriptions'], 'fa-building-circle-check'],
        ['Job Postings', $data['totals']['active_jobs'], 'fa-briefcase'],
        ['Hired Students', $data['totals']['hired_students'], 'fa-handshake'],
    ];
    foreach ($cards as $card):
    ?>
        <div class="statCard">
            <div class="flex justify-between text-slate-400 mb-3">
                <p><?= e($card[0]) ?></p>
                <i class="fa-solid <?= e($card[2]) ?> text-blue-300"></i>
            </div>
            <h2 class="text-2xl font-bold"><?= $card[1] ?></h2>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-2 gap-6">
    <section class="card">
        <h2 class="sectionTitle mb-4">Monthly Premium Sales</h2>
        <canvas id="salesChart" height="130"></canvas>
    </section>
    <section class="card">
        <h2 class="sectionTitle mb-4">Job Postings</h2>
        <canvas id="jobsChart" height="130"></canvas>
    </section>
    <section class="card">
        <h2 class="sectionTitle mb-4">Hired Students</h2>
        <canvas id="hiresChart" height="130"></canvas>
    </section>
    <section class="card">
        <h2 class="sectionTitle mb-4">Plan Distribution</h2>
        <canvas id="planChart" height="130"></canvas>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const analytics = <?= json_encode($data) ?>;
const chartColor = '#3B82F6';
const textColor = '#cbd5e1';
const mutedColor = '#94a3b8';

function lineChart(id, label, rows) {
    new Chart(document.getElementById(id), {
        type: 'line',
        data: {labels: rows.map(row => row.label), datasets: [{label, data: rows.map(row => Number(row.total)), borderColor: chartColor, backgroundColor: 'rgba(59,130,246,.15)', fill: true, tension: .35}]},
        options: {responsive: true, plugins: {legend: {labels: {color: textColor}}}, scales: {x: {ticks: {color: mutedColor}}, y: {ticks: {color: mutedColor}}}}
    });
}

lineChart('salesChart', 'Premium Sales', analytics.monthly_sales);
lineChart('jobsChart', 'Job Posts', analytics.job_postings);
lineChart('hiresChart', 'Hires', analytics.hired_students);

new Chart(document.getElementById('planChart'), {
    type: 'doughnut',
    data: {
        labels: analytics.plan_distribution.map(row => row.label || 'unknown'),
        datasets: [{data: analytics.plan_distribution.map(row => Number(row.total)), backgroundColor: ['#3B82F6', '#22C55E', '#F59E0B', '#A855F7']}]
    },
    options: {responsive: true, plugins: {legend: {labels: {color: textColor}}}}
});
</script>

<?php include '../footer.php'; ?>
