<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

require_once "db.php";

function respond($success, $message, $data = null, $status = 200) {
    http_response_code($status);

    echo json_encode([
        "success" => $success,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function readJsonBody() {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        respond(false, "Invalid JSON request.", null, 400);
    }

    return $data;
}

function validateStudent($data) {
    $name = trim($data["full_name"] ?? "");
    $email = trim($data["email"] ?? "");
    $course = trim($data["course"] ?? "");

    if ($name === "" || $email === "" || $course === "") {
        respond(false, "All fields are required.", null, 422);
    }

    if (mb_strlen($name) > 100 || mb_strlen($course) > 100) {
        respond(false, "Name or course is too long.", null, 422);
    }

    if (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, "Enter a valid email address.", null, 422);
    }

    return [$name, $email, $course];
}

try {
    $method = $_SERVER["REQUEST_METHOD"];

    // GET: fetch all students
    if ($method === "GET") {
        $stmt = $pdo->query(
            "SELECT student_id, full_name, email, course
             FROM students
             ORDER BY student_id DESC"
        );

        respond(true, "Students loaded successfully.", $stmt->fetchAll());
    }

    // Only POST is allowed for changes
    if ($method !== "POST") {
        header("Allow: GET, POST");
        respond(false, "Method not allowed.", null, 405);
    }

    // Verify CSRF token before changing records
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (
        empty($_SESSION["csrf_token"]) ||
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        respond(false, "Security token is invalid. Refresh the page.", null, 403);
    }

    $body = readJsonBody();
    $action = $body["action"] ?? "";

    // CREATE
    if ($action === "add") {
        [$name, $email, $course] = validateStudent($body);

        $stmt = $pdo->prepare(
            "INSERT INTO students (full_name, email, course)
             VALUES (:name, :email, :course)"
        );

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":course" => $course
        ]);

        respond(true, "Student added successfully.", [
            "student_id" => (int)$pdo->lastInsertId()
        ], 201);
    }

    // UPDATE
    if ($action === "update") {
        $id = filter_var(
            $body["student_id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($id === false || $id === null) {
            respond(false, "Invalid student ID.", null, 422);
        }

        [$name, $email, $course] = validateStudent($body);

        $stmt = $pdo->prepare(
            "UPDATE students
             SET full_name = :name, email = :email, course = :course
             WHERE student_id = :id"
        );

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":course" => $course,
            ":id" => $id
        ]);

        respond(true, "Student updated successfully.");
    }

    // DELETE
    if ($action === "delete") {
        $id = filter_var(
            $body["student_id"] ?? null,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1]]
        );

        if ($id === false || $id === null) {
            respond(false, "Invalid student ID.", null, 422);
        }

        $stmt = $pdo->prepare(
            "DELETE FROM students WHERE student_id = :id"
        );

        $stmt->execute([":id" => $id]);

        if ($stmt->rowCount() === 0) {
            respond(false, "Student not found.", null, 404);
        }

        respond(true, "Student deleted successfully.");
    }

    respond(false, "Unknown action.", null, 400);

} catch (PDOException $e) {
    error_log($e->getMessage());

    if ($e->getCode() === "23000") {
        respond(false, "This email may already be registered.", null, 409);
    }

    respond(false, "A database error occurred.", null, 500);

} catch (Throwable $e) {
    error_log($e->getMessage());
    respond(false, "The server encountered an error.", null, 500);
}