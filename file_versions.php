<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$file_id = (int) $_GET['id'];


/* =====================================================
   CHECK FILE OWNERSHIP
===================================================== */

$fileQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE id='$file_id'
     AND user_id='$user_id'
     AND deleted=0
     LIMIT 1"
);

if (!$fileQuery || mysqli_num_rows($fileQuery) == 0) {
    die("File not found or access denied.");
}

$file = mysqli_fetch_assoc($fileQuery);


/* =====================================================
   GET VERSION HISTORY
===================================================== */

$versionQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM file_versions
     WHERE file_id='$file_id'
     AND user_id='$user_id'
     ORDER BY version_number DESC"
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Version History - NexaVault</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: "Segoe UI", Arial, sans-serif;
    color: #1e293b;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37,99,235,.08),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(139,92,246,.07),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #ffffff
        );

    background-attachment: fixed;
}


/* =====================================================
   MAIN PAGE
===================================================== */

.version-page {
    max-width: 1100px;
    margin: auto;
    padding: 45px 20px 70px;
}


/* =====================================================
   TOP TITLE
===================================================== */

.version-title {
    color: #172033 !important;
    font-size: 28px;
    font-weight: 750;
}

.version-subtitle {
    color: #64748b;
    margin: 4px 0 0;
}

.version-page > .d-flex > div > div:first-child {
    color: #2563eb !important;
}


/* =====================================================
   DASHBOARD BUTTON
===================================================== */

.version-page .btn-outline-light {
    color: #334155 !important;
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    font-weight: 600;
    box-shadow: 0 3px 10px rgba(15,23,42,.05);
}

.version-page .btn-outline-light:hover {
    color: #1d4ed8 !important;
    background: #eff6ff !important;
    border-color: #93c5fd !important;
}


/* =====================================================
   MAIN PANEL
===================================================== */

.version-panel {
    background: #ffffff;

    border: 1px solid #dbe5f1;

    border-radius: 22px;

    box-shadow:
        0 18px 45px rgba(30,64,175,.08);

    overflow: hidden;
}


/* =====================================================
   HEADER
===================================================== */

.version-header {
    padding: 25px;

    border-bottom:
        1px solid #e5eaf1;
}


/* =====================================================
   CURRENT FILE
===================================================== */

.current-file {
    display: flex;
    align-items: center;
    gap: 15px;

    margin-top: 20px;
    padding: 15px;

    border-radius: 14px;

    background: #f8fbff;

    border: 1px solid #e2e8f0;
}


/* =====================================================
   FILE ICON
===================================================== */

.file-icon {
    width: 48px;
    height: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: #eff6ff;

    color: #2563eb;

    font-size: 22px;

    flex-shrink: 0;
}


/* =====================================================
   FILE NAME
===================================================== */

.file-name {
    color: #172033;

    font-size: 14px;

    font-weight: 650;

    word-break: break-word;
}

.file-info {
    color: #64748b;

    font-size: 11px;
}


/* =====================================================
   VERSION LIST
===================================================== */

.version-list {
    padding: 15px 25px 30px;
}


/* =====================================================
   VERSION ITEM
===================================================== */

.version-item {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 17px 0;

    border-bottom:
        1px solid #e8edf4;
}

.version-item:last-child {
    border-bottom: none;
}


/* =====================================================
   LEFT SECTION
===================================================== */

.version-left {
    display: flex;

    align-items: center;

    gap: 13px;

    min-width: 0;
}


/* =====================================================
   VERSION BADGE
===================================================== */

.version-badge {
    min-width: 42px;
    height: 42px;

    padding: 0 8px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    color: #1d4ed8;

    background: #eff6ff;

    border:
        1px solid #bfdbfe;

    font-size: 11px;

    font-weight: 700;

    flex-shrink: 0;
}


/* =====================================================
   VERSION DETAILS
===================================================== */

.version-details strong {
    display: block;

    color: #1e293b;

    font-size: 13px;
}

.version-details small {
    display: block;

    margin-top: 3px;

    color: #64748b;

    font-size: 10px;
}


/* =====================================================
   CURRENT VERSION BADGE
===================================================== */

.current-version {
    display: inline-flex;

    align-items: center;

    gap: 5px;

    margin-top: 6px;

    padding: 4px 8px;

    border-radius: 20px;

    background: #ecfdf5;

    color: #047857;

    border: 1px solid #bbf7d0;

    font-size: 9px;

    font-weight: 700;
}


/* =====================================================
   ACTION BUTTONS
===================================================== */

.version-action {
    display: flex;

    align-items: center;

    gap: 7px;

    flex-shrink: 0;
}

.version-action a {
    width: 34px;
    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    color: #64748b;

    background: #f8fafc;

    border:
        1px solid #dbe3ee;

    text-decoration: none;

    transition: .2s ease;
}

.version-action a:hover {
    color: #2563eb;

    background: #eff6ff;

    border-color: #93c5fd;

    transform: translateY(-2px);
}


/* =====================================================
   EMPTY VERSION
===================================================== */

.empty-version {
    padding: 70px 20px;

    text-align: center;

    color: #64748b;
}

.empty-version i {
    font-size: 45px;

    color: #94a3b8;
}

.empty-version h4 {
    color: #1e293b !important;
}

.empty-version p {
    color: #64748b;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:768px) {

    .version-page {
        padding: 30px 15px 50px;
    }

    .version-title {
        font-size: 24px;
    }

    .version-item {
        align-items: flex-start;

        flex-direction: column;

        gap: 14px;
    }

    .version-action {
        width: 100%;
    }

    .version-action a {
        width: 38px;
        height: 38px;
    }

}


/* =====================================================
   PRINT FRIENDLY
===================================================== */

@media print {

    body {
        background: #ffffff !important;

        color: #000000 !important;
    }

    .version-page {
        max-width: 100%;

        padding: 15px;
    }

    .version-panel {
        border: 1px solid #cccccc;

        box-shadow: none;
    }

    .version-page .btn-outline-light {
        display: none;
    }

    .version-title,
    .file-name,
    .version-details strong,
    .empty-version h4 {
        color: #000000 !important;
    }

    .version-subtitle,
    .file-info,
    .version-details small,
    .empty-version {
        color: #555555 !important;
    }

    .version-action {
        display: none;
    }

    .current-file,
    .version-badge {
        background: #f5f5f5 !important;
        border-color: #cccccc !important;
    }

    .current-version {
        background: #f0fdf4 !important;
        color: #166534 !important;
        border-color: #bbf7d0 !important;
    }

}

</style>
</head>

<body>


<main class="version-page">


<div class="d-flex justify-content-between
            align-items-center
            flex-wrap gap-3 mb-4">


<div>

<div style="
    color:#6ea1ff;
    font-size:10px;
    font-weight:800;
    letter-spacing:2px;
">

VERSION CONTROL

</div>

<h1 class="version-title mb-1">

File Version History

</h1>

<p class="version-subtitle">

View previous versions of your file.

</p>

</div>


<a href="dashboard.php"
   class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>

</div>


<div class="version-panel">


<div class="version-header">


<div class="current-file">


<div class="file-icon">

<i class="bi bi-file-earmark-text"></i>

</div>


<div>

<div class="file-name">

<?php
echo htmlspecialchars(
    $file['original_name']
);
?>

</div>

<div class="file-info">

Current file •
<?php
echo round(
    ($file['file_size'] ?? 0) / 1024,
    2
);
?>
 KB

</div>

</div>


</div>


</div>


<div class="version-list">


<?php

if ($versionQuery &&
    mysqli_num_rows($versionQuery) > 0) {

    while ($version =
           mysqli_fetch_assoc($versionQuery)) {

        $versionId =
            (int)$version['id'];

        $versionNumber =
            (int)$version['version_number'];

        $versionSize =
            round(
                ($version['file_size'] ?? 0)
                / 1024,
                2
            );

?>

<div class="version-item">


<div class="version-left">


<div class="version-badge">

V<?php echo $versionNumber; ?>

</div>


<div class="version-details">

<strong>

<?php
echo htmlspecialchars(
    $version['original_name']
);
?>

</strong>


<small>

<?php echo $versionSize; ?> KB
•
<?php

echo date(
    "d M Y • H:i",
    strtotime(
        $version['uploaded_at']
    )
);

?>

</small>


<?php if (
    (int)$version['is_current'] === 1
) { ?>

<span class="current-version">

<i class="bi bi-check-circle-fill"></i>

Current Version

</span>

<?php } ?>


</div>

</div>


<div class="version-action">


<!-- DOWNLOAD VERSION -->

<a
href="download_version.php?id=<?php echo $versionId; ?>"
title="Download Version">

<i class="bi bi-download"></i>

</a>


<!-- RESTORE -->

<?php if (
    (int)$version['is_current'] !== 1
) { ?>

<a
href="restore_version.php?id=<?php echo $versionId; ?>"
title="Restore Version"
onclick="
return confirm(
'Restore this version as the current file?'
);
">

<i class="bi bi-arrow-counterclockwise"></i>

</a>

<?php } ?>


</div>


</div>


<?php

    }

} else {

?>

<div class="empty-version">

<i class="bi bi-clock-history"></i>

<h4 class="mt-3 text-white">

No Version History

</h4>

<p>

No versions have been recorded for this file yet.

</p>

</div>

<?php } ?>


</div>

</div>


</main>


</body>

</html>