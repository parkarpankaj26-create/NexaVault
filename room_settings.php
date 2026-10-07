<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   CHECK ROOM ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Room.");
}

$room_id = (int) $_GET['id'];


/* =====================================================
   GET ROOM - OWNER ONLY
===================================================== */

$roomQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM secure_rooms
     WHERE id='$room_id'
     AND owner_id='$user_id'
     LIMIT 1"
);

if (!$roomQuery || mysqli_num_rows($roomQuery) === 0) {
    die("You don't have permission to manage this room.");
}

$room = mysqli_fetch_assoc($roomQuery);

$error = "";
$success = "";


/* =====================================================
   UPDATE ROOM DETAILS
===================================================== */

if (isset($_POST['update_room'])) {

    $room_name = trim(
        $_POST['room_name'] ?? ''
    );

    $room_description = trim(
        $_POST['room_description'] ?? ''
    );


    if ($room_name === '') {

        $error = "Room name is required.";

    } elseif (strlen($room_name) > 150) {

        $error = "Room name is too long.";

    } else {

        $roomNameSafe =
            mysqli_real_escape_string(
                $conn,
                $room_name
            );

        $roomDescriptionSafe =
            mysqli_real_escape_string(
                $conn,
                $room_description
            );


        $update = mysqli_query(
            $conn,
            "UPDATE secure_rooms
             SET
                room_name='$roomNameSafe',
                room_description='$roomDescriptionSafe'
             WHERE id='$room_id'
             AND owner_id='$user_id'"
        );


        if ($update) {

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
                    'Updated Secure Room settings'
                )"
            );


            $room['room_name'] = $room_name;
            $room['room_description'] = $room_description;

            $success =
                "Room settings updated successfully.";

        } else {

            $error =
                "Unable to update room settings.";
        }
    }
}


/* =====================================================
   ARCHIVE ROOM
===================================================== */

if (isset($_POST['archive_room'])) {

    $archive = mysqli_query(
        $conn,
        "UPDATE secure_rooms
         SET status='archived'
         WHERE id='$room_id'
         AND owner_id='$user_id'"
    );


    if ($archive) {

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
                'Archived Secure Room'
            )"
        );


        header(
            "Location: secure_rooms.php"
        );

        exit();

    } else {

        $error =
            "Unable to archive room.";
    }
}


/* =====================================================
   DISPLAY
===================================================== */

$roomName =
    htmlspecialchars(
        $room['room_name'],
        ENT_QUOTES
    );

$roomDescription =
    htmlspecialchars(
        $room['room_description'] ?? '',
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
Room Settings - <?php echo $roomName; ?>
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

body {

    min-height: 100vh;

    margin: 0;

    color: #e8eefc;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

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

}


.settings-page {

    max-width: 900px;

    margin: auto;

    padding: 45px 20px 60px;

}


.settings-card {

    padding: 30px;

    border-radius: 22px;

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
        rgba(0,0,0,.30);

}


.page-label {

    color: #6ea1ff;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.8px;

}


.page-title {

    color: #ffffff;

    font-size: 30px;

    font-weight: 800;

}


.page-subtitle {

    color: #91a0bb;

}


.form-label {

    color: #dce6fa;

    font-weight: 650;

}


.form-control {

    min-height: 48px;

    border-radius: 12px;

    background:
        rgba(5,18,40,.85) !important;

    border:
        1px solid
        rgba(120,155,220,.18) !important;

    color: #ffffff !important;

}


.form-control:focus {

    border-color:
        #4d7cff !important;

    box-shadow:
        0 0 0 3px
        rgba(77,124,255,.10) !important;

}


.security-box {

    margin-top: 25px;

    padding: 18px;

    border-radius: 15px;

    background:
        rgba(16,185,129,.07);

    border:
        1px solid
        rgba(16,185,129,.14);

}


.security-box strong {

    color: #86efac;

}


.security-box small {

    display: block;

    margin-top: 3px;

    color: #7184a3;

}


.danger-zone {

    margin-top: 30px;

    padding: 20px;

    border-radius: 16px;

    background:
        rgba(239,68,68,.06);

    border:
        1px solid
        rgba(239,68,68,.15);

}


.danger-zone h5 {

    color: #fca5a5;

    font-weight: 750;

}


.danger-zone p {

    color: #8fa2c2;

    font-size: 12px;

}


.btn-primary {

    border: none;

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

    color: #cbd5e1;

}


</style>

</head>


<body>


<div class="settings-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="d-flex
            justify-content-between
            align-items-start
            flex-wrap
            gap-3
            mb-4">

<div>

<span class="page-label">
SECURE ROOM SETTINGS
</span>

<h1 class="page-title mb-1">

<i class="bi bi-gear-fill"></i>

Room Settings

</h1>

<p class="page-subtitle mb-0">

<?php echo $roomName; ?>

</p>

</div>


<a
href="room.php?id=<?php echo $room_id; ?>"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Back to Room

</a>

</div>


<!-- =====================================================
     MESSAGES
===================================================== -->

<?php if ($error !== "") { ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?php echo htmlspecialchars($error); ?>

</div>

<?php } ?>


<?php if ($success !== "") { ?>

<div class="alert alert-success">

<i class="bi bi-check-circle-fill"></i>

<?php echo htmlspecialchars($success); ?>

</div>

<?php } ?>


<!-- =====================================================
     SETTINGS
===================================================== -->

<div class="settings-card">


<form method="POST">


<div class="mb-4">

<label class="form-label">

<i class="bi bi-house-door-fill"></i>

Room Name

</label>

<input
type="text"
name="room_name"
class="form-control"
value="<?php echo $roomName; ?>"
maxlength="150"
required>

</div>


<div class="mb-4">

<label class="form-label">

<i class="bi bi-card-text"></i>

Room Description

</label>

<textarea
name="room_description"
class="form-control"
rows="5"
placeholder="Describe the purpose of this room..."
><?php echo $roomDescription; ?></textarea>

</div>


<button
type="submit"
name="update_room"
class="btn btn-primary">

<i class="bi bi-check-lg"></i>

Save Changes

</button>


</form>


<!-- =====================================================
     SECURITY STATUS
===================================================== -->

<div class="security-box">

<strong>

<i class="bi bi-shield-fill-check"></i>

Secure Room Protection Active

</strong>

<small>

Owner-only settings • Member permissions •
Room activity tracking

</small>

</div>


<!-- =====================================================
     DANGER ZONE
===================================================== -->

<div class="danger-zone">

<h5>

<i class="bi bi-exclamation-triangle-fill"></i>

Danger Zone

</h5>


<p>

Archiving this room will hide it from active
Secure Rooms. Existing room data will remain
stored for future recovery.

</p>


<form method="POST"
      onsubmit="
        return confirm(
            'Are you sure you want to archive this room?'
        );
      ">

<button
type="submit"
name="archive_room"
class="btn btn-danger">

<i class="bi bi-archive-fill"></i>

Archive Room

</button>

</form>


</div>


</div>


</div>


</body>

</html>