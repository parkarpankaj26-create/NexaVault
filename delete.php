<?php
include 'includes/db.php';
include 'includes/activity.php';

if(isset($_GET['id']))
{
    $id = $_GET['id'];

    $result = mysqli_query($conn,
    "SELECT * FROM files WHERE id='$id'");

    $file = mysqli_fetch_assoc($result);

    if($file)
    {
        $user_id = $file['user_id'];

        logActivity(
            $conn,
            $user_id,
            "Moved file to Recycle Bin: ".$file['original_name']
        );

        mysqli_query($conn,
        "UPDATE files
        SET deleted=1
        WHERE id='$id'");
    }

    header("Location: dashboard.php");
    exit();
}
?>