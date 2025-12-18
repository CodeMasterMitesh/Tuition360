<?php
// Run this script once to add profile columns to users table
require_once __DIR__ . '/../config/db.php';

$queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS photo VARCHAR(255) NULL AFTER mobile",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER photo",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL AFTER address",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other') NULL AFTER date_of_birth"
];

$success = true;
foreach ($queries as $query) {
    if (!mysqli_query($conn, $query)) {
        echo "Failed: " . $query . " - Error: " . mysqli_error($conn) . "\n";
        $success = false;
    } else {
        echo "Success: " . $query . "\n";
    }
}

if ($success) {
    echo "\nAll migrations completed successfully!\n";
} else {
    echo "\nSome migrations failed. Check errors above.\n";
}
