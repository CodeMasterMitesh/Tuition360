<?php
// Minimal test script for Attendance controller functions
chdir(__DIR__ . '/../');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/controllers/AttendanceController.php';

echo "Running minimal attendance tests...\n";

try {
    $all = AttendanceController::getAll();
    echo "Total attendance records: " . count($all) . "\n";
    $byBranch = AttendanceController::getAll(null, 1);
    echo "Records for branch_id=1: " . count($byBranch) . "\n";
    $byType = AttendanceController::getAll('faculty', null);
    echo "Faculty records: " . count($byType) . "\n";
    echo "Test fetch single record (if exists)...\n";
    if (!empty($all)) {
        $id = intval($all[0]['id']);
        $stmt = mysqli_prepare($conn, "SELECT * FROM attendance WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        echo "Fetched record id={$id}: " . ($row ? 'OK' : 'NOT FOUND') . "\n";
    } else {
        echo "No attendance records to test single fetch.\n";
    }
    echo "Attendance minimal tests completed.\n";
} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
}

?>