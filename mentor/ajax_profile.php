<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireMentor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$mentorId = (int)$_SESSION['user_id'];
$bio = sanitize($_POST['bio'] ?? '');
$experience = sanitize($_POST['experience'] ?? '');
$degree = sanitize($_POST['degree'] ?? '');
$linkedin = sanitize($_POST['linkedin_url'] ?? '');
$github = sanitize($_POST['github_url'] ?? '');
$behance = sanitize($_POST['behance_url'] ?? '');
$portfolio = sanitize($_POST['portfolio_url'] ?? '');

$ok = dbExecute(
    $conn,
    "UPDATE mentor_profiles
     SET bio = ?, experience = ?, degree = ?, linkedin_url = ?, github_url = ?, behance_url = ?, portfolio_url = ?
     WHERE user_id = ?",
    "sssssssi",
    [$bio, $experience, $degree, $linkedin, $github, $behance, $portfolio, $mentorId]
);

jsonResponse(['success' => $ok, 'message' => $ok ? 'Profile updated.' : 'Unable to update profile.'], $ok ? 200 : 500);
?>
