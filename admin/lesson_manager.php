<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();
ensureModuleLessonsSchema($conn);

$message = '';
$error = '';

$modules = dbFetchAll(
    $conn,
    "SELECT sm.module_id, sm.title AS module_title, cs.subject_title
     FROM subject_modules sm
     JOIN career_subjects cs ON cs.subject_id = sm.subject_id
     ORDER BY cs.subject_title, sm.module_order",
    ""
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $contentType = sanitize($_POST['content_type'] ?? 'pdf');
    $contentUrl = sanitize($_POST['content_url'] ?? '');
    $isPremium = isset($_POST['is_premium']) ? 1 : 0;
    $lessonFile = null;

    if ($contentType === 'pdf' && isset($_FILES['lesson_file']) && !empty($_FILES['lesson_file']['name'])) {
        $uploadDir = __DIR__ . '/../uploads/lessons';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo($_FILES['lesson_file']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $error = 'Only PDF uploads are allowed for lesson files.';
        } else {
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['lesson_file']['name'], PATHINFO_FILENAME));
            $filename = $safeName . '-' . time() . '.' . $extension;
            $targetPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($_FILES['lesson_file']['tmp_name'], $targetPath)) {
                $lessonFile = 'uploads/lessons/' . $filename;
            } else {
                $error = 'Unable to save the uploaded PDF. Please try again.';
            }
        }
    }

    if (!$error) {
        if ($moduleId === 0 || $title === '') {
            $error = 'Please select a module and add a lesson title.';
        }
    }

    if (!$error) {
        if ($contentType === 'pdf' && !$lessonFile && $contentUrl === '') {
            $error = 'Please upload a PDF file or provide a lesson URL.';
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO module_lessons (module_id, title, content_type, content_url, lesson_file, is_premium, lesson_order) VALUES (?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(lesson_order), 0) + 1 FROM module_lessons WHERE module_id = ?))");
        $stmt->bind_param('issssii', $moduleId, $title, $contentType, $contentUrl, $lessonFile, $isPremium, $moduleId);
        if ($stmt->execute()) {
            $message = 'Lesson uploaded successfully.';
        } else {
            $error = 'Unable to save lesson. Please try again.';
        }
    }
}

$pageTitle = 'Lesson Manager';
$activePage = 'lessons';
include '../header.php';
?>

<div class="mb-8">
    <p class="text-blue-300 font-semibold mb-2">Lesson Management</p>
    <h1 class="text-3xl lg:text-4xl font-bold mb-2">Upload and organize lesson resources</h1>
    <p class="text-slate-400">Add PDF lessons, mark premium content, and keep your curriculum current.</p>
</div>

<?php if ($error): ?>
    <div class="mb-6 rounded-2xl border border-red-500 bg-red-500/10 p-4 text-red-200"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($message): ?>
    <div class="mb-6 rounded-2xl border border-green-500 bg-green-500/10 p-4 text-green-200"><?= e($message) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6 bg-[#162338] border border-[#334155] rounded-3xl p-6">
    <?= csrf_input() ?>
    <div class="grid sm:grid-cols-2 gap-4">
        <label class="block">
            <span class="text-slate-400">Module</span>
            <select name="module_id" class="inputStyle mt-2 w-full bg-slate-900 border border-slate-700 rounded-2xl p-3" required>
                <option value="">Select a module</option>
                <?php foreach ($modules as $module): ?>
                    <option value="<?= (int)$module['module_id'] ?>"><?= e($module['subject_title']) ?> — <?= e($module['module_title']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="block">
            <span class="text-slate-400">Lesson Title</span>
            <input type="text" name="title" class="inputStyle mt-2 w-full bg-slate-900 border border-slate-700 rounded-2xl p-3" placeholder="Lesson name" required>
        </label>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <label class="block">
            <span class="text-slate-400">Content type</span>
            <select name="content_type" class="inputStyle mt-2 w-full bg-slate-900 border border-slate-700 rounded-2xl p-3">
                <option value="pdf">PDF</option>
                <option value="video">Video</option>
                <option value="article">Article</option>
            </select>
        </label>

        <label class="block">
            <span class="text-slate-400">Premium lesson</span>
            <div class="mt-2 flex items-center gap-3">
                <input type="checkbox" name="is_premium" id="is_premium" class="h-5 w-5 rounded text-blue-500 focus:ring-blue-500 bg-slate-800 border-slate-700">
                <label for="is_premium" class="text-slate-200">Premium access required</label>
            </div>
        </label>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <label class="block">
            <span class="text-slate-400">PDF upload</span>
            <input type="file" name="lesson_file" accept=".pdf" class="mt-2 w-full text-sm text-slate-200 file:bg-slate-800 file:border file:border-slate-700 file:rounded-xl file:px-4 file:py-2" />
            <p class="text-slate-500 text-sm mt-2">Upload a PDF or provide a URL below.</p>
        </label>

        <label class="block">
            <span class="text-slate-400">External URL</span>
            <input type="url" name="content_url" class="inputStyle mt-2 w-full bg-slate-900 border border-slate-700 rounded-2xl p-3" placeholder="https://example.com/lesson.pdf">
        </label>
    </div>

    <button class="w-full bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-2xl font-semibold">Save Lesson</button>
</form>

<?php include '../footer.php'; ?>
