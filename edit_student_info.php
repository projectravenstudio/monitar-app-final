<?php
session_start();
require_once 'php/config.php';

// Redirect if not a student
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit;
}

$student_username = $_SESSION['user'];

// Fetch student details
$query = "SELECT first_name, last_name, grade_level, section FROM stud_tbl WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $student_username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    exit("Error: Student information not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $grade_level = trim($_POST['grade_level']);
    $section = trim($_POST['section']);

    // Update student info
    $update_query = "UPDATE stud_tbl SET first_name = ?, last_name = ?, grade_level = ?, section = ? WHERE username = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("sssss", $first_name, $last_name, $grade_level, $section, $student_username);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Information updated successfully.";
        header("Location: student_dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "Error updating information. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Info</title>
    <link rel="stylesheet" href="css/editstud.css">
</head>
<body>
    <div class="container">
        <h1>Edit Student Information</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form action="edit_student_info.php" method="POST">
    <label>Grade Level</label>
    <select name="grade_level" id="grade_level" required>
        <option value="" disabled>Select Grade Level</option>
        <?php
        $grades = ["Grade 7", "Grade 8", "Grade 9", "Grade 10", "Grade 11", "Grade 12"];
        foreach ($grades as $grade) {
            $selected = ($student['grade_level'] === $grade) ? "selected" : "";
            echo "<option value='$grade' $selected>$grade</option>";
        }
        ?>
    </select>

    <label>Section</label>
    <select name="section" id="section" required>
        <option value="" disabled>Select Section</option>
    </select>

    <button type="submit">Save Changes</button>
</form>

    </div>

    <script>
        const sectionSelect = document.getElementById("section");
        const gradeSelect = document.getElementById("grade_level");

        const sections = {
            "Grade 7": ["Venus", "Earth", "Mars", "Jupiter", "Mercury"],
            "Grade 8": ["Amethyst", "Diamond", "Ruby", "Emerald", "Pearl"],
            "Grade 9": ["Gold", "Silver", "Cobalt", "Nickel", "Iron"],
            "Grade 10": ["Aries", "Leo", "Pisces", "Virgo", "Gemini"],
            "Grade 11": ["Del Pilar", "Malvar", "Bonifacio", "Agoncillo", "Rizal"],
            "Grade 12": ["Zamora", "Quezon", "Aguinaldo", "Jacinto", "Mabini"]
        };

        function updateSections() {
            const selectedGrade = gradeSelect.value;
            sectionSelect.innerHTML = '<option value="" disabled selected>Select Section</option>';

            if (sections[selectedGrade]) {
                sections[selectedGrade].forEach(section => {
                    let option = document.createElement("option");
                    option.value = section;
                    option.textContent = section;
                    if (section === "<?php echo $student['section']; ?>") {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
        }

        gradeSelect.addEventListener("change", updateSections);
        updateSections();
    </script>
</body>
</html>
