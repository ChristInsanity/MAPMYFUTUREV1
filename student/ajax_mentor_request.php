<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}

$userId = (int)$_SESSION['user_id'];

if (!hasPremiumAccess($conn, $userId)) {
    jsonResponse(['success' => false, 'requires_premium' => true, 'message' => 'Premium is required to enroll with a mentor.'], 403);
}

$ok = requestMentorEnrollment($conn, $userId, (int)($_POST['mentor_id'] ?? 0));
jsonResponse(['success' => $ok, 'message' => $ok ? 'Enrollment request sent.' : 'Unable to request this mentor.'], $ok ? 200 : 422);
?>
