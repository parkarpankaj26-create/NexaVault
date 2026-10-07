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

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}

$message = "";
$message_type = "";


/* =====================================================
   HELPER - DEVICE NAME
===================================================== */

function detectDeviceName($userAgent)
{
    $ua = strtolower($userAgent);

    if (
        strpos($ua, 'android') !== false &&
        strpos($ua, 'mobile') !== false
    ) {
        return "Android Phone";
    }

    if (strpos($ua, 'android') !== false) {
        return "Android Tablet";
    }

    if (strpos($ua, 'iphone') !== false) {
        return "iPhone";
    }

    if (strpos($ua, 'ipad') !== false) {
        return "iPad";
    }

    if (strpos($ua, 'windows') !== false) {
        return "Windows PC";
    }

    if (strpos($ua, 'macintosh') !== false) {
        return "Mac";
    }

    if (strpos($ua, 'linux') !== false) {
        return "Linux Device";
    }

    return "Unknown Device";
}


/* =====================================================
   LOGIN
===================================================== */

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /* =================================================
       BASIC VALIDATION
    ================================================= */

    if ($email === '' || $password === '') {

        $message = "Email and password are required.";
        $message_type = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $message_type = "danger";

    } else {

        /* =================================================
           GET USER
        ================================================= */

        $stmt = mysqli_prepare(
            $conn,
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        if (!$stmt) {

            $message = "Unable to process login.";
            $message_type = "danger";

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            /* =================================================
               EMAIL NOT FOUND
            ================================================= */

            if (!$result || mysqli_num_rows($result) === 0) {

                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                $deviceName = detectDeviceName($userAgent);

                $historyStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO login_history
                    (
                        user_id,
                        email,
                        login_status,
                        ip_address,
                        user_agent,
                        device_name
                    )
                    VALUES
                    (
                        0,
                        ?,
                        'failed',
                        ?,
                        ?,
                        ?
                    )"
                );

                if ($historyStmt) {
                    mysqli_stmt_bind_param(
                        $historyStmt,
                        "ssss",
                        $email,
                        $ipAddress,
                        $userAgent,
                        $deviceName
                    );
                    mysqli_stmt_execute($historyStmt);
                    mysqli_stmt_close($historyStmt);
                }

                mysqli_stmt_close($stmt);

                $message = "Email not found.";
                $message_type = "danger";

            } else {

                $user = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);

                /* =================================================
                   PASSWORD CHECK
                ================================================= */

                if (
                    !isset($user['password']) ||
                    !password_verify($password, $user['password'])
                ) {

                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    $deviceName = detectDeviceName($userAgent);
                    $userId = (int)$user['id'];

                    /* Failed login history */
                    $historyStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO login_history
                        (
                            user_id,
                            email,
                            login_status,
                            ip_address,
                            user_agent,
                            device_name
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            'failed',
                            ?,
                            ?,
                            ?
                        )"
                    );

                    if ($historyStmt) {
                        mysqli_stmt_bind_param(
                            $historyStmt,
                            "issss",
                            $userId,
                            $email,
                            $ipAddress,
                            $userAgent,
                            $deviceName
                        );
                        mysqli_stmt_execute($historyStmt);
                        mysqli_stmt_close($historyStmt);
                    }

                    /* Failed login security alert */
                    $failedAlertTitle = "Failed Login Attempt";
                    $failedAlertMessage = "An incorrect password was entered for your account.";

                    $alertStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO security_alerts
                        (
                            user_id,
                            alert_type,
                            severity,
                            title,
                            message,
                            is_read
                        )
                        VALUES
                        (
                            ?,
                            'failed_login',
                            'medium',
                            ?,
                            ?,
                            0
                        )"
                    );

                    if ($alertStmt) {
                        mysqli_stmt_bind_param(
                            $alertStmt,
                            "iss",
                            $userId,
                            $failedAlertTitle,
                            $failedAlertMessage
                        );
                        mysqli_stmt_execute($alertStmt);
                        mysqli_stmt_close($alertStmt);
                    }

                    /* Failed login count in last 24 hours */
                    $recentFailedQuery = mysqli_prepare(
                        $conn,
                        "SELECT COUNT(*) AS total
                         FROM login_history
                         WHERE user_id = ?
                         AND login_status = 'failed'
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                    );

                    $recentFailedCount = 0;

                    if ($recentFailedQuery) {
                        mysqli_stmt_bind_param(
                            $recentFailedQuery,
                            "i",
                            $userId
                        );
                        mysqli_stmt_execute($recentFailedQuery);
                        $recentResult = mysqli_stmt_get_result($recentFailedQuery);

                        if ($recentResult) {
                            $recentRow = mysqli_fetch_assoc($recentResult);
                            $recentFailedCount = (int)($recentRow['total'] ?? 0);
                        }

                        mysqli_stmt_close($recentFailedQuery);
                    }

                    /* High-risk alert after 3 failed attempts */
                    if ($recentFailedCount === 3) {

                        $highAlertTitle = "Multiple Failed Login Attempts";
                        $highAlertMessage = "3 failed login attempts were detected in the last 24 hours.";

                        $highAlertStmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO security_alerts
                            (
                                user_id,
                                alert_type,
                                severity,
                                title,
                                message,
                                is_read
                            )
                            VALUES
                            (
                                ?,
                                'multiple_failed_logins',
                                'high',
                                ?,
                                ?,
                                0
                            )"
                        );

                        if ($highAlertStmt) {
                            mysqli_stmt_bind_param(
                                $highAlertStmt,
                                "iss",
                                $userId,
                                $highAlertTitle,
                                $highAlertMessage
                            );
                            mysqli_stmt_execute($highAlertStmt);
                            mysqli_stmt_close($highAlertStmt);
                        }
                    }

                    $message = "Incorrect Password.";
                    $message_type = "danger";

                } else {

                    /* =================================================
                       ACCOUNT STATUS
                    ================================================= */

                    if (
                        isset($user['status']) &&
                        $user['status'] === "blocked"
                    ) {

                        $message = "Your account has been blocked by Admin.";
                        $message_type = "danger";

                    } else {

                        /* =================================================
                           DEVICE / NETWORK DETAILS
                        ================================================= */

                        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                        $deviceName = detectDeviceName($userAgent);

                        /* =================================================
                           INVALIDATE OLD OTPs
                        ================================================= */

                        $invalidateStmt = mysqli_prepare(
                            $conn,
                            "UPDATE mfa_codes
                             SET is_used = 1
                             WHERE user_id = ?
                             AND is_used = 0"
                        );

                        if ($invalidateStmt) {
                            $userId = (int)$user['id'];
                            mysqli_stmt_bind_param(
                                $invalidateStmt,
                                "i",
                                $userId
                            );
                            mysqli_stmt_execute($invalidateStmt);
                            mysqli_stmt_close($invalidateStmt);
                        }

                        /* =================================================
                           GENERATE NEW OTP
                        ================================================= */

                        $otpCode = str_pad(
                            (string)random_int(0, 999999),
                            6,
                            '0',
                            STR_PAD_LEFT
                        );

                        $expiresAt = date(
                            'Y-m-d H:i:s',
                            strtotime('+5 minutes')
                        );

                        /* =================================================
                           SAVE NEW OTP
                        ================================================= */

                        $otpStmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO mfa_codes
                            (
                                user_id,
                                otp_code,
                                expires_at,
                                is_used
                            )
                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                0
                            )"
                        );

                        $otpInsert = false;

                        if ($otpStmt) {
                            $userId = (int)$user['id'];

                            mysqli_stmt_bind_param(
                                $otpStmt,
                                "iss",
                                $userId,
                                $otpCode,
                                $expiresAt
                            );

                            $otpInsert = mysqli_stmt_execute($otpStmt);
                            mysqli_stmt_close($otpStmt);
                        }

                        if (!$otpInsert) {

                            $message = "Unable to create OTP.";
                            $message_type = "danger";

                        } else {

                            /* =================================================
                               PENDING MFA SESSION
                            ================================================= */

                            $pendingSessionToken = bin2hex(random_bytes(64));

                            $_SESSION['mfa_pending_user_id'] = (int)$user['id'];
                            $_SESSION['mfa_pending_email'] = $user['email'];
                            $_SESSION['mfa_pending_name'] = $user['name'];
                            $_SESSION['mfa_pending_role'] = $user['role'];
                            $_SESSION['mfa_pending_session_token'] = $pendingSessionToken;
                            $_SESSION['mfa_pending_ip'] = $ipAddress;
                            $_SESSION['mfa_pending_user_agent'] = $userAgent;
                            $_SESSION['mfa_pending_device'] = $deviceName;

                            header("Location: otp_verify.php");
                            exit();
                        }
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - NexaVault</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: #172033;
            overflow-x: hidden;
            background:
                radial-gradient(circle at 12% 18%, rgba(37, 99, 235, 0.08), transparent 30%),
                radial-gradient(circle at 88% 82%, rgba(99, 102, 241, 0.07), transparent 28%),
                linear-gradient(135deg, #ffffff 0%, #f8fbff 50%, #eef4ff 100%);
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
        }

        .brand-logo {
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            text-decoration: none;
        }

        .navbar-custom .btn-outline-light {
            color: #334155;
            border-color: #cbd5e1;
            background: #ffffff;
        }

        .navbar-custom .btn-outline-light:hover {
            color: #111827;
            background: #f8fafc;
            border-color: #94a3b8;
        }

        .navbar-custom .btn-warning {
            background: linear-gradient(135deg, #3b82f6, #6366f1);
            border: none;
            color: #ffffff;
        }

        .login-wrapper {
            min-height: calc(100vh - 70px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 55px 15px;
        }

        .login-container {
            width: 100%;
            max-width: 1000px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 25px 70px rgba(15, 23, 42, 0.12);
        }

        .security-panel {
            padding: 50px;
            min-height: 560px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background:
                radial-gradient(circle at 15% 20%, rgba(59, 130, 246, 0.10), transparent 32%),
                linear-gradient(145deg, #f8fbff 0%, #eef4ff 100%);
            border-right: 1px solid #e5e7eb;
        }

        .security-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #2563eb;
            margin-bottom: 25px;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.12);
        }

        .security-panel h1 {
            font-size: 42px;
            font-weight: 700;
            color: #111827;
            line-height: 1.1;
        }

        .security-panel h1 span {
            color: #2563eb;
        }

        .security-panel > p {
            color: #64748b !important;
            opacity: 1 !important;
            line-height: 1.7;
        }

        .security-list {
            margin-top: 30px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            color: #475569;
        }

        .security-item i {
            color: #16a34a;
            font-size: 20px;
        }

        .login-panel {
            background: #ffffff;
            color: #212529;
            padding: 50px;
            min-height: 560px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-title {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
        }

        .login-subtitle {
            color: #64748b;
            margin-bottom: 30px;
        }

        .form-label,
        label {
            font-weight: 600;
            color: #334155;
        }

        .form-control {
            height: 50px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.12);
        }

        .input-group .btn-outline-secondary {
            border-color: #cbd5e1;
            background: #ffffff;
            color: #475569;
        }

        .input-group .btn-outline-secondary:hover {
            background: #f8fafc;
            color: #111827;
        }

        .login-btn {
            height: 50px;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
            border: none;
            background: linear-gradient(135deg, #3b82f6, #6366f1);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
        }

        .secure-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #198754;
            background: #ecfdf3;
            border: 1px solid #bbf7d0;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 25px;
        }

        .feature-mini {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 13px;
            color: #64748b;
        }

        .feature-mini span {
            text-align: center;
        }

        .feature-mini i {
            display: block;
            font-size: 20px;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .floating-circle {
            position: fixed;
            border-radius: 50%;
            filter: blur(4px);
            opacity: 0.20;
            pointer-events: none;
            z-index: 0;
        }

        .circle-one {
            width: 180px;
            height: 180px;
            background: rgba(59, 130, 246, 0.18);
            top: 15%;
            left: -70px;
        }

        .circle-two {
            width: 220px;
            height: 220px;
            background: rgba(99, 102, 241, 0.14);
            bottom: 5%;
            right: -80px;
        }

        .login-container,
        .login-wrapper > *,
        .navbar-custom .container {
            position: relative;
            z-index: 1;
        }

        .alert {
            border-radius: 12px;
        }

        @media(max-width: 768px) {

            .security-panel {
                min-height: auto;
                padding: 35px;
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
            }

            .security-panel h1 {
                font-size: 32px;
            }

            .login-panel {
                min-height: auto;
                padding: 35px;
            }

            .feature-mini {
                gap: 10px;
            }

            .login-wrapper {
                padding: 30px 12px;
            }
        }
    </style>
</head>

<body>

<div class="floating-circle circle-one"></div>
<div class="floating-circle circle-two"></div>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a href="index.php" class="brand-logo">
            🔐 NexaVault
        </a>

        <div class="ms-auto">
            <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                <i class="bi bi-house"></i>
                Home
            </a>

            <a href="register.php" class="btn btn-warning btn-sm">
                <i class="bi bi-person-plus"></i>
                Register
            </a>
        </div>
    </div>
</nav>

<div class="login-wrapper">
    <div class="login-container">
        <div class="row g-0">

            <div class="col-lg-6">
                <div class="security-panel">
                    <div class="security-icon">🛡️</div>

                    <h1>
                        Your Files.<br>
                        <span>Your Privacy.</span>
                    </h1>

                    <p class="mt-3">
                        NexaVault provides a secure environment to store, protect and share your important files.
                    </p>

                    <div class="security-list">

                        <div class="security-item">
                            <i class="bi bi-shield-check"></i>
                            <span>Secure account authentication</span>
                        </div>

                        <div class="security-item">
                            <i class="bi bi-lock-fill"></i>
                            <span>Password protected access</span>
                        </div>

                        <div class="security-item">
                            <i class="bi bi-cloud-check"></i>
                            <span>Safe cloud-style file sharing</span>
                        </div>

                        <div class="security-item">
                            <i class="bi bi-person-check-fill"></i>
                            <span>Authorized user access</span>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="login-panel">

                    <div class="secure-status">
                        <i class="bi bi-shield-fill-check"></i>
                        Secure connection established
                    </div>

                    <h2 class="login-title">Welcome Back 👋</h2>

                    <p class="login-subtitle">
                        Login to access your NexaVault dashboard.
                    </p>

                    <?php if ($message !== "") { ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
                            <i class="bi bi-exclamation-circle"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php } ?>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                placeholder="Enter your email"
                                required
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>

                            <div class="input-group">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Enter your password"
                                    required
                                    oninput="checkPasswordStrength()"
                                >

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    onclick="togglePassword()"
                                >
                                    <i id="eyeIcon" class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="password-strength mt-2">
                            <div class="progress" style="height: 6px;">
                                <div
                                    id="passwordStrengthBar"
                                    class="progress-bar"
                                    style="width: 0%;"
                                ></div>
                            </div>

                            <small id="passwordStrengthText" class="text-muted">
                                Enter your password
                            </small>
                        </div>

                        <button
                            type="submit"
                            name="login"
                            class="btn btn-primary w-100 login-btn mt-3"
                        >
                            🔐 Login Securely
                        </button>

                        <div class="mt-2 small text-muted">
                            <div id="lengthCheck">○ Minimum 8 characters</div>
                            <div id="upperCheck">○ At least 1 uppercase letter</div>
                            <div id="numberCheck">○ At least 1 number</div>
                            <div id="specialCheck">○ At least 1 special character</div>
                        </div>

                    </form>

                    <div class="text-center mt-4">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="register.php" class="fw-bold text-decoration-none">
                            Create Account
                        </a>
                    </div>

                    <div class="feature-mini">
                        <span>
                            <i class="bi bi-shield-lock"></i>
                            Secure
                        </span>

                        <span>
                            <i class="bi bi-lightning-charge"></i>
                            Fast
                        </span>

                        <span>
                            <i class="bi bi-person-check"></i>
                            Private
                        </span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassword()
{
    const password = document.getElementById("password");
    const eye = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        eye.classList.remove("bi-eye");
        eye.classList.add("bi-eye-slash");
    } else {
        password.type = "password";
        eye.classList.remove("bi-eye-slash");
        eye.classList.add("bi-eye");
    }
}

function checkPasswordStrength()
{
    const password = document.getElementById("password").value;
    const bar = document.getElementById("passwordStrengthBar");
    const text = document.getElementById("passwordStrengthText");

    const lengthCheck = document.getElementById("lengthCheck");
    const upperCheck = document.getElementById("upperCheck");
    const numberCheck = document.getElementById("numberCheck");
    const specialCheck = document.getElementById("specialCheck");

    let strength = 0;

    if (password.length >= 8) {
        lengthCheck.innerHTML = "✓ Minimum 8 characters";
        lengthCheck.className = "text-success";
        strength++;
    } else {
        lengthCheck.innerHTML = "○ Minimum 8 characters";
        lengthCheck.className = "text-muted";
    }

    if (/[A-Z]/.test(password)) {
        upperCheck.innerHTML = "✓ At least 1 uppercase letter";
        upperCheck.className = "text-success";
        strength++;
    } else {
        upperCheck.innerHTML = "○ At least 1 uppercase letter";
        upperCheck.className = "text-muted";
    }

    if (/[0-9]/.test(password)) {
        numberCheck.innerHTML = "✓ At least 1 number";
        numberCheck.className = "text-success";
        strength++;
    } else {
        numberCheck.innerHTML = "○ At least 1 number";
        numberCheck.className = "text-muted";
    }

    if (/[^A-Za-z0-9]/.test(password)) {
        specialCheck.innerHTML = "✓ At least 1 special character";
        specialCheck.className = "text-success";
        strength++;
    } else {
        specialCheck.innerHTML = "○ At least 1 special character";
        specialCheck.className = "text-muted";
    }

    if (password.length === 0) {
        bar.style.width = "0%";
        text.innerHTML = "Enter your password";
    } else if (strength <= 1) {
        bar.style.width = "25%";
        text.innerHTML = "🔴 Weak Password";
    } else if (strength === 2) {
        bar.style.width = "50%";
        text.innerHTML = "🟡 Medium Password";
    } else if (strength === 3) {
        bar.style.width = "75%";
        text.innerHTML = "🟢 Good Password";
    } else {
        bar.style.width = "100%";
        text.innerHTML = "🟢 Strong Password";
    }
}
</script>

</body>
</html>
