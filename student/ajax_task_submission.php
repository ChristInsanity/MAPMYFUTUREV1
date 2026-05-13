<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$studentId = (int)$_SESSION['user_id'];
$taskId = (int)($_POST['mentor_task_id'] ?? 0);
$notes = sanitize($_POST['notes'] ?? '');
$link = sanitize($_POST['submission_link'] ?? '');
$filePath = null;

if (isset($_FILES['submission_file']) && is_uploaded_file($_FILES['submission_file']['tmp_name'])) {
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $extension = strtolower(pathinfo($_FILES['submission_file']['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed, true)) {
        jsonResponse(['success' => false, 'message' => 'Upload must be PDF, DOC, DOCX, JPG, or PNG.'], 422);
    }

    $uploadDir = __DIR__ . '/../uploads/submissions';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['submission_file']['name'], PATHINFO_FILENAME));
    $filename = $safeName . '-' . $studentId . '-' . time() . '.' . $extension;

    if (!move_uploaded_file($_FILES['submission_file']['tmp_name'], $uploadDir . '/' . $filename)) {
        jsonResponse(['success' => false, 'message' => 'Upload failed. Please try again.'], 500);
    }

    $filePath = 'uploads/submissions/' . $filename;
}

if ($filePath === null && $link === '') {
    jsonResponse(['success' => false, 'message' => 'Upload a file or provide a link.'], 422);
}

$ok = saveMentorTaskSubmission($conn, $studentId, $taskId, $filePath, $link, $notes);

jsonResponse([
    'success' => $ok,
    'message' => $ok ? 'Submission uploaded.' : 'Unable to submit this task.',
    'status' => 'submitted'
], $ok ? 200 : 422);
?>
