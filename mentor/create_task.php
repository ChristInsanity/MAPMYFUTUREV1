<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
$preselectedStudentId = (int)($_GET['student_id'] ?? 0);
$paths = getMentorCareerAssignments($conn, $mentorId);
$students = array_values(array_filter(getMentorStudentsOverview($conn, $mentorId), fn($student) => $student['status'] === 'active'));
$subjects = dbFetchAll(
    $conn,
    "SELECT cs.subject_id, cs.subject_title, cs.subject_code, cy.path_id
     FROM career_subjects cs
     JOIN career_semesters csem ON csem.semester_id = cs.semester_id
     JOIN career_years cy ON cy.year_id = csem.year_id
     ORDER BY cs.subject_title"
);
$lessons = dbFetchAll(
    $conn,
    "SELECT ml.lesson_id, ml.title, sm.subject_id
     FROM module_lessons ml
     JOIN subject_modules sm ON sm.module_id = ml.module_id
     ORDER BY ml.title"
);

$pageTitle = 'Create Mentor Task';
$activePage = 'tasks';
$backUrl = 'dashboard.php';
$backLabel = 'Back to Dashboard';
include '../header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Create Mentor Task</h1>
</div>

<div id="taskMessage" class="hidden mb-6 rounded-2xl border p-4"></div>

<form id="mentorTaskForm" method="POST" class="card space-y-5">
    <?= csrf_input() ?>
    <input type="hidden" name="student_id" value="0">
    <div class="grid lg:grid-cols-3 gap-4">
        <label>
            <span class="text-slate-400">Career path</span>
            <select name="path_id" id="pathSelect" class="inputStyle mt-2" required>
                <option value="">Select career path</option>
                <?php foreach ($paths as $path): ?>
                    <option value="<?= (int)$path['career_path_id'] ?>"><?= e($path['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="text-slate-400">Subject</span>
            <select name="subject_id" id="subjectSelect" class="inputStyle mt-2" required>
                <option value="">Select subject</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= (int)$subject['subject_id'] ?>" data-path="<?= (int)$subject['path_id'] ?>"><?= e($subject['subject_code']) ?> - <?= e($subject['subject_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span class="text-slate-400">Existing lesson</span>
            <select name="lesson_id" id="lessonSelect" class="inputStyle mt-2" required>
                <option value="">Select lesson</option>
                <?php foreach ($lessons as $lesson): ?>
                    <option value="<?= (int)$lesson['lesson_id'] ?>" data-subject="<?= (int)$lesson['subject_id'] ?>"><?= e($lesson['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div>
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-3">
            <div>
                <span class="text-slate-400">Students</span>
                <p class="text-sm text-slate-500">Leave every student unchecked to assign to all active students in the selected subject.</p>
            </div>
            <button type="button" id="clearStudents" class="secondaryBtn px-3 py-2 text-sm">Clear Selection</button>
        </div>
        <div id="studentChecklist" class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
            <?php foreach ($students as $student): ?>
                <label class="studentChoice bg-[#020B24] border border-[#334155] rounded-xl p-4 flex items-start gap-3" data-subject="<?= (int)$student['subject_id'] ?>">
                    <input type="checkbox" name="student_ids[]" value="<?= (int)$student['student_id'] ?>" class="mt-1" <?= $preselectedStudentId === (int)$student['student_id'] ? 'checked' : '' ?>>
                    <span>
                        <span class="block font-semibold"><?= e($student['full_name']) ?></span>
                        <span class="block text-sm text-slate-400"><?= $student['year_number'] && $student['semester_number'] ? 'Year ' . (int)$student['year_number'] . ' - Semester ' . (int)$student['semester_number'] : 'Year/semester not set' ?></span>
                        <span class="block text-xs text-blue-200"><?= e($student['subject_code'] ?: 'Current subject') ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
            <?php if (count($students) === 0): ?><p class="text-slate-400">No active students available.</p><?php endif; ?>
        </div>
    </div>

    <input name="title" class="inputStyle" placeholder="Task title" required>
    <textarea name="instructions" class="inputStyle min-h-[160px]" placeholder="Instructions" required></textarea>
    <textarea name="resources" class="inputStyle min-h-[120px]" placeholder="Resources, links, or reading notes"></textarea>

    <div class="grid sm:grid-cols-2 gap-4">
        <input type="date" name="deadline" class="inputStyle">
        <input type="number" name="points" min="1" max="1000" value="100" class="inputStyle" placeholder="Points">
    </div>

    <button class="primaryBtn" type="submit"><i class="fa-solid fa-plus"></i> Create Task</button>
</form>

<script>
const pathSelect = document.getElementById('pathSelect');
const subjectSelect = document.getElementById('subjectSelect');
const lessonSelect = document.getElementById('lessonSelect');
const studentChoices = Array.from(document.querySelectorAll('.studentChoice'));

function filterSubjects() {
    const path = pathSelect.value;
    subjectSelect.querySelectorAll('option[data-path]').forEach(option => {
        option.hidden = Boolean(path && option.dataset.path !== path);
    });
    if (subjectSelect.selectedOptions[0]?.hidden) {
        subjectSelect.value = '';
    }
    filterLessons();
    filterStudents();
}

function filterLessons() {
    const subject = subjectSelect.value;
    lessonSelect.querySelectorAll('option[data-subject]').forEach(option => {
        option.hidden = subject && option.dataset.subject !== subject;
    });
    if (lessonSelect.selectedOptions[0]?.hidden) {
        lessonSelect.value = '';
    }
}

function filterStudents() {
    const subject = subjectSelect.value;
    studentChoices.forEach(choice => {
        const hidden = Boolean(subject && choice.dataset.subject !== subject);
        choice.classList.toggle('hidden', hidden);
        if (hidden) {
            choice.querySelector('input').checked = false;
        }
    });
}

pathSelect.addEventListener('change', filterSubjects);
subjectSelect.addEventListener('change', () => {
    filterLessons();
    filterStudents();
});
document.getElementById('clearStudents').addEventListener('click', () => {
    studentChoices.forEach(choice => choice.querySelector('input').checked = false);
});
filterSubjects();

document.getElementById('mentorTaskForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const result = await window.mmfPost('ajax_create_task.php', new FormData(form), true);
    const message = document.getElementById('taskMessage');
    message.className = `mb-6 rounded-2xl border p-4 ${result.success ? 'border-green-500 bg-green-500/10 text-green-200' : 'border-red-500 bg-red-500/10 text-red-200'}`;
    message.textContent = result.message;
    message.classList.remove('hidden');
    if (result.success) {
        form.reset();
        filterSubjects();
    }
});
</script>

<?php include '../footer.php'; ?>
