    <?php
    // ==============================
    // DATABASE CONFIGURATION
    // ==============================
    $host = 'localhost';
    $db = 'test'; // your database name
    $user = 'root';
    $pass = '';

    // Create connection
    $conn = new mysqli($host, $user, $pass, $db);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // ==============================
    // SESSION START
    // ==============================
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ==============================
    // HELPER FUNCTIONS
    // ==============================

    // Redirect
    function redirect($url) {
        header("Location: $url");
        exit;
    }

    // Auth check
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Role checks
    function isAdmin() {
        return isLoggedIn() && $_SESSION['role'] === 'admin';
    }

    function isStudent() {
        return isLoggedIn() && $_SESSION['role'] === 'student';
    }

    function isMentor() {
        return isLoggedIn() && $_SESSION['role'] === 'mentor';
    }

    function isEmployer() {
        return isLoggedIn() && $_SESSION['role'] === 'employer';
    }

    // ==============================
    // ACCESS CONTROL
    // ==============================

    function checkAuth() {
        if (!isLoggedIn()) {
            redirect('auth.php');
        }
    }

    function checkAdminAuth() {
        if (!isAdmin()) {
            redirect('auth.php');
        }
    }

    function checkStudentAuth() {
        if (!isStudent()) {
            redirect('auth.php');
        }
    }

    function checkMentorAuth() {
        if (!isMentor()) {
            redirect('auth.php');
        }
    }

    function checkEmployerAuth() {
        if (!isEmployer()) {
            redirect('auth.php');
        }
    }

    // ==============================
    // SANITIZE INPUT
    // ==============================

    function sanitize($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    ?>