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
   ONLY OWNER / MANAGE MEMBER CAN MANAGE MEMBERS
===================================================== */

$myPermission = '';

$myMemberQuery = mysqli_query(
    $conn,
    "SELECT permission
     FROM room_members
     WHERE room_id='$room_id'
     AND user_id='$user_id'
     LIMIT 1"
);

if (
    $myMemberQuery &&
    mysqli_num_rows($myMemberQuery) > 0
) {
    $myMember = mysqli_fetch_assoc($myMemberQuery);
    $myPermission = $myMember['permission'] ?? 'view';
}

$canManageMembers =
    ($user_id === $owner_id) ||
    ($myPermission === 'manage');

if (!$canManageMembers) {
    die("You don't have permission to manage room members.");
}


$error = "";
$success = "";


/* =====================================================
   ADD MEMBER
===================================================== */

if (isset($_POST['add_member'])) {

    $memberEmail = trim(
        $_POST['member_email'] ?? ''
    );

    $permission = $_POST['permission'] ?? 'view';

    $allowedPermissions = [
        'view',
        'download',
        'upload',
        'manage'
    ];


    if ($memberEmail === '') {

        $error = "Please enter the user's email.";

    } elseif (
        !in_array(
            $permission,
            $allowedPermissions,
            true
        )
    ) {

        $error = "Invalid permission.";

    } else {

        $memberEmailSafe =
            mysqli_real_escape_string(
                $conn,
                $memberEmail
            );


        /* Find user */

        $userQuery = mysqli_query(
            $conn,
            "SELECT id, name, email
             FROM users
             WHERE email='$memberEmailSafe'
             LIMIT 1"
        );


        if (
            !$userQuery ||
            mysqli_num_rows($userQuery) === 0
        ) {

            $error =
                "No NexaVault user found with this email.";

        } else {

            $memberUser =
                mysqli_fetch_assoc(
                    $userQuery
                );

            $memberUserId =
                (int)$memberUser['id'];


            /* Owner cannot be added */

            if ($memberUserId === $owner_id) {

                $error =
                    "Room owner already has full access.";

            } else {

                /* Check duplicate */

                $existingMemberQuery =
                    mysqli_query(
                        $conn,
                        "SELECT id
                         FROM room_members
                         WHERE room_id='$room_id'
                         AND user_id='$memberUserId'
                         LIMIT 1"
                    );


                if (
                    $existingMemberQuery &&
                    mysqli_num_rows(
                        $existingMemberQuery
                    ) > 0
                ) {

                    $error =
                        "This user is already a room member.";

                } else {

                    $insertMember =
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
                                '$memberUserId',
                                '$permission'
                            )"
                        );


                    if ($insertMember) {

                        /* Room activity */

                        $safeActivity =
                            mysqli_real_escape_string(
                                $conn,
                                "Added member " .
                                $memberUser['name'] .
                                " with " .
                                strtoupper($permission) .
                                " permission."
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


                        /* Notification */

                        if (
                            file_exists(
                                "includes/notification.php"
                            )
                        ) {

                            include
                                "includes/notification.php";

                            addNotification(
                                $conn,
                                $memberUserId,
                                "🏠 You were added to Secure Room: " .
                                $room['room_name']
                            );
                        }


                        $success =
                            "Member added successfully.";

                    } else {

                        $error =
                            "Unable to add member.";
                    }
                }
            }
        }
    }
}


/* =====================================================
   UPDATE MEMBER PERMISSION
===================================================== */

if (isset($_POST['update_permission'])) {

    $memberId =
        isset($_POST['member_id'])
        ? (int)$_POST['member_id']
        : 0;

    $newPermission =
        $_POST['new_permission'] ?? 'view';

    $allowedPermissions = [
        'view',
        'download',
        'upload',
        'manage'
    ];


    if (
        $memberId <= 0 ||
        !in_array(
            $newPermission,
            $allowedPermissions,
            true
        )
    ) {

        $error =
            "Invalid member or permission.";

    } else {

        $memberCheckQuery =
            mysqli_query(
                $conn,
                "SELECT user_id
                 FROM room_members
                 WHERE id='$memberId'
                 AND room_id='$room_id'
                 LIMIT 1"
            );


        if (
            !$memberCheckQuery ||
            mysqli_num_rows($memberCheckQuery) === 0
        ) {

            $error =
                "Room member not found.";

        } else {

            $memberRow =
                mysqli_fetch_assoc(
                    $memberCheckQuery
                );

            $targetUserId =
                (int)$memberRow['user_id'];


            if ($targetUserId === $owner_id) {

                $error =
                    "Owner permission cannot be changed.";

            } else {

                $updated =
                    mysqli_query(
                        $conn,
                        "UPDATE room_members
                         SET permission='$newPermission'
                         WHERE id='$memberId'
                         AND room_id='$room_id'"
                    );


                if ($updated) {

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
                            'Updated member permission to " .
                            mysqli_real_escape_string(
                                $conn,
                                strtoupper($newPermission)
                            ) .
                            "'
                        )"
                    );


                    $success =
                        "Member permission updated.";

                } else {

                    $error =
                        "Unable to update permission.";
                }
            }
        }
    }
}


/* =====================================================
   REMOVE MEMBER
===================================================== */

if (isset($_POST['remove_member'])) {

    $memberId =
        isset($_POST['member_id'])
        ? (int)$_POST['member_id']
        : 0;


    if ($memberId <= 0) {

        $error =
            "Invalid member.";

    } else {

        $memberCheckQuery =
            mysqli_query(
                $conn,
                "SELECT user_id
                 FROM room_members
                 WHERE id='$memberId'
                 AND room_id='$room_id'
                 LIMIT 1"
            );


        if (
            !$memberCheckQuery ||
            mysqli_num_rows($memberCheckQuery) === 0
        ) {

            $error =
                "Room member not found.";

        } else {

            $memberRow =
                mysqli_fetch_assoc(
                    $memberCheckQuery
                );

            $targetUserId =
                (int)$memberRow['user_id'];


            if ($targetUserId === $owner_id) {

                $error =
                    "The room owner cannot be removed.";

            } else {

                $deleted =
                    mysqli_query(
                        $conn,
                        "DELETE FROM room_members
                         WHERE id='$memberId'
                         AND room_id='$room_id'"
                    );


                if ($deleted) {

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
                            'Removed a room member'
                        )"
                    );


                    $success =
                        "Member removed successfully.";

                } else {

                    $error =
                        "Unable to remove member.";
                }
            }
        }
    }
}


/* =====================================================
   GET MEMBERS
===================================================== */

$membersQuery = mysqli_query(
    $conn,
    "SELECT
        rm.id AS member_id,
        rm.permission,
        rm.joined_at,
        u.id AS user_id,
        u.name,
        u.email
     FROM room_members rm
     INNER JOIN users u
        ON rm.user_id=u.id
     WHERE rm.room_id='$room_id'
     ORDER BY
        CASE
            WHEN rm.user_id='$owner_id' THEN 0
            ELSE 1
        END,
        rm.id ASC"
);

$members = [];

if ($membersQuery) {

    while ($row = mysqli_fetch_assoc($membersQuery)) {
        $members[] = $row;
    }
}


/* =====================================================
   DISPLAY VALUES
===================================================== */

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
Members - <?php echo $roomName; ?>
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


.members-page {

    max-width: 1250px;

    margin: auto;

    padding: 40px 25px 60px;

}


.panel {

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


.member-card {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 16px;

    margin-bottom: 12px;

    border-radius: 15px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(255,255,255,.06);

}


.member-left {

    display: flex;

    align-items: center;

    gap: 13px;

    min-width: 0;

}


.avatar {

    width: 44px;

    height: 44px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    font-weight: 800;

}


.member-name {

    color: #ffffff;

    font-size: 13px;

    font-weight: 700;

}


.member-email {

    margin-top: 2px;

    color: #7184a3;

    font-size: 10px;

    word-break: break-word;

}


.owner-badge {

    display: inline-block;

    margin-left: 5px;

    padding: 3px 7px;

    border-radius: 7px;

    color: #fde68a;

    background:
        rgba(245,158,11,.10);

    font-size: 8px;

    font-weight: 800;

}


.permission-badge {

    display: inline-block;

    padding: 6px 9px;

    border-radius: 9px;

    color: #93c5fd;

    background:
        rgba(59,130,246,.10);

    font-size: 9px;

    font-weight: 800;

}


.form-control,
.form-select {

    min-height: 46px;

    border-radius: 11px;

    background:
        rgba(5,18,40,.85) !important;

    border:
        1px solid
        rgba(120,155,220,.18) !important;

    color: #ffffff !important;

}


.form-select option {

    background: #0f172a;

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


.small-note {

    color: #7184a3;

    font-size: 10px;

}


@media(max-width:768px) {

    .member-card {

        align-items: flex-start;

        flex-direction: column;

    }

}

</style>

</head>


<body>


<div class="members-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="d-flex
            justify-content-between
            align-items-center
            flex-wrap
            gap-3
            mb-4">

<div>

<span class="page-label">
ROOM MEMBERS
</span>

<h1 class="page-title mb-1">

<i class="bi bi-people-fill"></i>

Room Members

</h1>

<p class="page-subtitle mb-0">

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


<div class="row g-4">


<!-- =====================================================
     ADD MEMBER
===================================================== -->

<div class="col-xl-4">


<div class="panel">


<span class="page-label">
ADD MEMBER
</span>

<h4 class="text-white fw-bold mt-1">

Invite User

</h4>


<p class="page-subtitle">

Add an existing NexaVault user
to this secure room.

</p>


<form method="POST">


<div class="mb-3">

<label class="form-label text-light">

User Email

</label>


<input
type="email"
name="member_email"
class="form-control"
placeholder="user@example.com"
required>

</div>


<div class="mb-3">

<label class="form-label text-light">

Permission

</label>


<select
name="permission"
class="form-select"
required>

<option value="view">

View Only

</option>

<option value="download">

View + Download

</option>

<option value="upload">

Upload Files

</option>

<option value="manage">

Manage Room

</option>

</select>

</div>


<button
type="submit"
name="add_member"
class="btn btn-primary w-100">

<i class="bi bi-person-plus-fill"></i>

Add Member

</button>


</form>


<div class="small-note mt-3">

The user must already have a NexaVault account.

</div>


</div>

</div>


<!-- =====================================================
     MEMBER LIST
===================================================== -->

<div class="col-xl-8">


<div class="panel">


<div class="d-flex
            justify-content-between
            align-items-center
            mb-3">

<div>

<span class="page-label">
ACCESS CONTROL
</span>

<h4 class="text-white fw-bold mb-0">

Members & Permissions

</h4>

</div>


<span class="permission-badge">

<?php echo count($members); ?>

Members

</span>

</div>


<?php foreach ($members as $member) { ?>


<?php

$memberName =
    htmlspecialchars(
        $member['name'] ?? 'User',
        ENT_QUOTES
    );

$memberEmail =
    htmlspecialchars(
        $member['email'] ?? '',
        ENT_QUOTES
    );

$memberPermission =
    $member['permission'] ?? 'view';

$memberId =
    (int)$member['member_id'];

$targetUserId =
    (int)$member['user_id'];

$isMemberOwner =
    ($targetUserId === $owner_id);

$initial =
    strtoupper(
        substr(
            trim(
                $member['name'] ??
                'U'
            ),
            0,
            1
        )
    );

?>

<div class="member-card">


<div class="member-left">


<div class="avatar">

<?php echo htmlspecialchars($initial); ?>

</div>


<div>

<div class="member-name">

<?php echo $memberName; ?>

<?php if ($isMemberOwner) { ?>

<span class="owner-badge">

OWNER

</span>

<?php } ?>

</div>


<div class="member-email">

<?php echo $memberEmail; ?>

</div>

</div>


</div>


<?php if ($isMemberOwner) { ?>


<div>

<span class="permission-badge">

<i class="bi bi-shield-fill-check"></i>

MANAGE

</span>

</div>


<?php } else { ?>


<div class="d-flex
            align-items-center
            gap-2
            flex-wrap">


<form
method="POST"
class="d-flex gap-2">

<input
type="hidden"
name="member_id"
value="<?php echo $memberId; ?>">


<select
name="new_permission"
class="form-select form-select-sm">

<option
value="view"
<?php
echo $memberPermission === 'view'
    ? 'selected'
    : '';
?>>

View

</option>


<option
value="download"
<?php
echo $memberPermission === 'download'
    ? 'selected'
    : '';
?>>

Download

</option>


<option
value="upload"
<?php
echo $memberPermission === 'upload'
    ? 'selected'
    : '';
?>>

Upload

</option>


<option
value="manage"
<?php
echo $memberPermission === 'manage'
    ? 'selected'
    : '';
?>>

Manage

</option>

</select>


<button
type="submit"
name="update_permission"
class="btn btn-outline-light btn-sm">

<i class="bi bi-check-lg"></i>

</button>

</form>


<form
method="POST"
onsubmit="
return confirm(
    'Remove this member from the room?'
);
">

<input
type="hidden"
name="member_id"
value="<?php echo $memberId; ?>">


<button
type="submit"
name="remove_member"
class="btn btn-danger btn-sm">

<i class="bi bi-person-x-fill"></i>

</button>

</form>


</div>


<?php } ?>


</div>


<?php } ?>


</div>


</div>


</div>


</div>


</div>


</body>

</html>