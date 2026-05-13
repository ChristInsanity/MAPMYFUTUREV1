<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$assessmentId = (int)($_GET['id'] ?? $_POST['assessment_id'] ?? 0);
$assessment = getAssessmentWithQuestions($conn, $assessmentId, $userId);

if (!$assessment || $assessment['task_status'] === 'locked') {
    redirect('assessments.php');
}

$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $answers = $_POST['answers'] ?? [];
    $score = gradeAssessment($conn, $assessmentId, $answers);
    $passed = saveAssessmentAttempt($conn, $userId, $assessmentId, $score);
    $result = [
        'score' => $score,
        'passed' => $passed
    ];
    $assessment = getAssessmentWithQuestions($conn, $assessmentId, $userId);
}

$pageTitle = 'Take Assessment';
$activePage = 'assessments';
include '../header.php';
?>

<div class="mb-8">
    <a href="assessments.php" class="text-blue-300 inline-flex items-center gap-2 mb-4">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Assessments
    </a>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($assessment['title']) ?></h1>
    <p class="text-slate-400"><?= e($assessment['description']) ?></p>
</div>

<?php if ($result): ?>
    <div class="<?= $result['passed'] ? 'bg-green-500/10 border-green-500/30' : 'bg-yellow-500/10 border-yellow-500/30' ?> border rounded-2xl p-6 mb-8">
        <div class="flex items-center gap-3 mb-2">
            <i class="fa-solid <?= $result['passed'] ? 'fa-circle-check text-green-300' : 'fa-rotate-right text-yellow-300' ?>"></i>
            <h2 class="text-2xl font-bold"><?= $result['passed'] ? 'Passed' : 'Retake Recommended' ?></h2>
        </div>
        <p class="text-slate-300">Score: <?= e($result['score']) ?>%. Passing score: <?= e($assessment['passing_score']) ?>%.</p>
    </div>
<?php endif; ?>

<form method="POST" class="space-y-5">
    <?= csrf_input() ?>
    <input type="hidden" name="assessment_id" value="<?= (int)$assessment['assessment_id'] ?>">

    <?php foreach ($assessment['questions'] as $index => $question): ?>
        <section class="card">
            <div class="flex gap-3 mb-5">
                <span class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center font-bold"><?= $index + 1 ?></span>
                <h2 class="text-xl font-bold"><?= e($question['question_text']) ?></h2>
            </div>

            <div class="grid md:grid-cols-3 gap-3">
                <?php foreach ($question['choices'] as $choice): ?>
                    <label class="cursor-pointer">
                        <input class="peer sr-only" type="radio" name="answers[<?= (int)$question['question_id'] ?>]" value="<?= (int)$choice['choice_id'] ?>" required>
                        <div class="bg-[#020B24] border border-slate-700 rounded-2xl p-4 min-h-[92px] flex items-center peer-checked:border-blue-500 peer-checked:bg-blue-500/10 transition">
                            <?= e($choice['choice_text']) ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <div class="flex justify-end">
        <button class="primaryBtn" type="submit">
            <i class="fa-solid fa-paper-plane"></i>
            Submit Answers
        </button>
    </div>
</form>

<?php include '../footer.php'; ?>
