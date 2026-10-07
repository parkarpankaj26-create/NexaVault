<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int)$_SESSION['user_id'];


/* =====================================================
   ROOM ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Room.");
}

$room_id = (int)$_GET['id'];


/* =====================================================
   GET ROOM
===================================================== */

$roomQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM secure_rooms
     WHERE id='$room_id'
     AND status='active'
     LIMIT 1"
);

if (!$roomQuery || mysqli_num_rows($roomQuery) === 0) {
    die("Room not found.");
}

$room = mysqli_fetch_assoc($roomQuery);

$owner_id = (int)$room['owner_id'];


/* =====================================================
   CHECK USER PERMISSION
===================================================== */

$memberQuery = mysqli_query(
    $conn,
    "SELECT permission
     FROM room_members
     WHERE room_id='$room_id'
     AND user_id='$user_id'
     LIMIT 1"
);

if (!$memberQuery || mysqli_num_rows($memberQuery) === 0) {
    die("You don't have access to this room.");
}

$member = mysqli_fetch_assoc($memberQuery);

$permission = $member['permission'];


/* Only upload/manage can add files */

if (
    $user_id !== $owner_id &&
    $permission !== 'upload' &&
    $permission !== 'manage'
) {
    die("You don't have permission to add files.");
}


/* =====================================================
   ADD FILE TO ROOM
===================================================== */

$message = "";
$error = "";

if (isset($_POST['add_file'])) {

    $file_id = isset($_POST['file_id'])
        ? (int)$_POST['file_id']
        : 0;

    if ($file_id <= 0) {

        $error = "Please select a file.";

    } else {

        /* Make sure file belongs to current user */

        $fileQuery = mysqli_query(
            $conn,
            "SELECT id, original_name
             FROM files
             WHERE id='$file_id'
             AND user_id='$user_id'
             AND deleted=0
             LIMIT 1"
        );

        if (
            !$fileQuery ||
            mysqli_num_rows($fileQuery) === 0
        ) {

            $error = "File not found or access denied.";

        } else {

            $selectedFile =
                mysqli_fetch_assoc($fileQuery);


            /* Check duplicate */

            $existing = mysqli_query(
                $conn,
                "SELECT id
                 FROM room_files
                 WHERE room_id='$room_id'
                 AND file_id='$file_id'
                 LIMIT 1"
            );


            if (
                $existing &&
                mysqli_num_rows($existing) > 0
            ) {

                $error =
                    "This file is already added to the room.";

            } else {

                $insert = mysqli_query(
                    $conn,
                    "INSERT INTO room_files
                    (
                        room_id,
                        file_id,
                        uploaded_by
                    )
                    VALUES
                    (
                        '$room_id',
                        '$file_id',
                        '$user_id'
                    )"
                );


                if ($insert) {

                    /* Activity */

                    $safeActivity =
                        mysqli_real_escape_string(
                            $conn,
                            "Added file to room: " .
                            $selectedFile['original_name']
                        );

                    mysqli_query(
                        $conn,
                        "INSERT INTO room_activity
                        (
                            room_id,
                            user_id,
                            activity
                        )
                        VALUES
                        (
                            '$room_id',
                            '$user_id',
                            '$safeActivity'
                        )"
                    );

                    $message =
                        "File added to Secure Room successfully.";

                } else {

                    $error =
                        "Unable to add file to room.";
                }
            }
        }
    }
}


/* =====================================================
   GET USER FILES
===================================================== */

$filesQuery = mysqli_query(
    $conn,
    "SELECT
        f.id,
        f.original_name,
        f.file_type,
        f.file_size
     FROM files f
     WHERE f.user_id='$user_id'
     AND f.deleted=0
     AND f.id NOT IN (
         SELECT file_id
         FROM room_files
         WHERE room_id='$room_id'
     )
     ORDER BY f.id DESC"
);

$availableFiles = [];

if ($filesQuery) {

    while ($row = mysqli_fetch_assoc($filesQuery)) {
        $availableFiles[] = $row;
    }
}


$roomName =
    htmlspecialchars(
        $room['room_name'],
        ENT_QUOTES
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Add File - <?php echo $roomName; ?>
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {

    min-height:100vh;

    margin:0;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37,99,235,.18),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(139,92,246,.15),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #020817,
            #07142f,
            #0a1838
        );

    color:#e8eefc;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

}


.add-file-page {

    max-width:850px;

    margin:auto;

    padding:50px 20px;

}


.add-file-card {

    padding:30px;

    border-radius:24px;

    background:
        linear-gradient(
            145deg,
            rgba(15,31,63,.96),
            rgba(7,20,43,.96)
        );

    border:
        1px solid
        rgba(120,155,220,.14);

    box-shadow:
        0 25px 65px
        rgba(0,0,0,.35);

}


.page-title {

    color:#ffffff;

    font-size:28px;

    font-weight:800;

}


.page-subtitle {

    color:#91a0bb;

}


.file-option {

    display:flex;

    align-items:center;

    gap:15px;

    padding:15px;

    margin-bottom:10px;

    border-radius:14px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(255,255,255,.06);

    transition:.2s ease;

}


.file-option:hover {

    border-color:
        rgba(96,165,250,.25);

    background:
        rgba(59,130,246,.06);

}


.file-option input {

    width:18px;

    height:18px;

}


.file-icon {

    width:42px;

    height:42px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:11px;

    color:#60a5fa;

    background:
        rgba(59,130,246,.10);

}


.file-name {

    color:#ffffff;

    font-size:13px;

    font-weight:650;

}


.file-meta {

    color:#7184a3;

    font-size:9px;

    margin-top:2px;

}


.btn-primary {

    border:none;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

}


.btn-outline-light {

    border-color:
        rgba(255,255,255,.15);

    color:#cbd5e1;

}


.empty-files {

    text-align:center;

    padding:50px 20px;

    color:#91a0bb;

}


.empty-files i {

    display:block;

    font-size:45px;

    margin-bottom:10px;

    color:#55739e;

}

</style>

</head>

<body>

<div class="add-file-page">

<div class="add-file-card">

<div class="d-flex
            justify-content-between
            align-items-start
            flex-wrap
            gap-3
            mb-4">

<div>

<h1 class="page-title">

<i class="bi bi-folder-plus"></i>

Add File to Room

</h1>

<p class="page-subtitle mb-0">

Room:
<strong>
<?php echo $roomName; ?>
</strong>

</p>

</div>


<a
href="room.php?id=<?php echo $room_id; ?>"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Back to Room

</a>

</div>


<?php if ($message !== "") { ?>

<div class="alert alert-success">

<i class="bi bi-check-circle-fill"></i>

<?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>


<?php if ($error !== "") { ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?php echo htmlspecialchars($error); ?>

</div>

<?php } ?>


<?php if (count($availableFiles) === 0) { ?>

<div class="empty-files">

<i class="bi bi-folder2-open"></i>

<h5>
No Available Files
</h5>

<p>
All your current files are already added to this room.
</p>

</div>

<?php } else { ?>

<form method="POST">

<div class="mb-4">

<label class="form-label fw-bold">

<i class="bi bi-files"></i>

Select a File

</label>


<?php foreach ($availableFiles as $available) { ?>

<?php

$availableName =
    htmlspecialchars(
        $available['original_name'],
        ENT_QUOTES
    );

$availableType =
    htmlspecialchars(
        $available['file_type'] ?? 'Unknown',
        ENT_QUOTES
    );

$availableSize =
    round(
        ((int)$available['file_size']) / 1024,
        2
    );

?>

<label class="file-option">

<input
type="radio"
name="file_id"
value="<?php echo (int)$available['id']; ?>"
required>


<div class="file-icon">

<i class="bi bi-file-earmark-fill"></i>

</div>


<div>

<div class="file-name">

<?php echo $availableName; ?>

</div>

<div class="file-meta">

<?php echo $availableType; ?>

&nbsp; • &nbsp;

<?php echo $availableSize; ?> KB

</div>

</div>

</label>

<?php } ?>

</div>


<button
type="submit"
name="add_file"
class="btn btn-primary w-100">

<i class="bi bi-folder-plus"></i>

Add Selected File

</button>

</form>

<?php } ?>


</div>

</div>

</body>

</html>