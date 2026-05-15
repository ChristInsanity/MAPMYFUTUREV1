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
$message = sanitize($_POST['message'] ?? '');

$assignment = dbFetchOne(
    $conn,
    "SELECT assignment_id
     FROM mentor_assignments
     WHERE mentor_id = ? AND student_id = ? AND status = 'active'",
    "ii",
    [$mentorId, $studentId]
);

if (!$assignment || trim($message) === '') {
    jsonResponse(['success' => false, 'message' => 'Unable to send this message.'], 422);
}

$ok = dbExecute(
    $conn,
    "INSERT INTO mentor_messages (assignment_id, sender_id, message) VALUES (?, ?, ?)",
    "iis",
    [(int)$assignment['assignment_id'], $mentorId, $message]
);

jsonResponse([
    'success' => $ok,
    'message' => $ok ? 'Message sent.' : 'Unable to send this message.',
    'item' => $ok ? [
        'sender' => $_SESSION['name'] ?? 'Mentor',
        'message' => $message,
        'created_at' => date('M d, Y g:i A')
    ] : null
], $ok ? 200 : 422);
?>
