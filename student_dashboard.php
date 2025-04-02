<?php
session_start();

// Redirect if not a student
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

// Database connection
require_once 'php/config.php';

// Fetch student details
$student_username = $_SESSION['user'];
$student_query = "SELECT first_name, last_name, grade_level, section FROM stud_tbl WHERE username = ?";
$student_stmt = $conn->prepare($student_query);
$student_stmt->bind_param("s", $student_username);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows > 0) {
    $student_data = $student_result->fetch_assoc();
    $student_first_name = $student_data["first_name"];
    $student_last_name = $student_data["last_name"];
    $student_grade_level = $student_data['grade_level'];
    $student_section = $student_data['section'];
} else {
    exit("Error: Student information not found.");
}

// Fetch adviser details from teacher_tbl based on grade level and section
$adviser_query = "SELECT first_name, last_name FROM teach_user WHERE grade_level = ? AND section = ? LIMIT 1";
$adviser_stmt = $conn->prepare($adviser_query);
$adviser_stmt->bind_param("ss", $student_grade_level, $student_section);
$adviser_stmt->execute();
$adviser_result = $adviser_stmt->get_result();

$adviser_name = "Not Assigned"; // Default if no adviser is found

if ($adviser_result->num_rows > 0) {
    $adviser_data = $adviser_result->fetch_assoc();
    $adviser_name = $adviser_data["first_name"] . " " . $adviser_data["last_name"];
}

// Fetch student's violation history
$violation_query = "SELECT * FROM violations WHERE username = ? ORDER BY violation_date DESC";
$violation_stmt = $conn->prepare($violation_query);
$violation_stmt->bind_param("s", $student_username);
$violation_stmt->execute();
$violation_result = $violation_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="css/student_dashboard.css">
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <h1>Student Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($student_first_name . " " . $student_last_name); ?></strong></p>
            <p>Grade Level: <strong><?php echo htmlspecialchars($student_grade_level); ?></strong>, 
               Section: <strong><?php echo htmlspecialchars($student_section); ?></strong></p>
            <p>Adviser: <strong><?php echo htmlspecialchars($adviser_name); ?></strong></p>
            <div class="header-buttons">
                <a href="edit_student_info.php" class="btn">Edit Info</a>
                <a href="logout.php" class="btn logout">Logout</a>
            </div>
        </header>

        <section class="section violations">
            <h2>Violation History</h2>
            <input type="text" id="search-violations" onkeyup="searchTable('violations-table', 'search-violations')" placeholder="Search for violations..">
            <div class="table-container">
                <table class="styled-table" id="violations-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $violation_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['violation_description']); ?></td>
                                <td><?php echo htmlspecialchars($row['violation_date']); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['violation_time'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    
    <script>
    function searchTable(tableId, searchInputId) {
        let input = document.getElementById(searchInputId).value.toLowerCase();
        let table = document.getElementById(tableId);
        let rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) {
            let cells = rows[i].getElementsByTagName("td");
            let found = false;

            for (let cell of cells) {
                if (cell.textContent.toLowerCase().includes(input)) {
                    found = true;
                    break;
                }
            }

            rows[i].style.display = found ? "" : "none";
        }
    }
    </script>
</body>
</html>
