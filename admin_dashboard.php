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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = intval($_POST['user_id']);
    $update_query = "UPDATE teach_user SET password = '" . DEFAULT_PASSWORD_HASH . "', password_changed = 0 WHERE id = $user_id";
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
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="teacher_dashboard.php">Teacher Dashboard</a></li>
                    <li><a href="logout.php" class="logout-button">Logout</a></li>
                </ul>
            </nav>
        </header>

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
                            <th>Grade Level</th>
                            <th>Section</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']); ?></td>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= htmlspecialchars($user['first_name']); ?></td>
                                <td><?= htmlspecialchars($user['last_name']); ?></td>
                                <td><?= htmlspecialchars($user['grade_level']); ?></td>
                                <td><?= htmlspecialchars($user['section']); ?></td>
                                <td><?= htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button>Modify</button>
                                        <div class="dropdown-content">
                                            <form action="admin_dashboard.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                <button type="submit" name="reset_password">Reset</button>
                                            </form>
                                            <form action="admin_dashboard.php" method="POST">
                                                <input type="hidden" name="user_id" value="<?= $user['id']; ?>">
                                                <input type="password" name="new_password" placeholder="New Password" required>
                                                <button type="submit" name="edit_password">Edit</button>
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
</body>
</html>