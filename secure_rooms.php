<?php

session_start();

/* =====================================================
   LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION['user_id']) ||
    (int)$_SESSION['user_id'] <= 0
) {
    header("Location: login.php");
    exit();
}


/* =====================================================
   DATABASE CONNECTION
===================================================== */

$dbFile = __DIR__ . '/includes/db.php';

if (!file_exists($dbFile)) {
    die(
        "Database configuration file not found.<br><br>" .
        "Expected location:<br>" .
        htmlspecialchars($dbFile)
    );
}

require_once $dbFile;

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}


$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user'] ?? 'User';


/* =====================================================
   MESSAGE
===================================================== */

$message = "";
$message_type = "info";


/* =====================================================
   CREATE SECURE ROOM
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_room'])
) {

    $roomName =
        trim($_POST['room_name'] ?? '');

    $roomDescription =
        trim($_POST['room_description'] ?? '');


    if ($roomName === '') {

        $message =
            "Room name is required.";

        $message_type =
            "danger";

    } elseif (strlen($roomName) > 150) {

        $message =
            "Room name must not exceed 150 characters.";

        $message_type =
            "danger";

    } else {

        $createStmt = mysqli_prepare(
            $conn,
            "INSERT INTO secure_rooms
            (
                room_name,
                room_description,
                owner_id,
                status,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'active',
                NOW()
            )"
        );


        if (!$createStmt) {

            $message =
                "Unable to prepare room creation query.";

            $message_type =
                "danger";

        } else {

            mysqli_stmt_bind_param(
                $createStmt,
                "ssi",
                $roomName,
                $roomDescription,
                $user_id
            );


            $created =
                mysqli_stmt_execute(
                    $createStmt
                );


            if ($created) {

                $newRoomId =
                    mysqli_insert_id($conn);


                /*
                 * Owner does not have to be in room_members
                 * because owner access is handled separately.
                 */


                /* Activity logging if available */

                $activityFile =
                    __DIR__ . '/includes/activity.php';

                if (
                    file_exists($activityFile)
                ) {

                    require_once $activityFile;

                    if (
                        function_exists('logActivity')
                    ) {

                        logActivity(
                            $conn,
                            $user_id,
                            "Created Secure Room: " .
                            $roomName
                        );
                    }
                }


                $message =
                    "Secure Room created successfully.";

                $message_type =
                    "success";


                /*
                 * Redirect after successful creation
                 * to prevent duplicate form submission.
                 */

                header(
                    "Location: secure_rooms.php?created=1"
                );

                exit();

            } else {

                $message =
                    "Unable to create room: " .
                    mysqli_error($conn);

                $message_type =
                    "danger";
            }


            mysqli_stmt_close(
                $createStmt
            );
        }
    }
}


/* =====================================================
   SUCCESS MESSAGE AFTER REDIRECT
===================================================== */

if (
    isset($_GET['created']) &&
    $_GET['created'] === '1'
) {

    $message =
        "Secure Room created successfully.";

    $message_type =
        "success";
}


/* =====================================================
   ARCHIVE ROOM
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['archive_room'])
) {

    $room_id =
        (int)(
            $_POST['room_id'] ?? 0
        );


    if ($room_id <= 0) {

        $message =
            "Invalid room.";

        $message_type =
            "danger";

    } else {

        /*
         * Only owner can archive a room.
         */

        $archiveStmt = mysqli_prepare(
            $conn,
            "UPDATE secure_rooms
             SET status = 'archived'
             WHERE id = ?
             AND owner_id = ?
             AND status = 'active'"
        );


        if ($archiveStmt) {

            mysqli_stmt_bind_param(
                $archiveStmt,
                "ii",
                $room_id,
                $user_id
            );

            $archived =
                mysqli_stmt_execute(
                    $archiveStmt
                );

            mysqli_stmt_close(
                $archiveStmt
            );


            if ($archived) {

                $message =
                    "Room archived successfully.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Unable to archive room.";

                $message_type =
                    "danger";
            }

        } else {

            $message =
                "Unable to prepare archive query.";

            $message_type =
                "danger";
        }
    }
}


/* =====================================================
   GET USER'S SECURE ROOMS
===================================================== */

$rooms = [];


/*
 * Owner rooms are included even when there is no
 * room_members record.
 *
 * Member rooms are included through room_members.
 */

$roomsStmt = mysqli_prepare(
    $conn,
    "SELECT
        sr.id,
        sr.room_name,
        sr.room_description,
        sr.owner_id,
        sr.status,
        sr.created_at,
        rm.permission AS member_permission
     FROM secure_rooms sr
     LEFT JOIN room_members rm
        ON sr.id = rm.room_id
        AND rm.user_id = ?
     WHERE sr.status = 'active'
     AND (
        sr.owner_id = ?
        OR rm.user_id = ?
     )
     ORDER BY sr.id DESC"
);


if (!$roomsStmt) {

    die(
        "Unable to prepare rooms query:<br>" .
        htmlspecialchars(
            mysqli_error($conn)
        )
    );
}


mysqli_stmt_bind_param(
    $roomsStmt,
    "iii",
    $user_id,
    $user_id,
    $user_id
);


if (!mysqli_stmt_execute($roomsStmt)) {

    mysqli_stmt_close($roomsStmt);

    die(
        "Unable to load Secure Rooms:<br>" .
        htmlspecialchars(
            mysqli_error($conn)
        )
    );
}


$roomsQuery =
    mysqli_stmt_get_result(
        $roomsStmt
    );


if ($roomsQuery) {

    while (
        $row =
        mysqli_fetch_assoc(
            $roomsQuery
        )
    ) {

        $rooms[] =
            $row;
    }
}


mysqli_stmt_close(
    $roomsStmt
);


/* =====================================================
   STATISTICS
===================================================== */

$totalRooms =
    count($rooms);

$ownedRooms =
    0;

$memberRooms =
    0;


foreach ($rooms as $room) {

    if (
        (int)$room['owner_id'] ===
        $user_id
    ) {

        $ownedRooms++;

    } else {

        $memberRooms++;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Secure Rooms - NexaVault
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>

<style>
* {
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    margin: 0;
    color: #1e293b;
    font-family: "Segoe UI", Arial, sans-serif;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37, 99, 235, .08),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(139, 92, 246, .07),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #f8fafc
        );
}

.rooms-page {
    max-width: 1250px;
    margin: auto;
    padding: 40px 20px 70px;
}

.page-label {
    color: #2563eb;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

.page-title {
    margin: 5px 0 0;
    color: #0f172a;
    font-size: 32px;
    font-weight: 800;
}

.page-subtitle {
    margin-top: 7px;
    color: #64748b;
    font-size: 13px;
}

.top-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

/* ================================
   COMMON PANEL
================================ */

.panel {
    border-radius: 22px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 12px 35px rgba(15, 23, 42, .08);
}

/* ================================
   STATISTICS
================================ */

.stat-card {
    padding: 20px;
}

.stat-icon {
    width: 46px;
    height: 46px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 13px;
    margin-bottom: 13px;

    font-size: 20px;
}

.blue {
    color: #2563eb;
    background: #eff6ff;
}

.purple {
    color: #7c3aed;
    background: #f5f3ff;
}

.green {
    color: #059669;
    background: #ecfdf5;
}

.stat-card small {
    color: #64748b;
    font-size: 11px;
}

.stat-card h2 {
    margin: 4px 0 0;

    color: #0f172a;

    font-size: 26px;
    font-weight: 800;
}

/* ================================
   CREATE ROOM
================================ */

.create-panel {
    padding: 25px;
}

.section-title {
    color: #0f172a;
    font-size: 18px;
    font-weight: 750;
}

.section-subtitle {
    color: #64748b;
    font-size: 11px;
}

.form-label {
    color: #334155;
    font-size: 11px;
    font-weight: 650;
}

.form-control {
    min-height: 48px;

    background: #ffffff !important;

    color: #1e293b !important;

    border:
        1px solid
        #cbd5e1 !important;

    border-radius: 11px;
}

.form-control::placeholder {
    color: #94a3b8;
}

.form-control:focus {
    border-color: #2563eb !important;

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, .10) !important;
}

.form-control textarea {
    resize: vertical;
}

.create-btn {
    min-height: 48px;

    border: none;
    border-radius: 11px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    font-weight: 650;
}

.create-btn:hover {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        );
}

/* ================================
   ROOM CARD
================================ */

.room-card {
    padding: 22px;
    height: 100%;

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}

.room-card:hover {
    transform: translateY(-3px);

    border-color: #bfdbfe;

    box-shadow:
        0 18px 40px rgba(37, 99, 235, .10);
}

.room-icon {
    width: 55px;
    height: 55px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 16px;

    color: #2563eb;

    background: #eff6ff;

    font-size: 25px;
}

.room-name {
    color: #0f172a;

    font-size: 17px;
    font-weight: 750;

    word-break: break-word;
}

.room-description {
    margin-top: 7px;

    color: #64748b;

    font-size: 11px;
    line-height: 1.7;
}

.room-meta {
    display: flex;
    flex-wrap: wrap;

    gap: 7px;

    margin-top: 14px;
}

.meta-badge {
    padding: 6px 9px;

    border-radius: 8px;

    color: #475569;

    background: #f1f5f9;

    font-size: 9px;
}

.owner-badge {
    color: #7c3aed;
    background: #f5f3ff;
}

.view-badge {
    color: #2563eb;
    background: #eff6ff;
}

.download-badge {
    color: #059669;
    background: #ecfdf5;
}

.upload-badge {
    color: #b45309;
    background: #fffbeb;
}

.manage-badge {
    color: #7c3aed;
    background: #f5f3ff;
}

/* ================================
   ROOM ACTIONS
================================ */

.room-actions {
    display: flex;
    gap: 7px;

    margin-top: 18px;
}

.open-btn {
    flex: 1;

    border: none;
    border-radius: 10px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );
}

.open-btn:hover {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        );
}

.archive-btn {
    border-radius: 10px;
}

/* ================================
   EMPTY STATE
================================ */

.empty-box {
    padding: 80px 20px;
    text-align: center;
}

.empty-box i {
    display: block;

    color: #94a3b8;

    font-size: 50px;

    margin-bottom: 14px;
}

.empty-box h4 {
    color: #0f172a;
}

.empty-box p {
    color: #64748b;

    font-size: 12px;
}

/* ================================
   BUTTONS
================================ */

.btn-outline-light {
    border-color: #cbd5e1;

    color: #334155;

    background: #ffffff;

    border-radius: 10px;
}

.btn-outline-light:hover {
    background: #f1f5f9;

    border-color: #94a3b8;

    color: #0f172a;
}

/* ================================
   ALERT
================================ */

.page-alert {
    border: none;
    border-radius: 12px;
}

/* ================================
   INFORMATION PANEL
================================ */

.info-icon {
    width: 45px;
    height: 45px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    flex-shrink: 0;
}

.info-title {
    color: #0f172a;

    font-size: 14px;
    font-weight: 750;
}

.info-text {
    color: #64748b;

    font-size: 11px;
    line-height: 1.8;

    margin-bottom: 0;
}

/* ================================
   MOBILE
================================ */

@media (max-width: 768px) {

    .rooms-page {
        padding: 28px 13px 50px;
    }

    .page-title {
        font-size: 27px;
    }

    .top-actions {
        width: 100%;
    }

    .top-actions .btn {
        width: 100%;
    }

    .room-actions {
        flex-direction: row;
    }
}

/* ================================
   PRINT FRIENDLY
================================ */

@media print {

    body {
        background: #ffffff !important;
        color: #000000 !important;
    }

    .rooms-page {
        max-width: 100%;
        padding: 20px;
    }

    .panel {
        background: #ffffff !important;

        border: 1px solid #d1d5db !important;

        box-shadow: none !important;
    }

    .page-title,
    .section-title,
    .room-name,
    .empty-box h4,
    .info-title {
        color: #000000 !important;
    }

    .page-subtitle,
    .section-subtitle,
    .room-description,
    .empty-box p,
    .info-text,
    .stat-card small {
        color: #475569 !important;
    }

    .top-actions,
    .create-panel,
    .room-actions {
        display: none !important;
    }
}
</style>


</head>


<body>


<div class="rooms-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div
    class="
        d-flex
        justify-content-between
        align-items-start
        flex-wrap
        gap-3
        mb-4
    "
>

    <div>

        <span class="page-label">

            COLLABORATION

        </span>


        <h1 class="page-title">

            <i class="bi bi-house-lock-fill"></i>

            Secure Rooms

        </h1>


        <p class="page-subtitle mb-0">

            Create and manage secure collaborative
            workspaces for your files and team members.

        </p>

    </div>


    <div class="top-actions">

        <a
            href="dashboard.php"
            class="btn btn-outline-light"
        >

            <i class="bi bi-grid"></i>

            Dashboard

        </a>

    </div>

</div>


<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message !== "") { ?>

<div
    class="
        alert
        alert-<?php echo htmlspecialchars($message_type); ?>
        page-alert
        mb-4
    "
>

    <?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-3 mb-4">


    <div class="col-md-4">

        <div class="panel stat-card">

            <div class="stat-icon blue">

                <i class="bi bi-house-lock-fill"></i>

            </div>

            <small>
                Total Rooms
            </small>

            <h2>
                <?php echo $totalRooms; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-4">

        <div class="panel stat-card">

            <div class="stat-icon purple">

                <i class="bi bi-person-badge-fill"></i>

            </div>

            <small>
                Rooms You Own
            </small>

            <h2>
                <?php echo $ownedRooms; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-4">

        <div class="panel stat-card">

            <div class="stat-icon green">

                <i class="bi bi-people-fill"></i>

            </div>

            <small>
                Shared With You
            </small>

            <h2>
                <?php echo $memberRooms; ?>
            </h2>

        </div>

    </div>


</div>


<!-- =====================================================
     CREATE ROOM
===================================================== -->

<div class="panel create-panel mb-4">

    <div class="mb-3">

        <h3 class="section-title">

            <i class="bi bi-plus-circle-fill"></i>

            Create New Secure Room

        </h3>

        <p class="section-subtitle mb-0">

            Create a protected workspace and add registered
            NexaVault users with different permissions.

        </p>

    </div>


    <form method="POST">

        <div class="row g-3">

            <div class="col-lg-5">

                <label class="form-label">

                    Room Name

                </label>

                <input
                    type="text"
                    name="room_name"
                    class="form-control"
                    maxlength="150"
                    placeholder="Example: Final Year Project Team"
                    required
                >

            </div>


            <div class="col-lg-5">

                <label class="form-label">

                    Description

                </label>

                <input
                    type="text"
                    name="room_description"
                    class="form-control"
                    maxlength="500"
                    placeholder="Describe the purpose of this room"
                >

            </div>


            <div class="col-lg-2 d-flex align-items-end">

                <button
                    type="submit"
                    name="create_room"
                    class="btn create-btn w-100"
                >

                    <i class="bi bi-plus-lg"></i>

                    Create Room

                </button>

            </div>

        </div>

    </form>

</div>


<!-- =====================================================
     ROOM LIST
===================================================== -->

<div class="mb-3">

    <h3 class="section-title mb-1">

        Your Secure Rooms

    </h3>

    <p class="section-subtitle">

        Rooms owned by you or shared with your account.

    </p>

</div>


<?php if ($totalRooms === 0) { ?>

    <div class="panel empty-box">

        <i class="bi bi-house-lock"></i>

        <h4>
            No Secure Rooms Yet
        </h4>

        <p>
            Create your first Secure Room to start
            collaborating with other NexaVault users.
        </p>

    </div>

<?php } else { ?>

    <div class="row g-4">

        <?php foreach ($rooms as $room) { ?>

            <?php

            $roomId =
                (int)$room['id'];

            $displayName =
                htmlspecialchars(
                    $room['room_name']
                    ?? 'Secure Room',
                    ENT_QUOTES,
                    'UTF-8'
                );

            $displayDescription =
                htmlspecialchars(
                    $room['room_description']
                    ?? 'Secure collaborative workspace.',
                    ENT_QUOTES,
                    'UTF-8'
                );

            $roomOwner =
                (int)$room['owner_id'] ===
                $user_id;

            $roomPermission =
                $roomOwner
                ? 'manage'
                : strtolower(
                    $room['member_permission']
                    ?? 'view'
                );


            switch ($roomPermission) {

                case 'manage':
                    $permissionClass =
                        'manage-badge';
                    break;

                case 'upload':
                    $permissionClass =
                        'upload-badge';
                    break;

                case 'download':
                    $permissionClass =
                        'download-badge';
                    break;

                default:
                    $permissionClass =
                        'view-badge';
                    $roomPermission =
                        'view';
                    break;
            }


            $roomCreated =
                "N/A";

            if (
                !empty(
                    $room['created_at']
                )
            ) {

                $roomTime =
                    strtotime(
                        $room['created_at']
                    );

                if (
                    $roomTime !== false
                ) {

                    $roomCreated =
                        date(
                            "d M Y",
                            $roomTime
                        );
                }
            }

            ?>


            <div class="col-lg-6 col-xl-4">

                <div class="panel room-card">


                    <div class="d-flex align-items-start gap-3">

                        <div class="room-icon">

                            <i
                                class="bi bi-house-lock-fill"
                            ></i>

                        </div>


                        <div
                            style="
                                flex:1;
                                min-width:0;
                            "
                        >

                            <div class="room-name">

                                <?php echo $displayName; ?>

                            </div>

                            <div class="room-description">

                                <?php
                                echo $displayDescription;
                                ?>

                            </div>

                        </div>

                    </div>


                    <div class="room-meta">

                        <span
                            class="
                                meta-badge
                                <?php
                                echo $roomOwner
                                    ? 'owner-badge'
                                    : $permissionClass;
                                ?>
                            "
                        >

                            <i
                                class="bi bi-person-badge"
                            ></i>

                            <?php

                            echo $roomOwner
                                ? 'Owner'
                                : ucfirst(
                                    $roomPermission
                                );

                            ?>

                        </span>


                        <span class="meta-badge">

                            <i class="bi bi-calendar3"></i>

                            <?php
                            echo $roomCreated;
                            ?>

                        </span>


                        <span class="meta-badge">

                            <i class="bi bi-shield-check"></i>

                            Active

                        </span>

                    </div>


                    <div class="room-actions">


                        <a
                            href="room.php?id=<?php echo $roomId; ?>"
                            class="btn open-btn"
                        >

                            <i class="bi bi-box-arrow-in-right"></i>

                            Open Room

                        </a>


                        <?php if ($roomOwner) { ?>

                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Archive this Secure Room?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="room_id"
                                    value="<?php echo $roomId; ?>"
                                >


                                <button
                                    type="submit"
                                    name="archive_room"
                                    class="
                                        btn
                                        btn-outline-light
                                        archive-btn
                                    "
                                    title="Archive Room"
                                >

                                    <i class="bi bi-archive"></i>

                                </button>

                            </form>

                        <?php } ?>


                    </div>


                </div>

            </div>


        <?php } ?>

    </div>

<?php } ?>


<!-- =====================================================
     INFORMATION
===================================================== -->

<div class="panel mt-4 p-4">

    <div class="d-flex gap-3">

        <div
            style="
                width:45px;
                height:45px;
                border-radius:12px;
                display:flex;
                align-items:center;
                justify-content:center;
                color:#60a5fa;
                background:rgba(37,99,235,.12);
                flex-shrink:0;
            "
        >

            <i class="bi bi-info-circle-fill"></i>

        </div>


        <div>

            <h5
                style="
                    color:#ffffff;
                    font-size:14px;
                    font-weight:750;
                "
            >

                Secure Room Permissions

            </h5>


            <p
                style="
                    color:#91a0bb;
                    font-size:11px;
                    line-height:1.8;
                    margin-bottom:0;
                "
            >

                <strong style="color:#60a5fa;">
                    View
                </strong>
                allows members to access the room and view
                authorized files.

                <strong style="color:#34d399;">
                    Download
                </strong>
                allows file downloading.

                <strong style="color:#fbbf24;">
                    Upload
                </strong>
                allows adding files.

                <strong style="color:#a78bfa;">
                    Manage
                </strong>
                provides room-management privileges.

            </p>

        </div>

    </div>

</div>


</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>