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
define('DEFAULT_PASSWORD_HASH', '');

// Handle password reset
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
//     $user_id = intval($_POST['user_id']);
//     $update_query = "UPDATE teach_user SET password = '" . DEFAULT_PASSWORD_HASH . "', password_changed = 0 WHERE id = $user_id";
//     $conn->query($update_query);
//     header("Location: admin_dashboard.php");
//     exit;
// }
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
//     $user_id = intval($_POST['user_id']);
    
//     // Ensure DEFAULT_PASSWORD_HASH is properly defined
//     $default_password = password_hash("1234", PASSWORD_DEFAULT);
    
//     $update_query = "UPDATE teach_user SET password = '$default_password' WHERE id = $user_id";
//     $conn->query($update_query);
    
//     header("Location: admin_dashboard.php");
//     exit;
// }

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    
    // Default password is '1234' and hash it
    $default_password = password_hash("1234", PASSWORD_DEFAULT);
    
    // Prepare the update query
    $update_query = $conn->prepare("UPDATE teach_user SET password = ?, password = 1234 WHERE id = ?");
    
    // Bind parameters: s for string (password), i for integer (user_id)
    $update_query->bind_param("si", $default_password, $user_id);
    
    // Execute the query
    $update_query->execute();
    
    // Redirect back to the admin dashboard
    header("Location: admin_dashboard.php");
    exit;
}


// // Handle password edit
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_password'])) {
//     $user_id = intval($_POST['user_id']);
//     $new_password = $_POST['new_password'];
//     $hashed_password = password_hash($new_password, PASSWORD_DEFAULT); // Hash the new password
//     $update_query = "UPDATE teach_user SET password = '$hashed_password' WHERE id = $user_id";
//     $conn->query($update_query);
//     header("Location: admin_dashboard.php");
//     exit;
// }
// Handle password edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_password'])) {
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];  // Get the password directly from the user input

    // Prepare the update query using a prepared statement
    $update_query = $conn->prepare("UPDATE teach_user SET password = ? WHERE id = ?");

    // Bind the parameters: s for string (password), i for integer (user_id)
    $update_query->bind_param("si", $new_password, $user_id);

    // Execute the query
    $update_query->execute();

    // Redirect back to the admin dashboard
    header("Location: admin_dashboard.php");
    exit;
}
// Handle delete student request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mass deletion
    if (isset($_POST['student_ids'])) {
        $student_ids = json_decode($_POST['student_ids'], true);
        if (is_array($student_ids) && count($student_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
            $types = str_repeat('i', count($student_ids));
            $stmt = $conn->prepare("DELETE FROM stud_tbl WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$student_ids);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete selected students.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid student IDs.']);
        }
        exit;
    }

    // Single deletion (existing logic)
    if (isset($_POST['student_id'])) {
        $student_id = $_POST['student_id'];
        if ($student_id) {
            $delete_student_query = "DELETE FROM stud_tbl WHERE id = ?";
            $delete_student_stmt = $conn->prepare($delete_student_query);
            $delete_student_stmt->bind_param("i", $student_id);

            if ($delete_student_stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to delete student.']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid student ID.']);
        }
        exit;
    }
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
        <?php 
// Assuming the user role is stored in a session variable
$user_role = $_SESSION['role'];  // Replace with your session variable if different

if ($user_role === 'admin') {
    // Admin bypass logic: You can choose not to run the query or run it differently
    // For example, set $class_result to an empty array or modify it to show admin-specific data
    $class_result = [];  // Empty or predefined data for the admin view
} else {
    // Query for non-admin users (normal flow)
    $class_result = $conn->query("SELECT * FROM students WHERE status = 'active'");
}
?>

<?php if ($user_role === 'admin'): ?>
    <?php
// Assuming the user role is stored in a session variable
$user_role = $_SESSION['role'];  // Replace with your session variable if different

// If the user is an admin, fetch all students without filtering by grade level or section
if ($user_role === 'admin') {
    $class_query = "SELECT * FROM stud_tbl";  // Get all students
    $class_stmt = $conn->prepare($class_query);
} else {
    // Query for non-admin users (e.g., teachers) to filter by grade level and section
    $class_query = "SELECT * FROM stud_tbl WHERE grade_level = ? AND section = ?";
    $class_stmt = $conn->prepare($class_query);
    $class_stmt->bind_param("ss", $teacher_grade_level, $teacher_section);  // Bind teacher's grade level and section
}

// Execute the query and get the result
$class_stmt->execute();
$class_result = $class_stmt->get_result();
?>

<section class="section class-list">
    <h2>Class List</h2>
    <input type="text" id="search-class-list" onkeyup="searchTable('class-list-table', 'search-class-list')" placeholder="Search for names..">
    <label for="entries">Show 
        <select id="entries" onchange="filterTableEntries()">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="15">15</option>
            <option value="25">25</option>
        </select> entries
        <div class="delete-button-wrapper">
        <button id="delete-selected">Delete Selected</button>
    </div>
    </label>

    <div class="table-container">
    <table class="styled-table" id="class-list-table">
        <thead>
            <tr>
                <th><input type="checkbox" id="select-all"></th>
                <th>Name</th>
                <th>Grade Level</th>
                <th>Section</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $class_result->fetch_assoc()): ?>
                <tr>
                    <td><input type="checkbox" class="select-student" value="<?php echo $row['id']; ?>"></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['section']); ?></td>
                    <td>
                        <button class="delete-student" data-id="<?php echo $row['id']; ?>">Delete</button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</section>

<?php endif; ?>

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
                    <th>Grade Level</th>
                    <th>Section</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $violators_result->fetch_assoc()): ?>
                    <tr data-grade_level="<?php echo htmlspecialchars($row['grade_level']); ?>" data-section="<?php echo htmlspecialchars($row['section']); ?>">
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['section']); ?>
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
                    <th>Grade Level</th>
                    <th>Section</th>
                    <th>Total Violations</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php while ($row = $violation_history_result->fetch_assoc()): ?>
                    <tr data-grade_level="<?php echo htmlspecialchars($row['grade_level']); ?>" data-section="<?php echo htmlspecialchars($row['section']); ?>">
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['section']); ?> </td>
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
        
        <div id="violation-modal" class="raven-modal" style="display: none;">
    <div class="raven-modal-content">
        <div class="raven-modal-header">
            <span class="raven-close-btn">&times;</span>
            <h2 class="raven-modal-title">Violation Details</h2>
        </div>
        <div id="violation-details" class="raven-modal-body"></div>
    </div>
</div>
        

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
    const importButton = document.querySelector(".import-button");
    
    importButton.addEventListener("click", function() {
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.accept = ".csv";
        fileInput.style.display = "none";
        document.body.appendChild(fileInput);

        fileInput.addEventListener("change", function() {
            const file = fileInput.files[0];
            if (file) {
                const formData = new FormData();
                formData.append("csv_file", file);

                fetch("import_csv.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Records imported successfully.");
                        location.reload();
                    } else {
                        alert("Failed to import records.");
                    }
                })
                .catch(error => console.error("Error:", error));
            }
            document.body.removeChild(fileInput);
        });
        fileInput.click();
    });

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
     document.querySelector(".raven-close-btn").addEventListener("click", function () {
        document.getElementById("violation-modal").style.display = "none";
    });

    // Close modal when clicking outside
    document.getElementById("violation-modal").addEventListener("click", function (e) {
        if (e.target === this) {
            this.style.display = "none";
        }
    });

// Filter visible rows
function filterTableEntries() {
    const limit = parseInt(document.getElementById("entries").value);
    const rows = document.querySelectorAll("#class-list-table tbody tr");

    rows.forEach((row, index) => {
        row.style.display = (index < limit) ? "" : "none";
    });
}

// Search table by name
function searchTable(tableId, searchInputId) {
    const input = document.getElementById(searchInputId).value.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}

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
});

// On page load
document.addEventListener("DOMContentLoaded", function () {
    filterTableEntries();

    // Delete Student
    document.querySelectorAll(".delete-student").forEach(btn => {
            btn.addEventListener("click", function () {
                let studentId = this.getAttribute("data-id");
                if (confirm("Are you sure you want to delete this student from the class?")) {
                    fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: `student_id=${studentId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert("Student deleted successfully!");
                            this.closest("tr").remove(); // Remove the row from the table
                        } else {
                            alert("Failed to delete student.");
                        }
                    });
                }
            });
        });
    });

    //Mass delete
document.getElementById("select-all").addEventListener("change", function() {
    document.querySelectorAll(".select-student").forEach(cb => {
        cb.checked = this.checked;
    });
});

document.getElementById("delete-selected").addEventListener("click", function () {
    const selectedIds = Array.from(document.querySelectorAll(".select-student:checked"))
        .map(cb => cb.value);

    if (selectedIds.length === 0) {
        alert("No students selected.");
        return;
    }

    if (confirm("Are you sure you want to delete the selected students?")) {
        fetch("<?php echo $_SERVER['PHP_SELF']; ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `student_ids=${JSON.stringify(selectedIds)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Selected students deleted successfully!");
                selectedIds.forEach(id => {
                    const row = document.querySelector(`.select-student[value="${id}"]`).closest("tr");
                    row.remove();
                });
            } else {
                alert("Failed to delete selected students.");
            }
        });
    }
});
</script>
</body>
</html>