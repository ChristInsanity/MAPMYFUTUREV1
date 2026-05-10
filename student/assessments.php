<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

$assessments = getAssessments($conn, $userId);
$pageTitle = 'Assessments';
$activePage = 'assessments';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Assessment module</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Quizzes and Skill Checks</h1>
    <p class="text-slate-400">Passing scores are saved to your profile and unlock the next roadmap tasks.</p>
</div>

<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($assessments as $assessment): ?>
        <?php $locked = $assessment['task_status'] === 'locked'; ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-300">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <span class="badge <?= e(statusClass($assessment['task_status'])) ?>"><?= e(readableStatus($assessment['task_status'])) ?></span>
            </div>

            <h2 class="text-xl font-bold mb-2"><?= e($assessment['title']) ?></h2>
            <p class="text-slate-400 mb-5 leading-7"><?= e($assessment['description']) ?></p>

            <div class="space-y-3 mb-5">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Passing Score</span>
                    <span><?= e($assessment['passing_score']) ?>%</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Latest Score</span>
                    <span><?= $assessment['latest_score'] !== null ? e($assessment['latest_score']) . '%' : 'Not taken' ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Result</span>
                    <span class="<?= (int)($assessment['latest_passed'] ?? 0) === 1 ? 'text-green-300' : 'text-yellow-300' ?>">
                        <?= $assessment['latest_score'] !== null ? ((int)$assessment['latest_passed'] === 1 ? 'Passed' : 'Needs retake') : 'Pending' ?>
                    </span>
                </div>
            </div>

            <?php if ($locked): ?>
                <button type="button" class="secondaryBtn opacity-60 cursor-not-allowed w-full">
                    <i class="fa-solid fa-lock"></i>
                    Locked
                </button>
            <?php else: ?>
                <a href="assessment_take.php?id=<?= (int)$assessment['assessment_id'] ?>" class="primaryBtn w-full">
                    <i class="fa-solid fa-play"></i>
                    <?= $assessment['latest_score'] !== null ? 'Retake Quiz' : 'Take Quiz' ?>
                </a>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>

<?php if (count($assessments) === 0): ?>
    <div class="card">
        <p class="text-slate-400">No assessments are attached to your active roadmap yet.</p>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
