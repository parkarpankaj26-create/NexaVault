<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn,
"SELECT * FROM files
WHERE user_id='$user_id'
ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">

<title>Recent Activity</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include 'includes/user_navbar.php'; ?>

<div class="container mt-5">

<h2 class="mb-4">
📜 Recent Activity
</h2>

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>File Name</th>

<th>Type</th>

<th>Size</th>

<th>Uploaded</th>

</tr>

</thead>

<tbody>

<?php

while($row=mysqli_fetch_assoc($result))
{
?>

<tr>

<td><?php echo $row['original_name']; ?></td>

<td><?php echo $row['file_type']; ?></td>

<td><?php echo $row['file_size']; ?> KB</td>

<td><?php echo $row['uploaded_at']; ?></td>

</tr>

<?php
}
?>

</tbody>

</table>

</div>

</body>
</html>