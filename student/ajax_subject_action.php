<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$userId = (int)$_SESSION['user_id'];
$subjectId = (int)($_POST['subject_id'] ?? 0);
$action = sanitize($_POST['action'] ?? '');
$ok = false;

if ($action === 'start_subject') {
    startSubject($conn, $userId, $subjectId);
    $ok = true;
} elseif ($action === 'complete_task') {
    $ok = completeModuleTask($conn, $userId, (int)($_POST['task_id'] ?? 0));
}

jsonResponse(['success' => $ok, 'message' => $ok ? 'Updated.' : 'Unable to update this subject.'], $ok ? 200 : 422);
?>
