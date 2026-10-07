<?php
session_start();

require_once __DIR__ . '/includes/db.php';

if (isset($_POST['register'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];

    $sql = "INSERT INTO users(name,email,password,security_question,security_answer)
            VALUES('$name','$email','$password','$security_question','$security_answer')";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
        alert('Registration Successful! Please Login.');
        window.location.href='login.php';
        </script>";

    } else {

        echo "<script>
        alert('Something went wrong');
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Account - NexaVault</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

/* ==============================
   GLOBAL
============================== */

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;

    font-family: "Segoe UI", Arial, sans-serif;

    color: #1e293b;

    background:
        radial-gradient(
            circle at 10% 15%,
            rgba(37, 99, 235, 0.08),
            transparent 30%
        ),
        radial-gradient(
            circle at 90% 25%,
            rgba(124, 58, 237, 0.07),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #eef5ff 50%,
            #f8fafc 100%
        );

    background-attachment: fixed;
}


/* ==============================
   NAVBAR
============================== */

.navbar {
    background: rgba(255, 255, 255, 0.96) !important;

    border-bottom: 1px solid #e2e8f0;

    backdrop-filter: blur(14px);

    box-shadow:
        0 8px 25px rgba(15, 23, 42, 0.06);
}

.navbar-brand {
    font-size: 21px;
    letter-spacing: 0.2px;

    color: #0f172a !important;
}

.navbar-brand span {
    color: #2563eb;
}

.navbar-brand i {
    color: #2563eb !important;
}


/* ==============================
   NAVBAR BUTTONS
============================== */

.navbar .btn-outline-light {
    color: #334155 !important;

    background: #ffffff !important;

    border: 1px solid #cbd5e1 !important;

    border-radius: 8px;
}

.navbar .btn-outline-light:hover {
    color: #1e293b !important;

    background: #f1f5f9 !important;

    border-color: #94a3b8 !important;
}

.navbar .btn-primary {
    border: none;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    border-radius: 8px;
}


/* ==============================
   REGISTER AREA
============================== */

.register-wrapper {

    min-height: calc(100vh - 73px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 45px 15px;
}


/* ==============================
   REGISTER CARD
============================== */

.register-card {

    width: 100%;

    max-width: 500px;

    position: relative;

    padding: 38px;

    border-radius: 24px;

    background:
        radial-gradient(
            circle at 90% 0%,
            rgba(37, 99, 235, 0.07),
            transparent 30%
        ),
        #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 20px 55px rgba(15, 23, 42, 0.10);

    overflow: hidden;
}


/* ==============================
   GLOWING CIRCLE
============================== */

.register-card::before {

    content: "";

    position: absolute;

    width: 220px;
    height: 220px;

    right: -110px;
    top: -110px;

    border-radius: 50%;

    background: rgba(37, 99, 235, 0.06);

    filter: blur(8px);

    pointer-events: none;
}


/* ==============================
   ICON
============================== */

.register-icon {

    width: 72px;
    height: 72px;

    margin: 0 auto 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 20px;

    font-size: 32px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    box-shadow:
        0 12px 30px rgba(37, 99, 235, 0.20);
}


/* ==============================
   HEADINGS
============================== */

.register-card h2 {

    color: #0f172a;

    font-size: 29px;

    font-weight: 700;

    margin-bottom: 7px;
}

.register-subtitle {

    color: #64748b;

    font-size: 14px;

    margin-bottom: 28px;
}


/* ==============================
   FORM
============================== */

.form-label {

    color: #334155;

    font-size: 14px;

    font-weight: 600;

    margin-bottom: 8px;
}

.form-label i {
    color: #2563eb;
}


/* ==============================
   INPUT GROUP
============================== */

.input-group {

    position: relative;
}


/* ==============================
   INPUT ICON
============================== */

.input-group-text {

    background: #f8fafc !important;

    color: #2563eb !important;

    border: 1px solid #cbd5e1 !important;

    border-right: none !important;

    border-radius: 12px 0 0 12px !important;
}


/* ==============================
   INPUTS
============================== */

.form-control,
.form-select {

    min-height: 46px;

    background: #ffffff !important;

    color: #1e293b !important;

    border: 1px solid #cbd5e1 !important;

    border-radius: 12px;

    font-size: 14px;
}


.input-group .form-control {

    border-radius: 0 12px 12px 0;
}


.form-control::placeholder {

    color: #94a3b8 !important;
}


.form-control:focus,
.form-select:focus {

    background: #ffffff !important;

    color: #1e293b !important;

    border-color: #2563eb !important;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, 0.10) !important;
}


.form-select option {

    background: #ffffff;

    color: #1e293b;
}


/* ==============================
   PASSWORD BUTTON
============================== */

.password-toggle {

    cursor: pointer;

    background: #f8fafc;

    color: #2563eb;

    border: 1px solid #cbd5e1;

    border-left: none;

    border-radius: 0 12px 12px 0;

    padding: 0 14px;

    display: flex;

    align-items: center;
}

.password-toggle:hover {

    color: #1d4ed8;

    background: #eff6ff;
}


/* ==============================
   PASSWORD NOTE
============================== */

.register-card .text-muted {

    color: #64748b !important;

    font-size: 11px;
}


/* ==============================
   SECURITY BOX
============================== */

.security-section {

    margin-top: 22px;

    padding: 18px;

    border-radius: 16px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;
}


.security-title {

    color: #2563eb;

    font-size: 13px;

    font-weight: 700;

    margin-bottom: 15px;

    display: flex;

    align-items: center;

    gap: 7px;
}


/* ==============================
   REGISTER BUTTON
============================== */

.register-btn {

    width: 100%;

    min-height: 48px;

    margin-top: 18px;

    border: none;

    border-radius: 12px;

    color: white;

    font-size: 15px;

    font-weight: 600;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    box-shadow:
        0 10px 25px rgba(37, 99, 235, 0.20);

    transition: all 0.25s ease;
}


.register-btn:hover {

    color: white;

    transform: translateY(-2px);

    box-shadow:
        0 14px 32px rgba(37, 99, 235, 0.28);
}


/* ==============================
   BOTTOM LINKS
============================== */

.bottom-links {

    text-align: center;

    margin-top: 22px;

    color: #64748b;

    font-size: 13px;
}


.bottom-links a {

    color: #2563eb;

    text-decoration: none;

    font-weight: 600;
}


.bottom-links a:hover {

    color: #1d4ed8;

    text-decoration: underline;
}


/* ==============================
   SECURITY NOTE
============================== */

.security-note {

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 6px;

    margin-top: 18px;

    color: #64748b;

    font-size: 12px;
}

.security-note i {
    color: #2563eb;
}


/* ==============================
   MOBILE
============================== */

@media(max-width:576px) {

    .register-wrapper {

        padding: 25px 15px;
    }

    .register-card {

        padding: 27px 20px;

        border-radius: 21px;
    }

    .register-card h2 {

        font-size: 25px;
    }

    .navbar .btn {

        font-size: 12px;
    }

}


/* ==============================
   PRINT
============================== */

@media print {

    body {
        background: #ffffff !important;
        color: #000000 !important;
    }

    .navbar {
        background: #ffffff !important;
        box-shadow: none !important;
    }

    .register-card {
        background: #ffffff !important;
        border: 1px solid #d1d5db !important;
        box-shadow: none !important;
    }

    .register-card h2 {
        color: #000000 !important;
    }

    .register-subtitle,
    .bottom-links,
    .security-note {
        color: #475569 !important;
    }

}

</style>

</head>


<body>


<!-- ==============================
     NAVBAR
============================== -->

<nav class="navbar navbar-expand-lg">

<div class="container">

<a class="navbar-brand fw-bold" href="index.php">

<i class="bi bi-shield-lock-fill"></i>

Nexa<span>Vault</span>

</a>


<div class="ms-auto">

<a href="index.php"
class="btn btn-outline-light btn-sm me-2">

<i class="bi bi-house"></i>

Home

</a>


<a href="login.php"
class="btn btn-primary btn-sm">

<i class="bi bi-box-arrow-in-right"></i>

Login

</a>

</div>

</div>

</nav>


<!-- ==============================
     REGISTER
============================== -->

<div class="register-wrapper">


<div class="register-card">


<div class="text-center">


<div class="register-icon">

<i class="bi bi-person-plus-fill"></i>

</div>


<h2>Create Your Account</h2>

<p class="register-subtitle">

Join NexaVault and securely manage your files.

</p>

</div>


<form method="POST">


<!-- NAME -->

<div class="mb-3">

<label class="form-label">

<i class="bi bi-person"></i>

Full Name

</label>

<div class="input-group">

<span class="input-group-text">

<i class="bi bi-person-fill"></i>

</span>

<input
type="text"
name="name"
class="form-control"
placeholder="Enter your full name"
required>

</div>

</div>


<!-- EMAIL -->

<div class="mb-3">

<label class="form-label">

<i class="bi bi-envelope"></i>

Email Address

</label>

<div class="input-group">

<span class="input-group-text">

<i class="bi bi-envelope-fill"></i>

</span>

<input
type="email"
name="email"
class="form-control"
placeholder="Enter your email address"
required>

</div>

</div>


<!-- PASSWORD -->

<div class="mb-3">

<label class="form-label">

<i class="bi bi-lock"></i>

Password

</label>

<div class="input-group">

<span class="input-group-text">

<i class="bi bi-lock-fill"></i>

</span>

<input
type="password"
name="password"
id="password"
class="form-control"
placeholder="Create a strong password"
required>

<button
type="button"
class="password-toggle"
onclick="togglePassword()">

<i class="bi bi-eye" id="eyeIcon"></i>

</button>

</div>

<small class="text-muted">

Use a strong password to protect your account.

</small>

</div>


<!-- SECURITY -->

<div class="security-section">


<div class="security-title">

<i class="bi bi-shield-check"></i>

Account Recovery Security

</div>


<!-- QUESTION -->

<div class="mb-3">

<label class="form-label">

Security Question

</label>

<select
name="security_question"
class="form-select"
required>

<option value="">

Select a recovery question

</option>

<option>
What is your favorite color?
</option>

<option>
What is your pet's name?
</option>

<option>
What is your birth city?
</option>

<option>
What is your favorite food?
</option>

</select>

</div>


<!-- ANSWER -->

<div>

<label class="form-label">

Security Answer

</label>

<input
type="text"
name="security_answer"
class="form-control"
placeholder="Enter your answer"
required>

</div>

</div>


<!-- BUTTON -->

<button
type="submit"
name="register"
class="register-btn">

<i class="bi bi-person-check-fill me-2"></i>

Create NexaVault Account

</button>


</form>


<!-- LINKS -->

<div class="bottom-links">

Already have a NexaVault account?

<a href="login.php">

Login Here

</a>

<br>

<a href="index.php">

<i class="bi bi-arrow-left"></i>

Back to Home

</a>

</div>


<div class="security-note">

<i class="bi bi-shield-lock-fill"></i>

Your account is protected by NexaVault security.

</div>


</div>

</div>


<script>

function togglePassword()
{
    const password = document.getElementById("password");

    const icon = document.getElementById("eyeIcon");

    if(password.type === "password")
    {
        password.type = "text";

        icon.classList.remove("bi-eye");

        icon.classList.add("bi-eye-slash");
    }
    else
    {
        password.type = "password";

        icon.classList.remove("bi-eye-slash");

        icon.classList.add("bi-eye");
    }
}

</script>


</body>

</html>