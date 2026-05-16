<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

if (sanitize($_POST['title'] ?? '') === '' || sanitize($_POST['required_skills'] ?? '') === '') {
    jsonResponse(['success' => false, 'message' => 'Position and required skills are required.'], 422);
}

$ok = createEmployerJob($conn, (int)$_SESSION['user_id'], $_POST);
$message = (int)($_POST['job_id'] ?? 0) > 0 ? 'Job post updated.' : 'Job post created.';
$jobId = (int)($_POST['job_id'] ?? 0);
if ($ok && $jobId <= 0) {
    $jobId = (int)$conn->insert_id;
}

jsonResponse([
    'success' => $ok,
    'message' => $ok ? $message : 'Unable to save job post.',
    'job_id' => $jobId
], $ok ? 200 : 422);
?>
