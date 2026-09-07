<?php
$con = mysqli_connect("localhost", "u941015828_sadhuvandna", "Sadhuvandna7832%^", "u941015828_sadhuvandna");
$res = mysqli_query($con, "SHOW TABLES");
$out = [];
while($row = mysqli_fetch_row($res)) { $out[] = $row[0]; }
file_put_contents("schema_tables.json", json_encode($out, JSON_PRETTY_PRINT));
?>
