<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();
ensureMentorTables($conn);

$userId = (int)$_SESSION['user_id'];
$taskId = (int)($_GET['id'] ?? $_POST['mentor_task_id'] ?? 0);
$task = dbFetchOne(
    $conn,
    "SELECT mt.*, u.full_name AS mentor_name, cs.subject_title, ml.title AS lesson_title
     FROM mentor_tasks mt
     JOIN mentor_students ms ON ms.mentor_id = mt.mentor_id AND ms.subject_id = mt.subject_id AND ms.student_id = ? AND ms.status = 'active'
     JOIN users u ON u.user_id = mt.mentor_id
     JOIN career_subjects cs ON cs.subject_id = mt.subject_id
     JOIN module_lessons ml ON ml.lesson_id = mt.lesson_id
     WHERE mt.mentor_task_id = ?",
    "ii",
    [$userId, $taskId]
);

if (!$task) {
    redirect('mentor_tasks.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $link = sanitize($_POST['submission_link'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $filePath = null;

    if (isset($_FILES['submission_file']) && !empty($_FILES['submission_file']['name'])) {
        $allowed = ['pdf', 'zip', 'docx'];
        $extension = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            $error = 'Upload must be PDF, ZIP, or DOCX.';
        } else {
            $uploadDir = __DIR__ . '/../uploads/submissions';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['submission_file']['name'], PATHINFO_FILENAME));
            $filename = $safeName . '-' . $userId . '-' . time() . '.' . $extension;

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                $filePath = 'uploads/submissions/' . $filename;
            }
        }
    }

    if (!$error && $filePath === null && $link === '') {
        $error = 'Upload a file or provide a link.';
    }

    if (!$error && saveMentorTaskSubmission($conn, $userId, $taskId, $filePath, $link, $notes)) {
        redirect('mentor_tasks.php');
    }
}

$pageTitle = 'Submit Mentor Task';
$activePage = 'mentor_tasks';
$backUrl = 'mentor_tasks.php';
$backLabel = 'Back to Tasks';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Mentor Tasks', 'url' => 'mentor_tasks.php'],
    ['label' => $task['title']]
];
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2"><?= e($task['subject_title']) ?> - <?= e($task['lesson_title']) ?></p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2"><?= e($task['title']) ?></h1>
    <p class="text-slate-400">Submit PDF, ZIP, DOCX, or a link for mentor review.</p>
</div>

<?php if ($error): ?><div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-red-200"><?= e($error) ?></div><?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">
    <section class="card lg:col-span-2">
        <h2 class="sectionTitle mb-4">Instructions</h2>
        <p class="text-slate-300 leading-7 mb-4"><?= e($task['instructions']) ?></p>
        <?php if ($task['resources']): ?>
            <div class="bg-[#020B24] border border-[#334155] rounded-xl p-4 text-slate-300"><?= nl2br(e($task['resources'])) ?></div>
        <?php endif; ?>
    </section>
    <form method="POST" enctype="multipart/form-data" class="card space-y-4">
        <?= csrf_input() ?>
        <input type="hidden" name="mentor_task_id" value="<?= (int)$taskId ?>">
        <label>
            <span class="text-slate-400">Upload file</span>
            <input type="file" name="submission_file" accept=".pdf,.zip,.docx" class="mt-2 w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2">
        </label>
        <input type="url" name="submission_link" class="inputStyle" placeholder="https://link-to-work.example">
        <textarea name="notes" class="inputStyle min-h-[120px]" placeholder="Notes for your mentor"></textarea>
        <button class="primaryBtn w-full" type="submit"><i class="fa-solid fa-upload"></i> Submit</button>
    </form>
</div>

<?php include '../footer.php'; ?>
