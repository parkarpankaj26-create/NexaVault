<?php

session_start();
include 'includes/db.php';

if($_SESSION['role']!="admin")
{
    die("Access Denied");
}

$id=$_GET['id'];

$file=mysqli_fetch_assoc(mysqli_query($conn,
"SELECT * FROM files WHERE id='$id'"));

if($file)
{
    if(file_exists("uploads/".$file['file_name']))
    {
        unlink("uploads/".$file['file_name']);
    }

    mysqli_query($conn,
    "DELETE FROM files WHERE id='$id'");
}

header("Location: admin_dashboard.php");

?>