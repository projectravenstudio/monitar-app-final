<?php
include 'php/config.php';

if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Fetch violations with ID included
    $query = "SELECT id, violation_description, violation_date, violation_time FROM violations WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    $violations = [];
    while ($row = $result->fetch_assoc()) {
        // Convert time to 12-hour format
        $row['violation_time'] = date("h:i A", strtotime($row['violation_time']));
        $violations[] = $row;
    }

    echo json_encode($violations);
}
?>
