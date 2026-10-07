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

$owner_id = (int) $room['owner_id'];


/* =====================================================
   CHECK ROOM ACCESS
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
    die("You don't have access to this Secure Room.");
}

$member = mysqli_fetch_assoc($memberQuery);

$permission = $member['permission'] ?? 'view';

$isOwner = ($user_id === $owner_id);


/* =====================================================
   GET ROOM ACTIVITIES
===================================================== */

$activityQuery = mysqli_query(
    $conn,
    "SELECT
        ra.id,
        ra.activity,
        ra.created_at,
        u.name,
        u.email
     FROM room_activity ra
     LEFT JOIN users u
        ON ra.user_id = u.id
     WHERE ra.room_id='$room_id'
     ORDER BY ra.id DESC
     LIMIT 100"
);

$activities = [];

if ($activityQuery) {

    while ($row = mysqli_fetch_assoc($activityQuery)) {
        $activities[] = $row;
    }
}


/* =====================================================
   DISPLAY
===================================================== */

$roomName = htmlspecialchars(
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
Room Activity - <?php echo $roomName; ?>
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


.activity-page {

    max-width: 1050px;

    margin: auto;

    padding: 40px 20px 60px;

}


.activity-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;

    margin-bottom: 25px;

}


.page-label {

    color: #6ea1ff;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 2px;

}


.page-title {

    margin: 5px 0 0;

    color: #ffffff;

    font-size: 30px;

    font-weight: 800;

}


.page-subtitle {

    margin: 5px 0 0;

    color: #91a0bb;

    font-size: 13px;

}


/* =====================================================
   MAIN CARD
===================================================== */

.activity-card {

    padding: 25px;

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


/* =====================================================
   TIMELINE
===================================================== */

.timeline {

    position: relative;

    margin-top: 10px;

}


.timeline::before {

    content: "";

    position: absolute;

    left: 20px;

    top: 10px;

    bottom: 10px;

    width: 2px;

    background:
        linear-gradient(
            to bottom,
            rgba(96,165,250,.35),
            rgba(139,92,246,.08)
        );

}


.activity-item {

    position: relative;

    display: flex;

    gap: 15px;

    padding: 14px 0 18px;

}


.activity-dot {

    width: 42px;

    height: 42px;

    flex-shrink: 0;

    position: relative;

    z-index: 2;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    color: #7daaff;

    background:
        #0d2145;

    border:
        1px solid
        rgba(96,165,250,.18);

}


.activity-content {

    flex: 1;

    min-width: 0;

    padding: 12px 15px;

    border-radius: 14px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(255,255,255,.06);

}


.activity-text {

    color: #e8eefc;

    font-size: 13px;

    font-weight: 650;

    line-height: 1.5;

}


.activity-user {

    margin-top: 5px;

    color: #6ea1ff;

    font-size: 10px;

}


.activity-time {

    margin-top: 3px;

    color: #7184a3;

    font-size: 9px;

}


/* =====================================================
   EMPTY
===================================================== */

.empty-activity {

    text-align: center;

    padding: 80px 20px;

}


.empty-activity i {

    display: block;

    margin-bottom: 15px;

    color: #55739e;

    font-size: 46px;

}


.empty-activity h4 {

    color: #ffffff;

}


.empty-activity p {

    color: #91a0bb;

}


/* =====================================================
   SUMMARY
===================================================== */

.activity-summary {

    display: flex;

    gap: 10px;

    flex-wrap: wrap;

    margin-bottom: 20px;

}


.summary-pill {

    padding: 8px 12px;

    border-radius: 10px;

    color: #a9bbd5;

    background:
        rgba(255,255,255,.045);

    border:
        1px solid
        rgba(255,255,255,.06);

    font-size: 10px;

}


.summary-pill strong {

    color: #ffffff;

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


@media(max-width:576px) {

    .activity-page {

        padding:
            30px 12px 45px;

    }

    .page-title {

        font-size: 25px;

    }

    .activity-card {

        padding: 18px;

    }

}

</style>

</head>


<body>

<div class="activity-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="activity-header">

<div>

<span class="page-label">
SECURE ROOM AUDIT
</span>

<h1 class="page-title">

<i class="bi bi-clock-history"></i>

Room Activity

</h1>

<p class="page-subtitle">

<?php echo $roomName; ?>

</p>

</div>


<div class="d-flex gap-2">

<a
href="room.php?id=<?php echo $room_id; ?>"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Back to Room

</a>

</div>

</div>


<!-- =====================================================
     ACTIVITY CARD
===================================================== -->

<div class="activity-card">


<div class="activity-summary">

<span class="summary-pill">

<i class="bi bi-list-check"></i>

Events:
<strong>
<?php echo count($activities); ?>
</strong>

</span>


<span class="summary-pill">

<i class="bi bi-shield-check"></i>

Access:
<strong>
<?php echo $isOwner ? 'Owner' : ucfirst($permission); ?>
</strong>

</span>


<span class="summary-pill">

<i class="bi bi-house-lock"></i>

Room:
<strong>
Protected
</strong>

</span>

</div>


<?php if (count($activities) === 0) { ?>


<div class="empty-activity">

<i class="bi bi-clock-history"></i>

<h4>
No Room Activity Yet
</h4>

<p>
Actions performed inside this Secure Room
will appear here.
</p>

</div>


<?php } else { ?>


<div class="timeline">


<?php foreach ($activities as $activity) { ?>


<?php

$activityText =
    htmlspecialchars(
        $activity['activity'] ??
        'Room activity',
        ENT_QUOTES
    );

$userName =
    htmlspecialchars(
        $activity['name'] ??
        'Unknown User',
        ENT_QUOTES
    );

$userEmail =
    htmlspecialchars(
        $activity['email'] ??
        '',
        ENT_QUOTES
    );

$initial =
    strtoupper(
        substr(
            trim(
                $activity['name'] ??
                'U'
            ),
            0,
            1
        )
    );

?>

<div class="activity-item">


<div class="activity-dot">

<?php echo htmlspecialchars($initial); ?>

</div>


<div class="activity-content">


<div class="activity-text">

<?php echo $activityText; ?>

</div>


<div class="activity-user">

<i class="bi bi-person-fill"></i>

<?php echo $userName; ?>

<?php if ($userEmail !== '') { ?>

&nbsp; • &nbsp;

<?php echo $userEmail; ?>

<?php } ?>

</div>


<div class="activity-time">

<i class="bi bi-clock"></i>

<?php

if (!empty($activity['created_at'])) {

    echo date(
        "d M Y • h:i A",
        strtotime(
            $activity['created_at']
        )
    );

} else {

    echo "Unknown time";

}

?>

</div>


</div>


</div>


<?php } ?>


</div>


<?php } ?>


</div>


</div>


</body>

</html>