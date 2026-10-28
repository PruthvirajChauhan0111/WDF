<?php
require_once __DIR__ . "/auth.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Please use the logout button.");
}

if (
    !isset($_POST["csrf_token"], $_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
) {
    http_response_code(403);
    exit("Invalid logout request.");
}

if (isset($_SESSION["student_id"])) {
    $stmt = $pdo->prepare(
        "UPDATE students
         SET remember_token_hash = NULL,
             remember_token_expiry = NULL
         WHERE student_id = :id"
    );

    $stmt->execute([":id" => $_SESSION["student_id"]]);
}

setcookie("remember_me", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "httponly" => true,
    "secure" => !empty($_SERVER["HTTPS"])
        && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Lax"
]);

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>