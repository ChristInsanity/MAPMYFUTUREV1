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
        if (function_exists('isAjaxRequest') && isAjaxRequest()) {
            $error = $_SESSION['error'] ?? null;
            $success = $_SESSION['success'] ?? null;
            unset($_SESSION['error'], $_SESSION['success']);
            jsonResponse([
                'success' => $error === null,
                'message' => $error ?? $success ?? 'Done.',
                'redirect' => $url
            ], $error === null ? 200 : 422);
        }

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

    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    function csrf_input() {
        return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
    }

    function isAjaxRequest() {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    function validate_csrf($token = null) {
        $token = $token
            ?? ($_POST['csrf_token'] ?? '')
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    function require_csrf() {
        if (!validate_csrf($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
            if (isAjaxRequest()) {
                jsonResponse(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.'], 403);
            }

            $_SESSION['error'] = 'Invalid security token. Please refresh and try again.';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $prefix = (str_contains($script, '/student/') || str_contains($script, '/admin/') || str_contains($script, '/mentor/') || str_contains($script, '/employer/')) ? '../' : '';
            redirect($prefix . 'auth.php');
        }
    }
    ?>
