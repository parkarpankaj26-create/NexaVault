<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];

$error = "";
$success = false;


/* =====================================================
   CREATE ROOM
===================================================== */

if (isset($_POST['create_room'])) {

    $room_name = trim(
        $_POST['room_name'] ?? ''
    );

    $room_description = trim(
        $_POST['room_description'] ?? ''
    );


    /* Validation */

    if ($room_name === '') {

        $error = "Room name is required.";

    } elseif (strlen($room_name) > 150) {

        $error = "Room name is too long.";

    } else {

        $room_name_safe = mysqli_real_escape_string(
            $conn,
            $room_name
        );

        $room_description_safe =
            mysqli_real_escape_string(
                $conn,
                $room_description
            );


        /* Secure room token */

        $room_token = bin2hex(
            random_bytes(32)
        );

        $room_token_safe =
            mysqli_real_escape_string(
                $conn,
                $room_token
            );


        /* Insert room */

        $query = mysqli_query(
            $conn,
            "INSERT INTO secure_rooms
            (
                owner_id,
                room_name,
                room_description,
                room_token,
                status
            )
            VALUES
            (
                '$user_id',
                '$room_name_safe',
                '$room_description_safe',
                '$room_token_safe',
                'active'
            )"
        );


        if ($query) {

            $room_id =
                mysqli_insert_id($conn);


            /* Automatically add owner as MANAGE member */

            mysqli_query(
                $conn,
                "INSERT INTO room_members
                (
                    room_id,
                    user_id,
                    permission
                )
                VALUES
                (
                    '$room_id',
                    '$user_id',
                    'manage'
                )"
            );


            /* Activity */

            if (file_exists("includes/activity.php")) {

                include 'includes/activity.php';

                logActivity(
                    $conn,
                    $user_id,
                    "Created Secure Room: " .
                    $room_name
                );
            }


            /* Notification */

            if (file_exists("includes/notification.php")) {

                include 'includes/notification.php';

                addNotification(
                    $conn,
                    $user_id,
                    "🏠 Secure Room created: " .
                    $room_name
                );
            }


            $success = true;

        } else {

            $error =
                "Unable to create secure room.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Create Secure Room - NexaVault</title>


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

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 30px 15px;

    color: #e8eefc;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at 12% 20%,
            rgba(37,99,235,.18),
            transparent 35%
        ),

        radial-gradient(
            circle at 88% 80%,
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


.room-card {

    width: 100%;

    max-width: 620px;

    padding: 35px;

    border-radius: 24px;

    background:
        linear-gradient(
            145deg,
            rgba(15,31,63,.96),
            rgba(7,20,43,.96)
        );

    border:
        1px solid
        rgba(120,155,220,.15);

    box-shadow:
        0 25px 65px
        rgba(0,0,0,.38);

}


.room-icon {

    width: 78px;

    height: 78px;

    margin: 0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    color: white;

    font-size: 34px;

    box-shadow:
        0 15px 35px
        rgba(37,99,235,.28);

}


.room-card h1 {

    color: #ffffff;

    font-size: 30px;

    font-weight: 800;

}


.subtitle {

    color: #91a0bb;

}


.form-label {

    color: #dce6fa;

    font-weight: 600;

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


.form-control::placeholder {

    color: #7184a3;

}


.form-control:focus {

    border-color:
        #4d7cff !important;

    box-shadow:
        0 0 0 3px
        rgba(77,124,255,.10) !important;

}


.create-btn {

    min-height: 50px;

    width: 100%;

    border: none;

    border-radius: 12px;

    color: white;

    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

}


.create-btn:hover {

    color: white;

    transform:
        translateY(-2px);

}


.back-link {

    color: #78a8ff;

    text-decoration: none;

}


.back-link:hover {

    color: white;

}


.room-info {

    margin-top: 22px;

    padding: 15px;

    border-radius: 13px;

    background:
        rgba(59,130,246,.07);

    border:
        1px solid
        rgba(59,130,246,.12);

    color: #91a0bb;

    font-size: 12px;

}


</style>

</head>


<body>


<div class="room-card">


<div class="text-center">

<div class="room-icon">

<i class="bi bi-house-lock-fill"></i>

</div>


<h1>

Create Secure Room

</h1>


<p class="subtitle">

Create a protected workspace for your
project, team or important files.

</p>

</div>


<?php if ($error !== "") { ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?php echo htmlspecialchars($error); ?>

</div>

<?php } ?>


<?php if ($success) { ?>


<div class="alert alert-success text-center">

<h5 class="fw-bold">

<i class="bi bi-check-circle-fill"></i>

Secure Room Created

</h5>

<p class="mb-3">

Your secure room has been created successfully.

</p>


<a
href="secure_rooms.php"
class="btn btn-primary">

<i class="bi bi-house-lock-fill"></i>

Open Secure Rooms

</a>

</div>


<?php } else { ?>


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
placeholder="Example: B.Sc Final Year Project"
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
rows="4"
placeholder="Describe the purpose of this secure room..."></textarea>

</div>


<button
type="submit"
name="create_room"
class="create-btn">

<i class="bi bi-shield-lock-fill me-2"></i>

Create Secure Room

<i class="bi bi-arrow-right ms-2"></i>

</button>


</form>


<?php } ?>


<div class="text-center mt-4">

<a
href="dashboard.php"
class="back-link">

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>

</div>


<div class="room-info">

<i class="bi bi-info-circle-fill"></i>

You will be the owner of this room and will
have full <strong>Manage</strong> permission.

</div>


</div>


</body>

</html>