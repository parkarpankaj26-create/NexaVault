<?php
include 'includes/db.php';

if(isset($_POST['reset']))
{
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $question = $_POST['security_question'];
    $answer = $_POST['security_answer'];
    $check = mysqli_query($conn, "SELECT * FROM users 
    WHERE email='$email' 
    AND security_question='$question' 
    AND security_answer='$answer'");

    if(mysqli_num_rows($check) > 0)
    {
        mysqli_query($conn, "UPDATE users SET password='$password' WHERE email='$email'");

        echo "<script>alert('Password Updated Successfully');</script>";
        echo "<script>window.location='login.php';</script>";
    }
    else
    {
         echo "<script>alert('Invalid Credentials');</script>";

    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

<div class="row justify-content-center">

<div class="col-md-5">

<div class="card shadow p-4">

<h2 class="text-center text-primary mb-4">
Forgot Password
</h2>

<form method="POST">

<div class="mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>
<div class="mb-3">
    <label>Security Question</label>
    <select name="security_question" class="form-control" required>
        <option value="">Select a Question</option>
        <option>What is your favorite color?</option>
        <option>What is your pet's name?</option>
        <option>What is your birth city?</option>
        <option>What is your favorite food?</option>
    </select>
</div>

<div class="mb-3">
    <label>Answer</label>
    <input type="text" name="security_answer" class="form-control" required>
</div>

<div class="mb-3">
<label>New Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<button type="submit" name="reset" class="btn btn-primary w-100">
Reset Password
</button>

</form>

</div>

</div>

</div>

</div>

</body>
</html>