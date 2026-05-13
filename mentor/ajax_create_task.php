<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}

$mentorId = (int)$_SESSION['user_id'];
$pathId = (int)($_POST['path_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);
$lessonId = (int)($_POST['lesson_id'] ?? 0);
$title = sanitize($_POST['title'] ?? '');
$instructions = sanitize($_POST['instructions'] ?? '');
$resources = sanitize($_POST['resources'] ?? '');
$deadline = sanitize($_POST['deadline'] ?? '');
$points = (int)($_POST['points'] ?? 100);

$allowed = dbFetchOne(
    $conn,
    "SELECT cs.subject_id
     FROM mentor_career_assignments mca
     JOIN career_years cy ON cy.path_id = mca.career_path_id
     JOIN career_semesters csem ON csem.year_id = cy.year_id
     JOIN career_subjects cs ON cs.semester_id = csem.semester_id
     JOIN subject_modules sm ON sm.subject_id = cs.subject_id
     JOIN module_lessons ml ON ml.module_id = sm.module_id
     WHERE mca.mentor_id = ? AND mca.career_path_id = ? AND cs.subject_id = ? AND ml.lesson_id = ?",
    "iiii",
    [$mentorId, $pathId, $subjectId, $lessonId]
);

if (!$allowed || $title === '' || $instructions === '') {
    jsonResponse(['success' => false, 'message' => 'Select an assigned career, subject, lesson, and complete the task.'], 422);
}

$ok = dbExecute(
    $conn,
    "INSERT INTO mentor_tasks (mentor_id, path_id, subject_id, lesson_id, title, instructions, resources, deadline, points)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    "iiiissssi",
    [$mentorId, $pathId, $subjectId, $lessonId, $title, $instructions, $resources, $deadline ?: null, $points]
);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Mentor task created.' : 'Unable to create task.'], $ok ? 200 : 500);
?>
