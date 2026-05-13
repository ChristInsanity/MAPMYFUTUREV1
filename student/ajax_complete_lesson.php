<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}

$userId = (int)$_SESSION['user_id'];
$lessonId = (int)($_POST['lesson_id'] ?? 0);
$subjectId = (int)($_POST['subject_id'] ?? 0);

$lesson = dbFetchOne(
    $conn,
    "SELECT ml.*, sm.subject_id, ss.status AS subject_status
     FROM module_lessons ml
     JOIN subject_modules sm ON sm.module_id = ml.module_id
     JOIN student_subjects ss ON ss.subject_id = sm.subject_id AND ss.user_id = ?
     WHERE ml.lesson_id = ? AND sm.subject_id = ?",
    "iii",
    [$userId, $lessonId, $subjectId]
);

if (!$lesson || $lesson['subject_status'] === 'locked') {
    jsonResponse(['success' => false, 'message' => 'Lesson is not available.'], 404);
}

if ((int)$lesson['is_premium'] === 1 && !hasPremiumAccess($conn, $userId)) {
    jsonResponse(['success' => false, 'requires_premium' => true, 'message' => 'Premium is required for this lesson.'], 403);
}

markLessonComplete($conn, $userId, $lessonId);

if (!empty($lesson['lesson_file']) && file_exists(__DIR__ . '/../' . $lesson['lesson_file'])) {
    $target = '../' . $lesson['lesson_file'];
} elseif (!empty($lesson['content_url'])) {
    $target = $lesson['content_url'];
} else {
    $target = '../uploads/lessons/placeholder.pdf';
}

jsonResponse([
    'success' => true,
    'message' => 'Lesson completed. Assessment unlocked.',
    'pdf_url' => $target
]);
?>
