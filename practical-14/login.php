<?php
require_once "db.php";
require_once "auth.php";

if (!empty($_SESSION["student_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
        $error = "Enter a valid email and password.";
    } else {
        $stmt = $pdo->prepare(
            "SELECT student_id, full_name, email, password_hash, role
             FROM students
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password_hash"])) {
            session_regenerate_id(true);

            $_SESSION["student_id"] = $user["student_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"] ?: "student";
            $_SESSION["last_activity"] = time();

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">

<div class="login-card">
    <div class="logo">SH</div>
    <h1>Welcome Back</h1>
    <p class="muted">Sign in to access your dashboard.</p>

    <?php if ($error): ?>
        <div class="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label for="email">Email Address</label>
        <input
            type="email"
            id="email"
            name="email"
            placeholder="Enter your email"
            required
            autocomplete="username"
        >

        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
        >

        <button type="submit" class="btn btn-primary full-width">
            Sign In
        </button>
    </form>

    <p class="footer-text">StudentHub Admin Dashboard</p>
</div>

</body>
</html>