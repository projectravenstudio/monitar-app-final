<?php
session_start();
require_once 'php/config.php';

date_default_timezone_set('Asia/Manila'); // Adjust timezone as needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $violations = $_POST['violations'] ?? [];

    if (empty($username)) {
        $_SESSION['error'] = 'Please enter a student username.';
    } elseif (empty($violations)) {
        $_SESSION['error'] = 'Please select at least one violation.';
    } else {
        $violation_descriptions = implode(', ', $violations);
        $current_date = date('Y-m-d');
        $current_time = date('H:i'); // 24-hour format


        try {
            // Check if the student exists
            $stmt = $conn->prepare("SELECT username FROM stud_tbl WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $_SESSION['error'] = 'Student username not found.';
            } else {
                // Check the total number of violations for the student
                $stmt = $conn->prepare("SELECT COUNT(*) as violation_count FROM violations WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $violation_result = $stmt->get_result();
                $violation_data = $violation_result->fetch_assoc();
                $violation_count = $violation_data['violation_count'];

                if ($violation_count >= 5) {
                    $_SESSION['error'] = "Student has reached the violation limit. Please contact the adviser.";
                } else {
                    // Check for duplicate violation entry for today
                    $stmt = $conn->prepare("SELECT id FROM violations WHERE username = ? AND violation_date = ?");
                    $stmt->bind_param("ss", $username, $current_date);
                    $stmt->execute();
                    $duplicate_result = $stmt->get_result();

                    if ($duplicate_result->num_rows > 0) {
                        $_SESSION['error'] = "Violation entry already exists for today.";
                    } else {
                        // Insert new violation
                        $stmt = $conn->prepare("INSERT INTO violations (username, violation_description, violation_date, violation_time) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $username, $violation_descriptions, $current_date, $current_time);
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Violation recorded successfully.";
                        } else {
                            $_SESSION['error'] = "Error adding violation.";
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Violation Entry</title>
    <link rel="stylesheet" href="css/attendance.css">
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <h1>Enter Violation</h1>
        <form action="" method="POST">
            <label for="username">Student Username:</label>
            <input type="text" name="username" id="username" required>

             
            
            <div>
                <input type="hidden" name="violations[]" value="Late">
            </div>
            
            <button type="submit">Submit Violation</button>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        form.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
    });
    </script>
</body>
</html>