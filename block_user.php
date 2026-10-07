<?php
session_start();
include 'includes/db.php';
include 'includes/activity.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] != "admin")
{
    header("Location: login.php");
    exit();
}

if(isset($_GET['id']))
{
    $id = intval($_GET['id']);

    mysqli_query($conn,
    "UPDATE users
    SET status='blocked'
    WHERE id='$id'");

    logActivity(
        $conn,
        $_SESSION['user_id'],
        "Blocked User ID: ".$id
    );
}

header("Location: admin_dashboard.php");
exit();
?>