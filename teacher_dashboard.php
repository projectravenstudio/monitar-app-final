<?php
session_start();

// Redirect if not a teacher
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

// Database connection
require_once 'php/config.php';

// Fetch teacher details
$teacher_username = $_SESSION['user'];
$teacher_query = "SELECT first_name, last_name, grade_level, section FROM teach_user WHERE username = ?";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->bind_param("s", $teacher_username);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

if ($teacher_result->num_rows > 0) {
    $teacher_data = $teacher_result->fetch_assoc();
    $teacher_first_name = $teacher_data["first_name"];
    $teacher_last_name = $teacher_data["last_name"];
    $teacher_grade_level = $teacher_data['grade_level'];
    $teacher_section = $teacher_data['section'];
} else {
    exit("Error: Teacher information not found.");
}

// Fetch class list
$class_query = "SELECT * FROM stud_tbl WHERE grade_level = ? AND section = ?";
$class_stmt = $conn->prepare($class_query);
$class_stmt->bind_param("ss", $teacher_grade_level, $teacher_section);
$class_stmt->execute();
$class_result = $class_stmt->get_result();

// Fetch violators for today
$today = date('Y-m-d');
$violators_query = "SELECT v.*, s.first_name, s.last_name 
                    FROM violations v 
                    JOIN stud_tbl s ON v.username = s.username 
                    WHERE s.grade_level = ? 
                    AND s.section = ? 
                    AND v.violation_date = ?";
$violators_stmt = $conn->prepare($violators_query);
$violators_stmt->bind_param("sss", $teacher_grade_level, $teacher_section, $today);
$violators_stmt->execute();
$violators_result = $violators_stmt->get_result();


// Fetch violation history
$violation_history_query = "SELECT s.username, s.first_name, s.last_name, 
                            COUNT(v.id) AS violation_count 
                            FROM stud_tbl s 
                            LEFT JOIN violations v ON s.username = v.username 
                            WHERE s.grade_level = ? AND s.section = ? 
                            GROUP BY s.username, s.first_name, s.last_name
                            HAVING violation_count > 0";

$violation_history_stmt = $conn->prepare($violation_history_query);
$violation_history_stmt->bind_param("ss", $teacher_grade_level, $teacher_section);
$violation_history_stmt->execute();
$violation_history_result = $violation_history_stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
    <link rel="stylesheet" href="css/teacher_dashboards.css">
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <h1>Teacher Dashboard</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($teacher_first_name . " " . $teacher_last_name); ?></strong></p>
            <p>Grade Level: <strong><?php echo htmlspecialchars($teacher_grade_level); ?></strong>, 
               Section: <strong><?php echo htmlspecialchars($teacher_section); ?></strong></p>
            <div class="header-buttons">
                <a href="edit_teacher_info.php" class="btn">Edit Info</a>
                <a href="logout.php" class="btn logout">Logout</a>
            </div>
        </header>

        <section class="section class-list">
            <h2>Class List</h2>
            <input type="text" id="search-class" onkeyup="searchTable('class-table', 'search-class')" placeholder="Search for names..">
            <div class="table-container">
            <table class="styled-table" id="class-table">
                <thead><tr><th>Name</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while ($row = $class_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><a href='student_details.php?username=<?php echo urlencode($row['username']); ?>'>View Details</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </section>

        <section class="section violators-list">
            <h2>Today's Violations</h2>
            <input type="text" id="search-violators" onkeyup="searchTable('violators-table', 'search-violators')" placeholder="Search for names..">
            <div class="table-container">
            <table class="styled-table" id="violators-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $violators_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['violation_description']); ?></td>
                            <td><?php echo htmlspecialchars($row['violation_date']); ?></td>
                            <td><?php echo date("h:i A", strtotime($row['violation_time'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </section>

        <section class="section frequent-violators">
            <h2>Violation History</h2>
            <input type="text" id="search-history" onkeyup="searchTable('history-table', 'search-history')" placeholder="Search for names..">
            <div class="table-container">
            <table class="styled-table" id="history-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Total Violations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $violation_history_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['violation_count']); ?></td>
                            <td><button class='view-violations' data-username='<?php echo htmlspecialchars($row['username']); ?>'>View Details</button></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </section>


        <!-- Modal for violation details -->
        <div id="violation-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div id="modal-header">
                    <span class="close-btn">&times;</span>
                    <h2>Violation Details</h2>
                </div>
                <div id="violation-details"></div>
            </div>
        </div>

</div>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    // Search Functionality
    function searchTable(tableId, searchInputId) {
        let input = document.getElementById(searchInputId).value.toLowerCase();
        let table = document.getElementById(tableId);
        let rows = table.getElementsByTagName("tr");

        for (let i = 1; i < rows.length; i++) { // Start from index 1 to skip table headers
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

    // Attach search event listeners
    document.getElementById("search-class").addEventListener("keyup", function () {
        searchTable("class-table", "search-class");
    });

    document.getElementById("search-violators").addEventListener("keyup", function () {
        searchTable("violators-table", "search-violators");
    });

    document.getElementById("search-history").addEventListener("keyup", function () {
        searchTable("history-table", "search-history");
    });

    // View Violations Modal Functionality
    document.querySelectorAll(".view-violations").forEach(button => {
        button.addEventListener("click", function () {
            let username = this.getAttribute("data-username");

            fetch(`fetch_violations.php?username=${username}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                    } else {
                        let modalDetails = document.getElementById("violation-details");
                        modalDetails.innerHTML = ""; // Clear previous content

                        if (data.length === 0) {
                            modalDetails.innerHTML += "<p>No violations found.</p>";
                        } else {
                            let table = "<table class='styled-table'><thead><tr><th>Description</th><th>Date</th><th>Time</th><th>Actions</th></tr></thead><tbody>";
                            data.forEach(v => {
                                table += `<tr data-id="${v.id}">
                                    <td><input type="text" value="${v.violation_description}" class="edit-description"></td>
                                    <td>${v.violation_date}</td>
                                    <td>${v.violation_time}</td>
                                    <td>
                                        <button class="save-edit" data-id="${v.id}">Save</button>
                                        <button class="delete-violation" data-id="${v.id}">Delete</button>
                                    </td>
                                </tr>`;
                            });
                            table += "</tbody></table>";
                            modalDetails.innerHTML += table;
                        }

                        // Show modal
                        document.getElementById("violation-modal").style.display = "block";

                        // Attach event listeners for edit and delete
                        document.querySelectorAll(".save-edit").forEach(btn => {
                            btn.addEventListener("click", function () {
                                let violationId = this.getAttribute("data-id");
                                let newDesc = this.closest("tr").querySelector(".edit-description").value;

                                fetch("update_violation.php", {
                                    method: "POST",
                                    headers: { "Content-Type": "application/json" },
                                    body: JSON.stringify({ violation_id: violationId, new_description: newDesc })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert("Violation updated successfully!");
                                        this.closest("tr").querySelector(".edit-description").value = newDesc; // Update input value
                                    } else {
                                        alert("Failed to update violation.");
                                    }
                                });
                            });
                        });

                        document.querySelectorAll(".delete-violation").forEach(btn => {
                            btn.addEventListener("click", function () {
                                let violationId = this.getAttribute("data-id");
                                if (confirm("Are you sure you want to delete this violation?")) {
                                    fetch("delete_violation.php", {
                                        method: "POST",
                                        headers: { "Content-Type": "application/json" },
                                        body: JSON.stringify({ violation_id: violationId })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            alert("Violation deleted successfully!");
                                            this.closest("tr").remove();
                                        } else {
                                            alert("Failed to delete violation.");
                                        }
                                    });
                                }
                            });
                        });

                    }
                })
                .catch(error => console.error("Error:", error));
        });
    });

    // Close modal when clicking the close button
    document.querySelector(".close-btn").addEventListener("click", function () {
        document.getElementById("violation-modal").style.display = "none";
    });

    // Close modal when clicking outside
    document.getElementById("violation-modal").addEventListener("click", function (e) {
        if (e.target === this) {
            this.style.display = "none";
        }
    });

});
</script>

</body>
</html>