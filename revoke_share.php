<?php

session_start();

include 'includes/db.php';


// ==========================================
// CHECK LOGIN
// ==========================================

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}


// ==========================================
// CHECK FILE ID
// ==========================================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    die("Invalid Request");

}

$id = (int)$_GET['id'];

$user_id = (int)$_SESSION['user_id'];


// ==========================================
// REVOKE SHARE LINK
// ==========================================

$sql = "UPDATE files
        SET
            is_shared = '0',
            share_token = NULL,
            share_password = NULL,
            link_used = '0',
            share_downloads = '0',
            expires_at = NULL
        WHERE id = '$id'
        AND user_id = '$user_id'";

$result = mysqli_query($conn, $sql);


if (!$result) {

    die("Error while revoking link: " . mysqli_error($conn));

}


// ==========================================
// REDIRECT
// ==========================================

header("Location: shared_files.php?revoked=1");
exit();

?>