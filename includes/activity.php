<?php
function logActivity($conn, $user_id, $activity)
{
    $activity = mysqli_real_escape_string($conn, $activity);

    mysqli_query($conn,
    "INSERT INTO activity_log(user_id, activity)
    VALUES('$user_id','$activity')");
}
?>