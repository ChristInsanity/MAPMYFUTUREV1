<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$ok = updateJobApplicationStatus(
    $conn,
    (int)$_SESSION['user_id'],
    (int)($_POST['application_id'] ?? 0),
    sanitize($_POST['status'] ?? '')
);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Applicant updated.' : 'Unable to update applicant.'], $ok ? 200 : 422);
?>
