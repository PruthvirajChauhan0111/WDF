<?php
require_once __DIR__ . "/auth.php";

if (isset($_SESSION["student_id"])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";

if (isset($_GET["timeout"])) {
    $message = "Your session expired. Please log in again.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Refresh the page.";
    } else {
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $remember = isset($_POST["remember"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === "") {
            $message = "Enter a valid email and password.";
        } else {
            $stmt = $pdo->prepare(
                "SELECT student_id, full_name, email, password_hash, role
                 FROM students WHERE email = :email LIMIT 1"
            );

            $stmt->execute([":email" => $email]);
            $user = $stmt->fetch();

            if (
                $user &&
                !empty($user["password_hash"]) &&
                password_verify($password, $user["password_hash"])
            ) {
                session_regenerate_id(true);

                $_SESSION["student_id"] = $user["student_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["last_activity"] = time();
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash("sha256", $token);
                    $expiry = date("Y-m-d H:i:s", time() + 2592000);

                    $update = $pdo->prepare(
                        "UPDATE students
                         SET remember_token_hash = :token_hash,
                             remember_token_expiry = :expiry
                         WHERE student_id = :id"
                    );

                    $update->execute([
                        ":token_hash" => $tokenHash,
                        ":expiry" => $expiry,
                        ":id" => $user["student_id"]
                    ]);

                    setcookie(
                        "remember_me",
                        $user["student_id"] . ":" . $token,
                        [
                            "expires" => time() + 2592000,
                            "path" => "/",
                            "httponly" => true,
                            "secure" => !empty($_SERVER["HTTPS"])
                                && $_SERVER["HTTPS"] !== "off",
                            "samesite" => "Lax"
                        ]
                    );
                }

                if ($user["role"] === "admin") {
                    header("Location: admin.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;

            } else {
                $message = "Invalid email or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudentHub Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="login-card">
        <h1>StudentHub</h1>
        <h2>Login to Your Account</h2>

        <?php if ($message !== ""): ?>
            <p class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
            >

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                required
                autocomplete="username"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <label class="remember">
                <input type="checkbox" name="remember">
                Remember Me
            </label>

            <button type="submit">Login</button>
        </form>
    </main>
</body>
</html>