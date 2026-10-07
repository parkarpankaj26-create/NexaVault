<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   CURRENT SESSION TOKEN
===================================================== */

$currentSessionToken =
    $_SESSION['session_token'] ?? '';

$currentSessionTokenSafe = mysqli_real_escape_string(
    $conn,
    $currentSessionToken
);


/* =====================================================
   UPDATE CURRENT SESSION ACTIVITY
===================================================== */

if ($currentSessionToken !== '') {

    mysqli_query(
        $conn,
        "UPDATE user_sessions
         SET last_activity=NOW()
         WHERE user_id='$user_id'
         AND session_token='$currentSessionTokenSafe'
         AND is_active=1"
    );
}


/* =====================================================
   LOGOUT SELECTED SESSION
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['logout_session'])
) {

    $sessionId = isset($_POST['session_id'])
        ? (int) $_POST['session_id']
        : 0;

    if ($sessionId > 0) {

        mysqli_query(
            $conn,
            "UPDATE user_sessions
             SET is_active=0
             WHERE id='$sessionId'
             AND user_id='$user_id'
             AND session_token!='$currentSessionTokenSafe'"
        );
    }

    header("Location: security_sessions.php");
    exit();
}


/* =====================================================
   LOGOUT ALL OTHER SESSIONS
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['logout_other_sessions'])
) {

    mysqli_query(
        $conn,
        "UPDATE user_sessions
         SET is_active=0
         WHERE user_id='$user_id'
         AND session_token!='$currentSessionTokenSafe'"
    );

    header("Location: security_sessions.php");
    exit();
}


/* =====================================================
   GET ACTIVE SESSIONS
===================================================== */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM user_sessions
     WHERE user_id='$user_id'
     AND is_active=1
     ORDER BY
        CASE
            WHEN session_token='$currentSessionTokenSafe'
            THEN 0
            ELSE 1
        END,
        last_activity DESC"
);

$sessions = [];

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $sessions[] = $row;
    }
}


/* =====================================================
   HELPER FUNCTIONS
===================================================== */

function getBrowserName($userAgent)
{
    $userAgent = strtolower($userAgent);

    if (strpos($userAgent, 'edg') !== false) {
        return 'Microsoft Edge';
    }

    if (strpos($userAgent, 'chrome') !== false) {
        return 'Google Chrome';
    }

    if (strpos($userAgent, 'firefox') !== false) {
        return 'Mozilla Firefox';
    }

    if (strpos($userAgent, 'safari') !== false) {
        return 'Safari';
    }

    if (
        strpos($userAgent, 'opera') !== false ||
        strpos($userAgent, 'opr') !== false
    ) {
        return 'Opera';
    }

    return 'Unknown Browser';
}


function getDeviceIcon($deviceName)
{
    $device = strtolower($deviceName);

    if (
        strpos($device, 'phone') !== false ||
        strpos($device, 'android') !== false ||
        strpos($device, 'mobile') !== false
    ) {
        return 'bi-phone-fill';
    }

    if (
        strpos($device, 'tablet') !== false ||
        strpos($device, 'ipad') !== false
    ) {
        return 'bi-tablet-fill';
    }

    return 'bi-laptop-fill';
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Active Sessions - NexaVault</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<style>

/* =====================================================
   GLOBAL
===================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    min-height: 100vh;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color: #1e293b;

    background:
        radial-gradient(
            circle at 8% 10%,
            rgba(37,99,235,.07),
            transparent 30%
        ),
        radial-gradient(
            circle at 92% 85%,
            rgba(99,102,241,.06),
            transparent 32%
        ),
        #f8fafc;

}


/* =====================================================
   PAGE
===================================================== */

.sessions-page {

    width: 100%;

    max-width: 1180px;

    margin: 0 auto;

    padding:
        105px 25px 70px;

}


/* =====================================================
   MAIN CARD
===================================================== */

.security-card {

    width: 100%;

    background: rgba(255,255,255,.96);

    border:
        1px solid #e2e8f0;

    border-radius: 24px;

    box-shadow:
        0 18px 50px rgba(15,23,42,.08);

    padding: 30px;

}


/* =====================================================
   HEADER
===================================================== */

.page-label {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: #2563eb;

    font-size: 11px;

    font-weight: 800;

    letter-spacing: 1.8px;

}


.page-title {

    margin:
        7px 0 0;

    color: #0f172a;

    font-size: 32px;

    font-weight: 800;

    line-height: 1.2;

}


.page-title i {

    color: #2563eb;

    margin-right: 5px;

}


.page-subtitle {

    margin-top: 8px;

    color: #64748b;

    font-size: 14px;

}


/* =====================================================
   SECURITY NOTICE
===================================================== */

.security-notice {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    padding: 16px 18px;

    margin:
        25px 0 22px;

    border-radius: 14px;

    background:
        #eff6ff;

    border:
        1px solid #dbeafe;

    color: #475569;

    font-size: 13px;

    line-height: 1.6;

}


.security-notice i {

    color: #2563eb;

    font-size: 17px;

    margin-top: 2px;

}


.security-notice strong {

    color: #1e3a8a;

}


/* =====================================================
   SESSION ITEM
===================================================== */

.session-item {

    width: 100%;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 25px;

    padding: 19px 20px;

    margin-top: 12px;

    border-radius: 17px;

    background: #ffffff;

    border:
        1px solid #e2e8f0;

    box-shadow:
        0 5px 18px rgba(15,23,42,.035);

    transition:
        transform .2s ease,
        box-shadow .2s ease,
        border-color .2s ease;

}


.session-item:hover {

    transform: translateY(-2px);

    border-color: #cbd5e1;

    box-shadow:
        0 10px 25px rgba(15,23,42,.07);

}


/* =====================================================
   SESSION LEFT
===================================================== */

.session-left {

    display: flex;

    align-items: center;

    gap: 16px;

    min-width: 0;

    flex: 1;

}


/* =====================================================
   DEVICE ICON
===================================================== */

.device-icon {

    width: 52px;

    height: 52px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 15px;

    background:
        #eff6ff;

    border:
        1px solid #dbeafe;

    color: #2563eb;

    font-size: 22px;

}


/* =====================================================
   SESSION INFORMATION
===================================================== */

.session-browser {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 6px;

    color: #0f172a;

    font-size: 15px;

    font-weight: 750;

}


.session-details {

    margin-top: 5px;

    color: #64748b;

    font-size: 11px;

    line-height: 1.8;

}


.session-details br {

    line-height: 1.5;

}


/* =====================================================
   CURRENT SESSION BADGE
===================================================== */

.current-badge {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 5px 9px;

    border-radius: 8px;

    color: #047857;

    background:
        #ecfdf5;

    border:
        1px solid #a7f3d0;

    font-size: 9px;

    font-weight: 800;

    vertical-align: middle;

}


.current-badge i {

    font-size: 9px;

}


/* =====================================================
   ACTIVE STATUS
===================================================== */

.session-active {

    display: inline-flex;

    align-items: center;

    gap: 5px;

    padding: 8px 12px;

    border-radius: 9px;

    color: #047857;

    background:
        #ecfdf5;

    border:
        1px solid #a7f3d0;

    font-size: 10px;

    font-weight: 800;

}


/* =====================================================
   BUTTONS
===================================================== */

.btn-primary {

    border: none;

    border-radius: 10px;

    padding:
        9px 15px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #4f46e5
        );

    box-shadow:
        0 5px 14px rgba(37,99,235,.16);

}


.btn-primary:hover {

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4338ca
        );

}


.btn-danger {

    border: none;

    border-radius: 10px;

    padding:
        9px 15px;

    background:
        linear-gradient(
            135deg,
            #dc2626,
            #ef4444
        );

    box-shadow:
        0 5px 14px rgba(220,38,38,.14);

}


.btn-danger:hover {

    background:
        linear-gradient(
            135deg,
            #b91c1c,
            #dc2626
        );

}


.btn-outline-light {

    border:
        1px solid #cbd5e1;

    color: #334155;

    background: #ffffff;

    border-radius: 10px;

    padding:
        9px 15px;

}


.btn-outline-light:hover {

    background: #f8fafc;

    border-color: #94a3b8;

    color: #0f172a;

}


/* =====================================================
   LOGOUT BUTTON INSIDE SESSION
===================================================== */

.session-item .btn-outline-light {

    white-space: nowrap;

    font-size: 12px;

    padding:
        8px 13px;

}


.session-item form {

    margin: 0;

}


/* =====================================================
   EMPTY STATE
===================================================== */

.empty-session {

    padding:
        75px 20px;

    text-align: center;

    color: #64748b;

}


.empty-session i {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 75px;

    height: 75px;

    margin:
        0 auto 18px;

    border-radius: 20px;

    background: #f1f5f9;

    color: #94a3b8;

    font-size: 35px;

}


.empty-session h5 {

    color: #0f172a !important;

    font-weight: 700;

}


.empty-session p {

    color: #64748b;

    font-size: 13px;

}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

    .sessions-page {

        padding:
            95px 18px 50px;

    }

    .security-card {

        padding: 24px;

    }

}


@media (max-width: 768px) {

    .sessions-page {

        padding:
            90px 14px 45px;

    }

    .security-card {

        padding: 20px;

        border-radius: 20px;

    }

    .page-title {

        font-size: 27px;

    }

    .page-subtitle {

        font-size: 13px;

    }

    .security-card > .d-flex {

        flex-direction: column;

        align-items: stretch !important;

    }

    .security-card > .d-flex > .d-flex {

        width: 100%;

        flex-direction: column;

    }

    .security-card > .d-flex > .d-flex form,
    .security-card > .d-flex > .d-flex a {

        width: 100%;

    }

    .security-card > .d-flex > .d-flex button,
    .security-card > .d-flex > .d-flex a {

        width: 100%;

    }

    .session-item {

        align-items: flex-start;

        flex-direction: column;

        gap: 16px;

        padding: 17px;

    }

    .session-left {

        width: 100%;

    }

    .session-item > div:last-child {

        width: 100%;

    }

    .session-item > div:last-child form,
    .session-item > div:last-child button {

        width: 100%;

    }

}


@media (max-width: 480px) {

    .page-title {

        font-size: 24px;

    }

    .device-icon {

        width: 46px;

        height: 46px;

        font-size: 19px;

    }

    .session-browser {

        font-size: 13px;

    }

    .session-details {

        font-size: 10px;

    }

    .security-notice {

        font-size: 12px;

    }

}


/* =====================================================
   PRINT
===================================================== */

@media print {

    body {

        background: #ffffff !important;

    }

    .sessions-page {

        max-width: 100%;

        padding: 20px;

    }

    .security-card {

        box-shadow: none;

        border: 1px solid #d1d5db;

    }

    .security-notice {

        background: #f8fafc;

    }

    .btn {

        display: none !important;

    }

}

</style>

</head>


<body>


<?php include 'includes/user_navbar.php'; ?>


<div class="sessions-page">


<div class="security-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="d-flex
            justify-content-between
            align-items-start
            flex-wrap
            gap-3">

    <div>

        <span class="page-label">

            <i class="bi bi-shield-check"></i>

            ACCOUNT SECURITY

        </span>


        <h1 class="page-title">

            <i class="bi bi-shield-lock-fill"></i>

            Active Sessions

        </h1>


        <p class="page-subtitle mb-0">

            Manage the devices currently signed in to
            your NexaVault account.

        </p>

    </div>


    <div class="d-flex gap-2">

        <form method="POST">

            <button
                type="submit"
                name="logout_other_sessions"
                class="btn btn-danger">

                <i class="bi bi-box-arrow-right"></i>

                Logout Other Devices

            </button>

        </form>


        <a
            href="dashboard.php"
            class="btn btn-outline-light">

            <i class="bi bi-arrow-left"></i>

            Dashboard

        </a>

    </div>

</div>


<!-- =====================================================
     SECURITY NOTICE
===================================================== -->

<div class="security-notice">

    <i class="bi bi-info-circle-fill"></i>

    <div>

        <strong>Security Tip:</strong>

        Review active sessions regularly.
        If you don't recognize a device, log it out immediately.

    </div>

</div>


<!-- =====================================================
     SESSION LIST
===================================================== -->

<?php if (count($sessions) === 0) { ?>


<div class="empty-session">

    <i class="bi bi-laptop"></i>

    <h5>
        No Active Sessions Found
    </h5>

    <p>
        Your current login session may not yet be registered.
    </p>

</div>


<?php } else { ?>


<?php foreach ($sessions as $session) { ?>


<?php

$sessionId =
    (int)$session['id'];

$deviceName =
    htmlspecialchars(
        $session['device_name'] ??
        'Unknown Device',
        ENT_QUOTES
    );

$browser =
    getBrowserName(
        $session['user_agent'] ?? ''
    );

$ipAddress =
    htmlspecialchars(
        $session['ip_address'] ??
        'Unknown IP',
        ENT_QUOTES
    );

$createdAt =
    !empty($session['created_at'])
    ? date(
        "d M Y • h:i A",
        strtotime(
            $session['created_at']
        )
    )
    : "Unknown";

$lastActivity =
    !empty($session['last_activity'])
    ? date(
        "d M Y • h:i A",
        strtotime(
            $session['last_activity']
        )
    )
    : "Unknown";

$isCurrent =
    (
        $currentSessionToken !== '' &&
        hash_equals(
            $currentSessionToken,
            $session['session_token']
        )
    );

$icon =
    getDeviceIcon(
        $deviceName
    );

?>


<div class="session-item">


    <div class="session-left">


        <div class="device-icon">

            <i class="bi <?php echo $icon; ?>"></i>

        </div>


        <div>

            <div class="session-browser">

                <?php echo $browser; ?>


                <?php if ($isCurrent) { ?>

                    <span class="current-badge">

                        <i class="bi bi-check-circle-fill"></i>

                        Current Session

                    </span>

                <?php } ?>

            </div>


            <div class="session-details">

                Device:
                <?php echo $deviceName; ?>

                <br>

                IP:
                <?php echo $ipAddress; ?>

                <br>

                Signed in:
                <?php echo $createdAt; ?>

                <br>

                Last active:
                <?php echo $lastActivity; ?>

            </div>

        </div>

    </div>


    <div>

        <?php if ($isCurrent) { ?>

            <span class="session-active">

                <span>●</span>

                ACTIVE

            </span>

        <?php } else { ?>


            <form method="POST">

                <input
                    type="hidden"
                    name="session_id"
                    value="<?php echo $sessionId; ?>">


                <button
                    type="submit"
                    name="logout_session"
                    class="btn btn-outline-light btn-sm"
                    onclick="
                        return confirm(
                            'Logout this device?'
                        );
                    ">

                    <i class="bi bi-box-arrow-right"></i>

                    Logout

                </button>

            </form>


        <?php } ?>

    </div>


</div>


<?php } ?>


<?php } ?>


</div>

</div>


</body>

</html>