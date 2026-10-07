<?php

session_start();

/* =====================================================
   DATABASE CONNECTION
===================================================== */

$dbFile = __DIR__ . '/includes/db.php';

if (!file_exists($dbFile)) {
    die(
        "Database configuration file not found.<br><br>" .
        "Expected location:<br>" .
        htmlspecialchars($dbFile)
    );
}

require_once $dbFile;


/* =====================================================
   LIVE STATISTICS
===================================================== */

// Total registered users
$totalUsers = 0;

$userResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

if ($userResult) {
    $userData = mysqli_fetch_assoc($userResult);
    $totalUsers = (int)($userData['total'] ?? 0);
}


// Total active files
$totalFiles = 0;

$fileResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE deleted=0"
);

if ($fileResult) {
    $fileData = mysqli_fetch_assoc($fileResult);
    $totalFiles = (int)($fileData['total'] ?? 0);
}


// Total secure shared files
$totalShares = 0;

$shareResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE is_shared=1
     AND deleted=0"
);

if ($shareResult) {
    $shareData = mysqli_fetch_assoc($shareResult);
    $totalShares = (int)($shareData['total'] ?? 0);
}


// Total downloads
$totalDownloads = 0;

$downloadResult = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(downloaded),0) AS total
     FROM files
     WHERE deleted=0"
);

if ($downloadResult) {
    $downloadData = mysqli_fetch_assoc($downloadResult);
    $totalDownloads = (int)($downloadData['total'] ?? 0);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>NexaVault - Secure File Sharing</title>


<!-- Bootstrap -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">


<!-- Bootstrap Icons -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- Main CSS -->

<link
    rel="stylesheet"
    href="assets/css/style.css?v=201">

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg nv-navbar fixed-top">

    <div class="container">


        <a
            class="navbar-brand nv-brand"
            href="index.php">

            <img
                src="assets/images/logo.png"
                alt="NexaVault Logo"
                class="nv-logo">

            <div class="nv-brand-text">

                <strong>
                    Nexa<span>Vault</span>
                </strong>

                <small>
                    Secure • Share • Trust
                </small>

            </div>

        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar">

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="mainNavbar">

            <ul
                class="navbar-nav ms-auto align-items-lg-center">


                <li class="nav-item">

                    <a
                        class="nav-link active"
                        href="#home">

                        Home

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#features">

                        Features

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#how-it-works">

                        How It Works

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#about">

                        About

                    </a>

                </li>


                <li class="nav-item">

                    <a
                        class="nav-link"
                        href="#contact">

                        Contact

                    </a>

                </li>


                <li class="nav-item ms-lg-3">

                    <button
                        id="themeToggle"
                        class="theme-btn"
                        type="button">

                        🌙

                    </button>

                </li>


                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <a
                        href="login.php"
                        class="btn nv-login">

                        <i class="bi bi-box-arrow-in-right"></i>

                        Login

                    </a>

                </li>


                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">

                    <a
                        href="register.php"
                        class="btn nv-register">

                        <i class="bi bi-person-plus-fill"></i>

                        Get Started

                    </a>

                </li>


            </ul>

        </div>

    </div>

</nav>



<!-- =====================================================
     HERO
===================================================== -->

<section
    id="home"
    class="nv-hero">

    <div class="hero-glow glow-one"></div>

    <div class="hero-glow glow-two"></div>


    <div class="container">

        <div class="row align-items-center g-5">


            <!-- LEFT -->

            <div class="col-lg-6">


                <div class="hero-badge">

                    <i class="bi bi-shield-lock-fill"></i>

                    Secure File Sharing Platform

                </div>


                <div class="hero-trust">

                    <span>
                        <i class="bi bi-shield-check"></i>
                        100% Secure
                    </span>

                    <span>
                        <i class="bi bi-lightning-charge-fill"></i>
                        Fast Sharing
                    </span>

                    <span>
                        <i class="bi bi-link-45deg"></i>
                        Secure Links
                    </span>

                </div>


                <h1 class="hero-title">

                    Secure Your Files.

                    <span>
                        Share Without Limits.
                    </span>

                </h1>


                <p class="hero-description">

                    NexaVault is a modern secure file sharing platform
                    that helps you upload, protect and share important
                    files using password-protected and controlled-access links.

                </p>


                <div class="hero-buttons">


                    <a
                        href="register.php"
                        class="btn hero-primary">

                        <i class="bi bi-cloud-arrow-up-fill"></i>

                        Start Sharing

                        <i class="bi bi-arrow-right"></i>

                    </a>


                    <a
                        href="#how-it-works"
                        class="btn hero-secondary">

                        <i class="bi bi-play-circle-fill"></i>

                        How It Works

                    </a>


                </div>


                <div class="hero-mini-stats">


                    <div>

                        <strong>
                            <i class="bi bi-lock-fill"></i>
                        </strong>

                        <span>
                            Password Protected
                        </span>

                    </div>


                    <div>

                        <strong>
                            <i class="bi bi-clock-fill"></i>
                        </strong>

                        <span>
                            Expiry Control
                        </span>

                    </div>


                    <div>

                        <strong>
                            <i class="bi bi-qr-code"></i>
                        </strong>

                        <span>
                            QR Sharing
                        </span>

                    </div>


                </div>

            </div>



            <!-- RIGHT -->

            <div class="col-lg-6">


                <div class="vault-showcase">


                    <div class="vault-glow"></div>


                    <img
                        src="assets/images/vault-hero.png"
                        alt="NexaVault Secure File Sharing"
                        class="vault-hero-image">


                    <div class="floating-file file-one">

                        <i class="bi bi-file-earmark-pdf-fill"></i>

                    </div>


                    <div class="floating-file file-two">

                        <i class="bi bi-file-earmark-word-fill"></i>

                    </div>


                    <div class="floating-file file-three">

                        <i class="bi bi-file-earmark-image-fill"></i>

                    </div>


                    <div class="floating-file file-four">

                        <i class="bi bi-folder-fill"></i>

                    </div>


                    <div class="vault-cloud">

                        <i class="bi bi-cloud-arrow-up-fill"></i>

                    </div>


                    <div class="vault-box">

                        <div class="vault-door">

                            <div class="vault-ring">

                                <i class="bi bi-lock-fill"></i>

                            </div>


                            <div class="vault-shield">

                                <i class="bi bi-shield-lock-fill"></i>

                            </div>

                        </div>

                    </div>


                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     LIVE STATISTICS
===================================================== -->

<section class="nv-stats">

    <div class="container">


        <div class="stats-heading">

            <span>
                NEXAVAULT AT A GLANCE
            </span>

            <h2>

                Trusted File Sharing,

                <strong>
                    Built For Security.
                </strong>

            </h2>

            <p>
                Real-time statistics from the NexaVault platform.
            </p>

        </div>


        <div class="row g-4">


            <!-- USERS -->

            <div class="col-lg-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon blue">

                        <i class="bi bi-people-fill"></i>

                    </div>


                    <div class="stat-number">

                        <?php echo $totalUsers; ?>+

                    </div>


                    <div class="stat-title">

                        Registered Users

                    </div>


                    <div class="stat-description">

                        Secure user accounts

                    </div>

                </div>

            </div>



            <!-- FILES -->

            <div class="col-lg-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon purple">

                        <i class="bi bi-folder-fill"></i>

                    </div>


                    <div class="stat-number">

                        <?php echo $totalFiles; ?>+

                    </div>


                    <div class="stat-title">

                        Files Stored

                    </div>


                    <div class="stat-description">

                        Protected digital files

                    </div>

                </div>

            </div>



            <!-- SHARED FILES -->

            <div class="col-lg-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon green">

                        <i class="bi bi-share-fill"></i>

                    </div>


                    <div class="stat-number">

                        <?php echo $totalShares; ?>+

                    </div>


                    <div class="stat-title">

                        Secure Shares

                    </div>


                    <div class="stat-description">

                        Protected sharing links

                    </div>

                </div>

            </div>



            <!-- DOWNLOADS -->

            <div class="col-lg-3 col-md-6">

                <div class="stat-card">

                    <div class="stat-icon orange">

                        <i class="bi bi-download"></i>

                    </div>


                    <div class="stat-number">

                        <?php echo $totalDownloads; ?>+

                    </div>


                    <div class="stat-title">

                        Downloads

                    </div>


                    <div class="stat-description">

                        Files accessed securely

                    </div>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     SECURITY TRUST
===================================================== -->

<section class="nv-security">

    <div class="container">

        <div class="row align-items-center g-5">


            <!-- LEFT -->

            <div class="col-lg-6">

                <span class="security-label">

                    <i class="bi bi-shield-check"></i>

                    BUILT WITH SECURITY IN MIND

                </span>


                <h2 class="security-title">

                    Your Files Deserve

                    <span>
                        Better Protection.
                    </span>

                </h2>


                <p class="security-description">

                    NexaVault provides multiple layers of protection
                    to help you keep your files secure while sharing
                    them with trusted users.

                </p>


                <div class="security-points">


                    <div class="security-point">

                        <div class="security-point-icon">

                            <i class="bi bi-lock-fill"></i>

                        </div>


                        <div>

                            <h5>
                                Password Protected Sharing
                            </h5>

                            <p>

                                Shared files can be protected with
                                a separate secure password.

                            </p>

                        </div>

                    </div>



                    <div class="security-point">

                        <div class="security-point-icon">

                            <i class="bi bi-person-check-fill"></i>

                        </div>


                        <div>

                            <h5>
                                Secure Authentication
                            </h5>

                            <p>

                                User accounts are protected through
                                authenticated login access.

                            </p>

                        </div>

                    </div>



                    <div class="security-point">

                        <div class="security-point-icon">

                            <i class="bi bi-clock-history"></i>

                        </div>


                        <div>

                            <h5>
                                Expiry & Download Control
                            </h5>

                            <p>

                                Control how long links remain active
                                and how many times files can be downloaded.

                            </p>

                        </div>

                    </div>



                    <div class="security-point">

                        <div class="security-point-icon">

                            <i class="bi bi-link-45deg"></i>

                        </div>


                        <div>

                            <h5>
                                Link Revocation
                            </h5>

                            <p>

                                Stop an active sharing link whenever
                                you no longer want it accessible.

                            </p>

                        </div>

                    </div>


                </div>

            </div>



            <!-- RIGHT -->

            <div class="col-lg-6">

                <div class="security-visual">

                    <div class="security-glow"></div>


                    <div class="security-main-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>


                    <h4>
                        NexaVault Security
                    </h4>


                    <p>
                        Multi-layer file protection
                    </p>


                    <div class="security-status">

                        <span class="status-dot"></span>

                        System Protected

                    </div>


                    <div class="security-mini-cards">


                        <div>

                            <i class="bi bi-key-fill"></i>

                            <span>
                                Password
                            </span>

                        </div>


                        <div>

                            <i class="bi bi-hourglass-split"></i>

                            <span>
                                Expiry
                            </span>

                        </div>


                        <div>

                            <i class="bi bi-download"></i>

                            <span>
                                Limits
                            </span>

                        </div>


                        <div>

                            <i class="bi bi-qr-code"></i>

                            <span>
                                QR Share
                            </span>

                        </div>


                    </div>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     LIVE SECURITY
===================================================== -->

<section class="nv-live-security">

    <div class="container">

        <div class="row align-items-center g-5">


            <!-- LEFT -->

            <div class="col-lg-6">

                <span class="live-security-label">

                    <i class="bi bi-broadcast-pin"></i>

                    LIVE SECURITY MONITOR

                </span>


                <h2 class="live-security-title">

                    NexaVault is

                    <span>
                        Always Watching.
                    </span>

                </h2>


                <p class="live-security-description">

                    NexaVault continuously monitors important security
                    controls to help keep your digital files protected.

                </p>


                <div class="live-status-main">

                    <span class="live-pulse"></span>


                    <div>

                        <strong>
                            System Protected
                        </strong>

                        <small>
                            Security monitoring active
                        </small>

                    </div>

                </div>

            </div>



            <!-- RIGHT -->

            <div class="col-lg-6">

                <div class="live-security-card">


                    <div class="live-card-header">

                        <div>

                            <span class="live-card-label">
                                SECURITY STATUS
                            </span>

                            <h4>
                                Protection Monitor
                            </h4>

                        </div>


                        <span class="monitor-dot">
                            LIVE
                        </span>

                    </div>



                    <div class="security-check-list">


                        <div class="security-check-item">

                            <div class="security-check-icon">

                                <i class="bi bi-person-check-fill"></i>

                            </div>


                            <div class="security-check-content">

                                <strong>
                                    Authentication
                                </strong>

                                <small>
                                    Account access protected
                                </small>

                            </div>


                            <span class="check-state">

                                <i class="bi bi-check-circle-fill"></i>

                            </span>

                        </div>



                        <div class="security-check-item">

                            <div class="security-check-icon">

                                <i class="bi bi-file-lock-fill"></i>

                            </div>


                            <div class="security-check-content">

                                <strong>
                                    File Protection
                                </strong>

                                <small>
                                    Private storage enabled
                                </small>

                            </div>


                            <span class="check-state">

                                <i class="bi bi-check-circle-fill"></i>

                            </span>

                        </div>



                        <div class="security-check-item">

                            <div class="security-check-icon">

                                <i class="bi bi-link-45deg"></i>

                            </div>


                            <div class="security-check-content">

                                <strong>
                                    Secure Sharing
                                </strong>

                                <small>
                                    Protected share links active
                                </small>

                            </div>


                            <span class="check-state">

                                <i class="bi bi-check-circle-fill"></i>

                            </span>

                        </div>



                        <div class="security-check-item">

                            <div class="security-check-icon">

                                <i class="bi bi-clock-history"></i>

                            </div>


                            <div class="security-check-content">

                                <strong>
                                    Expiry Control
                                </strong>

                                <small>
                                    Share link expiry available
                                </small>

                            </div>


                            <span class="check-state">

                                <i class="bi bi-check-circle-fill"></i>

                            </span>

                        </div>



                        <div class="security-check-item">

                            <div class="security-check-icon">

                                <i class="bi bi-shield-check"></i>

                            </div>


                            <div class="security-check-content">

                                <strong>
                                    Duplicate Detection
                                </strong>

                                <small>
                                    SHA-256 file verification active
                                </small>

                            </div>


                            <span class="check-state">

                                <i class="bi bi-check-circle-fill"></i>

                            </span>

                        </div>


                    </div>



                    <div class="live-monitor-footer">

                        <span>

                            <i class="bi bi-shield-fill-check"></i>

                            All security controls operational

                        </span>


                        <span class="footer-pulse"></span>

                    </div>


                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     LIVE SECURITY SCANNER
===================================================== -->

<section class="nv-security-scanner">

    <div class="container">

        <div class="row align-items-center g-5">


            <!-- LEFT -->

            <div class="col-lg-5">

                <span class="scanner-label">

                    <i class="bi bi-radar"></i>

                    LIVE SECURITY SCAN

                </span>


                <h2 class="scanner-title">

                    Security is

                    <span>
                        Always Active.
                    </span>

                </h2>


                <p class="scanner-description">

                    NexaVault continuously demonstrates the security
                    controls protecting your files, sharing links
                    and account activity.

                </p>


                <div class="scanner-status-box">

                    <span class="scanner-live-dot"></span>


                    <div>

                        <strong id="scannerStatus">
                            Security scan running
                        </strong>

                        <small id="scannerSubStatus">
                            Checking protection controls...
                        </small>

                    </div>

                </div>

            </div>



            <!-- RIGHT -->

            <div class="col-lg-7">

                <div class="scanner-card">


                    <div class="scanner-card-header">

                        <div>

                            <span>
                                SECURITY ENGINE
                            </span>

                            <h4>
                                NexaVault Protection Monitor
                            </h4>

                        </div>


                        <div class="scanner-live-badge">

                            <span></span>

                            LIVE

                        </div>

                    </div>



                    <!-- SCAN BAR -->

                    <div class="scanner-progress-area">

                        <div class="scanner-progress-head">

                            <small>
                                Protection Scan
                            </small>

                            <strong id="scanPercent">
                                0%
                            </strong>

                        </div>


                        <div class="scanner-progress">

                            <div
                                class="scanner-progress-fill"
                                id="scannerProgressFill">
                            </div>

                        </div>

                    </div>



                    <!-- CHECKS -->

                    <div class="scanner-checks">


                        <div
                            class="scanner-check"
                            data-step="0">

                            <div class="scanner-check-icon">

                                <i class="bi bi-person-check-fill"></i>

                            </div>


                            <div class="scanner-check-content">

                                <strong>
                                    Authentication
                                </strong>

                                <small>
                                    Account access protection
                                </small>

                            </div>


                            <span class="scanner-result">
                                WAIT
                            </span>

                        </div>



                        <div
                            class="scanner-check"
                            data-step="1">

                            <div class="scanner-check-icon">

                                <i class="bi bi-file-lock-fill"></i>

                            </div>


                            <div class="scanner-check-content">

                                <strong>
                                    File Protection
                                </strong>

                                <small>
                                    Private storage controls
                                </small>

                            </div>


                            <span class="scanner-result">
                                WAIT
                            </span>

                        </div>



                        <div
                            class="scanner-check"
                            data-step="2">

                            <div class="scanner-check-icon">

                                <i class="bi bi-link-45deg"></i>

                            </div>


                            <div class="scanner-check-content">

                                <strong>
                                    Secure Sharing
                                </strong>

                                <small>
                                    Protected share links
                                </small>

                            </div>


                            <span class="scanner-result">
                                WAIT
                            </span>

                        </div>



                        <div
                            class="scanner-check"
                            data-step="3">

                            <div class="scanner-check-icon">

                                <i class="bi bi-clock-history"></i>

                            </div>


                            <div class="scanner-check-content">

                                <strong>
                                    Expiry Control
                                </strong>

                                <small>
                                    Link expiration monitoring
                                </small>

                            </div>


                            <span class="scanner-result">
                                WAIT
                            </span>

                        </div>



                        <div
                            class="scanner-check"
                            data-step="4">

                            <div class="scanner-check-icon">

                                <i class="bi bi-shield-check"></i>

                            </div>


                            <div class="scanner-check-content">

                                <strong>
                                    Duplicate Detection
                                </strong>

                                <small>
                                    SHA-256 file verification
                                </small>

                            </div>


                            <span class="scanner-result">
                                WAIT
                            </span>

                        </div>


                    </div>



                    <!-- FOOTER -->

                    <div class="scanner-footer">

                        <span>

                            <i class="bi bi-shield-fill-check"></i>

                            All protection controls operational

                        </span>


                        <span id="scannerFooterStatus">
                            Scanning...
                        </span>

                    </div>


                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     FEATURES
===================================================== -->

<section
    id="features"
    class="nv-features">

    <div class="container">


        <div class="section-heading">

            <span>
                WHY NEXAVAULT
            </span>


            <h2>

                Everything You Need To

                <strong>
                    Share Securely.
                </strong>

            </h2>


            <p>

                Powerful security and sharing features designed
                to keep your important files under your control.

            </p>

        </div>



        <div class="row g-4">


            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon blue">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>


                    <h4>
                        Password Protection
                    </h4>


                    <p>

                        Protect shared files with a secure password
                        so only authorized users can access them.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon purple">

                        <i class="bi bi-clock-history"></i>

                    </div>


                    <h4>
                        Expiry Links
                    </h4>


                    <p>

                        Set an expiry date for your sharing link
                        and automatically limit its availability.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon green">

                        <i class="bi bi-download"></i>

                    </div>


                    <h4>
                        Download Limits
                    </h4>


                    <p>

                        Control how many times a shared file can
                        be downloaded.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon orange">

                        <i class="bi bi-qr-code"></i>

                    </div>


                    <h4>
                        QR Code Sharing
                    </h4>


                    <p>

                        Generate a QR code and share your protected
                        file link instantly.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon pink">

                        <i class="bi bi-link-45deg"></i>

                    </div>


                    <h4>
                        Revoke Sharing
                    </h4>


                    <p>

                        Instantly revoke an active sharing link whenever
                        you no longer want it accessible.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon cyan">

                        <i class="bi bi-eye-fill"></i>

                    </div>


                    <h4>
                        File Preview
                    </h4>


                    <p>

                        Preview supported files before downloading them
                        from your secure storage.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon yellow">

                        <i class="bi bi-star-fill"></i>

                    </div>


                    <h4>
                        Favorites
                    </h4>


                    <p>

                        Keep frequently used files easily accessible
                        with the favorites feature.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon red">

                        <i class="bi bi-trash-fill"></i>

                    </div>


                    <h4>
                        Recycle Bin
                    </h4>


                    <p>

                        Recover deleted files instead of permanently
                        losing important data.

                    </p>

                </div>

            </div>



            <div class="col-lg-4 col-md-6">

                <div class="nv-feature-card">

                    <div class="feature-icon violet">

                        <i class="bi bi-activity"></i>

                    </div>


                    <h4>
                        Activity Tracking
                    </h4>


                    <p>

                        Monitor important file activities and keep
                        better control over your shared content.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     HOW IT WORKS
===================================================== -->

<section
    id="how-it-works"
    class="nv-how">

    <div class="container">


        <div class="section-heading">

            <span>
                HOW IT WORKS
            </span>


            <h2>

                Simple Steps To

                <strong>
                    Share Securely.
                </strong>

            </h2>


            <p>
                Upload your file, protect it and share it securely.
            </p>

        </div>



        <div class="row g-4">


            <div class="col-lg-3 col-md-6">

                <div class="step-card">

                    <div class="step-number">
                        01
                    </div>


                    <div class="step-icon">

                        <i class="bi bi-cloud-arrow-up-fill"></i>

                    </div>


                    <h4>
                        Upload
                    </h4>


                    <p>

                        Upload your important file securely
                        to your NexaVault account.

                    </p>

                </div>

            </div>



            <div class="col-lg-3 col-md-6">

                <div class="step-card">

                    <div class="step-number">
                        02
                    </div>


                    <div class="step-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>


                    <h4>
                        Protect
                    </h4>


                    <p>

                        Add password protection, expiry
                        and download controls.

                    </p>

                </div>

            </div>



            <div class="col-lg-3 col-md-6">

                <div class="step-card">

                    <div class="step-number">
                        03
                    </div>


                    <div class="step-icon">

                        <i class="bi bi-share-fill"></i>

                    </div>


                    <h4>
                        Share
                    </h4>


                    <p>

                        Generate a secure link or QR code
                        and share it with others.

                    </p>

                </div>

            </div>



            <div class="col-lg-3 col-md-6">

                <div class="step-card">

                    <div class="step-number">
                        04
                    </div>


                    <div class="step-icon">

                        <i class="bi bi-check-circle-fill"></i>

                    </div>


                    <h4>
                        Stay Secure
                    </h4>


                    <p>

                        Control access and revoke the link
                        whenever required.

                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     ABOUT
===================================================== -->

<section
    id="about"
    class="nv-about">

    <div class="container">


        <div class="row align-items-center g-5">


            <div class="col-lg-6">


                <div class="about-visual">


                    <div class="about-icon">

                        <i class="bi bi-shield-lock-fill"></i>

                    </div>


                    <div class="about-orbit orbit-one"></div>

                    <div class="about-orbit orbit-two"></div>


                    <div class="about-text">

                        <strong>
                            NexaVault
                        </strong>

                        <span>
                            Secure • Share • Trust
                        </span>

                    </div>

                </div>

            </div>



            <div class="col-lg-6">


                <span class="about-label">
                    ABOUT NEXAVAULT
                </span>


                <h2>

                    Your Files.

                    <span>
                        Your Privacy.
                    </span>

                </h2>


                <p>

                    NexaVault is a secure file sharing platform developed
                    to provide users with a simple and controlled way to
                    store, protect and share digital files.

                </p>


                <p>

                    The platform combines password protection, secure
                    sharing links, expiry controls, download limits,
                    QR sharing and link revocation into one system.

                </p>



                <div class="about-points">


                    <div>

                        <i class="bi bi-check-circle-fill"></i>

                        Secure user authentication

                    </div>


                    <div>

                        <i class="bi bi-check-circle-fill"></i>

                        Controlled file sharing

                    </div>


                    <div>

                        <i class="bi bi-check-circle-fill"></i>

                        Password protected access

                    </div>


                    <div>

                        <i class="bi bi-check-circle-fill"></i>

                        Link expiry and revoke controls

                    </div>


                </div>


            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     CONTACT
===================================================== -->

<section
    id="contact"
    class="nv-contact">

    <div class="container">


        <div class="section-heading">

            <span>
                CONTACT
            </span>


            <h2>

                Need Help With

                <strong>
                    NexaVault?
                </strong>

            </h2>


            <p>
                We're here to help you with secure file sharing.
            </p>

        </div>



        <div class="row g-4">


            <div class="col-lg-4">

                <div class="contact-card">

                    <i class="bi bi-envelope-fill"></i>

                    <h4>
                        Email
                    </h4>


                    <p>
                        support@nexavault.com
                    </p>

                </div>

            </div>



            <div class="col-lg-4">

                <div class="contact-card">

                    <i class="bi bi-telephone-fill"></i>

                    <h4>
                        Phone
                    </h4>


                    <p>
                        +91 98765 43210
                    </p>

                </div>

            </div>



            <div class="col-lg-4">

                <div class="contact-card">

                    <i class="bi bi-geo-alt-fill"></i>

                    <h4>
                        Location
                    </h4>


                    <p>
                        Ratnagiri, Maharashtra, India
                    </p>

                </div>

            </div>


        </div>

    </div>

</section>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="nv-footer">

    <div class="container">


        <div class="footer-brand">


            <img
                src="assets/images/logo.png"
                alt="NexaVault">


            <div>

                <strong>
                    Nexa<span>Vault</span>
                </strong>


                <p>
                    Secure • Share • Trust
                </p>


                <p>
                    Developed by Pankaj Parkar
                </p>

            </div>

        </div>


        <hr>


        <div class="d-flex justify-content-between flex-wrap gap-3">


            <p class="mb-0">

                © 2026 NexaVault.
                All rights reserved.

            </p>


            <p class="mb-0">

                Secure File Sharing Platform

            </p>


        </div>

    </div>

</footer>



<!-- =====================================================
     BACK TO TOP
===================================================== -->

<a
    href="#home"
    class="back-top">

    <i class="bi bi-arrow-up"></i>

</a>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>



<!-- =====================================================
     THEME TOGGLE
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const toggle =
            document.getElementById("themeToggle");


        if (toggle) {

            toggle.addEventListener(
                "click",
                function () {

                    document.body.classList.toggle(
                        "dark-mode"
                    );


                    if (
                        document.body.classList.contains(
                            "dark-mode"
                        )
                    ) {

                        localStorage.setItem(
                            "theme",
                            "dark"
                        );

                        toggle.innerHTML = "☀️";

                    } else {

                        localStorage.setItem(
                            "theme",
                            "light"
                        );

                        toggle.innerHTML = "🌙";

                    }

                }
            );


            if (
                localStorage.getItem("theme")
                === "dark"
            ) {

                document.body.classList.add(
                    "dark-mode"
                );

                toggle.innerHTML = "☀️";

            }

        }

    }
);

</script>



<!-- =====================================================
     LIVE SECURITY SCANNER
===================================================== -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const checks =
            document.querySelectorAll(
                ".scanner-check"
            );


        const progress =
            document.getElementById(
                "scannerProgressFill"
            );


        const percent =
            document.getElementById(
                "scanPercent"
            );


        const status =
            document.getElementById(
                "scannerStatus"
            );


        const subStatus =
            document.getElementById(
                "scannerSubStatus"
            );


        const footer =
            document.getElementById(
                "scannerFooterStatus"
            );


        if (
            !checks.length ||
            !progress ||
            !percent
        ) {

            return;

        }


        const messages = [

            "Verifying account authentication...",

            "Checking file protection...",

            "Checking secure sharing...",

            "Checking expiry controls...",

            "Running duplicate detection..."

        ];


        let current = 0;


        function runScan() {


            current = 0;


            checks.forEach(
                function (item) {

                    item.classList.remove(
                        "active"
                    );

                    item.classList.remove(
                        "done"
                    );


                    const result =
                        item.querySelector(
                            ".scanner-result"
                        );


                    if (result) {

                        result.textContent =
                            "WAIT";

                    }

                }
            );


            progress.style.width =
                "0%";


            percent.textContent =
                "0%";


            status.textContent =
                "Security scan running";


            subStatus.textContent =
                "Checking protection controls...";


            footer.textContent =
                "Scanning...";


            function nextCheck() {


                if (
                    current >= checks.length
                ) {


                    progress.style.width =
                        "100%";


                    percent.textContent =
                        "100%";


                    status.textContent =
                        "All Security Controls Protected";


                    subStatus.textContent =
                        "NexaVault security monitoring active";


                    footer.textContent =
                        "Scan complete ✓";


                    setTimeout(
                        function () {

                            runScan();

                        },
                        3500
                    );


                    return;

                }


                const item =
                    checks[current];


                const result =
                    item.querySelector(
                        ".scanner-result"
                    );


                item.classList.add(
                    "active"
                );


                subStatus.textContent =
                    messages[current];


                if (result) {

                    result.textContent =
                        "SCANNING";

                }


                const startPercent =
                    current *
                    (100 / checks.length);


                const targetPercent =
                    (current + 1) *
                    (100 / checks.length);


                let value =
                    startPercent;


                const interval =
                    setInterval(
                        function () {


                            value += 2;


                            if (
                                value >=
                                targetPercent
                            ) {


                                value =
                                    targetPercent;


                                clearInterval(
                                    interval
                                );


                                item.classList.remove(
                                    "active"
                                );


                                item.classList.add(
                                    "done"
                                );


                                if (result) {

                                    result.textContent =
                                        "PASS";

                                }


                                current++;


                                setTimeout(
                                    nextCheck,
                                    400
                                );

                            }


                            progress.style.width =
                                value + "%";


                            percent.textContent =
                                Math.round(value) +
                                "%";


                        },
                        60
                    );

            }


            nextCheck();

        }


        runScan();

    }
);

</script>


</body>

</html>