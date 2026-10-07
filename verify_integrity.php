<?php

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   CHECK FILE ID
===================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid File Request.");
}

$file_id = (int) $_GET['id'];


/* =====================================================
   GET USER'S FILE
===================================================== */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE id='$file_id'
     AND user_id='$user_id'
     AND deleted=0
     LIMIT 1"
);

if (!$result || mysqli_num_rows($result) === 0) {
    die("File not found or access denied.");
}

$file = mysqli_fetch_assoc($result);


/* =====================================================
   FILE DETAILS
===================================================== */

$fileName =
    $file['original_name'] ?? 'Unknown File';

$storedHash =
    strtolower(
        trim(
            $file['file_hash'] ?? ''
        )
    );

$storedFileName =
    basename(
        $file['file_name']
    );

$filePath =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    $storedFileName;


/* =====================================================
   CHECK PHYSICAL FILE
===================================================== */

$verificationStatus = "unknown";
$currentHash = "";
$message = "";
$hashAvailable = false;

if (
    !file_exists($filePath) ||
    !is_file($filePath)
) {

    $verificationStatus = "missing";

    $message =
        "The physical file could not be found on the server.";

} elseif ($storedHash === '') {

    $verificationStatus = "unavailable";

    $message =
        "No stored SHA-256 hash is available for this file.";

} else {

    /* =================================================
       CALCULATE CURRENT HASH
    ================================================= */

    $currentHash =
        hash_file(
            'sha256',
            $filePath
        );

    if ($currentHash === false) {

        $verificationStatus = "error";

        $message =
            "Unable to calculate the current file hash.";

    } else {

        $currentHash =
            strtolower(
                trim($currentHash)
            );

        $hashAvailable = true;


        /* =================================================
           COMPARE
        ================================================= */

        if (
            hash_equals(
                $storedHash,
                $currentHash
            )
        ) {

            $verificationStatus = "verified";

            $message =
                "The file integrity check passed successfully.";

        } else {

            $verificationStatus = "modified";

            $message =
                "The current file does not match its stored SHA-256 hash.";
        }
        /* =====================================================
   SECURITY ALERT - HASH MISMATCH
===================================================== */

$alertTitle =
    "File Integrity Mismatch";

$alertMessage =
    "The current SHA-256 hash of " .
    $fileName .
    " does not match the stored fingerprint.";

$alertTitleSafe =
    mysqli_real_escape_string(
        $conn,
        $alertTitle
    );

$alertMessageSafe =
    mysqli_real_escape_string(
        $conn,
        $alertMessage
    );


/* Prevent duplicate alerts for the same file */

$existingAlertQuery = mysqli_query(
    $conn,
    "SELECT id
     FROM security_alerts
     WHERE user_id='$user_id'
     AND alert_type='file_integrity_mismatch'
     AND title='$alertTitleSafe'
     AND message='$alertMessageSafe'
     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
     LIMIT 1"
);


if (
    $existingAlertQuery &&
    mysqli_num_rows($existingAlertQuery) === 0
) {

    mysqli_query(
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
            '$user_id',
            'file_integrity_mismatch',
            'high',
            '$alertTitleSafe',
            '$alertMessageSafe',
            0
        )"
    );
}
    }
}


/* =====================================================
   ACTIVITY LOG
===================================================== */

$activityMessage =
    "Integrity verification: " .
    $fileName .
    " - " .
    strtoupper(
        $verificationStatus
    );

$activitySafe =
    mysqli_real_escape_string(
        $conn,
        $activityMessage
    );

mysqli_query(
    $conn,
    "INSERT INTO activity_log
    (
        user_id,
        activity,
        created_at
    )
    VALUES
    (
        '$user_id',
        '$activitySafe',
        NOW()
    )"
);


/* =====================================================
   SAFE DISPLAY
===================================================== */

$safeFileName =
    htmlspecialchars(
        $fileName,
        ENT_QUOTES
    );

$safeStoredHash =
    htmlspecialchars(
        $storedHash ?: 'Not Available',
        ENT_QUOTES
    );

$safeCurrentHash =
    htmlspecialchars(
        $currentHash ?: 'Not Available',
        ENT_QUOTES
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Integrity Verification - NexaVault
</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


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

    padding: 30px 15px;

    font-family: "Segoe UI", Arial, sans-serif;

    color: #1e293b;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37,99,235,.08),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(139,92,246,.07),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #ffffff
        );

    background-attachment: fixed;
}


/* =====================================================
   MAIN CARD
===================================================== */

.verify-card {
    width: 100%;
    max-width: 820px;

    padding: 32px;

    border-radius: 24px;

    background: #ffffff;

    border: 1px solid #dbe5f1;

    box-shadow:
        0 25px 65px
        rgba(30,64,175,.10);
}


/* =====================================================
   HEADER ICON
===================================================== */

.verify-icon {
    width: 78px;
    height: 78px;

    margin: 0 auto 18px;

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
        rgba(37,99,235,.18);
}


/* =====================================================
   TITLE
===================================================== */

.verify-title {
    color: #172033;

    font-size: 28px;

    font-weight: 800;
}

.verify-subtitle {
    color: #64748b;
}


/* =====================================================
   FILE INFORMATION
===================================================== */

.file-info {
    margin-top: 20px;

    padding: 13px 15px;

    border-radius: 13px;

    background: #f8fbff;

    border: 1px solid #dbeafe;

    color: #475569;

    font-size: 12px;
}

.file-info strong {
    color: #1e293b;
}

.file-info i {
    color: #2563eb;
}


/* =====================================================
   RESULT BOX
===================================================== */

.result-box {
    margin-top: 25px;

    padding: 22px;

    border-radius: 18px;
}


/* =====================================================
   VERIFIED
===================================================== */

.result-verified {
    background: #ecfdf5;

    border:
        1px solid #bbf7d0;
}


/* =====================================================
   MODIFIED
===================================================== */

.result-modified {
    background: #fef2f2;

    border:
        1px solid #fecaca;
}


/* =====================================================
   MISSING / ERROR
===================================================== */

.result-missing,
.result-error,
.result-unavailable {
    background: #fffbeb;

    border:
        1px solid #fde68a;
}


/* =====================================================
   RESULT ICON
===================================================== */

.result-icon {
    font-size: 38px;

    flex-shrink: 0;
}


/* =====================================================
   RESULT HEADING
===================================================== */

.result-heading {
    color: #172033;

    font-size: 19px;

    font-weight: 750;
}


/* =====================================================
   RESULT MESSAGE
===================================================== */

.result-message {
    margin-top: 6px;

    color: #64748b;

    font-size: 13px;

    line-height: 1.7;
}


/* =====================================================
   HASH BOXES
===================================================== */

.hash-box {
    margin-top: 18px;

    padding: 16px;

    border-radius: 14px;

    background: #ffffff;

    border:
        1px solid #dbe5f1;

    box-shadow:
        0 4px 12px
        rgba(15,23,42,.04);
}


/* =====================================================
   HASH LABEL
===================================================== */

.hash-label {
    display: block;

    color: #2563eb;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.2px;

    margin-bottom: 7px;
}


/* =====================================================
   HASH VALUE
===================================================== */

.hash-value {
    display: block;

    color: #334155;

    font-size: 11px;

    line-height: 1.7;

    word-break: break-all;

    font-family:
        Consolas,
        "Courier New",
        monospace;
}


/* =====================================================
   HASH MATCH BADGE
===================================================== */

.match-badge {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 12px;

    padding: 6px 10px;

    border-radius: 9px;

    color: #047857;

    background: #dcfce7;

    border: 1px solid #bbf7d0;

    font-size: 10px;

    font-weight: 800;
}


/* =====================================================
   HASH WARNING BADGE
===================================================== */

.warning-badge {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 12px;

    padding: 6px 10px;

    border-radius: 9px;

    color: #b91c1c;

    background: #fee2e2;

    border: 1px solid #fecaca;

    font-size: 10px;

    font-weight: 800;
}


/* =====================================================
   BUTTONS
===================================================== */

.btn-primary {
    border: none !important;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        ) !important;

    color: #ffffff !important;

    font-weight: 600;

    box-shadow:
        0 5px 14px
        rgba(37,99,235,.16);
}

.btn-primary:hover {
    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        ) !important;

    transform: translateY(-1px);
}


/* =====================================================
   DASHBOARD BUTTON
===================================================== */

.btn-outline-light {
    border:
        1px solid #cbd5e1 !important;

    color: #334155 !important;

    background: #ffffff !important;

    font-weight: 600;
}

.btn-outline-light:hover {
    border-color: #93c5fd !important;

    color: #1d4ed8 !important;

    background: #eff6ff !important;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:576px) {

    body {
        padding: 20px 12px;
    }

    .verify-card {
        padding: 22px;

        border-radius: 18px;
    }

    .verify-title {
        font-size: 23px;
    }

    .verify-subtitle {
        font-size: 13px;
    }

    .result-box {
        padding: 17px;
    }

    .result-heading {
        font-size: 17px;
    }

    .result-message {
        font-size: 12px;
    }

    .hash-value {
        font-size: 10px;
    }

}


/* =====================================================
   PRINT FRIENDLY
===================================================== */

@media print {

    body {
        background: #ffffff !important;

        color: #000000 !important;

        display: block;
    }

    .verify-card {
        max-width: 100%;

        border: 1px solid #cccccc;

        box-shadow: none;
    }

    .verify-title,
    .result-heading,
    .file-info strong {
        color: #000000 !important;
    }

    .verify-subtitle,
    .result-message,
    .file-info {
        color: #555555 !important;
    }

    .btn-outline-light,
    .btn-primary {
        display: none !important;
    }

    .hash-box {
        box-shadow: none;

        border-color: #cccccc;
    }

    .hash-value {
        color: #222222 !important;
    }

    .result-verified {
        background: #f0fdf4 !important;

        border-color: #bbf7d0 !important;
    }

    .result-modified {
        background: #fef2f2 !important;

        border-color: #fecaca !important;
    }

}

</style>
</head>


<body>


<div class="verify-card">


<div class="text-center">

<div class="verify-icon">

<i class="bi bi-shield-check"></i>

</div>


<h1 class="verify-title">

File Integrity Verification

</h1>


<p class="verify-subtitle mb-0">

Verify whether your stored file still matches
its original SHA-256 fingerprint.

</p>

</div>


<!-- =====================================================
     FILE INFORMATION
===================================================== -->

<div class="file-info">

<strong>
<i class="bi bi-file-earmark-fill"></i>
File:
</strong>

<?php echo $safeFileName; ?>

</div>


<!-- =====================================================
     RESULT
===================================================== -->

<?php

$resultClass = "";

$resultIcon = "";

$resultTitle = "";


switch ($verificationStatus) {

    case "verified":

        $resultClass =
            "result-verified";

        $resultIcon =
            "bi-check-circle-fill text-success";

        $resultTitle =
            "File Integrity Verified";

        break;


    case "modified":

        $resultClass =
            "result-modified";

        $resultIcon =
            "bi-exclamation-triangle-fill text-danger";

        $resultTitle =
            "Integrity Warning";

        break;


    case "missing":

        $resultClass =
            "result-missing";

        $resultIcon =
            "bi-file-earmark-x-fill text-warning";

        $resultTitle =
            "File Not Found";

        break;


    case "unavailable":

        $resultClass =
            "result-unavailable";

        $resultIcon =
            "bi-question-circle-fill text-warning";

        $resultTitle =
            "Hash Verification Unavailable";

        break;


    default:

        $resultClass =
            "result-error";

        $resultIcon =
            "bi-exclamation-circle-fill text-warning";

        $resultTitle =
            "Verification Error";

        break;
}

?>


<div class="result-box <?php echo $resultClass; ?>">


<div class="d-flex
            align-items-start
            gap-3">


<i class="bi <?php echo $resultIcon; ?> result-icon"></i>


<div>

<h3 class="result-heading">

<?php echo $resultTitle; ?>

</h3>


<p class="result-message mb-0">

<?php echo htmlspecialchars($message); ?>

</p>


<?php if ($verificationStatus === "verified") { ?>

<span class="match-badge">

<i class="bi bi-check-circle-fill"></i>

HASH MATCH

</span>

<?php } elseif ($verificationStatus === "modified") { ?>

<span class="warning-badge">

<i class="bi bi-shield-exclamation"></i>

HASH MISMATCH

</span>

<?php } ?>


</div>

</div>


<?php if ($hashAvailable) { ?>


<!-- STORED HASH -->

<div class="hash-box">

<span class="hash-label">

STORED SHA-256

</span>

<span class="hash-value">

<?php echo $safeStoredHash; ?>

</span>

</div>


<!-- CURRENT HASH -->

<div class="hash-box">

<span class="hash-label">

CURRENT SHA-256

</span>

<span class="hash-value">

<?php echo $safeCurrentHash; ?>

</span>

</div>


<?php } ?>


</div>


<!-- =====================================================
     ACTIONS
===================================================== -->

<div class="d-flex
            justify-content-center
            gap-2
            flex-wrap
            mt-4">


<a
href="dashboard.php"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>


<a
href="preview.php?id=<?php echo $file_id; ?>"
class="btn btn-primary">

<i class="bi bi-eye"></i>

File Preview

</a>


</div>


</div>


</body>

</html>