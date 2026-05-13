<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$status = sanitize($_POST['status'] ?? '');
$requestId = (int)($_POST['request_id'] ?? 0);
$ok = respondMentorStudentRequest($conn, (int)$_SESSION['user_id'], $requestId, $status);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Request updated.' : 'Unable to update request.'], $ok ? 200 : 422);
?>
