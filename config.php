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

    $conn->set_charset('utf8mb4');

    // ==============================
    // SESSION START
    // ==============================
    if (session_status() === PHP_SESSION_NONE) {
        $sessionPath = session_save_path();

        if ($sessionPath === '' || !is_writable($sessionPath)) {
            $sessionPath = __DIR__ . '/tmp/sessions';

            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0775, true);
            }

            session_save_path($sessionPath);
        }

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
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
    ?>
