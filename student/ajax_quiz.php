<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$result = completeReadyQuizAttempt($conn, (int)$_SESSION['user_id'], (int)($_POST['attempt_id'] ?? 0));

if (!$result) {
    jsonResponse(['success' => false, 'message' => 'Quiz is no longer available.'], 404);
}

jsonResponse(['success' => true, 'message' => 'Quiz completed.', 'score' => $result['score']]);
?>
