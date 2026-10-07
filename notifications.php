<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   MARK SINGLE NOTIFICATION AS READ
===================================================== */

if (isset($_GET['read']) && is_numeric($_GET['read'])) {

    $notification_id = (int) $_GET['read'];

    mysqli_query(
        $conn,
        "UPDATE notifications
         SET is_read=1
         WHERE id='$notification_id'
         AND user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}


/* =====================================================
   MARK ALL AS READ
===================================================== */

if (isset($_GET['mark_all_read'])) {

    mysqli_query(
        $conn,
        "UPDATE notifications
         SET is_read=1
         WHERE user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}


/* =====================================================
   DELETE NOTIFICATION
===================================================== */

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $notification_id = (int) $_GET['delete'];

    mysqli_query(
        $conn,
        "DELETE FROM notifications
         WHERE id='$notification_id'
         AND user_id='$user_id'"
    );

    header("Location: notifications.php");
    exit();
}


/* =====================================================
   GET UNREAD COUNT
===================================================== */

$unreadResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE user_id='$user_id'
     AND is_read=0"
);

$unreadRow = mysqli_fetch_assoc($unreadResult);

$unreadCount = (int) ($unreadRow['total'] ?? 0);


/* =====================================================
   GET NOTIFICATIONS
===================================================== */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM notifications
     WHERE user_id='$user_id'
     ORDER BY created_at DESC"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Notifications - NexaVault</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- Bootstrap Icons -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
>


<style>

/* =====================================================
   GLOBAL RESET
===================================================== */

* {
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {

    margin: 0;

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #1e293b;

    background:
        radial-gradient(
            circle at 8% 12%,
            rgba(37, 99, 235, 0.08),
            transparent 30%
        ),
        radial-gradient(
            circle at 92% 80%,
            rgba(99, 102, 241, 0.07),
            transparent 32%
        ),
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #f1f5ff 50%,
            #ffffff 100%
        );

    background-attachment: fixed;

}


/* =====================================================
   PAGE
===================================================== */

.notification-page {

    width: 100%;

    max-width: 1080px;

    margin: 0 auto;

    /*
       Space for fixed NexaVault navbar
    */

    padding:
        115px
        24px
        70px;

}


/* =====================================================
   HEADER
===================================================== */

.notification-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 20px;

    flex-wrap: wrap;

    margin-bottom: 28px;

}


.notification-label {

    display: inline-block;

    color: #2563eb;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 2px;

    margin-bottom: 5px;

}


.notification-title {

    display: flex;

    align-items: center;

    gap: 8px;

    color: #0f172a;

    font-size: 34px;

    font-weight: 800;

    letter-spacing: -0.7px;

    margin:
        3px
        0
        6px;

}


.notification-title i {

    color: #2563eb;

    font-size: 29px;

}


.notification-subtitle {

    color: #64748b;

    font-size: 14px;

    margin: 0;

}


/* =====================================================
   DASHBOARD BUTTON
===================================================== */

.notification-header .btn-outline-light {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    padding:
        9px
        16px;

    color: #334155;

    background: #ffffff;

    border:
        1px solid
        #dbe3ef;

    border-radius: 10px;

    font-size: 13px;

    font-weight: 600;

    text-decoration: none;

    box-shadow:
        0
        4px
        12px
        rgba(15, 23, 42, 0.05);

    transition:
        all
        0.2s
        ease;

}


.notification-header .btn-outline-light:hover {

    color: #2563eb;

    background: #f8fbff;

    border-color: #93c5fd;

    transform: translateY(-1px);

    box-shadow:
        0
        7px
        18px
        rgba(37, 99, 235, 0.10);

}


/* =====================================================
   MAIN PANEL
===================================================== */

.notification-panel {

    width: 100%;

    background:
        rgba(
            255,
            255,
            255,
            0.97
        );

    border:
        1px
        solid
        #e2e8f0;

    border-radius: 20px;

    overflow: hidden;

    box-shadow:
        0
        18px
        50px
        rgba(15, 23, 42, 0.08),

        0
        3px
        10px
        rgba(15, 23, 42, 0.03);

}


/* =====================================================
   TOOLBAR
===================================================== */

.notification-toolbar {

    min-height: 65px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 14px;

    flex-wrap: wrap;

    padding:
        16px
        24px;

    background:
        linear-gradient(
            180deg,
            #ffffff,
            #f8fbff
        );

    border-bottom:
        1px
        solid
        #e8eef6;

}


.notification-count {

    color: #64748b;

    font-size: 13px;

}


.notification-count strong {

    color: #2563eb;

    font-size: 15px;

}


/* =====================================================
   PRIMARY BUTTON
===================================================== */

.btn-primary {

    border: none;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    color: #ffffff;

    font-weight: 600;

    box-shadow:
        0
        5px
        14px
        rgba(37, 99, 235, 0.16);

    transition:
        all
        0.2s
        ease;

}


.btn-primary:hover {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4338ca
        );

    transform: translateY(-1px);

    box-shadow:
        0
        8px
        20px
        rgba(37, 99, 235, 0.22);

}


/* =====================================================
   NOTIFICATION ITEM
===================================================== */

.notification-item {

    width: 100%;

    min-height: 82px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding:
        18px
        24px;

    background: #ffffff;

    border-bottom:
        1px
        solid
        #edf2f7;

    transition:
        background
        0.2s
        ease;

}


.notification-item:last-child {

    border-bottom: none;

}


.notification-item:hover {

    background: #f8fbff;

}


/* =====================================================
   UNREAD NOTIFICATION
===================================================== */

.notification-item.unread {

    background:
        linear-gradient(
            90deg,
            rgba(37, 99, 235, 0.07),
            rgba(239, 246, 255, 0.40)
        );

    border-left:
        3px
        solid
        #2563eb;

    padding-left: 21px;

}


.notification-item.unread:hover {

    background:
        linear-gradient(
            90deg,
            rgba(37, 99, 235, 0.10),
            rgba(239, 246, 255, 0.65)
        );

}


/* =====================================================
   LEFT SIDE
===================================================== */

.notification-left {

    min-width: 0;

    flex: 1;

    display: flex;

    align-items: center;

    gap: 14px;

}


/* =====================================================
   NOTIFICATION ICON
===================================================== */

.notification-icon {

    width: 44px;

    height: 44px;

    min-width: 44px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            135deg,
            #eff6ff,
            #eef2ff
        );

    border:
        1px
        solid
        #dbeafe;

    color: #2563eb;

    font-size: 18px;

    box-shadow:
        0
        4px
        10px
        rgba(37, 99, 235, 0.07);

}


.notification-item:not(.unread)
.notification-icon {

    background: #f8fafc;

    border-color: #e2e8f0;

    color: #64748b;

    box-shadow: none;

}


/* =====================================================
   CONTENT
===================================================== */

.notification-content {

    min-width: 0;

    flex: 1;

}


.notification-message {

    color: #1e293b;

    font-size: 13px;

    line-height: 1.5;

    font-weight: 700;

    word-break: break-word;

}


.notification-item:not(.unread)
.notification-message {

    color: #475569;

    font-weight: 500;

}


.notification-time {

    display: block;

    margin-top: 5px;

    color: #94a3b8;

    font-size: 10px;

}


.notification-time i {

    margin-right: 3px;

    color: #64748b;

}


/* =====================================================
   ACTIONS
===================================================== */

.notification-actions {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    flex-shrink: 0;

}


.notification-action {

    width: 35px;

    height: 35px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    color: #64748b;

    background: #f8fafc;

    border:
        1px
        solid
        #e2e8f0;

    text-decoration: none;

    transition:
        all
        0.2s
        ease;

}


.notification-action:hover {

    color: #2563eb;

    background: #eff6ff;

    border-color: #bfdbfe;

    transform: translateY(-1px);

    box-shadow:
        0
        4px
        10px
        rgba(37, 99, 235, 0.08);

}


.notification-action[title="Delete notification"]:hover {

    color: #dc2626;

    background: #fef2f2;

    border-color: #fecaca;

    box-shadow:
        0
        4px
        10px
        rgba(220, 38, 38, 0.08);

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-notifications {

    text-align: center;

    padding:
        85px
        25px;

}


.empty-notifications-icon {

    width: 78px;

    height: 78px;

    margin:
        0
        auto
        20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #f1f5f9,
            #eef2ff
        );

    border:
        1px
        solid
        #e2e8f0;

    color: #64748b;

    font-size: 34px;

    box-shadow:
        0
        8px
        22px
        rgba(15, 23, 42, 0.05);

}


.empty-notifications h4 {

    color: #0f172a;

    font-size: 20px;

    font-weight: 700;

    margin-bottom: 7px;

}


.empty-notifications p {

    color: #64748b;

    font-size: 13px;

    margin-bottom: 18px;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 768px) {

    .notification-page {

        padding:
            100px
            15px
            50px;

    }


    .notification-header {

        align-items: flex-start;

    }


    .notification-title {

        font-size: 27px;

    }


    .notification-title i {

        font-size: 23px;

    }


    .notification-subtitle {

        font-size: 13px;

    }


    .notification-toolbar {

        align-items: flex-start;

        padding:
            16px
            18px;

    }


    .notification-item {

        align-items: flex-start;

        flex-direction: column;

        gap: 14px;

        padding:
            17px
            18px;

    }


    .notification-item.unread {

        padding-left: 15px;

    }


    .notification-left {

        width: 100%;

        align-items: flex-start;

    }


    .notification-actions {

        width: 100%;

        justify-content: flex-end;

    }

}


/* =====================================================
   SMALL MOBILE
===================================================== */

@media (max-width: 480px) {

    .notification-page {

        padding-left: 12px;

        padding-right: 12px;

    }


    .notification-header .btn-outline-light {

        width: 100%;

    }


    .notification-toolbar {

        flex-direction: column;

        align-items: stretch;

    }


    .notification-toolbar .btn-primary {

        width: 100%;

        justify-content: center;

    }


    .notification-left {

        gap: 11px;

    }


    .notification-icon {

        width: 40px;

        height: 40px;

        min-width: 40px;

        font-size: 16px;

    }


    .notification-message {

        font-size: 12.5px;

    }

}


/* =====================================================
   PRINT
===================================================== */

@media print {

    body {

        background: #ffffff !important;

        color: #000000 !important;

    }


    .notification-page {

        max-width: 100%;

        padding:
            20px;

    }


    .notification-panel {

        box-shadow: none;

        border: 1px solid #d1d5db;

    }


    .notification-actions,

    .notification-header .btn-outline-light,

    .notification-toolbar .btn-primary {

        display: none !important;

    }


    .notification-item {

        background: #ffffff !important;

    }

}

</style>

</head>


<body>


<?php include 'includes/user_navbar.php'; ?>


<main class="notification-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="notification-header">

    <div>

        <span class="notification-label">
            NOTIFICATION CENTER
        </span>


        <h1 class="notification-title">

            <i class="bi bi-bell-fill"></i>

            Notifications

        </h1>


        <p class="notification-subtitle">

            Stay updated about activity in your NexaVault account.

        </p>

    </div>


    <a
        href="dashboard.php"
        class="btn btn-outline-light"
    >

        <i class="bi bi-arrow-left"></i>

        Dashboard

    </a>

</div>



<!-- =====================================================
     PANEL
===================================================== -->

<div class="notification-panel">


    <!-- TOOLBAR -->

    <div class="notification-toolbar">


        <div class="notification-count">

            <?php if ($unreadCount > 0) { ?>

                <strong>
                    <?php echo $unreadCount; ?>
                </strong>

                unread notification(s)

            <?php } else { ?>

                All notifications are read.

            <?php } ?>

        </div>


        <?php if ($unreadCount > 0) { ?>

            <a
                href="notifications.php?mark_all_read=1"
                class="btn btn-primary btn-sm"
            >

                <i class="bi bi-check2-all"></i>

                Mark All as Read

            </a>

        <?php } ?>


    </div>



    <!-- =====================================================
         NOTIFICATIONS
    ===================================================== -->

    <?php

    if (
        !$result ||
        mysqli_num_rows($result) == 0
    ) {

    ?>

        <div class="empty-notifications">


            <div class="empty-notifications-icon">

                <i class="bi bi-bell-slash-fill"></i>

            </div>


            <h4>
                No Notifications
            </h4>


            <p>
                You're all caught up. New activity will appear here.
            </p>


            <a
                href="dashboard.php"
                class="btn btn-primary mt-2"
            >

                <i class="bi bi-grid-fill"></i>

                Back to Dashboard

            </a>


        </div>


    <?php

    } else {

        while ($row = mysqli_fetch_assoc($result)) {

            $notificationId =
                (int) $row['id'];

            $isRead =
                (int) ($row['is_read'] ?? 0);

            $message =
                htmlspecialchars(
                    $row['message']
                );

            $createdAt =
                strtotime(
                    $row['created_at']
                );

    ?>


        <div
            class="notification-item <?php echo $isRead ? '' : 'unread'; ?>"
        >


            <div class="notification-left">


                <div class="notification-icon">

                    <?php if ($isRead) { ?>

                        <i class="bi bi-bell"></i>

                    <?php } else { ?>

                        <i class="bi bi-bell-fill"></i>

                    <?php } ?>

                </div>


                <div class="notification-content">


                    <div class="notification-message">

                        <?php echo $message; ?>

                    </div>


                    <span class="notification-time">

                        <i class="bi bi-clock"></i>

                        <?php

                        echo date(
                            "d M Y • h:i A",
                            $createdAt
                        );

                        ?>

                    </span>


                </div>


            </div>



            <div class="notification-actions">


                <?php if (!$isRead) { ?>

                    <a
                        href="notifications.php?read=<?php echo $notificationId; ?>"
                        class="notification-action"
                        title="Mark as read"
                    >

                        <i class="bi bi-check2"></i>

                    </a>

                <?php } ?>


                <a
                    href="notifications.php?delete=<?php echo $notificationId; ?>"
                    class="notification-action"
                    title="Delete notification"
                    onclick="return confirm('Delete this notification?');"
                >

                    <i class="bi bi-trash3"></i>

                </a>


            </div>


        </div>


    <?php

        }

    }

    ?>


</div>


</main>


</body>

</html>