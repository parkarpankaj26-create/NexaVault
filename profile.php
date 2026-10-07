<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];

$query = mysqli_query($conn, "SELECT * FROM users WHERE name='$user'");
$data = mysqli_fetch_assoc($query);

$user_id = $_SESSION['user_id'];

$totalFiles = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) AS total FROM files WHERE user_id='$user_id'"))['total'];

$totalDownloads = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(downloaded) AS total FROM files WHERE user_id='$user_id'"))['total'];

if($totalDownloads == NULL)
{
    $totalDownloads = 0;
}

$storage = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT SUM(file_size) AS total FROM files WHERE user_id='$user_id'"))['total'];

if($storage == NULL)
{
    $storage = 0;
}

$storage = round($storage / 1024 / 1024,2);
?>

<!DOCTYPE html>
<html>
<head>

<title>My Profile</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="card shadow p-4">

<h2 class="text-center mb-4">
👤 My Profile
</h2>

<table class="table">

<tr>
<th>Name</th>
<td><?php echo $data['name']; ?></td>
</tr>

<tr>
<th>Email</th>
<td><?php echo $data['email']; ?></td>
</tr>

<tr>
<th>Account Created</th>
<td><?php echo $data['created_at']; ?></td>
</tr>

<tr>
<th>Total Files</th>
<td><?php echo $totalFiles; ?></td>
</tr>

<tr>
<th>Total Downloads</th>
<td><?php echo $totalDownloads; ?></td>
</tr>

<tr>
<th>Storage Used</th>
<td><?php echo $storage; ?> MB</td>
</tr>

</table>

<a href="dashboard.php" class="btn btn-primary">
⬅ Back to Dashboard
</a>

</div>

</div>

</body>
</html>