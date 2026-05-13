<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$studentId = (int)($_POST['student_id'] ?? 0);
$details = getEmployerApplicantDetails($conn, (int)$_SESSION['user_id'], $studentId);

if (!$details) {
    jsonResponse(['success' => false, 'message' => 'Applicant not found.'], 404);
}

ob_start();
?>
<div class="space-y-4">
    <div>
        <h3 class="text-2xl font-bold"><?= e($details['full_name']) ?></h3>
        <p class="text-slate-400"><?= e($details['email']) ?> · <?= e($details['career_path'] ?: 'Career not set') ?></p>
    </div>
    <div class="grid sm:grid-cols-2 gap-3">
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <p class="text-slate-400 text-sm">Readiness Score</p>
            <p class="text-2xl font-bold text-blue-200"><?= (int)$details['readiness_score'] ?>%</p>
        </div>
        <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
            <p class="text-slate-400 text-sm">Skills</p>
            <p class="text-slate-200"><?= e($details['skills'] ?: 'No skills listed') ?></p>
        </div>
    </div>
    <div>
        <h4 class="font-bold mb-2">Portfolio</h4>
        <div class="grid sm:grid-cols-2 gap-3">
            <?php foreach ($details['portfolio'] as $project): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="font-semibold"><?= e($project['title']) ?></p>
                    <p class="text-slate-400 text-sm"><?= e($project['description']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (count($details['portfolio']) === 0): ?><p class="text-slate-400">No portfolio projects yet.</p><?php endif; ?>
        </div>
    </div>
    <div>
        <h4 class="font-bold mb-2">Certifications</h4>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($details['certifications'] as $cert): ?>
                <span class="badge text-yellow-200 border-yellow-500/30 bg-yellow-500/10"><?= e($cert['title']) ?></span>
            <?php endforeach; ?>
            <?php if (count($details['certifications']) === 0): ?><span class="text-slate-400">No certifications listed.</span><?php endif; ?>
        </div>
    </div>
</div>
<?php
$html = ob_get_clean();
jsonResponse(['success' => true, 'html' => $html]);
?>
