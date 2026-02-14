<?php
include "connection.php";

$result = mysqli_query($con, "SELECT news_description FROM tbl_news LIMIT 1");

$row = mysqli_fetch_assoc($result);

echo $row['news_description'];

?>
