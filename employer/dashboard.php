<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

$employerId = (int)$_SESSION['user_id'];
$stats = getEmployerDashboardStats($conn, $employerId);

$pageTitle = 'Employer Dashboard';
$activePage = 'dashboard';
$breadcrumbs = [
    ['label' => 'Dashboard']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Hiring Dashboard</h1>
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
        ['Active Jobs', $stats['active_jobs'], 'fa-briefcase'],
        ['Applicants', $stats['applicants'], 'fa-users'],
        ['Hires', $stats['hires'], 'fa-handshake'],
        ['Views', $stats['views'], 'fa-eye']
    ];
    foreach ($cards as $card):
    ?>
        <div class="statCard">
            <div class="flex justify-between text-slate-400 mb-3">
                <p><?= e($card[0]) ?></p>
                <i class="fa-solid <?= e($card[2]) ?> text-blue-300"></i>
            </div>
            <h2 class="text-3xl font-bold"><?= (int)$card[1] ?></h2>
        </div>
    <?php endforeach; ?>
</div>

<div class="grid xl:grid-cols-3 gap-6 mb-8">
    <section class="card xl:col-span-2">
        <div class="flex items-center justify-between gap-4 mb-5">
            <div>
                <h2 class="sectionTitle mb-1">Weekly Applications</h2>
            </div>
        </div>
        <canvas id="weeklyApplicationsChart" height="120"></canvas>
    </section>
    <section class="card">
        <h2 class="sectionTitle mb-1">Hiring Conversion</h2>
        <canvas id="conversionChart" height="220"></canvas>
    </section>
</div>

<section class="card">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="sectionTitle mb-2">Applicant Review</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="jobs.php" class="secondaryBtn"><i class="fa-solid fa-briefcase"></i> Manage Job Posts</a>
            <a href="applicants.php" class="primaryBtn"><i class="fa-solid fa-user-check"></i> View Applicants</a>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const weeklyData = <?= json_encode($stats['weekly_applications']) ?>;
const conversionData = <?= json_encode($stats['conversion']) ?>;

new Chart(document.getElementById('weeklyApplicationsChart'), {
    type: 'line',
    data: {
        labels: weeklyData.map(row => row.label),
        datasets: [{
            label: 'Applications',
            data: weeklyData.map(row => Number(row.total)),
            borderColor: '#3B82F6',
            backgroundColor: 'rgba(59,130,246,.18)',
            fill: true,
            tension: .35
        }]
    },
    options: {
        plugins: { legend: { labels: { color: '#cbd5e1' } } },
        scales: {
            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,.12)' } },
            y: { ticks: { color: '#94a3b8', precision: 0 }, grid: { color: 'rgba(148,163,184,.12)' } }
        }
    }
});

new Chart(document.getElementById('conversionChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(conversionData).map(label => label.charAt(0).toUpperCase() + label.slice(1)),
        datasets: [{
            label: 'Applicants',
            data: Object.values(conversionData).map(Number),
            backgroundColor: '#3B82F6'
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
            y: { ticks: { color: '#94a3b8', precision: 0 }, grid: { color: 'rgba(148,163,184,.12)' } }
        }
    }
});
</script>

<?php include '../footer.php'; ?>
