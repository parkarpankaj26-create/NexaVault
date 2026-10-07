<?php
session_start();

include 'includes/db.php';
include 'includes/activity.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != "admin")
{
    die("Access Denied");
}

if(isset($_GET['id']))
{
    $id = intval($_GET['id']);

    // Get user details
    $user = mysqli_fetch_assoc(
        mysqli_query($conn,
        "SELECT * FROM users WHERE id='$id'")
    );

    if($user)
    {
        // Get all uploaded files of user
        $files = mysqli_query($conn,
        "SELECT * FROM files WHERE user_id='$id'");

        while($file = mysqli_fetch_assoc($files))
        {
            $path = "uploads/".$file['file_name'];

            if(file_exists($path))
            {
                unlink($path);
            }
        }

        // Delete all file records
        mysqli_query($conn,
        "DELETE FROM files WHERE user_id='$id'");

        // Activity Log
        logActivity(
            $conn,
            $_SESSION['user_id'],
            "Deleted User: ".$user['name']
        );

        // Delete user
        mysqli_query($conn,
        "DELETE FROM users WHERE id='$id'");
    }
}

header("Location: admin_dashboard.php");
exit();
?>
