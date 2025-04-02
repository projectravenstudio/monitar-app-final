<?php

session_start();

// the database connection
require_once 'php/config.php';

// Check if registration is open
$query_registration_status = "SELECT status FROM settings WHERE id = 1";  
$registration_status = $conn->query($query_registration_status)->fetch_assoc()['status'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data with fallback
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $section = $_POST['section'] ?? '';
    $grade_level = $_POST['grade_level'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if registration is open
    if ($registration_status != 'open') {
        $_SESSION['error'] = 'Registration is currently closed.';
        header("Location: teacher_register.php");
        exit;
    }

    // Process teacher registration
    $role = 'teacher'; // Teacher role

    // Check if username already exists
    $check_query = "SELECT * FROM teach_user WHERE username = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Username already exists.';
        header("Location: teacher_register.php");
        exit;
    }

    // Insert the new teacher into the database
    $insert_query = "INSERT INTO teach_user (first_name, last_name, section, grade_level, username, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param('sssssss', $first_name, $last_name, $section, $grade_level, $username, $password, $role);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Registration successful. You can now log in.';
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = 'Error occurred during registration. Please try again later.';
        header("Location: teacher_register.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Registration</title>
    <link rel="stylesheet" href="css/teacher_register.css">
</head>
<body>
    <div class="container">
        <h1>Teacher Registration</h1>
        <?php
        // Display error or success messages
        if (isset($_SESSION['error'])) {
            echo "<p class='error'>" . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<p class='success'>" . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']);
        }
        ?>
        
        <form action="teacher_register.php" method="POST">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            
            <!-- Grade Level Dropdown -->
            <select name="grade_level" required>
                <option value="" disabled selected>Select Grade Level</option>
                <option value="Grade 7">Grade 7</option>
                <option value="Grade 8">Grade 8</option>
                <option value="Grade 9">Grade 9</option>
                <option value="Grade 10">Grade 10</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
            </select>

            <!-- Section Dropdown based on Grade Level -->
            <select name="section" required>
                <option value="" disabled selected>Select Section</option>
            </select>

            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register</button>
        </form>
    </div>

    <script>
        const sectionSelect = document.querySelector('select[name="section"]');
        const gradeSelect = document.querySelector('select[name="grade_level"]');

        const sections = {
            "Grade 7": ["Venus", "Earth", "Mars", "Jupiter", "Mercury"],
            "Grade 8": ["Amethyst", "Diamond", "Ruby", "Emerald", "Pearl"],
            "Grade 9": ["Gold", "Silver", "Cobalt", "Nickel", "Iron"],
            "Grade 10": ["Aries", "Leo", "Pisces", "Virgo", "Gemini"],
            "Grade 11": ["Del Pilar", "Malvar", "Bonifacio", "Agoncillo", "Rizal"],
            "Grade 12": ["Zamora", "Quezon", "Aguinaldo", "Jacinto", "Mabini"]
        };

        gradeSelect.addEventListener('change', function() {
            const gradeLevel = gradeSelect.value;
            const availableSections = sections[gradeLevel] || [];
            sectionSelect.innerHTML = "<option value='' disabled selected>Select Section</option>";
            availableSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
            });
        });
    </script>
</body>
</html>
