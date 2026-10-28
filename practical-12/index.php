<?php
session_start();
require_once "db.php";

$uploadDir = __DIR__ . "/uploads/";
$uploadUrl = "uploads/";
$maxFileSize = 2 * 1024 * 1024; // 2 MB

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function deletePoster($filename, $uploadDir) {
    if (
        $filename !== null &&
        $filename !== "" &&
        basename($filename) === $filename
    ) {
        $path = $uploadDir . $filename;

        if (is_file($path)) {
            unlink($path);
        }
    }
}

function savePoster($field, $uploadDir, $maxFileSize) {
    if (!isset($_FILES[$field])) {
        return null;
    }

    $file = $_FILES[$field];

    if ($file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Poster upload failed.");
    }

    if ($file["size"] <= 0 || $file["size"] > $maxFileSize) {
        throw new RuntimeException("Poster must be smaller than 2 MB.");
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        throw new RuntimeException("Invalid uploaded file.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    if (!isset($allowedTypes[$mime])) {
        throw new RuntimeException(
            "Only JPG, PNG, and WEBP images are allowed."
        );
    }

    // Generate a random filename instead of trusting the original name.
    $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mime];
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        throw new RuntimeException("Could not save the poster.");
    }

    return $filename;
}

// Process form actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Invalid request. Refresh the page and try again.");
    }

    $action = $_POST["action"] ?? "";

    try {
        if ($action === "add" || $action === "update") {
            $id = (int)($_POST["event_id"] ?? 0);
            $title = trim($_POST["title"] ?? "");
            $description = trim($_POST["description"] ?? "");
            $eventDate = trim($_POST["event_date"] ?? "");
            $venue = trim($_POST["venue"] ?? "");

            if (
                $title === "" ||
                $description === "" ||
                $eventDate === "" ||
                $venue === ""
            ) {
                throw new RuntimeException("Please fill in all event fields.");
            }

            if (
                strlen($title) > 150 ||
                strlen($venue) > 150
            ) {
                throw new RuntimeException("Title or venue is too long.");
            }

            $dateObject = DateTime::createFromFormat("!Y-m-d", $eventDate);

            if (
                !$dateObject ||
                $dateObject->format("Y-m-d") !== $eventDate
            ) {
                throw new RuntimeException("Please enter a valid event date.");
            }

            if ($action === "add") {
                $newPoster = savePoster(
                    "poster",
                    $uploadDir,
                    $maxFileSize
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO practical12_events
                     (title, description, event_date, venue, poster)
                     VALUES (:title, :description, :event_date, :venue, :poster)"
                );

                $stmt->execute([
                    ":title" => $title,
                    ":description" => $description,
                    ":event_date" => $eventDate,
                    ":venue" => $venue,
                    ":poster" => $newPoster
                ]);

                $message = "Event added successfully.";

            } else {
                if ($id <= 0) {
                    throw new RuntimeException("Invalid event ID.");
                }

                $stmt = $pdo->prepare(
                    "SELECT poster FROM practical12_events
                     WHERE event_id = :id"
                );
                $stmt->execute([":id" => $id]);
                $existingEvent = $stmt->fetch();

                if (!$existingEvent) {
                    throw new RuntimeException("Event not found.");
                }

                $oldPoster = $existingEvent["poster"];
                $newPoster = savePoster(
                    "poster",
                    $uploadDir,
                    $maxFileSize
                );

                $posterToSave = $newPoster ?? $oldPoster;

                $stmt = $pdo->prepare(
                    "UPDATE practical12_events
                     SET title = :title,
                         description = :description,
                         event_date = :event_date,
                         venue = :venue,
                         poster = :poster
                     WHERE event_id = :id"
                );

                $stmt->execute([
                    ":title" => $title,
                    ":description" => $description,
                    ":event_date" => $eventDate,
                    ":venue" => $venue,
                    ":poster" => $posterToSave,
                    ":id" => $id
                ]);

                if ($newPoster !== null) {
                    deletePoster($oldPoster, $uploadDir);
                }

                $message = "Event updated successfully.";
            }

        } elseif ($action === "delete") {
            $id = (int)($_POST["event_id"] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException("Invalid event ID.");
            }

            $stmt = $pdo->prepare(
                "SELECT poster FROM practical12_events
                 WHERE event_id = :id"
            );
            $stmt->execute([":id" => $id]);
            $event = $stmt->fetch();

            if (!$event) {
                throw new RuntimeException("Event not found.");
            }

            $stmt = $pdo->prepare(
                "DELETE FROM practical12_events WHERE event_id = :id"
            );
            $stmt->execute([":id" => $id]);

            deletePoster($event["poster"], $uploadDir);
            $message = "Event deleted successfully.";
        }

    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        $error = "A database error occurred. Please check the table.";
    } catch (Throwable $ex) {
        $error = $ex->getMessage();
    }
}

// Fetch event for editing
$editEvent = null;

if (isset($_GET["edit"])) {
    $editId = (int)$_GET["edit"];

    $stmt = $pdo->prepare(
        "SELECT * FROM practical12_events WHERE event_id = :id"
    );
    $stmt->execute([":id" => $editId]);
    $editEvent = $stmt->fetch();

    if (!$editEvent) {
        $error = "Event not found.";
    }
}

// Fetch all events
$stmt = $pdo->query(
    "SELECT * FROM practical12_events ORDER BY event_date DESC, event_id DESC"
);
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <header>
        <h1>Event Management System</h1>
        <p>Create events, manage details, and upload event posters.</p>
    </header>

    <?php if ($message !== ""): ?>
        <div class="alert success"><?= e($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert error"><?= e($error) ?></div>
    <?php endif; ?>

    <section class="card">
        <h2><?= $editEvent ? "Edit Event" : "Add New Event" ?></h2>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="action"
                   value="<?= $editEvent ? "update" : "add" ?>">

            <?php if ($editEvent): ?>
                <input type="hidden" name="event_id"
                       value="<?= (int)$editEvent["event_id"] ?>">
            <?php endif; ?>

            <label for="title">Event Title</label>
            <input type="text" id="title" name="title"
                   maxlength="150" required
                   value="<?= e($editEvent["title"] ?? "") ?>">

            <label for="description">Description</label>
            <textarea id="description" name="description"
                      rows="4" required><?= e($editEvent["description"] ?? "") ?></textarea>

            <div class="form-grid">
                <div>
                    <label for="event_date">Event Date</label>
                    <input type="date" id="event_date" name="event_date"
                           required
                           value="<?= e($editEvent["event_date"] ?? "") ?>">
                </div>

                <div>
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue"
                           maxlength="150" required
                           value="<?= e($editEvent["venue"] ?? "") ?>">
                </div>
            </div>

            <label for="poster">Event Poster (JPG, PNG, WEBP; max 2 MB)</label>
            <input type="file" id="poster" name="poster"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

            <?php if (!empty($editEvent["poster"])): ?>
                <p class="current-poster">Current poster:</p>
                <img class="preview"
                     src="<?= e($uploadUrl . $editEvent["poster"]) ?>"
                     alt="Current event poster">
                <p class="hint">Choose a new file only if you want to replace it.</p>
            <?php endif; ?>

            <div class="button-group">
                <button type="submit" class="btn primary">
                    <?= $editEvent ? "Update Event" : "Add Event" ?>
                </button>

                <?php if ($editEvent): ?>
                    <a href="index.php" class="btn secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>All Events</h2>

        <?php if (count($events) === 0): ?>
            <p>No events have been added yet.</p>
        <?php else: ?>
            <div class="event-grid">
                <?php foreach ($events as $event): ?>
                    <article class="event-card">
                        <?php if (!empty($event["poster"])): ?>
                            <img class="poster"
                                 src="<?= e($uploadUrl . $event["poster"]) ?>"
                                 alt="<?= e($event["title"]) ?> poster">
                        <?php else: ?>
                            <div class="no-poster">No poster uploaded</div>
                        <?php endif; ?>

                        <div class="event-content">
                            <h3><?= e($event["title"]) ?></h3>
                            <p><strong>Date:</strong> <?= e($event["event_date"]) ?></p>
                            <p><strong>Venue:</strong> <?= e($event["venue"]) ?></p>
                            <p><?= nl2br(e($event["description"])) ?></p>

                            <div class="actions">
                                <a class="btn edit"
                                   href="index.php?edit=<?= (int)$event["event_id"] ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      onsubmit="return confirm('Delete this event?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION["csrf_token"]) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="event_id"
                                           value="<?= (int)$event["event_id"] ?>">
                                    <button type="submit" class="btn delete">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <footer>Practical 12 | Event Management with Poster Upload</footer>
</div>

</body>
</html>