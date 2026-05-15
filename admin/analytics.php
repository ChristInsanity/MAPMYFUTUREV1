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

<style>
    .analyticsHeader{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:28px;}
    .analyticsKpi{background:linear-gradient(180deg,rgba(30,41,59,.92),rgba(22,35,56,.96));border:1px solid rgba(148,163,184,.22);border-radius:16px;padding:18px;box-shadow:0 16px 36px rgba(0,0,0,.18);}
    .analyticsKpiTop{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#94a3b8;font-size:13px;font-weight:800;margin-bottom:16px;}
    .analyticsKpiIcon{width:36px;height:36px;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;background:rgba(59,130,246,.12);color:#93c5fd;border:1px solid rgba(59,130,246,.22);}
    .analyticsKpiValue{font-size:26px;line-height:1;font-weight:900;color:#f8fafc;}
    .chartCard{background:#162338;border:1px solid rgba(148,163,184,.22);border-radius:16px;padding:20px;box-shadow:0 18px 42px rgba(0,0,0,.18);}
    .chartCardHeader{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;}
    .chartCardHeader h2{font-size:18px;font-weight:900;}
    .chartCardHeader span{font-size:12px;color:#94a3b8;font-weight:800;text-transform:uppercase;letter-spacing:0;}
    .chartCanvasWrap{height:260px;}
    .chartCanvasWrap canvas{width:100%!important;height:100%!important;}
    @media (max-width:768px){.analyticsHeader{display:block}.chartCanvasWrap{height:220px}}
</style>

<div class="analyticsHeader">
    <div>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2">Platform Analytics</h1>
    </div>
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
        <div class="analyticsKpi">
            <div class="analyticsKpiTop">
                <p><?= e($card[0]) ?></p>
                <span class="analyticsKpiIcon"><i class="fa-solid <?= e($card[2]) ?>"></i></span>
            </div>
            <h2 class="analyticsKpiValue"><?= $card[1] ?></h2>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-2 gap-6">
    <section class="chartCard">
        <div class="chartCardHeader"><h2>Monthly Premium Sales</h2><span>Revenue</span></div>
        <div class="chartCanvasWrap"><canvas id="salesChart"></canvas></div>
    </section>
    <section class="chartCard">
        <div class="chartCardHeader"><h2>Job Postings</h2><span>Activity</span></div>
        <div class="chartCanvasWrap"><canvas id="jobsChart"></canvas></div>
    </section>
    <section class="chartCard">
        <div class="chartCardHeader"><h2>Hired Students</h2><span>Outcomes</span></div>
        <div class="chartCanvasWrap"><canvas id="hiresChart"></canvas></div>
    </section>
    <section class="chartCard">
        <div class="chartCardHeader"><h2>Plan Distribution</h2><span>Plans</span></div>
        <div class="chartCanvasWrap"><canvas id="planChart"></canvas></div>
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
        options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {labels: {color: textColor}}}, scales: {x: {ticks: {color: mutedColor}}, y: {ticks: {color: mutedColor}}}}
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
    options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {labels: {color: textColor}}}}
});
</script>

<?php include '../footer.php'; ?>
