<?php
session_start();

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn,
"SELECT * FROM files
WHERE user_id='$user_id'
AND deleted=1");
?>
<!DOCTYPE html>
<html>

<head>

<title>Recycle Bin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include 'includes/user_navbar.php'; ?>

<div class="container mt-4">

<h2 class="mb-4">
🗑 Recycle Bin
</h2>

<table class="table table-bordered table-hover">

<thead>

<tr>

<th>File Name</th>

<th>Size</th>

<th>Restore</th>

<th>Delete Forever</th>

</tr>

</thead>

<tbody>
<?php

while($row=mysqli_fetch_assoc($result))
{
?>

<tr>

<td><?php echo $row['original_name']; ?></td>

<td>

<?php echo round($row['file_size']/1024,2); ?>

KB

</td>

<td>

<a href="restore.php?id=<?php echo $row['id']; ?>"

class="btn btn-success btn-sm">

♻ Restore

</a>

</td>

<td>

<a href="delete_forever.php?id=<?php echo $row['id']; ?>"

class="btn btn-danger btn-sm"

onclick="return confirm('Delete permanently?')">

🗑 Delete Forever

</a>

</td>

</tr>

<?php
}
?>

</tbody>

</table>

</div>

</body>

</html>
