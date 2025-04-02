<?php
session_start();

// Teacher role only
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'teacher') {
    header("Location: index.php");
    exit;
}

//  database connection
require_once 'php/config.php';

// Check if 'username' is passed in the URL (security stuff para sa sql injection)
if (isset($_GET['username'])) {
    $student_username = $_GET['username'];

    //  student details
    $student_query = "SELECT * FROM stud_tbl WHERE username = ?";
    $student_stmt = $conn->prepare($student_query);
    $student_stmt->bind_param("s", $student_username);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();

    if ($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        $student_first_name = $student_data['first_name'];
        $student_last_name = $student_data['last_name'];
        $student_grade_level = $student_data['grade_level'];
        $student_section = $student_data['section'];
    } else {
        echo "Error: Student information not found.";
        exit;
    }

    // violations for this student
    $violation_query = "SELECT * FROM violations WHERE username = ?";
    $violation_stmt = $conn->prepare($violation_query);
    $violation_stmt->bind_param("s", $student_username);
    $violation_stmt->execute();
    $violation_result = $violation_stmt->get_result();
} else {
    echo "Error: No student selected.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>
    <link rel="stylesheet" href="css/student_detail.css">
</head>
<body>
    <div class="student-details">
        <!-- Header Section -->
        <header class="header">
            <h1>Student Details</h1>
            <p>Student: <strong><?php echo htmlspecialchars($student_first_name . " " . $student_last_name); ?></strong></p>
            <p>Grade Level: <strong><?php echo htmlspecialchars($student_grade_level); ?></strong>, Section: <strong><?php echo htmlspecialchars($student_section); ?></strong></p>
            <div class="header-buttons">
                <a href="teacher_dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        </header>

        <!-- Violation List Section -->
        <section class="section violation-list">
            <h2>Violations</h2>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Violation Description</th>
                        <th>Violation Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($violation_result->num_rows > 0) {
                        while ($row = $violation_result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['violation_description']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['violation_date']) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No violations recorded.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
