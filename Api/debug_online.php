<?php
include '../php/connection.php';
echo "--- tbl_members ---\n";
$res = $con->query("DESCRIBE tbl_members");
while($row = $res->fetch_assoc()) if($row['Field'] == 'last_active') print_r($row);
$res = $con->query("SELECT id, last_active, NOW() as current_now, UNIX_TIMESTAMP(NOW()) as ts_now, UNIX_TIMESTAMP(last_active) as ts_la FROM tbl_members ORDER BY last_active DESC LIMIT 1");
print_r($res->fetch_assoc());

echo "\n--- tbl_marriage_profiles ---\n";
$res = $con->query("DESCRIBE tbl_marriage_profiles");
while($row = $res->fetch_assoc()) if($row['Field'] == 'last_active') print_r($row);
$res = $con->query("SELECT id, last_active, NOW() as current_now, UNIX_TIMESTAMP(NOW()) as ts_now, UNIX_TIMESTAMP(last_active) as ts_la FROM tbl_marriage_profiles ORDER BY last_active DESC LIMIT 1");
print_r($res->fetch_assoc());
?>
