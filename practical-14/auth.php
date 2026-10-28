<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.use_strict_mode", "1");
    session_start();
}

function requireLogin()
{
    if (empty($_SESSION["student_id"])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole($allowedRoles)
{
    requireLogin();

    $role = $_SESSION["role"] ?? "";

    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        exit("403 Forbidden: You do not have permission to access this page.");
    }
}
?>