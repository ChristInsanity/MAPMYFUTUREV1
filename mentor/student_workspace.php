<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$studentId = (int)($_GET['student_id'] ?? 0);
$students = getMentorStudentsOverview($conn, $mentorId);
$student = null;

foreach ($students as $candidate) {
    if ((int)$candidate['student_id'] === $studentId && $candidate['status'] === 'active') {
        $student = $candidate;
        break;
    }
}

if (!$student) {
    redirect('students.php');
}

$assignment = dbFetchOne(
    $conn,
    "SELECT assignment_id FROM mentor_assignments WHERE mentor_id = ? AND student_id = ? AND status = 'active'",
    "ii",
    [$mentorId, $studentId]
);

if ($assignment) {
    dbExecute(
        $conn,
        "UPDATE mentor_messages SET read_at = NOW() WHERE assignment_id = ? AND sender_id = ? AND read_at IS NULL",
        "ii",
        [(int)$assignment['assignment_id'], $studentId]
    );
}

$messages = $assignment ? dbFetchAll(
    $conn,
    "SELECT mm.*, u.full_name
     FROM mentor_messages mm
     JOIN users u ON u.user_id = mm.sender_id
     WHERE mm.assignment_id = ?
     ORDER BY mm.created_at ASC",
    "i",
    [(int)$assignment['assignment_id']]
) : [];

$lessons = getMentorAssignableLessons($conn, $mentorId, $studentId);
$tasks = getMentorRoomTasks($conn, $studentId, $mentorId);
$submissions = array_values(array_filter($tasks, fn($task) => !empty($task['submission_id'])));

$pageTitle = 'Student Workspace';
$activePage = 'students';
$backUrl = 'students.php';
$backLabel = 'Students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Students', 'url' => 'students.php'],
    ['label' => $student['full_name']]
];
include '../header.php';
?>

<div class="mb-8 flex flex-col lg:flex-row lg:items-start justify-between gap-5">
    <div>
        <p class="text-blue-300 font-semibold mb-1">Student Workspace</p>
        <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($student['full_name']) ?></h1>
        <p class="text-slate-400"><?= e($student['career_path'] ?: 'Career not set') ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <span class="badge text-blue-300 border-blue-500/30 bg-blue-500/10">
            <?= $student['year_number'] && $student['semester_number'] ? 'Year ' . (int)$student['year_number'] . ' - Semester ' . (int)$student['semester_number'] : 'Year/semester not set' ?>
        </span>
        <span class="badge text-green-300 border-green-500/30 bg-green-500/10"><?= (int)$student['roadmap_progress'] ?>% roadmap</span>
        <span class="badge text-purple-300 border-purple-500/30 bg-purple-500/10"><?= (int)$student['readiness_score'] ?>% readiness</span>
    </div>
</div>

<div id="workspaceToast" class="hidden fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl"></div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="flex items-center justify-between gap-4 mb-5">
                <h2 class="sectionTitle">Communication</h2>
                <button type="button" id="openMessageModal" class="primaryBtn"><i class="fa-solid fa-message"></i> Message Student</button>
            </div>
            <div id="threadList" class="space-y-4 max-h-[500px] overflow-y-auto pr-1">
                <?php foreach ($messages as $message): ?>
                    <?php $isMentorMessage = (int)$message['sender_id'] === $mentorId; ?>
                    <div class="messageRow flex gap-3 <?= $isMentorMessage ? 'justify-end' : 'justify-start' ?>">
                        <?php if (!$isMentorMessage): ?><div class="messageAvatar"><?= e(strtoupper(substr($message['full_name'], 0, 1))) ?></div><?php endif; ?>
                        <div class="max-w-[78%]">
                            <div class="flex items-center gap-2 mb-1 <?= $isMentorMessage ? 'justify-end' : '' ?>">
                                <span class="text-sm font-bold <?= $isMentorMessage ? 'text-green-200' : 'text-blue-200' ?>"><?= e($message['full_name']) ?></span>
                                <span class="text-xs text-slate-500"><?= e(date('M d, Y g:i A', strtotime($message['created_at']))) ?></span>
                            </div>
                            <div class="messageBubble <?= $isMentorMessage ? 'isMentorOwn' : 'isStudent' ?>"><?= nl2br(e($message['message'])) ?></div>
                        </div>
                        <?php if ($isMentorMessage): ?><div class="messageAvatar isMentorOwn"><?= e(strtoupper(substr($_SESSION['name'] ?? 'M', 0, 1))) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (count($messages) === 0): ?><p id="emptyThread" class="text-slate-400">No messages yet.</p><?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="flex items-center justify-between gap-4 mb-5">
                <h2 class="sectionTitle">Task Management</h2>
                <button type="button" id="openAssignModal" class="primaryBtn"><i class="fa-solid fa-list-check"></i> Assign Task</button>
            </div>
            <div class="taskTableWrap">
                <table class="taskTable">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Task</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <?php $status = $task['submission_status'] ?: 'assigned'; ?>
                            <tr>
                                <td><?= e($task['subject_title']) ?></td>
                                <td class="font-semibold"><?= e($task['title']) ?></td>
                                <td><?= $task['deadline'] ? e(date('M d, Y', strtotime($task['deadline']))) : 'No deadline' ?></td>
                                <td><span class="badge <?= e(statusClass($status === 'approved' ? 'completed' : ($status === 'submitted' ? 'submitted' : 'available'))) ?>"><?= e(readableStatus($status)) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($tasks) === 0): ?><tr><td colspan="4" class="text-slate-400">No tasks assigned yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-5">Submission Review</h2>
            <div class="taskTableWrap">
                <table class="taskTable">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Task</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?= e($student['full_name']) ?></td>
                                <td><?= e($submission['subject_title']) ?></td>
                                <td class="font-semibold"><?= e($submission['title']) ?></td>
                                <td><?= e(date('M d, Y', strtotime($submission['submitted_at']))) ?></td>
                                <td><span class="badge <?= e(statusClass($submission['submission_status'] === 'approved' ? 'completed' : 'submitted')) ?>"><?= e(readableStatus($submission['submission_status'])) ?></span></td>
                                <td><a href="review_submission.php?id=<?= (int)$submission['submission_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">Review</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($submissions) === 0): ?><tr><td colspan="6" class="text-slate-400">No submissions from this student yet.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <aside class="space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">Academic Context</h2>
            <div class="space-y-3 text-sm">
                <p><span class="text-slate-500">Current term:</span> <?= $student['year_number'] && $student['semester_number'] ? 'Year ' . (int)$student['year_number'] . ' - Semester ' . (int)$student['semester_number'] : 'Not set' ?></p>
                <p><span class="text-slate-500">Subject:</span> <?= e($student['subject_code'] ?: 'Not set') ?> <?= e($student['subject_title'] ?: '') ?></p>
                <p><span class="text-slate-500">Readiness:</span> <?= (int)$student['readiness_score'] ?>%</p>
                <p><span class="text-slate-500">Roadmap progress:</span> <?= (int)$student['roadmap_progress'] ?>%</p>
            </div>
        </div>
    </aside>
</div>

<div id="messageModal" class="hidden fixed inset-0 z-[60] bg-black/70 px-4 py-8">
    <div class="max-w-lg mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-2xl font-bold">Message Student</h2>
            <button type="button" class="secondaryBtn px-3 py-2 closeModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="messageForm" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
            <textarea name="message" class="inputStyle min-h-[150px]" placeholder="Message, note, or feedback for this student" required></textarea>
            <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
        </form>
    </div>
</div>

<div id="assignModal" class="hidden fixed inset-0 z-[60] bg-black/70 px-4 py-8 overflow-y-auto">
    <div class="max-w-2xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-2xl font-bold">Assign Task</h2>
            <button type="button" class="secondaryBtn px-3 py-2 closeModal"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="assignTaskForm" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="student_ids[]" value="<?= (int)$studentId ?>">
            <select name="lesson_bundle" id="lessonBundle" class="inputStyle" required>
                <option value="">Select existing roadmap lesson</option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?= (int)$lesson['path_id'] ?>|<?= (int)$lesson['subject_id'] ?>|<?= (int)$lesson['lesson_id'] ?>">
                        <?= e($lesson['career_title']) ?> - <?= e($lesson['subject_code']) ?> - <?= e($lesson['lesson_title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="path_id" id="pathId">
            <input type="hidden" name="subject_id" id="subjectId">
            <input type="hidden" name="lesson_id" id="lessonId">
            <input name="title" class="inputStyle" placeholder="Task title" required>
            <textarea name="instructions" class="inputStyle min-h-[130px]" placeholder="Instructions" required></textarea>
            <div class="grid sm:grid-cols-2 gap-4">
                <input type="date" name="deadline" class="inputStyle">
                <input type="number" name="points" class="inputStyle" value="100" min="1" max="1000">
            </div>
            <textarea name="resources" class="inputStyle min-h-[90px]" placeholder="Resources or links"></textarea>
            <input type="file" name="attachment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" class="inputStyle text-sm">
            <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-list-check"></i> Assign Task</button>
        </form>
    </div>
</div>

<style>
    .messageAvatar{width:36px;height:36px;border-radius:999px;background:#2563eb;display:flex;align-items:center;justify-content:center;font-weight:800;flex:0 0 auto;}
    .messageAvatar.isMentorOwn{background:#16a34a;}
    .messageBubble{border:1px solid #334155;padding:12px 14px;border-radius:14px;line-height:1.6;}
    .messageBubble.isStudent{background:#020B24;color:#cbd5e1;}
    .messageBubble.isMentorOwn{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.28);color:#dcfce7;}
    .taskTableWrap{max-height:420px;overflow:auto;border:1px solid #334155;border-radius:14px;}
    .taskTable{width:100%;border-collapse:separate;border-spacing:0;}
    .taskTable th{position:sticky;top:0;background:#0f172a;color:#94a3b8;text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.08em;padding:12px 14px;z-index:1;}
    .taskTable td{height:64px;padding:10px 14px;border-top:1px solid #334155;color:#cbd5e1;vertical-align:middle;}
</style>

<script>
const workspaceToast = document.getElementById('workspaceToast');
const threadList = document.getElementById('threadList');

function showWorkspaceToast(message, success = true) {
    workspaceToast.textContent = message;
    workspaceToast.className = `fixed right-4 top-24 z-[70] rounded-xl border px-4 py-3 text-sm shadow-2xl ${success ? 'bg-green-500/10 border-green-500 text-green-200' : 'bg-red-500/10 border-red-500 text-red-200'}`;
    workspaceToast.classList.remove('hidden');
    setTimeout(() => workspaceToast.classList.add('hidden'), 2600);
}

function closeAllModals() {
    document.querySelectorAll('#messageModal, #assignModal').forEach(modal => modal.classList.add('hidden'));
}

document.getElementById('openMessageModal').addEventListener('click', () => document.getElementById('messageModal').classList.remove('hidden'));
document.getElementById('openAssignModal').addEventListener('click', () => document.getElementById('assignModal').classList.remove('hidden'));
document.querySelectorAll('.closeModal').forEach(button => button.addEventListener('click', closeAllModals));

threadList.scrollTop = threadList.scrollHeight;

document.getElementById('lessonBundle').addEventListener('change', (event) => {
    const [pathId, subjectId, lessonId] = event.target.value.split('|');
    document.getElementById('pathId').value = pathId || '';
    document.getElementById('subjectId').value = subjectId || '';
    document.getElementById('lessonId').value = lessonId || '';
});

document.getElementById('assignTaskForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_create_task.php', new FormData(event.currentTarget), true);
    if (result.success) {
        event.currentTarget.reset();
        closeAllModals();
        showWorkspaceToast('Task assigned successfully.');
    } else {
        showWorkspaceToast(result.message || 'Unable to assign task.', false);
    }
});

document.getElementById('messageForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await window.mmfPost('ajax_student_message.php', new FormData(form), true);
    if (!result.success) {
        showWorkspaceToast(result.message || 'Unable to send message.', false);
        return;
    }

    document.getElementById('emptyThread')?.remove();
    const item = document.createElement('div');
    item.className = 'messageRow flex gap-3 justify-end';
    item.innerHTML = `<div class="max-w-[78%]"><div class="flex items-center gap-2 mb-1 justify-end"><span class="text-sm font-bold text-green-200"></span><span class="text-xs text-slate-500"></span></div><div class="messageBubble isMentorOwn"></div></div><div class="messageAvatar isMentorOwn"></div>`;
    item.querySelector('.text-green-200').textContent = result.item.sender;
    item.querySelector('.text-slate-500').textContent = result.item.created_at;
    item.querySelector('.messageBubble').textContent = result.item.message;
    item.querySelector('.messageAvatar').textContent = (result.item.sender || 'M').charAt(0).toUpperCase();
    threadList.appendChild(item);
    form.reset();
    closeAllModals();
    showWorkspaceToast('Message sent.');
    threadList.scrollTop = threadList.scrollHeight;
});
</script>

<?php include '../footer.php'; ?>
