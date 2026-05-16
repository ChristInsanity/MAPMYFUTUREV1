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
            <p class="text-slate-400"><?= e($mentor['specialization'] ?: 'Mentor') ?> - <?= (int)($mentor['years_experience'] ?? 0) ?> years</p>
        </div>
    </div>
</div>

<div id="roomToast" class="hidden fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl"></div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="flex items-center justify-between gap-4 mb-5">
                <h2 class="sectionTitle">Conversation</h2>
                <button type="button" id="openQuestionModal" class="primaryBtn"><i class="fa-solid fa-circle-question"></i> Ask Question</button>
            </div>
            <div id="threadList" class="space-y-4 max-h-[520px] overflow-y-auto pr-1">
                <?php foreach ($messages as $message): ?>
                    <?php $isStudentMessage = (int)$message['sender_id'] === $studentId; ?>
                    <div class="messageRow flex gap-3 <?= $isStudentMessage ? 'justify-end' : 'justify-start' ?>">
                        <?php if (!$isStudentMessage): ?>
                            <div class="messageAvatar"><?= e(strtoupper(substr($message['full_name'], 0, 1))) ?></div>
                        <?php endif; ?>
                        <div class="max-w-[78%]">
                            <div class="flex items-center gap-2 mb-1 <?= $isStudentMessage ? 'justify-end' : '' ?>">
                                <span class="text-sm font-bold <?= $isStudentMessage ? 'text-green-200' : 'text-blue-200' ?>"><?= e($message['full_name']) ?></span>
                                <span class="text-xs text-slate-500"><?= e(date('M d, Y g:i A', strtotime($message['created_at']))) ?></span>
                            </div>
                            <div class="messageBubble <?= $isStudentMessage ? 'isStudent' : 'isMentor' ?>"><?= nl2br(e($message['message'])) ?></div>
                        </div>
                        <?php if ($isStudentMessage): ?>
                            <div class="messageAvatar isStudent"><?= e(strtoupper(substr($_SESSION['name'] ?? 'You', 0, 1))) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($messages) === 0): ?>
                    <p id="emptyThread" class="text-slate-400">No questions yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-5">Assigned Tasks</h2>
            <div class="taskTableWrap">
                <table class="taskTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Task Title</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <?php $status = $task['submission_status'] ?: 'assigned'; ?>
                            <tr class="taskRow" data-task-id="<?= (int)$task['mentor_task_id'] ?>">
                                <td><?= e($task['subject_title']) ?></td>
                                <td class="font-semibold"><?= e($task['title']) ?></td>
                                <td><?= $task['deadline'] ? e(date('M d, Y', strtotime($task['deadline']))) : 'No deadline' ?></td>
                                <td><span class="badge taskStatus <?= e(statusClass($status === 'approved' ? 'completed' : ($status === 'submitted' ? 'submitted' : 'available'))) ?>"><?= e(readableStatus($status)) ?></span></td>
                                <td><button type="button" class="secondaryBtn px-3 py-2 text-sm openTaskModal" data-task-id="<?= (int)$task['mentor_task_id'] ?>">View</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($tasks) === 0): ?>
                            <tr><td colspan="5" class="text-slate-400">No assigned tasks from this mentor yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <details class="card">
            <summary class="cursor-pointer sectionTitle list-none flex items-center justify-between">
                <span>Progress Timeline</span>
                <i class="fa-solid fa-chevron-down text-sm text-slate-400"></i>
            </summary>
            <div class="space-y-3 mt-5">
                <?php foreach ($tasks as $task): ?>
                    <div class="border-l-2 border-blue-500/40 pl-4 py-1">
                        <p class="font-semibold truncate"><?= e($task['title']) ?></p>
                        <p class="text-xs text-slate-400">Assigned <?= e(date('M d, Y', strtotime($task['created_at']))) ?></p>
                        <?php if ($task['submitted_at']): ?><p class="text-xs text-slate-400">Submitted <?= e(date('M d, Y', strtotime($task['submitted_at']))) ?></p><?php endif; ?>
                        <?php if ($task['reviewed_at']): ?><p class="text-xs text-slate-400">Reviewed <?= e(date('M d, Y', strtotime($task['reviewed_at']))) ?></p><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($tasks) === 0): ?><p class="text-slate-400">Timeline begins once your mentor assigns work.</p><?php endif; ?>
            </div>
        </details>
    </aside>
</div>

<div id="questionModal" class="hidden fixed inset-0 z-[60] bg-black/70 px-4 py-8">
    <div class="max-w-lg mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-2xl font-bold">Ask Question</h2>
            <button type="button" class="secondaryBtn px-3 py-2 closeModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="questionForm" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="mentor_id" value="<?= (int)$mentorId ?>">
            <textarea name="message" class="inputStyle min-h-[150px]" placeholder="Ask about a task, your portfolio, or your next step..." required></textarea>
            <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-paper-plane"></i> Send Question</button>
        </form>
    </div>
</div>

<?php foreach ($tasks as $task): ?>
    <div id="taskModal<?= (int)$task['mentor_task_id'] ?>" class="taskModal hidden fixed inset-0 z-[60] bg-black/70 px-4 py-8 overflow-y-auto">
        <div class="max-w-2xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
            <div class="flex items-start justify-between gap-4 mb-5">
                <div>
                    <p class="text-blue-300 text-sm font-semibold mb-1"><?= e($task['subject_title']) ?></p>
                    <h2 class="text-2xl font-bold"><?= e($task['title']) ?></h2>
                    <p class="text-slate-500 text-sm"><?= $task['deadline'] ? 'Due ' . e(date('M d, Y', strtotime($task['deadline']))) : 'No deadline' ?> - <?= (int)$task['points'] ?> pts</p>
                </div>
                <button type="button" class="secondaryBtn px-3 py-2 closeModal"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="space-y-4">
                <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <p class="text-slate-400 mb-2">Instructions</p>
                    <p class="text-slate-300"><?= nl2br(e($task['instructions'])) ?></p>
                </div>
                <?php if ($task['resources']): ?>
                    <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4">
                        <p class="text-slate-400 mb-2">Resources</p>
                        <p class="text-slate-300"><?= nl2br(e($task['resources'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if (!empty($task['attachment_file'])): ?>
                    <a href="../<?= e($task['attachment_file']) ?>" target="_blank" class="secondaryBtn"><i class="fa-solid fa-paperclip"></i> Open Attachment</a>
                <?php endif; ?>
                <form class="submissionForm space-y-3" data-task-id="<?= (int)$task['mentor_task_id'] ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="mentor_task_id" value="<?= (int)$task['mentor_task_id'] ?>">
                    <input type="file" name="submission_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2">
                    <input type="url" name="submission_link" class="inputStyle" placeholder="Optional project link">
                    <textarea name="notes" class="inputStyle min-h-[100px]" placeholder="Notes for your mentor"></textarea>
                    <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-upload"></i> Submit Work</button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
    .messageAvatar{width:36px;height:36px;border-radius:999px;background:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800;flex:0 0 auto;}
    .messageAvatar.isStudent{background:#16a34a;}
    .messageBubble{border:1px solid #334155;padding:12px 14px;border-radius:14px;line-height:1.6;}
    .messageBubble.isMentor{background:#020B24;color:#cbd5e1;}
    .messageBubble.isStudent{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.28);color:#dcfce7;}
    .taskTableWrap{max-height:520px;overflow:auto;border:1px solid #334155;border-radius:14px;}
    .taskTable{width:100%;border-collapse:separate;border-spacing:0;}
    .taskTable th{position:sticky;top:0;background:#0f172a;color:#94a3b8;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.08em;padding:12px 14px;z-index:1;}
    .taskTable td{height:64px;padding:10px 14px;border-top:1px solid #334155;color:#cbd5e1;vertical-align:middle;}
</style>

<script>
const questionModal = document.getElementById('questionModal');
const threadList = document.getElementById('threadList');
const roomToast = document.getElementById('roomToast');

function showRoomToast(message, success = true) {
    roomToast.textContent = message;
    roomToast.className = `fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl ${success ? 'bg-green-500/10 border-green-500 text-green-200' : 'bg-red-500/10 border-red-500 text-red-200'}`;
    roomToast.classList.remove('hidden');
    setTimeout(() => roomToast.classList.add('hidden'), 2600);
}

function closeAllModals() {
    document.querySelectorAll('#questionModal, .taskModal').forEach(modal => modal.classList.add('hidden'));
}

document.getElementById('openQuestionModal').addEventListener('click', () => questionModal.classList.remove('hidden'));
document.querySelectorAll('.closeModal').forEach(button => button.addEventListener('click', closeAllModals));
document.querySelectorAll('.openTaskModal').forEach(button => {
    button.addEventListener('click', () => document.getElementById(`taskModal${button.dataset.taskId}`)?.classList.remove('hidden'));
});

function scrollThreadToLatest() {
    threadList.scrollTop = threadList.scrollHeight;
}
scrollThreadToLatest();

document.getElementById('questionForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await window.mmfPost('ajax_mentor_question.php', new FormData(form), true);

    if (!result.success) {
        showRoomToast(result.message || 'Unable to send.', false);
        return;
    }

    document.getElementById('emptyThread')?.remove();
    const item = document.createElement('div');
    item.className = 'messageRow flex gap-3 justify-end';
    item.innerHTML = `<div class="max-w-[78%]"><div class="flex items-center gap-2 mb-1 justify-end"><span class="text-sm font-bold text-green-200"></span><span class="text-xs text-slate-500"></span></div><div class="messageBubble isStudent"></div></div><div class="messageAvatar isStudent"></div>`;
    item.querySelector('.text-green-200').textContent = result.question.sender;
    item.querySelector('.text-slate-500').textContent = result.question.created_at;
    item.querySelector('.messageBubble').textContent = result.question.message;
    item.querySelector('.messageAvatar').textContent = (result.question.sender || 'Y').charAt(0).toUpperCase();
    threadList.appendChild(item);
    form.reset();
    closeAllModals();
    showRoomToast('Question sent.');
    scrollThreadToLatest();
});

document.querySelectorAll('.submissionForm').forEach(form => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading';
        const result = await window.mmfPost('ajax_task_submission.php', new FormData(form), true);
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-upload"></i> Submit Work';

        if (result.success) {
            const row = document.querySelector(`.taskRow[data-task-id="${form.dataset.taskId}"]`);
            const status = row?.querySelector('.taskStatus');
            if (status) {
                status.className = 'badge taskStatus text-purple-300 border-purple-500/30 bg-purple-500/10';
                status.textContent = 'Submitted';
            }
            form.reset();
            closeAllModals();
            showRoomToast('Submission uploaded.');
        } else {
            showRoomToast(result.message || 'Upload failed.', false);
        }
    });
});
</script>

<?php include '../footer.php'; ?>
