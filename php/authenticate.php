<?php
session_start();
require_once 'config.php';

// Get user input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    $_SESSION['error'] = "Please fill in all fields.";
    header("Location: ../index.php");
    exit;
}

$conn->begin_transaction();

try {
    $user = null;
    $role = '';

    // Check in teacher table first
    $stmt = $conn->prepare("SELECT *, role FROM teach_user WHERE username = ?"); //Added role to the select.
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user['role']; //Get the role from the database.
} else {
        // Check in student table if not found in teacher table
        $stmt = $conn->prepare("SELECT * FROM stud_tbl WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = 'student';
        }
    }

    $stmt->close();

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        logActivity($conn, "Failed login attempt - Username not found: " . $username);
        header("Location: ../index.php");
        exit;
    }

    // Plain-text password check
    if ($password !== $user['password']) {
        $_SESSION['error'] = "Invalid password.";
        logActivity($conn, "Failed login attempt - Incorrect password: " . $username);
        header("Location: ../index.php");
        exit;
    }

    // Check for student violations
    if ($role === 'student') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS violations FROM violations WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $violation_result = $stmt->get_result();
        $violation_data = $violation_result->fetch_assoc();
        $stmt->close();

        if ($violation_data['violations'] >= 5) {
            $_SESSION['error'] = "You have reached the violation limit. Please contact your adviser.";
            logActivity($conn, "Login blocked due to violation limit - Username: " . $username);
            header("Location: ../index.php");
            exit;
        }
    }

    // Successful login
    $_SESSION['user'] = $user['username'];
    $_SESSION['role'] = $role; //The role is now pulled from the database.

    logActivity($conn, "User " . $username . " (" . ucfirst($role) . ") logged in successfully.");

    $conn->commit();

    // Redirect based on role
    if ($role == 'admin') {
        header("Location: ../admin_dashboard.php");
    } elseif ($role == 'teacher') {
        header("Location: ../teacher_dashboard.php");
    } else {
        header("Location: ../student_dashboard.php");
    }
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: ../index.php");
    exit;
}

// Function to log user activity
function logActivity($conn, $action) {
    $stmt = $conn->prepare("INSERT INTO activity_log (action) VALUES (?)");
    $stmt->bind_param("s", $action);
    $stmt->execute();
    $stmt->close();
}
?>
