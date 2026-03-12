<?php
// migration_add_chat_platform.php
include 'connection.php';

echo "Starting migration: Adding chat_platform column to tbl_messages...<br>";

// 1. Add column if it doesn't exist
$check = $con->query("SHOW COLUMNS FROM tbl_messages LIKE 'chat_platform'");
if ($check->num_rows == 0) {
    if ($con->query("ALTER TABLE tbl_messages ADD COLUMN chat_platform VARCHAR(20) DEFAULT 'marriage' AFTER message")) {
        echo "✅ Created column 'chat_platform'.<br>";
    } else {
        echo "❌ Error creating column: " . $con->error . "<br>";
        exit;
    }
} else {
    echo "ℹ️ Column 'chat_platform' already exists.<br>";
}

// 2. Update existing records to 'marriage'
if ($con->query("UPDATE tbl_messages SET chat_platform = 'marriage' WHERE chat_platform IS NULL OR chat_platform = ''")) {
    echo "✅ Updated existing messages to 'marriage'.<br>";
}

echo "Migration completed successfully!";
?>
