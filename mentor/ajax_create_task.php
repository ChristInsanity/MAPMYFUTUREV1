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
$studentIds = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['student_ids'] ?? [])))));
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

if ($studentId > 0 && count($studentIds) === 0) {
    $studentIds[] = $studentId;
}

if (count($studentIds) === 0) {
    $ok = createMentorTask($conn, $mentorId, 0, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline, $points);
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Mentor task created.' : 'Unable to create task.'], $ok ? 200 : 500);
}

$created = 0;
foreach ($studentIds as $assignedStudentId) {
    if (createMentorTask($conn, $mentorId, $assignedStudentId, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline, $points)) {
        $created++;
    }
}

$ok = $created === count($studentIds);
jsonResponse([
    'success' => $ok,
    'message' => $ok ? "Mentor task assigned to {$created} student" . ($created === 1 ? '.' : 's.') : 'Unable to assign the task to every selected student.'
], $ok ? 200 : 500);
?>
