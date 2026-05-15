<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$students = getMentorStudentsOverview($conn, $mentorId);
$pendingRequests = array_values(array_filter(getMentorIncomingRequests($conn, $mentorId), fn($request) => $request['status'] === 'pending'));
$lessons = getMentorAssignableLessons($conn, $mentorId);

$activeStudents = array_values(array_filter($students, fn($student) => $student['status'] === 'active'));
$completedStudents = array_values(array_filter($students, fn($student) => $student['status'] === 'completed'));

$pageTitle = 'Mentor Students';
$activePage = 'students';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Students']
];
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Mentor Students</h1>
</div>

<div class="flex flex-wrap gap-3 mb-6">
    <button class="tabBtn primaryBtn" data-tab="active">Active Students</button>
    <button class="tabBtn secondaryBtn" data-tab="pending">Pending Requests</button>
    <button class="tabBtn secondaryBtn" data-tab="completed">Completed Students</button>
</div>

<section id="activeTab" class="tabPanel grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($activeStudents as $student): ?>
        <article class="card">
            <div class="flex justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xl font-bold"><?= e($student['full_name']) ?></h2>
                    <p class="text-slate-400"><?= e($student['career_path'] ?: 'Career not set') ?></p>
                    <p class="text-sm text-blue-200 mt-1">
                        <?= $student['year_number'] && $student['semester_number'] ? 'Year ' . (int)$student['year_number'] . ' - Semester ' . (int)$student['semester_number'] : 'Year/semester not set' ?>
                    </p>
                </div>
                <span class="badge text-blue-300 border-blue-500/30 bg-blue-500/10"><?= (int)$student['readiness_score'] ?>%</span>
            </div>
            <div class="bg-slate-950 h-2 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-blue-500" style="width:<?= (int)$student['readiness_score'] ?>%"></div>
            </div>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-3 mb-4">
                <div class="flex justify-between gap-3 text-sm mb-2">
                    <span class="text-slate-400"><?= e($student['subject_code'] ?: 'Current subject') ?></span>
                    <span class="font-bold text-blue-200"><?= (int)$student['roadmap_progress'] ?>%</span>
                </div>
                <div class="bg-slate-900 h-2 rounded-full overflow-hidden">
                    <div class="h-full bg-green-500" style="width:<?= (int)$student['roadmap_progress'] ?>%"></div>
                </div>
                <p class="text-xs text-slate-500 mt-2"><?= e($student['subject_title'] ?: 'Roadmap progress') ?></p>
            </div>
            <p class="text-slate-500 text-sm mb-4">Latest activity: <?= $student['latest_activity'] ? e(date('M d, Y', strtotime($student['latest_activity']))) : 'No activity yet' ?></p>
            <div class="grid grid-cols-3 gap-2">
                <a href="review_submission.php?student_id=<?= (int)$student['student_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">Open</a>
                <button type="button" class="primaryBtn px-3 py-2 text-sm assignBtn" data-student-id="<?= (int)$student['student_id'] ?>" data-student-name="<?= e($student['full_name']) ?>" data-subject-id="<?= (int)$student['subject_id'] ?>">Assign</button>
                <a href="review_submission.php?student_id=<?= (int)$student['student_id'] ?>" class="secondaryBtn px-3 py-2 text-sm">Review</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($activeStudents) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No active students yet.</div><?php endif; ?>
</section>

<section id="pendingTab" class="tabPanel hidden grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($pendingRequests as $request): ?>
        <article class="card">
            <h2 class="text-xl font-bold mb-1"><?= e($request['full_name']) ?></h2>
            <p class="text-slate-400 mb-4"><?= e($request['career_path'] ?: 'Career not set') ?></p>
            <div class="flex gap-2">
                <button class="primaryBtn requestAction" data-id="<?= (int)$request['request_id'] ?>" data-action="accepted">Accept</button>
                <button class="dangerBtn requestAction" data-id="<?= (int)$request['request_id'] ?>" data-action="rejected">Reject</button>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (count($pendingRequests) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No pending requests.</div><?php endif; ?>
</section>

<section id="completedTab" class="tabPanel hidden grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($completedStudents as $student): ?>
        <article class="card">
            <h2 class="text-xl font-bold mb-1"><?= e($student['full_name']) ?></h2>
            <p class="text-slate-400 mb-4"><?= e($student['career_path'] ?: 'Career not set') ?></p>
            <span class="badge text-green-300 border-green-500/30 bg-green-500/10">Completed</span>
        </article>
    <?php endforeach; ?>
    <?php if (count($completedStudents) === 0): ?><div class="card md:col-span-2 xl:col-span-3 text-slate-400">No completed students yet.</div><?php endif; ?>
</section>

<div id="assignModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8">
    <div class="max-w-2xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
        <div class="flex justify-between items-start gap-4 mb-5">
            <div>
                <h2 class="text-2xl font-bold">Assign Task</h2>
                <p id="assignStudentName" class="text-slate-400"></p>
            </div>
            <button type="button" id="closeAssign" class="secondaryBtn px-3 py-2"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="assignTaskForm" class="space-y-4">
            <?= csrf_input() ?>
            <input type="hidden" name="student_id" id="assignStudentId">
            <select name="lesson_bundle" id="lessonBundle" class="inputStyle" required>
                <option value="">Select existing roadmap lesson</option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?= (int)$lesson['path_id'] ?>|<?= (int)$lesson['subject_id'] ?>|<?= (int)$lesson['lesson_id'] ?>" data-student-filter="<?= (int)$lesson['subject_id'] ?>">
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
            <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-list-check"></i> Assign Task</button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.tabBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('.tabBtn').forEach(item => item.className = 'tabBtn secondaryBtn');
        button.className = 'tabBtn primaryBtn';
        document.querySelectorAll('.tabPanel').forEach(panel => panel.classList.add('hidden'));
        document.getElementById(`${button.dataset.tab}Tab`).classList.remove('hidden');
    });
});

const assignModal = document.getElementById('assignModal');
const lessonBundle = document.getElementById('lessonBundle');
document.querySelectorAll('.assignBtn').forEach(button => {
    button.addEventListener('click', () => {
        document.getElementById('assignStudentId').value = button.dataset.studentId;
        document.getElementById('assignStudentName').textContent = button.dataset.studentName;
        lessonBundle.value = '';
        document.getElementById('pathId').value = '';
        document.getElementById('subjectId').value = '';
        document.getElementById('lessonId').value = '';
        lessonBundle.querySelectorAll('option[data-student-filter]').forEach(option => {
            option.hidden = button.dataset.subjectId && option.dataset.studentFilter !== button.dataset.subjectId;
        });
        assignModal.classList.remove('hidden');
    });
});
document.getElementById('closeAssign').addEventListener('click', () => assignModal.classList.add('hidden'));
lessonBundle.addEventListener('change', (event) => {
    const [pathId, subjectId, lessonId] = event.target.value.split('|');
    document.getElementById('pathId').value = pathId || '';
    document.getElementById('subjectId').value = subjectId || '';
    document.getElementById('lessonId').value = lessonId || '';
});

document.getElementById('assignTaskForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const result = await window.mmfPost('ajax_create_task.php', new FormData(event.currentTarget), true);

    if (result.success) {
        alert('Task assigned.');
        assignModal.classList.add('hidden');
        event.currentTarget.reset();
    } else {
        alert(result.message || 'Unable to assign task.');
    }
});

document.querySelectorAll('.requestAction').forEach(button => {
    button.addEventListener('click', async () => {
        const result = await window.mmfPost('ajax_enrollment.php', {request_id: button.dataset.id, status: button.dataset.action});
        if (result.success) {
            button.closest('article').remove();
        } else {
            alert(result.message || 'Unable to update request.');
        }
    });
});
</script>

<?php include '../footer.php'; ?>
