<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int)$_SESSION['user_id'];


/* =====================================================
   GET LOGIN HISTORY
===================================================== */

$query = mysqli_query(
    $conn,
    "SELECT
        id,
        email,
        login_status,
        ip_address,
        user_agent,
        device_name,
        created_at
     FROM login_history
     WHERE user_id='$user_id'
     ORDER BY id DESC
     LIMIT 100"
);

$history = [];

if ($query) {

    while ($row = mysqli_fetch_assoc($query)) {
        $history[] = $row;
    }
}


/* =====================================================
   STATISTICS
===================================================== */

$totalLogins = count($history);
$successfulLogins = 0;
$failedLogins = 0;

foreach ($history as $row) {

    if ($row['login_status'] === 'success') {
        $successfulLogins++;
    }

    if ($row['login_status'] === 'failed') {
        $failedLogins++;
    }
}


/* =====================================================
   BROWSER
===================================================== */

function detectBrowser($userAgent)
{
    $ua = strtolower($userAgent ?? '');

    if (strpos($ua, 'edg') !== false) {
        return 'Microsoft Edge';
    }

    if (strpos($ua, 'opr') !== false ||
        strpos($ua, 'opera') !== false) {
        return 'Opera';
    }

    if (strpos($ua, 'chrome') !== false) {
        return 'Google Chrome';
    }

    if (strpos($ua, 'firefox') !== false) {
        return 'Mozilla Firefox';
    }

    if (strpos($ua, 'safari') !== false) {
        return 'Safari';
    }

    return 'Unknown Browser';
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Login History - NexaVault</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>

body {

    min-height:100vh;

    margin:0;

    color:#e8eefc;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37,99,235,.18),
            transparent 35%
        ),

        radial-gradient(
            circle at 90% 80%,
            rgba(139,92,246,.15),
            transparent 35%
        ),

        linear-gradient(
            135deg,
            #020817,
            #07142f,
            #0a1838
        );

}


.history-page {

    max-width:1200px;

    margin:auto;

    padding:40px 20px 60px;

}


/* =====================================================
   HEADER
===================================================== */

.page-label {

    color:#6ea1ff;

    font-size:10px;

    font-weight:800;

    letter-spacing:2px;

}


.page-title {

    margin:5px 0 0;

    color:#ffffff;

    font-size:30px;

    font-weight:800;

}


.page-subtitle {

    color:#91a0bb;

    margin-top:5px;

}


/* =====================================================
   CARD
===================================================== */

.history-card {

    padding:25px;

    border-radius:22px;

    background:
        linear-gradient(
            145deg,
            rgba(15,31,63,.96),
            rgba(7,20,43,.96)
        );

    border:
        1px solid
        rgba(120,155,220,.14);

    box-shadow:
        0 25px 65px
        rgba(0,0,0,.30);

}


/* =====================================================
   STAT
===================================================== */

.stat-box {

    padding:18px;

    border-radius:15px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(255,255,255,.06);

}


.stat-box span {

    display:block;

    color:#7184a3;

    font-size:10px;

}


.stat-box strong {

    display:block;

    margin-top:4px;

    color:#ffffff;

    font-size:24px;

}


/* =====================================================
   HISTORY ITEM
===================================================== */

.history-item {

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    padding:17px;

    margin-top:12px;

    border-radius:15px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(255,255,255,.06);

}


.history-left {

    display:flex;

    align-items:center;

    gap:13px;

    min-width:0;

}


.status-icon {

    width:45px;

    height:45px;

    flex-shrink:0;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:13px;

    font-size:20px;

}


.success-icon {

    color:#34d399;

    background:
        rgba(16,185,129,.10);

}


.failed-icon {

    color:#f87171;

    background:
        rgba(239,68,68,.10);

}


.history-title {

    color:#ffffff;

    font-size:13px;

    font-weight:700;

}


.history-info {

    margin-top:4px;

    color:#7184a3;

    font-size:10px;

    line-height:1.7;

}


.history-time {

    color:#7184a3;

    font-size:10px;

    white-space:nowrap;

}


/* =====================================================
   EMPTY
===================================================== */

.empty-history {

    text-align:center;

    padding:70px 20px;

    color:#91a0bb;

}


.empty-history i {

    display:block;

    font-size:45px;

    color:#55739e;

    margin-bottom:15px;

}


/* =====================================================
   BUTTONS
===================================================== */

.btn-outline-light {

    border-color:
        rgba(255,255,255,.15);

    color:#cbd5e1;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px) {

    .history-item {

        align-items:flex-start;

        flex-direction:column;

    }

    .history-time {

        white-space:normal;

    }

}

</style>

</head>


<body>

<div class="history-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="d-flex
            justify-content-between
            align-items-start
            flex-wrap
            gap-3
            mb-4">

<div>

<span class="page-label">
ACCOUNT SECURITY
</span>

<h1 class="page-title">

<i class="bi bi-clock-history"></i>

Login History

</h1>

<p class="page-subtitle mb-0">

Review successful and failed login attempts.

</p>

</div>


<div class="d-flex gap-2">

<a
href="security_sessions.php"
class="btn btn-outline-light">

<i class="bi bi-shield-lock-fill"></i>

Active Sessions

</a>


<a
href="dashboard.php"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>

</div>

</div>


<!-- =====================================================
     STATS
===================================================== -->

<div class="row g-3 mb-4">

<div class="col-md-4">

<div class="stat-box">

<span>
TOTAL ATTEMPTS
</span>

<strong>
<?php echo $totalLogins; ?>
</strong>

</div>

</div>


<div class="col-md-4">

<div class="stat-box">

<span>
SUCCESSFUL
</span>

<strong
style="color:#34d399;">

<?php echo $successfulLogins; ?>

</strong>

</div>

</div>


<div class="col-md-4">

<div class="stat-box">

<span>
FAILED
</span>

<strong
style="color:#f87171;">

<?php echo $failedLogins; ?>

</strong>

</div>

</div>

</div>


<!-- =====================================================
     HISTORY
===================================================== -->

<div class="history-card">


<?php if (count($history) === 0) { ?>


<div class="empty-history">

<i class="bi bi-clock-history"></i>

<h5 class="text-white">
No Login History
</h5>

<p>
Your login attempts will appear here.
</p>

</div>


<?php } else { ?>


<?php foreach ($history as $row) { ?>

<?php

$isSuccess =
    $row['login_status'] === 'success';

$browser =
    detectBrowser(
        $row['user_agent']
    );

$device =
    htmlspecialchars(
        $row['device_name'] ?? 'Unknown Device',
        ENT_QUOTES
    );

$ip =
    htmlspecialchars(
        $row['ip_address'] ?? 'Unknown',
        ENT_QUOTES
    );

$email =
    htmlspecialchars(
        $row['email'] ?? '',
        ENT_QUOTES
    );

?>

<div class="history-item">


<div class="history-left">


<div class="status-icon
    <?php echo $isSuccess
        ? 'success-icon'
        : 'failed-icon'; ?>">

<?php if ($isSuccess) { ?>

<i class="bi bi-check-circle-fill"></i>

<?php } else { ?>

<i class="bi bi-x-circle-fill"></i>

<?php } ?>

</div>


<div>

<div class="history-title">

<?php echo $isSuccess
    ? 'Successful Login'
    : 'Failed Login'; ?>

</div>


<div class="history-info">

<i class="bi bi-person-fill"></i>

<?php echo $email; ?>

<br>

<i class="bi bi-laptop-fill"></i>

<?php echo $browser; ?>

&nbsp; • &nbsp;

<?php echo $device; ?>

<br>

<i class="bi bi-globe2"></i>

IP:
<?php echo $ip; ?>

</div>

</div>


</div>


<div class="history-time">

<?php

if (!empty($row['created_at'])) {

    echo date(
        "d M Y • h:i A",
        strtotime(
            $row['created_at']
        )
    );

}

?>

</div>


</div>

<?php } ?>


<?php } ?>


</div>


</div>


</body>

</html>