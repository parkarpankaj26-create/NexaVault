<?php
include 'includes/db.php';
session_start();

if(isset($_POST['login']))
{
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0)
    {
        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password']))
        {
            if($user['status'] == "blocked")
            {
                echo "<script>alert('Your account has been blocked by Admin.');</script>";
            }
            else
            {
                $_SESSION['user'] = $user['name'];
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                if($user['role'] == "admin")
                {
                    header("Location: admin_dashboard.php");
                }
                else
                {
                    header("Location: dashboard.php");
                }

                exit();
            }
        }
        else
        {
            echo "<script>alert('Incorrect Password');</script>";
        }
    }
    else
    {
        echo "<script>alert('Email not found');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SecureShare</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>


<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow">

<div class="container">

<a class="navbar-brand fw-bold" href="index.php">
🔐 SecureShare
</a>

<div class="ms-auto">

<a href="index.php" class="btn btn-outline-light btn-sm me-2">
🏠 Home
</a>

<a href="register.php" class="btn btn-warning btn-sm">
📝 Register
</a>

</div>

</div>

</nav>
<div class="container mt-5">
    <div class="row justify-content-center">

        <div class="col-md-5">

            <div class="card shadow p-4">

                <h2 class="text-center text-primary mb-4">
                    Login
                </h2>

                <form method="POST">

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                       <div class="input-group">
    <input type="password" id="password" name="password" class="form-control" required>

    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
        👁️
    </button>
</div>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100">
                        Login
                    </button>

                    <div class="text-center mt-3">
            <a href="forgot_password.php">
                Forgot Password?
            </a>
            
            </div>
<div class="text-center mt-3">

<a href="index.php" class="text-decoration-none">
⬅ Back to Home
</a>

<br><br>

Don't have an account?

<a href="register.php" class="fw-bold text-decoration-none">
Register Here
</a>

</div>
                </form>

            </div>

        </div>

    </div>
</div>
<script>
function togglePassword()
{
    var x = document.getElementById("password");

    if(x.type === "password")
    {
        x.type = "text";
    }
    else
    {
        x.type = "password";
    }
}
</script>
</body>
</html>