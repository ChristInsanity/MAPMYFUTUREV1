<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$studentId = (int)$_SESSION['user_id'];
$mentorId = (int)($_POST['mentor_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');

$ok = sendStudentMentorQuestion($conn, $studentId, $mentorId, $message);

jsonResponse([
    'success' => $ok,
    'message' => $ok ? 'Question sent.' : 'Unable to send this question.',
    'question' => $ok ? [
        'sender' => $_SESSION['full_name'] ?? 'You',
        'message' => $message,
        'created_at' => date('M d, Y g:i A')
    ] : null
], $ok ? 200 : 422);
?>
