<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$subjectId = (int)($_GET['id'] ?? $_POST['subject_id'] ?? 0);
$data = getSubjectLearningData($conn, $userId, $subjectId);

if (!$data) {
    redirect('roadmap.php');
}

$subject = $data['subject'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'start_subject') {
        startSubject($conn, $userId, $subjectId);
    }

    if ($action === 'complete_task') {
        completeModuleTask($conn, $userId, (int)($_POST['task_id'] ?? 0));
    }

    redirect('subject.php?id=' . $subjectId);
}

$locked = $subject['status'] === 'locked';
$data = getSubjectLearningData($conn, $userId, $subjectId);
$subject = $data['subject'];
$modules = $data['modules'];

$pageTitle = $subject['subject_title'];
$activePage = 'roadmap';
include '../header.php';
?>

<div class="mb-8">
    <a href="roadmap.php" class="text-blue-300 inline-flex items-center gap-2 mb-4">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Roadmap
    </a>

    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-5">
        <div>
            <p class="text-blue-300 font-semibold mb-2">Year <?= (int)$subject['year_number'] ?> Semester <?= (int)$subject['semester_number'] ?></p>
            <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($subject['subject_title']) ?></h1>
            <p class="text-slate-400 leading-7"><?= e($subject['description']) ?></p>
        </div>
        <span class="badge <?= e(statusClass($subject['status'])) ?> self-start">
            <?= e(readableStatus($subject['status'])) ?>
        </span>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-6">
        <?php if ($locked): ?>
            <section class="card">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fa-solid fa-lock text-slate-500"></i>
                    <h2 class="sectionTitle">Locked Subject</h2>
                </div>
                <p class="text-slate-400">Complete the previous semester to unlock this subject.</p>
            </section>
        <?php else: ?>
            <?php foreach ($modules as $module): ?>
                <section class="card">
                    <div class="flex items-center gap-3 mb-5">
                        <span class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center font-bold"><?= (int)$module['module_order'] ?></span>
                        <h2 class="sectionTitle"><?= e($module['title']) ?></h2>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-bold mb-3 text-slate-300">Lessons</h3>
                        <div class="space-y-3">
                            <?php foreach ($module['lessons'] as $lesson): ?>
                                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid <?= $lesson['content_type'] === 'video' ? 'fa-circle-play' : ($lesson['content_type'] === 'pdf' ? 'fa-file-pdf' : 'fa-book-open') ?> text-blue-300"></i>
                                        <div>
                                            <p class="font-semibold"><?= e($lesson['title']) ?></p>
                                            <p class="text-slate-500 text-sm"><?= e(ucfirst($lesson['content_type'])) ?> lesson</p>
                                        </div>
                                    </div>
                                    <?php if ($lesson['content_url']): ?>
                                        <a href="<?= e($lesson['content_url']) ?>" class="secondaryBtn" target="_blank">Open</a>
                                    <?php else: ?>
                                        <span class="badge text-blue-300 border-blue-500/30 bg-blue-500/10">Self-paced</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold mb-3 text-slate-300">Tasks</h3>
                        <div class="space-y-3">
                            <?php foreach ($module['tasks'] as $task): ?>
                                <?php $done = $task['submission_status'] === 'completed'; ?>
                                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <i class="fa-solid <?= e(taskIcon($task['task_type'])) ?> text-blue-300"></i>
                                            <div>
                                                <p class="font-semibold"><?= e($task['title']) ?></p>
                                                <p class="text-slate-500 text-sm"><?= e(ucfirst($task['task_type'])) ?> - <?= (int)$task['points'] ?> points</p>
                                            </div>
                                        </div>

                                        <?php if ($done): ?>
                                            <span class="badge text-green-300 border-green-500/30 bg-green-500/10">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Completed
                                            </span>
                                        <?php else: ?>
                                            <form method="POST">
                                                <input type="hidden" name="subject_id" value="<?= (int)$subjectId ?>">
                                                <input type="hidden" name="task_id" value="<?= (int)$task['task_id'] ?>">
                                                <input type="hidden" name="action" value="complete_task">
                                                <button class="primaryBtn" type="submit">
                                                    <i class="fa-solid fa-check"></i>
                                                    <?= $task['task_type'] === 'quiz' ? 'Complete Quiz' : 'Submit Task' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($task['feedback']): ?>
                                        <div class="mt-3 bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 text-sm text-slate-300">
                                            <?= e($task['feedback']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <aside class="space-y-6">
        <section class="card">
            <h2 class="sectionTitle mb-4">Subject Progress</h2>
            <div class="text-4xl font-bold mb-4"><?= (int)$subject['progress'] ?>%</div>
            <div class="bg-[#020B24] h-3 rounded-full overflow-hidden mb-5">
                <div class="h-full bg-blue-500" style="width:<?= (int)$subject['progress'] ?>%"></div>
            </div>

            <?php if ($subject['status'] === 'available'): ?>
                <form method="POST">
                    <input type="hidden" name="subject_id" value="<?= (int)$subjectId ?>">
                    <input type="hidden" name="action" value="start_subject">
                    <button class="primaryBtn w-full" type="submit">
                        <i class="fa-solid fa-play"></i>
                        Enroll and Start
                    </button>
                </form>
            <?php elseif ($subject['status'] === 'completed'): ?>
                <div class="badge text-green-300 border-green-500/30 bg-green-500/10">
                    <i class="fa-solid fa-circle-check"></i>
                    Subject Completed
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2 class="sectionTitle mb-4">Learning Mode</h2>
            <div class="space-y-3">
                <div class="bg-[#020B24] border border-blue-500/30 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-1">
                        <i class="fa-solid fa-unlock text-blue-300"></i>
                        <p class="font-bold">Free</p>
                    </div>
                    <p class="text-slate-400 text-sm">Self-paced modules, quizzes, and auto progression.</p>
                </div>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-1">
                        <i class="fa-solid fa-users text-purple-300"></i>
                        <p class="font-bold">Premium</p>
                    </div>
                    <p class="text-slate-400 text-sm">Mentor support, submission review, and feedback.</p>
                </div>
            </div>
        </section>
    </aside>
</div>

<?php include '../footer.php'; ?>
