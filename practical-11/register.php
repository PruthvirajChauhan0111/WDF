
<?php

header('Content-Type: application/json; charset=utf-8');

function sendJson($status, $message) {
    http_response_code($status);

    echo json_encode([
        'success' => $status >= 200 && $status < 300,
        'message' => $message
    ]);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(405, 'Please submit the registration form.');
}

try {
    require __DIR__ . '/db.php';

    // Match the name attributes in register.html
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $course = trim($_POST['course'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $terms = isset($_POST['terms']);

    // Validate required fields
    if (
        $fullName === '' ||
        $email === '' ||
        $mobile === '' ||
        $password === '' ||
        $confirmPassword === '' ||
        $course === '' ||
        $year === '' ||
        $gender === ''
    ) {
        sendJson(400, 'Please fill in all required fields.');
    }

    if (!$terms) {
        sendJson(400, 'Please accept the terms and conditions.');
    }

    if (strlen($fullName) < 2 || strlen($fullName) > 60) {
        sendJson(400, 'Please enter a name between 2 and 60 characters.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJson(400, 'Please enter a valid email address.');
    }

    $digits = preg_replace('/\D/', '', $mobile);

    if (
        !preg_match('/^\+?[0-9\s()-]+$/', $mobile) ||
        strlen($digits) < 10 ||
        strlen($digits) > 15
    ) {
        sendJson(400, 'Please enter a valid mobile number.');
    }

    if ($password !== $confirmPassword) {
        sendJson(400, 'Passwords do not match.');
    }

    if (strlen($password) < 8) {
        sendJson(400, 'Password must contain at least 8 characters.');
    }

    if (!in_array($gender, [
        'female',
        'male',
        'other',
        'prefer-not-to-say'
    ], true)) {
        sendJson(400, 'Please select a valid gender option.');
    }

    if (!in_array($year, ['1', '2', '3', '4'], true)) {
        sendJson(400, 'Please select a valid year of study.');
    }

    if (!in_array($course, [
        'computer-engineering',
        'information-technology',
        'computer-science',
        'electronics',
        'other'
    ], true)) {
        sendJson(400, 'Please select a valid course.');
    }

    // Hash the password before storing it
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Assumes db.php creates a PDO connection named $pdo
    $sql = "INSERT INTO students
        (full_name, email, mobile, password_hash, course,
         year_of_study, gender)
        VALUES
        (:full_name, :email, :mobile, :password_hash, :course,
         :year_of_study, :gender)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':mobile' => $mobile,
        ':password_hash' => $passwordHash,
        ':course' => $course,
        ':year_of_study' => $year,
        ':gender' => $gender
    ]);

    sendJson(201, 'Registration successful!');

} catch (PDOException $e) {
    error_log('StudentHub database error: ' . $e->getMessage());

    if ($e->getCode() === '23000') {
        sendJson(409, 'This email or another unique value may already be registered.');
    }

    sendJson(
        500,
        'Database error. Check your db.php settings and students table columns.'
    );

} catch (Throwable $e) {
    error_log('StudentHub PHP error: ' . $e->getMessage());

    sendJson(500, 'Server error. Check the PHP error log.');
}
?>
