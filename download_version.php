<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   CHECK VERSION ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid version request.");
}

$version_id = (int) $_GET['id'];


/* =====================================================
   GET VERSION
===================================================== */

$query = mysqli_query(
    $conn,
    "SELECT *
     FROM file_versions
     WHERE id='$version_id'
     AND user_id='$user_id'
     LIMIT 1"
);

if (!$query || mysqli_num_rows($query) == 0) {
    die("Version not found or access denied.");
}

$version = mysqli_fetch_assoc($query);


/* =====================================================
   FILE PATH
===================================================== */

$fileName = $version['file_name'];

$filePath = __DIR__ . "/uploads/" . $fileName;


/* =====================================================
   CHECK FILE EXISTS
===================================================== */

if (!file_exists($filePath)) {
    die("This version file is no longer available on the server.");
}


/* =====================================================
   LOG ACTIVITY
===================================================== */

include 'includes/activity.php';

$activity = "Downloaded file version " .
            $version['version_number'] .
            ": " .
            $version['original_name'];

logActivity(
    $conn,
    $user_id,
    $activity
);


/* =====================================================
   DOWNLOAD
===================================================== */

$downloadName = basename(
    $version['original_name']
);

$contentType = $version['file_type'] ?: "application/octet-stream";

$fileSize = filesize($filePath);


header("Content-Description: File Transfer");

header("Content-Type: " . $contentType);

header(
    'Content-Disposition: attachment; filename="' .
    $downloadName .
    '"'
);

header("Content-Length: " . $fileSize);

header("Cache-Control: no-cache, must-revalidate");

header("Pragma: public");

header("Expires: 0");


readfile($filePath);

exit();

?>