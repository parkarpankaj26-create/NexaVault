<?php

function addNotification($conn, $user_id, $message)
{
    mysqli_query($conn,
    "INSERT INTO notifications(user_id, message)
    VALUES('$user_id', '$message')");
}

?>