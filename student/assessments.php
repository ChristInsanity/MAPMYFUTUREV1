<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_id'])) {
    require_csrf();
    $result = completeReadyQuizAttempt($conn, $userId, (int)$_POST['attempt_id']);

    if ($result) {
        $_SESSION['quiz_result'] = $result;
    }

    redirect('assessments.php');
}

$quizResult = $_SESSION['quiz_result'] ?? null;
if ($quizResult) unset($_SESSION['quiz_result']);
$readyAttempts = getReadyQuizAttempts($conn, $userId);
$completedAttempts = getCompletedQuizAttempts($conn, $userId, 6);

$pageTitle = 'Assessments';
$activePage = 'assessments';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Assessments']
];
include '../header.php';
?>
<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Quizzes and Skill Checks</h1>
    <p class="text-slate-400">Each completed lesson unlocks a quiz. Complete all to finish your subject.</p>
</div>

<?php if ($quizResult): ?>
    <div class="card mb-6 border-green-500">
        <h2 class="text-xl font-bold mb-2 text-green-300">QUIZ COMPLETED</h2>
        <p class="text-slate-300 mb-2">Score: <span class="font-bold text-blue-300"><?= (int)$quizResult['score'] ?>%</span></p>
        <span class="badge text-green-300 border-green-500/30 bg-green-500/10">
            COMPLETED
        </span>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($readyAttempts as $attempt): ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-300">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <span class="badge text-yellow-300 border-yellow-500/30 bg-yellow-500/10">
                    READY TO TAKE
                </span>
            </div>
            <h2 class="text-xl font-bold mb-2"><?= e($attempt['lesson_title']) ?></h2>
            <p class="text-slate-400 mb-5 leading-7"><?= e($attempt['subject_code']) ?> - <?= e($attempt['subject_title']) ?></p>
            <div class="space-y-3 mb-5">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Status</span>
                    <span>Assessment Unlocked</span>
                </div>
            </div>
            <form method="POST" class="quizForm">
                <?= csrf_input() ?>
                <input type="hidden" name="attempt_id" value="<?= (int)$attempt['attempt_id'] ?>">
                <button type="submit" class="primaryBtn w-full">
                    <i class="fa-solid fa-play"></i>
                    TAKE QUIZ
                </button>
            </form>
        </article>
    <?php endforeach; ?>
</div>

<?php if (count($readyAttempts) === 0): ?>
    <div class="card">
        <p class="text-slate-400">No ready quizzes right now. Read a lesson to unlock its assessment.</p>
    </div>
<?php endif; ?>

<?php if (count($completedAttempts) > 0): ?>
    <section class="card mt-8">
        <h2 class="sectionTitle mb-4">Completed</h2>
        <div class="space-y-3">
            <?php foreach ($completedAttempts as $attempt): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold"><?= e($attempt['lesson_title']) ?></p>
                        <p class="text-slate-500 text-sm"><?= e($attempt['subject_title']) ?></p>
                    </div>
                    <span class="badge text-green-300 border-green-500/30 bg-green-500/10">
                        <i class="fa-solid fa-circle-check"></i>
                        COMPLETED - Score <?= (int)$attempt['score'] ?>%
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<script>
document.querySelectorAll('.quizForm').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
        const result = await window.mmfPost('ajax_quiz.php', {
            attempt_id: form.querySelector('input[name="attempt_id"]').value
        });

        if (result.success) {
            form.closest('article').innerHTML = `
                <div class="flex justify-between gap-4 mb-5">
                    <div class="w-12 h-12 rounded-2xl bg-green-600/20 border border-green-500/30 flex items-center justify-center text-green-300">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <span class="badge text-green-300 border-green-500/30 bg-green-500/10">COMPLETED</span>
                </div>
                <h2 class="text-xl font-bold mb-2">Quiz Completed</h2>
                <p class="text-slate-300">Score <span class="font-bold text-blue-300">${result.score}%</span></p>
            `;
        } else {
            alert(result.message || 'Unable to complete quiz.');
            button.disabled = false;
            button.innerHTML = '<i class="fa-solid fa-play"></i> TAKE QUIZ';
        }
    });
});
</script>

<?php include '../footer.php'; ?>
