<?php

session_start();

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user_id'])
) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user'];

$message = "";
$message_type = "";

$generatedLink = "";


/* =====================================================
   CREATE FILE DROP
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['create_drop'])
) {

    $dropName =
        trim($_POST['drop_name'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $maxFiles =
        isset($_POST['max_files'])
        ? (int)$_POST['max_files']
        : 1;

    $expiryHours =
        isset($_POST['expiry_hours'])
        ? (int)$_POST['expiry_hours']
        : 24;


    /* =================================================
       VALIDATION
    ================================================= */

    if ($dropName === '') {

        $message =
            "Drop name is required.";

        $message_type =
            "danger";

    } elseif (
        strlen($dropName) > 150
    ) {

        $message =
            "Drop name is too long.";

        $message_type =
            "danger";

    } elseif (
        $maxFiles < 1 ||
        $maxFiles > 50
    ) {

        $message =
            "Maximum files must be between 1 and 50.";

        $message_type =
            "danger";

    } elseif (
        $expiryHours < 1 ||
        $expiryHours > 168
    ) {

        $message =
            "Expiry must be between 1 and 168 hours.";

        $message_type =
            "danger";

    } else {


        /* =============================================
           GENERATE SECURE TOKEN
        ============================================= */

        $dropToken =
            bin2hex(
                random_bytes(32)
            );


        $dropNameSafe =
            mysqli_real_escape_string(
                $conn,
                $dropName
            );

        $descriptionSafe =
            mysqli_real_escape_string(
                $conn,
                $description
            );

        $tokenSafe =
            mysqli_real_escape_string(
                $conn,
                $dropToken
            );


        /* =============================================
           EXPIRY
        ============================================= */

        $expiresAt =
            date(
                "Y-m-d H:i:s",
                time() +
                ($expiryHours * 60 * 60)
            );


        /* =============================================
           INSERT
        ============================================= */

        $insertQuery =
            mysqli_query(
                $conn,
                "INSERT INTO file_drops
                (
                    user_id,
                    drop_token,
                    drop_name,
                    description,
                    max_files,
                    uploaded_files,
                    expires_at,
                    is_active
                )
                VALUES
                (
                    '$user_id',
                    '$tokenSafe',
                    '$dropNameSafe',
                    '$descriptionSafe',
                    '$maxFiles',
                    0,
                    '$expiresAt',
                    1
                )"
            );


        if (!$insertQuery) {

            $message =
                "Unable to create file drop: " .
                mysqli_error($conn);

            $message_type =
                "danger";

        } else {


            /* =========================================
               GENERATE SHARING LINK
            ========================================= */

            $generatedLink =
                "drop_upload.php?token=" .
                urlencode($dropToken);


            $message =
                "File Drop created successfully.";

            $message_type =
                "success";


            /* =========================================
               ACTIVITY LOG
            ========================================= */

            if (
                file_exists(
                    "includes/activity.php"
                )
            ) {

                include_once
                    "includes/activity.php";


                if (
                    function_exists(
                        "logActivity"
                    )
                ) {

                    logActivity(
                        $conn,
                        $user_id,
                        "Created File Drop: " .
                        $dropName
                    );
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

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Create File Drop - NexaVault
</title>


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
    font-family: "Segoe UI", Arial, sans-serif;
    color: #1f2937;
    background:
        radial-gradient(
            circle at 10% 15%,
            rgba(37, 99, 235, 0.07),
            transparent 32%
        ),
        radial-gradient(
            circle at 90% 85%,
            rgba(99, 102, 241, 0.06),
            transparent 32%
        ),
        linear-gradient(
            135deg,
            #f8fafc,
            #eef4ff,
            #f8fafc
        );
}

/* =========================
   MAIN PAGE
========================= */

.drop-page {
    width: 100%;
    max-width: 720px;
    margin: auto;
    padding: 45px 20px 60px;
}

/* =========================
   NAVIGATION
========================= */

.nav-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.btn-outline-light {
    border: 1px solid #cbd5e1 !important;
    color: #334155 !important;
    background: #ffffff !important;
    border-radius: 10px;
    font-weight: 600;
    padding: 8px 13px;
    transition: 0.2s ease;
}

.btn-outline-light:hover {
    background: #f1f5f9 !important;
    border-color: #94a3b8 !important;
    color: #1e293b !important;
}

/* =========================
   MAIN CARD
========================= */

.drop-card {
    padding: 32px;
    border-radius: 22px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 15px 40px rgba(15, 23, 42, 0.08);
}

/* =========================
   ICON
========================= */

.drop-icon {
    width: 72px;
    height: 72px;
    margin-bottom: 20px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 20px;

    font-size: 30px;
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    box-shadow:
        0 12px 28px rgba(37, 99, 235, 0.20);
}

/* =========================
   PAGE TEXT
========================= */

.page-label {
    color: #2563eb;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
}

h1 {
    margin-bottom: 8px;
    color: #111827;
    font-size: 30px;
    font-weight: 800;
}

.subtitle {
    color: #64748b;
    font-size: 13px;
    line-height: 1.7;
    margin-bottom: 0;
}

/* =========================
   ALERT
========================= */

.alert {
    margin-top: 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
}

.alert-success {
    color: #166534;
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.alert-danger {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
}

/* =========================
   FORM
========================= */

.form-label {
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 7px;
}

.form-control,
.form-select {
    min-height: 48px;

    color: #1f2937 !important;
    background: #ffffff !important;

    border: 1px solid #cbd5e1 !important;

    border-radius: 12px;

    font-size: 13px;

    box-shadow: none !important;

    transition: 0.2s ease;
}

textarea.form-control {
    min-height: auto;
    resize: vertical;
}

.form-control::placeholder {
    color: #94a3b8;
}

.form-control:focus,
.form-select:focus {
    color: #111827 !important;
    background: #ffffff !important;

    border-color: #2563eb !important;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, 0.10) !important;
}

.form-select option {
    color: #111827;
    background: #ffffff;
}

/* =========================
   CREATE BUTTON
========================= */

.create-btn {
    width: 100%;
    min-height: 50px;

    border: none;
    border-radius: 12px;

    color: #ffffff;

    font-size: 14px;
    font-weight: 700;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    box-shadow:
        0 10px 24px rgba(37, 99, 235, 0.18);

    cursor: pointer;

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.create-btn:hover {
    color: #ffffff;

    transform: translateY(-1px);

    box-shadow:
        0 14px 28px rgba(37, 99, 235, 0.24);
}

.create-btn:active {
    transform: translateY(0);
}

/* =========================
   SUCCESS BOX
========================= */

.success-box {
    margin-top: 20px;
    padding: 18px;

    border-radius: 15px;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;
}

.success-box strong {
    color: #15803d;
    font-size: 13px;
}

/* =========================
   GENERATED LINK
========================= */

.generated-link {
    margin-top: 10px;
    padding: 12px;

    overflow-wrap: anywhere;

    border-radius: 10px;

    color: #334155;

    background: #ffffff;

    border: 1px solid #dbe4ee;

    font-size: 11px;
    line-height: 1.6;
}

/* =========================
   SUCCESS BUTTONS
========================= */

.btn-outline-success {
    border-color: #86efac !important;
    color: #15803d !important;
    background: #ffffff !important;

    border-radius: 9px;
    font-weight: 600;
}

.btn-outline-success:hover {
    color: #166534 !important;
    background: #f0fdf4 !important;
    border-color: #4ade80 !important;
}

/* =========================
   INFO BOX
========================= */

.info-box {
    margin-top: 20px;
    padding: 16px;

    border-radius: 13px;

    color: #64748b;

    background: #eff6ff;

    border: 1px solid #dbeafe;

    font-size: 11px;
    line-height: 1.7;
}

.info-box i {
    color: #2563eb;
    margin-right: 4px;
}

.info-box strong {
    color: #334155;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 576px) {

    .drop-page {
        padding: 25px 14px 40px;
    }

    .drop-card {
        padding: 22px 18px;
        border-radius: 18px;
    }

    .drop-icon {
        width: 62px;
        height: 62px;
        border-radius: 17px;
        font-size: 26px;
    }

    h1 {
        font-size: 25px;
    }

    .subtitle {
        font-size: 12px;
    }

    .nav-actions {
        justify-content: flex-start;
    }

    .nav-actions a {
        flex: 1;
        text-align: center;
    }

    .create-btn {
        min-height: 48px;
    }
}

/* =========================
   PRINT
========================= */

@media print {

    body {
        background: #ffffff !important;
        color: #111827 !important;
    }

    .drop-page {
        max-width: 100%;
        padding: 10px;
    }

    .drop-card {
        box-shadow: none !important;
        border: 1px solid #d1d5db;
    }

    .nav-actions,
    .create-btn,
    .btn {
        display: none !important;
    }

    .success-box,
    .info-box {
        break-inside: avoid;
    }
}
</style>
</head>


<body>

<main class="drop-page">


<div class="nav-actions">

<a
    href="dashboard.php"
    class="btn btn-outline-light btn-sm">

    <i class="bi bi-arrow-left"></i>

    Dashboard

</a>


<a
    href="file_drops.php"
    class="btn btn-outline-light btn-sm">

    <i class="bi bi-collection"></i>

    My File Drops

</a>

</div>


<div class="drop-card">


<div class="drop-icon">

<i class="bi bi-cloud-arrow-down-fill"></i>

</div>


<span class="page-label">

TEMPORARY FILE DROP

</span>


<h1 class="mt-2">

Create File Drop

</h1>


<p class="subtitle">

Create a temporary upload link and allow
someone to securely send files directly
to your NexaVault storage.

</p>


<?php if ($message !== "") { ?>

<div class="alert alert-<?php echo $message_type; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php } ?>


<form
    method="POST"
    class="mt-4"
>


<div class="mb-3">

<label class="form-label">

Drop Name

</label>


<input
    type="text"
    name="drop_name"
    class="form-control"
    maxlength="150"
    placeholder="Example: Project Submission"
    required
>

</div>


<div class="mb-3">

<label class="form-label">

Description

</label>


<textarea
    name="description"
    class="form-control"
    rows="4"
    placeholder="Enter a short description..."
></textarea>

</div>


<div class="row g-3">


<div class="col-md-6">

<label class="form-label">

Maximum Files

</label>


<select
    name="max_files"
    class="form-select"
>

<option value="1">
1 File
</option>

<option value="3">
3 Files
</option>

<option value="5"
        selected>
5 Files
</option>

<option value="10">
10 Files
</option>

<option value="25">
25 Files
</option>

<option value="50">
50 Files
</option>

</select>

</div>


<div class="col-md-6">

<label class="form-label">

Link Expiry

</label>


<select
    name="expiry_hours"
    class="form-select"
>

<option value="1">
1 Hour
</option>

<option value="6">
6 Hours
</option>

<option value="12">
12 Hours
</option>

<option value="24"
        selected>
24 Hours
</option>

<option value="48">
48 Hours
</option>

<option value="72">
72 Hours
</option>

<option value="168">
7 Days
</option>

</select>

</div>

</div>


<button
    type="submit"
    name="create_drop"
    class="create-btn mt-4"
>

<i class="bi bi-shield-plus"></i>

Create Secure File Drop

</button>


</form>


<?php if ($generatedLink !== "") { ?>


<div class="success-box">


<strong>

<i class="bi bi-check-circle-fill"></i>

File Drop Created Successfully

</strong>


<div class="generated-link">

<?php
echo htmlspecialchars(
    $generatedLink
);
?>

</div>


<button
    type="button"
    class="btn btn-sm btn-outline-success mt-3"
    onclick="copyDropLink()"
>

<i class="bi bi-copy"></i>

Copy Link

</button>


<a
    href="<?php echo htmlspecialchars($generatedLink); ?>"
    target="_blank"
    class="btn btn-sm btn-outline-light mt-3 ms-2"
>

<i class="bi bi-box-arrow-up-right"></i>

Open

</a>


</div>


<?php } ?>


<div class="info-box">

<i class="bi bi-shield-lock"></i>

<strong>

How it works:

</strong>

Create a temporary drop, share the generated
link with another person, and their uploaded
files will be stored in your NexaVault account.
The link automatically expires after the selected
time.

</div>


</div>

</main>


<script>

function copyDropLink()
{

    const link =
        <?php
        echo json_encode(
            $generatedLink
        );
        ?>;

    if (!link) {
        return;
    }

    const fullLink =
        window.location.origin +
        window.location.pathname
            .replace(
                "create_drop.php",
                ""
            ) +
        link;


    navigator.clipboard
        .writeText(fullLink)
        .then(function () {

            alert(
                "File Drop link copied successfully."
            );

        })
        .catch(function () {

            alert(
                "Unable to copy the link."
            );

        });
}

</script>


</body>

</html>