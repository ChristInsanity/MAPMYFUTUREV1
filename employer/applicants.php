<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

$employerId = (int)$_SESSION['user_id'];
$stages = getEmployerApplicantsByStage($conn, $employerId);
$stageLabels = [
    'submitted' => 'New',
    'reviewing' => 'Reviewing',
    'shortlisted' => 'Shortlisted',
    'interview' => 'Interview',
    'assessment' => 'Assessment',
    'hired' => 'Hired',
    'rejected' => 'Rejected'
];

$pageTitle = 'Employer Applicants';
$activePage = 'applicants';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Applicants']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Applicants</h1>
</div>

<div class="grid xl:grid-cols-3 gap-5">
    <?php foreach ($stageLabels as $stage => $label): ?>
        <section class="card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold"><?= e($label) ?></h2>
                <span class="badge"><?= count($stages[$stage]) ?></span>
            </div>
            <div class="space-y-3">
                <?php foreach ($stages[$stage] as $applicant): ?>
                    <article class="bg-[#020B24] border border-[#334155] rounded-xl p-4 applicantCard" data-application-id="<?= (int)$applicant['application_id'] ?>">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div>
                                <h3 class="font-bold"><?= e($applicant['full_name']) ?></h3>
                                <p class="text-slate-400 text-sm"><?= e($applicant['job_title']) ?></p>
                            </div>
                            <span class="badge text-green-300 border-green-500/30 bg-green-500/10"><?= (int)$applicant['compatibility'] ?>% Match</span>
                        </div>
                        <p class="text-slate-500 text-sm mb-3"><?= e($applicant['career_path'] ?: 'Career not set') ?> &middot; <?= (int)$applicant['readiness_score'] ?>% readiness</p>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php foreach (array_slice(parseSkillTags($applicant['required_skills']), 0, 5) as $skill): ?>
                                <span class="badge"><?= e($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex flex-wrap gap-2 mb-4">
                            <a class="secondaryBtn px-3 py-2 text-sm" href="../student/portfolio.php?id=<?= (int)$applicant['user_id'] ?>">Portfolio</a>
                            <?php if (!empty($applicant['resume_path'])): ?>
                                <a class="secondaryBtn px-3 py-2 text-sm" href="../<?= e($applicant['resume_path']) ?>" target="_blank">Resume</a>
                            <?php endif; ?>
                            <?php if (!empty($applicant['cover_letter_path'])): ?>
                                <a class="secondaryBtn px-3 py-2 text-sm" href="../<?= e($applicant['cover_letter_path']) ?>" target="_blank">Cover Letter</a>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button class="secondaryBtn px-3 py-2 text-sm statusBtn" data-status="reviewing">Review</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="shortlisted">Shortlist</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="interview">Interview</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="assessment">Assessment</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="hired">Hire</button>
                            <button class="dangerBtn px-3 py-2 text-sm statusBtn" data-status="rejected">Reject</button>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (count($stages[$stage]) === 0): ?>
                    <p class="text-slate-500 text-sm">No applicants in this stage.</p>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.statusBtn').forEach(button => {
    button.addEventListener('click', async () => {
        const card = button.closest('.applicantCard');
        const result = await window.mmfPost('ajax_application_status.php', {
            application_id: card.dataset.applicationId,
            status: button.dataset.status
        });

        if (result.success) {
            window.location.reload();
        } else {
            alert(result.message || 'Unable to update applicant.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
