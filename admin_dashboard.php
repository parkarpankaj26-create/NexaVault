<?php
session_start();
include 'includes/db.php';

/* =========================================================
   ADMIN SECURITY
   ========================================================= */

if (!isset($_SESSION['user']) || $_SESSION['role'] != "admin") {
    header("Location: login.php");
    exit();
}

/* =========================================================
   BASIC STATISTICS
   ========================================================= */

/* Total Users */
$userCountResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$userCountRow = mysqli_fetch_assoc($userCountResult);
$userCount = (int)$userCountRow['total'];


/* Total Active Files */
$fileCountResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE deleted=0"
);

$fileCountRow = mysqli_fetch_assoc($fileCountResult);
$fileCount = (int)$fileCountRow['total'];


/* Total Downloads */
$downloadResult = mysqli_query(
    $conn,
    "SELECT SUM(downloaded) AS total
     FROM files"
);

$downloadRow = mysqli_fetch_assoc($downloadResult);
$downloadCount = (int)($downloadRow['total'] ?? 0);


/* =========================================================
   ANALYTICS DATA
   ========================================================= */

/* Active Users */
$activeUsersResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role != 'admin'
     AND status = 'active'"
);

$activeUsersRow = mysqli_fetch_assoc($activeUsersResult);
$activeUsers = (int)$activeUsersRow['total'];


/* Blocked Users */
$blockedUsersResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users
     WHERE role != 'admin'
     AND status != 'active'"
);

$blockedUsersRow = mysqli_fetch_assoc($blockedUsersResult);
$blockedUsers = (int)$blockedUsersRow['total'];


/* Shared Files */
$sharedFilesResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE is_shared = '1'
     AND deleted = 0"
);

$sharedFilesRow = mysqli_fetch_assoc($sharedFilesResult);
$sharedFiles = (int)$sharedFilesRow['total'];


/* Deleted Files */
$deletedFilesResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE deleted = 1"
);

$deletedFilesRow = mysqli_fetch_assoc($deletedFilesResult);
$deletedFiles = (int)$deletedFilesRow['total'];


/* =========================================================
   TOTAL STORAGE
   ========================================================= */

$storageResult = mysqli_query(
    $conn,
    "SELECT SUM(file_size) AS total_size
     FROM files
     WHERE deleted = 0"
);

$storageRow = mysqli_fetch_assoc($storageResult);

$totalStorage = (float)($storageRow['total_size'] ?? 0);

$totalStorageMB = round(
    $totalStorage / (1024 * 1024),
    2
);


/* =========================================================
   FILE TYPE DISTRIBUTION
   ========================================================= */

$typeResult = mysqli_query(
    $conn,
    "SELECT file_type, COUNT(*) AS total
     FROM files
     WHERE deleted = 0
     GROUP BY file_type
     ORDER BY total DESC"
);

$fileTypes = [];

if ($typeResult) {

    while ($type = mysqli_fetch_assoc($typeResult)) {
        $fileTypes[] = $type;
    }

}
?>
<!DOCTYPE html>
<html>
<head>

<title>Admin Dashboard</title>
<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin_style.css">
</head>

<body class="bg-light">

<nav class="admin-navbar">

    <div class="admin-logo">
        🔐 NexaVault
    </div>

    <div class="admin-nav-links">

        <a href="admin_dashboard.php">
            <i class="bi bi-speedometer2"></i>
            Dashboard
        </a>

        <a href="#users">
    <i class="bi bi-people-fill"></i>
    Users
</a>

        <a href="#files">
    <i class="bi bi-folder-fill"></i>
    Files
</a>

        <a href="#analytics">
            <i class="bi bi-graph-up"></i>
            Analytics
        </a>

       <a href="#settings">
    <i class="bi bi-gear-fill"></i>
    Settings
</a>

        <a href="logout.php" class="admin-logout">
            <i class="bi bi-box-arrow-right"></i>
            Logout
        </a>

    </div>

</nav>

<div class="content p-4">

<h2 class="mb-4">
👑 Admin Dashboard
</h2>
<form method="GET" class="mb-3">

<div class="input-group">

<input
type="text"
name="search"
class="form-control"
placeholder="Search User by Name or Email..."
value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">

<button class="btn btn-primary">
Search
</button>

</div>

</form>
<div class="row">

<div class="col-md-4">
<div class="card shadow p-4 text-center">
<h1><?php echo $userCount; ?></h1>
<p>Total Users</p>
</div>
</div>

<div class="col-md-4">
<div class="card shadow p-4 text-center">
<h1><?php echo $fileCount; ?></h1>
<p>Total Files</p>
</div>
</div>

<div class="col-md-4">
<div class="card shadow p-4 text-center">
<h1><?php echo $downloadCount; ?></h1>
<p>Total Downloads</p>
</div>
</div>

</div>

<div class="mt-5">

<a href="logout.php" class="btn btn-danger">
Logout
</a>
<hr class="my-5">
<section id="users">
<h3 class="mb-4">
👥 Manage Users
</h3>

<table class="table table-bordered table-hover shadow">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Name</th>

<th>Email</th>

<th>Role</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>
</section>
<tbody>

<?php
if(isset($_GET['search']) && $_GET['search']!="")
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);

    $users = mysqli_query($conn,
    "SELECT * FROM users
    WHERE name LIKE '%$search%'
    OR email LIKE '%$search%'");
}
else
{
    $users = mysqli_query($conn,
    "SELECT * FROM users");
}


while($u = mysqli_fetch_assoc($users))
{

?>

<tr>

<td><?php echo $u['id']; ?></td>

<td><?php echo $u['name']; ?></td>

<td><?php echo $u['email']; ?></td>

<td>

<?php

if($u['role']=="admin")
{
    echo "<span class='badge bg-danger'>Admin</span>";
}
else
{
    echo "<span class='badge bg-primary'>User</span>";
}

?>

</td>
<td>

<?php

if($u['status']=="active")
{
    echo "<span class='badge bg-success'>Active</span>";
}
else
{
    echo "<span class='badge bg-danger'>Blocked</span>";
}

?>

</td>
<td>

<?php

if($u['role']!="admin")
{

?>
<?php
if($u['status']=="active")
{
?>

<a href="block_user.php?id=<?php echo $u['id']; ?>"
class="btn btn-warning btn-sm">

Block

</a>

<?php
}
else
{
?>

<a href="unblock_user.php?id=<?php echo $u['id']; ?>"
class="btn btn-success btn-sm">

Unblock

</a>

<?php
}
?>
<a href="view_user.php?id=<?php echo $u['id']; ?>"
class="btn btn-info btn-sm">

View

</a>
<a
href="delete_user.php?id=<?php echo $u['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this user?')">

Delete

</a>

<?php

}

?>

</td>

</tr>

<?php

}

?>

</tbody>

</table>
<hr class="admin-divider my-5">

<section id="analytics" class="admin-analytics">

    <div class="analytics-heading">

        <div>
            <span class="section-label">
                SYSTEM INSIGHTS
            </span>

            <h3>
                <i class="bi bi-graph-up-arrow"></i>
                Analytics Overview
            </h3>

            <p>
                Monitor NexaVault users, files, storage and sharing activity.
            </p>
        </div>

    </div>


    <!-- ANALYTICS CARDS -->

    <div class="row g-4 mt-2">

        <div class="col-lg-3 col-md-6">

            <div class="analytics-card">

                <div class="analytics-icon blue">
                    <i class="bi bi-people-fill"></i>
                </div>

                <div>
                    <small>Active Users</small>

                    <h2>
                        <?php echo $activeUsers; ?>
                    </h2>

                    <span class="analytics-status">
                        <i class="bi bi-check-circle-fill"></i>
                        Currently Active
                    </span>
                </div>

            </div>

        </div>


        <div class="col-lg-3 col-md-6">

            <div class="analytics-card">

                <div class="analytics-icon red">
                    <i class="bi bi-person-lock"></i>
                </div>

                <div>
                    <small>Blocked Users</small>

                    <h2>
                        <?php echo $blockedUsers; ?>
                    </h2>

                    <span class="analytics-status danger">
                        <i class="bi bi-shield-x"></i>
                        Restricted
                    </span>
                </div>

            </div>

        </div>


        <div class="col-lg-3 col-md-6">

            <div class="analytics-card">

                <div class="analytics-icon purple">
                    <i class="bi bi-share-fill"></i>
                </div>

                <div>
                    <small>Shared Files</small>

                    <h2>
                        <?php echo $sharedFiles; ?>
                    </h2>

                    <span class="analytics-status">
                        <i class="bi bi-link-45deg"></i>
                        Secure Links
                    </span>
                </div>

            </div>

        </div>


        <div class="col-lg-3 col-md-6">

            <div class="analytics-card">

                <div class="analytics-icon green">
                    <i class="bi bi-hdd-fill"></i>
                </div>

                <div>
                    <small>Storage Used</small>

                    <h2>
                        <?php echo $totalStorageMB; ?>
                        <span>MB</span>
                    </h2>

                    <span class="analytics-status">
                        <i class="bi bi-cloud-check-fill"></i>
                        NexaVault Storage
                    </span>
                </div>

            </div>

        </div>

    </div>


    <!-- SECOND ROW -->

    <div class="row g-4 mt-2">

        <!-- FILE STATISTICS -->

        <div class="col-lg-8">

            <div class="analytics-panel">

                <div class="panel-header">

                    <div>
                        <span class="section-label">
                            FILE STATISTICS
                        </span>

                        <h4>
                            File Type Distribution
                        </h4>
                    </div>

                    <i class="bi bi-pie-chart-fill panel-icon"></i>

                </div>


                <div class="file-type-list">

                    <?php

                    if(count($fileTypes) > 0)
                    {

                        foreach($fileTypes as $type)
                        {

                            $typeName = $type['file_type'];

                            $typeCount = $type['total'];

                            $percentage =
                                $fileCount > 0
                                ? round(($typeCount / $fileCount) * 100)
                                : 0;

                    ?>

                    <div class="file-type-row">

                        <div class="file-type-info">

                            <span>
                                <?php echo htmlspecialchars($typeName); ?>
                            </span>

                            <strong>
                                <?php echo $typeCount; ?>
                            </strong>

                        </div>

                        <div class="analytics-progress">

                            <div
                                class="analytics-progress-bar"
                                style="width: <?php echo $percentage; ?>%;">
                            </div>

                        </div>

                        <small>
                            <?php echo $percentage; ?>%
                        </small>

                    </div>

                    <?php

                        }

                    }
                    else
                    {

                    ?>

                    <div class="empty-analytics">
                        <i class="bi bi-folder-x"></i>
                        <p>No file statistics available.</p>
                    </div>

                    <?php

                    }

                    ?>

                </div>

            </div>

        </div>


        <!-- SYSTEM SUMMARY -->

        <div class="col-lg-4">

            <div class="analytics-panel">

                <div class="panel-header">

                    <div>
                        <span class="section-label">
                            SYSTEM SUMMARY
                        </span>

                        <h4>
                            NexaVault Status
                        </h4>
                    </div>

                    <i class="bi bi-shield-check panel-icon"></i>

                </div>


                <div class="system-stat">

                    <span>
                        <i class="bi bi-people"></i>
                        Total Users
                    </span>

                    <strong>
                        <?php echo $userCount; ?>
                    </strong>

                </div>


                <div class="system-stat">

                    <span>
                        <i class="bi bi-folder2-open"></i>
                        Total Files
                    </span>

                    <strong>
                        <?php echo $fileCount; ?>
                    </strong>

                </div>


                <div class="system-stat">

                    <span>
                        <i class="bi bi-download"></i>
                        Total Downloads
                    </span>

                    <strong>
                        <?php echo $downloadCount; ?>
                    </strong>

                </div>


                <div class="system-stat">

                    <span>
                        <i class="bi bi-trash3"></i>
                        Deleted Files
                    </span>

                    <strong>
                        <?php echo $deletedFiles; ?>
                    </strong>

                </div>


                <div class="system-stat">

                    <span>
                        <i class="bi bi-shield-lock"></i>
                        Security
                    </span>

                    <strong class="secure-text">
                        Protected
                    </strong>

                </div>


                <div class="system-status">

                    <span class="status-dot"></span>

                    NexaVault System Operational

                </div>

            </div>

        </div>

    </div>

</section>
<hr class="my-5">
<section id="files">
<h3 class="mb-4">
📁 Manage Files
</h3>
<form method="GET" class="mb-3">

<div class="input-group">

<input
type="text"
name="file_search"
class="form-control"
placeholder="Search File..."
value="<?php echo isset($_GET['file_search']) ? $_GET['file_search'] : ''; ?>">

<button class="btn btn-success">
Search File
</button>

</div>

</form>
<table class="table table-bordered table-hover shadow">

<thead class="table-primary">

<tr>

<th>ID</th>
<th>File Name</th>
<th>Type</th>
<th>Size</th>
<th>User ID</th>
<th>Action</th>

</tr>

</thead>
</section>
<tbody>

<?php

if(isset($_GET['file_search']) && $_GET['file_search']!="")
{
    $search = mysqli_real_escape_string($conn,$_GET['file_search']);

    $files = mysqli_query($conn,
    "SELECT * FROM files
    WHERE original_name LIKE '%$search%'
    ORDER BY id DESC");
}
else
{
    $files = mysqli_query($conn,
    "SELECT * FROM files
    ORDER BY id DESC");
}
while($f = mysqli_fetch_assoc($files))
{

?>

<tr>

<td><?php echo $f['id']; ?></td>

<td><?php echo $f['original_name']; ?></td>

<td><?php echo $f['file_type']; ?></td>

<td><?php echo round($f['file_size']/1024,2); ?> KB</td>

<td><?php echo $f['user_id']; ?></td>

<td>

<a
href="delete_file_admin.php?id=<?php echo $f['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this file?')">

Delete

</a>

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
</body>
</html>