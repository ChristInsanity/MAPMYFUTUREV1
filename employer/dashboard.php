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
    <p class="text-blue-300 font-semibold mb-2">Employer Center</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Hiring Dashboard</h1>
    <p class="text-slate-400">Track job activity, applicants, hires, and student profile views from the database.</p>
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

<section class="card">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="sectionTitle mb-2">Applicant Review</h2>
            <p class="text-slate-400">Review readiness scores, portfolios, and certifications.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="jobs.php" class="secondaryBtn"><i class="fa-solid fa-briefcase"></i> Manage Job Posts</a>
            <a href="applicants.php" class="primaryBtn"><i class="fa-solid fa-user-check"></i> View Applicants</a>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>
