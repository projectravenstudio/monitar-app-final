<?php
require_once 'php/config.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$violation_id = $data['violation_id'];

$query = "DELETE FROM violations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $violation_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Failed to delete violation."]);
}
?>
