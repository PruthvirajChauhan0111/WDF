
<?php
session_start();

$errors = [];
$success = "";

function escapeOutput($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$name = "";
$email = "";
$phone = "";
$course = "";
$year = "";
$gender = "";
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $message = trim($_POST["message"] ?? "");
    $submittedToken = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION["csrf_token"], $submittedToken)
    ) {
        $errors[] = "Your form session has expired. Please refresh and try again.";
    }

    if ($name === "" || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $errors[] = "Name must contain between 2 and 80 characters.";
    } elseif (!preg_match("/^[\p{L}\s.'-]+$/u", $name)) {
        $errors[] = "Name contains invalid characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = "Enter a valid email address.";
    }

    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Mobile number must contain exactly 10 digits.";
    }

    $allowedCourses = [
        "Computer Engineering",
        "Information Technology",
        "Computer Science",
        "Electronics Engineering"
    ];

    if (!in_array($course, $allowedCourses, true)) {
        $errors[] = "Please select a valid course.";
    }

    if (!in_array($year, ["1", "2", "3", "4"], true)) {
        $errors[] = "Please select a valid academic year.";
    }

    if (!in_array($gender, ["Male", "Female", "Other", "Prefer not to say"], true)) {
        $errors[] = "Please select a valid gender option.";
    }

    if (mb_strlen($message) > 1000) {
        $errors[] = "Message must not exceed 1000 characters.";
    }

    if (empty($_POST["terms"])) {
        $errors[] = "You must agree to the terms before submitting.";
    }

    if (empty($errors)) {
        $filePath = __DIR__ . DIRECTORY_SEPARATOR . "students.csv";
        $file = @fopen($filePath, "c+");

        if ($file === false) {
            $errors[] = "Unable to open the storage file. Check your folder permissions.";
        } elseif (!flock($file, LOCK_EX)) {
            fclose($file);
            $errors[] = "Unable to lock the storage file. Please try again.";
        } else {
            $fileSize = filesize($filePath);

            if ($fileSize === false) {
                $fileSize = 0;
            }

            if ($fileSize === 0) {
                $header = [
                    "id",
                    "submitted_at",
                    "name",
                    "email",
                    "phone",
                    "course",
                    "year",
                    "gender",
                    "message"
                ];

                if (fputcsv($file, $header) === false) {
                    $errors[] = "Unable to initialize the CSV file.";
                }
            }

            if (empty($errors) && fseek($file, 0, SEEK_END) !== 0) {
                $errors[] = "Unable to prepare the storage file.";
            }

            if (empty($errors)) {
                $record = [
                    bin2hex(random_bytes(8)),
                    date("Y-m-d H:i:s"),
                    $name,
                    $email,
                    $phone,
                    $course,
                    $year,
                    $gender,
                    $message
                ];

                if (fputcsv($file, $record) === false) {
                    $errors[] = "Your submission could not be saved.";
                } elseif (!fflush($file)) {
                    $errors[] = "The submission could not be written completely.";
                }
            }

            flock($file, LOCK_UN);
            fclose($file);

            if (empty($errors)) {
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
                $_SESSION["form_success"] = "Registration submitted successfully!";
                header("Location: server-register.php");
                exit;
            }
        }
    }
}

if (isset($_SESSION["form_success"])) {
    $success = $_SESSION["form_success"];
    unset($_SESSION["form_success"]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .server-form-page {
            width: min(94%, 760px);
            margin: 45px auto;
        }

        .server-form-card {
            padding: 32px;
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow-md);
        }

        .server-form-card > p {
            color: var(--text-secondary);
        }

        .server-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .server-form-field {
            margin-bottom: 18px;
        }

        .server-form-field label {
            display: block;
            margin-bottom: 7px;
            font-weight: 700;
        }

        .server-form-field textarea {
            min-height: 110px;
        }

        .server-form-message {
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        .server-form-success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .server-form-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .server-form-error ul {
            margin: 8px 0 0;
        }

        .server-form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .server-form-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-weight: 700;
        }

        .server-terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 8px;
        }

        .server-terms input {
            margin-top: 6px;
        }

        @media (max-width: 560px) {
            .server-form-card {
                padding: 22px;
            }

            .server-form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a class="logo" href="index.html">StudentHub</a>
            <nav>
                <a href="index.html">Home</a>
                <a href="about.html">About</a>
                <a href="courses.html">Courses</a>
                <a href="register.html">Registration</a>
                <a href="server-register.php" class="active">PHP Form</a>
            </nav>
        </div>
    </header>

    <main class="server-form-page">
        <section class="server-form-card">
            <h1>Student Registration</h1>
            <p>Submit your details. The server validates the information and stores successful submissions in a CSV file.</p>

            <?php if ($success !== ""): ?>
                <div class="server-form-message server-form-success" role="status">
                    <?= escapeOutput($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="server-form-message server-form-error" role="alert">
                    <strong>Please correct the following issues:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= escapeOutput($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="server-register.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= escapeOutput($_SESSION["csrf_token"]) ?>"
                >

                <div class="server-form-grid">
                    <div class="server-form-field">
                        <label for="name">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            maxlength="80"
                            autocomplete="name"
                            value="<?= escapeOutput($name) ?>"
                            required
                        >
                    </div>

                    <div class="server-form-field">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="254"
                            autocomplete="email"
                            value="<?= escapeOutput($email) ?>"
                            required
                        >
                    </div>

                    <div class="server-form-field">
                        <label for="phone">Mobile Number</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            inputmode="numeric"
                            pattern="[0-9]{10}"
                            maxlength="10"
                            autocomplete="tel"
                            value="<?= escapeOutput($phone) ?>"
                            required
                        >
                    </div>

                    <div class="server-form-field">
                        <label for="course">Course</label>
                        <select id="course" name="course" required>
                            <option value="">Select Course</option>
                            <?php
                            $courseOptions = [
                                "Computer Engineering",
                                "Information Technology",
                                "Computer Science",
                                "Electronics Engineering"
                            ];
                            foreach ($courseOptions as $option):
                            ?>
                                <option
                                    value="<?= escapeOutput($option) ?>"
                                    <?= $course === $option ? "selected" : "" ?>
                                >
                                    <?= escapeOutput($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="server-form-field">
                        <label for="year">Academic Year</label>
                        <select id="year" name="year" required>
                            <option value="">Select Year</option>
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                                <option
                                    value="<?= $i ?>"
                                    <?= $year === (string)$i ? "selected" : "" ?>
                                >
                                    Year <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="server-form-field">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Option</option>
                            <?php
                            $genderOptions = [
                                "Male",
                                "Female",
                                "Other",
                                "Prefer not to say"
                            ];
                            foreach ($genderOptions as $option):
                            ?>
                                <option
                                    value="<?= escapeOutput($option) ?>"
                                    <?= $gender === $option ? "selected" : "" ?>
                                >
                                    <?= escapeOutput($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="server-form-field">
                    <label for="message">Additional Message (Optional)</label>
                    <textarea
                        id="message"
                        name="message"
                        maxlength="1000"
                    ><?= escapeOutput($message) ?></textarea>
                </div>

                <label class="server-terms" for="terms">
                    <input type="checkbox" id="terms" name="terms" value="1" required>
                    <span>I confirm that the information provided is correct.</span>
                </label>

                <div class="server-form-actions">
                    <button class="btn btn-primary" type="submit">
                        Submit Registration
                    </button>
                    <a href="view-submissions.php">View Saved Records</a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>StudentHub · Built by Pruthviraj Chauhan</p>
        </div>
    </footer>
</body>
</html>
