<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int)$_SESSION['user_id'];

$result = mysqli_query(
    $conn,
    "SELECT id, file_name, original_name, file_hash
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0"
);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
}

$updated = 0;
$missing = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $storedName = basename($row['file_name']);

    $filePath =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        "uploads" .
        DIRECTORY_SEPARATOR .
        $storedName;

    /* Hash already exists */
    if (!empty($row['file_hash'])) {
        continue;
    }

    /* Physical file missing */
    if (!file_exists($filePath) || !is_file($filePath)) {
        $missing++;
        continue;
    }

    /* Generate SHA-256 */
    $hash = hash_file('sha256', $filePath);

    if ($hash === false) {
        $missing++;
        continue;
    }

    $hashSafe = mysqli_real_escape_string(
        $conn,
        $hash
    );

    mysqli_query(
        $conn,
        "UPDATE files
         SET file_hash='$hashSafe'
         WHERE id=" . (int)$row['id'] . "
         AND user_id='$user_id'"
    );

    if (mysqli_affected_rows($conn) > 0) {
        $updated++;
    }
}

echo "
<!DOCTYPE html>
<html>
<head>
<title>NexaVault Hash Update</title>
<style>
body{
    font-family:Segoe UI,Arial,sans-serif;
    background:#020817;
    color:#e8eefc;
    text-align:center;
    padding:80px 20px;
}
.box{
    max-width:600px;
    margin:auto;
    padding:35px;
    border-radius:20px;
    background:#0b1934;
}
h2{color:#60a5fa;}
p{color:#94a3b8;}
</style>
</head>
<body>
<div class='box'>
<h2>SHA-256 Update Complete</h2>
<p>Files updated: <strong>$updated</strong></p>
<p>Files missing on server: <strong>$missing</strong></p>
<p>You can now use File Integrity Verification.</p>
</div>
</body>
</html>
";
?>