<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$mentorId = (int)$_SESSION['user_id'];
$studentId = (int)($_POST['student_id'] ?? 0);
$pathId = (int)($_POST['path_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);
$lessonId = (int)($_POST['lesson_id'] ?? 0);
$title = sanitize($_POST['title'] ?? '');
$instructions = sanitize($_POST['instructions'] ?? '');
$resources = sanitize($_POST['resources'] ?? '');
$deadline = sanitize($_POST['deadline'] ?? '');
$points = (int)($_POST['points'] ?? 100);

if ($title === '' || $instructions === '') {
    jsonResponse(['success' => false, 'message' => 'Select an assigned career, subject, lesson, and complete the task.'], 422);
}

$ok = createMentorTask($conn, $mentorId, $studentId, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline, $points);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Mentor task created.' : 'Unable to create task.'], $ok ? 200 : 500);
?>
