<?php
require_once "db.php";
require_once "auth.php";

requireLogin();

// Automatically log out users after 20 minutes of inactivity.
$timeout = 1200;

if (
    isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > $timeout
) {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            "",
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION["last_activity"] = time();

$name = $_SESSION["full_name"] ?? "User";
$role = $_SESSION["role"] ?? "student";

// Build the menu based on the logged-in user's role.
$menu = [
    ["label" => "Dashboard", "icon" => "⌂", "url" => "dashboard.php"]
];

if ($role === "admin") {
    $menu[] = [
        "label" => "Student Management",
        "icon" => "♙",
        "url" => "../practical-11/index.php"
    ];

    $menu[] = [
        "label" => "Event Management",
        "icon" => "▦",
        "url" => "../practical-12/index.php"
    ];

    $menu[] = [
        "label" => "AJAX Student Records",
        "icon" => "↻",
        "url" => "../practical-13/index.php"
    ];

    $menu[] = [
        "label" => "Admin Panel",
        "icon" => "⚙",
        "url" => "#admin-panel"
    ];
} else {
    $menu[] = [
        "label" => "My Profile",
        "icon" => "♙",
        "url" => "#profile"
    ];
}

$studentCount = 0;
$eventCount = 0;

if ($role === "admin") {
    try {
        $studentCount = (int) $pdo
            ->query("SELECT COUNT(*) FROM students")
            ->fetchColumn();
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }

    try {
        $eventCount = (int) $pdo
            ->query("SELECT COUNT(*) FROM practical12_events")
            ->fetchColumn();
    } catch (PDOException $e) {
        // The event table may not have been created yet.
        $eventCount = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | StudentHub</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app-layout">

    <aside class="sidebar">
        <a class="brand" href="dashboard.php">
            <span class="brand-icon">SH</span>
            <span>StudentHub</span>
        </a>

        <p class="menu-heading">MAIN MENU</p>

        <nav class="navigation">
            <?php foreach ($menu as $item): ?>
                <a
                    class="nav-link <?= $item["label"] === "Dashboard" ? "active" : "" ?>"
                    href="<?= htmlspecialchars($item["url"], ENT_QUOTES, "UTF-8") ?>"
                >
                    <span class="nav-icon">
                        <?= htmlspecialchars($item["icon"], ENT_QUOTES, "UTF-8") ?>
                    </span>

                    <?= htmlspecialchars($item["label"], ENT_QUOTES, "UTF-8") ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <div class="user-mini">
                <div class="avatar">
                    <?= strtoupper(substr($name, 0, 1)) ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>
                    </strong>
                    <span><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, "UTF-8") ?></span>
                </div>
            </div>

            <a class="logout-link" href="logout.php">↪ Log Out</a>
        </div>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="eyebrow">OVERVIEW</p>
                <h1>Dashboard</h1>
            </div>

            <div class="topbar-user">
                <div class="avatar">
                    <?= strtoupper(substr($name, 0, 1)) ?>
                </div>

                <div>
                    <strong>
                        <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>
                    </strong>
                    <span><?= htmlspecialchars(ucfirst($role), ENT_QUOTES, "UTF-8") ?></span>
                </div>
            </div>
        </header>

        <section class="welcome-card">
            <div>
                <span class="welcome-label">YOUR WORKSPACE</span>
                <h2>Hello, <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>!</h2>
                <p>
                    Welcome to your StudentHub dashboard.
                    Manage your workspace from one place.
                </p>
            </div>

            <div class="welcome-art">✦</div>
        </section>

        <section class="section-heading">
            <div>
                <h2>Overview</h2>
                <p>Here is your dashboard summary.</p>
            </div>

            <span class="role-badge">
                <?= htmlspecialchars(strtoupper($role), ENT_QUOTES, "UTF-8") ?> ACCESS
            </span>
        </section>

        <?php if ($role === "admin"): ?>

            <section class="stats-grid">

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total Students</span>
                        <span class="stat-icon blue">♙</span>
                    </div>

                    <h3><?= $studentCount ?></h3>
                    <p>Registered student records</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Total Events</span>
                        <span class="stat-icon purple">▦</span>
                    </div>

                    <h3><?= $eventCount ?></h3>
                    <p>Events in the database</p>
                </article>

                <article class="stat-card">
                    <div class="stat-top">
                        <span>Your Role</span>
                        <span class="stat-icon green">✓</span>
                    </div>

                    <h3>Admin</h3>
                    <p>Administrative access enabled</p>
                </article>

            </section>

            <section class="content-card" id="admin-panel">
                <div class="card-heading">
                    <div>
                        <h2>Admin Control Center</h2>
                        <p>Quick access to management features.</p>
                    </div>

                    <span class="status-dot">Active</span>
                </div>

                <div class="quick-links">
                    <a href="../practical-11/index.php" class="quick-link">
                        <span class="quick-icon blue">♙</span>
                        <span>
                            <strong>Manage Students</strong>
                            <small>Add, edit, search and delete records</small>
                        </span>
                        <span class="arrow">→</span>
                    </a>

                    <a href="../practical-12/index.php" class="quick-link">
                        <span class="quick-icon purple">▦</span>
                        <span>
                            <strong>Manage Events</strong>
                            <small>Create events and manage posters</small>
                        </span>
                        <span class="arrow">→</span>
                    </a>

                    <a href="../practical-13/index.php" class="quick-link">
                        <span class="quick-icon green">↻</span>
                        <span>
                            <strong>AJAX Student Records</strong>
                            <small>Manage records without reloading</small>
                        </span>
                        <span class="arrow">→</span>
                    </a>
                </div>
            </section>

        <?php else: ?>

            <section class="content-card" id="profile">
                <div class="card-heading">
                    <div>
                        <h2>Your Profile</h2>
                        <p>Your account information.</p>
                    </div>
                </div>

                <div class="profile-details">
                    <p>
                        <strong>Name:</strong>
                        <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= htmlspecialchars($_SESSION["email"] ?? "", ENT_QUOTES, "UTF-8") ?>
                    </p>

                    <p>
                        <strong>Role:</strong>
                        <?= htmlspecialchars(ucfirst($role), ENT_QUOTES, "UTF-8") ?>
                    </p>
                </div>

                <p class="notice">
                    Your account has student-level access. Administrative menu
                    items are available only to authorized administrators.
                </p>
            </section>

        <?php endif; ?>

        <footer class="page-footer">
            StudentHub · Practical 14 · Role-Based Access Control
        </footer>

    </main>
</div>

</body>
</html>