<?php

session_start();

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int)$_SESSION['user_id'];

$message = "";
$message_type = "";


/* =====================================================
   DEACTIVATE FILE DROP
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['deactivate_drop'])
) {

    $drop_id =
        (int)($_POST['drop_id'] ?? 0);

    if ($drop_id > 0) {

        $updateQuery = mysqli_query(
            $conn,
            "UPDATE file_drops
             SET is_active=0
             WHERE id='$drop_id'
             AND user_id='$user_id'"
        );

        if ($updateQuery) {

            $message =
                "File Drop deactivated successfully.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to deactivate File Drop.";

            $message_type =
                "danger";
        }
    }
}


/* =====================================================
   DELETE FILE DROP
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_drop'])
) {

    $drop_id =
        (int)($_POST['drop_id'] ?? 0);

    if ($drop_id > 0) {

        $deleteQuery = mysqli_query(
            $conn,
            "DELETE FROM file_drops
             WHERE id='$drop_id'
             AND user_id='$user_id'"
        );

        if ($deleteQuery) {

            $message =
                "File Drop deleted successfully.";

            $message_type =
                "success";

        } else {

            $message =
                "Unable to delete File Drop.";

            $message_type =
                "danger";
        }
    }
}


/* =====================================================
   FETCH FILE DROPS
===================================================== */

$drops = [];

$dropsQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM file_drops
     WHERE user_id='$user_id'
     ORDER BY id DESC"
);


if ($dropsQuery) {

    while (
        $row =
        mysqli_fetch_assoc($dropsQuery)
    ) {

        $drops[] = $row;
    }
}


/* =====================================================
   STATISTICS
===================================================== */

$totalDrops = count($drops);

$activeDrops = 0;
$expiredDrops = 0;
$completedDrops = 0;
$totalReceivedFiles = 0;
$totalReceivedBytes = 0;
$totalReceivedMB =
    round(
        $totalReceivedBytes / 1024 / 1024,
        2
    );
foreach ($drops as $drop) {
    $totalReceivedFiles +=
        (int)$drop['uploaded_files'];
    $dropActive =
        (int)$drop['is_active'] === 1;

    $dropExpired =
        strtotime($drop['expires_at']) <= time();

    $dropCompleted =
        (int)$drop['uploaded_files'] >=
        (int)$drop['max_files'];


    if ($dropCompleted) {

        $completedDrops++;
    $currentDropId =
        (int)$drop['id'];

    $sizeQuery = mysqli_query(
        $conn,
        "SELECT COALESCE(
            SUM(file_size),
            0
        ) AS total_size
        FROM files
        WHERE user_id='$user_id'
        AND source_drop_id='$currentDropId'
        AND deleted=0"
    );

    $dropTotalSize = 0;

    if ($sizeQuery) {

        $sizeRow =
            mysqli_fetch_assoc(
                $sizeQuery
            );

        $dropTotalSize =
            (int)(
                $sizeRow['total_size'] ?? 0
            );
    }

    $totalReceivedBytes +=
        $dropTotalSize;
    } elseif (
        $dropExpired ||
        !$dropActive
    ) {

        $expiredDrops++;

    } else {

        $activeDrops++;
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
    My File Drops - NexaVault
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

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
    min-height: 100%;
}

body {
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #1e293b;

    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(37, 99, 235, 0.08),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(99, 102, 241, 0.07),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fafc 0%,
            #eef4ff 50%,
            #ffffff 100%
        );

    background-attachment: fixed;
}


/* =====================================================
   PAGE
===================================================== */

.page {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px 60px;
}


/* =====================================================
   COMMON PANEL
===================================================== */

.panel {
    border-radius: 20px;

    background:
        rgba(255, 255, 255, 0.96);

    border: 1px solid #e2e8f0;

    box-shadow:
        0 12px 35px rgba(15, 23, 42, 0.08);
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
    color: #0f172a;
    font-size: 31px;
    font-weight: 800;
    margin: 5px 0;
}

.page-title i {
    color: #2563eb;
}

.page-subtitle {
    color: #64748b;
    font-size: 13px;
}


/* =====================================================
   STATISTICS
===================================================== */

.stat-card {
    padding: 22px;
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
        rgba(37, 99, 235, 0.10);
}


/* GREEN */

.green {
    color: #059669;

    background:
        rgba(16, 185, 129, 0.10);
}


/* YELLOW */

.yellow {
    color: #d97706;

    background:
        rgba(245, 158, 11, 0.12);
}


/* RED */

.red {
    color: #dc2626;

    background:
        rgba(239, 68, 68, 0.10);
}


/* PURPLE */

.purple {
    color: #7c3aed;

    background:
        rgba(124, 58, 237, 0.10);
}


.stat-card small {
    color: #64748b;
    font-size: 12px;
}

.stat-card h2 {
    color: #0f172a;
    margin: 4px 0 0;
    font-size: 28px;
    font-weight: 800;
}


/* =====================================================
   DROP CARD
===================================================== */

.drop-card {
    padding: 22px;
    margin-bottom: 16px;
}

.drop-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;

    gap: 15px;
    flex-wrap: wrap;
}

.drop-name {
    color: #0f172a;
    font-size: 17px;
    font-weight: 750;
}

.drop-description {
    margin-top: 4px;
    color: #64748b;
    font-size: 11px;
}


/* =====================================================
   DROP INFORMATION
===================================================== */

.drop-info {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px;
}

.info-badge {
    padding: 7px 10px;

    border-radius: 20px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    color: #475569;

    font-size: 10px;
}

.info-badge i {
    color: #2563eb;
}


/* =====================================================
   STATUS
===================================================== */

.status {
    display: inline-flex;

    align-items: center;
    gap: 6px;

    padding: 7px 11px;

    border-radius: 30px;

    font-size: 10px;
    font-weight: 800;
}


/* ACTIVE */

.status-active {
    color: #047857;

    background:
        #ecfdf5;

    border: 1px solid #a7f3d0;
}


/* EXPIRED */

.status-expired {
    color: #b91c1c;

    background:
        #fef2f2;

    border: 1px solid #fecaca;
}


/* COMPLETED */

.status-completed {
    color: #1d4ed8;

    background:
        #eff6ff;

    border: 1px solid #bfdbfe;
}


/* =====================================================
   LINK
===================================================== */

.drop-link {
    margin-top: 15px;

    padding: 12px 14px;

    overflow-wrap: anywhere;

    border-radius: 11px;

    color: #475569;

    background: #f8fafc;

    border: 1px solid #e2e8f0;

    font-size: 10px;
}

.drop-link i {
    color: #2563eb;
    margin-right: 5px;
}


/* =====================================================
   ACTIONS
===================================================== */

.actions {
    display: flex;

    gap: 8px;

    flex-wrap: wrap;

    margin-top: 15px;
}

.btn {
    border-radius: 10px;
    font-weight: 600;
}


/* PRIMARY */

.btn-primary {
    border: none;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    box-shadow:
        0 5px 15px rgba(37, 99, 235, 0.18);
}

.btn-primary:hover {
    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4338ca
        );
}


/* OUTLINE LIGHT */

.btn-outline-light {
    color: #334155;

    background: #ffffff;

    border: 1px solid #cbd5e1;
}

.btn-outline-light:hover {
    color: #1d4ed8;

    background: #eff6ff;

    border-color: #93c5fd;
}


/* OUTLINE WARNING */

.btn-outline-warning {
    color: #b45309;

    background: #ffffff;

    border-color: #f59e0b;
}

.btn-outline-warning:hover {
    color: #92400e;

    background: #fffbeb;

    border-color: #d97706;
}


/* OUTLINE DANGER */

.btn-outline-danger {
    color: #dc2626;

    background: #ffffff;

    border-color: #fca5a5;
}

.btn-outline-danger:hover {
    color: #b91c1c;

    background: #fef2f2;

    border-color: #ef4444;
}


/* =====================================================
   ALERT
===================================================== */

.alert {
    border-radius: 12px;
    border-width: 1px;
    font-size: 13px;
}

.alert-success {
    color: #166534;
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.alert-danger {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-state {
    padding: 80px 20px;
    text-align: center;
}

.empty-state i {
    color: #2563eb;
    font-size: 48px;
}

.empty-state h4 {
    color: #0f172a;
    margin-top: 15px;
    font-weight: 700;
}

.empty-state p {
    color: #64748b;
    font-size: 12px;
}


/* =====================================================
   BOTTOM LINK
===================================================== */

.text-info {
    color: #2563eb !important;
}

.text-info:hover {
    color: #1d4ed8 !important;
}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 768px) {

    .page {
        padding: 30px 12px 50px;
    }

    .page-title {
        font-size: 27px;
    }

    .drop-card {
        padding: 18px;
    }

    .drop-header {
        flex-direction: column;
    }

    .drop-header > div:last-child {
        width: 100%;
    }

    .actions {
        width: 100%;
    }

    .actions .btn,
    .actions form {
        width: 100%;
    }

    .actions form .btn {
        width: 100%;
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

    .panel {
        box-shadow: none !important;
        border: 1px solid #d1d5db !important;
    }

    .actions,
    .btn,
    .text-info {
        display: none !important;
    }

}

</style>

</head>


<body>


<main class="page">


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
TEMPORARY FILE DROP
</span>

<h1 class="page-title">

<i class="bi bi-cloud-arrow-down-fill"></i>

My File Drops

</h1>

<p class="page-subtitle mb-0">

Create and manage temporary file collection links.

</p>

</div>


<div class="d-flex gap-2">

<a
    href="dashboard.php"
    class="btn btn-outline-light">

    <i class="bi bi-arrow-left"></i>

    Dashboard

</a>


<a
    href="create_drop.php"
    class="btn btn-primary">

    <i class="bi bi-plus-lg"></i>

    Create File Drop

</a>

</div>

</div>


<!-- =====================================================
     MESSAGE
===================================================== -->

<?php if ($message !== '') { ?>

<div class="alert alert-<?php echo $message_type; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-3 mb-4">


<div class="col-md-3">

<div class="panel stat-card">

<div class="stat-icon blue">

<i class="bi bi-collection"></i>

</div>

<small>
Total Drops
</small>

<h2>
<?php echo $totalDrops; ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="panel stat-card">

<div class="stat-icon green">

<i class="bi bi-check-circle"></i>

</div>

<small>
Active
</small>

<h2>
<?php echo $activeDrops; ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="panel stat-card">

<div class="stat-icon blue">

<i class="bi bi-cloud-check"></i>

</div>

<small>
Completed
</small>

<h2>
<?php echo $completedDrops; ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="panel stat-card">

<div class="stat-icon red">

<i class="bi bi-clock-history"></i>

</div>

<small>
Expired / Disabled
</small>

<h2>
<?php echo $expiredDrops; ?>
</h2>

</div>

</div>


</div>
<div class="row g-3 mb-4">

    <div class="col-md-6">

        <div class="panel stat-card">

            <div class="stat-icon yellow">

                <i class="bi bi-files"></i>

            </div>

            <small>
                Total Files Received
            </small>

            <h2>
                <?php echo $totalReceivedFiles; ?>
            </h2>

        </div>

    </div>


    <div class="col-md-6">

        <div class="panel stat-card">

            <div class="stat-icon purple">

                <i class="bi bi-hdd"></i>

            </div>

            <small>
                Total Received Size
            </small>

            <h2>
                <?php echo $totalReceivedMB; ?>
                <small style="font-size:12px;">
                    MB
                </small>
            </h2>

        </div>

    </div>

</div>

<!-- =====================================================
     DROPS
===================================================== -->

<?php if ($totalDrops === 0) { ?>


<div class="panel empty-state">

<i class="bi bi-cloud-arrow-down"></i>

<h4>
No File Drops Yet
</h4>

<p>

Create a temporary upload link to receive files securely.

</p>


<a
    href="create_drop.php"
    class="btn btn-primary mt-2">

    <i class="bi bi-plus-lg"></i>

    Create Your First File Drop

</a>

</div>


<?php } else { ?>


<?php foreach ($drops as $drop) { ?>


<?php

$dropId =
    (int)$drop['id'];

$dropToken =
    $drop['drop_token'];

$dropName =
    htmlspecialchars(
        $drop['drop_name']
    );

$dropDescription =
    htmlspecialchars(
        $drop['description'] ?? ''
    );

$uploaded =
    (int)$drop['uploaded_files'];

$maxFiles =
    (int)$drop['max_files'];
$remainingSlots =
    max(
        0,
        $maxFiles - $uploaded
    );
    $dropTotalSizeMB =
    round(
        $dropTotalSize / 1024 / 1024,
        2
    );
$expiresAt =
    $drop['expires_at'];

$isActive =
    (int)$drop['is_active'] === 1;

$isExpired =
    strtotime($expiresAt) <= time();

$isCompleted =
    $uploaded >= $maxFiles;


if ($isCompleted) {

    $statusText =
        "Completed";

    $statusClass =
        "status-completed";

    $statusIcon =
        "bi-check-circle-fill";

} elseif (
    !$isActive ||
    $isExpired
) {

    $statusText =
        "Expired / Disabled";

    $statusClass =
        "status-expired";

    $statusIcon =
        "bi-x-circle-fill";

} else {

    $statusText =
        "Active";

    $statusClass =
        "status-active";

    $statusIcon =
        "bi-broadcast-pin";
}


$relativeLink =
    "drop_upload.php?token=" .
    urlencode($dropToken);

?>

<div class="panel drop-card">


<div class="drop-header">


<div>

<div class="drop-name">

<?php echo $dropName; ?>

</div>


<?php if ($dropDescription !== '') { ?>

<div class="drop-description">

<?php echo $dropDescription; ?>

</div>

<?php } ?>


<div class="drop-info">

<span class="info-badge">

<i class="bi bi-files"></i>

<?php echo $uploaded; ?> /
<?php echo $maxFiles; ?>

Files
<span class="info-badge">

    <i class="bi bi-hourglass-split"></i>

    <?php echo $remainingSlots; ?>

    Slots Remaining

</span>


<span class="info-badge">

    <i class="bi bi-hdd"></i>

    <?php echo $dropTotalSizeMB; ?>

    MB Received

</span>
</span>
<div>
    <strong>Expiry:</strong>
    <?= htmlspecialchars($expiryText) ?>
</div>

<span class="info-badge">

<i class="bi bi-clock"></i>

<?php

echo date(
    "d M Y, h:i A",
    strtotime($expiresAt)
);

?>

</span>


<span class="info-badge">

<i class="bi bi-calendar3"></i>

Created

<?php

echo date(
    "d M Y",
    strtotime(
        $drop['created_at']
    )
);

?>

</span>

</div>

</div>


<div>

<span class="status <?php echo $statusClass; ?>">

<i class="bi <?php echo $statusIcon; ?>"></i>

<?php echo $statusText; ?>

</span>

</div>


</div>


<div class="drop-link">

<i class="bi bi-link-45deg"></i>

<?php

echo htmlspecialchars(
    $relativeLink
);

?>

</div>


<div class="actions">


<button
    type="button"
    class="btn btn-outline-light btn-sm"
    onclick="copyDropLink(
        '<?php
        echo htmlspecialchars(
            $relativeLink,
            ENT_QUOTES
        );
        ?>'
    )">

    <i class="bi bi-copy"></i>

    Copy Link

</button>


<a
    href="<?php echo htmlspecialchars($relativeLink); ?>"
    target="_blank"
    class="btn btn-outline-light btn-sm">

    <i class="bi bi-box-arrow-up-right"></i>

    Open

</a>


<?php if ($isActive && !$isExpired && !$isCompleted) { ?>


<form method="POST">

<input
    type="hidden"
    name="drop_id"
    value="<?php echo $dropId; ?>"
>


<button
    type="submit"
    name="deactivate_drop"
    class="btn btn-outline-warning btn-sm"
    onclick="
        return confirm(
            'Deactivate this File Drop?'
        );
    ">

    <i class="bi bi-pause-circle"></i>

    Deactivate

</button>

</form>


<?php } ?>


<form
    method="POST"
    onsubmit="
        return confirm(
            'Delete this File Drop permanently?'
        );
    "
>

<input
    type="hidden"
    name="drop_id"
    value="<?php echo $dropId; ?>"
>


<button
    type="submit"
    name="delete_drop"
    class="btn btn-outline-danger btn-sm">

    <i class="bi bi-trash3"></i>

    Delete

</button>

</form>


</div>


</div>


<?php } ?>


<?php } ?>


<div class="text-center mt-4">

<a
    href="dashboard.php"
    class="text-info text-decoration-none">

    <i class="bi bi-arrow-left"></i>

    Back to Dashboard

</a>

</div>


</main>


<script>

function copyDropLink(link)
{

    const fullLink =
        window.location.origin +
        window.location.pathname
            .replace(
                "file_drops.php",
                ""
            ) +
        link;


    navigator.clipboard
        .writeText(fullLink)
        .then(function () {

            alert(
                "File Drop link copied successfully."
            );

        })
        .catch(function () {

            alert(
                "Unable to copy the link."
            );

        });

}

</script>


</body>

</html>