<?php
session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Manager - Fetch API</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header>
        <h1>Student Manager</h1>
        <p>Manage student records without refreshing the page.</p>
    </header>

    <div id="message" class="message" role="status" aria-live="polite"></div>

    <section class="card">
        <h2 id="formTitle">Add Student</h2>

        <form id="studentForm">
            <input type="hidden" id="studentId">

            <div class="form-grid">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" maxlength="100" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" maxlength="100" required>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" id="submitButton" class="btn primary">
                    Add Student
                </button>

                <button type="button" id="cancelButton" class="btn secondary"
                        hidden>
                    Cancel Edit
                </button>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-heading">
            <h2>Student Records</h2>
            <button type="button" id="refreshButton" class="btn secondary">
                Refresh List
            </button>
        </div>

        <div class="search-box">
            <label for="search">Search students</label>
            <input type="search" id="search"
                   placeholder="Search by name, email or course">
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentTable">
                    <tr>
                        <td colspan="5">Loading students...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <footer>Practical 13 | PHP JSON API and Fetch-Based CRUD</footer>
</div>

<script>
    window.APP_CSRF_TOKEN = <?= json_encode(
        $csrfToken,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>
<script src="script.js"></script>

</body>
</html>