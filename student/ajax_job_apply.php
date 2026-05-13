<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$studentId = (int)$_SESSION['user_id'];
$jobId = (int)($_POST['job_id'] ?? 0);
$coverText = sanitize($_POST['cover_letter'] ?? '');
$uploadDir = __DIR__ . '/../uploads/job_applications';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$saveFile = function ($field) use ($uploadDir, $studentId) {
    if (!isset($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return null;
    }

    $extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'doc', 'docx'], true)) {
        jsonResponse(['success' => false, 'message' => 'Uploads must be PDF, DOC, or DOCX.'], 422);
    }

    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
    $filename = $field . '-' . $safeName . '-' . $studentId . '-' . time() . '.' . $extension;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $uploadDir . '/' . $filename)) {
        jsonResponse(['success' => false, 'message' => 'Upload failed.'], 500);
    }

    return 'uploads/job_applications/' . $filename;
};

$resumePath = $saveFile('resume');
if (!$resumePath) {
    jsonResponse(['success' => false, 'message' => 'Resume is required.'], 422);
}

$coverPath = $saveFile('cover_letter_file');
$ok = createJobApplication($conn, $studentId, $jobId, $resumePath, $coverPath, $coverText);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Application Pending.' : 'Unable to submit application.'], $ok ? 200 : 422);
?>
