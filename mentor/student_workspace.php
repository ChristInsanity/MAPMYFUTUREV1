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
    </div>
</div>

<div id="workspaceMessage" class="hidden mb-6 rounded-2xl border p-4"></div>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="lg:col-span-2 space-y-6">
        <div class="card">
            <h2 class="sectionTitle mb-4">Communication</h2>
            <form id="messageForm" class="space-y-4 mb-5">
                <?= csrf_input() ?>
                <input type="hidden" name="student_id" value="<?= (int)$studentId ?>">
                <textarea name="message" class="inputStyle min-h-[110px]" placeholder="Message, note, or feedback for this student" required></textarea>
                <button class="primaryBtn" type="submit"><i class="fa-solid fa-paper-plane"></i> Send</button>
            </form>
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
                <?php if (count($messages) === 0): ?><p id="emptyThread" class="text-slate-400">No messages yet.</p><?php endif; ?>
            </div>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-4">Assign Task</h2>
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
                <label>
                    <span class="text-slate-400">Attachment</span>
                    <input type="file" name="attachment_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" class="inputStyle mt-2 text-sm">
                </label>
                <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-list-check"></i> Assign Task</button>
            </form>
        </div>

        <div class="card">
            <h2 class="sectionTitle mb-4">Submission Review</h2>
            <div class="space-y-3">
                <?php foreach ($submissions as $submission): ?>
                    <article class="bg-[#020B24] border border-[#334155] rounded-xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold"><?= e($submission['title']) ?></h3>
                            <p class="text-sm text-slate-400"><?= e($submission['subject_title']) ?> - submitted <?= e(date('M d, Y', strtotime($submission['submitted_at']))) ?></p>
                            <?php if ($submission['comment']): ?><p class="text-sm text-blue-200 mt-2"><?= e($submission['comment']) ?></p><?php endif; ?>
                        </div>
                        <a href="review_submission.php?id=<?= (int)$submission['submission_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">Review</a>
                    </article>
                <?php endforeach; ?>
                <?php if (count($submissions) === 0): ?><p class="text-slate-400">No submissions from this student yet.</p><?php endif; ?>
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

        <div class="card">
            <h2 class="sectionTitle mb-4">Task Management</h2>
            <div class="space-y-3">
                <?php foreach ($tasks as $task): ?>
                    <?php $status = $task['submission_status'] ?: 'assigned'; ?>
                    <div class="border-l-2 border-blue-500/40 pl-4">
                        <p class="font-semibold"><?= e($task['title']) ?></p>
                        <p class="text-sm text-slate-400"><?= e(readableStatus($status)) ?><?= $task['deadline'] ? ' - due ' . e(date('M d, Y', strtotime($task['deadline']))) : '' ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (count($tasks) === 0): ?><p class="text-slate-400">No tasks assigned yet.</p><?php endif; ?>
            </div>
        </div>
    </aside>
</div>

<script>
const workspaceMessage = document.getElementById('workspaceMessage');
function setWorkspaceMessage(message, success = true) {
    workspaceMessage.className = `mb-6 rounded-2xl border p-4 ${success ? 'border-green-500 bg-green-500/10 text-green-200' : 'border-red-500 bg-red-500/10 text-red-200'}`;
    workspaceMessage.textContent = message;
    workspaceMessage.classList.remove('hidden');
}

document.getElementById('lessonBundle').addEventListener('change', (event) => {
    const [pathId, subjectId, lessonId] = event.target.value.split('|');
    document.getElementById('pathId').value = pathId || '';
    document.getElementById('subjectId').value = subjectId || '';
    document.getElementById('lessonId').value = lessonId || '';
});

document.getElementById('assignTaskForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_create_task.php', new FormData(event.currentTarget), true);
    setWorkspaceMessage(result.message || (result.success ? 'Task assigned.' : 'Unable to assign task.'), result.success);
    if (result.success) {
        event.currentTarget.reset();
    }
});

document.getElementById('messageForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await window.mmfPost('ajax_student_message.php', new FormData(form), true);
    if (!result.success) {
        setWorkspaceMessage(result.message || 'Unable to send message.', false);
        return;
    }

    document.getElementById('emptyThread')?.remove();
    const item = document.createElement('div');
    item.className = 'bg-[#020B24] border border-[#334155] rounded-xl p-4';
    item.innerHTML = `<div class="flex justify-between gap-3 mb-2 text-sm"><span class="font-bold text-blue-200"></span><span class="text-slate-500"></span></div><p class="text-slate-300"></p>`;
    item.querySelector('span:first-child').textContent = result.item.sender;
    item.querySelector('span:last-child').textContent = result.item.created_at;
    item.querySelector('p').textContent = result.item.message;
    document.getElementById('threadList').appendChild(item);
    form.reset();
});
</script>

<?php include '../footer.php'; ?>
