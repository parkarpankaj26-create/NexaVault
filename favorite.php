<?php

session_start();


// ===============================
// CHECK LOGIN
// ===============================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// ===============================
// DATABASE
// ===============================

require_once __DIR__ . '/includes/db.php';

$user_id = (int) $_SESSION['user_id'];


// ===============================
// GET FILE ID
// ===============================

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = (int) $_GET['id'];


// ===============================
// GET CURRENT FAVORITE STATUS
// ===============================

$query = mysqli_query(
    $conn,
    "SELECT favorite
     FROM files
     WHERE id='$file_id'
     AND user_id='$user_id'
     AND deleted=0
     LIMIT 1"
);


// File does not belong to this user
if (!$query || mysqli_num_rows($query) == 0) {
    header("Location: dashboard.php");
    exit();
}


$file = mysqli_fetch_assoc($query);

$currentFavorite = (int) ($file['favorite'] ?? 0);


// ===============================
// TOGGLE FAVORITE
// ===============================

$newFavorite = ($currentFavorite == 1) ? 0 : 1;


$update = mysqli_query(
    $conn,
    "UPDATE files
     SET favorite='$newFavorite'
     WHERE id='$file_id'
     AND user_id='$user_id'"
);


// ===============================
// REDIRECT
// ===============================

header("Location: dashboard.php");
exit();

?>