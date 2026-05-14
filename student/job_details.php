<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$jobId = (int)($_GET['id'] ?? 0);
$job = getJobDetailsForStudent($conn, $userId, $jobId);
$profile = getStudentProfile($conn, $userId);
$progress = getStudentProgress($conn, $userId);
$projects = getPortfolioProjects($conn, $userId);

if (!$job) {
    redirect('job_market.php');
}

$pageTitle = 'Job Details';
$activePage = 'jobs';
$backUrl = 'job_market.php';
$backLabel = 'Job Market';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Job Market', 'url' => 'job_market.php'],
    ['label' => $job['title']]
];
include '../header.php';
?>

<div class="card mb-8">
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-5">
        <div class="flex gap-4">
            <div class="w-16 h-16 rounded-xl bg-blue-600 flex items-center justify-center text-2xl font-bold">
                <?= e(strtoupper(substr($job['company_name'] ?: $job['employer_name'], 0, 1))) ?>
            </div>
            <div>
                <p class="text-blue-300 font-semibold mb-1"><?= e($job['company_name'] ?: $job['employer_name']) ?></p>
                <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($job['title']) ?></h1>
                <p class="text-slate-400"><?= e($job['location'] ?: 'Location not specified') ?> · <?= e(ucfirst($job['work_setup'])) ?> · <?= e($job['employment_type'] ?: 'Full-time') ?></p>
                <p class="text-slate-400"><?= e($job['salary'] ?: 'Salary not posted') ?></p>
            </div>
        </div>
        <div class="flex flex-col gap-3">
            <span class="badge text-green-300 border-green-500/30 bg-green-500/10 justify-center"><?= (int)$job['compatibility'] ?>% Match</span>
            <?php if ($job['application_status']): ?>
                <span class="badge text-purple-300 border-purple-500/30 bg-purple-500/10 justify-center"><?= e(readableStatus($job['application_status'])) ?></span>
            <?php else: ?>
                <button type="button" id="openApply" class="primaryBtn"><i class="fa-solid fa-paper-plane"></i> Apply</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">Required Skills</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach (parseSkillTags($job['required_skills']) as $skill): ?>
                    <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10"><?= e($skill) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Responsibilities</h2>
            <p class="text-slate-300 leading-7"><?= nl2br(e($job['responsibilities'] ?: $job['description'])) ?></p>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Requirements</h2>
            <p class="text-slate-300 leading-7"><?= nl2br(e($job['qualifications'] ?: $job['description'])) ?></p>
            <div class="grid sm:grid-cols-2 gap-3 mt-5">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-500 text-sm">Experience</p>
                    <p class="font-semibold"><?= e($job['required_experience'] ?: 'Not specified') ?></p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-500 text-sm">Education</p>
                    <p class="font-semibold"><?= e($job['education'] ?: 'Not specified') ?></p>
                </div>
            </div>
        </div>
        <div class="card">
            <h2 class="sectionTitle mb-4">Preferred Qualifications</h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach (parseSkillTags($job['preferred_skills']) as $skill): ?>
                    <span class="badge text-cyan-200 border-cyan-500/30 bg-cyan-500/10"><?= e($skill) ?></span>
                <?php endforeach; ?>
                <?php foreach (parseSkillTags($job['optional_skills'] ?? '') as $skill): ?>
                    <span class="badge text-cyan-200 border-cyan-500/30 bg-cyan-500/10"><?= e($skill) ?></span>
                <?php endforeach; ?>
                <?php if (count(parseSkillTags(($job['preferred_skills'] ?? '') . ',' . ($job['optional_skills'] ?? ''))) === 0): ?><p class="text-slate-400">No preferred qualifications posted.</p><?php endif; ?>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">Hiring Process</h2>
            <?php $steps = parseSkillTags($job['hiring_process'] ?: "Resume review,Initial screening,Technical evaluation,Final interview"); ?>
            <div class="space-y-3">
                <?php foreach ($steps as $index => $step): ?>
                    <div class="flex gap-3">
                        <span class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center font-bold shrink-0"><?= $index + 1 ?></span>
                        <p class="text-slate-300"><?= e(ucwords($step)) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</div>

<div id="applyModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
    <div class="max-w-2xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex justify-between gap-4 mb-5">
            <div>
                <p class="text-blue-300 font-semibold">Application</p>
                <h2 class="text-2xl font-bold"><?= e($job['title']) ?></h2>
            </div>
            <button type="button" id="closeApply" class="secondaryBtn px-3 py-2"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="applyForm" class="space-y-5">
            <?= csrf_input() ?>
            <input type="hidden" name="job_id" value="<?= (int)$job['job_id'] ?>">
            <section class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                <h3 class="font-bold mb-3">Step 1: Review Profile</h3>
                <p class="text-slate-300"><?= e($profile['career_path'] ?? 'Career not set') ?> · <?= (int)$progress['readiness'] ?>% readiness · <?= count($projects) ?> portfolio projects</p>
            </section>
            <section>
                <h3 class="font-bold mb-3">Step 2: Upload Documents</h3>
                <div class="grid gap-3">
                    <label class="text-slate-300">Resume PDF/DOC/DOCX
                        <input type="file" name="resume" accept=".pdf,.doc,.docx" class="mt-2 w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2" required>
                    </label>
                    <label class="text-slate-300">Cover Letter PDF/DOC/DOCX
                        <input type="file" name="cover_letter_file" accept=".pdf,.doc,.docx" class="mt-2 w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2">
                    </label>
                    <textarea name="cover_letter" class="inputStyle min-h-[120px]" placeholder="Optional cover letter note"></textarea>
                </div>
            </section>
            <button type="submit" class="primaryBtn w-full">Step 3: Submit Application</button>
        </form>
        <div id="applySuccess" class="hidden text-center py-8">
            <i class="fa-solid fa-circle-check text-green-300 text-5xl mb-4"></i>
            <h3 class="text-2xl font-bold mb-2">Application Pending</h3>
            <p class="text-slate-400">Your application was submitted for employer review.</p>
        </div>
    </div>
</div>

<script>
const applyModal = document.getElementById('applyModal');
document.getElementById('openApply')?.addEventListener('click', () => applyModal.classList.remove('hidden'));
document.getElementById('closeApply')?.addEventListener('click', () => applyModal.classList.add('hidden'));
document.getElementById('applyForm')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_job_apply.php', new FormData(event.currentTarget), true);
    if (result.success) {
        event.currentTarget.classList.add('hidden');
        document.getElementById('applySuccess').classList.remove('hidden');
    } else {
        alert(result.message || 'Unable to submit application.');
    }
});
</script>

<?php include '../footer.php'; ?>
