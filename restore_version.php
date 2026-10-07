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
    header("Location: dashboard.php");
    exit();
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

$file_id = (int) $version['file_id'];
$version_file_name = $version['file_name'];
$version_number = (int) $version['version_number'];
$original_name = $version['original_name'];


/* =====================================================
   CHECK VERSION FILE EXISTS
===================================================== */

$versionPath = __DIR__ . "/uploads/" . $version_file_name;

if (!file_exists($versionPath)) {
    die("This version file is no longer available.");
}


/* =====================================================
   GET CURRENT FILE
===================================================== */

$currentQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE id='$file_id'
     AND user_id='$user_id'
     LIMIT 1"
);

if (!$currentQuery || mysqli_num_rows($currentQuery) == 0) {
    die("Original file not found.");
}

$currentFile = mysqli_fetch_assoc($currentQuery);

$current_file_name = $currentFile['file_name'];


/* =====================================================
   ALREADY CURRENT?
===================================================== */

if ($current_file_name === $version_file_name) {

    echo "<script>
            alert('This version is already the current version.');
            window.location.href='file_versions.php?id=$file_id';
          </script>";

    exit();
}


/* =====================================================
   MARK ALL VERSIONS AS NOT CURRENT
===================================================== */

$resetVersions = mysqli_query(
    $conn,
    "UPDATE file_versions
     SET is_current=0
     WHERE file_id='$file_id'
     AND user_id='$user_id'"
);

if (!$resetVersions) {
    die("Unable to update version status.");
}


/* =====================================================
   MARK SELECTED VERSION AS CURRENT
===================================================== */

$restoreVersion = mysqli_query(
    $conn,
    "UPDATE file_versions
     SET is_current=1
     WHERE id='$version_id'
     AND file_id='$file_id'
     AND user_id='$user_id'"
);

if (!$restoreVersion) {
    die("Unable to restore this version.");
}


/* =====================================================
   UPDATE MAIN FILE RECORD
===================================================== */

$new_file_name = mysqli_real_escape_string(
    $conn,
    $version['file_name']
);

$new_original_name = mysqli_real_escape_string(
    $conn,
    $version['original_name']
);

$new_file_size = (int) $version['file_size'];

$new_file_type = mysqli_real_escape_string(
    $conn,
    $version['file_type']
);


$updateMainFile = mysqli_query(
    $conn,
    "UPDATE files
     SET
        file_name='$new_file_name',
        original_name='$new_original_name',
        file_size='$new_file_size',
        file_type='$new_file_type'
     WHERE id='$file_id'
     AND user_id='$user_id'"
);

if (!$updateMainFile) {
    die("Version restored, but main file update failed.");
}


/* =====================================================
   ACTIVITY LOG
===================================================== */

include 'includes/activity.php';

logActivity(
    $conn,
    $user_id,
    "Restored Version $version_number of: $original_name"
);


/* =====================================================
   NOTIFICATION
===================================================== */

if (file_exists("includes/notification.php")) {

    include 'includes/notification.php';

    addNotification(
        $conn,
        $user_id,
        "♻️ Version $version_number restored: $original_name"
    );
}


/* =====================================================
   SUCCESS
===================================================== */

echo "<script>
        alert('✅ Version $version_number restored successfully.');
        window.location.href='file_versions.php?id=$file_id';
      </script>";

exit();

?>