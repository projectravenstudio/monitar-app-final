<?php
require_once 'php/config.php';

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$violation_id = $data['violation_id'];
$new_description = $data['new_description'];

$query = "UPDATE violations SET violation_description = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("si", $new_description, $violation_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Failed to update violation."]);
}
?>
