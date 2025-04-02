<?php

session_start();

// database connection
require_once 'php/config.php';

// Check if registration is open
$query_registration_status = "SELECT status FROM settings WHERE id = 1";
$registration_status = $conn->query($query_registration_status)->fetch_assoc()['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if registration is open
    if ($registration_status != 'open') {
        $_SESSION['error'] = 'Registration is currently closed.';
        header("Location: index.php");
        exit;
    }
}

// Redirect to student dashboard if already logged in
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student') {
    header("Location: student_dashboard.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/u.css">
</head>

<body>
    <div class="main-container">
        <div class="content-wrapper">
            <?php if ($registration_status == 'open'): ?>
                <header>
                    <h1>Welcome to Saint Joseph Academy</h1>
                    <p>Choose your registration type:</p>
                </header>

                <div class="registration-options">
                    <a href="teacher_register.php" class="btn green">Teacher Registration</a>
                    <a href="student_register.php" class="btn green">Student Registration</a>
                </div>

                <hr class="divider">
            <?php else: ?>
                <div class="alert info">Registration is currently closed.</div>
            <?php endif; ?>

            <!-- Login Form Section -->
            <div class="login-section">
                <h2>Login</h2>
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<div class='alert error'>" . $_SESSION['error'] . "</div>";
                    unset($_SESSION['error']);
                }
                ?>
                <form action="php/authenticate.php" method="POST">
                    <input type="hidden" name="user_type" value="student">
                    <table class="login-table">
                        <tr>
                            <td><label for="username">Username:</label></td>
                            <td><input type="text" name="username" id="username" placeholder="Username" required></td>
                        </tr>
                        <tr>
                            <td><label for="password">Password:</label></td>
                            <td><input type="password" name="password" id="password" placeholder="Password" required></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="submit" class="btn green">Login</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
