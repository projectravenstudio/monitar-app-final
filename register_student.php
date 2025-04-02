<?php
session_start();
require_once 'php/config.php';

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate input
    if ($first_name && $last_name && $section && $grade_level && $username && $password) {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM stud_tbl WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 0) {
            $stmt->close();

            // Insert new student into the database (password stored as plaintext)
            $stmt = $conn->prepare("INSERT INTO stud_tbl (first_name, last_name, section, grade_level, username, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $first_name, $last_name, $section, $grade_level, $username, $password);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Student registered successfully.";
            } else {
                $_SESSION['error'] = "Failed to register student.";
            }
        } else {
            $_SESSION['error'] = "Username already exists.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "All fields are required.";
    }
}

// Redirect back to admin dashboard
header("Location: /web/admin_dashboard.php");
exit;
?>
