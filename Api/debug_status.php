<?php
include 'connection.php';
echo "--- Schema Check ---\n";
$res1 = $con->query("SHOW COLUMNS FROM tbl_members LIKE 'last_active'");
echo "tbl_members last_active: " . ($res1->num_rows > 0 ? "Exists" : "MISSING") . "\n";

$res2 = $con->query("SHOW COLUMNS FROM tbl_marriage_profiles LIKE 'last_active'");
echo "tbl_marriage_profiles last_active: " . ($res2->num_rows > 0 ? "Exists" : "MISSING") . "\n";

echo "\n--- Value Check ---\n";
$res3 = $con->query("SELECT id, last_active, NOW() as server_now FROM tbl_members ORDER BY last_active DESC LIMIT 1");
print_r($res3->fetch_assoc());

$res4 = $con->query("SELECT id, user_id, last_active, NOW() as server_now FROM tbl_marriage_profiles ORDER BY last_active DESC LIMIT 1");
print_r($res4->fetch_assoc());
?>
