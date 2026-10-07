<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   CHECK FILE ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Request");
}

$id = (int) $_GET['id'];


/* =====================================================
   GET ONLY CURRENT USER'S FILE
===================================================== */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE id='$id'
     AND user_id='$user_id'
     AND deleted=0
     LIMIT 1"
);

if (!$result || mysqli_num_rows($result) == 0) {
    die("File not found or access denied.");
}

$file = mysqli_fetch_assoc($result);


/* =====================================================
   FILE NAME / PATH
===================================================== */

$storedFileName = basename($file['file_name']);

$originalFileName = basename(
    $file['original_name']
);

$filePath = __DIR__ . "/uploads/" . $storedFileName;


/* =====================================================
   CHECK FILE EXISTS
===================================================== */

if (!file_exists($filePath) || !is_file($filePath)) {
    die("File does not exist on the server.");
}


/* =====================================================
   UPDATE DOWNLOAD COUNT
===================================================== */

mysqli_query(
    $conn,
    "UPDATE files
     SET downloaded = downloaded + 1
     WHERE id='$id'
     AND user_id='$user_id'"
);


/* =====================================================
   DOWNLOAD HISTORY
===================================================== */

$safeFileName = mysqli_real_escape_string(
    $conn,
    $originalFileName
);

mysqli_query(
    $conn,
    "INSERT INTO download_history
    (user_id, file_name)
    VALUES
    ('$user_id', '$safeFileName')"
);


/* =====================================================
   ACTIVITY LOG
===================================================== */

if (file_exists("includes/activity.php")) {

    include 'includes/activity.php';

    logActivity(
        $conn,
        $user_id,
        "Downloaded file: " . $originalFileName
    );
}


/* =====================================================
   MIME TYPE
===================================================== */

$finfo = finfo_open(FILEINFO_MIME_TYPE);

$mimeType = finfo_file(
    $finfo,
    $filePath
);

finfo_close($finfo);

if (!$mimeType) {
    $mimeType = "application/octet-stream";
}


/* =====================================================
   DOWNLOAD HEADERS
===================================================== */

$fileSize = filesize($filePath);

header("Content-Description: File Transfer");

header("Content-Type: " . $mimeType);

header(
    'Content-Disposition: attachment; filename="' .
    str_replace('"', '', $originalFileName) .
    '"'
);

header("Content-Transfer-Encoding: binary");

header("Expires: 0");

header("Cache-Control: must-revalidate");

header("Pragma: public");

header("Content-Length: " . $fileSize);


/* =====================================================
   SEND FILE
===================================================== */

if (ob_get_level()) {
    ob_end_clean();
}

flush();

readfile($filePath);

exit();

?>