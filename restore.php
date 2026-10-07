<?php
session_start();
include 'includes/db.php';
include 'includes/activity.php';

if(isset($_GET['id']))
{
    $id = intval($_GET['id']);

    $result = mysqli_query($conn,
    "SELECT * FROM files WHERE id='$id'");

    $file = mysqli_fetch_assoc($result);

    if($file)
    {
        mysqli_query($conn,
        "UPDATE files
        SET deleted=0
        WHERE id='$id'");

        logActivity(
            $conn,
            $_SESSION['user_id'],
            "Restored file: ".$file['original_name']
        );
    }

    header("Location: recycle_bin.php");
    exit();
}
?>