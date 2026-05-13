<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireStudent();

$userId = (int)$_SESSION['user_id'];
$lessonId = (int)($_GET['lesson_id'] ?? 0);
$subjectId = (int)($_GET['subject_id'] ?? 0);

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
    redirect('roadmap.php');
}

if (!validate_csrf($_GET['csrf_token'] ?? '')) {
    redirect('subject.php?id=' . $subjectId);
}

if ((int)$lesson['is_premium'] === 1 && !hasPremiumAccess($conn, $userId)) {
    redirect('subscription.php');
}

markLessonComplete($conn, $userId, $lessonId);

$target = $lesson['lesson_file'] ?: $lesson['content_url'];
$placeholder = '../uploads/lessons/placeholder.pdf';

if (!empty($lesson['lesson_file']) && file_exists(__DIR__ . '/../' . $lesson['lesson_file'])) {
    $target = '../' . $lesson['lesson_file'];
} elseif (!empty($lesson['content_url'])) {
    $target = $lesson['content_url'];
} else {
    $target = $placeholder;
}

redirect($target);
?>
