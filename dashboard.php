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


$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user'];


/* =====================================================
   UNREAD SECURITY ALERTS
===================================================== */

$unreadAlerts = 0;

$alertCountQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM security_alerts
     WHERE user_id='$user_id'
     AND is_read=0"
);

if ($alertCountQuery) {

    $alertCountRow = mysqli_fetch_assoc(
        $alertCountQuery
    );

    $unreadAlerts =
        (int)($alertCountRow['total'] ?? 0);
}


/* =====================================================
   USER STATISTICS
===================================================== */

/* Total Files */

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0"
);

$totalFiles = $query
    ? (int)(mysqli_fetch_assoc($query)['total'] ?? 0)
    : 0;


/* Favorite Files */

$query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND favorite=1
     AND deleted=0"
);

$totalFavorite = $query
    ? (int)(mysqli_fetch_assoc($query)['total'] ?? 0)
    : 0;


/* Total Downloads */

$query = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(downloaded),0) AS total
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0"
);

$totalDownloads = $query
    ? (int)(mysqli_fetch_assoc($query)['total'] ?? 0)
    : 0;


/* Storage Used */

$query = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(file_size),0) AS total
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0"
);

$storageBytes = $query
    ? (float)(mysqli_fetch_assoc($query)['total'] ?? 0)
    : 0;

$storage = round(
    $storageBytes / 1024 / 1024,
    2
);

$maxStorage = 100;

$storagePercent = (
    $maxStorage > 0
)
    ? ($storage / $maxStorage) * 100
    : 0;

if ($storagePercent > 100) {
    $storagePercent = 100;
}


/* =====================================================
   FILE DROP STATISTICS
===================================================== */

$totalDrops = 0;
$activeDrops = 0;
$totalDroppedFiles = 0;

$dropStatsQuery = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_drops,
        COALESCE(
            SUM(
                CASE
                    WHEN is_active=1
                    AND expires_at > NOW()
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS active_drops,
        COALESCE(SUM(uploaded_files),0) AS total_received
     FROM file_drops
     WHERE user_id='$user_id'"
);

if ($dropStatsQuery) {

    $dropStats = mysqli_fetch_assoc(
        $dropStatsQuery
    );

    $totalDrops =
        (int)($dropStats['total_drops'] ?? 0);

    $activeDrops =
        (int)($dropStats['active_drops'] ?? 0);

    $totalDroppedFiles =
        (int)($dropStats['total_received'] ?? 0);
}


/* =====================================================
   SHARED FILES
===================================================== */

$sharedFiles = 0;

$shareCheck = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'shared_files'"
);

if (
    $shareCheck &&
    mysqli_num_rows($shareCheck) > 0
) {

    $query = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM shared_files
         WHERE user_id='$user_id'"
    );

    if ($query) {

        $sharedFiles =
            (int)(
                mysqli_fetch_assoc($query)['total']
                ?? 0
            );
    }
}


/* =====================================================
   NOTIFICATIONS
===================================================== */

$notificationCount = 0;

$notificationCheck = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'notifications'"
);

if (
    $notificationCheck &&
    mysqli_num_rows($notificationCheck) > 0
) {

    $query = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM notifications
         WHERE user_id='$user_id'
         AND is_read=0"
    );

    if ($query) {

        $notificationCount =
            (int)(
                mysqli_fetch_assoc($query)['total']
                ?? 0
            );
    }
}


/* =====================================================
   FILE ANALYTICS
===================================================== */

$pdfCount = 0;
$imageCount = 0;
$wordCount = 0;
$zipCount = 0;

$query = mysqli_query(
    $conn,
    "SELECT file_type
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0"
);

if ($query) {

    while ($file = mysqli_fetch_assoc($query)) {

        $type = strtolower(
            $file['file_type'] ?? ''
        );

        if (strpos($type, 'pdf') !== false) {

            $pdfCount++;

        } elseif (strpos($type, 'image') !== false) {

            $imageCount++;

        } elseif (
            strpos($type, 'word') !== false ||
            strpos($type, 'document') !== false
        ) {

            $wordCount++;

        } elseif (strpos($type, 'zip') !== false) {

            $zipCount++;
        }
    }
}


/* =====================================================
   RECENT ACTIVITY
===================================================== */

$activityResult = false;

$activityCheck = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'activity_log'"
);

if (
    $activityCheck &&
    mysqli_num_rows($activityCheck) > 0
) {

    $activityResult = mysqli_query(
        $conn,
        "SELECT *
         FROM activity_log
         WHERE user_id='$user_id'
         ORDER BY created_at DESC
         LIMIT 5"
    );
}


/* =====================================================
   TAG FILTERS
===================================================== */

$tagList = [];

$tagQuery = mysqli_query(
    $conn,
    "SELECT tags
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0
     AND tags IS NOT NULL
     AND tags!=''"
);

if ($tagQuery) {

    while ($tagRow = mysqli_fetch_assoc($tagQuery)) {

        $tags = explode(
            ',',
            $tagRow['tags']
        );

        foreach ($tags as $tag) {

            $tag = trim($tag);

            if ($tag !== '') {
                $tagList[] = $tag;
            }
        }
    }
}

$tagList = array_values(
    array_unique($tagList)
);

sort($tagList);


/* =====================================================
   USER INITIAL
===================================================== */

$userInitial = strtoupper(
    substr(
        trim($user_name),
        0,
        1
    )
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>NexaVault Dashboard</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">


<!-- Bootstrap Icons -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- Dashboard CSS -->

<link
    rel="stylesheet"
    href="assets/css/dashboard.css?v=301">

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="nv-dashboard-navbar">

    <div class="container-fluid px-lg-5">

        <div class="d-flex align-items-center justify-content-between">


            <!-- BRAND -->

            <a
                href="index.php"
                class="nv-dashboard-brand text-decoration-none">

                <img
                    src="assets/images/logo.png"
                    alt="NexaVault"
                    class="dashboard-logo">

                <div class="dashboard-brand-text">

                    <strong>
                        Nexa<span>Vault</span>
                    </strong>

                    <small>
                        Secure • Share • Trust
                    </small>

                </div>

            </a>


            <!-- NAV RIGHT -->

            <div class="dashboard-nav-right">


                <a
                    href="index.php"
                    class="dashboard-nav-link d-none d-md-inline-flex">

                    <i class="bi bi-house"></i>

                    Home

                </a>


                <a
                    href="notifications.php"
                    class="notification-button">

                    <i class="bi bi-bell-fill"></i>

                    <?php if ($notificationCount > 0) { ?>

                        <span class="notification-badge">

                            <?php echo $notificationCount; ?>

                        </span>

                    <?php } ?>

                </a>


                <div class="dropdown">

                    <button
                        class="user-menu"
                        type="button"
                        data-bs-toggle="dropdown">

                        <div class="user-mini-avatar">

                            <?php echo $userInitial; ?>

                        </div>


                        <span class="d-none d-lg-inline">

                            <?php echo htmlspecialchars($user_name); ?>

                        </span>


                        <i class="bi bi-chevron-down"></i>

                    </button>


                    <ul
                        class="dropdown-menu dropdown-menu-end shadow border-0">

                        <li>

                            <h6 class="dropdown-header">

                                <?php echo htmlspecialchars($user_name); ?>

                            </h6>

                        </li>


                        <li>
                            <hr class="dropdown-divider">
                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="profile.php">

                                <i class="bi bi-person me-2"></i>

                                My Profile

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="settings.php">

                                <i class="bi bi-gear me-2"></i>

                                Account Settings

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item"
                                href="favorites.php">

                                <i class="bi bi-star me-2"></i>

                                Favorites

                            </a>

                        </li>


                        <li>

                            <a
                                class="dropdown-item text-danger"
                                href="logout.php">

                                <i class="bi bi-box-arrow-right me-2"></i>

                                Logout

                            </a>

                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>

</nav>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="dashboard-main">

    <div class="container-fluid px-lg-5">


        <!-- =================================================
             WELCOME HERO
        ================================================= -->

        <section class="dashboard-welcome mb-4">

            <div class="welcome-glow"></div>


            <div class="row align-items-center">


                <div class="col-lg-8">


                    <span class="welcome-badge">

                        <i class="bi bi-shield-check"></i>

                        Secure Account

                    </span>


                    <h1>

                        Welcome back,

                        <span>
                            <?php echo htmlspecialchars($user_name); ?>
                        </span>

                        👋

                    </h1>


                    <p>

                        Manage, protect and securely share your files
                        from one powerful workspace.

                    </p>


                    <div class="welcome-meta">


                        <span>

                            <i class="bi bi-calendar3"></i>

                            <?php echo date("d M Y"); ?>

                        </span>


                        <span>

                            <i class="bi bi-shield-lock-fill"></i>

                            Protected Storage

                        </span>


                        <span>

                            <i class="bi bi-circle-fill online-dot"></i>

                            Active & Secure

                        </span>


                        <span>

                            <i class="bi bi-clock"></i>

                            <span id="liveClock">
                                Loading...
                            </span>

                        </span>


                    </div>

                </div>



                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">


                    <div class="welcome-avatar">

                        <?php echo $userInitial; ?>

                    </div>


                    <a
                        href="upload.php"
                        class="btn nv-upload-btn mt-3">

                        <i class="bi bi-cloud-arrow-up-fill"></i>

                        Upload File

                        <i class="bi bi-arrow-right"></i>

                    </a>

                </div>


            </div>

        </section>



        <!-- =================================================
             STATISTICS
        ================================================= -->

        <section class="row g-4 mb-4">


            <!-- TOTAL FILES -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-folder-fill"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Total Files
                        </span>


                        <h2>

                            <?php echo $totalFiles; ?>

                        </h2>


                        <small>

                            <i class="bi bi-files"></i>

                            Your stored files

                        </small>

                    </div>

                </div>

            </div>



            <!-- DOWNLOADS -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon green">

                        <i class="bi bi-download"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Total Downloads
                        </span>


                        <h2>

                            <?php echo $totalDownloads; ?>

                        </h2>


                        <small>

                            <i class="bi bi-graph-up"></i>

                            File downloads

                        </small>

                    </div>

                </div>

            </div>



            <!-- FAVORITES -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon yellow">

                        <i class="bi bi-star-fill"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Favorites
                        </span>


                        <h2>

                            <?php echo $totalFavorite; ?>

                        </h2>


                        <small>

                            <i class="bi bi-bookmark-heart"></i>

                            Saved favorites

                        </small>

                    </div>

                </div>

            </div>



            <!-- STORAGE -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-hdd-fill"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Storage Used
                        </span>


                        <h2>

                            <?php echo $storage; ?>

                            <small>
                                MB
                            </small>

                        </h2>


                        <div class="storage-progress">

                            <div class="progress">

                                <div
                                    class="progress-bar"
                                    style="width:<?php echo $storagePercent; ?>%">

                                </div>

                            </div>


                            <small>

                                <?php echo round($storagePercent); ?>%
                                of
                                <?php echo $maxStorage; ?> MB

                            </small>

                        </div>

                    </div>

                </div>

            </div>



            <!-- TOTAL FILE DROPS -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon cyan">

                        <i class="bi bi-cloud-arrow-down-fill"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Total File Drops
                        </span>


                        <h2>

                            <?php echo $totalDrops; ?>

                        </h2>


                        <small>

                            <i class="bi bi-box-arrow-in-down"></i>

                            Created drops

                        </small>

                    </div>

                </div>

            </div>



            <!-- ACTIVE DROPS -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon green">

                        <i class="bi bi-broadcast-pin"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Active Drops
                        </span>


                        <h2>

                            <?php echo $activeDrops; ?>

                        </h2>


                        <small>

                            <i class="bi bi-check-circle"></i>

                            Currently accepting files

                        </small>

                    </div>

                </div>

            </div>



            <!-- FILES RECEIVED -->

            <div class="col-xl-3 col-md-6">

                <div class="dashboard-stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-file-earmark-arrow-down-fill"></i>

                    </div>


                    <div class="stat-content">

                        <span>
                            Files Received
                        </span>


                        <h2>

                            <?php echo $totalDroppedFiles; ?>

                        </h2>


                        <small>

                            <i class="bi bi-cloud-check"></i>

                            Through File Drops

                        </small>

                    </div>

                </div>

            </div>


        </section>



        <!-- =================================================
             QUICK ACTIONS
        ================================================= -->

        <section class="dashboard-section mb-4">


            <div class="section-title-row">

                <div>

                    <span class="section-label">
                        QUICK ACCESS
                    </span>

                    <h3>
                        Everything You Need
                    </h3>

                </div>

            </div>


            <div class="row g-3">


                <!-- UPLOAD -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="upload.php"
                        class="quick-action-card">

                        <div class="quick-icon blue">

                            <i class="bi bi-cloud-arrow-up-fill"></i>

                        </div>


                        <strong>
                            Upload File
                        </strong>


                        <small>
                            Add new file
                        </small>

                    </a>

                </div>



                <!-- FAVORITES -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="favorites.php"
                        class="quick-action-card">

                        <div class="quick-icon yellow">

                            <i class="bi bi-star-fill"></i>

                        </div>


                        <strong>
                            Favorite
                        </strong>


                        <small>
                            Saved files
                        </small>

                    </a>

                </div>



                <!-- SHARED -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="shared_files.php"
                        class="quick-action-card">

                        <div class="quick-icon purple">

                            <i class="bi bi-link-45deg"></i>

                        </div>


                        <strong>
                            Shared Files
                        </strong>


                        <small>
                            Shared content
                        </small>

                    </a>

                </div>



                <!-- ACTIVITY -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="recent_activity.php"
                        class="quick-action-card">

                        <div class="quick-icon green">

                            <i class="bi bi-clock-history"></i>

                        </div>


                        <strong>
                            Activity
                        </strong>


                        <small>
                            Recent actions
                        </small>

                    </a>

                </div>



                <!-- RECYCLE BIN -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="recycle_bin.php"
                        class="quick-action-card">

                        <div class="quick-icon red">

                            <i class="bi bi-trash3-fill"></i>

                        </div>


                        <strong>
                            Recycle Bin
                        </strong>


                        <small>
                            Deleted files
                        </small>

                    </a>

                </div>



                <!-- PROFILE -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="profile.php"
                        class="quick-action-card">

                        <div class="quick-icon cyan">

                            <i class="bi bi-person-fill"></i>

                        </div>


                        <strong>
                            My Profile
                        </strong>


                        <small>
                            Account details
                        </small>

                    </a>

                </div>



                <!-- SECURE ROOMS -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="secure_rooms.php"
                        class="quick-action-card">

                        <div class="quick-icon purple">

                            <i class="bi bi-house-lock-fill"></i>

                        </div>


                        <strong>
                            Secure Rooms
                        </strong>


                        <small>
                            Team workspaces
                        </small>

                    </a>

                </div>



                <!-- ACTIVE SESSIONS -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="security_sessions.php"
                        class="quick-action-card">

                        <div class="quick-icon cyan">

                            <i class="bi bi-shield-lock-fill"></i>

                        </div>


                        <strong>
                            Active Sessions
                        </strong>


                        <small>
                            Manage devices
                        </small>

                    </a>

                </div>



                <!-- SECURITY CENTER -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="security_center.php"
                        class="quick-action-card">

                        <div class="quick-icon blue">

                            <i class="bi bi-shield-lock-fill"></i>

                        </div>


                        <strong>
                            Security Center
                        </strong>


                        <small>
                            Account protection
                        </small>

                    </a>

                </div>



                <!-- FILE DROPS -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="file_drops.php"
                        class="quick-action-card">

                        <div class="quick-icon blue">

                            <i class="bi bi-cloud-arrow-down-fill"></i>

                        </div>


                        <strong>
                            File Drops
                        </strong>


                        <small>
                            Receive files
                        </small>

                    </a>

                </div>



                <!-- SECURITY ALERTS -->

                <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                    <a
                        href="security_alerts.php"
                        class="quick-action-card position-relative">

                        <div class="quick-icon red">

                            <i class="bi bi-bell-fill"></i>

                        </div>


                        <strong>
                            Security Alerts
                        </strong>


                        <small>
                            Security events
                        </small>


                        <?php if ($unreadAlerts > 0) { ?>

                            <span
                                style="
                                    position:absolute;
                                    top:10px;
                                    right:10px;
                                    min-width:22px;
                                    height:22px;
                                    padding:2px 6px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    border-radius:50px;
                                    background:#ef4444;
                                    color:#ffffff;
                                    font-size:10px;
                                    font-weight:800;
                                    border:2px solid #07142f;
                                ">

                                <?php echo $unreadAlerts; ?>

                            </span>

                        <?php } ?>

                    </a>

                </div>


            </div>

        </section>



        <!-- =================================================
             MAIN GRID
        ================================================= -->

        <div class="row g-4 mb-4">


            <!-- =================================================
                 MY FILES
            ================================================= -->

            <div class="col-xl-8">


                <div class="nv-panel h-100">


                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                FILE VAULT
                            </span>

                            <h3>
                                My Files
                            </h3>

                        </div>


                        <a
                            href="upload.php"
                            class="btn panel-upload-btn">

                            <i class="bi bi-plus-lg"></i>

                            Upload

                        </a>

                    </div>



                    <!-- SEARCH -->

                    <div class="file-toolbar">


                        <div class="search-box">

                            <i class="bi bi-search"></i>

                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Search your files...">

                        </div>


                        <div class="file-filters">


                            <a
                                href="dashboard.php"
                                class="filter-btn active">

                                All

                            </a>


                            <a
                                href="dashboard.php?type=pdf"
                                class="filter-btn pdf">

                                PDF

                            </a>


                            <a
                                href="dashboard.php?type=image"
                                class="filter-btn image">

                                Images

                            </a>


                            <a
                                href="dashboard.php?type=word"
                                class="filter-btn word">

                                Word

                            </a>


                            <a
                                href="dashboard.php?type=zip"
                                class="filter-btn zip">

                                ZIP

                            </a>


                            <?php foreach ($tagList as $tag) { ?>

                                <button
                                    type="button"
                                    class="filter-btn tag-filter"
                                    data-tag="<?php echo htmlspecialchars($tag, ENT_QUOTES); ?>">

                                    <i class="bi bi-tag-fill"></i>

                                    <?php echo htmlspecialchars($tag); ?>

                                </button>

                            <?php } ?>


                        </div>



                        <form
                            method="GET"
                            class="sort-form">


                            <select
                                name="sort"
                                class="form-select"
                                onchange="this.form.submit()">


                                <option value="">
                                    Sort By
                                </option>


                                <option
                                    value="name"
                                    <?php echo (
                                        ($_GET['sort'] ?? '') === 'name'
                                    ) ? 'selected' : ''; ?>>

                                    Name

                                </option>


                                <option
                                    value="date"
                                    <?php echo (
                                        ($_GET['sort'] ?? '') === 'date'
                                    ) ? 'selected' : ''; ?>>

                                    Upload Date

                                </option>


                                <option
                                    value="size"
                                    <?php echo (
                                        ($_GET['sort'] ?? '') === 'size'
                                    ) ? 'selected' : ''; ?>>

                                    File Size

                                </option>


                                <option
                                    value="download"
                                    <?php echo (
                                        ($_GET['sort'] ?? '') === 'download'
                                    ) ? 'selected' : ''; ?>>

                                    Downloads

                                </option>


                            </select>

                        </form>


                    </div>



                    <!-- FILE TABLE -->

                    <div class="table-responsive">

                        <table
                            class="table nv-file-table"
                            id="fileTable">


                            <thead>

                                <tr>

                                    <th>
                                        File
                                    </th>

                                    <th>
                                        Size
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Downloads
                                    </th>

                                    <th>
                                        Actions
                                    </th>

                                </tr>

                            </thead>



                            <tbody>


                                <?php

                                /* =========================
                                   FILE QUERY
                                ========================= */

                                if (isset($_GET['favorite'])) {

                                    $sql =
                                        "SELECT *
                                         FROM files
                                         WHERE user_id='$user_id'
                                         AND favorite=1
                                         AND deleted=0";

                                } elseif (isset($_GET['type'])) {

                                    $filter = $_GET['type'];


                                    if ($filter === "pdf") {

                                        $sql =
                                            "SELECT *
                                             FROM files
                                             WHERE user_id='$user_id'
                                             AND deleted=0
                                             AND file_type LIKE '%pdf%'";

                                    } elseif ($filter === "image") {

                                        $sql =
                                            "SELECT *
                                             FROM files
                                             WHERE user_id='$user_id'
                                             AND deleted=0
                                             AND file_type LIKE 'image%'";

                                    } elseif ($filter === "word") {

                                        $sql =
                                            "SELECT *
                                             FROM files
                                             WHERE user_id='$user_id'
                                             AND deleted=0
                                             AND (
                                                file_type LIKE '%word%'
                                                OR file_type LIKE '%document%'
                                             )";

                                    } elseif ($filter === "zip") {

                                        $sql =
                                            "SELECT *
                                             FROM files
                                             WHERE user_id='$user_id'
                                             AND deleted=0
                                             AND file_type LIKE '%zip%'";

                                    } else {

                                        $sql =
                                            "SELECT *
                                             FROM files
                                             WHERE user_id='$user_id'
                                             AND deleted=0";
                                    }

                                } else {

                                    $sql =
                                        "SELECT *
                                         FROM files
                                         WHERE user_id='$user_id'
                                         AND deleted=0";
                                }


                                /* SORT */

                                $sort =
                                    $_GET['sort'] ?? '';


                                if ($sort === "name") {

                                    $sql .=
                                        " ORDER BY original_name ASC";

                                } elseif ($sort === "date") {

                                    $sql .=
                                        " ORDER BY uploaded_at DESC";

                                } elseif ($sort === "size") {

                                    $sql .=
                                        " ORDER BY file_size DESC";

                                } elseif ($sort === "download") {

                                    $sql .=
                                        " ORDER BY downloaded DESC";
                                }


                                $result =
                                    mysqli_query(
                                        $conn,
                                        $sql
                                    );


                                if (
                                    $result &&
                                    mysqli_num_rows($result) > 0
                                ) {


                                    while (
                                        $row =
                                        mysqli_fetch_assoc($result)
                                    ) {


                                        $fileName =
                                            htmlspecialchars(
                                                $row['original_name']
                                                ?? 'Unknown File'
                                            );


                                        $fileType =
                                            strtolower(
                                                $row['file_type']
                                                ?? ''
                                            );


                                        $fileSize =
                                            round(
                                                (
                                                    $row['file_size']
                                                    ?? 0
                                                ) / 1024,
                                                2
                                            );


                                        $downloads =
                                            (int)(
                                                $row['downloaded']
                                                ?? 0
                                            );


                                        /* FILE ICON */

                                        $fileIcon =
                                            "bi-file-earmark-fill";

                                        $fileClass =
                                            "default";


                                        if (
                                            strpos(
                                                $fileType,
                                                "pdf"
                                            ) !== false
                                        ) {

                                            $fileIcon =
                                                "bi-file-earmark-pdf-fill";

                                            $fileClass =
                                                "pdf";

                                        } elseif (
                                            strpos(
                                                $fileType,
                                                "image"
                                            ) !== false
                                        ) {

                                            $fileIcon =
                                                "bi-file-earmark-image-fill";

                                            $fileClass =
                                                "image";

                                        } elseif (
                                            strpos(
                                                $fileType,
                                                "word"
                                            ) !== false ||
                                            strpos(
                                                $fileType,
                                                "document"
                                            ) !== false
                                        ) {

                                            $fileIcon =
                                                "bi-file-earmark-word-fill";

                                            $fileClass =
                                                "word";

                                        } elseif (
                                            strpos(
                                                $fileType,
                                                "zip"
                                            ) !== false
                                        ) {

                                            $fileIcon =
                                                "bi-file-earmark-zip-fill";

                                            $fileClass =
                                                "zip";
                                        }

                                        ?>


                                        <tr>


                                            <!-- FILE -->

                                            <td>

                                                <div class="file-info">


                                                    <div
                                                        class="file-type-icon <?php echo $fileClass; ?>">

                                                        <i
                                                            class="bi <?php echo $fileIcon; ?>">
                                                        </i>

                                                    </div>


                                                    <div class="file-name">


                                                        <strong>

                                                            <?php echo $fileName; ?>

                                                        </strong>


                                                        <small>
                                                            NexaVault Storage
                                                        </small>


                                                        <?php if (!empty($row['tags'])) { ?>

                                                            <span class="file-tags">

                                                                <i class="bi bi-tags-fill"></i>

                                                                <?php echo htmlspecialchars(
                                                                    $row['tags']
                                                                ); ?>

                                                            </span>

                                                        <?php } ?>


                                                    </div>

                                                </div>

                                            </td>



                                            <!-- SIZE -->

                                            <td>

                                                <span class="file-size">

                                                    <?php echo $fileSize; ?> KB

                                                </span>

                                            </td>



                                            <!-- TYPE -->

                                            <td>

                                                <span
                                                    class="file-type-badge <?php echo $fileClass; ?>">

                                                    <?php

                                                    if ($fileClass === "pdf") {

                                                        echo "PDF";

                                                    } elseif ($fileClass === "image") {

                                                        echo "Image";

                                                    } elseif ($fileClass === "word") {

                                                        echo "Word";

                                                    } elseif ($fileClass === "zip") {

                                                        echo "ZIP";

                                                    } else {

                                                        echo "File";
                                                    }

                                                    ?>

                                                </span>

                                            </td>



                                            <!-- DOWNLOADS -->

                                            <td>

                                                <div class="download-count">

                                                    <i class="bi bi-download"></i>

                                                    <?php echo $downloads; ?>

                                                </div>

                                            </td>



                                            <!-- ACTIONS -->

                                            <td>

                                                <div class="file-actions">


                                                    <a
                                                        href="file_versions.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn version"
                                                        title="Version History">

                                                        <i class="bi bi-clock-history"></i>

                                                    </a>


                                                    <a
                                                        href="preview.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn preview"
                                                        title="Preview">

                                                        <i class="bi bi-eye"></i>

                                                    </a>


                                                    <a
                                                        href="verify_integrity.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="btn btn-sm btn-outline-info"
                                                        title="Verify File Integrity">

                                                        <i class="bi bi-shield-check"></i>

                                                    </a>


                                                    <a
                                                        href="download.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn download"
                                                        title="Download">

                                                        <i class="bi bi-download"></i>

                                                    </a>


                                                    <a
                                                        href="favorite.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn favorite <?php echo (
                                                            ($row['favorite'] ?? 0) == 1
                                                        ) ? 'selected' : ''; ?>"
                                                        title="Favorite">

                                                        <i
                                                            class="bi <?php echo (
                                                                ($row['favorite'] ?? 0) == 1
                                                            )
                                                                ? 'bi-star-fill'
                                                                : 'bi-star'; ?>">
                                                        </i>

                                                    </a>


                                                    <a
                                                        href="share.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn share"
                                                        title="Share File">

                                                        <i class="bi bi-link-45deg"></i>

                                                    </a>


                                                    <a
                                                        href="rename.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn rename"
                                                        title="Rename">

                                                        <i class="bi bi-pencil"></i>

                                                    </a>


                                                    <a
                                                        href="file_details.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn info"
                                                        title="File Information">

                                                        <i class="bi bi-info-circle"></i>

                                                    </a>


                                                    <a
                                                        href="delete.php?id=<?php echo (int)$row['id']; ?>"
                                                        class="action-btn delete"
                                                        title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this file?');">

                                                        <i class="bi bi-trash3"></i>

                                                    </a>


                                                </div>

                                            </td>


                                        </tr>


                                    <?php

                                    }

                                } else {

                                ?>


                                    <tr>

                                        <td colspan="5">


                                            <div class="empty-files">


                                                <div class="empty-icon">

                                                    <i class="bi bi-folder2-open"></i>

                                                </div>


                                                <h4>
                                                    Your vault is empty
                                                </h4>


                                                <p>

                                                    Upload your first file
                                                    and start using NexaVault securely.

                                                </p>


                                                <a
                                                    href="upload.php"
                                                    class="btn nv-upload-btn">

                                                    <i class="bi bi-cloud-arrow-up-fill"></i>

                                                    Upload Your First File

                                                </a>


                                            </div>


                                        </td>

                                    </tr>


                                <?php

                                }

                                ?>


                            </tbody>

                        </table>

                    </div>

                </div>

            </div>



            <!-- =================================================
                 RIGHT SIDEBAR
            ================================================= -->

            <div class="col-xl-4">


                <!-- ANALYTICS -->

                <div class="nv-panel analytics-panel mb-4">


                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                ANALYTICS
                            </span>

                            <h3>
                                File Overview
                            </h3>

                        </div>


                        <i
                            class="bi bi-pie-chart-fill panel-title-icon">
                        </i>

                    </div>


                    <div class="chart-container">

                        <canvas id="fileChart"></canvas>

                    </div>


                    <div class="analytics-legend">


                        <div>

                            <span class="legend-dot pdf"></span>

                            PDF

                            <strong>
                                <?php echo $pdfCount; ?>
                            </strong>

                        </div>


                        <div>

                            <span class="legend-dot image"></span>

                            Images

                            <strong>
                                <?php echo $imageCount; ?>
                            </strong>

                        </div>


                        <div>

                            <span class="legend-dot word"></span>

                            Word

                            <strong>
                                <?php echo $wordCount; ?>
                            </strong>

                        </div>


                        <div>

                            <span class="legend-dot zip"></span>

                            ZIP

                            <strong>
                                <?php echo $zipCount; ?>
                            </strong>

                        </div>


                    </div>

                </div>



                <!-- SECURITY STATUS -->

                <div class="nv-panel security-panel mb-4">


                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                SECURITY
                            </span>

                            <h3>
                                Vault Protection
                            </h3>

                        </div>


                        <i
                            class="bi bi-shield-lock-fill panel-title-icon">
                        </i>

                    </div>


                    <div class="security-status-row">

                        <div class="security-status-icon">

                            <i class="bi bi-check-lg"></i>

                        </div>


                        <div>

                            <strong>
                                Account Protected
                            </strong>

                            <small>
                                Secure authentication enabled
                            </small>

                        </div>

                    </div>


                    <div class="security-status-row">

                        <div class="security-status-icon">

                            <i class="bi bi-lock-fill"></i>

                        </div>


                        <div>

                            <strong>
                                Password Protection
                            </strong>

                            <small>
                                Protected file access
                            </small>

                        </div>

                    </div>


                    <div class="security-status-row">

                        <div class="security-status-icon">

                            <i class="bi bi-link-45deg"></i>

                        </div>


                        <div>

                            <strong>
                                Controlled Sharing
                            </strong>

                            <small>
                                Manage your shared links
                            </small>

                        </div>

                    </div>


                    <div class="security-status-row">

                        <div class="security-status-icon">

                            <i class="bi bi-shield-check"></i>

                        </div>


                        <div>

                            <strong>
                                NexaVault Secure
                            </strong>

                            <small>
                                Your files remain under your control
                            </small>

                        </div>

                    </div>


                </div>



                <!-- RECENT ACTIVITY -->

                <div class="nv-panel activity-panel">


                    <div class="panel-header">

                        <div>

                            <span class="section-label">
                                ACTIVITY
                            </span>

                            <h3>
                                Recent Activity
                            </h3>

                        </div>


                        <a
                            href="recent_activity.php"
                            class="activity-view">

                            View All

                        </a>

                    </div>


                    <div class="activity-list">


                        <?php

                        if (
                            $activityResult &&
                            mysqli_num_rows($activityResult) > 0
                        ) {


                            while (
                                $activity =
                                mysqli_fetch_assoc($activityResult)
                            ) {

                        ?>


                                <div class="activity-item">


                                    <div class="activity-icon">

                                        <i class="bi bi-activity"></i>

                                    </div>


                                    <div class="activity-details">

                                        <strong>

                                            <?php echo htmlspecialchars(
                                                $activity['activity'] ?? 'Activity'
                                            ); ?>

                                        </strong>


                                        <small>

                                            <?php

                                            $activityTime =
                                                strtotime(
                                                    $activity['created_at'] ?? ''
                                                );

                                            echo $activityTime
                                                ? date(
                                                    "d M Y • H:i",
                                                    $activityTime
                                                )
                                                : 'Unknown time';

                                            ?>

                                        </small>

                                    </div>


                                </div>


                        <?php

                            }

                        } else {

                        ?>


                            <div class="no-activity">

                                <i class="bi bi-clock-history"></i>

                                <p>
                                    No recent activity yet.
                                </p>

                            </div>


                        <?php

                        }

                        ?>


                    </div>

                </div>


            </div>


        </div>



        <!-- =================================================
             FOOTER
        ================================================= -->

        <footer class="dashboard-footer">


            <div>

                <strong>
                    Nexa<span>Vault</span>
                </strong>

                <span>
                    Secure • Share • Trust
                </span>

            </div>


            <p>

                © 2026 NexaVault.
                All rights reserved.

            </p>

        </footer>


    </div>

</main>



<!-- =====================================================
     SCRIPTS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


<script
    src="https://cdn.jsdelivr.net/npm/chart.js">
</script>



<script>

/* =====================================================
   FILE SEARCH
===================================================== */

const searchInput =
    document.getElementById("searchInput");

if (searchInput) {

    searchInput.addEventListener(
        "keyup",
        function () {

            const filter =
                this.value.toLowerCase();

            const rows =
                document.querySelectorAll(
                    "#fileTable tbody tr"
                );

            rows.forEach(
                function (row) {

                    const text =
                        row.innerText.toLowerCase();

                    row.style.display =
                        text.includes(filter)
                            ? ""
                            : "none";
                }
            );
        }
    );
}


/* =====================================================
   TAG FILTER
===================================================== */

document
    .querySelectorAll(".tag-filter")
    .forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    const selectedTag =
                        this.getAttribute(
                            "data-tag"
                        ).toLowerCase();

                    const rows =
                        document.querySelectorAll(
                            "#fileTable tbody tr"
                        );

                    rows.forEach(
                        function (row) {

                            const text =
                                row.innerText.toLowerCase();

                            row.style.display =
                                text.includes(selectedTag)
                                    ? ""
                                    : "none";
                        }
                    );
                }
            );
        }
    );


/* =====================================================
   FILE CHART
===================================================== */

const chartElement =
    document.getElementById("fileChart");

if (chartElement) {

    new Chart(
        chartElement,
        {

            type: "doughnut",

            data: {

                labels: [
                    "PDF",
                    "Images",
                    "Word",
                    "ZIP"
                ],

                datasets: [{

                    data: [

                        <?php echo $pdfCount; ?>,
                        <?php echo $imageCount; ?>,
                        <?php echo $wordCount; ?>,
                        <?php echo $zipCount; ?>

                    ],

                    backgroundColor: [

                        "#2563eb",
                        "#06b6d4",
                        "#8b5cf6",
                        "#f59e0b"

                    ],

                    borderWidth: 0,

                    hoverOffset: 8

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                cutout: "68%",

                plugins: {

                    legend: {
                        display: false
                    }

                }

            }

        }
    );
}


/* =====================================================
   LIVE CLOCK
===================================================== */

function updateLiveClock() {

    const clock =
        document.getElementById("liveClock");

    if (!clock) {
        return;
    }

    const now =
        new Date();

    const time =
        now.toLocaleTimeString(
            "en-IN",
            {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit"
            }
        );

    clock.textContent =
        time;
}

updateLiveClock();

setInterval(
    updateLiveClock,
    1000
);

</script>


</body>

</html>