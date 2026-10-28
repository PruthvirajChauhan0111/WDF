<?php
require_once __DIR__ . "/auth.php";
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="login-card">
        <h1>Student Dashboard</h1>

        <p>
            Welcome,
            <strong>
                <?= htmlspecialchars($_SESSION["full_name"], ENT_QUOTES, "UTF-8") ?>
            </strong>!
        </p>

        <p>You have successfully logged in.</p>

        <p>
            Role:
            <?= htmlspecialchars($_SESSION["role"], ENT_QUOTES, "UTF-8") ?>
        </p>

        <form method="POST" action="logout.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>