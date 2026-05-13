<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

jsonResponse(['success' => true, 'data' => getSalesReportData($conn)]);
?>
