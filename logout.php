<?php

require_once 'config.php';

// remove all session values
$_SESSION = [];

// destroy session
session_destroy();

// optional: remove session cookie too
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// back to login
redirect('auth.php');