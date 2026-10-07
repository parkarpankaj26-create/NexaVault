
<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';
$user_id = $_SESSION['user_id'];
$totalFavorite = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files
WHERE user_id='$user_id' AND favorite=1"))['total'];
if($totalFavorite == NULL)
{
    $totalFavorite = 0;
}
// Total Files
$totalFiles = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files WHERE user_id='$user_id'"))['total'];

// Total Downloads
$totalDownloads = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(downloaded) AS total FROM files WHERE user_id='$user_id'"))['total'];

if ($totalDownloads == NULL) {
    $totalDownloads = 0;
}

// Storage Used
$storage = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(file_size) AS total FROM files WHERE user_id='$user_id'"))['total'];

if ($storage == NULL) {
    $storage = 0;
}

$storage = round($storage / 1024 / 1024, 2);
$maxStorage = 100; // Maximum Storage in MB

$storagePercent = ($storage / $maxStorage) * 100;

if($storagePercent > 100)
{
    $storagePercent = 100;
}



// ===== File Analytics Queries =====

$pdfCount = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files
WHERE user_id='$user_id' AND file_type LIKE '%pdf%'"))['total'];

$imageCount = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files
WHERE user_id='$user_id' AND file_type LIKE 'image%'"))['total'];

$wordCount = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files
WHERE user_id='$user_id'
AND (file_type LIKE '%word%' OR file_type LIKE '%document%')"))['total'];

$zipCount = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files
WHERE user_id='$user_id' AND file_type LIKE '%zip%'"))['total'];

$activityResult = mysqli_query($conn,
"SELECT *
FROM activity_log
WHERE user_id='$user_id'
ORDER BY created_at DESC
LIMIT 5");
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>


<body style="background:#f4f7fc;">
<?php
$notify = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM notifications
WHERE user_id='$user_id' AND is_read=0"));
?>

<a href="notifications.php" class="btn btn-light btn-sm me-2">
    🔔
    <?php
    if($notify['total']>0)
    {
        echo "<span class='badge bg-danger'>".$notify['total']."</span>";
    }
    ?>
</a>
            <span class="text-white me-3">
    <i class="bi bi-person-circle"></i>
    <?php echo $_SESSION['user']; ?>
</span>

<a href="profile.php" class="btn btn-warning btn-sm me-2">
    👤 My Profile
</a>
<a href="logout.php" class="btn btn-light btn-sm">
    <i class="bi bi-box-arrow-right"></i> Logout
</a>

<a href="dashboard.php?favorite=1" class="btn btn-warning btn-sm">
⭐ Favorite Files
</a>

<a href="dashboard.php" class="btn btn-secondary btn-sm">
All Files
</a>
        </div>

    </div>
</nav>

<div class="container mt-4">
<div class="card shadow border-0 mb-4">
<div class="row g-3 mb-4">

    <div class="col-md-3">
        <a href="upload.php" class="text-decoration-none">
            <div class="card shadow text-center p-4 h-100">
                <h1>📤</h1>
                <h5>Upload File</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="favorites.php" class="text-decoration-none">
            <div class="card shadow text-center p-4 h-100">
                <h1>⭐</h1>
                <h5>Favorites</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="recent_activity.php" class="text-decoration-none">
            <div class="card shadow text-center p-4 h-100">
                <h1>📜</h1>
                <h5>Activity</h5>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a href="profile.php" class="text-decoration-none">
            <div class="card shadow text-center p-4 h-100">
                <h1>👤</h1>
                <h5>My Profile</h5>
            </div>
        </a>
    </div>
    <div class="col-md-3">
    <a href="settings.php" class="text-decoration-none">
        <div class="card shadow text-center p-4 h-100">
            <h1>⚙</h1>
           
        <a href="settings.php" class="btn btn-dark btn-sm">
   <h5>Account Settings</h5>
</a>
        </div>
     
    </a>
</div>

<div class="col-md-3">
    <a href="recycle_bin.php" class="text-decoration-none">

        <div class="card shadow text-center p-4 h-100">

            <h1>🗑</h1>

            <h5>Recycle Bin</h5>

        </div>

    </a>
    </div>

</div>
<div class="welcome-card mb-4">

    <div class="row align-items-center">

        <div class="col-md-8">

            <span class="badge bg-light text-primary mb-3 px-3 py-2">
                <i class="bi bi-shield-check"></i>
                Secure Account
            </span>

            <h2 class="fw-bold mb-2">
                👋 Welcome,
                <?php echo htmlspecialchars($_SESSION['user']); ?>
            </h2>

            <p class="mb-3 opacity-75">
                Manage, protect and securely share your files from one place.
            </p>
<div class="d-flex flex-wrap gap-3">

                <span>
                    <i class="bi bi-calendar3"></i>
                    <?php echo date("d M Y"); ?>
                </span>

                <span>
                    <i class="bi bi-shield-lock-fill"></i>
                    Protected Storage
                </span>

            </div>

        </div>


        <div class="col-md-4 text-md-end mt-4 mt-md-0">

            <div class="user-avatar ms-md-auto">

                <?php
                echo strtoupper(
                    substr($_SESSION['user'], 0, 1)
                );
                ?>

            </div>

            <div class="mt-3">

                <small class="opacity-75">
                    Account Status
                </small>

                <div class="fw-bold">
                    <i class="bi bi-circle-fill text-success"
                       style="font-size:8px;"></i>

                    Active & Secure
                </div>

            </div>

        </div>

    </div>


    <hr class="border-light opacity-25 my-4">


    <div class="row text-center text-md-start">

        <div class="col-md-4 mb-3 mb-md-0">

            <div class="d-flex align-items-center gap-3">

                <div class="fs-3">
                    📁
                </div>

                <div>

                    <small class="opacity-75">
                        Total Files
                    </small>

                    <h4 class="fw-bold mb-0">
                        <?php echo $totalFiles; ?>
                    </h4>

                </div>

            </div>

        </div>


        <div class="col-md-4 mb-3 mb-md-0">

            

<div class="col-md-4 text-end">

<h5 class="mb-1">

</div>

</div>

</div>

</div>
<div class="card shadow-lg h-100 border-0 rounded-4 mb-4">
    <div class="card-body p-4">

<h4 class="fw-bold mb-3">
    📁 My Files
</h4>
<div class="row mb-3">

    <div class="col-md-4">

        <input type="text"
               id="searchInput"
               class="form-control"
               placeholder="🔍 Search File...">

    </div>
    <div class="mb-3">

    <a href="dashboard.php" class="btn btn-secondary btn-sm">📂 All</a>

    <a href="dashboard.php?type=pdf" class="btn btn-danger btn-sm">📄 PDF</a>

    <a href="dashboard.php?type=image" class="btn btn-primary btn-sm">🖼 Images</a>

    <a href="dashboard.php?type=word" class="btn btn-info btn-sm">📝 Word</a>

    <a href="dashboard.php?type=zip" class="btn btn-dark btn-sm">📦 ZIP</a>

</div>
<form method="GET" class="mt-3">

<select name="sort" class="form-select w-auto d-inline">

<option value="">Sort By</option>

<option value="name">Name</option>

<option value="date">Upload Date</option>

<option value="size">File Size</option>

<option value="download">Downloads</option>

</select>

<button class="btn btn-primary btn-sm">
Apply
</button>

</form>
<div class="row mb-4">

<div class="col-md-3 mb-3">
<div class="card border-0 shadow rounded-4 h-100">

<div class="card-body text-center">

<div class="display-5 mb-2">📂</div>

<h2 class="fw-bold text-primary">
<?php echo $totalFiles; ?>
</h2>

<p class="text-muted mb-0">
Total Files
</p>

</div>

</div>
</div>


<div class="col-md-4">
<div class="card shadow h-100 border-0 text-center">
<div class="card-body">
<h1>💾</h1>
<h3><?php echo $storage; ?> MB</h3>
<div class="mt-2">

<div class="progress" style="height:22px;">

<div class="progress-bar bg-success"
role="progressbar"
style="width: <?php echo $storagePercent; ?>%;">

<?php echo round($storagePercent); ?>%

</div>

</div>

<small class="text-muted">

<?php echo $storage; ?> MB / <?php echo $maxStorage; ?> MB Used

</small>

</div>
<p>Storage Used</p>
</div>
</div>
</div>

</div>
</div>
<p class="text-muted">
Manage, upload and securely share your files.
</p>

        <p class="text-muted mb-0">
            Manage, upload and securely share your files from one place.
        </p>

    </div>
</div>
<div class="card shadow h-100 border-0 rounded-4 p-4 mb-4">

    <h4 class="mb-3">
        📊 File Analytics
    </h4>

    <div class="chart-box">
    <canvas id="fileChart"></canvas>
</div>

</div>
<div class="card shadow h-100 border-0 rounded-4 p-4">    
    <a href="upload.php" class="btn btn-primary mt-3">
    Upload New File
            </a>
            <hr>
<div class="card shadow h-100 border-0 rounded-4 p-4 mb-4">

<h4 class="fw-bold text-primary mb-3">
    <i class="bi bi-clock-history"></i> Recent Activity
</h4>

    <ul class="list-group">

    <?php while($activity = mysqli_fetch_assoc($activityResult)) { ?>

        <li class="list-group-item d-flex justify-content-between">

            <span>
                <?php echo $activity['activity']; ?>
            </span>

            <small class="text-muted">
                <?php echo date("d M Y H:i", strtotime($activity['created_at'])); ?>
            </small>

        </li>

    <?php } ?>

    </ul>


</div>

<h3 class="mt-4">My Files</h3>
<div class="table-responsive">
<table class="table table-bordered mt-3" id="fileTable">
<thead class="table-primary">
    <tr>
    <th>File Name</th>
    <th>Size</th>
    <th>Type</th>
    <th>Downloads</th>
    <th>Preview</th>
    <th>Download</th>
    <th>Favorite</th>
    <th>Share</th>
    <th>Rename</th>
    <th>Delete</th>
    <th>Info</th>
</tr>
</thead>
<tbody>
<?php


if(isset($_GET['favorite']))
{

$sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND favorite=1
AND deleted=0";
}

elseif(isset($_GET['type']))
{
    $filter = $_GET['type'];

    if($filter == "pdf")
    {
        $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0
AND file_type LIKE '%pdf%'";

    }
    elseif($filter == "image")
    {
       $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0
AND file_type LIKE 'image%'";
    }
    elseif($filter == "word")
    {
       $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0
AND (file_type LIKE '%word%'
OR file_type LIKE '%document%')";
    }
    elseif($filter == "zip")
    {
        $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0
AND file_type LIKE '%zip%'";
    }
    else
    {
        $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0";
    }
}
else
{
   
        $sql = "SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=0";
}
if(isset($_GET['sort']))
{
    $sort = $_GET['sort'];

    if($sort == "name")
    {
        $sql .= " ORDER BY original_name ASC";
    }
    elseif($sort == "date")
    {
        $sql .= " ORDER BY uploaded_at DESC";
    }
    elseif($sort == "size")
    {
        $sql .= " ORDER BY file_size DESC";
    }
    elseif($sort == "download")
    {
        $sql .= " ORDER BY downloaded DESC";
    }
}

$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result))
{
?>

<td>
    <?php echo htmlspecialchars($row['original_name'] ?? 'Unknown File'); ?>
</td>

<td>
    <?php echo round(($row['file_size'] ?? 0) / 1024, 2); ?> KB
</td>

<td>
    <?php
    $type = strtolower($row['file_type'] ?? '');

    if (strpos($type, "pdf") !== false) {
        echo "📄 PDF";
    }
    elseif (strpos($type, "image") !== false) {
        echo "🖼 Image";
    }
    elseif (
        strpos($type, "word") !== false ||
        strpos($type, "document") !== false
    ) {
        echo "📝 Word";
    }
    elseif (strpos($type, "zip") !== false) {
        echo "📦 ZIP";
    }
    else {
        echo "📁 File";
    }
    ?>
</td>

<td>
    <?php echo $row['downloaded'] ?? 0; ?>
</td>

<td>
    <a href="preview.php?id=<?php echo $row['id']; ?>"
       class="btn btn-success btn-sm rounded-pill px-3">
        👁 Preview
    </a>
</td>
    <td>
        <a href="download.php?id=<?php echo $row['id']; ?>"
           class="btn btn-success btn-sm rounded-pill px-3">
            Download
        </a>
    </td>

    <td>
        <a href="favorite.php?id=<?php echo $row['id']; ?>"
           class="btn btn-sm <?php echo ($row['favorite'] == 1) ? 'btn-warning' : 'btn-outline-warning'; ?>">

            <?php
            echo ($row['favorite'] == 1) ? "⭐" : "☆";
            ?>

        </a>
    </td>

    <td>
        <a href="share.php?id=<?php echo $row['id']; ?>"
           class="btn btn-warning btn-sm">
            🔗 Share
        </a>
    </td>

    <td>
        <a href="rename.php?id=<?php echo $row['id']; ?>"
           class="btn btn-success btn-sm rounded-pill px-3">
            ✏ Rename
        </a>
    </td>

    <td>
        <a href="delete.php?id=<?php echo $row['id']; ?>"
           class="btn btn-danger btn-sm"
           onclick="return confirm('Are you sure you want to delete this file?');">
            🗑 Delete
        </a>
    </td>

    <td>
        <a href="file_details.php?id=<?php echo $row['id']; ?>"
           class="btn btn-secondary btn-sm">
            ℹ Info
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
<script>
document.getElementById("searchInput").addEventListener("keyup", function () {

    let filter = this.value.toLowerCase();

    let rows = document.querySelectorAll("#fileTable tbody tr");

    rows.forEach(function(row){

        let text = row.innerText.toLowerCase();

        if(text.indexOf(filter) > -1){
            row.style.display = "";
        }else{
            row.style.display = "none";
        }

    });

});
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('fileChart');

new Chart(ctx, {
    type: 'pie',

    data: {
        labels: ['PDF', 'Images', 'Word', 'ZIP'],

        datasets: [{
            label: 'Files',
            data: [
                <?php echo $pdfCount; ?>,
                <?php echo $imageCount; ?>,
                <?php echo $wordCount; ?>,
                <?php echo $zipCount; ?>
            ],
            backgroundColor: [
                '#FF6384',
                '#36A2EB',
                '#FFCE56',
                '#4BC0C0'
            ],
            borderWidth: 1
        }]
    },

   options: {
    responsive: true,
    maintainAspectRatio: false,

    plugins: {
        legend: {
            position: 'right',
            labels: {
                font: {
                    size: 14
                }
            }
        }
    }
   }
});
</script>
</body>
</html>
