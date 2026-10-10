<?php

session_start();

include 'includes/db.php';


/* =====================================================
   CHECK SHARE TOKEN
===================================================== */

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing share link.");
}

$token = trim($_GET['token']);

$tokenSafe = mysqli_real_escape_string(
    $conn,
    $token
);


/* =====================================================
   GET SHARED FILE
===================================================== */

$query = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE share_token='$tokenSafe'
     AND is_shared=1
     AND deleted=0
     LIMIT 1"
);

if (!$query || mysqli_num_rows($query) === 0) {
    die("This share link is invalid or no longer available.");
}

$file = mysqli_fetch_assoc($query);


/* =====================================================
   FILE DETAILS
===================================================== */

$fileId = (int) $file['id'];

$ownerId = (int) $file['user_id'];

$fileName = $file['original_name'] ?? 'Shared File';

$fileType = $file['file_type'] ?? 'application/octet-stream';

$fileSize = round(
    ((int)($file['file_size'] ?? 0)) / 1024,
    2
);

$sharePassword = $file['share_password'] ?? '';

$downloadLimit = (int)($file['download_limit'] ?? 0);

$currentDownloads = (int)($file['link_used'] ?? 0);

$expiresAt = $file['expires_at'] ?? null;


/* =====================================================
   CHECK EXPIRY
===================================================== */

if (
    $expiresAt !== null &&
    trim((string)$expiresAt) !== '' &&
    strtotime($expiresAt) <= time()
) {

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Share Link Expired</title>
            <style>
                body{
                    margin:0;
                    min-height:100vh;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#020817;
                    color:#e8eefc;
                    font-family:Segoe UI,Arial,sans-serif;
                }
                .box{
                    text-align:center;
                    padding:40px;
                }
                h2{color:#f87171;}
                p{color:#94a3b8;}
            </style>
        </head>
        <body>
            <div class='box'>
                <h2>🔒 Share Link Expired</h2>
                <p>This file sharing link has expired.</p>
            </div>
        </body>
        </html>
    ");

    exit();
}


/* =====================================================
   CHECK DOWNLOAD LIMIT BEFORE PASSWORD
===================================================== */

if (
    $downloadLimit > 0 &&
    $currentDownloads >= $downloadLimit
) {

    die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Download Limit Reached</title>
            <style>
                body{
                    margin:0;
                    min-height:100vh;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    background:#020817;
                    color:#e8eefc;
                    font-family:Segoe UI,Arial,sans-serif;
                }
                .box{
                    text-align:center;
                    padding:40px;
                }
                h2{color:#f87171;}
                p{color:#94a3b8;}
            </style>
        </head>
        <body>
            <div class='box'>
                <h2>🚫 Download Limit Reached</h2>
                <p>
                    This share link has reached its maximum
                    download limit.
                </p>
            </div>
        </body>
        </html>
    ");

    exit();
}


/* =====================================================
   TOKEN-SPECIFIC SESSION
===================================================== */

$sessionKey =
    "shared_token_" .
    hash('sha256', $token);

$authenticated = false;

if (
    isset($_SESSION[$sessionKey]) &&
    $_SESSION[$sessionKey] === true
) {
    $authenticated = true;
}


/* =====================================================
   PASSWORD SUBMISSION
===================================================== */

$error = "";

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['share_password']) &&
    !isset($_POST['download_file'])
) {

    $enteredPassword =
        $_POST['share_password'] ?? '';


    if (
        empty($sharePassword) ||
        password_verify(
            $enteredPassword,
            $sharePassword
        )
    ) {

        $_SESSION[$sessionKey] = true;

        $authenticated = true;

    } else {

        $error =
            "Incorrect password. Please try again.";
    }
}


/* =====================================================
   DOWNLOAD FILE
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['download_file']) &&
    $authenticated
) {

    /* -------------------------------------------------
       GET FRESH FILE DATA
    ------------------------------------------------- */

    $downloadQuery = mysqli_query(
        $conn,
        "SELECT
            id,
            user_id,
            original_name,
            file_name,
            file_type,
            file_size,
            is_shared,
            deleted,
            COALESCE(link_used, 0) AS link_used,
            COALESCE(download_limit, 0) AS download_limit,
            expires_at
         FROM files
         WHERE id='$fileId'
         LIMIT 1"
    );

    if (!$downloadQuery) {

        die(
            "Database Error: " .
            htmlspecialchars(mysqli_error($conn))
        );
    }

    if (mysqli_num_rows($downloadQuery) === 0) {

        die("Share file record not found.");
    }

    $downloadFile =
        mysqli_fetch_assoc($downloadQuery);


    /* -------------------------------------------------
       STATUS CHECK
    ------------------------------------------------- */

    if ((int)$downloadFile['is_shared'] !== 1) {

        die("🚫 This share link is no longer active.");
    }

    if ((int)$downloadFile['deleted'] === 1) {

        die("🚫 This file has been deleted.");
    }


    /* -------------------------------------------------
       EXPIRY CHECK
    ------------------------------------------------- */

    $downloadExpiry =
        $downloadFile['expires_at'];

    if (
        $downloadExpiry !== null &&
        trim((string)$downloadExpiry) !== '' &&
        strtotime($downloadExpiry) <= time()
    ) {

        die("⏰ This share link has expired.");
    }


    /* -------------------------------------------------
       DOWNLOAD LIMIT
    ------------------------------------------------- */

    $used =
        (int)$downloadFile['link_used'];

    $limit =
        (int)$downloadFile['download_limit'];


    if (
        $limit > 0 &&
        $used >= $limit
    ) {

        die(
            "🚫 Download limit reached. Used: " .
            $used .
            " / " .
            $limit
        );
    }


    /* -------------------------------------------------
       FILE PATH
    ------------------------------------------------- */

    $storedFileName =
        basename(
            $downloadFile['file_name']
        );

    $filePath =
        __DIR__ .
        DIRECTORY_SEPARATOR .
        "uploads" .
        DIRECTORY_SEPARATOR .
        $storedFileName;


    if (
        !file_exists($filePath) ||
        !is_file($filePath)
    ) {

        die(
            "❌ File not found on server.<br><br>" .
            "Expected file:<br>" .
            htmlspecialchars($storedFileName)
        );
    }


    /* -------------------------------------------------
       UPDATE DOWNLOAD COUNT
    ------------------------------------------------- */

    if ($limit > 0) {

        $updateQuery = mysqli_query(
            $conn,
            "UPDATE files
             SET
                link_used = COALESCE(link_used, 0) + 1,
                downloaded = COALESCE(downloaded, 0) + 1
             WHERE id='$fileId'
             AND is_shared=1
             AND deleted=0
             AND (
                 expires_at IS NULL
                 OR expires_at=''
                 OR expires_at > NOW()
             )
             AND COALESCE(link_used, 0) < download_limit"
        );

    } else {

        $updateQuery = mysqli_query(
            $conn,
            "UPDATE files
             SET
                link_used = COALESCE(link_used, 0) + 1,
                downloaded = COALESCE(downloaded, 0) + 1
             WHERE id='$fileId'
             AND is_shared=1
             AND deleted=0
             AND (
                 expires_at IS NULL
                 OR expires_at=''
                 OR expires_at > NOW()
             )"
        );
    }


    /* -------------------------------------------------
       DATABASE UPDATE ERROR
    ------------------------------------------------- */

    if (!$updateQuery) {

        die(
            "❌ Download update failed:<br><br>" .
            htmlspecialchars(mysqli_error($conn))
        );
    }


    /* -------------------------------------------------
       VERIFY COUNT
    ------------------------------------------------- */

    $verifyQuery = mysqli_query(
        $conn,
        "SELECT
            COALESCE(link_used, 0) AS used_count,
            COALESCE(download_limit, 0) AS max_limit
         FROM files
         WHERE id='$fileId'
         LIMIT 1"
    );


    if (!$verifyQuery) {

        die(
            "❌ Unable to verify download count:<br><br>" .
            htmlspecialchars(mysqli_error($conn))
        );
    }


    $verify =
        mysqli_fetch_assoc($verifyQuery);


    $newUsed =
        (int)$verify['used_count'];

    $newLimit =
        (int)$verify['max_limit'];


    /* -------------------------------------------------
       ACTIVITY LOG
    ------------------------------------------------- */

    $safeActivityFile =
        mysqli_real_escape_string(
            $conn,
            $downloadFile['original_name']
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
            '$ownerId',
            'Shared file downloaded: $safeActivityFile',
            NOW()
        )"
    );


    /* -------------------------------------------------
       DOWNLOAD HISTORY
    ------------------------------------------------- */

    $historyTable =
        mysqli_query(
            $conn,
            "SHOW TABLES LIKE 'download_history'"
        );

    if (
        $historyTable &&
        mysqli_num_rows($historyTable) > 0
    ) {

        $safeHistoryName =
            mysqli_real_escape_string(
                $conn,
                $downloadFile['original_name']
            );

        mysqli_query(
            $conn,
            "INSERT INTO download_history
            (
                user_id,
                file_name
            )
            VALUES
            (
                '$ownerId',
                '$safeHistoryName'
            )"
        );
    }


    /* -------------------------------------------------
       MIME TYPE
    ------------------------------------------------- */

    $finfo =
        finfo_open(FILEINFO_MIME_TYPE);

    if ($finfo !== false) {

        $mimeType =
            finfo_file(
                $finfo,
                $filePath
            );

        finfo_close($finfo);

    } else {

        $mimeType =
            "application/octet-stream";
    }


    if (!$mimeType) {

        $mimeType =
            "application/octet-stream";
    }


    /* -------------------------------------------------
       DOWNLOAD
    ------------------------------------------------- */

    $downloadName =
        str_replace(
            ['"', "\r", "\n"],
            '',
            basename(
                $downloadFile['original_name']
            )
        );

    $size =
        filesize($filePath);


    header(
        "Content-Description: File Transfer"
    );

    header(
        "Content-Type: " . $mimeType
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $downloadName .
        '"'
    );

    header(
        "Content-Length: " . $size
    );

    header(
        "Cache-Control: no-cache, must-revalidate"
    );

    header(
        "Pragma: public"
    );

    header("Expires: 0");


    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    flush();

    readfile($filePath);

    exit();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>NexaVault - Secure File</title>


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
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0;
    padding: 20px;
    font-family: "Segoe UI", Arial, sans-serif;

    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(37, 99, 235, .06),
            transparent 30%
        ),
        radial-gradient(
            circle at 80% 80%,
            rgba(124, 92, 255, .06),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #f8fafc,
            #f1f5f9,
            #ffffff
        );

    color: #1e293b;
}

.share-card {
    width: 100%;
    max-width: 520px;
    padding: 35px;
    border-radius: 24px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 20px 55px rgba(15, 23, 42, .10);
}

.logo-icon {
    width: 75px;
    height: 75px;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;

    background: linear-gradient(
        135deg,
        #3b82f6,
        #6366f1
    );

    color: #ffffff;
    font-size: 34px;

    box-shadow: 0 10px 25px rgba(59, 130, 246, .20);
}

h2 {
    color: #0f172a;
    font-weight: 700;
}

.subtitle {
    color: #64748b;
}

.file-box {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px;
    margin: 25px 0;
    border-radius: 15px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.file-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 12px;
    background: #dbeafe;
    color: #2563eb;
    font-size: 23px;
}

.file-name {
    font-weight: 600;
    color: #1e293b;
    word-break: break-word;
}

.file-info {
    color: #64748b;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.form-label {
    color: #334155;
    font-weight: 600;
}

.form-control {
    background: #ffffff;
    color: #1e293b;
    border: 1px solid #cbd5e1;
    border-radius: 12px;
    min-height: 48px;
    padding: 12px 14px;
}

.form-control::placeholder {
    color: #94a3b8;
}

.form-control:focus {
    background: #ffffff;
    color: #1e293b;
    border-color: #4d7cff;
    box-shadow: 0 0 0 3px rgba(77, 124, 255, .12);
}

.btn-primary {
    min-height: 48px;
    border: none;
    border-radius: 12px;
    color: #ffffff;

    background: linear-gradient(
        135deg,
        #3b82f6,
        #6366f1
    );
}

.btn-primary:hover {
    background: linear-gradient(
        135deg,
        #2563eb,
        #4f46e5
    );
    color: #ffffff;
}

.btn-success {
    min-height: 48px;
    border-radius: 12px;
}

.alert-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 12px;
}

.success-box {
    padding: 20px;
    border-radius: 15px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
}

.success-box h5 {
    color: #166534 !important;
}

.success-box p {
    color: #475569 !important;
}

.download-info {
    margin-top: 10px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}

.download-info strong {
    color: #2563eb;
}

.security {
    text-align: center;
    margin-top: 20px;
    color: #64748b;
    font-size: 13px;
}

@media (max-width: 576px) {
    .share-card {
        padding: 25px 20px;
    }
}
    </style>

</head>


<body>


<div class="share-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="text-center">


<div class="logo-icon">

<i class="bi bi-shield-lock-fill"></i>

</div>


<h2 class="mt-4">

NexaVault Secure File

</h2>


<p class="subtitle">

You have received a secure file.

</p>


</div>


<!-- =====================================================
     FILE DETAILS
===================================================== -->

<div class="file-box">


<div class="file-icon">

<i class="bi bi-file-earmark-fill"></i>

</div>


<div>


<div class="file-name">

<?php

echo htmlspecialchars(
    $fileName
);

?>

</div>


<div class="file-info">

<?php

echo htmlspecialchars(
    $fileType
);

?>

&nbsp; • &nbsp;

<?php

echo $fileSize;

?>

KB

</div>


</div>

</div>


<!-- =====================================================
     PASSWORD FORM
===================================================== -->

<?php if (!$authenticated) { ?>


<form method="POST">


<div class="mb-3">


<label class="form-label">

<i class="bi bi-lock-fill"></i>

Enter Share Password

</label>


<input
type="password"
name="share_password"
class="form-control"
placeholder="Enter password"
required
autocomplete="off">


</div>


<?php if (!empty($error)) { ?>


<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?php

echo htmlspecialchars(
    $error
);

?>

</div>


<?php } ?>


<button
type="submit"
class="btn btn-primary w-100">

<i class="bi bi-unlock-fill"></i>

Unlock File

</button>


</form>


<?php } else { ?>


<!-- =====================================================
     UNLOCKED
===================================================== -->

<div class="success-box">


<div class="text-center mb-3">


<i
class="bi bi-check-circle-fill text-success"
style="font-size:45px;">
</i>


<h5 class="mt-2 text-white">

File Unlocked

</h5>


<p class="text-secondary mb-0">

Your password has been verified successfully.

</p>


</div>


<form method="POST">


<button
type="submit"
name="download_file"
class="btn btn-success w-100">

<i class="bi bi-download"></i>

Download File

</button>


</form>


<?php if ($downloadLimit > 0) { ?>

<div class="download-info">

Downloads:
<strong>
<?php echo $currentDownloads; ?>
/
<?php echo $downloadLimit; ?>
</strong>

</div>

<?php } else { ?>

<div class="download-info">

Downloads:
<strong>
Unlimited
</strong>

</div>

<?php } ?>


</div>


<?php } ?>


<!-- =====================================================
     SECURITY FOOTER
===================================================== -->

<div class="security">

<i class="bi bi-shield-check"></i>

Protected by NexaVault Secure Sharing

</div>


</div>


</body>

</html>