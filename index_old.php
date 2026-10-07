<?php
include 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">


<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SecureShare</title>

    <!-- Bootstrap CSS -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS -->

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm">

    <div class="container">

        <a class="navbar-brand fw-bold fs-3 text-primary" href="#">
            SecureShare
        </a>

        <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbar">

            <span class="navbar-toggler-icon"></span>

        </button>

        <div class="collapse navbar-collapse" id="navbar">

            <ul class="navbar-nav ms-auto">

                <li class="nav-item">
                    <a class="nav-link active" href="#home">Home</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#features">Features</a>
                </li>

                <li class="nav-item">
                   <a class="nav-link" href="#about">About</a>
                </li>

                <li class="nav-item">
                    <a class="btn btn-outline-primary ms-2" href="login.php">
                        Login
                    </a>
                </li>

                <li class="nav-item">
                    <a class="btn btn-primary ms-2" href="register.php">
                        Register
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>
<section id="features" class="container py-5">

    <div class="text-center mb-5">

        <h2 class="fw-bold">
            Why Choose <span class="text-primary">SecureShare?</span>
            </h1>

            <p class="lead mt-4">

                SecureShare lets you upload, protect and share your files
                safely with password protection and secure access.

            </p>

            <a href="register.php" class="btn btn-primary btn-lg mt-3">
                Get Started
            </a>

            <a href="#" class="btn btn-outline-primary btn-lg mt-3 ms-2">
                Learn More
            </a>

        </div>

        <div class="col-lg-6 text-center">

            <div class="hero-card bg-light rounded-4 p-5 shadow">

    <h1 class="text-primary fw-bold">
        🔐 SecureShare
    </h1>

    <p class="mt-3">
        Secure File Sharing Platform
    </p>

</div>

        </div>

    </div>
<section id="home" class="container py-5">

    <div class="text-center mb-5">

        <h2 class="fw-bold">
            Why Choose <span class="text-primary">SecureShare?</span>
        </h2>

        <p class="text-muted">
            Fast, Secure and Reliable File Sharing Platform
        </p>

    </div>

    <div class="row g-4">

        <div class="col-md-4">

            <div class="card shadow border-0 p-4 h-100 text-center">

                <h1>🔒</h1>

                <h4>Secure Upload</h4>

                <p>
                    Upload files safely with advanced protection.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow border-0 p-4 h-100 text-center">

                <h1>⚡</h1>

                <h4>Fast Sharing</h4>

                <p>
                    Share files instantly with secure links.
                </p>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow border-0 p-4 h-100 text-center">

                <h1>🛡️</h1>

                <h4>Password Protection</h4>

                <p>
                    Keep your files safe using password protection.
                </p>

            </div>

        </div>

    </div>

</section>
</section>
<section id="about" class="container py-5">

    <div class="card shadow border-0 rounded-4 p-5">

        <h2 class="text-primary fw-bold mb-4">
            About SecureShare
        </h2>

        <p class="lead">
            SecureShare is a secure file sharing system developed using
            PHP, MySQL and Bootstrap.
        </p>

        <p>
            It allows users to register, login, upload files,
            download files, delete files and reset passwords securely.
            The project focuses on providing a simple, fast and secure
            file management experience.
        </p>

    </div>

</section>
<section class="container py-5">

    <div class="row text-center">

        <div class="col-md-3">

            <h2 class="text-primary fw-bold">5000+</h2>
            <p>Files Shared</p>

        </div>

        <div class="col-md-3">

            <h2 class="text-primary fw-bold">2500+</h2>
            <p>Happy Users</p>

        </div>

        <div class="col-md-3">

            <h2 class="text-primary fw-bold">99.9%</h2>
            <p>Secure Transfers</p>

        </div>

        <div class="col-md-3">

            <h2 class="text-primary fw-bold">24/7</h2>
            <p>Availability</p>

        </div>

    </div>

</section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>