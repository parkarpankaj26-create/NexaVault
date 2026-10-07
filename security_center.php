<?php

session_start();

/* =====================================================
   LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit();
}


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


$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user'];


/* =====================================================
   UNREAD SECURITY ALERTS
===================================================== */

$unreadAlerts = 0;

$alertCountQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM security_alerts
     WHERE user_id='$user_id'
     AND is_read=0"
);

if ($alertCountQuery) {

    $alertCountRow = mysqli_fetch_assoc(
        $alertCountQuery
    );

    $unreadAlerts =
        (int)($alertCountRow['total'] ?? 0);
}


/* =====================================================
   SHARING SECURITY COUNTS
===================================================== */

$passwordProtectedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0
     AND share_password IS NOT NULL
     AND share_password <> ''"
);

$passwordProtected = 0;

if ($passwordProtectedResult) {

    $row = mysqli_fetch_assoc(
        $passwordProtectedResult
    );

    $passwordProtected =
        (int)($row['total'] ?? 0);
}


$expiryResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0
     AND expires_at IS NOT NULL
     AND expires_at <> ''"
);

$expiryEnabled = 0;

if ($expiryResult) {

    $row = mysqli_fetch_assoc(
        $expiryResult
    );

    $expiryEnabled =
        (int)($row['total'] ?? 0);
}


$limitResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0
     AND COALESCE(download_limit,0) > 0"
);

$limitedShares = 0;

if ($limitResult) {

    $row = mysqli_fetch_assoc(
        $limitResult
    );

    $limitedShares =
        (int)($row['total'] ?? 0);
}


$hashResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND deleted=0
     AND file_hash IS NOT NULL
     AND file_hash <> ''"
);

$hashedFiles = 0;

if ($hashResult) {

    $row = mysqli_fetch_assoc(
        $hashResult
    );

    $hashedFiles =
        (int)($row['total'] ?? 0);
}


/* =====================================================
   SESSION / LOGIN COUNTS
===================================================== */

$sessionResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM user_sessions
     WHERE user_id='$user_id'
     AND is_active=1"
);

$activeSessions = 0;

if ($sessionResult) {

    $row = mysqli_fetch_assoc(
        $sessionResult
    );

    $activeSessions =
        (int)($row['total'] ?? 0);
}


$historyCheck = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM login_history
     WHERE user_id='$user_id'"
);

$loginHistoryCount = 0;

if ($historyCheck) {

    $row = mysqli_fetch_assoc(
        $historyCheck
    );

    $loginHistoryCount =
        (int)($row['total'] ?? 0);
}


/* =====================================================
   MFA STATUS
===================================================== */

$mfaResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM mfa_codes
     WHERE user_id='$user_id'
     AND is_used=1"
);

$mfaVerified = 0;

if ($mfaResult) {

    $row = mysqli_fetch_assoc(
        $mfaResult
    );

    $mfaVerified =
        (int)($row['total'] ?? 0);
}


/* =====================================================
   SECURITY ALERT COUNTS - LAST 24 HOURS
===================================================== */

$highRiskAlerts = 0;
$mediumRiskAlerts = 0;


$highAlertResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM security_alerts
     WHERE user_id='$user_id'
     AND severity='high'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);

if ($highAlertResult) {

    $row = mysqli_fetch_assoc(
        $highAlertResult
    );

    $highRiskAlerts =
        (int)($row['total'] ?? 0);
}


$mediumAlertResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM security_alerts
     WHERE user_id='$user_id'
     AND severity='medium'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);

if ($mediumAlertResult) {

    $row = mysqli_fetch_assoc(
        $mediumAlertResult
    );

    $mediumRiskAlerts =
        (int)($row['total'] ?? 0);
}


/* =====================================================
   ADVANCED SECURITY SCORE
   Base score = exactly 100 points
===================================================== */

$securityScore = 0;


/* Password protection = 15 */

if ($passwordProtected > 0) {
    $securityScore += 15;
}


/* Link expiry = 10 */

if ($expiryEnabled > 0) {
    $securityScore += 10;
}


/* Download limits = 10 */

if ($limitedShares > 0) {
    $securityScore += 10;
}


/* SHA-256 integrity = 20 */

if ($hashedFiles > 0) {
    $securityScore += 20;
}


/* Active sessions = 10 */

if ($activeSessions > 0) {
    $securityScore += 10;
}


/* Login history = 10 */

if ($loginHistoryCount > 0) {
    $securityScore += 10;
}


/* MFA = 25 */

if ($mfaVerified > 0) {
    $securityScore += 25;
}


/* Security alert deductions */

$alertDeduction =
    ($highRiskAlerts * 15) +
    ($mediumRiskAlerts * 5);

if ($alertDeduction > 40) {
    $alertDeduction = 40;
}

$securityScore -= $alertDeduction;


if ($securityScore < 0) {
    $securityScore = 0;
}

if ($securityScore > 100) {
    $securityScore = 100;
}


/* =====================================================
   SCORE LABEL
===================================================== */

if ($securityScore >= 90) {

    $securityScoreLabel = "Excellent";

} elseif ($securityScore >= 75) {

    $securityScoreLabel = "Strong";

} elseif ($securityScore >= 50) {

    $securityScoreLabel = "Moderate";

} elseif ($securityScore >= 25) {

    $securityScoreLabel = "Needs Improvement";

} else {

    $securityScoreLabel = "Critical";
}


/* =====================================================
   SCORE BREAKDOWN DATA
===================================================== */

$scoreItems = [];


$scoreItems[] = [
    'title' => 'Password Protected Sharing',
    'points' => $passwordProtected > 0 ? 15 : 0,
    'status' => $passwordProtected > 0 ? 'pass' : 'warning',
    'description' => $passwordProtected > 0
        ? 'Shared files have password protection enabled.'
        : 'Create at least one password-protected share.'
];


$scoreItems[] = [
    'title' => 'Expiry Controls',
    'points' => $expiryEnabled > 0 ? 10 : 0,
    'status' => $expiryEnabled > 0 ? 'pass' : 'warning',
    'description' => $expiryEnabled > 0
        ? 'Shared links can use automatic expiry.'
        : 'Use expiry dates for sensitive shared links.'
];


$scoreItems[] = [
    'title' => 'Download Limits',
    'points' => $limitedShares > 0 ? 10 : 0,
    'status' => $limitedShares > 0 ? 'pass' : 'warning',
    'description' => $limitedShares > 0
        ? 'Download limits are enabled for shared files.'
        : 'Use download limits to restrict file access.'
];


$scoreItems[] = [
    'title' => 'File Integrity Protection',
    'points' => $hashedFiles > 0 ? 20 : 0,
    'status' => $hashedFiles > 0 ? 'pass' : 'warning',
    'description' => $hashedFiles > 0
        ? 'SHA-256 hashes are available for your files.'
        : 'Verify and protect your files using SHA-256.'
];


$scoreItems[] = [
    'title' => 'Active Session Monitoring',
    'points' => $activeSessions > 0 ? 10 : 0,
    'status' => $activeSessions > 0 ? 'pass' : 'warning',
    'description' => $activeSessions > 0
        ? 'Active login sessions are being monitored.'
        : 'Login again to create an active monitored session.'
];


$scoreItems[] = [
    'title' => 'Login History',
    'points' => $loginHistoryCount > 0 ? 10 : 0,
    'status' => $loginHistoryCount > 0 ? 'pass' : 'warning',
    'description' => $loginHistoryCount > 0
        ? 'Login activity is being recorded.'
        : 'Login activity will appear after authentication.'
];


$scoreItems[] = [
    'title' => 'Multi-Factor Authentication',
    'points' => $mfaVerified > 0 ? 25 : 0,
    'status' => $mfaVerified > 0 ? 'pass' : 'warning',
    'description' => $mfaVerified > 0
        ? 'MFA verification has been completed successfully.'
        : 'Complete MFA verification to strengthen account security.'
];


if ($highRiskAlerts > 0) {

    $scoreItems[] = [
        'title' => 'High Risk Security Alerts',
        'points' => -($highRiskAlerts * 15),
        'status' => 'danger',
        'description' =>
            $highRiskAlerts .
            ' high-risk security alert(s) affected your score.'
    ];
}


if ($mediumRiskAlerts > 0) {

    $scoreItems[] = [
        'title' => 'Medium Risk Security Alerts',
        'points' => -($mediumRiskAlerts * 5),
        'status' => 'warning',
        'description' =>
            $mediumRiskAlerts .
            ' medium-risk alert(s) affected your score.'
    ];
}


/* =====================================================
   SUSPICIOUS LOGIN DETECTION
===================================================== */

$suspiciousLevel = "normal";
$suspiciousTitle = "No Suspicious Activity";
$suspiciousMessage = "No unusual login activity detected.";
$suspiciousCount = 0;

$recentFailed24h = 0;
$newDeviceDetected = false;
$newIpDetected = false;


$failed24Query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM login_history
     WHERE user_id='$user_id'
     AND login_status='failed'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
);

if ($failed24Query) {

    $failed24Row =
        mysqli_fetch_assoc(
            $failed24Query
        );

    $recentFailed24h =
        (int)($failed24Row['total'] ?? 0);
}


/* Current successful login */

$currentLogin = null;

$currentLoginQuery = mysqli_query(
    $conn,
    "SELECT
        device_name,
        ip_address,
        created_at
     FROM login_history
     WHERE user_id='$user_id'
     AND login_status='success'
     ORDER BY id DESC
     LIMIT 1"
);

if (
    $currentLoginQuery &&
    mysqli_num_rows($currentLoginQuery) > 0
) {

    $currentLogin =
        mysqli_fetch_assoc(
            $currentLoginQuery
        );
}


/* Known devices and IPs */

$knownDevices = [];
$knownIps = [];

$previousLoginQuery = mysqli_query(
    $conn,
    "SELECT
        device_name,
        ip_address
     FROM login_history
     WHERE user_id='$user_id'
     AND login_status='success'
     ORDER BY id DESC
     LIMIT 20"
);

if ($previousLoginQuery) {

    while (
        $loginRow =
        mysqli_fetch_assoc(
            $previousLoginQuery
        )
    ) {

        if (!empty($loginRow['device_name'])) {

            $knownDevices[] =
                $loginRow['device_name'];
        }

        if (!empty($loginRow['ip_address'])) {

            $knownIps[] =
                $loginRow['ip_address'];
        }
    }
}


if ($currentLogin) {

    $currentDevice =
        $currentLogin['device_name'] ?? '';

    $currentIp =
        $currentLogin['ip_address'] ?? '';


    if (
        $currentDevice !== '' &&
        count($knownDevices) > 1
    ) {

        $deviceOccurrences = 0;

        foreach ($knownDevices as $device) {

            if ($device === $currentDevice) {
                $deviceOccurrences++;
            }
        }

        if ($deviceOccurrences === 1) {
            $newDeviceDetected = true;
        }
    }


    if (
        $currentIp !== '' &&
        count($knownIps) > 1
    ) {

        $ipOccurrences = 0;

        foreach ($knownIps as $ip) {

            if ($ip === $currentIp) {
                $ipOccurrences++;
            }
        }

        if ($ipOccurrences === 1) {
            $newIpDetected = true;
        }
    }
}


/* Suspicious level */

if ($recentFailed24h >= 5) {

    $suspiciousLevel = "high";

    $suspiciousTitle =
        "High-Risk Login Activity";

    $suspiciousMessage =
        $recentFailed24h .
        " failed login attempts were detected in the last 24 hours.";

    $suspiciousCount =
        $recentFailed24h;


} elseif (
    $newDeviceDetected &&
    $newIpDetected
) {

    $suspiciousLevel = "high";

    $suspiciousTitle =
        "New Device & IP Detected";

    $suspiciousMessage =
        "A successful login was detected from a new device and IP address.";

    $suspiciousCount = 1;


} elseif (
    $newDeviceDetected ||
    $newIpDetected ||
    $recentFailed24h >= 3
) {

    $suspiciousLevel = "warning";

    if ($newDeviceDetected) {

        $suspiciousTitle =
            "New Device Detected";

        $suspiciousMessage =
            "A successful login from a new device was detected.";

    } elseif ($newIpDetected) {

        $suspiciousTitle =
            "New IP Address Detected";

        $suspiciousMessage =
            "A successful login from a new IP address was detected.";

    } else {

        $suspiciousTitle =
            "Multiple Failed Logins";

        $suspiciousMessage =
            $recentFailed24h .
            " failed login attempts were detected in the last 24 hours.";
    }

    $suspiciousCount =
        max(
            1,
            $recentFailed24h
        );
}


/* =====================================================
   SECURITY STATUS TEXT
===================================================== */

$securityStatusText =
    "System Protected";

$securityStatusDescription =
    "NexaVault security controls are active.";


if ($suspiciousLevel === "warning") {

    $securityStatusText =
        "Security Review Recommended";

    $securityStatusDescription =
        "Unusual login activity requires attention.";

} elseif ($suspiciousLevel === "high") {

    $securityStatusText =
        "Security Alert Detected";

    $securityStatusDescription =
        "Potentially suspicious login activity was detected.";
}


/* =====================================================
   SECURITY STATISTICS
===================================================== */

$totalShared = 0;
$activeLinks = 0;
$expiredLinks = 0;
$totalSharedDownloads = 0;


$sharedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0"
);

if ($sharedResult) {

    $sharedRow =
        mysqli_fetch_assoc(
            $sharedResult
        );

    $totalShared =
        (int)($sharedRow['total'] ?? 0);
}


$activeResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0
     AND (
        expires_at IS NULL
        OR expires_at > NOW()
     )"
);

if ($activeResult) {

    $activeRow =
        mysqli_fetch_assoc(
            $activeResult
        );

    $activeLinks =
        (int)($activeRow['total'] ?? 0);
}


$expiredResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0
     AND expires_at IS NOT NULL
     AND expires_at <= NOW()"
);

if ($expiredResult) {

    $expiredRow =
        mysqli_fetch_assoc(
            $expiredResult
        );

    $expiredLinks =
        (int)($expiredRow['total'] ?? 0);
}


$downloadResult = mysqli_query(
    $conn,
    "SELECT COALESCE(SUM(share_downloads),0) AS total
     FROM files
     WHERE user_id='$user_id'
     AND is_shared=1
     AND deleted=0"
);

if ($downloadResult) {

    $downloadRow =
        mysqli_fetch_assoc(
            $downloadResult
        );

    $totalSharedDownloads =
        (int)($downloadRow['total'] ?? 0);
}


/* =====================================================
   RECENT ACTIVITY
===================================================== */

$activityResult = false;

$activityCheck = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'activity_log'"
);

if (
    $activityCheck &&
    mysqli_num_rows($activityCheck) > 0
) {

    $activityResult = mysqli_query(
        $conn,
        "SELECT *
         FROM activity_log
         WHERE user_id='$user_id'
         ORDER BY created_at DESC
         LIMIT 6"
    );
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>
    Security Center - NexaVault
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">


<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    min-height:100vh;
    font-family:"Segoe UI",Arial,sans-serif;
    color:#1e293b;

    background:
        radial-gradient(
            circle at 10% 15%,
            rgba(37,99,235,.08),
            transparent 32%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(124,58,237,.07),
            transparent 34%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #f8fafc
        );

    background-attachment:fixed;
}


/* =====================================================
   PAGE
===================================================== */

.security-page{
    max-width:1250px;
    margin:auto;
    padding:42px 22px 70px;
}


/* =====================================================
   HEADER
===================================================== */

.security-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
    margin-bottom:28px;
}

.security-label{
    color:#2563eb;
    font-size:10px;
    font-weight:800;
    letter-spacing:2px;
}

.security-title{
    margin:5px 0;
    color:#0f172a;
    font-size:32px;
    font-weight:800;
}

.security-subtitle{
    color:#64748b;
    margin:0;
    font-size:13px;
}


/* =====================================================
   COMMON PANEL
===================================================== */

.security-panel{
    background:#ffffff;

    border:1px solid #e2e8f0;

    border-radius:22px;

    box-shadow:
        0 12px 35px
        rgba(15,23,42,.07);

    backdrop-filter:none;
}


/* =====================================================
   HERO
===================================================== */

.security-hero{
    position:relative;
    padding:32px;
    overflow:hidden;
}

.security-hero::after{
    content:"";

    position:absolute;

    width:250px;
    height:250px;

    right:-100px;
    top:-110px;

    border-radius:50%;

    background:
        rgba(37,99,235,.06);

    filter:blur(8px);
}

.security-hero-content{
    position:relative;
    z-index:2;

    display:flex;
    align-items:center;
    gap:22px;
}

.security-shield{
    width:76px;
    height:76px;

    border-radius:20px;

    display:flex;
    align-items:center;
    justify-content:center;

    flex-shrink:0;

    font-size:36px;
    color:#ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    box-shadow:
        0 12px 28px
        rgba(37,99,235,.22);
}

.security-hero h3{
    margin:0;
    color:#0f172a;
    font-size:23px;
    font-weight:800;
}

.security-hero p{
    margin:7px 0 0;
    color:#64748b;
    font-size:12px;
}


/* =====================================================
   PROTECTION STATUS
===================================================== */

.protection-status{
    display:inline-flex;
    align-items:center;
    gap:8px;

    margin-top:15px;
    padding:8px 13px;

    border-radius:30px;

    background:#ecfdf5;
    border:1px solid #bbf7d0;

    color:#059669;

    font-size:12px;
    font-weight:700;
}

.protection-dot{
    width:8px;
    height:8px;

    border-radius:50%;

    background:#22c55e;

    box-shadow:
        0 0 8px
        rgba(34,197,94,.35);
}


/* =====================================================
   SECURITY STATISTICS
===================================================== */

.security-stat{
    height:100%;
    padding:23px;

    transition:.25s ease;
}

.security-stat:hover{
    transform:translateY(-4px);

    border-color:#bfdbfe;

    box-shadow:
        0 16px 35px
        rgba(37,99,235,.10);
}

.security-stat-icon{
    width:50px;
    height:50px;

    border-radius:14px;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:21px;

    margin-bottom:15px;
}

.blue{
    background:#eff6ff;
    color:#2563eb;
}

.green{
    background:#ecfdf5;
    color:#059669;
}

.red{
    background:#fef2f2;
    color:#dc2626;
}

.purple{
    background:#f5f3ff;
    color:#7c3aed;
}

.security-stat small{
    color:#64748b;
    font-size:11px;
}

.security-stat h2{
    color:#0f172a;
    font-size:29px;
    font-weight:800;
    margin:3px 0 0;
}


/* =====================================================
   CHECKLIST
===================================================== */

.security-checklist{
    padding:25px;
}

.panel-title{
    color:#0f172a;
    font-size:18px;
    font-weight:750;
    margin-bottom:20px;
}

.panel-title i{
    color:#2563eb;
    margin-right:5px;
}

.security-check{
    display:flex;
    align-items:center;
    gap:14px;

    padding:14px 0;

    border-bottom:
        1px solid #edf2f7;
}

.security-check:last-child{
    border-bottom:none;
}

.check-icon{
    width:38px;
    height:38px;

    border-radius:11px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#ecfdf5;
    color:#059669;

    flex-shrink:0;
}

.security-check strong{
    color:#1e293b;
    display:block;
    font-size:13px;
}

.security-check small{
    color:#64748b;
    font-size:10px;
}


/* =====================================================
   ACTIVITY
===================================================== */

.activity-list{
    padding:5px 25px 20px;
}

.activity-item{
    display:flex;
    gap:13px;

    padding:15px 0;

    border-bottom:
        1px solid #edf2f7;
}

.activity-item:last-child{
    border-bottom:none;
}

.activity-icon{
    width:38px;
    height:38px;

    border-radius:11px;

    display:flex;
    align-items:center;
    justify-content:center;

    flex-shrink:0;

    color:#2563eb;
    background:#eff6ff;
}

.activity-details strong{
    display:block;
    color:#1e293b;
    font-size:13px;
}

.activity-details small{
    color:#64748b;
    font-size:11px;
}


/* =====================================================
   SECURITY TIP
===================================================== */

.security-tip{
    padding:25px;
}

.tip-box{
    padding:15px 16px;

    border-radius:13px;

    background:#f8fafc;

    border:
        1px solid #e2e8f0;

    color:#64748b;

    font-size:12px;
    line-height:1.7;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn-primary{
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    border:none;
    border-radius:10px;
}

.btn-primary:hover{
    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        );

    color:#ffffff;
}

.btn-outline-light{
    border:
        1px solid #cbd5e1 !important;

    color:#334155 !important;

    background:#ffffff !important;

    border-radius:10px;
}

.btn-outline-light:hover{
    background:#f1f5f9 !important;
    color:#0f172a !important;
}


/* =====================================================
   DARK INLINE STYLE OVERRIDES
   ===================================================== */

.security-panel
[style*="background:rgba(255,255,255,.035)"]{
    background:#f8fafc !important;
    border:1px solid #e2e8f0 !important;
}


/* White text used in inline styles */

.security-panel
[style*="color:#ffffff"]{
    color:#0f172a !important;
}


/* Light blue dark-theme text */

.security-panel
[style*="color:#6ea1ff"]{
    color:#2563eb !important;
}


/* Muted dark-theme text */

.security-panel
[style*="color:#8193af"],
.security-panel
[style*="color:#7184a3"],
.security-panel
[style*="color:#7f90ae"],
.security-panel
[style*="color:#8fa2c2"],
.security-panel
[style*="color:#91a0bb"]{
    color:#64748b !important;
}


/* Dark-theme main text */

.security-panel
[style*="color:#e8eefc"]{
    color:#1e293b !important;
}


/* Blue inline text */

.security-panel
[style*="color:#60a5fa"]{
    color:#2563eb !important;
}


/* Purple */

.security-panel
[style*="color:#a78bfa"]{
    color:#7c3aed !important;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px){

    .security-page{
        padding:28px 12px 50px;
    }

    .security-title{
        font-size:27px;
    }

    .security-hero{
        padding:23px;
    }

    .security-hero-content{
        align-items:flex-start;
        gap:15px;
    }

    .security-shield{
        width:60px;
        height:60px;
        font-size:28px;
        border-radius:16px;
    }

    .security-hero h3{
        font-size:19px;
    }

    .security-header{
        align-items:flex-start;
    }
}


/* =====================================================
   PRINT
===================================================== */

@media print{

    body{
        background:#ffffff !important;
        color:#000000 !important;
    }

    .security-page{
        max-width:100%;
        padding:15px;
    }

    .security-panel{
        background:#ffffff !important;
        border:1px solid #d1d5db !important;
        box-shadow:none !important;
    }

    .security-title,
    .security-hero h3,
    .panel-title,
    .security-stat h2,
    .security-check strong,
    .activity-details strong{
        color:#000000 !important;
    }

    .security-subtitle,
    .security-hero p,
    .security-check small,
    .activity-details small,
    .tip-box{
        color:#475569 !important;
    }

    .security-header .btn{
        display:none !important;
    }
}

</style>

</head>


<body>

<main class="security-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="security-header">

    <div>

        <span class="security-label">
            SECURITY CENTER
        </span>

        <h1 class="security-title">
            Security & Protection
        </h1>

        <p class="security-subtitle">
            Monitor your NexaVault account and shared-file security.
        </p>

    </div>


    <div class="d-flex gap-2 flex-wrap">


        <a
            href="dashboard.php"
            class="btn btn-outline-light">

            <i class="bi bi-arrow-left"></i>

            Dashboard

        </a>


        <a
            href="security_alerts.php"
            class="btn btn-outline-light position-relative">

            <i class="bi bi-bell-fill"></i>

            Security Alerts


            <?php if ($unreadAlerts > 0) { ?>

                <span
                    style="
                        position:absolute;
                        top:-7px;
                        right:-7px;
                        min-width:21px;
                        height:21px;
                        padding:2px 6px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:50px;
                        background:#ef4444;
                        color:#ffffff;
                        font-size:10px;
                        font-weight:800;
                        border:2px solid #07142f;
                    ">

                    <?php echo $unreadAlerts; ?>

                </span>

            <?php } ?>

        </a>

    </div>

</div>


<!-- =====================================================
     PROTECTION HERO
===================================================== -->

<div class="security-panel security-hero mb-4">


    <div class="security-hero-content">


        <div class="security-shield">

            <i class="bi bi-shield-lock-fill"></i>

        </div>


        <div>

            <h3>

                <?php echo htmlspecialchars(
                    $securityStatusText
                ); ?>

            </h3>


            <p>

                <?php echo htmlspecialchars(
                    $securityStatusDescription
                ); ?>

            </p>


            <div class="protection-status">

                <span class="protection-dot"></span>

                <?php echo htmlspecialchars(
                    $securityStatusText
                ); ?>

            </div>

        </div>


    </div>

</div>


<!-- =====================================================
     SECURITY STATISTICS
===================================================== -->

<div class="row g-4 mb-4">


    <div class="col-lg-3 col-md-6">

        <div class="security-panel security-stat">

            <div class="security-stat-icon blue">

                <i class="bi bi-share-fill"></i>

            </div>

            <small>
                Total Shared Links
            </small>

            <h2>
                <?php echo $totalShared; ?>
            </h2>

        </div>

    </div>


    <div class="col-lg-3 col-md-6">

        <div class="security-panel security-stat">

            <div class="security-stat-icon green">

                <i class="bi bi-shield-check"></i>

            </div>

            <small>
                Active Links
            </small>

            <h2>
                <?php echo $activeLinks; ?>
            </h2>

        </div>

    </div>


    <div class="col-lg-3 col-md-6">

        <div class="security-panel security-stat">

            <div class="security-stat-icon red">

                <i class="bi bi-clock-history"></i>

            </div>

            <small>
                Expired Links
            </small>

            <h2>
                <?php echo $expiredLinks; ?>
            </h2>

        </div>

    </div>


    <div class="col-lg-3 col-md-6">

        <div class="security-panel security-stat">

            <div class="security-stat-icon purple">

                <i class="bi bi-download"></i>

            </div>

            <small>
                Shared Downloads
            </small>

            <h2>
                <?php echo $totalSharedDownloads; ?>
            </h2>

        </div>

    </div>


</div>


<!-- =====================================================
     SECURITY CHECKLIST + ACTIVITY
===================================================== -->

<div class="row g-4 mb-4">


    <!-- CHECKLIST -->

    <div class="col-lg-6">

        <div class="security-panel security-checklist h-100">

            <div class="panel-title">

                <i class="bi bi-shield-check"></i>

                Security Controls

            </div>


            <div class="security-check">

                <div class="check-icon">
                    <i class="bi bi-check-lg"></i>
                </div>

                <div>

                    <strong>
                        Account Authentication
                    </strong>

                    <small>
                        Login protection is enabled.
                    </small>

                </div>

            </div>


            <div class="security-check">

                <div class="check-icon">
                    <i class="bi bi-check-lg"></i>
                </div>

                <div>

                    <strong>
                        Password Protected Sharing
                    </strong>

                    <small>
                        Shared files can require a secure password.
                    </small>

                </div>

            </div>


            <div class="security-check">

                <div class="check-icon">
                    <i class="bi bi-check-lg"></i>
                </div>

                <div>

                    <strong>
                        Link Expiry Control
                    </strong>

                    <small>
                        Shared links can automatically expire.
                    </small>

                </div>

            </div>


            <div class="security-check">

                <div class="check-icon">
                    <i class="bi bi-check-lg"></i>
                </div>

                <div>

                    <strong>
                        Download Limits
                    </strong>

                    <small>
                        Sharing links can restrict file downloads.
                    </small>

                </div>

            </div>


            <div class="security-check">

                <div class="check-icon">
                    <i class="bi bi-check-lg"></i>
                </div>

                <div>

                    <strong>
                        Link Revocation
                    </strong>

                    <small>
                        Active sharing links can be revoked.
                    </small>

                </div>

            </div>

        </div>

    </div>


    <!-- ACTIVITY -->

    <div class="col-lg-6">

        <div class="security-panel h-100">

            <div class="panel-title p-4 pb-2">

                <i class="bi bi-activity"></i>

                Recent Security Activity

            </div>


            <div class="activity-list">


                <?php

                if (
                    $activityResult &&
                    mysqli_num_rows($activityResult) > 0
                ) {

                    while (
                        $activity =
                        mysqli_fetch_assoc(
                            $activityResult
                        )
                    ) {

                        ?>

                        <div class="activity-item">

                            <div class="activity-icon">

                                <i class="bi bi-shield-check"></i>

                            </div>


                            <div class="activity-details">

                                <strong>

                                    <?php echo htmlspecialchars(
                                        $activity['activity'] ??
                                        'Security Activity'
                                    ); ?>

                                </strong>


                                <small>

                                    <?php

                                    $activityTime =
                                        strtotime(
                                            $activity['created_at'] ??
                                            ''
                                        );

                                    echo $activityTime
                                        ? date(
                                            "d M Y • H:i",
                                            $activityTime
                                        )
                                        : "Unknown time";

                                    ?>

                                </small>

                            </div>

                        </div>

                        <?php

                    }

                } else {

                    ?>

                    <div class="text-center py-5">

                        <i
                            class="bi bi-clock-history"
                            style="
                                font-size:38px;
                                color:#64748b;
                            ">
                        </i>

                        <p
                            style="color:#8fa2c2;"
                            class="mt-3 mb-0">

                            No recent security activity.

                        </p>

                    </div>

                    <?php
                }

                ?>


            </div>

        </div>

    </div>


</div>


<!-- =====================================================
     SUSPICIOUS ACTIVITY
===================================================== -->

<div
    class="security-panel mb-4"
    style="padding:25px;">

    <div
        class="d-flex
               justify-content-between
               align-items-center
               flex-wrap
               gap-3">


        <div>

            <div
                style="
                    color:#6ea1ff;
                    font-size:10px;
                    font-weight:800;
                    letter-spacing:1.5px;
                ">

                LOGIN SECURITY MONITOR

            </div>


            <h4
                style="
                    color:#ffffff;
                    font-weight:750;
                    margin:5px 0;
                ">

                Suspicious Activity Detection

            </h4>


            <p
                style="
                    color:#8193af;
                    font-size:12px;
                    margin:0;
                ">

                Rule-based monitoring of unusual login behavior.

            </p>

        </div>


        <?php if ($suspiciousLevel === "normal") { ?>

            <span
                class="badge rounded-pill"
                style="
                    background:rgba(16,185,129,.12);
                    color:#34d399;
                    padding:9px 13px;
                ">

                <i class="bi bi-shield-check"></i>

                Normal

            </span>

        <?php } elseif ($suspiciousLevel === "warning") { ?>

            <span
                class="badge rounded-pill"
                style="
                    background:rgba(245,158,11,.12);
                    color:#fbbf24;
                    padding:9px 13px;
                ">

                <i class="bi bi-exclamation-triangle"></i>

                Warning

            </span>

        <?php } else { ?>

            <span
                class="badge rounded-pill"
                style="
                    background:rgba(239,68,68,.12);
                    color:#f87171;
                    padding:9px 13px;
                ">

                <i class="bi bi-shield-exclamation"></i>

                High Risk

            </span>

        <?php } ?>

    </div>


    <div
        class="mt-4 p-3"
        style="
            border-radius:14px;
            background:rgba(255,255,255,.035);
            border:1px solid rgba(255,255,255,.06);
        ">


        <div
            style="
                color:#ffffff;
                font-size:13px;
                font-weight:700;
            ">

            <?php echo htmlspecialchars(
                $suspiciousTitle
            ); ?>

        </div>


        <div
            style="
                color:#8193af;
                font-size:11px;
                margin-top:5px;
                line-height:1.7;
            ">

            <?php echo htmlspecialchars(
                $suspiciousMessage
            ); ?>

        </div>


        <div
            class="d-flex
                   gap-3
                   flex-wrap
                   mt-3">


            <span
                style="
                    color:#8fa2c2;
                    font-size:10px;
                ">

                <i class="bi bi-x-circle"></i>

                Failed in 24h:

                <strong style="color:#ffffff;">

                    <?php echo $recentFailed24h; ?>

                </strong>

            </span>


            <span
                style="
                    color:#8fa2c2;
                    font-size:10px;
                ">

                <i class="bi bi-phone"></i>

                New Device:

                <strong style="color:#ffffff;">

                    <?php
                    echo $newDeviceDetected
                        ? 'Yes'
                        : 'No';
                    ?>

                </strong>

            </span>


            <span
                style="
                    color:#8fa2c2;
                    font-size:10px;
                ">

                <i class="bi bi-globe2"></i>

                New IP:

                <strong style="color:#ffffff;">

                    <?php
                    echo $newIpDetected
                        ? 'Yes'
                        : 'No';
                    ?>

                </strong>

            </span>


        </div>

    </div>


    <div class="mt-3">


        <a
            href="login_history.php"
            class="btn btn-outline-light btn-sm">

            <i class="bi bi-clock-history"></i>

            View Login History

        </a>


        <a
            href="security_sessions.php"
            class="btn btn-primary btn-sm ms-2">

            <i class="bi bi-shield-lock"></i>

            Review Sessions

        </a>


    </div>

</div>


<!-- =====================================================
     SECURITY SCORE
===================================================== -->

<div
    class="security-panel mb-4"
    style="padding:25px;">


    <div
        class="d-flex
               justify-content-between
               align-items-center
               flex-wrap
               gap-3">


        <div>

            <span
                style="
                    color:#6ea1ff;
                    font-size:10px;
                    font-weight:800;
                    letter-spacing:1.8px;
                ">

                ACCOUNT SECURITY SCORE

            </span>


            <h4
                style="
                    color:#ffffff;
                    font-weight:750;
                    margin:5px 0;
                ">

                Overall Protection

            </h4>


            <p
                style="
                    color:#8193af;
                    font-size:11px;
                    margin:0;
                ">

                Based on your current security controls.

            </p>

        </div>


        <div style="text-align:right;">

            <div
                style="
                    font-size:38px;
                    font-weight:800;
                    color:#ffffff;
                ">

                <?php echo $securityScore; ?>%

            </div>


            <span
                style="
                    color:#60a5fa;
                    font-size:11px;
                    font-weight:700;
                ">

                <?php echo $securityScoreLabel; ?>

            </span>

        </div>


    </div>


    <div
        style="
            margin-top:20px;
            height:10px;
            border-radius:20px;
            background:rgba(255,255,255,.07);
            overflow:hidden;
        ">


        <div
            style="
                width:<?php echo $securityScore; ?>%;
                height:100%;
                border-radius:20px;
                background:linear-gradient(90deg,#2563eb,#6366f1);
            ">
        </div>

    </div>


    <div class="row g-3 mt-3">


        <div class="col-md-4">

            <div
                style="
                    padding:12px;
                    border-radius:12px;
                    background:rgba(255,255,255,.035);
                ">

                <small style="color:#7184a3;">
                    Protected Shares
                </small>


                <div
                    style="
                        color:#ffffff;
                        font-weight:700;
                        margin-top:3px;
                    ">

                    <?php echo $passwordProtected; ?>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div
                style="
                    padding:12px;
                    border-radius:12px;
                    background:rgba(255,255,255,.035);
                ">

                <small style="color:#7184a3;">
                    Hashed Files
                </small>


                <div
                    style="
                        color:#ffffff;
                        font-weight:700;
                        margin-top:3px;
                    ">

                    <?php echo $hashedFiles; ?>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div
                style="
                    padding:12px;
                    border-radius:12px;
                    background:rgba(255,255,255,.035);
                ">

                <small style="color:#7184a3;">
                    Active Sessions
                </small>


                <div
                    style="
                        color:#ffffff;
                        font-weight:700;
                        margin-top:3px;
                    ">

                    <?php echo $activeSessions; ?>

                </div>

            </div>

        </div>


    </div>

</div>


<!-- =====================================================
     SECURITY SCORE BREAKDOWN
===================================================== -->

<div
    class="security-panel mb-4"
    style="padding:25px;">


    <div
        class="d-flex
               justify-content-between
               align-items-center
               flex-wrap
               gap-3
               mb-3">


        <div>

            <span
                style="
                    color:#6ea1ff;
                    font-size:10px;
                    font-weight:800;
                    letter-spacing:1.8px;
                ">

                SCORE BREAKDOWN

            </span>


            <h4
                style="
                    color:#ffffff;
                    font-weight:750;
                    margin:5px 0;
                ">

                Why is your score
                <?php echo $securityScore; ?>%?

            </h4>


            <p
                style="
                    color:#8193af;
                    font-size:11px;
                    margin:0;
                ">

                Your score is calculated from active NexaVault
                security controls.

            </p>

        </div>


        <span
            style="
                padding:8px 12px;
                border-radius:10px;
                background:rgba(59,130,246,.10);
                color:#60a5fa;
                font-size:11px;
                font-weight:800;
            ">

            <?php echo $securityScore; ?>/100

        </span>


    </div>


    <?php foreach ($scoreItems as $item) { ?>

        <?php

        if ($item['status'] === 'pass') {

            $icon = 'bi-check-circle-fill';
            $iconColor = '#34d399';
            $iconBg = 'rgba(16,185,129,.10)';

        } elseif ($item['status'] === 'warning') {

            $icon = 'bi-exclamation-circle-fill';
            $iconColor = '#fbbf24';
            $iconBg = 'rgba(245,158,11,.10)';

        } else {

            $icon = 'bi-shield-exclamation';
            $iconColor = '#f87171';
            $iconBg = 'rgba(239,68,68,.10)';
        }

        ?>


        <div
            style="
                display:flex;
                align-items:center;
                justify-content:space-between;
                gap:15px;
                padding:14px 0;
                border-bottom:1px solid rgba(255,255,255,.06);
            ">


            <div
                style="
                    display:flex;
                    align-items:center;
                    gap:12px;
                    min-width:0;
                ">


                <div
                    style="
                        width:38px;
                        height:38px;
                        flex-shrink:0;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        border-radius:10px;
                        color:<?php echo $iconColor; ?>;
                        background:<?php echo $iconBg; ?>;
                    ">

                    <i class="bi <?php echo $icon; ?>"></i>

                </div>


                <div>

                    <strong
                        style="
                            display:block;
                            color:#e8eefc;
                            font-size:12px;
                        ">

                        <?php echo htmlspecialchars(
                            $item['title']
                        ); ?>

                    </strong>


                    <small
                        style="
                            display:block;
                            color:#7184a3;
                            font-size:9px;
                            margin-top:3px;
                        ">

                        <?php echo htmlspecialchars(
                            $item['description']
                        ); ?>

                    </small>

                </div>

            </div>


            <div
                style="
                    white-space:nowrap;
                    font-weight:800;
                    font-size:12px;
                    color:<?php echo $iconColor; ?>;
                ">

                <?php

                $displayPoints =
                    (int)$item['points'];

                if ($displayPoints > 0) {

                    echo '+' . $displayPoints;

                } else {

                    echo $displayPoints;
                }

                ?>

                pts

            </div>


        </div>


    <?php } ?>


    <div
        class="d-flex
               justify-content-between
               align-items-center
               mt-4">

        <span
            style="
                color:#7184a3;
                font-size:10px;
            ">

            Maximum Security Score

        </span>


        <strong
            style="
                color:#ffffff;
                font-size:14px;
            ">

            100 pts

        </strong>

    </div>

</div>


<!-- =====================================================
     SECURITY RECOMMENDATIONS
===================================================== -->

<div class="security-panel security-tip">

    <div class="panel-title">

        <i class="bi bi-lightbulb"></i>

        Security Recommendations

    </div>


    <div class="tip-box">


        <?php
        $hasRecommendation = false;
        ?>


        <?php if ($mfaVerified <= 0) {

            $hasRecommendation = true;
        ?>

            <div class="mb-3">

                <strong style="color:#fbbf24;">

                    <i class="bi bi-shield-lock"></i>

                    Complete MFA Verification

                </strong>


                <div class="mt-1">

                    Complete the OTP verification flow
                    to add an additional authentication
                    layer to your account.

                </div>

            </div>

        <?php } ?>


        <?php if ($highRiskAlerts > 0) {

            $hasRecommendation = true;
        ?>

            <div class="mb-3">

                <strong style="color:#f87171;">

                    <i class="bi bi-exclamation-triangle-fill"></i>

                    Review High-Risk Alerts

                </strong>


                <div class="mt-1">

                    <?php echo $highRiskAlerts; ?>
                    high-risk security alert(s) were
                    detected during the last 24 hours.

                    <a
                        href="security_alerts.php"
                        class="text-info text-decoration-none">

                        Review alerts

                    </a>.

                </div>

            </div>

        <?php } ?>


        <?php if ($mediumRiskAlerts > 0) {

            $hasRecommendation = true;
        ?>

            <div class="mb-3">

                <strong style="color:#fbbf24;">

                    <i class="bi bi-info-circle-fill"></i>

                    Review Medium-Risk Activity

                </strong>


                <div class="mt-1">

                    <?php echo $mediumRiskAlerts; ?>
                    medium-risk security alert(s)
                    were detected during the last 24 hours.

                </div>

            </div>

        <?php } ?>


        <?php if ($passwordProtected <= 0) {

            $hasRecommendation = true;
        ?>

            <div class="mb-3">

                <strong style="color:#60a5fa;">

                    <i class="bi bi-lock-fill"></i>

                    Protect Shared Files

                </strong>


                <div class="mt-1">

                    Use password protection when
                    sharing sensitive files.

                </div>

            </div>

        <?php } ?>


        <?php if ($expiryEnabled <= 0) {

            $hasRecommendation = true;
        ?>

            <div class="mb-3">

                <strong style="color:#60a5fa;">

                    <i class="bi bi-calendar-x"></i>

                    Add Link Expiry

                </strong>


                <div class="mt-1">

                    Add expiry dates to sensitive
                    shared links to reduce long-term access.

                </div>

            </div>

        <?php } ?>


        <?php if ($limitedShares <= 0) {

            $hasRecommendation = true;
        ?>

            <div>

                <strong style="color:#60a5fa;">

                    <i class="bi bi-download"></i>

                    Set Download Limits

                </strong>


                <div class="mt-1">

                    Restrict the number of downloads
                    for important shared files.

                </div>

            </div>

        <?php } ?>


        <?php if (!$hasRecommendation) { ?>

            <strong style="color:#34d399;">

                <i class="bi bi-shield-check"></i>

                Security posture looks good

            </strong>


            <div class="mt-1">

                No immediate security recommendations
                are pending based on the current checks.

            </div>

        <?php } ?>


    </div>

</div>


</main>

</body>

</html>