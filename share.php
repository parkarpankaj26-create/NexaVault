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
    die("Invalid Request");
}

$id = (int) $_GET['id'];


/* =====================================================
   GET USER'S FILE
   IMPORTANT:
   Do NOT check is_shared here.
   Even expired share should allow a new link.
===================================================== */

$result = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE id='$id'
     AND user_id='$user_id'
     AND deleted=0
     LIMIT 1"
);

if (!$result || mysqli_num_rows($result) === 0) {
    die("File not found or access denied.");
}

$file = mysqli_fetch_assoc($result);


/* =====================================================
   VARIABLES
===================================================== */

$success = false;
$error = "";
$link = "";


/* =====================================================
   GENERATE NEW SHARE LINK
===================================================== */

if (isset($_POST['generate'])) {

    $share_password = $_POST['share_password'] ?? '';

    $download_limit = isset($_POST['download_limit'])
        ? (int) $_POST['download_limit']
        : 3;

    $expires_at = $_POST['expires_at'] ?? '';


    /* =================================================
       PASSWORD VALIDATION
    ================================================= */

    if (strlen($share_password) < 6) {

        $error =
            "Password must be at least 6 characters.";

    }


    /* =================================================
       DOWNLOAD LIMIT VALIDATION
    ================================================= */

    elseif (!in_array(
        $download_limit,
        [0, 1, 3, 5],
        true
    )) {

        $error =
            "Invalid download limit.";

    }


    else {

        /* =================================================
           GENERATE SECURE TOKEN
        ================================================= */

        $token =
            bin2hex(
                random_bytes(32)
            );


        /* =================================================
           HASH PASSWORD
        ================================================= */

        $hashed_password =
            password_hash(
                $share_password,
                PASSWORD_DEFAULT
            );


        /* =================================================
           EXPIRY
        ================================================= */

        if ($expires_at === '') {

            $expires_at_sql = "NULL";

        } else {

            $expires_at =
                mysqli_real_escape_string(
                    $conn,
                    $expires_at
                );

            $expires_at_sql =
                "'$expires_at'";
        }


        /* =================================================
           TOKEN ESCAPE
        ================================================= */

        $token_safe =
            mysqli_real_escape_string(
                $conn,
                $token
            );


        /* =================================================
           UPDATE FILE
        ================================================= */

        $updateQuery = "
            UPDATE files
            SET
                share_token='$token_safe',
                share_password='$hashed_password',
                is_shared=1,
                link_used=0,
                download_limit='$download_limit',
                expires_at=$expires_at_sql
            WHERE id='$id'
            AND user_id='$user_id'
        ";


        if (mysqli_query($conn, $updateQuery)) {


            /* =================================================
               ACTIVITY LOG
            ================================================= */

            if (file_exists("includes/activity.php")) {

                include 'includes/activity.php';

                logActivity(
                    $conn,
                    $user_id,
                    "Created new secure share link: " .
                    $file['original_name']
                );
            }


            /* =================================================
               NOTIFICATION
            ================================================= */

            if (file_exists("includes/notification.php")) {

                include 'includes/notification.php';

                addNotification(
                    $conn,
                    $user_id,
                    "🔗 New secure share link created: " .
                    $file['original_name']
                );
            }


            /* =================================================
               GENERATE SHARE URL
            ================================================= */

            $link =
                "http://10.24.29.39/NexaVault/view_share.php?token="
                . $token;


            $success = true;


        } else {

            $error =
                "Unable to create secure share link.";
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Share File - NexaVault</title>


<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
rel="stylesheet">


<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<script
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</script>


<style>


body {
    min-height: 100vh;
    background:
        radial-gradient(circle at 10% 20%, rgba(37, 99, 235, .06), transparent 35%),
        radial-gradient(circle at 90% 80%, rgba(139, 92, 246, .06), transparent 35%),
        linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #ffffff 100%);
    color: #1e293b;
}

.share-container {
    max-width: 750px;
    margin: 60px auto;
    padding: 0 15px;
}

.share-card {
    border: 1px solid #e2e8f0;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 15px 45px rgba(15, 23, 42, .08);
    color: #1e293b;
}

.file-icon {
    font-size: 65px;
    color: #2563eb;
}

.file-name {
    word-break: break-word;
    color: #1e293b;
}

.alert-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    color: #1e40af;
}

.alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
}

.alert-danger {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
}

.form-label {
    color: #334155;
}

.form-control,
.form-select {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #1e293b;
    border-radius: 12px;
    padding: 12px 14px;
}

.form-control::placeholder {
    color: #94a3b8;
}

.form-control:focus,
.form-select:focus {
    background: #ffffff;
    color: #1e293b;
    border-color: #3b82f6;
    box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .12);
}

.form-select option {
    background: #ffffff;
    color: #1e293b;
}

.btn-primary {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    border: none;
    border-radius: 12px;
    color: #ffffff;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #1d4ed8, #6d28d9);
    color: #ffffff;
}

.btn-success,
.btn-danger {
    border-radius: 12px;
}

.btn-outline-secondary {
    border-color: #cbd5e1;
    color: #475569;
    border-radius: 12px;
}

.btn-outline-secondary:hover {
    background: #f1f5f9;
    color: #1e293b;
    border-color: #94a3b8;
}

.qr-box {
    display: inline-block;
    padding: 20px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
}

small,
.form-text {
    color: #64748b;
}

@media (max-width: 576px) {
    .share-container {
        margin: 25px auto;
    }
}
</style>

</head>


<body>


<div class="container share-container">

<div class="card shadow-lg p-4 share-card">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="text-center mb-4">

<i class="bi bi-share-fill text-primary file-icon"></i>

<h2 class="fw-bold mt-3">

Share Your File

</h2>

<p class="text-muted">

Generate a new secure password-protected sharing link.

</p>

</div>


<!-- =====================================================
     FILE INFORMATION
===================================================== -->

<div class="alert alert-info">

<div class="d-flex align-items-center">

<i class="bi bi-file-earmark-fill fs-3 me-3"></i>

<div>

<strong>File</strong>

<div class="file-name">

<?php

echo htmlspecialchars(
    $file['original_name']
);

?>

</div>

</div>

</div>

</div>


<!-- =====================================================
     ERROR
===================================================== -->

<?php if ($error !== "") { ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-triangle-fill"></i>

<?php

echo htmlspecialchars($error);

?>

</div>

<?php } ?>


<!-- =====================================================
     SUCCESS
===================================================== -->

<?php if ($success) { ?>

<div class="alert alert-success">

<h5 class="fw-bold">

<i class="bi bi-check-circle-fill"></i>

New Secure Link Generated

</h5>


<p>

Your previous sharing link has been replaced
with this new secure link.

</p>


<input
type="text"
class="form-control"
id="shareLink"
value="<?php echo htmlspecialchars($link); ?>"
readonly>


<button
type="button"
class="btn btn-success w-100 mt-3"
onclick="copyLink()">

<i class="bi bi-clipboard"></i>

Copy Secure Link

</button>


<!-- QR -->
<div class="text-center mt-4">

    <h6 class="fw-bold">
        <i class="bi bi-qr-code"></i>
        Scan QR Code
    </h6>

    <div class="qr-box mt-3">
        <div id="qrcode"></div>
    </div>

    <p class="text-muted small mt-2">
        Scan this QR code to access the secure file.
    </p>

</div>

</div>


<!-- ACTIONS -->

<div class="d-grid gap-2 mt-4">


<a
href="<?php echo htmlspecialchars($link); ?>"
target="_blank"
class="btn btn-primary">

<i class="bi bi-box-arrow-up-right"></i>

Open Secure Link

</a>


<a
href="https://wa.me/?text=<?php echo urlencode($link); ?>"
target="_blank"
class="btn btn-success">

<i class="bi bi-whatsapp"></i>

Share on WhatsApp

</a>


<a
href="mailto:?subject=NexaVault%20Secure%20File&body=<?php echo urlencode($link); ?>"
class="btn btn-danger">

<i class="bi bi-envelope-fill"></i>

Share by Email

</a>


<a
href="shared_files.php"
class="btn btn-outline-secondary">

<i class="bi bi-share-fill"></i>

Back to Shared Files

</a>


</div>


</div>


<?php } else { ?>


<!-- =====================================================
     SHARE FORM
===================================================== -->

<form method="POST">


<div class="mb-4">

<label class="form-label fw-bold">

<i class="bi bi-lock-fill"></i>

Set Share Password

</label>


<input
type="password"
name="share_password"
class="form-control"
placeholder="Enter password"
required
minlength="6"
autocomplete="new-password">


<small>
Minimum 6 characters.
</small>

</div>


<!-- DOWNLOAD LIMIT -->

<div class="mb-4">

<label class="form-label fw-bold">

<i class="bi bi-download"></i>

Download Limit

</label>


<select
name="download_limit"
class="form-select"
required>

<option value="1">
1 Download
</option>

<option value="3" selected>
3 Downloads
</option>

<option value="5">
5 Downloads
</option>

<option value="0">
Unlimited Downloads
</option>

</select>


<small>
Control how many times the shared file
can be downloaded.
</small>

</div>


<!-- EXPIRY -->

<div class="mb-4">

<label class="form-label fw-bold">

<i class="bi bi-clock-history"></i>

Link Expiry

</label>


<select
name="expires_at"
class="form-select">

<option value="">
Never Expires
</option>


<option value="<?php
echo date(
    'Y-m-d H:i:s',
    strtotime('+1 hour')
);
?>">

1 Hour

</option>


<option value="<?php
echo date(
    'Y-m-d H:i:s',
    strtotime('+1 day')
);
?>">

1 Day

</option>


<option value="<?php
echo date(
    'Y-m-d H:i:s',
    strtotime('+7 days')
);
?>">

7 Days

</option>

</select>


<small>
Choose how long this sharing link
should remain active.
</small>

</div>


<button
type="submit"
name="generate"
class="btn btn-primary btn-lg w-100">

<i class="bi bi-shield-lock-fill"></i>

Generate New Secure Link

<i class="bi bi-arrow-right"></i>

</button>


</form>


<?php } ?>


</div>

</div>


<script>

function copyLink() {

    const shareLink =
        document.getElementById("shareLink");

    if (!shareLink) {
        return;
    }

    shareLink.select();
    shareLink.setSelectionRange(0, 99999);

    navigator.clipboard.writeText(
        shareLink.value
    ).then(function () {

        alert("✅ Secure link copied!");

    }).catch(function () {

        document.execCommand("copy");

        alert("✅ Secure link copied!");

    });

}


/* =====================================================
   QR CODE
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const qrContainer =
            document.getElementById("qrcode");

        if (!qrContainer) {
            return;
        }

        /* Clear old QR */
        qrContainer.innerHTML = "";

        const link =
            <?php echo json_encode($link); ?>;

        if (!link) {
            console.log("Share link not available.");
            return;
        }

        if (typeof QRCode === "undefined") {

            qrContainer.innerHTML =
                "<p style='color:red;'>QR library failed to load.</p>";

            console.log(
                "QRCode library is not loaded."
            );

            return;
        }


        new QRCode(
            qrContainer,
            {
                text: link,
                width: 180,
                height: 180,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel:
                    QRCode.CorrectLevel.H
            }
        );

    }
);

</script>


</body>

</html>