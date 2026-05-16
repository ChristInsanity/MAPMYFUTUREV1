<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireEmployer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$employerId = (int)$_SESSION['user_id'];
$jobId = (int)($_POST['job_id'] ?? 0);
$action = sanitize($_POST['action'] ?? '');

if ($jobId <= 0) {
    jsonResponse(['success' => false, 'message' => 'Job post was not found.'], 422);
}

if ($action === 'duplicate') {
    $ok = duplicateEmployerJob($conn, $employerId, $jobId);
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Draft duplicate created.' : 'Unable to duplicate job post.'], $ok ? 200 : 422);
}

$statusMap = [
    'open' => 'open',
    'restore' => 'open',
    'close' => 'closed',
    'archive' => 'archived',
    'draft' => 'draft'
];

if (!isset($statusMap[$action])) {
    jsonResponse(['success' => false, 'message' => 'Unsupported job action.'], 422);
}

$ok = updateEmployerJobStatus($conn, $employerId, $jobId, $statusMap[$action]);
jsonResponse(['success' => $ok, 'message' => $ok ? 'Job post updated.' : 'Unable to update job post.'], $ok ? 200 : 422);
?>
