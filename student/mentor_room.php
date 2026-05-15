<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$studentId = (int)$_SESSION['user_id'];
$mentorId = (int)($_GET['id'] ?? 0);
$assignment = getStudentMentorAssignment($conn, $studentId, $mentorId);

if (!$assignment) {
    redirect('mentors.php');
}

dbExecute(
    $conn,
    "UPDATE mentor_messages SET read_at = NOW() WHERE assignment_id = ? AND sender_id = ? AND read_at IS NULL",
    "ii",
    [(int)$assignment['assignment_id'], $mentorId]
);

$mentor = dbFetchOne(
    $conn,
    "SELECT u.user_id, u.full_name, u.email, u.profile_photo,
            mp.specialization, mp.years_experience, mp.bio
     FROM users u
     LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
     WHERE u.user_id = ? AND u.role = 'mentor'",
    "i",
    [$mentorId]
);
$messages = getMentorConversation($conn, $studentId, $mentorId);
$tasks = getMentorRoomTasks($conn, $studentId, $mentorId);
$tasksByLesson = [];

foreach ($tasks as $task) {
    $tasksByLesson[$task['lesson_title']][] = $task;
}

$pageTitle = 'Mentor Room';
$activePage = 'my_mentor';
$backUrl = 'mentors.php';
$backLabel = 'My Mentor';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'My Mentor', 'url' => 'mentors.php'],
    ['label' => $mentor['full_name'] ?? 'Mentor Room']
];
include '../header.php';
?>

<div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-5">
    <div class="flex items-center gap-4">
        <div class="w-16 h-16 rounded-full bg-blue-600 overflow-hidden flex items-center justify-center text-2xl font-bold">
            <?php if (!empty($mentor['profile_photo'])): ?>
                <img src="../<?= e($mentor['profile_photo']) ?>" alt="<?= e($mentor['full_name']) ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <?= e(strtoupper(substr($mentor['full_name'], 0, 1))) ?>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-blue-300 font-semibold mb-1">Mentor room</p>
            <h1 class="text-3xl lg:text-4xl font-bold"><?= e($mentor['full_name']) ?></h1>
            <p class="text-slate-400"><?= e($mentor['specialization'] ?: 'Mentor') ?> · <?= (int)($mentor['years_experience'] ?? 0) ?> years</p>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="flex items-center justify-between mb-5">
                <h2 class="sectionTitle">Ask Question</h2>
                <span class="badge text-slate-300">Mentor replies enabled later</span>
            </div>
            <form id="questionForm" class="space-y-4">
                <?= csrf_input() ?>
                <input type="hidden" name="mentor_id" value="<?= (int)$mentorId ?>">
                <textarea name="message" class="inputStyle min-h-[120px]" placeholder="Ask about this lesson, your portfolio, or your next step..." required></textarea>
                <button class="primaryBtn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send Question</button>
            </form>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-5">Thread</h2>
            <div id="threadList" class="space-y-3">
                <?php foreach ($messages as $message): ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                        <div class="flex justify-between gap-3 mb-2 text-sm">
                            <span class="font-bold text-blue-200"><?= e($message['full_name']) ?></span>
                            <span class="text-slate-500"><?= e(date('M d, Y g:i A', strtotime($message['created_at']))) ?></span>
                        </div>
                        <p class="text-slate-300"><?= nl2br(e($message['message'])) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (count($messages) === 0): ?>
                    <p id="emptyThread" class="text-slate-400">No questions yet. Start the thread above.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-5">Assigned Tasks</h2>
            <div class="space-y-6">
                <?php foreach ($tasksByLesson as $lessonTitle => $lessonTasks): ?>
                    <div>
                        <h3 class="text-lg font-bold mb-3 text-blue-200"><?= e($lessonTitle) ?></h3>
                        <div class="space-y-3">
                            <?php foreach ($lessonTasks as $task): ?>
                                <?php $status = $task['submission_status'] ?: 'assigned'; ?>
                                <article class="bg-[#020B24] border border-[#334155] rounded-xl p-4 taskCard" data-task-id="<?= (int)$task['mentor_task_id'] ?>">
                                    <div class="flex flex-col md:flex-row md:items-start justify-between gap-3 mb-3">
                                        <div>
                                            <h4 class="font-bold"><?= e($task['title']) ?></h4>
                                            <p class="text-slate-500 text-sm"><?= $task['deadline'] ? 'Due ' . e(date('M d, Y', strtotime($task['deadline']))) : 'No deadline' ?> · <?= (int)$task['points'] ?> pts</p>
                                        </div>
                                        <span class="badge taskStatus <?= e(statusClass($status === 'approved' ? 'completed' : ($status === 'submitted' ? 'submitted' : 'available'))) ?>">
                                            <?= e(readableStatus($status)) ?>
                                        </span>
                                    </div>
                                    <p class="text-slate-300 mb-3"><?= nl2br(e($task['instructions'])) ?></p>
                                    <?php if ($task['resources']): ?>
                                        <div class="mb-3 text-sm text-slate-300 bg-[#162338] border border-[#334155] rounded-xl p-3"><?= nl2br(e($task['resources'])) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($task['attachment_file'])): ?>
                                        <a href="../<?= e($task['attachment_file']) ?>" target="_blank" class="secondaryBtn px-3 py-2 text-sm mb-3"><i class="fa-solid fa-paperclip"></i> Open Attachment</a>
                                    <?php endif; ?>
                                    <form class="submissionForm grid md:grid-cols-[1fr_auto] gap-3 items-end">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="mentor_task_id" value="<?= (int)$task['mentor_task_id'] ?>">
                                        <div class="space-y-3">
                                            <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2">
                                            <input type="url" name="submission_link" class="inputStyle" placeholder="Optional project link">
                                            <textarea name="notes" class="inputStyle min-h-[90px]" placeholder="Notes for your mentor"></textarea>
                                        </div>
                                        <button class="primaryBtn" type="submit"><i class="fa-solid fa-upload"></i> Upload</button>
                                    </form>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (count($tasksByLesson) === 0): ?>
                    <p class="text-slate-400">No assigned tasks from this mentor yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-5">Progress Timeline</h2>
            <div class="space-y-4">
                <?php foreach ($tasks as $task): ?>
                    <div class="border-l-2 border-blue-500/40 pl-4">
                        <p class="font-semibold"><?= e($task['title']) ?></p>
                        <p class="text-sm text-slate-400">Task assigned <?= e(date('M d, Y', strtotime($task['created_at']))) ?></p>
                        <?php if ($task['submitted_at']): ?>
                            <p class="text-sm text-slate-400">Submitted <?= e(date('M d, Y', strtotime($task['submitted_at']))) ?></p>
                        <?php endif; ?>
                        <?php if ($task['reviewed_at']): ?>
                            <p class="text-sm text-slate-400">Reviewed <?= e(date('M d, Y', strtotime($task['reviewed_at']))) ?></p>
                        <?php endif; ?>
                        <?php if ($task['submission_status'] === 'approved'): ?>
                            <p class="text-sm text-green-300">Completed</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($tasks) === 0): ?><p class="text-slate-400">Timeline begins once your mentor assigns work.</p><?php endif; ?>
            </div>
        </div>
    </aside>
</div>

<script>
document.getElementById('questionForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await window.mmfPost('ajax_mentor_question.php', new FormData(form), true);

    if (!result.success) {
        alert(result.message || 'Unable to send.');
        return;
    }

    document.getElementById('emptyThread')?.remove();
    const item = document.createElement('div');
    item.className = 'bg-[#020B24] border border-[#334155] rounded-xl p-4';
    item.innerHTML = `<div class="flex justify-between gap-3 mb-2 text-sm"><span class="font-bold text-blue-200">${result.question.sender}</span><span class="text-slate-500">${result.question.created_at}</span></div><p class="text-slate-300"></p>`;
    item.querySelector('p').textContent = result.question.message;
    document.getElementById('threadList').appendChild(item);
    form.reset();
});

document.querySelectorAll('.submissionForm').forEach(form => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading';
        const result = await window.mmfPost('ajax_task_submission.php', new FormData(form), true);
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-upload"></i> Upload';

        if (result.success) {
            const status = form.closest('.taskCard').querySelector('.taskStatus');
            status.className = 'badge taskStatus text-purple-300 border-purple-500/30 bg-purple-500/10';
            status.textContent = 'Submitted';
            form.reset();
        } else {
            alert(result.message || 'Upload failed.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
