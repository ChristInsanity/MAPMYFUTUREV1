<?php
require_once 'config.php';
require_once 'includes/student_functions.php';


// ==============================
// FLASH MESSAGE
// ==============================
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}



// ==============================
// HANDLE REGISTER
// ==============================
if(isset($_POST['register'])){

    $name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = sanitize($_POST['password'] ?? '');
    $confirmPassword = sanitize($_POST['confirm_password'] ?? '');
    $role = sanitize($_POST['role'] ?? 'student');
    $organizationName = sanitize($_POST['organization_name'] ?? '');
    $applicationNote = sanitize($_POST['application_note'] ?? '');
    $mentorAge = (int)($_POST['mentor_age'] ?? 0);
    $mentorDegree = sanitize($_POST['mentor_degree'] ?? '');
    $mentorSpecialization = sanitize($_POST['mentor_specialization'] ?? '');
    $mentorYears = (int)($_POST['mentor_years_experience'] ?? 0);
    $mentorIndustry = sanitize($_POST['mentor_industry'] ?? '');
    $mentorBio = sanitize($_POST['mentor_bio'] ?? '');
    $employerCompanyName = sanitize($_POST['employer_company_name'] ?? '');
    $employerBusinessEmail = sanitize($_POST['employer_business_email'] ?? '');
    $employerIndustry = sanitize($_POST['employer_industry'] ?? '');
    $employerCompanySize = sanitize($_POST['employer_company_size'] ?? '');
    $employerWebsite = sanitize($_POST['employer_website'] ?? '');
    $employerRegistrationNumber = sanitize($_POST['employer_registration_number'] ?? '');
    $employerContactPerson = sanitize($_POST['employer_contact_person'] ?? '');
    $employerContactPosition = sanitize($_POST['employer_contact_position'] ?? '');
    $employerContactNumber = sanitize($_POST['employer_contact_number'] ?? '');
    $employerOfficeAddress = sanitize($_POST['employer_office_address'] ?? '');
    $resumePath = null;
    $businessPermitPath = null;
    $companyProfilePath = null;

    if($password !== $confirmPassword){
        $_SESSION['error'] = "Passwords do not match.";
        redirect("auth.php");
    }

    if ($role === 'mentor' && ($mentorAge <= 0 || $mentorDegree === '' || $mentorSpecialization === '' || $mentorYears < 0 || empty($_FILES['mentor_resume']['name']))) {
        $_SESSION['error'] = "Please complete the mentor application details.";
        redirect("auth.php");
    }

    if ($role === 'employer' && (
        $employerCompanyName === '' ||
        $employerBusinessEmail === '' ||
        $employerIndustry === '' ||
        $employerCompanySize === '' ||
        $employerRegistrationNumber === '' ||
        $employerContactPerson === '' ||
        $employerContactPosition === '' ||
        $employerContactNumber === '' ||
        $employerOfficeAddress === '' ||
        empty($_FILES['business_permit_upload']['name']) ||
        empty($_FILES['company_profile_pdf']['name'])
    )) {
        $_SESSION['error'] = "Please complete the employer application details.";
        redirect("auth.php");
    }

    if ($role === 'mentor' && isset($_FILES['mentor_resume']) && !empty($_FILES['mentor_resume']['name'])) {
        $allowed = ['pdf'];
        $extension = strtolower(pathinfo($_FILES['mentor_resume']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed, true)) {
            $_SESSION['error'] = "Resume upload must be PDF.";
            redirect("auth.php");
        }

        $uploadDir = __DIR__ . '/uploads/mentors';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES['mentor_resume']['name'], PATHINFO_FILENAME));
        $filename = $safeName . '-' . time() . '.' . $extension;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['mentor_resume']['tmp_name'], $targetPath)) {
            $resumePath = 'uploads/mentors/' . $filename;
        }
    }

    if ($role === 'employer') {
        $uploadDir = __DIR__ . '/uploads/employers';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach (['business_permit_upload' => 'permit', 'company_profile_pdf' => 'profile'] as $field => $prefix) {
            $extension = strtolower(pathinfo($_FILES[$field]['name'] ?? '', PATHINFO_EXTENSION));
            if ($extension !== 'pdf') {
                $_SESSION['error'] = "Employer verification uploads must be PDF.";
                redirect("auth.php");
            }

            $safeName = $prefix . '-' . preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($_FILES[$field]['name'], PATHINFO_FILENAME));
            $filename = $safeName . '-' . time() . '.' . $extension;
            $targetPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
                if ($field === 'business_permit_upload') {
                    $businessPermitPath = 'uploads/employers/' . $filename;
                } else {
                    $companyProfilePath = 'uploads/employers/' . $filename;
                }
            }
        }
    }

    $status = $role === "student"
        ? "approved"
        : "pending";

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    $check = $conn->prepare("
        SELECT user_id
        FROM users
        WHERE email = ?
    ");

    $check->bind_param("s",$email);
    $check->execute();


    if($check->get_result()->num_rows > 0){

        $_SESSION['error'] = "Email already exists.";
        redirect("auth.php");

    }


    $stmt = $conn->prepare("
        INSERT INTO users
        (
            full_name,
            email,
            password,
            role,
            status,
            profile_completed
        )
        VALUES(?,?,?,?,?,0)
    ");

    $stmt->bind_param(
        "sssss",
        $name,
        $email,
        $hashedPassword,
        $role,
        $status
    );

    if($stmt->execute()){
        $userId = $stmt->insert_id;

        if ($role !== 'student') {
            ensureMentorTables($conn);
            $tableExists = $conn->query("SHOW TABLES LIKE 'user_applications'")->num_rows > 0;
            if (!$tableExists) {
                $conn->query(
                    "CREATE TABLE IF NOT EXISTS user_applications (
                        application_id INT(11) NOT NULL AUTO_INCREMENT,
                        user_id INT(11) NOT NULL,
                        role ENUM('mentor','employer') NOT NULL,
                        organization_name VARCHAR(255) DEFAULT NULL,
                        application_note TEXT DEFAULT NULL,
                        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
                        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (application_id),
                        UNIQUE KEY uq_user_applications_user (user_id),
                        CONSTRAINT fk_user_applications_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
                );
            }

            $appStmt = $conn->prepare("
                INSERT INTO user_applications
                (user_id, role, organization_name, application_note, status)
                VALUES (?, ?, ?, ?, 'pending')
            ");
            $appStmt->bind_param("isss", $userId, $role, $organizationName, $applicationNote);
            $appStmt->execute();

            if ($role === 'mentor') {
                dbExecute(
                    $conn,
                    "INSERT INTO mentor_profiles
                     (user_id, age, degree, specialization, years_experience, industry, resume_upload, certifications, bio, verification_status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                     ON DUPLICATE KEY UPDATE
                        age = VALUES(age),
                        degree = VALUES(degree),
                        specialization = VALUES(specialization),
                        years_experience = VALUES(years_experience),
                        industry = VALUES(industry),
                        resume_upload = VALUES(resume_upload),
                        certifications = VALUES(certifications),
                        bio = VALUES(bio),
                        verification_status = 'pending'",
                    "iississss",
                    [$userId, $mentorAge, $mentorDegree, $mentorSpecialization, $mentorYears, $mentorIndustry, $resumePath, '', $mentorBio]
                );

                $certTitles = (array)($_POST['mentor_certification_titles'] ?? []);
                $certFiles = $_FILES['mentor_certification_files'] ?? null;
                $certUploadDir = __DIR__ . '/uploads/mentor_certifications';
                if (!is_dir($certUploadDir)) {
                    mkdir($certUploadDir, 0755, true);
                }

                foreach ($certTitles as $index => $certTitle) {
                    $certTitle = sanitize($certTitle);
                    $fileName = $certFiles['name'][$index] ?? '';
                    if ($certTitle === '' || $fileName === '') {
                        continue;
                    }

                    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    if ($extension !== 'pdf') {
                        continue;
                    }

                    $safeName = preg_replace('/[^A-Za-z0-9_-]/', '-', pathinfo($fileName, PATHINFO_FILENAME));
                    $filename = $safeName . '-' . $userId . '-' . time() . '-' . $index . '.pdf';
                    if (move_uploaded_file($certFiles['tmp_name'][$index], $certUploadDir . '/' . $filename)) {
                        dbExecute(
                            $conn,
                            "INSERT INTO mentor_certifications (user_id, title, file_path) VALUES (?, ?, ?)",
                            "iss",
                            [$userId, $certTitle, 'uploads/mentor_certifications/' . $filename]
                        );
                    }
                }
            }

            if ($role === 'employer') {
                dbExecute(
                    $conn,
                    "INSERT INTO employer_profiles
                     (user_id, company_name, business_email, industry, company_size, website, business_registration_number, business_permit_upload, company_profile_pdf, contact_person, contact_position, contact_number, office_address, verification_status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                     ON DUPLICATE KEY UPDATE
                        company_name = VALUES(company_name),
                        business_email = VALUES(business_email),
                        industry = VALUES(industry),
                        company_size = VALUES(company_size),
                        website = VALUES(website),
                        business_registration_number = VALUES(business_registration_number),
                        business_permit_upload = VALUES(business_permit_upload),
                        company_profile_pdf = VALUES(company_profile_pdf),
                        contact_person = VALUES(contact_person),
                        contact_position = VALUES(contact_position),
                        contact_number = VALUES(contact_number),
                        office_address = VALUES(office_address),
                        verification_status = 'pending'",
                    "issssssssssss",
                    [$userId, $employerCompanyName, $employerBusinessEmail, $employerIndustry, $employerCompanySize, $employerWebsite, $employerRegistrationNumber, $businessPermitPath, $companyProfilePath, $employerContactPerson, $employerContactPosition, $employerContactNumber, $employerOfficeAddress]
                );
            }
        }

        if($role=="student"){
            $_SESSION['success'] =
                "Account created successfully.";
        }else{
            $_SESSION['success'] =
                $role === 'mentor' ? "Application submitted for review." : "Registration submitted for approval.";
        }

    }else{

        $_SESSION['error'] =
            "Something went wrong.";
    }


    redirect("auth.php");
}




// ==============================
// HANDLE LOGIN
// ==============================
if(isset($_POST['login'])){


    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);


    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE email = ?
    ");

    $stmt->bind_param("s",$email);
    $stmt->execute();

    $result = $stmt->get_result();


    if($result->num_rows != 1){

        $_SESSION['error'] = "User not found.";
        redirect("auth.php");

    }


    $user = $result->fetch_assoc();


    if(!password_verify(
        $password,
        $user['password']
    )){

        $_SESSION['error'] =
            "Invalid password.";

        redirect("auth.php");
    }



    if($user['status'] != "approved"){

        $_SESSION['error'] =
            "Account waiting for approval.";

        redirect("auth.php");
    }



    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['full_name'];



    // ADMIN
    if($user['role']=="admin"){

        redirect("admin/dashboard.php");
    }



    // STUDENT
    if($user['role']=="student"){


        if($user['profile_completed']==0){

            redirect("student/profile_setup.php");

        }else{

            redirect("student/dashboard.php");
        }

    }



    if($user['role']=="mentor"){

        redirect("mentor/dashboard.php");
    }



    if($user['role']=="employer"){

        redirect("employer/dashboard.php");
    }
}
?>



<!DOCTYPE html>
<html>

<head>

<title>Map My Future</title>

<script src="https://cdn.tailwindcss.com"></script>

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
/>

</head>



<body class="bg-slate-950 text-white flex items-center justify-center min-h-screen px-4">



<div class="w-full max-w-md bg-slate-900 rounded-2xl p-8 border border-slate-800">


    <!-- LOGO -->
    <div class="text-center mb-8">

        <div class="w-14 h-14 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center mb-4">
            <i class="fa-solid fa-route text-xl"></i>
        </div>

        <h1 class="text-2xl font-bold">
            Map My Future
        </h1>

        <p class="text-slate-400 text-sm mt-2">
            AI-powered career guidance
        </p>

    </div>



    <!-- MESSAGE -->
    <?php if(isset($error)): ?>

        <div class="bg-red-500/20 border border-red-500 p-3 rounded-xl mb-4">
            <?= $error ?>
        </div>

    <?php endif; ?>


    <?php if(isset($success)): ?>

        <div class="bg-green-500/20 border border-green-500 p-3 rounded-xl mb-4">
            <?= $success ?>
        </div>

    <?php endif; ?>




    <!-- TABS -->
    <div class="flex mb-6 bg-slate-800 rounded-xl p-1">

        <button
            onclick="showLogin()"
            id="loginTab"
            type="button"
            class="flex-1 py-2 rounded-lg bg-blue-600"
        >
            Sign In
        </button>


        <button
            onclick="showRegister()"
            id="registerTab"
            type="button"
            class="flex-1 py-2 rounded-lg"
        >
            Create Account
        </button>

    </div>





    <!-- LOGIN -->
    <form method="POST" id="loginForm">
        <?= csrf_input() ?>


        <input
            type="email"
            name="email"
            required
            placeholder="Email"
            class="w-full mb-4 p-3 rounded-xl bg-slate-800"
        >


        <input
            type="password"
            name="password"
            required
            placeholder="Password"
            class="w-full mb-6 p-3 rounded-xl bg-slate-800"
        >



        <button
            name="login"
            class="w-full bg-blue-600 py-3 rounded-xl font-semibold hover:bg-blue-500"
        >
            Sign In
        </button>


    </form>






    <!-- REGISTER -->
    <form
        method="POST"
        id="registerForm"
        class="hidden"
        enctype="multipart/form-data"
    >
        <?= csrf_input() ?>


        <input
            type="text"
            name="full_name"
            required
            placeholder="Full Name"
            class="w-full mb-4 p-3 rounded-xl bg-slate-800"
        >



        <input
            type="email"
            name="email"
            required
            placeholder="Email"
            class="w-full mb-4 p-3 rounded-xl bg-slate-800"
        >



        <input
            type="password"
            name="password"
            required
            placeholder="Password"
            class="w-full mb-4 p-3 rounded-xl bg-slate-800"
        >



        <input
            type="password"
            name="confirm_password"
            required
            placeholder="Confirm Password"
            class="w-full mb-6 p-3 rounded-xl bg-slate-800"
        >




        <!-- ROLE -->
        <div class="grid grid-cols-1 gap-3 mb-6">


            <label>

                <input
                    type="radio"
                    name="role"
                    value="student"
                    checked
                    hidden
                >

                <div class="roleCard activeRole">

                    <div>
                        🎓
                    </div>

                    <div>

                        <p class="font-semibold">
                            Student
                        </p>

                        <p class="text-sm text-slate-400">
                            Instant access
                        </p>

                    </div>

                </div>

            </label>





            <label>

                <input
                    type="radio"
                    name="role"
                    value="mentor"
                    hidden
                >

                <div class="roleCard">

                    <div>
                        🧠
                    </div>

                    <div>

                        <p class="font-semibold">
                            Mentor
                        </p>

                        <p class="text-sm text-slate-400">
                            Requires verification
                        </p>

                    </div>

                </div>

            </label>





            <label>

                <input
                    type="radio"
                    name="role"
                    value="employer"
                    hidden
                >

                <div class="roleCard">

                    <div>
                        💼
                    </div>

                    <div>

                        <p class="font-semibold">
                            Employer
                        </p>

                        <p class="text-sm text-slate-400">
                            Requires verification
                        </p>

                    </div>

                </div>

            </label>


        </div>




        <input type="hidden" name="organization_name" id="organization_name">
        <input type="hidden" name="application_note" id="application_note">

        <div id="mentorModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
            <div class="max-w-2xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-2xl font-bold">Mentor Application</h2>
                        <p class="text-slate-400 text-sm mt-1">Complete this so admin can review your mentor account.</p>
                    </div>
                    <button type="button" onclick="closeMentorModal()" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <input type="number" name="mentor_age" min="18" placeholder="Age" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="mentor_degree" placeholder="Degree" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="mentor_specialization" placeholder="Specialization" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="number" name="mentor_years_experience" min="0" placeholder="Years experience" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="mentor_industry" placeholder="Industry" class="w-full p-3 rounded-xl bg-slate-800">
                    <label class="block">
                        <span class="block text-sm text-slate-400 mb-2">Upload Resume (PDF only)</span>
                        <input type="file" name="mentor_resume" accept=".pdf" class="w-full p-3 rounded-xl bg-slate-800 text-sm">
                        <span class="text-xs text-slate-500">Supported format: PDF</span>
                    </label>
                </div>

                <textarea name="mentor_bio" rows="4" placeholder="Short mentor bio" class="w-full mt-4 p-3 rounded-xl bg-slate-800 resize-none"></textarea>

                <div class="mt-4 bg-[#020B24] border border-[#334155] rounded-xl p-4">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="font-bold">Certifications</h3>
                        <button type="button" onclick="openCertificationModal()" class="bg-slate-800 hover:bg-slate-700 px-3 py-2 rounded-xl text-sm">
                            <i class="fa-solid fa-plus"></i>
                            Add Certification
                        </button>
                    </div>
                    <div id="certificationsList" class="space-y-2 text-sm text-slate-300">
                        <p class="text-slate-500">No certifications added yet.</p>
                    </div>
                    <div id="certificationInputs"></div>
                </div>

                <button type="button" onclick="closeMentorModal()" class="w-full mt-5 bg-blue-600 py-3 rounded-xl font-semibold hover:bg-blue-500">
                    Save Mentor Details
                </button>
            </div>
        </div>

        <div id="certificationModal" class="hidden fixed inset-0 z-[60] bg-black/70 px-4 py-8">
            <div class="max-w-md mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
                <h2 class="text-xl font-bold mb-4">Add Certification</h2>
                <input id="certTitleDraft" type="text" placeholder="Certification Title" class="w-full p-3 rounded-xl bg-slate-800 mb-4">
                <input id="certFileDraft" type="file" accept=".pdf" class="w-full p-3 rounded-xl bg-slate-800 text-sm mb-4">
                <div class="flex gap-3">
                    <button type="button" onclick="saveCertificationDraft()" class="flex-1 bg-blue-600 py-3 rounded-xl font-semibold">Save Certification</button>
                    <button type="button" onclick="closeCertificationModal()" class="flex-1 bg-slate-800 py-3 rounded-xl font-semibold">Cancel</button>
                </div>
            </div>
        </div>

        <div id="employerModal" class="hidden fixed inset-0 z-50 bg-black/70 px-4 py-8 overflow-y-auto">
            <div class="max-w-3xl mx-auto bg-[#162338] border border-[#334155] rounded-2xl p-6">
                <div class="flex items-start justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-2xl font-bold">Employer Application</h2>
                        <p class="text-slate-400 text-sm mt-1">Company identity, verification, and contact details.</p>
                    </div>
                    <button type="button" onclick="closeEmployerModal()" class="w-10 h-10 rounded-xl bg-slate-800 hover:bg-slate-700">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <h3 class="font-bold mb-3 text-blue-200">Company Identity</h3>
                <div class="grid sm:grid-cols-2 gap-4 mb-5">
                    <input type="text" name="employer_company_name" placeholder="Company Name" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="email" name="employer_business_email" placeholder="Business Email" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="employer_industry" placeholder="Industry" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="employer_company_size" placeholder="Company Size" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="url" name="employer_website" placeholder="Website" class="w-full p-3 rounded-xl bg-slate-800">
                </div>

                <h3 class="font-bold mb-3 text-blue-200">Verification</h3>
                <div class="grid sm:grid-cols-2 gap-4 mb-5">
                    <input type="text" name="employer_registration_number" placeholder="Business Registration Number" class="w-full p-3 rounded-xl bg-slate-800">
                    <label class="block">
                        <span class="block text-sm text-slate-400 mb-2">Business Permit Upload (PDF)</span>
                        <input type="file" name="business_permit_upload" accept=".pdf" class="w-full p-3 rounded-xl bg-slate-800 text-sm">
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="block text-sm text-slate-400 mb-2">Company Profile PDF</span>
                        <input type="file" name="company_profile_pdf" accept=".pdf" class="w-full p-3 rounded-xl bg-slate-800 text-sm">
                    </label>
                </div>

                <h3 class="font-bold mb-3 text-blue-200">Contact</h3>
                <div class="grid sm:grid-cols-2 gap-4">
                    <input type="text" name="employer_contact_person" placeholder="Contact Person" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="employer_contact_position" placeholder="Position" class="w-full p-3 rounded-xl bg-slate-800">
                    <input type="text" name="employer_contact_number" placeholder="Contact Number" class="w-full p-3 rounded-xl bg-slate-800">
                    <textarea name="employer_office_address" rows="3" placeholder="Office Address" class="w-full p-3 rounded-xl bg-slate-800 resize-none sm:col-span-2"></textarea>
                </div>

                <button type="button" onclick="closeEmployerModal()" class="w-full mt-5 bg-blue-600 py-3 rounded-xl font-semibold hover:bg-blue-500">
                    Save Employer Details
                </button>
            </div>
        </div>

        <button
            name="register"
            class="w-full bg-green-600 py-3 rounded-xl font-semibold hover:bg-green-500"
        >
            Create Account
        </button>


    </form>


</div>





<style>

.roleCard{

    display:flex;
    gap:16px;

    align-items:center;

    padding:16px;

    border-radius:16px;

    cursor:pointer;

    background:#1e293b;

    border:2px solid transparent;

    transition:.3s;
}


.roleCard:hover{

    transform:translateY(-2px);
}


.activeRole{

    border-color:#2563eb;

    box-shadow:0 0 20px rgba(37,99,235,.3);
}

</style>





<script>


function showLogin(){

    loginForm.classList.remove("hidden");
    registerForm.classList.add("hidden");

    loginTab.classList.add("bg-blue-600");
    registerTab.classList.remove("bg-blue-600");
}



function showRegister(){

    registerForm.classList.remove("hidden");
    loginForm.classList.add("hidden");

    registerTab.classList.add("bg-blue-600");
    loginTab.classList.remove("bg-blue-600");
}




function refreshApplicationDetails() {
    const selectedRole = document.querySelector('input[name="role"]:checked')?.value;

    if (selectedRole === 'mentor') {
        openMentorModal();
    }

    if (selectedRole === 'employer') {
        openEmployerModal();
    }
}

function openMentorModal() {
    document.getElementById('mentorModal')?.classList.remove('hidden');
}

function closeMentorModal() {
    document.getElementById('mentorModal')?.classList.add('hidden');
}

function openEmployerModal() {
    document.getElementById('employerModal')?.classList.remove('hidden');
}

function closeEmployerModal() {
    document.getElementById('organization_name').value = document.querySelector('input[name="employer_company_name"]')?.value || '';
    document.getElementById('application_note').value = 'Employer verification application submitted.';
    document.getElementById('employerModal')?.classList.add('hidden');
}

function openCertificationModal() {
    document.getElementById('certificationModal')?.classList.remove('hidden');
}

function closeCertificationModal() {
    document.getElementById('certTitleDraft').value = '';
    document.getElementById('certFileDraft').value = '';
    document.getElementById('certificationModal')?.classList.add('hidden');
}

function saveCertificationDraft() {
    const title = document.getElementById('certTitleDraft').value.trim();
    const fileInput = document.getElementById('certFileDraft');
    const file = fileInput.files[0];

    if (!title || !file || file.type !== 'application/pdf') {
        alert('Add a certification title and PDF file.');
        return;
    }

    const index = document.querySelectorAll('.certRow').length;
    const hiddenWrap = document.getElementById('certificationInputs');
    const titleInput = document.createElement('input');
    titleInput.type = 'hidden';
    titleInput.name = 'mentor_certification_titles[]';
    titleInput.value = title;

    const storedFile = document.createElement('input');
    storedFile.type = 'file';
    storedFile.name = 'mentor_certification_files[]';
    storedFile.className = 'hidden certFileInput';
    const transfer = new DataTransfer();
    transfer.items.add(file);
    storedFile.files = transfer.files;

    const row = document.createElement('div');
    row.className = 'certRow flex items-center justify-between gap-3 bg-slate-800 rounded-xl px-3 py-2';
    row.innerHTML = `<span>✓ ${title}<span class="text-slate-500 ml-2">${file.name}</span></span><button type="button" class="text-red-300">Remove</button>`;
    row.querySelector('button').addEventListener('click', () => {
        row.remove();
        titleInput.remove();
        storedFile.remove();
        refreshCertificationEmpty();
    });

    hiddenWrap.appendChild(titleInput);
    hiddenWrap.appendChild(storedFile);
    document.getElementById('certificationsList').appendChild(row);
    refreshCertificationEmpty();
    closeCertificationModal();
}

function refreshCertificationEmpty() {
    const list = document.getElementById('certificationsList');
    const empty = list.querySelector('p');
    if (empty) {
        empty.classList.toggle('hidden', document.querySelectorAll('.certRow').length > 0);
    }
}

document
.querySelectorAll('input[name="role"]')
.forEach((radio)=>{

    radio.addEventListener("change",()=>{

        document
        .querySelectorAll(".roleCard")
        .forEach(card=>{

            card.classList.remove(
                "activeRole"
            );

        });


        radio
        .nextElementSibling
        .classList.add(
            "activeRole"
        );

        refreshApplicationDetails();
    });

});

refreshApplicationDetails();

registerForm.addEventListener('submit', async (event) => {
    const selectedRole = document.querySelector('input[name="role"]:checked')?.value;
    if (selectedRole !== 'employer') {
        return;
    }

    event.preventDefault();
    const formData = new FormData(registerForm);
    const button = registerForm.querySelector('button[name="register"]');
    button.disabled = true;
    button.textContent = 'Submitting...';

    const response = await fetch('auth.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });
    const result = await response.json();
    alert(result.message || (result.success ? 'Application submitted for review.' : 'Unable to submit application.'));
    if (result.success) {
        window.location = 'auth.php';
    } else {
        button.disabled = false;
        button.textContent = 'Create Account';
    }
});


</script>



</body>
</html>
