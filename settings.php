<?php
session_start();
include 'includes/db.php';
include 'includes/activity.php';

if(!isset($_SESSION['user']))
{
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn,"SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($result);

if(isset($_POST['update_profile']))
{
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);

    mysqli_query($conn,
    "UPDATE users SET
    name='$name',
    email='$email'
    WHERE id='$user_id'");

    $_SESSION['user'] = $name;

    logActivity($conn,$user_id,"Updated Profile");

    header("Location: settings.php?success=1");
    exit();
}

if(isset($_POST['change_password']))
{
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if(password_verify($current,$user['password']))
    {
        if($new==$confirm)
        {
            $hash=password_hash($new,PASSWORD_DEFAULT);

            mysqli_query($conn,
            "UPDATE users
            SET password='$hash'
            WHERE id='$user_id'");

            logActivity($conn,$user_id,"Changed Password");

            header("Location: settings.php?password=1");
            exit();
        }
        else
        {
            $error="New passwords do not match.";
        }
    }
    else
    {
        $error="Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>

<title>Settings</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include 'includes/user_navbar.php'; ?>

<div class="container mt-5">

<div class="card shadow p-4">

<h2 class="text-primary mb-4">
⚙ Account Settings
</h2>

<?php

if(isset($_GET['success']))
{
    echo "<div class='alert alert-success'>Profile Updated Successfully</div>";
}

if(isset($_GET['password']))
{
    echo "<div class='alert alert-success'>Password Changed Successfully</div>";
}

if(isset($error))
{
    echo "<div class='alert alert-danger'>$error</div>";
}

?>

<!-- ===== Profile Form ===== -->

<form method="POST">

<div class="mb-3">

<label>Name</label>

<input
type="text"
name="name"
class="form-control"
value="<?php echo $user['name']; ?>">

</div>

<div class="mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control"
value="<?php echo $user['email']; ?>">

</div>

<button
type="submit"
name="update_profile"
class="btn btn-primary">

Update Profile

</button>

</form>

<hr class="my-4">

<!-- ===== Password Form ===== -->

<form method="POST">

<div class="mb-3">

<label>Current Password</label>

<input
type="password"
name="current_password"
class="form-control">

</div>

<div class="mb-3">

<label>New Password</label>

<input
type="password"
name="new_password"
class="form-control">

</div>

<div class="mb-3">

<label>Confirm Password</label>

<input
type="password"
name="confirm_password"
class="form-control">

</div>

<button
type="submit"
name="change_password"
class="btn btn-success">

Change Password

</button>

</form>

<a href="dashboard.php" class="btn btn-secondary mt-4">

⬅ Back to Dashboard

</a>

</div>

</div>

</body>

</html>
