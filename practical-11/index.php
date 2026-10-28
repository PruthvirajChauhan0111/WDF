<?php
session_start();

require_once "db.php";

// Generate a CSRF token for form security.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

// Handle form submissions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $message = "Invalid request. Please refresh the page.";
        $messageType = "error";

    } else {

        $action = $_POST["action"] ?? "";

        try {

            // CREATE: Add a student.
            if ($action === "add") {

                $name = trim($_POST["full_name"] ?? "");
                $email = trim($_POST["email"] ?? "");
                $course = trim($_POST["course"] ?? "");

                if ($name === "" || $email === "" || $course === "") {
                    throw new Exception("Please fill in all fields.");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid email.");
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO students (full_name, email, course)
                     VALUES (:name, :email, :course)"
                );

                $stmt->execute([
                    ":name" => $name,
                    ":email" => $email,
                    ":course" => $course
                ]);

                $message = "Student added successfully!";
            }

            // UPDATE: Edit a student.
            elseif ($action === "update") {

                $id = filter_var(
                    $_POST["student_id"] ?? "",
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 1]]
                );

                $name = trim($_POST["full_name"] ?? "");
                $email = trim($_POST["email"] ?? "");
                $course = trim($_POST["course"] ?? "");

                if (!$id || $name === "" || $email === "" || $course === "") {
                    throw new Exception("Please provide valid student details.");
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Please enter a valid email.");
                }

                $stmt = $pdo->prepare(
                    "UPDATE students
                     SET full_name = :name,
                         email = :email,
                         course = :course
                     WHERE student_id = :id"
                );

                $stmt->execute([
                    ":name" => $name,
                    ":email" => $email,
                    ":course" => $course,
                    ":id" => $id
                ]);

                $message = "Student updated successfully!";
            }

            // DELETE: Remove a student.
            elseif ($action === "delete") {

                $id = filter_var(
                    $_POST["student_id"] ?? "",
                    FILTER_VALIDATE_INT,
                    ["options" => ["min_range" => 1]]
                );

                if (!$id) {
                    throw new Exception("Invalid student ID.");
                }

                $stmt = $pdo->prepare(
                    "DELETE FROM students WHERE student_id = :id"
                );

                $stmt->execute([":id" => $id]);

                $message = $stmt->rowCount() > 0
                    ? "Student deleted successfully!"
                    : "Student record not found.";
            }

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $message = "This email is already registered.";
            } else {
                error_log($e->getMessage());
                $message = "A database error occurred.";
            }

            $messageType = "error";

        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "error";
        }
    }
}

// Get search and filter values.
$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");

// Fetch available courses for the filter dropdown.
$courseStmt = $pdo->query(
    "SELECT DISTINCT course
     FROM students
     WHERE course <> ''
     ORDER BY course"
);

$courses = $courseStmt->fetchAll();

// Build the search and filter query safely.
$sql = "SELECT student_id, full_name, email, course, created_at
        FROM students
        WHERE 1 = 1";

$params = [];

if ($search !== "") {
    $sql .= " AND (
        full_name LIKE :name
        OR email LIKE :email
        OR course LIKE :search_course
    )";

    $keyword = "%" . $search . "%";

    $params[":name"] = $keyword;
    $params[":email"] = $keyword;
    $params[":search_course"] = $keyword;
}

if ($courseFilter !== "") {
    $sql .= " AND course = :filter_course";
    $params[":filter_course"] = $courseFilter;
}

$sql .= " ORDER BY student_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Load a student for editing, if requested.
$editStudent = null;

if (isset($_GET["edit"])) {
    $editId = filter_var(
        $_GET["edit"],
        FILTER_VALIDATE_INT,
        ["options" => ["min_range" => 1]]
    );

    if ($editId) {
        $editStmt = $pdo->prepare(
            "SELECT student_id, full_name, email, course
             FROM students
             WHERE student_id = :id"
        );

        $editStmt->execute([":id" => $editId]);
        $editStudent = $editStmt->fetch();
    }
}

function e($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Management System</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <header class="page-header">
        <div>
            <p class="eyebrow">PRACTICAL 11</p>
            <h1>Student Management</h1>
            <p class="subtitle">
                Manage student records in one place.
            </p>
        </div>

        <div class="total">
            <span>Total Results</span>
            <strong><?= count($students) ?></strong>
        </div>
    </header>

    <?php if ($message !== ""): ?>
        <div class="message <?= e($messageType) ?>">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="card">
        <h2>
            <?= $editStudent ? "Update Student" : "Add New Student" ?>
        </h2>

        <form method="POST" action="index.php" class="student-form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION["csrf_token"]) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="<?= $editStudent ? "update" : "add" ?>"
            >

            <?php if ($editStudent): ?>
                <input
                    type="hidden"
                    name="student_id"
                    value="<?= e($editStudent["student_id"]) ?>"
                >
            <?php endif; ?>

            <div class="form-grid">

                <div class="field">
                    <label for="full_name">Full Name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="100"
                        placeholder="Enter student name"
                        value="<?= e($editStudent["full_name"] ?? "") ?>"
                        required
                    >
                </div>

                <div class="field">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        placeholder="student@example.com"
                        value="<?= e($editStudent["email"] ?? "") ?>"
                        required
                    >
                </div>

                <div class="field full-width">
                    <label for="course">Course</label>
                    <input
                        type="text"
                        id="course"
                        name="course"
                        maxlength="100"
                        placeholder="Enter course name"
                        value="<?= e($editStudent["course"] ?? "") ?>"
                        required
                    >
                </div>

            </div>

            <div class="form-actions">
                <?php if ($editStudent): ?>
                    <button type="submit" class="btn primary">
                        Update Student
                    </button>

                    <a href="index.php" class="btn secondary">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" class="btn primary">
                        Add Student
                    </button>
                <?php endif; ?>
            </div>

        </form>
    </section>

    <section class="card">
        <h2>Search and Filter Students</h2>

        <form method="GET" action="index.php" class="filter-form">

            <div class="field search-field">
                <label for="search">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Search name, email or course"
                    value="<?= e($search) ?>"
                >
            </div>

            <div class="field">
                <label for="course-filter">Filter by Course</label>
                <select id="course-filter" name="course">
                    <option value="">All Courses</option>

                    <?php foreach ($courses as $item): ?>
                        <option
                            value="<?= e($item["course"]) ?>"
                            <?= $courseFilter === $item["course"]
                                ? "selected"
                                : "" ?>
                        >
                            <?= e($item["course"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn primary">
                Search
            </button>

            <a href="index.php" class="btn secondary">
                Reset
            </a>

        </form>
    </section>

    <section class="card">
        <h2>Student Records</h2>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (count($students) > 0): ?>

                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= e($student["student_id"]) ?></td>
                                <td><?= e($student["full_name"]) ?></td>
                                <td><?= e($student["email"]) ?></td>
                                <td>
                                    <span class="course-badge">
                                        <?= e($student["course"]) ?>
                                    </span>
                                </td>
                                <td><?= e($student["created_at"]) ?></td>
                                <td>
                                    <div class="action-buttons">

                                        <a
                                            class="btn small edit"
                                            href="index.php?edit=<?= e($student["student_id"]) ?>"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="POST"
                                            action="index.php"
                                            onsubmit="return confirm('Are you sure you want to delete this student?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?= e($_SESSION["csrf_token"]) ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete"
                                            >

                                            <input
                                                type="hidden"
                                                name="student_id"
                                                value="<?= e($student["student_id"]) ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn small delete"
                                            >
                                                Delete
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty">
                                No student records found.
                            </td>
                        </tr>

                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <footer>
        Student Management System &middot; Practical 11
    </footer>

</div>

</body>
</html>