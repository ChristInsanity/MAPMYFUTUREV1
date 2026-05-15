<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();
ensureMentorTables($conn);

$mentorId = (int)$_SESSION['user_id'];
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
    <div class="grid lg:grid-cols-4 gap-4">
        <label>
            <span class="text-slate-400">Student</span>
            <select name="student_id" id="studentSelect" class="inputStyle mt-2">
                <option value="0" data-subject="">All active students</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= (int)$student['student_id'] ?>" data-subject="<?= (int)$student['subject_id'] ?>">
                        <?= e($student['full_name']) ?><?= $student['year_number'] && $student['semester_number'] ? ' - Y' . (int)$student['year_number'] . ' S' . (int)$student['semester_number'] : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
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
const studentSelect = document.getElementById('studentSelect');
const subjectSelect = document.getElementById('subjectSelect');
const lessonSelect = document.getElementById('lessonSelect');

function filterSubjects() {
    const path = pathSelect.value;
    const studentSubject = studentSelect.value !== '0'
        ? studentSelect.selectedOptions[0]?.dataset.subject
        : '';
    subjectSelect.querySelectorAll('option[data-path]').forEach(option => {
        option.hidden = Boolean((path && option.dataset.path !== path) || (studentSubject && option.value !== studentSubject));
    });
    if (subjectSelect.selectedOptions[0]?.hidden) {
        subjectSelect.value = '';
    }
    filterLessons();
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

pathSelect.addEventListener('change', filterSubjects);
studentSelect.addEventListener('change', filterSubjects);
subjectSelect.addEventListener('change', filterLessons);
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
