<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user']) || $_SESSION['role'] != "admin")
{
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id']))
{
    header("Location: admin_dashboard.php");
    exit();
}

$id = intval($_GET['id']);

$user = mysqli_fetch_assoc(
mysqli_query($conn,
"SELECT * FROM users WHERE id='$id'")
);

$totalFiles = mysqli_num_rows(
mysqli_query($conn,
"SELECT * FROM files WHERE user_id='$id'")
);

$totalDownloads = mysqli_fetch_assoc(
mysqli_query($conn,
"SELECT SUM(downloaded) AS total FROM files WHERE user_id='$id'")
);

$files = mysqli_query($conn,
"SELECT * FROM files WHERE user_id='$id' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>

<head>

<title>View User</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow p-4">

<h2 class="text-primary mb-4">
👤 User Profile
</h2>

<table class="table table-bordered">

<tr>
<th>Name</th>
<td><?php echo $user['name']; ?></td>
</tr>

<tr>
<th>Email</th>
<td><?php echo $user['email']; ?></td>
</tr>

<tr>
<th>Role</th>
<td><?php echo ucfirst($user['role']); ?></td>
</tr>

<tr>
<th>Status</th>
<td><?php echo ucfirst($user['status']); ?></td>
</tr>

<tr>
<th>Total Files</th>
<td><?php echo $totalFiles; ?></td>
</tr>

<tr>
<th>Total Downloads</th>
<td><?php echo $totalDownloads['total'] ? $totalDownloads['total'] : 0; ?></td>
</tr>

</table>

<hr>

<h4 class="mb-3">
📁 Uploaded Files
</h4>

<table class="table table-hover table-bordered">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>File Name</th>

<th>Type</th>

<th>Size</th>

<th>Downloads</th>

</tr>

</thead>

<tbody>

<?php while($f=mysqli_fetch_assoc($files)){ ?>

<tr>

<td><?php echo $f['id']; ?></td>

<td><?php echo $f['original_name']; ?></td>

<td><?php echo $f['file_type']; ?></td>

<td><?php echo round($f['file_size']/1024,2); ?> KB</td>

<td><?php echo $f['downloaded']; ?></td>

</tr>

<?php } ?>

</tbody>

</table>

<a href="admin_dashboard.php"
class="btn btn-secondary">

⬅ Back

</a>

</div>

</div>

</body>

</html>