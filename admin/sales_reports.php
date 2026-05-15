<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();

$initial = getSalesReportData($conn);

$pageTitle = 'Subscription Reports';
$activePage = 'subscriptions';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Subscription Reports']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Subscription Reports</h1>
</div>

<div id="salesCards" class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-8">
    <?php
    $cards = [
        ['Total Revenue', $initial['totals']['total_revenue'], 'fa-coins', true],
        ['Monthly Revenue', $initial['totals']['monthly_revenue'], 'fa-calendar-days', true],
        ['Active Premium Users', $initial['totals']['active_premium_users'], 'fa-crown', false],
        ['Mentor Enrollments', $initial['totals']['mentor_enrollments'], 'fa-user-check', false],
        ['Refunds', $initial['totals']['refunds'], 'fa-rotate-left', true]
    ];
    foreach ($cards as $card):
    ?>
        <div class="statCard">
            <div class="flex justify-between text-slate-400 mb-3">
                <p><?= e($card[0]) ?></p>
                <i class="fa-solid <?= e($card[2]) ?> text-blue-300"></i>
            </div>
            <h2 class="text-2xl font-bold"><?= $card[3] ? '&#8369;' . number_format((float)$card[1], 2) : (int)$card[1] ?></h2>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-3 gap-6">
    <section class="card xl:col-span-2">
        <h2 class="sectionTitle mb-4">Monthly Sales</h2>
        <canvas id="monthlySalesChart" height="120"></canvas>
    </section>
    <section class="card">
        <h2 class="sectionTitle mb-4">Plan Distribution</h2>
        <canvas id="planChart" height="220"></canvas>
    </section>
    <section class="card xl:col-span-3">
        <h2 class="sectionTitle mb-4">Mentor Revenue</h2>
        <canvas id="mentorRevenueChart" height="110"></canvas>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let charts = [];

function chartColors(count) {
    return ['#3B82F6', '#22C55E', '#F59E0B', '#A855F7', '#EF4444', '#14B8A6'].slice(0, count);
}

function renderCharts(data) {
    charts.forEach(chart => chart.destroy());
    charts = [];

    const monthlyLabels = data.monthly_sales.map(item => item.label);
    const monthlyValues = data.monthly_sales.map(item => Number(item.total));
    const planLabels = data.plan_distribution.map(item => item.label || 'unknown');
    const planValues = data.plan_distribution.map(item => Number(item.total));
    const mentorLabels = data.mentor_revenue.map(item => item.label);
    const mentorValues = data.mentor_revenue.map(item => Number(item.total));

    charts.push(new Chart(document.getElementById('monthlySalesChart'), {
        type: 'line',
        data: {labels: monthlyLabels, datasets: [{label: 'Revenue', data: monthlyValues, borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,.15)', tension: .35, fill: true}]},
        options: {responsive: true, plugins: {legend: {labels: {color: '#cbd5e1'}}}, scales: {x: {ticks: {color: '#94a3b8'}}, y: {ticks: {color: '#94a3b8'}}}}
    }));

    charts.push(new Chart(document.getElementById('planChart'), {
        type: 'doughnut',
        data: {labels: planLabels, datasets: [{data: planValues, backgroundColor: chartColors(planLabels.length)}]},
        options: {responsive: true, plugins: {legend: {labels: {color: '#cbd5e1'}}}}
    }));

    charts.push(new Chart(document.getElementById('mentorRevenueChart'), {
        type: 'bar',
        data: {labels: mentorLabels, datasets: [{label: 'Revenue', data: mentorValues, backgroundColor: '#3B82F6'}]},
        options: {responsive: true, plugins: {legend: {labels: {color: '#cbd5e1'}}}, scales: {x: {ticks: {color: '#94a3b8'}}, y: {ticks: {color: '#94a3b8'}}}}
    }));
}

renderCharts(<?= json_encode($initial) ?>);

window.mmfPost('ajax_sales_reports.php').then(result => {
    if (result.success) {
        renderCharts(result.data);
    }
});
</script>

<?php include '../footer.php'; ?>
