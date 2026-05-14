<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';
require_once '../includes/profile_functions.php';

requireMentor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Invalid request.'], 405);
}
require_csrf();

$mentorId = (int)$_SESSION['user_id'];
[$identityOk, $identityMessage] = updateUserIdentity(
    $conn,
    $mentorId,
    $_POST['full_name'] ?? '',
    $_POST['email'] ?? ''
);
[$photoOk, $photoPath, $photoMessage] = saveProfilePhotoUpload($conn, $mentorId);

$bio = sanitize($_POST['bio'] ?? '');
$experience = sanitize($_POST['experience'] ?? '');
$degree = sanitize($_POST['degree'] ?? '');
$specialization = sanitize($_POST['specialization'] ?? '');
$industry = sanitize($_POST['industry'] ?? '');
$yearsExperience = max(0, (int)($_POST['years_experience'] ?? 0));
$linkedin = sanitize($_POST['linkedin_url'] ?? '');
$github = sanitize($_POST['github_url'] ?? '');
$behance = sanitize($_POST['behance_url'] ?? '');
$portfolio = sanitize($_POST['portfolio_url'] ?? '');

$ok = dbExecute(
    $conn,
    "UPDATE mentor_profiles
     SET bio = ?, experience = ?, degree = ?, specialization = ?, industry = ?, years_experience = ?, linkedin_url = ?, github_url = ?, behance_url = ?, portfolio_url = ?
     WHERE user_id = ?",
    "sssssissssi",
    [$bio, $experience, $degree, $specialization, $industry, $yearsExperience, $linkedin, $github, $behance, $portfolio, $mentorId]
);

$certTitle = sanitize($_POST['certification_title'] ?? '');
if ($certTitle !== '' && !empty($_FILES['certification_file']['name'])) {
    $extension = strtolower(pathinfo($_FILES['certification_file']['name'], PATHINFO_EXTENSION));
    if ($extension === 'pdf') {
        $uploadDir = __DIR__ . '/../uploads/mentor_certifications';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['certification_file']['name'], PATHINFO_FILENAME));
        $filename = $safeName . '-' . $mentorId . '-' . time() . '.pdf';
        if (move_uploaded_file($_FILES['certification_file']['tmp_name'], $uploadDir . '/' . $filename)) {
            dbExecute(
                $conn,
                "INSERT INTO mentor_certifications (user_id, title, file_path) VALUES (?, ?, ?)",
                "iss",
                [$mentorId, $certTitle, 'uploads/mentor_certifications/' . $filename]
            );
        }
    }
}

$success = $identityOk && $photoOk && $ok;
jsonResponse(['success' => $success, 'message' => $success ? 'Profile updated.' : ($photoMessage ?: $identityMessage ?: 'Unable to update profile.')], $success ? 200 : 500);
?>
