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

$user_id = (int) $_SESSION['user_id'];

$message = "";
$message_type = "";


/* =====================================================
   MARK SINGLE ALERT AS READ
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_read'])
) {

    $alert_id = isset($_POST['alert_id'])
        ? (int) $_POST['alert_id']
        : 0;

    if ($alert_id > 0) {

        mysqli_query(
            $conn,
            "UPDATE security_alerts
             SET is_read=1
             WHERE id='$alert_id'
             AND user_id='$user_id'"
        );
    }

    header("Location: security_alerts.php");
    exit();
}


/* =====================================================
   MARK ALL ALERTS AS READ
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['mark_all_read'])
) {

    mysqli_query(
        $conn,
        "UPDATE security_alerts
         SET is_read=1
         WHERE user_id='$user_id'"
    );

    header("Location: security_alerts.php");
    exit();
}


/* =====================================================
   DELETE SINGLE ALERT
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_alert'])
) {

    $alert_id = isset($_POST['alert_id'])
        ? (int) $_POST['alert_id']
        : 0;

    if ($alert_id > 0) {

        mysqli_query(
            $conn,
            "DELETE FROM security_alerts
             WHERE id='$alert_id'
             AND user_id='$user_id'"
        );
    }

    header("Location: security_alerts.php");
    exit();
}


/* =====================================================
   GET ALERTS
===================================================== */

$alerts = [];

$alertsQuery = mysqli_query(
    $conn,
    "SELECT
        id,
        alert_type,
        severity,
        title,
        message,
        is_read,
        created_at
     FROM security_alerts
     WHERE user_id='$user_id'
     ORDER BY
        is_read ASC,
        id DESC
     LIMIT 100"
);

if ($alertsQuery) {

    while ($row = mysqli_fetch_assoc($alertsQuery)) {

        $alerts[] = $row;
    }
}


/* =====================================================
   STATISTICS
===================================================== */

$totalAlerts = count($alerts);

$unreadAlerts = 0;
$highAlerts = 0;
$mediumAlerts = 0;
$lowAlerts = 0;

foreach ($alerts as $alert) {

    if ((int) $alert['is_read'] === 0) {
        $unreadAlerts++;
    }

    switch (
        strtolower(
            $alert['severity'] ?? 'low'
        )
    ) {

        case 'high':
            $highAlerts++;
            break;

        case 'medium':
            $mediumAlerts++;
            break;

        case 'low':
            $lowAlerts++;
            break;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>Security Alerts - NexaVault</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {

    min-height: 100vh;

    margin: 0;

    color: #1e293b;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37,99,235,.08),
            transparent 35%
        ),

        radial-gradient(
            circle at 90% 80%,
            rgba(124,58,237,.07),
            transparent 35%
        ),

        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #f8fafc
        );

    background-attachment: fixed;
}


/* =====================================================
   PAGE
===================================================== */

.alerts-page {

    max-width: 1180px;

    margin: auto;

    padding: 42px 20px 60px;

}


/* =====================================================
   HEADER
===================================================== */

.page-label {

    color: #2563eb;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 2px;

}


.page-title {

    margin: 5px 0 0;

    color: #0f172a;

    font-size: 31px;

    font-weight: 800;

}


.page-title i {

    color: #2563eb;

}


.page-subtitle {

    margin-top: 6px;

    color: #64748b;

    font-size: 13px;

}


/* =====================================================
   PANEL
===================================================== */

.panel {

    border-radius: 20px;

    background: #ffffff;

    border:
        1px solid
        #e2e8f0;

    box-shadow:
        0 12px 35px
        rgba(15,23,42,.07);

}


/* =====================================================
   STAT
===================================================== */

.stat-card {

    padding: 20px;

    height: 100%;

}


.stat-icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    margin-bottom: 14px;

    font-size: 21px;

}


/* BLUE */

.blue {

    color: #2563eb;

    background:
        #eff6ff;

}


/* RED */

.red {

    color: #dc2626;

    background:
        #fef2f2;

}


/* YELLOW */

.yellow {

    color: #d97706;

    background:
        #fffbeb;

}


/* GREEN */

.green {

    color: #059669;

    background:
        #ecfdf5;

}


.stat-card small {

    color: #64748b;

}


.stat-card h2 {

    margin: 4px 0 0;

    color: #0f172a;

    font-size: 28px;

    font-weight: 800;

}


/* =====================================================
   ALERT ITEM
===================================================== */

.alert-item {

    display: flex;

    gap: 15px;

    padding: 18px;

    border-bottom:
        1px solid
        #e2e8f0;

    transition: .2s ease;

}


.alert-item:last-child {

    border-bottom: none;

}


.alert-item.unread {

    background:
        rgba(37,99,235,.035);

}


.alert-item:hover {

    background:
        #f8fafc;

}


/* =====================================================
   ALERT ICON
===================================================== */

.alert-icon {

    width: 46px;

    height: 46px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    font-size: 20px;

}


.alert-high {

    color: #dc2626;

    background:
        #fef2f2;

}


.alert-medium {

    color: #d97706;

    background:
        #fffbeb;

}


.alert-low {

    color: #059669;

    background:
        #ecfdf5;

}


/* =====================================================
   ALERT CONTENT
===================================================== */

.alert-content {

    flex: 1;

    min-width: 0;

}


.alert-title {

    color: #0f172a;

    font-size: 13px;

    font-weight: 750;

}


.alert-message {

    margin-top: 4px;

    color: #64748b;

    font-size: 11px;

    line-height: 1.7;

}


.alert-meta {

    margin-top: 7px;

    color: #94a3b8;

    font-size: 9px;

}


.alert-meta i {

    color: #64748b;

}


.unread-dot {

    display: inline-block;

    width: 7px;

    height: 7px;

    margin-right: 5px;

    border-radius: 50%;

    background: #2563eb;

}


/* =====================================================
   ACTION BUTTONS
===================================================== */

.action-buttons {

    display: flex;

    align-items: center;

    gap: 7px;

    flex-shrink: 0;

}


.action-buttons .btn-outline-light {

    border:
        1px solid
        #cbd5e1 !important;

    color: #475569 !important;

    background: #ffffff !important;

    border-radius: 8px;

}


.action-buttons .btn-outline-light:hover {

    background:
        #eff6ff !important;

    border-color:
        #93c5fd !important;

    color:
        #2563eb !important;

}


/* =====================================================
   HEADER BUTTONS
===================================================== */

.btn-primary {

    border: none;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

}


.btn-primary:hover {

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        );

}


.alerts-page > div:first-child .btn-outline-light {

    border:
        1px solid
        #cbd5e1 !important;

    color:
        #334155 !important;

    background:
        #ffffff !important;

    border-radius: 9px;

}


.alerts-page > div:first-child .btn-outline-light:hover {

    background:
        #f1f5f9 !important;

    color:
        #0f172a !important;

}


/* =====================================================
   EMPTY ALERTS
===================================================== */

.empty-alerts {

    padding: 80px 20px;

    text-align: center;

}


.empty-alerts i {

    display: block;

    margin-bottom: 15px;

    color: #94a3b8;

    font-size: 48px;

}


.empty-alerts h4 {

    color: #0f172a;

    font-weight: 700;

}


.empty-alerts p {

    color: #64748b;

}


/* =====================================================
   FOOTER
===================================================== */

.alerts-page > .text-center a {

    color: #2563eb !important;

    font-size: 13px;

    font-weight: 600;

}


.alerts-page > .text-center a:hover {

    color: #1d4ed8 !important;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px) {

    .alerts-page {

        padding:
            30px 12px 50px;

    }

    .alert-item {

        align-items: flex-start;

        flex-direction: column;

    }

    .action-buttons {

        width: 100%;

    }

}


/* =====================================================
   PRINT
===================================================== */

@media print {

    body {

        background:
            #ffffff !important;

        color:
            #000000 !important;

    }

    .alerts-page {

        max-width: 100%;

        padding: 15px;

    }

    .panel {

        background:
            #ffffff !important;

        border:
            1px solid
            #d1d5db !important;

        box-shadow:
            none !important;

    }

    .action-buttons,
    .alerts-page > div:first-child .btn,
    .alerts-page > .text-center {

        display: none !important;

    }

    .page-title,
    .alert-title,
    .stat-card h2,
    .empty-alerts h4 {

        color:
            #000000 !important;

    }

    .page-subtitle,
    .alert-message,
    .alert-meta,
    .stat-card small,
    .empty-alerts p {

        color:
            #475569 !important;

    }

}

</style>

</head>


<body>


<div class="alerts-page">


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
            SECURITY CENTER
        </span>


        <h1 class="page-title">

            <i class="bi bi-bell-fill"></i>

            Security Alerts

        </h1>


        <p class="page-subtitle mb-0">

            Review important security events and account warnings.

        </p>

    </div>


    <div class="d-flex gap-2 flex-wrap">


        <?php if ($unreadAlerts > 0) { ?>

            <form method="POST">

                <button
                    type="submit"
                    name="mark_all_read"
                    class="btn btn-primary">

                    <i class="bi bi-check2-all"></i>

                    Mark All Read

                </button>

            </form>

        <?php } ?>


        <a
            href="security_center.php"
            class="btn btn-outline-light">

            <i class="bi bi-shield-lock"></i>

            Security Center

        </a>


    </div>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-3 mb-4">


    <div class="col-md-3">

        <div class="panel stat-card">

            <div class="stat-icon blue">

                <i class="bi bi-bell-fill"></i>

            </div>

            <small>
                Total Alerts
            </small>

            <h2>
                <?php echo $totalAlerts; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-3">

        <div class="panel stat-card">

            <div class="stat-icon red">

                <i class="bi bi-bell"></i>

            </div>

            <small>
                Unread
            </small>

            <h2>
                <?php echo $unreadAlerts; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-3">

        <div class="panel stat-card">

            <div class="stat-icon yellow">

                <i class="bi bi-exclamation-triangle"></i>

            </div>

            <small>
                High Risk
            </small>

            <h2>
                <?php echo $highAlerts; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-3">

        <div class="panel stat-card">

            <div class="stat-icon green">

                <i class="bi bi-info-circle"></i>

            </div>

            <small>
                Other Alerts
            </small>

            <h2>

                <?php
                echo $mediumAlerts + $lowAlerts;
                ?>

            </h2>

        </div>

    </div>


</div>


<!-- =====================================================
     ALERT LIST
===================================================== -->

<div class="panel">

<?php

if ($totalAlerts === 0) {

?>

    <div class="empty-alerts">

        <i class="bi bi-shield-check"></i>

        <h4>
            No Security Alerts
        </h4>

        <p>
            Your account currently has no recorded security alerts.
        </p>

    </div>

<?php

} else {

    foreach ($alerts as $alert) {

        $severity = strtolower(
            $alert['severity'] ?? 'low'
        );

        if ($severity === 'high') {

            $icon = 'bi-shield-exclamation';

            $iconClass = 'alert-high';

        } elseif ($severity === 'medium') {

            $icon = 'bi-exclamation-triangle-fill';

            $iconClass = 'alert-medium';

        } else {

            $icon = 'bi-info-circle-fill';

            $iconClass = 'alert-low';
        }


        $title = htmlspecialchars(
            $alert['title'] ?? 'Security Alert',
            ENT_QUOTES,
            'UTF-8'
        );


        $alertMessage = htmlspecialchars(
            $alert['message'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        );


        $alertType = htmlspecialchars(
            $alert['alert_type'] ?? 'security',
            ENT_QUOTES,
            'UTF-8'
        );


        $createdAt = "Unknown";

        if (!empty($alert['created_at'])) {

            $timestamp = strtotime(
                $alert['created_at']
            );

            if ($timestamp !== false) {

                $createdAt = date(
                    "d M Y • h:i A",
                    $timestamp
                );
            }
        }


        $isUnread =
            ((int) $alert['is_read'] === 0);

?>

        <div class="alert-item <?php echo $isUnread ? 'unread' : ''; ?>">

            <div class="alert-icon <?php echo $iconClass; ?>">

                <i class="bi <?php echo $icon; ?>"></i>

            </div>


            <div class="alert-content">

                <div class="alert-title">

                    <?php

                    if ($isUnread) {

                        echo '<span class="unread-dot"></span>';

                    }

                    ?>

                    <?php echo $title; ?>

                </div>


                <div class="alert-message">

                    <?php echo $alertMessage; ?>

                </div>


                <div class="alert-meta">

                    <i class="bi bi-tag"></i>

                    <?php echo $alertType; ?>

                    &nbsp; • &nbsp;

                    <i class="bi bi-clock"></i>

                    <?php echo $createdAt; ?>

                </div>

            </div>


            <div class="action-buttons">


                <?php

                if ($isUnread) {

?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="alert_id"
                            value="<?php echo (int) $alert['id']; ?>"
                        >

                        <button
                            type="submit"
                            name="mark_read"
                            class="btn btn-outline-light btn-sm"
                            title="Mark as read">

                            <i class="bi bi-check2"></i>

                        </button>

                    </form>

<?php

                }

?>


                <form
                    method="POST"
                    onsubmit="return confirm('Delete this security alert?');">

                    <input
                        type="hidden"
                        name="alert_id"
                        value="<?php echo (int) $alert['id']; ?>"
                    >

                    <button
                        type="submit"
                        name="delete_alert"
                        class="btn btn-outline-light btn-sm"
                        title="Delete alert">

                        <i class="bi bi-trash3"></i>

                    </button>

                </form>


            </div>

        </div>

<?php

    }

}

?>

</div>


<!-- =====================================================
     FOOTER
===================================================== -->

<div class="text-center mt-4">

    <a
        href="dashboard.php"
        class="text-decoration-none">

        <i class="bi bi-arrow-left"></i>

        Back to Dashboard

    </a>

</div>


</div>


</body>

</html>