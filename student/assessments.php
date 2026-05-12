<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$profile = getStudentProfile($conn, $userId);

if (!$profile) {
    redirect('profile_setup.php');
}

// === LESSON QUIZ CARDS ===
$pageTitle = 'Assessments';
$activePage = 'assessments';
include '../header.php';

// Get all completed lessons for this student
$subjects = getStudentSubjectRows($conn, $userId);
$completedLessons = [];
foreach ($subjects as $subject) {
    $data = getSubjectLearningData($conn, $userId, $subject['subject_id']);
    foreach ($data['modules'] as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!empty($lesson['completed'])) {
                $completedLessons[] = $lesson;
            }
        }
    }
}

// Handle quiz simulation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['take_quiz_lesson_id'])) {
    $lessonId = (int)$_POST['take_quiz_lesson_id'];
    $quiz = getLessonQuizByLesson($conn, $lessonId);
    if ($quiz) {
        $result = createLessonQuizAttempt($conn, $userId, $quiz['quiz_id']);
        $_SESSION['quiz_result'] = [
            'lesson_id' => $lessonId,
            'score' => $result['score'],
            'passed' => $result['passed']
        ];
        header('Location: assessments.php');
        exit;
    }
}

// Show quiz result if just taken
$quizResult = $_SESSION['quiz_result'] ?? null;
if ($quizResult) unset($_SESSION['quiz_result']);

?>
<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Assessment module</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Quizzes and Skill Checks</h1>
    <p class="text-slate-400">Each completed lesson unlocks a quiz. Complete all to finish your subject.</p>
</div>

<?php if ($quizResult): ?>
    <div class="card mb-6 border-green-500">
        <h2 class="text-xl font-bold mb-2 text-green-300">QUIZ COMPLETED</h2>
        <p class="text-slate-300 mb-2">Score: <span class="font-bold text-blue-300"><?= (int)$quizResult['score'] ?>%</span></p>
        <span class="badge <?= $quizResult['passed'] ? 'text-green-300 border-green-500/30 bg-green-500/10' : 'text-yellow-300 border-yellow-500/30 bg-yellow-500/10' ?>">
            <?= $quizResult['passed'] ? 'COMPLETED' : 'FAILED' ?>
        </span>
    </div>
<?php endif; ?>

<div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($completedLessons as $lesson): ?>
        <?php
            $quiz = getLessonQuizByLesson($conn, $lesson['lesson_id']);
            if (!$quiz) continue;
            $attempt = getLessonQuizAttempt($conn, $userId, $quiz['quiz_id']);
            $quizDone = $attempt && $attempt['passed'] == 1;
        ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-5">
                <div class="w-12 h-12 rounded-2xl bg-blue-600/20 border border-blue-500/30 flex items-center justify-center text-blue-300">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <span class="badge <?= $quizDone ? 'text-green-300 border-green-500/30 bg-green-500/10' : 'text-yellow-300 border-yellow-500/30 bg-yellow-500/10' ?>">
                    <?= $quizDone ? 'COMPLETED' : 'READY TO TAKE' ?>
                </span>
            </div>
            <h2 class="text-xl font-bold mb-2"><?= e($lesson['title']) ?></h2>
            <p class="text-slate-400 mb-5 leading-7">Quiz for this lesson. Passing score: <?= (int)$quiz['passing_score'] ?>%</p>
            <div class="space-y-3 mb-5">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Latest Score</span>
                    <span><?= $attempt ? ((int)$attempt['score']) . '%' : 'Not taken' ?></span>
                </div>
            </div>
            <?php if ($quizDone): ?>
                <span class="badge text-green-300 border-green-500/30 bg-green-500/10 w-full block text-center">
                    <i class="fa-solid fa-circle-check"></i>
                    COMPLETED
                </span>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="take_quiz_lesson_id" value="<?= (int)$lesson['lesson_id'] ?>">
                    <button type="submit" class="primaryBtn w-full">
                        <i class="fa-solid fa-play"></i>
                        TAKE QUIZ
                    </button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>

<?php if (count($completedLessons) === 0): ?>
    <div class="card">
        <p class="text-slate-400">No completed lessons yet. Complete a lesson to unlock its quiz.</p>
    </div>
<?php endif; ?>

<?php include '../footer.php'; ?>
