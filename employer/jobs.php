<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

$employerId = (int)$_SESSION['user_id'];
$jobs = getEmployerJobs($conn, $employerId);
$careers = getAllCareers($conn);

$pageTitle = 'Job Posts';
$activePage = 'jobs';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Job Posts']
];
include '../header.php';
?>

<div class="mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-5">
    <div>
        <p class="text-blue-300 font-semibold mb-2">Recruiter workspace</p>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2">Job Posts</h1>
        <p class="text-slate-400">Create career-targeted job posts with required skills for student matching.</p>
    </div>
    <button type="button" id="openJobModal" class="primaryBtn"><i class="fa-solid fa-plus"></i> Create Job</button>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <?php foreach ($jobs as $job): ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-4">
                <div>
                    <p class="text-slate-400 text-sm"><?= e($job['career_title'] ?: 'All aligned careers') ?></p>
                    <h2 class="text-xl font-bold"><?= e($job['title']) ?></h2>
                </div>
                <span class="badge text-blue-200 border-blue-500/30 bg-blue-500/10"><?= e(ucfirst($job['posting_status'] ?? 'open')) ?></span>
            </div>
            <p class="text-slate-300 mb-4">
                <?= e($job['location'] ?: 'Location not set') ?> &middot;
                <?= e(ucfirst($job['work_setup'])) ?> &middot;
                <?= e($job['salary'] ?: 'Salary not posted') ?>
            </p>
            <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-slate-500">Views</p>
                    <p class="font-bold"><?= (int)$job['views'] ?></p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-slate-500">Applications</p>
                    <p class="font-bold"><?= (int)$job['applicant_count'] ?></p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-slate-500">Compatibility Avg.</p>
                    <p class="font-bold"><?= (int)$job['compatibility_average'] ?>%</p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3">
                    <p class="text-slate-500">Hiring Stage</p>
                    <p class="font-bold"><?= e($job['hiring_stage']) ?></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach (array_slice(parseSkillTags($job['required_skills']), 0, 6) as $skill): ?>
                    <span class="badge"><?= e($skill) ?></span>
                <?php endforeach; ?>
            </div>
            <div class="flex flex-wrap gap-2 mt-5">
                <button type="button" class="secondaryBtn px-3 py-2 text-sm editJobBtn" data-job='<?= e(json_encode($job, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'><i class="fa-solid fa-pen"></i> Edit</button>
                <?php if (($job['posting_status'] ?? 'open') !== 'closed'): ?>
                    <button type="button" class="secondaryBtn px-3 py-2 text-sm jobActionBtn" data-job-id="<?= (int)$job['job_id'] ?>" data-action="close">Close</button>
                <?php endif; ?>
                <button type="button" class="secondaryBtn px-3 py-2 text-sm jobActionBtn" data-job-id="<?= (int)$job['job_id'] ?>" data-action="duplicate">Duplicate</button>
                <button type="button" class="dangerBtn px-3 py-2 text-sm jobActionBtn" data-job-id="<?= (int)$job['job_id'] ?>" data-action="archive">Archive</button>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($jobs) === 0): ?>
        <section class="card lg:col-span-2 text-slate-400">No job posts yet.</section>
    <?php endif; ?>
</div>

<div id="jobModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
    <div class="max-w-3xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex justify-between gap-4 mb-5">
            <div>
                <p class="text-blue-300 font-semibold">LinkedIn-style job post</p>
                <h2 class="text-2xl font-bold" id="jobModalTitle">Create Job</h2>
            </div>
            <button type="button" id="closeJobModal" class="secondaryBtn px-3 py-2"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="jobForm" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="job_id" id="job_id">
            <div class="grid md:grid-cols-2 gap-4">
                <input name="title" class="inputStyle" placeholder="Position" required>
                <input name="department" class="inputStyle" placeholder="Department">
                <select name="path_id" class="inputStyle">
                    <option value="">All matching careers</option>
                    <?php foreach ($careers as $career): ?>
                        <option value="<?= (int)$career['path_id'] ?>"><?= e($career['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="work_setup" class="inputStyle">
                    <option value="onsite">Onsite</option>
                    <option value="hybrid">Hybrid</option>
                    <option value="remote">Remote</option>
                </select>
                <input name="salary" class="inputStyle" placeholder="Salary range">
                <input name="location" class="inputStyle" placeholder="Location">
                <input name="employment_type" class="inputStyle" placeholder="Employment type">
                <input type="date" name="application_deadline" class="inputStyle">
                <input name="required_experience" class="inputStyle" placeholder="Required experience">
                <input name="education" class="inputStyle" placeholder="Education">
                <input type="number" min="1" name="max_applicants" class="inputStyle" placeholder="Max applicants">
                <select name="posting_status" class="inputStyle">
                    <option value="open">Open</option>
                    <option value="draft">Draft</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <textarea name="description" class="inputStyle min-h-[120px]" placeholder="Job description"></textarea>
            <textarea name="responsibilities" class="inputStyle min-h-[120px]" placeholder="Responsibilities"></textarea>
            <textarea name="qualifications" class="inputStyle min-h-[120px]" placeholder="Qualifications"></textarea>
            <input name="required_skills" class="inputStyle" placeholder="Required skills, comma separated" required>
            <input name="preferred_skills" class="inputStyle" placeholder="Preferred skills, comma separated">
            <input name="optional_skills" class="inputStyle" placeholder="Optional skills, comma separated">
            <textarea name="hiring_process" class="inputStyle min-h-[90px]" placeholder="Hiring process, comma separated"></textarea>
            <button class="primaryBtn w-full" type="submit" id="saveJobBtn">Publish Job</button>
        </form>
    </div>
</div>

<script>
const jobModal = document.getElementById('jobModal');
const jobForm = document.getElementById('jobForm');
const jobModalTitle = document.getElementById('jobModalTitle');
const saveJobBtn = document.getElementById('saveJobBtn');

function resetJobForm() {
    jobForm.reset();
    document.getElementById('job_id').value = '';
    jobModalTitle.textContent = 'Create Job';
    saveJobBtn.textContent = 'Publish Job';
}

document.getElementById('openJobModal').addEventListener('click', () => {
    resetJobForm();
    jobModal.classList.remove('hidden');
});

document.getElementById('closeJobModal').addEventListener('click', () => jobModal.classList.add('hidden'));

jobForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_job_create.php', new FormData(event.currentTarget), true);
    if (result.success) {
        window.location.reload();
    } else {
        alert(result.message || 'Unable to save job.');
    }
});

document.querySelectorAll('.editJobBtn').forEach(button => {
    button.addEventListener('click', () => {
        const job = JSON.parse(button.dataset.job);
        resetJobForm();
        Object.entries(job).forEach(([key, value]) => {
            const field = jobForm.elements[key];
            if (field) {
                field.value = value || '';
            }
        });
        document.getElementById('job_id').value = job.job_id;
        jobModalTitle.textContent = 'Edit Job';
        saveJobBtn.textContent = 'Save Changes';
        jobModal.classList.remove('hidden');
    });
});

document.querySelectorAll('.jobActionBtn').forEach(button => {
    button.addEventListener('click', async () => {
        const result = await window.mmfPost('ajax_job_action.php', {
            job_id: button.dataset.jobId,
            action: button.dataset.action
        });
        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Unable to update job.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
