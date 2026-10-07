<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/db.php';

echo "DB FILE LOADED<br>";

if ($conn) {
    echo "DATABASE CONNECTION WORKING<br>";
} else {
    echo "DATABASE CONNECTION FAILED<br>";
}

$result = mysqli_query($conn, "SELECT 1");

if ($result) {
    echo "MYSQL QUERY WORKING";
} else {
    echo "MYSQL QUERY FAILED: " . mysqli_error($conn);
}

exit();
?>