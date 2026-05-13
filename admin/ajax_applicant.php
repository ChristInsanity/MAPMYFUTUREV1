<?php
require_once '../auth_guard.php';
require_once '../includes/student_functions.php';

requireAdmin();
ensureMentorTables($conn);

if (!validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    jsonResponse(['success' => false, 'message' => 'Invalid security token.'], 403);
}

$action = sanitize($_POST['action'] ?? '');
$userId = (int)($_POST['user_id'] ?? 0);
$adminId = (int)$_SESSION['user_id'];

if ($action === 'review') {
    $applicant = dbFetchOne(
        $conn,
        "SELECT u.user_id, u.full_name, u.email, u.role, u.status,
                ua.organization_name, ua.application_note,
                mp.age, mp.degree, mp.specialization, mp.years_experience, mp.industry, mp.resume_upload, mp.bio,
                ep.company_name, ep.business_email, ep.company_size, ep.website, ep.business_registration_number,
                ep.business_permit_upload, ep.company_profile_pdf, ep.contact_person, ep.contact_position, ep.contact_number, ep.office_address,
                ep.industry AS employer_industry
         FROM users u
         LEFT JOIN user_applications ua ON ua.user_id = u.user_id
         LEFT JOIN mentor_profiles mp ON mp.user_id = u.user_id
         LEFT JOIN employer_profiles ep ON ep.user_id = u.user_id
         WHERE u.user_id = ? AND u.role IN ('mentor','employer')",
        "i",
        [$userId]
    );

    if (!$applicant) {
        jsonResponse(['success' => false, 'message' => 'Applicant not found.'], 404);
    }

    $certifications = $applicant['role'] === 'mentor'
        ? dbFetchAll($conn, "SELECT * FROM mentor_certifications WHERE user_id = ? ORDER BY uploaded_at DESC", "i", [$userId])
        : [];
    $assignedCareers = $applicant['role'] === 'mentor' ? getMentorCareerAssignments($conn, $userId) : [];

    jsonResponse([
        'success' => true,
        'applicant' => $applicant,
        'certifications' => $certifications,
        'assigned_careers' => $assignedCareers,
        'career_paths' => dbFetchAll($conn, "SELECT path_id, title FROM career_paths ORDER BY title")
    ]);
}

if ($action === 'approve' || $action === 'reject') {
    $careerPathIds = array_map('intval', (array)($_POST['career_path_ids'] ?? []));
    $ok = setApplicantStatus($conn, $userId, $action === 'approve' ? 'approved' : 'rejected', $adminId, $careerPathIds);
    jsonResponse(['success' => $ok, 'message' => $ok ? 'Applicant updated.' : 'Unable to update applicant. Assign at least one track before approving a mentor.'], $ok ? 200 : 422);
}

jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
?>
