<?php

session_start();

/* =====================================================
   LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user_id'])
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

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user'] ?? 'User';


/* =====================================================
   ROOM ID
===================================================== */

$room_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($room_id <= 0) {
    header("Location: secure_rooms.php");
    exit();
}


/* =====================================================
   MESSAGE
===================================================== */

$message = "";
$message_type = "info";


/* =====================================================
   GET ROOM
===================================================== */

$roomStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM secure_rooms
     WHERE id = ?
     AND status = 'active'
     LIMIT 1"
);

if (!$roomStmt) {
    die(
        "Room query failed: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $roomStmt,
    "i",
    $room_id
);

mysqli_stmt_execute($roomStmt);

$roomQuery = mysqli_stmt_get_result($roomStmt);

mysqli_stmt_close($roomStmt);

if (
    !$roomQuery ||
    mysqli_num_rows($roomQuery) === 0
) {
    die(
        "<div style='
            font-family:Segoe UI,Arial,sans-serif;
            padding:40px;
            color:#dc3545;
        '>
            <h3>Room not found</h3>
            <p>The requested Secure Room does not exist or is inactive.</p>
            <a href='secure_rooms.php'>Back to Secure Rooms</a>
        </div>"
    );
}

$room = mysqli_fetch_assoc($roomQuery);

$owner_id = (int)($room['owner_id'] ?? 0);

$isOwner = ($user_id === $owner_id);


/* =====================================================
   CHECK USER ACCESS
   OWNER OR MEMBER
===================================================== */

$permission = 'view';
$isMember = false;

if (!$isOwner) {

    $memberStmt = mysqli_prepare(
        $conn,
        "SELECT permission
         FROM room_members
         WHERE room_id = ?
         AND user_id = ?
         LIMIT 1"
    );

    if (!$memberStmt) {
        die(
            "Member access query failed: " .
            htmlspecialchars(mysqli_error($conn))
        );
    }

    mysqli_stmt_bind_param(
        $memberStmt,
        "ii",
        $room_id,
        $user_id
    );

    mysqli_stmt_execute($memberStmt);

    $memberQuery =
        mysqli_stmt_get_result($memberStmt);

    if (
        $memberQuery &&
        mysqli_num_rows($memberQuery) > 0
    ) {

        $member = mysqli_fetch_assoc($memberQuery);

        $permission =
            strtolower(
                $member['permission'] ?? 'view'
            );

        $isMember = true;
    }

    mysqli_stmt_close($memberStmt);


    if (!$isMember) {
        die(
            "<div style='
                font-family:Segoe UI,Arial,sans-serif;
                padding:40px;
                color:#dc3545;
            '>
                <h3>Access Denied</h3>
                <p>You do not have access to this Secure Room.</p>
                <a href='secure_rooms.php'>Back to Secure Rooms</a>
            </div>"
        );
    }

} else {

    $permission = 'manage';
}


/* =====================================================
   ROOM BASIC DATA
===================================================== */

$roomName = htmlspecialchars(
    $room['room_name'] ?? 'Secure Room',
    ENT_QUOTES,
    'UTF-8'
);

$roomDescription = htmlspecialchars(
    $room['room_description'] ??
    'Secure collaborative workspace.',
    ENT_QUOTES,
    'UTF-8'
);


/* =====================================================
   PERMISSION HELPERS
===================================================== */

$canManage =
    $isOwner ||
    $permission === 'manage';

$canUpload =
    $isOwner ||
    $permission === 'upload' ||
    $permission === 'manage';

$canDownload =
    $isOwner ||
    $permission === 'download' ||
    $permission === 'upload' ||
    $permission === 'manage';


/* =====================================================
   ADD MEMBER
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['add_member'])
) {

    if (!$isOwner) {

        $message =
            "Only the room owner can add members.";

        $message_type =
            "danger";

    } else {

        $memberEmail =
            trim(
                $_POST['member_email'] ?? ''
            );

        $memberPermission =
            strtolower(
                trim(
                    $_POST['permission']
                    ?? 'view'
                )
            );

        $allowedPermissions = [
            'view',
            'download',
            'upload',
            'manage'
        ];


        if (
            $memberEmail === '' ||
            !filter_var(
                $memberEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $message =
                "Please enter a valid member email.";

            $message_type =
                "danger";

        } elseif (
            !in_array(
                $memberPermission,
                $allowedPermissions,
                true
            )
        ) {

            $message =
                "Invalid permission.";

            $message_type =
                "danger";

        } else {

            $findUserStmt = mysqli_prepare(
                $conn,
                "SELECT id, name, email
                 FROM users
                 WHERE email = ?
                 LIMIT 1"
            );

            $memberUser = null;

            if ($findUserStmt) {

                mysqli_stmt_bind_param(
                    $findUserStmt,
                    "s",
                    $memberEmail
                );

                mysqli_stmt_execute(
                    $findUserStmt
                );

                $findUserResult =
                    mysqli_stmt_get_result(
                        $findUserStmt
                    );

                if (
                    $findUserResult &&
                    mysqli_num_rows(
                        $findUserResult
                    ) > 0
                ) {

                    $memberUser =
                        mysqli_fetch_assoc(
                            $findUserResult
                        );
                }

                mysqli_stmt_close(
                    $findUserStmt
                );
            }


            if (!$memberUser) {

                $message =
                    "No NexaVault user found with that email.";

                $message_type =
                    "danger";

            } else {

                $memberUserId =
                    (int)$memberUser['id'];


                if ($memberUserId === $owner_id) {

                    $message =
                        "The room owner is already the owner of this room.";

                    $message_type =
                        "warning";

                } else {

                    $memberCheckStmt = mysqli_prepare(
                        $conn,
                        "SELECT id
                         FROM room_members
                         WHERE room_id = ?
                         AND user_id = ?
                         LIMIT 1"
                    );

                    $existingMember = false;

                    if ($memberCheckStmt) {

                        mysqli_stmt_bind_param(
                            $memberCheckStmt,
                            "ii",
                            $room_id,
                            $memberUserId
                        );

                        mysqli_stmt_execute(
                            $memberCheckStmt
                        );

                        $memberCheckResult =
                            mysqli_stmt_get_result(
                                $memberCheckStmt
                            );

                        if (
                            $memberCheckResult &&
                            mysqli_num_rows(
                                $memberCheckResult
                            ) > 0
                        ) {
                            $existingMember = true;
                        }

                        mysqli_stmt_close(
                            $memberCheckStmt
                        );
                    }


                    if ($existingMember) {

                        $updateMemberStmt = mysqli_prepare(
                            $conn,
                            "UPDATE room_members
                             SET permission = ?
                             WHERE room_id = ?
                             AND user_id = ?"
                        );

                        $updated = false;

                        if ($updateMemberStmt) {

                            mysqli_stmt_bind_param(
                                $updateMemberStmt,
                                "sii",
                                $memberPermission,
                                $room_id,
                                $memberUserId
                            );

                            $updated =
                                mysqli_stmt_execute(
                                    $updateMemberStmt
                                );

                            mysqli_stmt_close(
                                $updateMemberStmt
                            );
                        }

                        $message =
                            $updated
                            ? "Member permission updated successfully."
                            : "Unable to update member permission.";

                        $message_type =
                            $updated
                            ? "success"
                            : "danger";

                    } else {

                        $insertMemberStmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO room_members
                            (
                                room_id,
                                user_id,
                                permission
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?
                            )"
                        );

                        $inserted = false;

                        if ($insertMemberStmt) {

                            mysqli_stmt_bind_param(
                                $insertMemberStmt,
                                "iis",
                                $room_id,
                                $memberUserId,
                                $memberPermission
                            );

                            $inserted =
                                mysqli_stmt_execute(
                                    $insertMemberStmt
                                );

                            mysqli_stmt_close(
                                $insertMemberStmt
                            );
                        }

                        $message =
                            $inserted
                            ? "Member added successfully."
                            : "Unable to add member.";

                        $message_type =
                            $inserted
                            ? "success"
                            : "danger";
                    }
                }
            }
        }
    }
}


/* =====================================================
   UPDATE MEMBER PERMISSION
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_permission'])
) {

    if (!$isOwner) {

        $message =
            "Only the room owner can change permissions.";

        $message_type =
            "danger";

    } else {

        $memberUserId =
            (int)(
                $_POST['member_user_id']
                ?? 0
            );

        $newPermission =
            strtolower(
                trim(
                    $_POST['new_permission']
                    ?? ''
                )
            );

        $allowedPermissions = [
            'view',
            'download',
            'upload',
            'manage'
        ];


        if (
            $memberUserId <= 0 ||
            !in_array(
                $newPermission,
                $allowedPermissions,
                true
            )
        ) {

            $message =
                "Invalid member or permission.";

            $message_type =
                "danger";

        } elseif (
            $memberUserId === $owner_id
        ) {

            $message =
                "The owner's permission cannot be changed.";

            $message_type =
                "warning";

        } else {

            $permissionStmt = mysqli_prepare(
                $conn,
                "UPDATE room_members
                 SET permission = ?
                 WHERE room_id = ?
                 AND user_id = ?"
            );

            $updated = false;

            if ($permissionStmt) {

                mysqli_stmt_bind_param(
                    $permissionStmt,
                    "sii",
                    $newPermission,
                    $room_id,
                    $memberUserId
                );

                $updated =
                    mysqli_stmt_execute(
                        $permissionStmt
                    );

                mysqli_stmt_close(
                    $permissionStmt
                );
            }

            $message =
                $updated
                ? "Member permission updated."
                : "Unable to update permission.";

            $message_type =
                $updated
                ? "success"
                : "danger";
        }
    }
}


/* =====================================================
   REMOVE MEMBER
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['remove_member'])
) {

    $memberUserId =
        (int)(
            $_POST['member_user_id']
            ?? 0
        );


    if ($memberUserId <= 0) {

        $message =
            "Invalid member.";

        $message_type =
            "danger";

    } elseif (
        $memberUserId === $owner_id
    ) {

        $message =
            "The room owner cannot be removed.";

        $message_type =
            "warning";

    } elseif (
        !$isOwner &&
        $memberUserId !== $user_id
    ) {

        $message =
            "You are not allowed to remove this member.";

        $message_type =
            "danger";

    } else {

        $removeStmt = mysqli_prepare(
            $conn,
            "DELETE FROM room_members
             WHERE room_id = ?
             AND user_id = ?"
        );

        $removed = false;

        if ($removeStmt) {

            mysqli_stmt_bind_param(
                $removeStmt,
                "ii",
                $room_id,
                $memberUserId
            );

            $removed =
                mysqli_stmt_execute(
                    $removeStmt
                );

            mysqli_stmt_close(
                $removeStmt
            );
        }


        if ($removed) {

            if ($memberUserId === $user_id) {
                header("Location: secure_rooms.php");
                exit();
            }

            $message =
                "Member removed successfully.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to remove member.";

            $message_type =
                "danger";
        }
    }
}


/* =====================================================
   GET MEMBERS
===================================================== */

$members = [];

$membersStmt = mysqli_prepare(
    $conn,
    "SELECT
        rm.user_id,
        rm.permission,
        u.name,
        u.email
     FROM room_members rm
     INNER JOIN users u
        ON rm.user_id = u.id
     WHERE rm.room_id = ?
     ORDER BY
        u.name ASC"
);

if ($membersStmt) {

    mysqli_stmt_bind_param(
        $membersStmt,
        "i",
        $room_id
    );

    mysqli_stmt_execute(
        $membersStmt
    );

    $membersQuery =
        mysqli_stmt_get_result(
            $membersStmt
        );

    if ($membersQuery) {

        while (
            $member =
            mysqli_fetch_assoc(
                $membersQuery
            )
        ) {

            $members[] =
                $member;
        }
    }

    mysqli_stmt_close(
        $membersStmt
    );
}


/* =====================================================
   MEMBER COUNT
===================================================== */

$memberCount =
    count($members);

$ownerAlreadyListed = false;

foreach ($members as $member) {

    if (
        (int)$member['user_id'] ===
        $owner_id
    ) {

        $ownerAlreadyListed = true;
        break;
    }
}

$displayMemberCount =
    $memberCount +
    ($ownerAlreadyListed ? 0 : 1);


/* =====================================================
   ROOM FILES
   Try room_files table first.
===================================================== */

$roomFiles = [];

$roomFilesSupported = false;


/* Check room_files table */

$roomFilesTableCheck =
    mysqli_query(
        $conn,
        "SHOW TABLES LIKE 'room_files'"
    );


if (
    $roomFilesTableCheck &&
    mysqli_num_rows(
        $roomFilesTableCheck
    ) > 0
) {

    $roomFilesSupported = true;

    $fileStmt = mysqli_prepare(
        $conn,
        "SELECT
            rf.id AS room_file_id,
            rf.added_at,
            f.id,
            f.original_name,
            f.file_type,
            f.file_size,
            f.favorite,
            f.deleted
         FROM room_files rf
         INNER JOIN files f
             ON rf.file_id = f.id
         WHERE rf.room_id = ?
         AND f.deleted = 0
         ORDER BY rf.id DESC"
    );

    if ($fileStmt) {

        mysqli_stmt_bind_param(
            $fileStmt,
            "i",
            $room_id
        );

        mysqli_stmt_execute(
            $fileStmt
        );

        $fileQuery =
            mysqli_stmt_get_result(
                $fileStmt
            );

        if ($fileQuery) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $fileQuery
                )
            ) {

                $roomFiles[] =
                    $row;
            }
        }

        mysqli_stmt_close(
            $fileStmt
        );
    }
}


/* =====================================================
   FALLBACK:
   files.room_id
===================================================== */

if (!$roomFilesSupported) {

    $roomColumnCheck =
        mysqli_query(
            $conn,
            "SHOW COLUMNS
             FROM files
             LIKE 'room_id'"
        );

    if (
        $roomColumnCheck &&
        mysqli_num_rows(
            $roomColumnCheck
        ) > 0
    ) {

        $roomFilesSupported = true;

        $fileStmt = mysqli_prepare(
            $conn,
            "SELECT
                id AS room_file_id,
                id,
                file_name,
                original_name,
                file_type,
                file_size,
                favorite,
                deleted
             FROM files
             WHERE room_id = ?
             AND deleted = 0
             ORDER BY id DESC"
        );

        if ($fileStmt) {

            mysqli_stmt_bind_param(
                $fileStmt,
                "i",
                $room_id
            );

            mysqli_stmt_execute(
                $fileStmt
            );

            $fileQuery =
                mysqli_stmt_get_result(
                    $fileStmt
                );

            if ($fileQuery) {

                while (
                    $row =
                    mysqli_fetch_assoc(
                        $fileQuery
                    )
                ) {

                    $roomFiles[] =
                        $row;
                }
            }

            mysqli_stmt_close(
                $fileStmt
            );
        }
    }
}


/* =====================================================
   ROOM ACTIVITY
===================================================== */

$activities = [];
$activityCount = 0;

$activityTableCheck =
    mysqli_query(
        $conn,
        "SHOW TABLES LIKE 'room_activity'"
    );

if (
    $activityTableCheck &&
    mysqli_num_rows(
        $activityTableCheck
    ) > 0
) {

    $countActivityStmt = mysqli_prepare(
        $conn,
        "SELECT COUNT(*) AS total
         FROM room_activity
         WHERE room_id = ?"
    );

    if ($countActivityStmt) {

        mysqli_stmt_bind_param(
            $countActivityStmt,
            "i",
            $room_id
        );

        mysqli_stmt_execute(
            $countActivityStmt
        );

        $activityCountQuery =
            mysqli_stmt_get_result(
                $countActivityStmt
            );

        if ($activityCountQuery) {

            $activityRow =
                mysqli_fetch_assoc(
                    $activityCountQuery
                );

            $activityCount =
                (int)(
                    $activityRow['total']
                    ?? 0
                );
        }

        mysqli_stmt_close(
            $countActivityStmt
        );
    }


    $activityStmt = mysqli_prepare(
        $conn,
        "SELECT
            ra.activity,
            ra.created_at,
            u.name
         FROM room_activity ra
         LEFT JOIN users u
             ON ra.user_id = u.id
         WHERE ra.room_id = ?
         ORDER BY ra.id DESC
         LIMIT 10"
    );

    if ($activityStmt) {

        mysqli_stmt_bind_param(
            $activityStmt,
            "i",
            $room_id
        );

        mysqli_stmt_execute(
            $activityStmt
        );

        $activityQuery =
            mysqli_stmt_get_result(
                $activityStmt
            );

        if ($activityQuery) {

            while (
                $activity =
                mysqli_fetch_assoc(
                    $activityQuery
                )
            ) {

                $activities[] =
                    $activity;
            }
        }

        mysqli_stmt_close(
            $activityStmt
        );
    }
}


/* =====================================================
   CREATED DATE
===================================================== */

$createdAt = "N/A";

if (!empty($room['created_at'])) {

    $roomTimestamp =
        strtotime(
            $room['created_at']
        );

    if (
        $roomTimestamp !== false
    ) {

        $createdAt =
            date(
                "d M Y",
                $roomTimestamp
            );
    }
}


/* =====================================================
   PERMISSION CLASS
===================================================== */

switch ($permission) {

    case 'manage':
        $permissionClass =
            'permission-manage';
        break;

    case 'upload':
        $permissionClass =
            'permission-upload';
        break;

    case 'download':
        $permissionClass =
            'permission-download';
        break;

    default:
        $permissionClass =
            'permission-view';
        break;
}


/* =====================================================
   FILE ICON
===================================================== */

function getRoomFileIcon($fileType)
{
    $type =
        strtolower(
            $fileType ?? ''
        );

    if (
        strpos($type, 'pdf') !== false
    ) {
        return 'bi-file-earmark-pdf-fill';
    }

    if (
        strpos($type, 'image') !== false
    ) {
        return 'bi-file-earmark-image-fill';
    }

    if (
        strpos($type, 'word') !== false ||
        strpos($type, 'document') !== false
    ) {
        return 'bi-file-earmark-word-fill';
    }

    if (
        strpos($type, 'zip') !== false
    ) {
        return 'bi-file-earmark-zip-fill';
    }

    return 'bi-file-earmark-fill';
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
    <?php echo $roomName; ?> - NexaVault
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
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    font-family:"Segoe UI",Arial,sans-serif;
    color:#1e293b;
    background:
      radial-gradient(circle at 10% 15%,rgba(37,99,235,.08),transparent 32%),
      radial-gradient(circle at 90% 80%,rgba(124,58,237,.07),transparent 34%),
      linear-gradient(135deg,#f8fbff,#eef5ff,#f8fafc);
}
a{text-decoration:none}
.room-page{max-width:1250px;margin:auto;padding:35px 20px 70px}
.room-topbar{
    display:flex;justify-content:space-between;align-items:center;gap:20px;
    margin-bottom:24px;padding:24px;border-radius:22px;
    background:#fff;border:1px solid #e2e8f0;
    box-shadow:0 12px 35px rgba(15,23,42,.08)
}
.room-label{display:block;color:#2563eb;font-size:10px;font-weight:800;letter-spacing:2px}
.room-title{margin:5px 0;color:#0f172a;font-size:30px;font-weight:800;word-break:break-word}
.room-title i{color:#2563eb}
.room-description-main{margin:5px 0 0;color:#64748b;font-size:13px}
.room-actions-top{display:flex;gap:8px;flex-wrap:wrap}
.room-actions-top .btn{white-space:nowrap}
.room-panel{
    background:#fff;border:1px solid #e2e8f0;border-radius:20px;
    box-shadow:0 12px 35px rgba(15,23,42,.07);padding:22px
}
.room-hero{padding:28px}
.room-icon-large{
    width:58px;height:58px;border-radius:16px;display:flex;align-items:center;
    justify-content:center;background:#eff6ff;color:#2563eb;font-size:27px;margin-bottom:14px
}
.room-hero h2{margin:0;color:#0f172a;font-size:24px;font-weight:800}
.room-hero p{margin:7px 0 0;color:#64748b;font-size:12px;line-height:1.7}
.room-meta{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
.room-meta>span{
    display:inline-flex;align-items:center;gap:5px;padding:7px 10px;border-radius:9px;
    background:#f1f5f9;color:#475569;font-size:10px
}
.permission-view{color:#2563eb!important;background:#eff6ff!important}
.permission-download{color:#059669!important;background:#ecfdf5!important}
.permission-upload{color:#b45309!important;background:#fffbeb!important}
.permission-manage{color:#7c3aed!important;background:#f5f3ff!important}
.panel-heading{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding-bottom:15px;margin-bottom:15px;border-bottom:1px solid #e2e8f0
}
.panel-heading h3{margin:0;color:#0f172a;font-size:17px;font-weight:750}
.panel-heading h3 i{color:#2563eb;margin-right:6px}
.panel-heading>span{color:#64748b!important;font-size:11px}
.add-member-box{
    padding:16px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0
}
.add-member-box label{display:block;color:#334155;font-size:11px;font-weight:650}
.form-control,.form-select{
    min-height:44px;background:#fff!important;color:#1e293b!important;
    border:1px solid #cbd5e1!important;border-radius:10px
}
.form-control::placeholder{color:#94a3b8}
.form-control:focus,.form-select:focus{
    border-color:#2563eb!important;box-shadow:0 0 0 3px rgba(37,99,235,.10)!important
}
.form-select option{color:#1e293b;background:#fff}
.btn-primary{
    border:none;border-radius:10px;background:linear-gradient(135deg,#2563eb,#6366f1);
    color:#fff
}
.btn-primary:hover{color:#fff;background:linear-gradient(135deg,#1d4ed8,#4f46e5)}
.btn-outline-light{
    border:1px solid #cbd5e1!important;border-radius:10px!important;
    color:#334155!important;background:#fff!important
}
.btn-outline-light:hover{color:#0f172a!important;background:#f1f5f9!important}
.btn-outline-danger{border-radius:9px}
.member-item{
    display:flex;align-items:center;gap:12px;padding:12px 0;
    border-bottom:1px solid #edf2f7
}
.member-item:last-child{border-bottom:none}
.member-avatar{
    width:40px;height:40px;min-width:40px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    color:#2563eb;background:#eff6ff;font-weight:800;font-size:13px
}
.member-info{min-width:0;flex:1}
.member-info strong{display:block;color:#0f172a;font-size:12px;font-weight:700}
.member-info small{display:block;margin-top:3px;color:#64748b;font-size:10px;overflow:hidden;text-overflow:ellipsis}
.member-info strong span{color:#2563eb!important}
.member-actions{display:flex;align-items:center;gap:7px}
.permission-select{min-width:110px!important;min-height:36px!important;font-size:10px}
.empty-files{
    text-align:center;padding:38px 15px;color:#64748b
}
.empty-files i{display:block;color:#94a3b8;font-size:34px;margin-bottom:10px}
.empty-files p{margin:0 0 12px;font-size:11px}
.room-file-item{
    display:flex;align-items:center;gap:11px;padding:12px 0;
    border-bottom:1px solid #edf2f7
}
.room-file-item:last-child{border-bottom:none}
.file-icon{
    width:40px;height:40px;min-width:40px;border-radius:11px;
    display:flex;align-items:center;justify-content:center;
    background:#eff6ff;color:#2563eb;font-size:18px
}
.file-info{min-width:0;flex:1}
.file-info strong{
    display:block;color:#1e293b;font-size:11px;white-space:nowrap;
    overflow:hidden;text-overflow:ellipsis
}
.file-info small{display:block;margin-top:3px;color:#64748b;font-size:9px}
.page-alert{border:none;border-radius:12px}
.info-icon{
    width:40px;height:40px;min-width:40px;border-radius:11px;
    display:flex;align-items:center;justify-content:center;
    color:#2563eb;background:#eff6ff
}
.info-text{color:#64748b!important;font-size:11px;line-height:1.8;margin:0}
.info-text strong{color:#334155}
.room-panel [style*="background:rgba(255,255,255,.035)"]{
    background:#f8fafc!important;border:1px solid #e2e8f0!important
}
.room-panel [style*="color:#ffffff"]{color:#0f172a!important}
.room-panel [style*="color:#6ea1ff"]{color:#2563eb!important}
.room-panel [style*="color:#8193af"]{color:#64748b!important}
.room-panel [style*="color:#7184a3"]{color:#64748b!important}
.room-panel [style*="color:#e8eefc"]{color:#334155!important}
.room-panel [style*="color:#a78bfa"]{color:#7c3aed!important}
.room-panel [style*="color:#60a5fa"]{color:#2563eb!important}
@media(max-width:768px){
    .room-page{padding:22px 12px 45px}
    .room-topbar{padding:18px;align-items:flex-start;flex-direction:column}
    .room-title{font-size:25px}
    .room-actions-top{width:100%}
    .room-actions-top .btn{flex:1}
    .room-hero{padding:20px}
    .member-actions{flex-direction:column;align-items:stretch}
    .permission-select{min-width:90px!important}
}
@media print{
    body{background:#fff!important;color:#000!important}
    .room-page{max-width:100%;padding:15px}
    .room-topbar,.room-panel{background:#fff!important;border:1px solid #d1d5db!important;box-shadow:none!important}
    .room-actions-top,.add-member-box,.member-actions,.room-file-item .btn{display:none!important}
    .room-title,.room-hero h2,.panel-heading h3,.member-info strong{color:#000!important}
    .room-description-main,.room-hero p,.member-info small,.file-info small,.info-text{color:#475569!important}
}
</style>
<body>

<div class="room-page">


<!-- =====================================================
     TOP BAR
===================================================== -->

<div class="room-topbar">

    <div>

        <span class="room-label">
            SECURE WORKSPACE
        </span>

        <h1 class="room-title">

            <i class="bi bi-house-lock-fill"></i>

            <?php echo $roomName; ?>

        </h1>

        <p class="room-description-main">

            <?php echo $roomDescription; ?>

        </p>

    </div>


    <div class="room-actions-top">

        <a
            href="secure_rooms.php"
            class="btn btn-outline-light"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Rooms

        </a>


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
        class="alert
        alert-<?php echo htmlspecialchars($message_type); ?>
        page-alert mb-4"
    >

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php } ?>


<!-- =====================================================
     ROOM HERO
===================================================== -->

<div class="room-panel room-hero mb-4">

    <div class="row align-items-center g-4">

        <div class="col-lg-8">

            <div class="room-icon-large">

                <i class="bi bi-shield-lock-fill"></i>

            </div>

            <h2>

                <?php echo $roomName; ?>

            </h2>

            <p>

                <?php echo $roomDescription; ?>

            </p>

            <div class="room-meta">

                <span>

                    <i class="bi bi-calendar3"></i>

                    Created:
                    <?php echo $createdAt; ?>

                </span>

                <span>

                    <i class="bi bi-people-fill"></i>

                    <?php echo $displayMemberCount; ?>
                    Members

                </span>

                <span>

                    <i class="bi bi-folder-fill"></i>

                    <?php echo count($roomFiles); ?>
                    Files

                </span>

                <span
                    class="<?php echo $permissionClass; ?>"
                >

                    <i class="bi bi-person-badge"></i>

                    Your Permission:
                    <?php echo ucfirst($permission); ?>

                </span>

            </div>

        </div>


        <div class="col-lg-4">

            <div
                style="
                    padding:20px;
                    border-radius:16px;
                    background:rgba(255,255,255,.035);
                    border:1px solid rgba(255,255,255,.06);
                "
            >

                <div
                    style="
                        color:#6ea1ff;
                        font-size:10px;
                        font-weight:800;
                        letter-spacing:1.5px;
                    "
                >

                    ROOM ACCESS

                </div>

                <div
                    style="
                        color:#ffffff;
                        font-size:25px;
                        font-weight:800;
                        margin-top:5px;
                    "
                >

                    <?php echo ucfirst($permission); ?>

                </div>

                <small
                    style="
                        color:#8193af;
                        line-height:1.6;
                    "
                >

                    <?php
                    echo $isOwner
                        ? "You are the owner of this Secure Room."
                        : "Your current permission for this room.";
                    ?>

                </small>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     MAIN GRID
===================================================== -->

<div class="row g-4">


<!-- =====================================================
     MEMBERS
===================================================== -->

<div class="col-lg-6">

    <div class="room-panel">

        <div class="panel-heading">

            <h3>

                <i class="bi bi-people-fill"></i>

                Room Members

            </h3>

            <span
                style="
                    color:#7184a3;
                    font-size:11px;
                "
            >

                <?php echo $displayMemberCount; ?>
                members

            </span>

        </div>


        <?php if ($isOwner) { ?>

            <div class="add-member-box mb-3">

                <form method="POST">

                    <label class="mb-2">

                        Add Member by Email

                    </label>

                    <div class="row g-2">

                        <div class="col-md-6">

                            <input
                                type="email"
                                name="member_email"
                                class="form-control"
                                placeholder="member@example.com"
                                required
                            >

                        </div>

                        <div class="col-md-3">

                            <select
                                name="permission"
                                class="form-select"
                                required
                            >

                                <option value="view">
                                    View
                                </option>

                                <option value="download">
                                    Download
                                </option>

                                <option value="upload">
                                    Upload
                                </option>

                                <option value="manage">
                                    Manage
                                </option>

                            </select>

                        </div>

                        <div class="col-md-3">

                            <button
                                type="submit"
                                name="add_member"
                                class="btn btn-primary w-100"
                            >

                                <i class="bi bi-person-plus"></i>

                                Add

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        <?php } ?>


        <div class="member-item">

            <div class="member-avatar">

                <?php
                echo strtoupper(
                    substr(
                        trim(
                            $room['owner_name']
                            ?? 'O'
                        ),
                        0,
                        1
                    )
                );
                ?>

            </div>

            <div class="member-info">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $room['owner_name']
                        ?? 'Room Owner',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                    <span
                        style="
                            color:#a78bfa;
                            font-size:9px;
                            margin-left:5px;
                        "
                    >

                        OWNER

                    </span>

                </strong>

                <small>

                    <?php
                    echo htmlspecialchars(
                        $room['owner_email']
                        ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </small>

            </div>

            <span
                class="permission-manage"
                style="
                    padding:6px 9px;
                    border-radius:9px;
                    font-size:10px;
                "
            >

                Manage

            </span>

        </div>


        <?php if (count($members) === 0) { ?>

            <div class="empty-files">

                <i class="bi bi-people"></i>

                <p>
                    No additional members have been added.
                </p>

            </div>

        <?php } else { ?>

            <?php foreach ($members as $member) { ?>

                <?php

                $memberId =
                    (int)$member['user_id'];

                $memberName =
                    htmlspecialchars(
                        $member['name']
                        ?? 'User',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $memberEmail =
                    htmlspecialchars(
                        $member['email']
                        ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                $memberPermission =
                    strtolower(
                        $member['permission']
                        ?? 'view'
                    );

                $memberInitial =
                    strtoupper(
                        substr(
                            trim(
                                $member['name']
                                ?? 'U'
                            ),
                            0,
                            1
                        )
                    );

                if (
                    $memberId ===
                    $user_id
                ) {
                    $youLabel = "YOU";
                } else {
                    $youLabel = "";
                }

                switch ($memberPermission) {

                    case 'manage':
                        $memberPermissionClass =
                            'permission-manage';
                        break;

                    case 'upload':
                        $memberPermissionClass =
                            'permission-upload';
                        break;

                    case 'download':
                        $memberPermissionClass =
                            'permission-download';
                        break;

                    default:
                        $memberPermissionClass =
                            'permission-view';
                        break;
                }

                ?>

                <div class="member-item">

                    <div class="member-avatar">

                        <?php echo $memberInitial; ?>

                    </div>

                    <div class="member-info">

                        <strong>

                            <?php echo $memberName; ?>

                            <?php if ($youLabel !== "") { ?>

                                <span
                                    style="
                                        color:#60a5fa;
                                        font-size:9px;
                                        margin-left:5px;
                                    "
                                >

                                    <?php echo $youLabel; ?>

                                </span>

                            <?php } ?>

                        </strong>

                        <small>

                            <?php echo $memberEmail; ?>

                        </small>

                    </div>


                    <?php if (
                        $isOwner &&
                        $memberId !== $owner_id
                    ) { ?>

                        <div class="member-actions">

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="member_user_id"
                                    value="<?php echo $memberId; ?>"
                                >

                                <input
                                    type="hidden"
                                    name="update_permission"
                                    value="1"
                                >

                                <select
                                    name="new_permission"
                                    class="form-select permission-select"
                                    onchange="this.form.submit()"
                                >

                                    <option
                                        value="view"
                                        <?php echo
                                        $memberPermission === 'view'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        View
                                    </option>

                                    <option
                                        value="download"
                                        <?php echo
                                        $memberPermission === 'download'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Download
                                    </option>

                                    <option
                                        value="upload"
                                        <?php echo
                                        $memberPermission === 'upload'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Upload
                                    </option>

                                    <option
                                        value="manage"
                                        <?php echo
                                        $memberPermission === 'manage'
                                            ? 'selected'
                                            : ''; ?>
                                    >
                                        Manage
                                    </option>

                                </select>

                            </form>


                            <form
                                method="POST"
                                onsubmit="
                                    return confirm(
                                        'Remove this member from the room?'
                                    );
                                "
                            >

                                <input
                                    type="hidden"
                                    name="member_user_id"
                                    value="<?php echo $memberId; ?>"
                                >

                                <button
                                    type="submit"
                                    name="remove_member"
                                    class="btn btn-outline-danger btn-sm"
                                >

                                    <i class="bi bi-person-x"></i>

                                </button>

                            </form>

                        </div>

                    <?php } else { ?>

                        <span
                            class="<?php echo $memberPermissionClass; ?>"
                            style="
                                padding:6px 9px;
                                border-radius:9px;
                                font-size:10px;
                            "
                        >

                            <?php
                            echo ucfirst(
                                $memberPermission
                            );
                            ?>

                        </span>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } ?>

    </div>

</div>


<!-- =====================================================
     ROOM FILES
===================================================== -->

<div class="col-lg-6">

    <div class="room-panel">

        <div class="panel-heading">

            <h3>

                <i class="bi bi-folder-fill"></i>

                Room Files

            </h3>

            <span
                style="
                    color:#7184a3;
                    font-size:11px;
                "
            >

                <?php echo count($roomFiles); ?>
                files

            </span>

        </div>


        <?php if ($roomFilesSupported) { ?>

            <?php if (count($roomFiles) === 0) { ?>

                <div class="empty-files">

                    <i class="bi bi-folder2-open"></i>

                    <p>
                        No files have been added to this room yet.
                    </p>

                    <?php if ($canUpload) { ?>

                        <a
                            href="room_add_file.php?id=<?php echo $room_id; ?>"
                            class="btn btn-primary btn-sm"
                        >

                            <i class="bi bi-plus-lg"></i>

                            Add File

                        </a>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <?php foreach ($roomFiles as $file) { ?>

                    <?php

                    $fileId =
                        (int)($file['id'] ?? 0);

                    $fileName =
                        htmlspecialchars(
                            $file['original_name']
                            ?? 'Unknown File',
                            ENT_QUOTES,
                            'UTF-8'
                        );

                    $fileType =
                        $file['file_type']
                        ?? '';

                    $fileSize =
                        round(
                            (
                                (int)(
                                    $file['file_size']
                                    ?? 0
                                )
                            ) / 1024,
                            2
                        );

                    $fileIcon =
                        getRoomFileIcon(
                            $fileType
                        );

                    ?>

                    <div class="room-file-item">

                        <div class="file-icon">

                            <i
                                class="bi <?php echo $fileIcon; ?>"
                            ></i>

                        </div>


                        <div class="file-info">

                            <strong
                                title="<?php echo $fileName; ?>"
                            >

                                <?php echo $fileName; ?>

                            </strong>

                            <small>

                                <?php echo $fileSize; ?> KB

                            </small>

                        </div>


                        <div class="d-flex gap-2">

                            <?php if ($fileId > 0) { ?>

                                <a
                                    href="preview.php?id=<?php echo $fileId; ?>"
                                    class="btn btn-outline-light btn-sm"
                                    title="Preview"
                                >

                                    <i class="bi bi-eye"></i>

                                </a>


                                <?php if ($canDownload) { ?>

                                    <a
                                        href="download.php?id=<?php echo $fileId; ?>"
                                        class="btn btn-primary btn-sm"
                                        title="Download"
                                    >

                                        <i class="bi bi-download"></i>

                                    </a>

                                <?php } ?>

                            <?php } ?>

                        </div>

                    </div>

                <?php } ?>

            <?php } ?>

        <?php } else { ?>

            <div
                class="empty-files"
                style="
                    text-align:left;
                    padding:25px;
                "
            >

                <i
                    class="bi bi-info-circle"
                    style="
                        font-size:25px;
                        margin-bottom:10px;
                    "
                ></i>
<p class="info-text">
                

                    Room file association is not configured
                    in the current database.

                    <br><br>

                    The room and member collaboration features
                    are available, but files need either a
                    <strong>room_files</strong> table or a
                    <strong>room_id</strong> column in the
                    <strong>files</strong> table.

                </p>

            </div>

        <?php } ?>

    </div>

</div>


<!-- =====================================================
     ACTIVITY
===================================================== -->

<div class="col-12">

    <div class="room-panel">

        <div class="panel-heading">

            <h3>

                <i class="bi bi-clock-history"></i>

                Recent Room Activity

            </h3>

            <span
                style="
                    color:#7184a3;
                    font-size:11px;
                "
            >

                <?php echo $activityCount; ?>
                activities

            </span>

        </div>


        <?php if (count($activities) === 0) { ?>

            <div class="empty-files">

                <i class="bi bi-clock-history"></i>

                <p>
                    No room activity recorded yet.
                </p>

            </div>

        <?php } else { ?>

            <div class="row g-3">

                <?php foreach ($activities as $activity) { ?>

                    <div class="col-lg-6">

                        <div
                            style="
                                display:flex;
                                gap:12px;
                                padding:13px;
                                border-radius:13px;
                                background:rgba(255,255,255,.035);
                            "
                        >

                       <div class="info-icon">
    <i class="bi bi-info-circle-fill"></i>
</div>

                                <i class="bi bi-activity"></i>

                            </div>


                            <div>

                                <div
                                    style="
                                        color:#e8eefc;
                                        font-size:11px;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['activity']
                                        ?? 'Room activity',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>

                                <small
                                    style="
                                        display:block;
                                        color:#7184a3;
                                        margin-top:4px;
                                        font-size:9px;
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $activity['name']
                                        ?? 'User',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                    <?php if (
                                        !empty(
                                            $activity['created_at']
                                        )
                                    ) { ?>

                                        •
                                        <?php
                                        $activityTime =
                                            strtotime(
                                                $activity['created_at']
                                            );

                                        echo $activityTime !== false
                                            ? date(
                                                "d M Y • H:i",
                                                $activityTime
                                            )
                                            : '';
                                        ?>

                                    <?php } ?>

                                </small>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</div>


</div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>

</body>

</html>