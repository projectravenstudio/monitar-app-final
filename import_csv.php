<?php
require_once 'php/config.php';

/**
 * Imports CSV data from the given file path into the database.
 *
 * Assumes the first row of the CSV file contains column headers matching
 * the column names in the 'records' table.
 *
 * @param string $filePath The path to the CSV file.
 * @param mysqli $conn     The MySQLi connection object.
 * @return bool            Returns true on success, false on failure.
 */
function importCsv($filePath, $conn) {
    if (($handle = fopen($filePath, "r")) !== false) {
        // Read header row to get column names
        $header = fgetcsv($handle, 1000, ",");
        if (!$header) {
            fclose($handle);
            return false;
        }
        
        // Build the prepared statement using header columns for the 'records' table
        $columns      = implode(", ", $header);
        $placeholders = implode(", ", array_fill(0, count($header), "?"));
        $query        = "INSERT INTO records ($columns) VALUES ($placeholders)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            fclose($handle);
            return false;
        }
        
        // Set all parameter types as string (adjust if needed)
        $types = str_repeat("s", count($header));
        
        // Helper function to pass parameters by reference
        function refValues($arr){
            $refs = [];
            foreach($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        
        // Loop through the file and insert each record
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $params = array_merge([$types], $data);
            call_user_func_array([$stmt, 'bind_param'], refValues($params));
            if (!$stmt->execute()) {
                fclose($handle);
                return false;
            }
        }
        fclose($handle);
        return true;
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $tmpName = $_FILES['csv_file']['tmp_name'];
    if (importCsv($tmpName, $conn)) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "CSV import failed."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
}
?>