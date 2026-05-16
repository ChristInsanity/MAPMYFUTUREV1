<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

$employerId = (int)$_SESSION['user_id'];
$jobs = getEmployerJobs($conn, $employerId, 'archived');

$pageTitle = 'Archived Job Posts';
$activePage = 'jobs';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Job Posts', 'url' => 'jobs.php'],
    ['label' => 'Archived']
];
include '../header.php';
?>

<div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-5">
    <div>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2">Archived Job Posts</h1>
        <p class="text-slate-400">Restore archived posts when hiring reopens.</p>
    </div>
    <a href="jobs.php" class="secondaryBtn"><i class="fa-solid fa-arrow-left"></i> Active Jobs</a>
</div>

<div class="card mb-6">
    <div class="grid md:grid-cols-[1fr_220px] gap-4">
        <input id="archiveSearch" class="inputStyle" placeholder="Search archived posts">
        <select id="archiveFilter" class="inputStyle">
            <option value="">All work setups</option>
            <option value="onsite">Onsite</option>
            <option value="hybrid">Hybrid</option>
            <option value="remote">Remote</option>
        </select>
    </div>
</div>

<div id="archiveToast" class="hidden fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl"></div>

<div id="archiveGrid" class="grid lg:grid-cols-2 gap-5">
    <?php foreach ($jobs as $job): ?>
        <?php
        $search = strtolower(trim(($job['title'] ?? '') . ' ' . ($job['career_title'] ?? '') . ' ' . ($job['location'] ?? '') . ' ' . ($job['required_skills'] ?? '')));
        ?>
        <article class="card archiveCard" data-search="<?= e($search) ?>" data-work-setup="<?= e($job['work_setup']) ?>">
            <div class="flex justify-between gap-4 mb-4">
                <div>
                    <p class="text-slate-400 text-sm"><?= e($job['career_title'] ?: 'All aligned careers') ?></p>
                    <h2 class="text-xl font-bold"><?= e($job['title']) ?></h2>
                </div>
                <span class="badge text-slate-300 border-slate-500/30 bg-slate-500/10">Archived</span>
            </div>
            <p class="text-slate-300 mb-4">
                <?= e($job['location'] ?: 'Location not set') ?> &middot;
                <?= e(ucfirst($job['work_setup'])) ?> &middot;
                <?= e($job['salary'] ?: 'Salary not posted') ?>
            </p>
            <div class="flex flex-wrap gap-2 mb-5">
                <?php foreach (array_slice(parseSkillTags($job['required_skills']), 0, 6) as $skill): ?>
                    <span class="badge"><?= e($skill) ?></span>
                <?php endforeach; ?>
            </div>
            <button type="button" class="primaryBtn restoreBtn" data-job-id="<?= (int)$job['job_id'] ?>"><i class="fa-solid fa-rotate-left"></i> Restore</button>
        </article>
    <?php endforeach; ?>
    <?php if (count($jobs) === 0): ?>
        <section class="card lg:col-span-2 text-slate-400">No archived job posts.</section>
    <?php endif; ?>
</div>

<script>
const archiveSearch = document.getElementById('archiveSearch');
const archiveFilter = document.getElementById('archiveFilter');
const archiveToast = document.getElementById('archiveToast');

function showArchiveToast(message, success = true) {
    archiveToast.textContent = message;
    archiveToast.className = `fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl ${success ? 'bg-green-500/10 border-green-500 text-green-200' : 'bg-red-500/10 border-red-500 text-red-200'}`;
    archiveToast.classList.remove('hidden');
    setTimeout(() => archiveToast.classList.add('hidden'), 2600);
}

function filterArchive() {
    const query = archiveSearch.value.trim().toLowerCase();
    const setup = archiveFilter.value;
    document.querySelectorAll('.archiveCard').forEach(card => {
        const visible = (!query || card.dataset.search.includes(query)) && (!setup || card.dataset.workSetup === setup);
        card.classList.toggle('hidden', !visible);
    });
}

archiveSearch.addEventListener('input', filterArchive);
archiveFilter.addEventListener('change', filterArchive);

document.querySelectorAll('.restoreBtn').forEach(button => {
    button.addEventListener('click', async () => {
        const result = await window.mmfPost('ajax_job_action.php', {job_id: button.dataset.jobId, action: 'restore'});
        if (result.success) {
            button.closest('article')?.remove();
            showArchiveToast('Job post restored.');
        } else {
            showArchiveToast(result.message || 'Unable to restore job.', false);
        }
    });
});
</script>

<?php include '../footer.php'; ?>
