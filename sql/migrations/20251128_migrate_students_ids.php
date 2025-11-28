<?php
// Migration script: populate batch_assignment_students from batch_assignments.students_ids
// Run: php 20251128_migrate_students_ids.php (from this directory)

chdir(__DIR__);
require_once __DIR__ . '/../../config/db.php';

header_remove(); // if run via CLI avoid headers

echo "Starting migration: populate batch_assignment_students from batch_assignments.students_ids\n";

$conn = isset($conn) ? $conn : (function() {
    // attempt to require config/db.php manually if not set
    if (file_exists(__DIR__ . '/../../config/db.php')) {
        require __DIR__ . '/../../config/db.php';
        global $conn;
        return $conn;
    }
    throw new Exception('Database config not found');
})();

$sel = "SELECT id, students_ids FROM batch_assignments WHERE students_ids IS NOT NULL AND students_ids <> '' AND students_ids <> '[]'";
$res = mysqli_query($conn, $sel);
if (!$res) { echo "Failed to select assignments: " . mysqli_error($conn) . "\n"; exit(1); }

$checkStmt = mysqli_prepare($conn, "SELECT 1 FROM batch_assignment_students WHERE assignment_id = ? AND student_id = ? LIMIT 1");
$insStmt = mysqli_prepare($conn, "INSERT INTO batch_assignment_students (assignment_id, student_id) VALUES (?, ?)");

$totalRows = 0; $inserted = 0; $skipped = 0; $errors = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $totalRows++;
    $aid = intval($row['id']);
    $json = $row['students_ids'];
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        // try to handle comma separated fallback
        $arr = preg_split('/\s*,\s*/', trim($json));
    }
    foreach ($arr as $sidRaw) {
        $sid = intval($sidRaw);
        if ($sid <= 0) { $skipped++; continue; }
        // check existence
        mysqli_stmt_bind_param($checkStmt, 'ii', $aid, $sid);
        if (!mysqli_stmt_execute($checkStmt)) { $errors++; continue; }
        $cres = mysqli_stmt_get_result($checkStmt);
        if ($cres && mysqli_fetch_assoc($cres)) { $skipped++; continue; }
        // insert
        mysqli_stmt_bind_param($insStmt, 'ii', $aid, $sid);
        if (mysqli_stmt_execute($insStmt)) { $inserted++; } else { $errors++; }
    }
}

echo "Migration complete. Assignments scanned: $totalRows, inserted: $inserted, skipped(existing/invalid): $skipped, errors: $errors\n";

echo "Next step (recommended): optionally add a UNIQUE index to prevent duplicates:\n";
echo "  ALTER TABLE batch_assignment_students ADD UNIQUE KEY uniq_assignment_student (assignment_id, student_id);\n";

echo "Done.\n";

?>