<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$studentId = (int)($_POST['student_id'] ?? 0);
$message = sanitize($_POST['message'] ?? 'We would like to invite you to connect with our hiring team.');
$ok = inviteEmployerApplicant($conn, (int)$_SESSION['user_id'], $studentId, $message);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Invite sent.' : 'Unable to invite this applicant.'], $ok ? 200 : 422);
?>
