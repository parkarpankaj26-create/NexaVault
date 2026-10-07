<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id']))
{
    die("Invalid Request");
}

$id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn,
"SELECT * FROM files
WHERE id='$id' AND user_id='$user_id'");

if(mysqli_num_rows($result)==0)
{
    die("File Not Found");
}

$file = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>

<head>

<title>File Details</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include 'includes/user_navbar.php'; ?>

<div class="container mt-5">

<div class="card shadow p-4">

<h2 class="text-primary mb-4">
📄 File Details
</h2>

<table class="table table-bordered">

<tr>
<th width="30%">File Name</th>
<td><?php echo $file['original_name']; ?></td>
</tr>

<tr>
<th>File Type</th>
<td><?php echo $file['file_type']; ?></td>
</tr>

<tr>
<th>File Size</th>
<td><?php echo round($file['file_size']/1024,2); ?> KB</td>
</tr>

<tr>
<th>Upload Date</th>
<td><?php echo $file['uploaded_at']; ?></td>
</tr>

<tr>
<th>Total Downloads</th>
<td><?php echo $file['downloaded']; ?></td>
</tr>

<tr>
<th>Favorite</th>
<td>
<?php
echo ($file['favorite']==1) ? "⭐ Yes" : "❌ No";
?>
</td>
</tr>

<tr>
<th>Shared</th>
<td>
<?php
echo ($file['is_shared']==1) ? "🔗 Yes" : "❌ No";
?>
</td>
</tr>

</table>

<a href="dashboard.php" class="btn btn-primary">
⬅ Back to Dashboard
</a>

</div>

</div>

</body>
</html>