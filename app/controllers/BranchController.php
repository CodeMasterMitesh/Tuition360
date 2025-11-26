<?php
if (!defined('APP_INIT')) { http_response_code(403); exit('Forbidden'); }
// app/controllers/BranchController.php
require_once __DIR__ . '/../../config/db.php';
class BranchController {
    public static function getAll() {
        global $conn;
        $result = mysqli_query($conn, "SELECT * FROM branches");
        $branches = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $branches[] = $row;
        }
        return $branches;
    }
    public static function create($name, $address) {
        global $conn;
        $stmt = mysqli_prepare($conn, "INSERT INTO branches (company_id, name, address) VALUES (1, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $name, $address);
        return mysqli_stmt_execute($stmt);
    }
}
?>
