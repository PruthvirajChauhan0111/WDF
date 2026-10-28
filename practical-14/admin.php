<?php
require_once __DIR__ . "/auth.php";
requireRole("admin");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="login-card">
        <h1>Admin Dashboard</h1>

        <p>
            Welcome, Admin
            <?= htmlspecialchars($_SESSION["full_name"], ENT_QUOTES, "UTF-8") ?>!
        </p>

        <p>You have administrator access.</p>

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