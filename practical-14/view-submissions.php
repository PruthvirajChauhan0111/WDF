
<?php
session_start();

function escapeOutput($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$records = [];
$error = "";

$filePath = __DIR__ . DIRECTORY_SEPARATOR . "students.csv";

if (is_file($filePath) && is_readable($filePath)) {
    $file = fopen($filePath, "r");

    if ($file === false) {
        $error = "Unable to open the records file.";
    } elseif (!flock($file, LOCK_SH)) {
        fclose($file);
        $error = "Unable to read the records file right now.";
    } else {
        $headers = fgetcsv($file);

        if ($headers !== false) {
            while (($row = fgetcsv($file)) !== false) {
                if (count($row) === count($headers)) {
                    $records[] = array_combine($headers, $row);
                }
            }
        }

        flock($file, LOCK_UN);
        fclose($file);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Registrations | StudentHub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .records-page {
            width: min(96%, 1200px);
            margin: 45px auto;
        }

        .records-card {
            padding: 28px;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--surface);
            box-shadow: var(--shadow-md);
        }

        .records-intro {
            color: var(--text-secondary);
        }

        .records-alert {
            padding: 15px;
            border-radius: 10px;
            background: var(--danger-bg);
            color: var(--danger);
        }

        .records-empty {
            padding: 30px 15px;
            text-align: center;
            color: var(--text-secondary);
            border: 1px dashed var(--border);
            border-radius: 12px;
        }

        .records-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .records-actions a {
            display: inline-flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-weight: 700;
        }

        .records-count {
            margin-bottom: 18px;
            color: var(--text-secondary);
            font-weight: 700;
        }

        .records-table td {
            overflow-wrap: anywhere;
        }

        @media (max-width: 600px) {
            .records-card {
                padding: 18px;
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
                <a href="server-register.php">PHP Registration</a>
                <a href="view-submissions.php" class="active">Saved Records</a>
            </nav>
        </div>
    </header>

    <main class="records-page">
        <section class="records-card">
            <h1>Saved Registrations</h1>
            <p class="records-intro">
                Registration data read from the server-side CSV file.
            </p>

            <div class="records-actions">
                <a href="server-register.php">Add Registration</a>
                <a href="index.html">Back to Home</a>
            </div>

            <?php if ($error !== ""): ?>
                <div class="records-alert" role="alert">
                    <?= escapeOutput($error) ?>
                </div>
            <?php elseif (empty($records)): ?>
                <div class="records-empty">
                    <h2>No registrations yet</h2>
                    <p>Submit the registration form to see records here.</p>
                </div>
            <?php else: ?>
                <p class="records-count">
                    Total records: <?= count($records) ?>
                </p>

                <div class="table-wrapper">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Submitted At</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?= escapeOutput($record["submitted_at"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["name"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["email"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["phone"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["course"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["year"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["gender"] ?? "") ?></td>
                                    <td><?= escapeOutput($record["message"] ?? "") ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>StudentHub · Built by Pruthviraj Chauhan</p>
        </div>
    </footer>
</body>
</html>
