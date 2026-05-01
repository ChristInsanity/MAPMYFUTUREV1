<?php
require_once 'config.php';

// ==============================
// HANDLE REGISTER
// ==============================
if (isset($_POST['register'])) {

    $name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    $role = sanitize($_POST['role']);

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if email exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already exists!";
    } else {

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            $success = "Registration successful! Wait for admin approval.";
        } else {
            $error = "Something went wrong!";
        }
    }
}

// ==============================
// HANDLE LOGIN
// ==============================
if (isset($_POST['login'])) {

    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {

            if ($user['status'] !== 'approved') {
                $error = "Account not yet approved!";
            } else {

                // SESSION
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];

                // REDIRECT BASED ON ROLE
                if ($user['role'] == 'admin') {
                    redirect('admin/dashboard.php');
                } elseif ($user['role'] == 'student') {
                    redirect('student/dashboard.php');
                } elseif ($user['role'] == 'mentor') {
                    redirect('mentor/dashboard.php');
                } elseif ($user['role'] == 'employer') {
                    redirect('employer/dashboard.php');
                }
            }

        } else {
            $error = "Invalid password!";
        }

    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Auth - Map My Future</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-950 text-white flex items-center justify-center min-h-screen">

<div class="bg-slate-900 p-8 rounded-xl w-full max-w-md">

    <h2 class="text-2xl font-bold mb-6 text-center">Map My Future</h2>

    <!-- MESSAGE -->
    <?php if (isset($error)) : ?>
        <div class="bg-red-500 p-2 mb-4 rounded"><?= $error ?></div>
    <?php endif; ?>

    <?php if (isset($success)) : ?>
        <div class="bg-green-500 p-2 mb-4 rounded"><?= $success ?></div>
    <?php endif; ?>

    <!-- LOGIN -->
    <form method="POST" class="mb-6">
        <h3 class="font-semibold mb-2">Login</h3>

        <input type="email" name="email" placeholder="Email"
            class="w-full p-2 mb-3 bg-slate-800 rounded">

        <input type="password" name="password" placeholder="Password"
            class="w-full p-2 mb-3 bg-slate-800 rounded">

        <button name="login"
            class="w-full bg-blue-600 py-2 rounded">
            Login
        </button>
    </form>

    <hr class="border-slate-700 mb-6">

    <!-- REGISTER -->
    <form method="POST">
        <h3 class="font-semibold mb-2">Register</h3>

        <input type="text" name="full_name" placeholder="Full Name"
            class="w-full p-2 mb-3 bg-slate-800 rounded">

        <input type="email" name="email" placeholder="Email"
            class="w-full p-2 mb-3 bg-slate-800 rounded">

        <input type="password" name="password" placeholder="Password"
            class="w-full p-2 mb-3 bg-slate-800 rounded">

        <select name="role" class="w-full p-2 mb-3 bg-slate-800 rounded">
            <option value="student">Student</option>
            <option value="mentor">Mentor</option>
            <option value="employer">Employer</option>
        </select>

        <button name="register"
            class="w-full bg-green-600 py-2 rounded">
            Register
        </button>
    </form>

</div>

</body>
</html>