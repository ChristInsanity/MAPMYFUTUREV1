<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$tasks = getAvailableMentorTasksForStudent($conn, $userId);

$pageTitle = 'Mentor Tasks';
$activePage = 'my_mentor';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'My Mentor', 'url' => 'mentors.php'],
    ['label' => 'Mentor Tasks']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">New Mentor Task</h1>
</div>

<div class="grid lg:grid-cols-2 gap-5">
    <?php foreach ($tasks as $task): ?>
        <article class="card">
            <div class="flex justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-bold"><?= e($task['title']) ?></h2>
                    <p class="text-slate-400"><?= e($task['subject_title']) ?> - <?= e($task['lesson_title']) ?></p>
                    <p class="text-slate-500 text-sm">Mentor: <?= e($task['mentor_name']) ?></p>
                </div>
                <span class="badge <?= e(statusClass($task['submission_status'] ? ($task['submission_status'] === 'approved' ? 'completed' : 'submitted') : 'available')) ?>">
                    <?= e($task['submission_status'] ? readableStatus($task['submission_status']) : 'New') ?>
                </span>
            </div>
            <p class="text-slate-300 leading-7 mb-4"><?= e($task['instructions']) ?></p>
            <?php if ($task['resources']): ?>
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 text-slate-300 mb-4"><?= nl2br(e($task['resources'])) ?></div>
            <?php endif; ?>
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <span class="text-slate-400"><?= (int)$task['points'] ?> points<?= $task['deadline'] ? ' - Due ' . e(date('M d, Y', strtotime($task['deadline']))) : '' ?></span>
                <a href="submit_mentor_task.php?id=<?= (int)$task['mentor_task_id'] ?>" class="primaryBtn">
                    <i class="fa-solid fa-upload"></i>
                    <?= $task['submission_id'] ? 'Update Submission' : 'Upload Submission' ?>
                </a>
            </div>
            <?php if ($task['comment']): ?>
                <div class="mt-4 bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 text-slate-300">
                    <p class="font-bold text-blue-200 mb-1">Mentor Comment</p>
                    <?= e($task['comment']) ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (count($tasks) === 0): ?>
        <div class="card lg:col-span-2">
            <p class="text-slate-400">No mentor tasks yet. Approved mentor assignments will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
