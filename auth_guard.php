<?php
require_once __DIR__ . '/config.php';

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../auth.php');
    }
}

function requireRole($role) {
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        redirect('../auth.php');
    }
}

function requireStudent() {
    requireRole('student');
}

function requireAdmin() {
    requireRole('admin');
}

function requireMentor() {
    requireRole('mentor');
}

function requireEmployer() {
    requireRole('employer');
}
?>
