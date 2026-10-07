<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   GET SHARED FILES
===================================================== */

$sql = "
    SELECT *
    FROM files
    WHERE user_id='$user_id'
    AND is_shared=1
    AND deleted=0
    ORDER BY id DESC
";

$result = mysqli_query($conn, $sql);


/* =====================================================
   STATISTICS
===================================================== */

$totalShared = 0;
$activeLinks = 0;
$expiredLinks = 0;
$totalDownloads = 0;

$files = [];

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {

        $files[] = $row;

        $totalShared++;

        /* Shared download count */
        $downloads = (int)($row['link_used'] ?? 0);

        $totalDownloads += $downloads;

        /* Expiry */
        if (
            !empty($row['expires_at']) &&
            strtotime($row['expires_at']) <= time()
        ) {
            $expiredLinks++;
        } else {
            $activeLinks++;
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

<title>Shared Files - NexaVault</title>

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

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37, 99, 235, 0.08),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(99, 102, 241, 0.07),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #f8fafc
        );

    color: #1f2937;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;
}


/* ==============================
   CONTAINER
================================ */

.page-container {
    max-width: 1400px;
    margin: auto;
    padding: 45px 25px;
}


/* ==============================
   HEADER
================================ */

.page-title {
    font-size: 34px;
    font-weight: 800;
    color: #172554;
}

.page-subtitle {
    color: #64748b;
}


/* ==============================
   GLASS CARD
================================ */

.glass-card {
    background: #ffffff;

    border:
        1px solid
        #dbe5f2;

    border-radius: 20px;

    box-shadow:
        0 18px 45px
        rgba(30, 64, 175, 0.08);
}


/* ==============================
   STAT CARDS
================================ */

.stat-card {
    padding: 24px;
    height: 100%;

    transition:
        transform .25s ease,
        border .25s ease;
}

.stat-card:hover {
    transform: translateY(-5px);

    border-color:
        #93c5fd;
}

.stat-icon {
    width: 52px;
    height: 52px;

    border-radius: 15px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 23px;

    margin-bottom: 18px;
}

.stat-blue {
    background: #eff6ff;
    color: #2563eb;
}

.stat-green {
    background: #ecfdf5;
    color: #059669;
}

.stat-red {
    background: #fef2f2;
    color: #dc2626;
}

.stat-purple {
    background: #f5f3ff;
    color: #7c3aed;
}

.stat-card h2 {
    font-size: 30px;
    font-weight: 800;

    color: #172554;

    margin: 0;
}

.stat-card span {
    color: #64748b;
    font-size: 14px;
}


/* ==============================
   TABLE
================================ */

.table-card {
    overflow: hidden;
}

.table {
    margin: 0;

    color: #334155 !important;

    --bs-table-bg: transparent;

    --bs-table-color: #334155;

    --bs-table-border-color:
        #e2e8f0;

    background: transparent !important;
}


/* ==============================
   TABLE HEADER
================================ */

.table thead {
    background: #eff6ff;
}

.table thead th {
    color: #475569 !important;

    font-size: 13px;

    text-transform: uppercase;

    letter-spacing: .5px;

    padding: 17px;

    border-color:
        #dbe5f2;

    background:
        #eff6ff !important;
}


/* ==============================
   TABLE BODY
================================ */

.table tbody {
    background: #ffffff !important;
}

.table tbody tr {
    background: #ffffff !important;

    transition:
        background .2s ease;
}

.table tbody td {
    padding: 18px 17px;

    vertical-align: middle;

    color: #334155 !important;

    background:
        #ffffff !important;

    border-color:
        #e2e8f0 !important;
}


/* ==============================
   TABLE HOVER
================================ */

.table tbody tr:hover,
.table tbody tr:hover td {
    background:
        #f8fafc !important;

    color: #1e293b !important;
}


/* ==============================
   FILE
================================ */

.file-icon {
    width: 45px;
    height: 45px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        #eff6ff;

    color:
        #2563eb;

    font-size: 20px;
}

.file-name {
    color: #1e293b !important;

    font-weight: 700;

    font-size: 14px;

    word-break: break-word;
}

.file-subtitle {
    color: #64748b !important;

    font-size: 10px;
}


/* ==============================
   STATUS
================================ */

.status {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 11px;

    border-radius: 30px;

    font-size: 12px;

    font-weight: 700;
}

.status-active {
    background:
        #ecfdf5;

    color:
        #059669;
}

.status-expired {
    background:
        #fef2f2;

    color:
        #dc2626;
}


/* ==============================
   DOWNLOAD
================================ */

.download-count {
    color: #475569;

    font-weight: 600;
}

.download-count i {
    color: #2563eb;

    margin-right: 5px;
}


/* ==============================
   BUTTONS
================================ */

.btn {
    border-radius: 10px;
}

.btn-primary {
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    border: none;
}

.btn-primary:hover {
    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        );
}

.btn-outline-light {
    border-color:
        #cbd5e1;

    color:
        #334155;

    background:
        #ffffff;
}

.btn-outline-light:hover {
    background:
        #eff6ff;

    color:
        #1d4ed8;

    border-color:
        #93c5fd;
}


/* ==============================
   EMPTY
================================ */

.empty-state {
    padding: 80px 20px;

    text-align: center;
}

.empty-state-icon {
    font-size: 65px;

    color: #94a3b8;
}

.empty-state h4 {
    color: #1e293b;

    margin-top: 20px;
}

.empty-state p {
    color: #64748b;
}


/* ==============================
   SECURITY INFORMATION
================================ */

.security-title {
    color: #1e293b !important;
}

.security-text {
    color: #64748b !important;
}


/* ==============================
   MOBILE
================================ */

@media(max-width:768px) {

    .page-title {
        font-size: 27px;
    }

    .page-container {
        padding: 30px 15px;
    }

}


/* ==============================
   PRINT FRIENDLY
================================ */

@media print {

    body {
        background: white !important;
        color: #000 !important;
    }

    .glass-card {
        background: white !important;

        box-shadow: none !important;

        border:
            1px solid #ccc !important;
    }

    .page-title,
    .stat-card h2,
    .file-name {
        color: #000 !important;
    }

    .page-subtitle,
    .stat-card span,
    .file-subtitle,
    .security-text {
        color: #444 !important;
    }

    .table tbody td {
        background: white !important;
        color: #000 !important;
    }

}

</style>

</head>


<body>


<div class="page-container">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="d-flex justify-content-between
            align-items-center
            flex-wrap gap-3 mb-4">

    <div>

        <h1 class="page-title">

            <i class="bi bi-share-fill"></i>

            Shared Files

        </h1>

        <p class="page-subtitle mb-0">

            Manage and monitor your secure sharing links.

        </p>

    </div>


    <a
        href="dashboard.php"
        class="btn btn-outline-light">

        <i class="bi bi-arrow-left"></i>

        Dashboard

    </a>

</div>


<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-4 mb-4">


    <!-- TOTAL SHARED -->

    <div class="col-xl-3 col-md-6">

        <div class="glass-card stat-card">

            <div class="stat-icon stat-blue">

                <i class="bi bi-share-fill"></i>

            </div>

            <span>
                Total Shared Files
            </span>

            <h2>
                <?php echo $totalShared; ?>
            </h2>

        </div>

    </div>


    <!-- ACTIVE LINKS -->

    <div class="col-xl-3 col-md-6">

        <div class="glass-card stat-card">

            <div class="stat-icon stat-green">

                <i class="bi bi-shield-check"></i>

            </div>

            <span>
                Active Links
            </span>

            <h2>
                <?php echo $activeLinks; ?>
            </h2>

        </div>

    </div>


    <!-- EXPIRED LINKS -->

    <div class="col-xl-3 col-md-6">

        <div class="glass-card stat-card">

            <div class="stat-icon stat-red">

                <i class="bi bi-clock-history"></i>

            </div>

            <span>
                Expired Links
            </span>

            <h2>
                <?php echo $expiredLinks; ?>
            </h2>

        </div>

    </div>


    <!-- TOTAL DOWNLOADS -->

    <div class="col-xl-3 col-md-6">

        <div class="glass-card stat-card">

            <div class="stat-icon stat-purple">

                <i class="bi bi-download"></i>

            </div>

            <span>
                Total Downloads
            </span>

            <h2>
                <?php echo $totalDownloads; ?>
            </h2>

        </div>

    </div>


</div>


<!-- =====================================================
     TABLE
===================================================== -->

<div class="glass-card table-card">


    <div class="p-4 border-bottom"
         style="border-color:rgba(255,255,255,.06)!important;">

        <div class="d-flex justify-content-between
                    align-items-center
                    flex-wrap gap-2">

            <div>

                <small
                    style="color:#64748b;
                           letter-spacing:1px;">

                    SECURE SHARE MANAGEMENT

                </small>

                <h4 class="fw-bold mb-0" style="color:#1e293b;">
    Your Sharing Links
</h4>
            </div>


            <span
                class="badge rounded-pill"
                style="
                    background:rgba(37,99,235,.15);
                    color:#60a5fa;
                ">

                <?php echo $totalShared; ?>

                Links

            </span>

        </div>

    </div>


    <?php if ($totalShared === 0) { ?>


        <!-- EMPTY -->

        <div class="empty-state">

            <div class="empty-state-icon">

                <i class="bi bi-link-45deg"></i>

            </div>

            <h4>

                No Shared Files

            </h4>

            <p>

                You haven't created any secure
                sharing links yet.

            </p>

            <a
                href="dashboard.php"
                class="btn btn-primary">

                <i class="bi bi-arrow-left"></i>

                Back to Files

            </a>

        </div>


    <?php } else { ?>


        <!-- TABLE -->

        <div class="table-responsive">

            <table class="table align-middle">


                <thead>

                    <tr>

                        <th>File</th>

                        <th>Status</th>

                        <th>Expiry</th>

                        <th>Downloads</th>

                        <th>Limit</th>

                        <th>Share Link</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>


                <?php foreach ($files as $row) { ?>


                    <?php

                    /* =================================================
                       SHARE LINK
                    ================================================= */

                    $shareLink =
                        "http://" .
                        $_SERVER['HTTP_HOST'] .
                        "/NexaVault/view_share.php?token=" .
                        urlencode(
                            $row['share_token']
                        );


                    /* =================================================
                       FILE NAME
                    ================================================= */

                    $fileName =
                        htmlspecialchars(
                            $row['original_name'] ??
                            'Unknown File',
                            ENT_QUOTES
                        );


                    /* =================================================
                       EXPIRY
                    ================================================= */

                    $expired = false;

                    if (
                        !empty($row['expires_at']) &&
                        strtotime($row['expires_at']) <= time()
                    ) {

                        $expired = true;

                    }


                    /* =================================================
                       DOWNLOADS
                    ================================================= */

                    $downloads =
                        (int)($row['link_used'] ?? 0);


                    /* =================================================
                       LIMIT
                    ================================================= */

                    $limit =
                        (int)($row['download_limit'] ?? 0);

                    ?>


                    <tr>


                        <!-- FILE -->

                        <td>

                            <div class="d-flex
                                        align-items-center
                                        gap-3">

                                <div class="file-icon">

                                    <i class="bi bi-file-earmark-fill"></i>

                                </div>


                                <div>

                                    <div class="file-name">

                                        <?php echo $fileName; ?>

                                    </div>

                                    <div class="file-subtitle">

                                        NexaVault Secure Storage

                                    </div>

                                </div>

                            </div>

                        </td>


                        <!-- STATUS -->

                        <td>

                            <?php if ($expired) { ?>

                                <span class="status status-expired">

                                    <i class="bi bi-x-circle-fill"></i>

                                    Expired

                                </span>

                            <?php } else { ?>

                                <span class="status status-active">

                                    <i class="bi bi-check-circle-fill"></i>

                                    Active

                                </span>

                            <?php } ?>

                        </td>


                        <!-- EXPIRY -->

                        <td>

                            <?php

                            if (
                                empty($row['expires_at'])
                            ) {

                                echo '
                                    <span style="color:#94a3b8;">
                                        Never
                                    </span>
                                ';

                            } else {

                                echo date(
                                    "d M Y • H:i",
                                    strtotime(
                                        $row['expires_at']
                                    )
                                );

                            }

                            ?>

                        </td>


                        <!-- DOWNLOADS -->

                        <td>

                            <div class="download-count">

                                <i class="bi bi-download"></i>

                                <?php echo $downloads; ?>

                            </div>

                        </td>


                        <!-- LIMIT -->

                        <td>

                            <?php

                            if ($limit === 0) {

                                echo '
                                    <span style="color:#34d399;">
                                        Unlimited
                                    </span>
                                ';

                            } else {

                                echo
                                    $downloads .
                                    " / " .
                                    $limit;

                            }

                            ?>

                        </td>


                        <!-- SHARE LINK -->

                        <td>

                            <?php if (
                                !$expired &&
                                !empty($row['share_token'])
                            ) { ?>

                                <div class="d-flex
                                            gap-2
                                            flex-wrap">

                                    <a
                                        href="<?php
                                        echo htmlspecialchars(
                                            $shareLink
                                        );
                                        ?>"
                                        target="_blank"
                                        class="btn btn-primary btn-sm">

                                        <i class="bi bi-box-arrow-up-right"></i>

                                        Open

                                    </a>


                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm"
                                        onclick="copyShareLink('<?php
                                        echo htmlspecialchars(
                                            $shareLink,
                                            ENT_QUOTES
                                        );
                                        ?>')">

                                        <i class="bi bi-clipboard"></i>

                                        Copy

                                    </button>

                                </div>

                            <?php } else { ?>

                                <span class="badge bg-secondary">

                                    Link Unavailable

                                </span>

                            <?php } ?>

                        </td>


                        <!-- ACTION -->

                        <td>

                            <div class="d-flex
                                        gap-2
                                        flex-wrap">


                                <!-- NEW LINK -->

                                <a
                                    href="share.php?id=<?php echo (int)$row['id']; ?>"
                                    class="btn btn-primary btn-sm">

                                    <i class="bi bi-shield-lock-fill"></i>

                                    New Link

                                </a>


                                <?php if (!$expired) { ?>


                                    <!-- REVOKE -->

                                    <a
                                        href="revoke_share.php?id=<?php echo (int)$row['id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="
                                            return confirm(
                                                'Revoke this sharing link?'
                                            );
                                        ">

                                        <i class="bi bi-x-circle"></i>

                                        Revoke

                                    </a>


                                <?php } else { ?>


                                    <!-- EXPIRED -->

                                    <span
                                        class="badge bg-secondary d-flex
                                               align-items-center">

                                        <i
                                            class="bi bi-clock-history me-1">
                                        </i>

                                        Expired

                                    </span>


                                <?php } ?>


                            </div>

                        </td>


                    </tr>


                <?php } ?>


                </tbody>

            </table>

        </div>


    <?php } ?>


</div>


<!-- =====================================================
     SECURITY INFORMATION
===================================================== -->

<div class="glass-card mt-4 p-4">


    <div class="d-flex
                align-items-center
                gap-3">


        <div class="stat-icon stat-green mb-0">

            <i class="bi bi-shield-lock-fill"></i>

        </div>


        <div>

            <h5 class="fw-bold mb-1" style="color:#1e293b;">
    NexaVault Secure Sharing
</h5>


            <p class="mb-0" style="color:#64748b;">

                Your sharing links are protected with
                secure random tokens, password protection,
                expiry controls and download limits.

            </p>

        </div>


    </div>


</div>


</div>


<script>

/* =====================================================
   COPY SHARE LINK
===================================================== */

function copyShareLink(link) {

    navigator.clipboard.writeText(link)

        .then(function () {

            alert(
                "✅ Secure link copied!"
            );

        })

        .catch(function () {

            alert(
                "❌ Unable to copy link."
            );

        });

}

</script>


</body>

</html>