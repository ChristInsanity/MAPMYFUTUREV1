<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$jobs = getRelevantJobsForStudent($conn, $userId);

$pageTitle = 'Job Market';
$activePage = 'jobs';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Job Market']
];
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Employment phase</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($profile['career_path'] ?? 'Career') ?> Job Market</h1>
    <p class="text-slate-400">Browse roles matched to your roadmap progress, assessments, projects, and career pathway.</p>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <?php foreach ($jobs as $job): ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-5">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-14 h-14 rounded-xl bg-blue-600 flex items-center justify-center text-xl font-bold shrink-0">
                        <?= e(strtoupper(substr($job['company_name'] ?: $job['employer_name'], 0, 1))) ?>
                    </div>
                    <div class="min-w-0">
                        <p class="text-slate-400 text-sm truncate"><?= e($job['company_name'] ?: $job['employer_name']) ?></p>
                        <h2 class="text-xl font-bold truncate"><?= e($job['title']) ?></h2>
                    </div>
                </div>
                <span class="badge text-green-300 border-green-500/30 bg-green-500/10"><?= (int)$job['compatibility'] ?>% Match</span>
            </div>

            <div class="grid sm:grid-cols-2 gap-3 text-sm text-slate-300 mb-5">
                <p><i class="fa-solid fa-laptop-house text-blue-300 mr-2"></i><?= e(ucfirst($job['work_setup'] ?? 'onsite')) ?></p>
                <p><i class="fa-solid fa-location-dot text-blue-300 mr-2"></i><?= e($job['location'] ?: 'Location not specified') ?></p>
                <p><i class="fa-solid fa-money-bill-wave text-blue-300 mr-2"></i><?= e($job['salary'] ?: 'Salary not posted') ?></p>
                <p><i class="fa-solid fa-clock text-blue-300 mr-2"></i><?= e(date('M d, Y', strtotime($job['created_at']))) ?></p>
            </div>

            <div class="flex flex-wrap gap-2 mb-5">
                <?php foreach (array_slice(parseSkillTags($job['required_skills']), 0, 6) as $skill): ?>
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10"><?= e($skill) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-between gap-3">
                <a href="job_details.php?id=<?= (int)$job['job_id'] ?>" class="primaryBtn">View Details</a>
                <?php if ($job['application_status']): ?>
                    <span class="badge text-purple-300 border-purple-500/30 bg-purple-500/10"><?= e(readableStatus($job['application_status'])) ?></span>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (count($jobs) === 0): ?>
        <section class="card lg:col-span-2">
            <p class="text-slate-400">No active jobs match your selected career yet.</p>
        </section>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
