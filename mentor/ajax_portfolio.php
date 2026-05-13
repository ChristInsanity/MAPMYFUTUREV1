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
$linkedin = sanitize($_POST['linkedin_url'] ?? '');
$github = sanitize($_POST['github_url'] ?? '');
$portfolio = sanitize($_POST['portfolio_url'] ?? '');

dbExecute(
    $conn,
    "UPDATE mentor_profiles SET bio = ?, linkedin_url = ?, github_url = ?, portfolio_url = ? WHERE user_id = ?",
    "ssssi",
    [$bio, $linkedin, $github, $portfolio, $mentorId]
);

$types = [
    'education' => ['title' => 'education_title', 'description' => 'education_description'],
    'experience' => ['title' => 'experience_title', 'description' => 'experience_description'],
    'skill' => ['title' => 'skill_title', 'description' => 'skill_description'],
    'project' => ['title' => 'project_title', 'description' => 'project_description', 'link' => 'project_link']
];

foreach ($types as $type => $fields) {
    $items = [];
    $titles = $_POST[$fields['title']] ?? [];
    $descriptions = $_POST[$fields['description']] ?? [];
    $links = isset($fields['link']) ? ($_POST[$fields['link']] ?? []) : [];

    foreach ($titles as $index => $title) {
        $filePath = '';

        if ($type === 'project' && isset($_FILES['project_file']['name'][$index]) && $_FILES['project_file']['name'][$index] !== '') {
            $extension = strtolower(pathinfo($_FILES['project_file']['name'][$index], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip'];

            if (!in_array($extension, $allowed, true)) {
                jsonResponse(['success' => false, 'message' => 'Project files must be PDF, DOC, DOCX, JPG, PNG, or ZIP.'], 422);
            }

            $uploadDir = __DIR__ . '/../uploads/mentor_portfolio';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['project_file']['name'][$index], PATHINFO_FILENAME));
            $filename = $safeName . '-' . $mentorId . '-' . time() . '-' . $index . '.' . $extension;

            if (move_uploaded_file($_FILES['project_file']['tmp_name'][$index], $uploadDir . '/' . $filename)) {
                $filePath = 'uploads/mentor_portfolio/' . $filename;
            }
        }

        $items[] = [
            'title' => $title,
            'description' => $descriptions[$index] ?? '',
            'link_url' => $links[$index] ?? '',
            'file_path' => $filePath
        ];
    }

    replaceMentorPortfolioItems($conn, $mentorId, $type, $items);
}

jsonResponse(['success' => true, 'message' => 'Portfolio saved.']);
?>
