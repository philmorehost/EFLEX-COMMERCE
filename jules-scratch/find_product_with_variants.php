<?php
require_once 'includes/db_connect.php';

$sql = "SELECT product_id FROM product_variants LIMIT 1";
$result = $mysqli->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo $row['product_id'];
} else {
    echo "0";
}