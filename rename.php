<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

$id = $_GET['id'];

$result = mysqli_query($conn,"SELECT * FROM files WHERE id='$id'");
$file = mysqli_fetch_assoc($result);

if(isset($_POST['rename']))
{
    $newName = trim($_POST['new_name']);

    if($newName != "")
    {
        mysqli_query($conn,"UPDATE files SET original_name='$newName' WHERE id='$id'");

        echo "<script>alert('File Renamed Successfully');</script>";
        echo "<script>window.location='dashboard.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Rename File</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-5">

<div class="card shadow p-4">

<h3 class="mb-4 text-center">
Rename File
</h3>

<form method="POST">

<div class="mb-3">

<label>Current Name</label>

<input type="text"
class="form-control"
value="<?php echo $file['original_name']; ?>"
readonly>

</div>

<div class="mb-3">

<label>New Name</label>

<input type="text"
name="new_name"
class="form-control"
value="<?php echo $file['original_name']; ?>"
required>

</div>

<button class="btn btn-primary w-100"
name="rename">

Save Changes

</button>

</form>

</div>

</div>

</div>

</div>

</body>
</html>