<?php
// migration_add_calls_platform.php
include 'connection.php';

echo "Starting migration: Adding platform column to tbl_calls...<br>";

$check = $con->query("SHOW COLUMNS FROM tbl_calls LIKE 'platform'");
if ($check->num_rows == 0) {
    if ($con->query("ALTER TABLE tbl_calls ADD COLUMN platform VARCHAR(20) DEFAULT 'marriage' AFTER type")) {
        echo "✅ Created column 'platform'.<br>";
    }
    else {
        echo "❌ Error creating column: " . $con->error . "<br>";
        exit;
    }
}
else {
    echo "ℹ️ Column 'platform' already exists.<br>";
}

echo "Migration completed successfully!";
?>
