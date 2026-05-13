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
    <p class="text-blue-300 font-semibold mb-2">Applicant tracking system</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Applicants</h1>
    <p class="text-slate-400">Move students through hiring stages and record hires into employment history.</p>
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
                        <h3 class="font-bold"><?= e($applicant['full_name']) ?></h3>
                        <p class="text-slate-400 text-sm mb-2"><?= e($applicant['job_title']) ?></p>
                        <p class="text-slate-500 text-sm mb-4"><?= e($applicant['career_path'] ?: 'Career not set') ?> · <?= (int)$applicant['readiness_score'] ?>% readiness</p>
                        <div class="flex flex-wrap gap-2">
                            <a class="secondaryBtn px-3 py-2 text-sm" href="../student/portfolio.php?id=<?= (int)$applicant['user_id'] ?>">Portfolio</a>
                            <button class="secondaryBtn px-3 py-2 text-sm statusBtn" data-status="reviewing">Review</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="shortlisted">Shortlist</button>
                            <button class="primaryBtn px-3 py-2 text-sm statusBtn" data-status="interview">Interview</button>
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
