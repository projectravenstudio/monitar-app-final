<?php
session_start();

// Teacher only
if (!isset($_SESSION['user']) || $_SESSION['role'] != 'teacher') {
    header("Location: index.php");
    exit;
}

// database connection
require_once 'php/config.php';

// teacher details
$teacher_username = $_SESSION['user'];
$teacher_query = "SELECT grade_level, section FROM teach_user WHERE username = ?";
$teacher_stmt = $conn->prepare($teacher_query);
$teacher_stmt->bind_param("s", $teacher_username);
$teacher_stmt->execute();
$teacher_result = $teacher_stmt->get_result();

if ($teacher_result->num_rows > 0) {
    $teacher_data = $teacher_result->fetch_assoc();
    $teacher_grade_level = $teacher_data['grade_level'];
    $teacher_section = $teacher_data['section'];
} else {
    echo "Error: Teacher information not found.";
    exit;
}

// Update teacher info when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_grade_level = $_POST['grade_level'];
    $new_section = $_POST['section'];

    // Update query
    $update_query = "UPDATE teach_user SET grade_level = ?, section = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sss", $new_grade_level, $new_section, $teacher_username);

    if ($update_stmt->execute()) {
        // back to the dashboard after successful update
        header("Location: teacher_dashboard.php");
        exit;
    } else {
        echo "Error: Unable to update teacher information.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Teacher Info</title>
    <link rel="stylesheet" href="css/editteach.css">
</head>
<body>
    <div class="dashboard">
        <!-- Header Section -->
        <header class="header">
            <h1>Edit Teacher Information</h1>
            <p>Welcome, <strong><?php echo htmlspecialchars($teacher_username); ?></strong></p>
        </header>

        <!-- Edit Teacher Form Section -->
        <section class="section edit-teacher-info">
            <form action="edit_teacher_info.php" method="POST">
                <!-- Grade Level Dropdown -->
                <label for="grade_level">Grade Level:</label>
                <select id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select Grade Level</option>
                    <option value="Grade 7" <?php if ($teacher_grade_level === 'Grade 7') echo 'selected'; ?>>Grade 7</option>
                    <option value="Grade 8" <?php if ($teacher_grade_level === 'Grade 8') echo 'selected'; ?>>Grade 8</option>
                    <option value="Grade 9" <?php if ($teacher_grade_level === 'Grade 9') echo 'selected'; ?>>Grade 9</option>
                    <option value="Grade 10" <?php if ($teacher_grade_level === 'Grade 10') echo 'selected'; ?>>Grade 10</option>
                    <option value="Grade 11" <?php if ($teacher_grade_level === 'Grade 11') echo 'selected'; ?>>Grade 11</option>
                    <option value="Grade 12" <?php if ($teacher_grade_level === 'Grade 12') echo 'selected'; ?>>Grade 12</option>
                </select>

                <!-- Section Dropdown -->
                <label for="section">Section:</label>
                <select id="section" name="section" required>
                    <option value="" disabled selected>Select Section</option>
                </select>

                <button type="submit" class="btn">Save Changes</button>
            </form>
        </section>

        <!-- Footer Section -->
        <footer class="footer">
            <a href="teacher_dashboard.php" class="btn">Back to Dashboard</a>
        </footer>
    </div>

    <script>
        // sections for each grade level
        const sections = {
            "Grade 7": ["Venus", "Earth", "Mars", "Jupiter", "Mercury"],
            "Grade 8": ["Amethyst", "Diamond", "Ruby", "Emerald", "Pearl"],
            "Grade 9": ["Gold", "Silver", "Cobalt", "Nickel", "Iron"],
            "Grade 10": ["Aries", "Leo", "Pisces", "Virgo", "Gemini"],
            "Grade 11": ["Del Pilar", "Malvar", "Bonifacio", "Agoncillo", "Rizal"],
            "Grade 12": ["Zamora", "Quezon", "Aguinaldo", "Jacinto", "Mabini"]
        };

        const gradeSelect = document.querySelector('#grade_level');
        const sectionSelect = document.querySelector('#section');
        const currentSection = "<?php echo $teacher_section; ?>"; //current section from PHP

        // Populate sections based on the selected grade level
        gradeSelect.addEventListener('change', function () {
            const gradeLevel = gradeSelect.value;
            const availableSections = sections[gradeLevel] || [];
            sectionSelect.innerHTML = "<option value='' disabled selected>Select Section</option>";
            availableSections.forEach(section => {
                const option = document.createElement('option');
                option.value = section;
                option.textContent = section;
                if (section === currentSection) {
                    option.selected = true; //Preselecteed current section
                }
                sectionSelect.appendChild(option);
            });
        });

        // Trigger the change event for section oin load
        gradeSelect.dispatchEvent(new Event('change'));
    </script>
</body>
</html>
