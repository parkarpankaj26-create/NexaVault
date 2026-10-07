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


/* =====================================================
   DEVELOPMENT MFA MODE
   Real email OTP is not enabled yet.
===================================================== */

$DEV_MFA_MODE = true;


/* =====================================================
   CHECK MFA PENDING SESSION
===================================================== */

if (
    !isset($_SESSION['mfa_pending_user_id']) ||
    !isset($_SESSION['mfa_pending_email'])
) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['mfa_pending_user_id'];
$email   = $_SESSION['mfa_pending_email'];


/* =====================================================
   MESSAGES
===================================================== */

$message = "";
$message_type = "";


/* =====================================================
   GET LATEST UNUSED OTP
===================================================== */

$otpStmt = mysqli_prepare(
    $conn,
    "SELECT
        id,
        user_id,
        otp_code,
        expires_at,
        is_used
     FROM mfa_codes
     WHERE user_id = ?
     AND is_used = 0
     ORDER BY id DESC
     LIMIT 1"
);

if (!$otpStmt) {
    die(
        "OTP Database Error: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

mysqli_stmt_bind_param(
    $otpStmt,
    "i",
    $user_id
);

mysqli_stmt_execute($otpStmt);

$otpQuery = mysqli_stmt_get_result($otpStmt);

if (!$otpQuery) {
    mysqli_stmt_close($otpStmt);

    die(
        "OTP Database Error: " .
        htmlspecialchars(mysqli_error($conn))
    );
}

if (mysqli_num_rows($otpQuery) === 0) {
    mysqli_stmt_close($otpStmt);

    die(
        "No OTP found. Please login again."
    );
}

$otpRow = mysqli_fetch_assoc($otpQuery);

mysqli_stmt_close($otpStmt);

$otpId   = (int)$otpRow['id'];
$otpCode = (string)$otpRow['otp_code'];


/* =====================================================
   VERIFY OTP
===================================================== */

if (isset($_POST['verify_otp'])) {

    $enteredOtp = trim($_POST['otp'] ?? '');

    /* =================================================
       OTP FORMAT
    ================================================= */

    if (
        !preg_match(
            '/^[0-9]{6}$/',
            $enteredOtp
        )
    ) {

        $message = "Enter a valid 6-digit OTP.";
        $message_type = "danger";

    } elseif (
        !hash_equals(
            $otpCode,
            $enteredOtp
        )
    ) {

        $message = "Invalid OTP. Please try again.";
        $message_type = "danger";

    } else {

        /* =================================================
           OTP EXPIRY CHECK
           Development mode keeps current project behavior.
        ================================================= */

        if (!$DEV_MFA_MODE) {

            $expiresTimestamp =
                strtotime($otpRow['expires_at']);

            if (
                $expiresTimestamp === false ||
                $expiresTimestamp < time()
            ) {

                $message =
                    "OTP has expired. Please login again.";

                $message_type = "danger";
            }
        }


        if ($message === "") {

            /* =================================================
               MARK OTP USED
            ================================================= */

            $usedStmt = mysqli_prepare(
                $conn,
                "UPDATE mfa_codes
                 SET is_used = 1
                 WHERE id = ?
                 AND is_used = 0"
            );

            $usedQuery = false;

            if ($usedStmt) {

                mysqli_stmt_bind_param(
                    $usedStmt,
                    "i",
                    $otpId
                );

                $usedQuery =
                    mysqli_stmt_execute(
                        $usedStmt
                    );

                mysqli_stmt_close($usedStmt);
            }

            if (!$usedQuery) {

                die(
                    "Unable to update OTP: " .
                    htmlspecialchars(mysqli_error($conn))
                );
            }


            /* =================================================
               PENDING LOGIN DETAILS
            ================================================= */

            $ipAddress =
                $_SESSION['mfa_pending_ip']
                ?? 'Unknown';

            $userAgent =
                $_SESSION['mfa_pending_user_agent']
                ?? 'Unknown';

            $deviceName =
                $_SESSION['mfa_pending_device']
                ?? 'Unknown Device';

            $sessionToken =
                $_SESSION['mfa_pending_session_token']
                ?? '';

            $userName =
                $_SESSION['mfa_pending_name']
                ?? 'User';

            $role =
                $_SESSION['mfa_pending_role']
                ?? 'user';


            /* =================================================
               GET PREVIOUS SUCCESSFUL LOGIN
            ================================================= */

            $previousLoginStmt = mysqli_prepare(
                $conn,
                "SELECT
                    device_name,
                    ip_address
                 FROM login_history
                 WHERE user_id = ?
                 AND login_status = 'success'
                 ORDER BY id DESC
                 LIMIT 1"
            );

            $previousLogin = null;

            if ($previousLoginStmt) {

                mysqli_stmt_bind_param(
                    $previousLoginStmt,
                    "i",
                    $user_id
                );

                mysqli_stmt_execute(
                    $previousLoginStmt
                );

                $previousLoginQuery =
                    mysqli_stmt_get_result(
                        $previousLoginStmt
                    );

                if (
                    $previousLoginQuery &&
                    mysqli_num_rows($previousLoginQuery) > 0
                ) {

                    $previousLogin =
                        mysqli_fetch_assoc(
                            $previousLoginQuery
                        );
                }

                mysqli_stmt_close(
                    $previousLoginStmt
                );
            }


            /* =================================================
               NEW DEVICE / NEW IP DETECTION
            ================================================= */

            $newDevice = false;
            $newIp = false;

            if ($previousLogin) {

                $previousDevice =
                    $previousLogin['device_name']
                    ?? '';

                $previousIp =
                    $previousLogin['ip_address']
                    ?? '';


                /* Device check */

                if (
                    $deviceName !== '' &&
                    $previousDevice !== '' &&
                    $deviceName !== $previousDevice
                ) {

                    $newDevice = true;
                }


                /* IP check */

                if (
                    $ipAddress !== '' &&
                    $previousIp !== '' &&
                    $ipAddress !== $previousIp
                ) {

                    $newIp = true;
                }
            }


            /* =================================================
               CREATE SECURITY ALERT
            ================================================= */

            if ($newDevice || $newIp) {

                if (
                    $newDevice &&
                    $newIp
                ) {

                    $alertType =
                        'new_device_and_ip';

                    $severity =
                        'high';

                    $alertTitle =
                        'New Device & IP Detected';

                    $alertMessage =
                        'A successful login was detected from a new device and IP address.';

                } elseif ($newDevice) {

                    $alertType =
                        'new_device';

                    $severity =
                        'medium';

                    $alertTitle =
                        'New Device Detected';

                    $alertMessage =
                        'A successful login was detected from a device not used in your previous login.';

                } else {

                    $alertType =
                        'new_ip';

                    $severity =
                        'medium';

                    $alertTitle =
                        'New IP Address Detected';

                    $alertMessage =
                        'A successful login was detected from a different IP address.';
                }


                /* =================================================
                   PREVENT DUPLICATE ALERTS
                ================================================= */

                $duplicateAlertStmt = mysqli_prepare(
                    $conn,
                    "SELECT id
                     FROM security_alerts
                     WHERE user_id = ?
                     AND alert_type = ?
                     AND created_at >= DATE_SUB(
                         NOW(),
                         INTERVAL 24 HOUR
                     )
                     LIMIT 1"
                );

                $duplicateExists = false;

                if ($duplicateAlertStmt) {

                    mysqli_stmt_bind_param(
                        $duplicateAlertStmt,
                        "is",
                        $user_id,
                        $alertType
                    );

                    mysqli_stmt_execute(
                        $duplicateAlertStmt
                    );

                    $duplicateAlertQuery =
                        mysqli_stmt_get_result(
                            $duplicateAlertStmt
                        );

                    if (
                        $duplicateAlertQuery &&
                        mysqli_num_rows(
                            $duplicateAlertQuery
                        ) > 0
                    ) {

                        $duplicateExists = true;
                    }

                    mysqli_stmt_close(
                        $duplicateAlertStmt
                    );
                }


                if (!$duplicateExists) {

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
                            ?,
                            ?,
                            ?,
                            ?,
                            0
                        )"
                    );

                    if ($alertStmt) {

                        mysqli_stmt_bind_param(
                            $alertStmt,
                            "issss",
                            $user_id,
                            $alertType,
                            $severity,
                            $alertTitle,
                            $alertMessage
                        );

                        mysqli_stmt_execute(
                            $alertStmt
                        );

                        mysqli_stmt_close(
                            $alertStmt
                        );
                    }
                }
            }


            /* =================================================
               CREATE ACTIVE SESSION
            ================================================= */

            $sessionStmt = mysqli_prepare(
                $conn,
                "INSERT INTO user_sessions
                (
                    user_id,
                    session_token,
                    ip_address,
                    user_agent,
                    device_name,
                    created_at,
                    last_activity,
                    is_active
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    NOW(),
                    1
                )"
            );

            $sessionInsert = false;

            if ($sessionStmt) {

                mysqli_stmt_bind_param(
                    $sessionStmt,
                    "issss",
                    $user_id,
                    $sessionToken,
                    $ipAddress,
                    $userAgent,
                    $deviceName
                );

                $sessionInsert =
                    mysqli_stmt_execute(
                        $sessionStmt
                    );

                mysqli_stmt_close(
                    $sessionStmt
                );
            }


            if (!$sessionInsert) {

                die(
                    "Session creation failed: " .
                    htmlspecialchars(mysqli_error($conn))
                );
            }


            /* =================================================
               SUCCESSFUL LOGIN HISTORY
            ================================================= */

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
                    'success',
                    ?,
                    ?,
                    ?
                )"
            );

            if ($historyStmt) {

                mysqli_stmt_bind_param(
                    $historyStmt,
                    "issss",
                    $user_id,
                    $email,
                    $ipAddress,
                    $userAgent,
                    $deviceName
                );

                mysqli_stmt_execute(
                    $historyStmt
                );

                mysqli_stmt_close(
                    $historyStmt
                );
            }


            /* =================================================
               REGENERATE SESSION ID
            ================================================= */

            session_regenerate_id(true);


            /* =================================================
               FINAL LOGIN SESSION
            ================================================= */

            $_SESSION['user'] =
                $userName;

            $_SESSION['user_id'] =
                $user_id;

            $_SESSION['role'] =
                $role;

            $_SESSION['session_token'] =
                $sessionToken;


            /* =================================================
               CLEAR MFA PENDING SESSION
            ================================================= */

            unset(
                $_SESSION['mfa_pending_user_id'],
                $_SESSION['mfa_pending_email'],
                $_SESSION['mfa_pending_name'],
                $_SESSION['mfa_pending_role'],
                $_SESSION['mfa_pending_session_token'],
                $_SESSION['mfa_pending_ip'],
                $_SESSION['mfa_pending_user_agent'],
                $_SESSION['mfa_pending_device']
            );


            /* =================================================
               ACTIVITY LOG
            ================================================= */

            $activityFile =
                __DIR__ . '/includes/activity.php';

            if (file_exists($activityFile)) {

                require_once $activityFile;

                if (
                    function_exists('logActivity')
                ) {

                    logActivity(
                        $conn,
                        $user_id,
                        "MFA OTP verification successful"
                    );
                }
            }


            /* =================================================
               REDIRECT
            ================================================= */

            if ($_SESSION['role'] === 'admin') {

                header(
                    "Location: admin_dashboard.php"
                );

            } else {

                header(
                    "Location: dashboard.php"
                );
            }

            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>NexaVault - OTP Verification</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    >

    <style>

        * {
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            margin: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            color: #172033;

            background:
                radial-gradient(
                    circle at 10% 20%,
                    rgba(59,130,246,0.08),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 90% 80%,
                    rgba(99,102,241,0.07),
                    transparent 35%
                ),
                linear-gradient(
                    135deg,
                    #ffffff 0%,
                    #f8fbff 50%,
                    #eef4ff 100%
                );

            overflow-x: hidden;
        }

        .otp-card {

            width: 100%;

            max-width: 520px;

            padding: 38px;

            border-radius: 24px;

            background: rgba(255,255,255,0.97);

            border:
                1px solid
                #e2e8f0;

            box-shadow:
                0 25px 65px
                rgba(15,23,42,0.12);
        }

        .otp-icon {

            width: 78px;

            height: 78px;

            margin: 0 auto 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 22px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #6366f1
                );

            color: #ffffff;

            font-size: 34px;

            box-shadow:
                0 15px 35px
                rgba(37,99,235,.20);
        }

        .otp-title {

            color: #111827;

            font-size: 28px;

            font-weight: 800;
        }

        .otp-subtitle {

            color: #64748b;

            font-size: 13px;

            line-height: 1.7;
        }

        .email-label {

            color: #2563eb;

            font-size: 12px;

            font-weight: 700;

            word-break: break-word;
        }

        .dev-box {

            margin-top: 20px;

            padding: 17px;

            text-align: center;

            border-radius: 15px;

            background:
                #fffbeb;

            border:
                1px solid
                #fde68a;
        }

        .dev-label {

            color: #b45309;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: 1.4px;
        }

        .dev-otp {

            margin-top: 6px;

            color: #111827;

            font-size: 28px;

            letter-spacing: 7px;

            font-weight: 900;
        }

        .dev-note {

            margin-top: 5px;

            color: #64748b;

            font-size: 10px;
        }

        .otp-input {

            height: 58px;

            text-align: center;

            letter-spacing: 8px;

            font-size: 24px;

            font-weight: 800;

            border-radius: 14px;

            background:
                #f8fafc !important;

            border:
                1px solid
                #cbd5e1 !important;

            color: #111827 !important;
        }

        .otp-input:focus {

            border-color:
                #2563eb !important;

            box-shadow:
                0 0 0 3px
                rgba(37,99,235,.10) !important;
        }

        .btn-primary {

            border: none;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #6366f1
                );

            border-radius: 12px;

            font-weight: 700;

            transition: .3s ease;
        }

        .btn-primary:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 24px
                rgba(37,99,235,.22);
        }

        .back-login {

            color: #2563eb !important;

            font-weight: 600;
        }

        .back-login:hover {

            color: #1d4ed8 !important;
        }

        @media (max-width: 576px) {

            body {
                padding: 15px;
            }

            .otp-card {
                padding: 28px 20px;
                border-radius: 20px;
            }

            .otp-title {
                font-size: 24px;
            }

            .otp-input {
                height: 54px;
                font-size: 21px;
                letter-spacing: 6px;
            }

        }

    </style>

</head>

<body>

<div class="otp-card">

    <div class="text-center">

        <div class="otp-icon">

            <i class="bi bi-shield-lock-fill"></i>

        </div>

        <h1 class="otp-title">

            Verify Your Login

        </h1>

        <p class="otp-subtitle">

            Enter the 6-digit development verification
            code to complete your NexaVault login.

        </p>

        <div class="email-label">

            <?php echo htmlspecialchars($email); ?>

        </div>

    </div>


    <?php if ($message !== "") { ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?> mt-4"
        >

            <i class="bi bi-exclamation-circle-fill"></i>

            <?php echo htmlspecialchars($message); ?>

        </div>

    <?php } ?>


    <div class="dev-box">

        <div class="dev-label">

            DEVELOPMENT / DEMO OTP

        </div>

        <div class="dev-otp">

            <?php echo htmlspecialchars($otpCode); ?>

        </div>

        <div class="dev-note">

            Development mode — real email delivery is not enabled.

        </div>

    </div>


    <form
        method="POST"
        class="mt-4"
    >

        <input
            type="text"
            name="otp"
            class="form-control otp-input"
            maxlength="6"
            minlength="6"
            inputmode="numeric"
            autocomplete="one-time-code"
            pattern="[0-9]{6}"
            placeholder="Enter OTP"
            required
        >

        <button
            type="submit"
            name="verify_otp"
            class="btn btn-primary w-100 mt-4 py-3"
        >

            <i class="bi bi-shield-check"></i>

            Verify OTP

        </button>

    </form>


    <div class="text-center mt-4">

        <a
            href="login.php"
            class="text-decoration-none back-login"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Login

        </a>

    </div>

</div>

</body>
</html>