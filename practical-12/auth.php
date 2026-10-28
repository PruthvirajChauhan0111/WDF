<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set("session.use_strict_mode", "1");
    session_start();
}

require_once __DIR__ . "/db.php";

define("SESSION_TIMEOUT", 900); // 15 minutes

// Check whether the current session has expired.
if (isset($_SESSION["student_id"])) {
    $lastActivity = $_SESSION["last_activity"] ?? time();

    if (time() - $lastActivity > SESSION_TIMEOUT) {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            setcookie(session_name(), "", [
                "expires" => time() - 3600,
                "path" => $params["path"],
                "domain" => $params["domain"],
                "secure" => $params["secure"],
                "httponly" => $params["httponly"],
                "samesite" => "Lax"
            ]);
        }

        session_destroy();
        header("Location: login.php?timeout=1");
        exit;
    }

    $_SESSION["last_activity"] = time();
}

// Create a CSRF token for forms.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function requireLogin(): void {
    if (!isset($_SESSION["student_id"])) {
        header("Location: login.php");
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();

    if (($_SESSION["role"] ?? "") !== $role) {
        http_response_code(403);
        exit("Access denied: you do not have permission to view this page.");
    }
}
?>