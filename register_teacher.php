<?php
session_start();
require_once 'php/config.php';

// Check if form data is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $section = $_POST['section'] ?? '';
    $grade_level = $_POST['grade_level'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input (sql injection safety stuff)
    if (!empty($first_name) && !empty($last_name) && !empty($section) && !empty($grade_level) && !empty($username) && !empty($password)) {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT * FROM teach_user WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // Insert new teacher to the database
            $stmt = $conn->prepare("INSERT INTO teach_user (first_name, last_name, section, grade_level, username, password, role) VALUES (?, ?, ?, ?, ?, ?, 'teacher')");
            $stmt->bind_param("ssssss", $first_name, $last_name, $section, $grade_level, $username, $password);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Teacher registered successfully.";
            } else {
                $_SESSION['error'] = "Failed to register teacher.";
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
