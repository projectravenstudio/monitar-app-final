<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

require_once 'php/config.php';

// statistics
$query_users = "SELECT COUNT(*) as total_users FROM teach_user";
$total_users = $conn->query($query_users)->fetch_assoc()['total_users'];

$query_teachers = "SELECT COUNT(*) as total_teachers FROM teach_user WHERE role = 'teacher'";
$total_teachers = $conn->query($query_teachers)->fetch_assoc()['total_teachers'];

$query_students = "SELECT COUNT(*) as total_students FROM stud_tbl";
$total_students = $conn->query($query_students)->fetch_assoc()['total_students'];

// 

// // Fetch class list
// $class_query = "SELECT * FROM stud_tbl WHERE grade_level = ? AND section = ?";
// $class_stmt = $conn->prepare($class_query);
// $class_stmt->bind_param("ss", $teacher_grade_level, $teacher_section);
// $class_stmt->execute();
// $class_result = $class_stmt->get_result();

// Fetch violators for today
$today = date('Y-m-d');
$violators_query = "SELECT v.*, s.first_name, s.last_name, s.grade_level, s.section 
                    FROM violations v 
                    JOIN stud_tbl s ON v.username = s.username 
                    WHERE v.violation_date = ?";
$violators_stmt = $conn->prepare($violators_query);
$violators_stmt->bind_param("s", $today);
$violators_stmt->execute();
$violators_result = $violators_stmt->get_result();


// Fetch violation history
$violation_history_query = "SELECT s.username, s.first_name, s.last_name, s.grade_level, s.section,
                            COUNT(v.id) AS violation_count 
                            FROM stud_tbl s 
                            LEFT JOIN violations v ON s.username = v.username 
                            GROUP BY s.username, s.first_name, s.last_name
                            HAVING violation_count > 0";
$violation_history_stmt = $conn->prepare($violation_history_query);
$violation_history_stmt->execute();
$violation_history_result = $violation_history_stmt->get_result();

//  all users
$query_all_users = "SELECT id, username, first_name, last_name, grade_level, section, role FROM teach_user";
$all_users = $conn->query($query_all_users)->fetch_all(MYSQLI_ASSOC);

// registration status
$query_registration_status = "SELECT status FROM settings WHERE id = 1";
$registration_status = $conn->query($query_registration_status)->fetch_assoc()['status'];

// Update registration status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_registration'])) {
    $new_status = ($registration_status == 'open') ? 'closed' : 'open';
    $update_query = "UPDATE settings SET status = '$new_status' WHERE id = 1";
    $conn->query($update_query);
    header("Location: admin_dashboard.php");
    exit;
}



// Default password hash (replace with your generated hash!)
define('DEFAULT_PASSWORD_HASH', '$2y$10$YOUR_GENERATED_HASH_HERE');

// Handle password reset
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
//     $user_id = intval($_POST['user_id']);
//     $update_query = "UPDATE teach_user SET password = '" . DEFAULT_PASSWORD_HASH . "', password_changed = 0 WHERE id = $user_id";
//     $conn->query($update_query);
//     header("Location: admin_dashboard.php");
//     exit;
// }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    
    // Ensure DEFAULT_PASSWORD_HASH is properly defined
    $default_password = password_hash("default_password", PASSWORD_DEFAULT);
    
    $update_query = "UPDATE teach_user SET password = '$default_password' WHERE id = $user_id";
    $conn->query($update_query);
    
    header("Location: admin_dashboard.php");
    exit;
}



// Handle password edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the new password
    $update_query = "UPDATE teach_user SET password = '$hashed_password' WHERE id = $user_id";
    $conn->query($update_query);
    header("Location: admin_dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <link rel="stylesheet" href="css/admin_dashboard.css">
    <style>
        .filters{
            margin-top: 10px;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: rgb(26, 20, 141);
            min-width: 120px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content button {
            width: 100%;
            display: block;
            border: none;
            background-color: transparent;
            padding: 8px 12px;
            text-align: left;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Welcome, Admin</h1>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php" class="dashboard-button">Dashboard</a></li>
                    <!-- <li><a href="teacher_dashboard.php">Teacher Dashboard</a></li> -->
                    <li><a href="#" class="download-button">Download</a></li>
                    <li><a href="#" class="import-button">Import CSV/Excel</a></li>
                    <li><a href="logout.php" class="logout-container logout-button">Logout</a></li>
                </ul>
            </nav>
        </header>
        <br>
        <main>
            <section class="stats">
                <h2>Statistics</h2>
                <div class="stat-box">
                    <h3>Total Users</h3>
                    <p><?= $total_users; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Teachers</h3>
                    <p><?= $total_teachers; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Students</h3>
                    <p><?= $total_students; ?></p>
                </div>
            </section>

        <br>
        <br>
        <form method="GET" class="filter-form">
                    <label for="filter_date">Date:</label>
                    <input type="date" name="filter_date" id="filter_date" value="<?= $_GET['filter_date'] ?? '' ?>">

                    <label for="filter_week">Week:</label>
                    <input type="week" name="filter_week" id="filter_week" value="<?= $_GET['filter_week'] ?? '' ?>">

                    <label for="filter_month">Month:</label>
                    <input type="month" name="filter_month" id="filter_month" value="<?= $_GET['filter_month'] ?? '' ?>">

                    <button type="submit" class="apply-filter-button">Apply Filter</button>
                </form>
                <br>
    <!-- -->
        <section class="section violators-list">
            <h2>Today's Violations</h2>
            <input type="text" id="search-violators" onkeyup="searchTable('violators-table', 'search-violators')" placeholder="Search for names..">
            
            <!-- New Filters for Grade Level and Section -->
            <div class="filters">
            <label for="violator-grade-filter">Grade Level:</label>
            <select id="violator-grade-filter" onchange="updateSectionsViolators(); filterViolators();">
                <option value="">All</option>
                <?php
                $grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
                foreach ($grades as $grade) {
                echo "<option value='" . htmlspecialchars($grade) . "'>" . htmlspecialchars($grade) . "</option>";
                }
                ?>
            </select>

            <label for="violator-section-filter">Section:</label>
            <select id="violator-section-filter" onchange="filterViolators()">
                <option value="">All</option>
            </select>
            </div>

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
                    <tr data-grade_level="<?php echo htmlspecialchars($row['grade_level']); ?>" data-section="<?php echo htmlspecialchars($row['section']); ?>">
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

        <script>
            const sectionsDataViolators = {
            "Grade 7": ["Venus", "Earth", "Mars", "Jupiter", "Mercury"],
            "Grade 8": ["Amethyst", "Diamond", "Ruby", "Emerald", "Pearl"],
            "Grade 9": ["Gold", "Silver", "Cobalt", "Nickel", "Iron"],
            "Grade 10": ["Aries", "Leo", "Pisces", "Virgo", "Gemini"],
            "Grade 11": ["Del Pilar", "Malvar", "Bonifacio", "Agoncillo", "Rizal"],
            "Grade 12": ["Zamora", "Quezon", "Aguinaldo", "Jacinto", "Mabini"]
            };

            function updateSectionsViolators() {
            const gradeFilter = document.getElementById("violator-grade-filter");
            const sectionSelect = document.getElementById("violator-section-filter");
            const selectedGrade = gradeFilter.value;

            // Reset section options
            sectionSelect.innerHTML = '<option value="">All</option>';

            if (selectedGrade && sectionsDataViolators[selectedGrade]) {
                sectionsDataViolators[selectedGrade].forEach(function(section) {
                const option = document.createElement("option");
                option.value = section;
                option.textContent = section;
                sectionSelect.appendChild(option);
                });
            } else {
                // When no grade is selected, show all sections grouped by grade
                for (const [grade, sections] of Object.entries(sectionsDataViolators)) {
                const optgroup = document.createElement("optgroup");
                optgroup.label = grade;
                sections.forEach(function(section) {
                    const option = document.createElement("option");
                    option.value = section;
                    option.textContent = section;
                    optgroup.appendChild(option);
                });
                sectionSelect.appendChild(optgroup);
                }
            }
            }

            function filterViolators() {
            var gradeFilter = document.getElementById('violator-grade-filter').value.toLowerCase();
            var sectionFilter = document.getElementById('violator-section-filter').value.toLowerCase();
            var table = document.getElementById('violators-table');
            var rows = table.getElementsByTagName('tr');

            // Skip header row at index 0
            for (var i = 1; i < rows.length; i++) {
                var row = rows[i];
                var grade = row.getAttribute('data-grade_level') ? row.getAttribute('data-grade_level').toLowerCase() : '';
                var section = row.getAttribute('data-section') ? row.getAttribute('data-section').toLowerCase() : '';

                if ((gradeFilter === '' || grade === gradeFilter) && (sectionFilter === '' || section === sectionFilter)) {
                row.style.display = '';
                } else {
                row.style.display = 'none';
                }
            }
            }

            document.addEventListener("DOMContentLoaded", updateSectionsViolators);
        </script>
        <br>

     <!-- -->
        <section class="section frequent-violators">
            <h2>Violation History</h2>
            <input type="text" id="search-history" onkeyup="searchTable('history-table', 'search-history')" placeholder="Search for names..">

            <!-- New Filters for Grade Level and Section -->
            <div class="filters">
            <label for="grade-filter">Grade Level:</label>
            <select id="grade-filter" onchange="updateSections(); filterHistory();">
                <option value="">All</option>
                <?php
                $grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
                foreach ($grades as $grade) {
                    echo "<option value='" . htmlspecialchars($grade) . "'>" . htmlspecialchars($grade) . "</option>";
                }
                ?>
            </select>

            <label for="section-filter">Section:</label>
            <select id="section-filter" onchange="filterHistory()">
                <option value="">All</option>
            </select>

            <script>
                const sectionsData = {
                    "Grade 7": ["Venus", "Earth", "Mars", "Jupiter", "Mercury"],
                    "Grade 8": ["Amethyst", "Diamond", "Ruby", "Emerald", "Pearl"],
                    "Grade 9": ["Gold", "Silver", "Cobalt", "Nickel", "Iron"],
                    "Grade 10": ["Aries", "Leo", "Pisces", "Virgo", "Gemini"],
                    "Grade 11": ["Del Pilar", "Malvar", "Bonifacio", "Agoncillo", "Rizal"],
                    "Grade 12": ["Zamora", "Quezon", "Aguinaldo", "Jacinto", "Mabini"]
                };

                function updateSections() {
                    const gradeFilter = document.getElementById("grade-filter");
                    const sectionSelect = document.getElementById("section-filter");
                    const selectedGrade = gradeFilter.value;

                    // Reset section options
                    sectionSelect.innerHTML = '<option value="">All</option>';

                    if (selectedGrade && sectionsData[selectedGrade]) {
                        sectionsData[selectedGrade].forEach(function(section) {
                            const option = document.createElement("option");
                            option.value = section;
                            option.textContent = section;
                            sectionSelect.appendChild(option);
                        });
                    } else {
                        // When no grade is selected, show all sections grouped by grade
                        for (const [grade, sections] of Object.entries(sectionsData)) {
                            const optgroup = document.createElement("optgroup");
                            optgroup.label = grade;
                            sections.forEach(function(section) {
                                const option = document.createElement("option");
                                option.value = section;
                                option.textContent = section;
                                optgroup.appendChild(option);
                            });
                            sectionSelect.appendChild(optgroup);
                        }
                    }
                }

                document.addEventListener("DOMContentLoaded", updateSections);
            </script>
            </select>
            </div>

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
                    <tr data-grade_level="<?php echo htmlspecialchars($row['grade_level']); ?>" data-section="<?php echo htmlspecialchars($row['section']); ?>">
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['violation_count']); ?></td>
                    <td>
                        <button class="view-violations" data-username="<?php echo htmlspecialchars($row['username']); ?>">View Details</button>
                    </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </section>

        <script>
        function filterHistory() {
            var gradeFilter = document.getElementById('grade-filter').value.toLowerCase();
            var sectionFilter = document.getElementById('section-filter').value.toLowerCase();
            var table = document.getElementById('history-table');
            var rows = table.getElementsByTagName('tr');

            // Skip table header row which is at index 0
            for (var i = 1; i < rows.length; i++) {
            var row = rows[i];
            var grade = row.getAttribute('data-grade_level') ? row.getAttribute('data-grade_level').toLowerCase() : '';
            var section = row.getAttribute('data-section') ? row.getAttribute('data-section').toLowerCase() : '';

            if ((gradeFilter === '' || grade === gradeFilter) && (sectionFilter === '' || section === sectionFilter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
            }
        }
        </script>
        <br>
         <!-- -->
        <br>
            <section class="registration-control">
                <h2>Registration Control</h2>
                <form method="POST">
                    <p>Registration is currently: <?= ucfirst($registration_status); ?></p>
                    <button type="submit" name="toggle_registration"><?= ($registration_status == 'open') ? 'Close Registration' : 'Open Registration'; ?></button>
                </form>
            </section>

            <section class="user-accounts">
                <h2>User Accounts</h2>
                <input type="text" id="search-box" placeholder="Search by username..." onkeyup="filterUsers()">
                <table id="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th onclick="toggleFilter('grade_level')" style="cursor:pointer;">
                                Grade Level <span id="grade_level-arrow">▲▼</span>
                            </th>
                            <th onclick="toggleFilter('section')" style="cursor:pointer;">
                                Section <span id="section-arrow">▲▼</span>
                            </th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $index => $user): ?>
                            <tr class="user-row" data-index="<?= $index ?>">
                                <td><?= htmlspecialchars($user['id']); ?></td>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= htmlspecialchars($user['first_name']); ?></td>
                                <td><?= htmlspecialchars($user['last_name']); ?></td>
                                <td class="grade_level"><?= htmlspecialchars($user['grade_level']); ?></td>
                                <td class="section"><?= htmlspecialchars($user['section']); ?></td>
                                <td><?= htmlspecialchars($user['role']); ?></td>
                        <!-- -->
                        <!-- Modify Button in Table -->
<td>
    <button class="open-modal-btn" data-modal-id="modal-<?= $user['id']; ?>">Modify</button>

    <!-- Unique Modal for this user -->
    <div id="modal-<?= $user['id']; ?>" class="modal-<?= $user['id']; ?>">
        <div class="modal-content-<?= $user['id']; ?>">
            <span class="close" data-modal-id="modal-<?= $user['id']; ?>">&times;</span>
            <h2>Modify User</h2>

            <!-- Reset Password Form -->
            <form action="admin_dashboard.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                <button type="submit" name="reset_password">Reset Password</button>
            </form>
            

            <!-- Edit Password Form -->
            <form action="admin_dashboard.php" method="POST">
                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                <br>
                <input type="password" name="new_password" placeholder="New Password" required>
                <br>
                <button type="submit" name="edit_password">Edit Password</button>
            </form>
        </div>
    </div>
</td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
        function filterUsers() {
            const searchBox = document.getElementById('search-box');
            const filter = searchBox.value.toLowerCase();
            const rows = document.querySelectorAll('#user-table tbody tr');

            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                row.style.display = username.includes(filter) ? '' : 'none';
            });
        }
    </script>

<script>
let sortState = {
    grade_level: 0, // 0 = no sort, 1 = ascending, 2 = descending
    section: 0
};

function toggleFilter(column) {
    const rows = Array.from(document.querySelectorAll("#user-table tbody tr"));
    const arrow = document.getElementById(`${column}-arrow`);

    if (sortState[column] === 0) {
        // Ascending sort
        rows.sort((a, b) => a.querySelector(`.${column}`).textContent.localeCompare(b.querySelector(`.${column}`).textContent, undefined, { numeric: true }));
        sortState[column] = 1;
        arrow.innerHTML = "▲";
    } else if (sortState[column] === 1) {
        // Descending sort
        rows.sort((a, b) => b.querySelector(`.${column}`).textContent.localeCompare(a.querySelector(`.${column}`).textContent, undefined, { numeric: true }));
        sortState[column] = 2;
        arrow.innerHTML = "▼";
    } else {
        // Reset (original order)
        rows.sort((a, b) => a.dataset.index - b.dataset.index);
        sortState[column] = 0;
        arrow.innerHTML = "▲▼";
    }

    // Reset other column arrows
    Object.keys(sortState).forEach(col => {
        if (col !== column) {
            document.getElementById(`${col}-arrow`).innerHTML = "▲▼";
            sortState[col] = 0;
        }
    });

    const tbody = document.querySelector("#user-table tbody");
    tbody.innerHTML = ""; // Clear and re-add sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

// Store initial order
document.querySelectorAll("#user-table tbody tr").forEach((row, index) => {
    row.dataset.index = index;
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const openButtons = document.querySelectorAll(".open-modal-btn");
    const closeButtons = document.querySelectorAll(".close");
    const downloadButton = document.querySelector(".download-button");

    // Open the modal when clicking the "Modify" button
    openButtons.forEach(button => {
        button.addEventListener("click", function () {
            const modalId = this.getAttribute("data-modal-id");
            document.getElementById(modalId).style.display = "flex";
        });
    });

    // Close the modal when clicking the close button
    closeButtons.forEach(button => {
        button.addEventListener("click", function () {
            const modalId = this.getAttribute("data-modal-id");
            document.getElementById(modalId).style.display = "none";
        });
    });

    downloadButton.addEventListener("click", function(){
        const violatorsTable = document.getElementById("violators-table");

        // Get headers from the table's thead
        const headerRow = violatorsTable.querySelector("thead tr");
        let headers = [];
        headerRow.querySelectorAll("th").forEach(th => {
            headers.push('"' + th.textContent.trim().replace(/"/g, '""') + '"');
        });

        let csvContent = "data:text/csv;charset=utf-8," + headers.join(",") + "\r\n";

        // Process each row in tbody
        const rows = violatorsTable.querySelectorAll("tbody tr");
        rows.forEach(row => {
            const cells = row.querySelectorAll("td");
            let rowData = [];
            cells.forEach(cell => {
                rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
            });
            csvContent += rowData.join(",") + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "todays_violations.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Close modal when clicking outside of it
    window.addEventListener("click", function (event) {
        document.querySelectorAll("[id^='modal-']").forEach(modal => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    });

});


</script>
</body>
</html>