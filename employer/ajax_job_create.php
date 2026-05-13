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
jsonResponse(['success' => $ok, 'message' => $ok ? 'Job post created.' : 'Unable to create job post.'], $ok ? 200 : 422);
?>
