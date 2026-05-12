<?php
require_once 'config.php';


// ==============================
// FLASH MESSAGE
// ==============================
$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;

unset($_SESSION['error']);
unset($_SESSION['success']);



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

    if($password !== $confirmPassword){
        $_SESSION['error'] = "Passwords do not match.";
        redirect("auth.php");
    }

    if ($role !== 'student' && trim($applicationNote) === '') {
        $_SESSION['error'] = "Please tell us why you're applying as a mentor or employer.";
        redirect("auth.php");
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
        }

        if($role=="student"){
            $_SESSION['success'] =
                "Account created successfully.";
        }else{
            $_SESSION['success'] =
                "Registration submitted for approval.";
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
    >


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




        <div id="applicationDetails" class="hidden bg-slate-800 border border-slate-700 rounded-3xl p-5 mb-6 space-y-4">
            <div>
                <label class="block text-sm text-slate-400 mb-2">Company or Organization</label>
                <input
                    type="text"
                    name="organization_name"
                    placeholder="Company, school, or mentor network"
                    class="w-full p-3 rounded-xl bg-slate-800"
                >
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2">Tell us about your experience</label>
                <textarea
                    name="application_note"
                    rows="4"
                    placeholder="Why should we approve your mentor or employer account?"
                    class="w-full p-3 rounded-xl bg-slate-800 resize-none"
                ></textarea>
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
    const applicationDetails = document.getElementById('applicationDetails');

    if (applicationDetails) {
        applicationDetails.classList.toggle('hidden', selectedRole === 'student');
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


</script>



</body>
</html>