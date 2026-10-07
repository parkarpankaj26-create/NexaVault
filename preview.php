
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
    die("Invalid Request.");
}

$id = (int) $_GET['id'];


/* =====================================================
   GET ONLY USER'S FILE
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
   FILE PATH
===================================================== */

$fileName = basename($file['file_name']);

$filePath = __DIR__ . "/uploads/" . $fileName;

$browserPath =
    "uploads/" .
    rawurlencode($fileName);


/* =====================================================
   FILE EXISTENCE
===================================================== */

if (!file_exists($filePath)) {
    die("File does not exist on the server.");
}


/* =====================================================
   FILE DETAILS
===================================================== */

$fileType = strtolower(
    $file['file_type'] ?? ''
);

$originalName =
    htmlspecialchars(
        $file['original_name'] ??
        'Unknown File',
        ENT_QUOTES
    );

$fileSize =
    round(
        ((int)($file['file_size'] ?? 0))
        / 1024,
        2
    );


/* =====================================================
   DETERMINE PREVIEW TYPE
===================================================== */

$isPdf =
    strpos($fileType, 'pdf') !== false;

$isImage =
    strpos($fileType, 'image/') === 0;


/* =====================================================
   IMAGE MIME VALIDATION
===================================================== */

$imageType = null;

if ($isImage) {

    $imageInfo =
        @getimagesize($filePath);

    if ($imageInfo && isset($imageInfo['mime'])) {
        $imageType = $imageInfo['mime'];
    }
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
Preview - NexaVault
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
    margin: 0;
    min-height: 100vh;

    font-family: "Segoe UI", Arial, sans-serif;

    color: #1f2937;

    background:
        radial-gradient(
            circle at 12% 20%,
            rgba(37, 99, 235, 0.07),
            transparent 35%
        ),
        radial-gradient(
            circle at 88% 75%,
            rgba(99, 102, 241, 0.06),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fafc 0%,
            #eef4ff 50%,
            #f8fafc 100%
        );

    background-attachment: fixed;
}

/* =========================
   PAGE
========================= */

.preview-page {
    max-width: 1250px;
    margin: auto;
    padding: 35px 20px 60px;
}

/* =========================
   HEADER
========================= */

.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;
    flex-wrap: wrap;

    margin-bottom: 22px;
}

.preview-label {
    color: #2563eb;

    font-size: 10px;
    font-weight: 800;

    letter-spacing: 2px;
}

.preview-title {
    margin: 4px 0 0;

    color: #111827;

    font-size: 28px;
    font-weight: 800;

    word-break: break-word;
}

/* =========================
   MAIN CARD
========================= */

.preview-card {
    border-radius: 22px;

    background: #ffffff;

    border: 1px solid #e2e8f0;

    box-shadow:
        0 18px 45px rgba(15, 23, 42, 0.08);

    overflow: hidden;
}

/* =========================
   FILE META
========================= */

.file-meta {
    padding: 18px 22px;

    border-bottom: 1px solid #e2e8f0;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;
    flex-wrap: wrap;
}

.file-meta-left {
    display: flex;
    align-items: center;
    gap: 12px;

    min-width: 0;
}

.file-icon {
    width: 46px;
    height: 46px;

    flex: 0 0 46px;

    border-radius: 13px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border: 1px solid #dbeafe;

    font-size: 21px;
}

.file-meta-name {
    color: #1f2937;

    font-size: 14px;
    font-weight: 700;

    word-break: break-word;
}

.file-meta-details {
    color: #64748b;

    font-size: 10px;

    margin-top: 3px;
}

/* =========================
   TOOLS
========================= */

.preview-tools {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.preview-tools .btn {
    border-radius: 10px;
}

/* =========================
   PREVIEW AREA
========================= */

.preview-area {
    padding: 22px;
}

/* =========================
   PDF
========================= */

.pdf-frame {
    width: 100%;
    height: 720px;

    border: 1px solid #dbe3ec;

    border-radius: 14px;

    background: #ffffff;

    display: block;
}

/* =========================
   IMAGE PREVIEW
========================= */

.image-preview-wrapper {
    min-height: 500px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 25px;

    border-radius: 16px;

    background: #f8fafc;

    border: 1px solid #e2e8f0;
}

.image-preview {
    display: block;

    max-width: 100%;
    max-height: 680px;

    object-fit: contain;

    border-radius: 10px;

    box-shadow:
        0 15px 35px rgba(15, 23, 42, 0.12);
}

/* =========================
   UNSUPPORTED PREVIEW
========================= */

.unsupported-preview {
    min-height: 480px;

    display: flex;
    flex-direction: column;

    align-items: center;
    justify-content: center;

    text-align: center;

    padding: 40px;
}

.unsupported-icon {
    width: 90px;
    height: 90px;

    border-radius: 24px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eff6ff;

    border: 1px solid #dbeafe;

    color: #2563eb;

    font-size: 42px;

    margin-bottom: 20px;
}

.unsupported-preview h3 {
    color: #111827;

    font-weight: 750;

    margin-bottom: 10px;
}

.unsupported-preview p {
    max-width: 520px;

    color: #64748b;

    line-height: 1.7;

    font-size: 13px;

    margin-bottom: 20px;
}

/* =========================
   FILE INFORMATION
========================= */

.preview-info {
    display: grid;

    grid-template-columns: repeat(3, 1fr);

    gap: 10px;

    padding: 18px 22px;

    border-top: 1px solid #e2e8f0;

    background: #f8fafc;
}

.info-item {
    padding: 12px;

    border-radius: 12px;

    background: #ffffff;

    border: 1px solid #e2e8f0;
}

.info-item span {
    display: block;

    color: #64748b;

    font-size: 10px;

    margin-bottom: 3px;
}

.info-item strong {
    color: #1f2937;

    font-size: 12px;

    word-break: break-word;
}

/* =========================
   BUTTONS
========================= */

.btn-primary {
    border: none !important;

    color: #ffffff !important;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        ) !important;

    border-radius: 10px;

    box-shadow:
        0 7px 18px rgba(37, 99, 235, 0.16);

    font-weight: 600;
}

.btn-primary:hover {
    color: #ffffff !important;

    background:
        linear-gradient(
            135deg,
            #1d4ed8,
            #4f46e5
        ) !important;
}

.btn-outline-light {
    border: 1px solid #cbd5e1 !important;

    color: #334155 !important;

    background: #ffffff !important;

    border-radius: 10px;

    font-weight: 600;
}

.btn-outline-light:hover {
    background: #f1f5f9 !important;

    border-color: #94a3b8 !important;

    color: #1e293b !important;
}

/* =========================
   MOBILE
========================= */

@media (max-width: 768px) {

    .preview-page {
        padding: 25px 12px 40px;
    }

    .preview-title {
        font-size: 23px;
    }

    .file-meta {
        align-items: flex-start;
    }

    .preview-tools {
        width: 100%;
    }

    .preview-tools .btn {
        flex: 1;
    }

    .pdf-frame {
        height: 550px;
    }

    .preview-area {
        padding: 14px;
    }

    .preview-info {
        grid-template-columns: 1fr;
        padding: 14px;
    }

    .image-preview-wrapper {
        min-height: 350px;
        padding: 15px;
    }

    .unsupported-preview {
        min-height: 380px;
        padding: 25px 15px;
    }
}

@media (max-width: 480px) {

    .preview-header {
        align-items: stretch;
    }

    .preview-header > div:last-child {
        width: 100%;
    }

    .preview-header > div:last-child .btn {
        flex: 1;
    }

    .file-meta {
        padding: 15px;
    }

    .file-meta-left {
        width: 100%;
    }

    .file-meta-name {
        font-size: 13px;
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

    .preview-page {
        max-width: 100%;
        padding: 10px;
    }

    .preview-card {
        box-shadow: none !important;
        border: 1px solid #d1d5db;
    }

    .preview-header .btn,
    .preview-tools {
        display: none !important;
    }

    .preview-info,
    .info-item {
        break-inside: avoid;
    }
}
</style>

</head>


<body>


<main class="preview-page">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="preview-header">

<div>

<span class="preview-label">
FILE PREVIEW
</span>

<h1 class="preview-title">
<?php echo $originalName; ?>
</h1>

</div>


<div class="d-flex gap-2 flex-wrap">

<a
href="dashboard.php"
class="btn btn-outline-light">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>

<a
href="download.php?id=<?php echo $id; ?>"
class="btn btn-primary">

<i class="bi bi-download"></i>

Download

</a>

</div>

</div>


<!-- =====================================================
     PREVIEW CARD
===================================================== -->

<div class="preview-card">


<!-- FILE META -->

<div class="file-meta">


<div class="file-meta-left">


<div class="file-icon">

<?php if ($isPdf) { ?>

<i class="bi bi-file-earmark-pdf-fill"></i>

<?php } elseif ($isImage) { ?>

<i class="bi bi-file-earmark-image-fill"></i>

<?php } else { ?>

<i class="bi bi-file-earmark-fill"></i>

<?php } ?>

</div>


<div>

<div class="file-meta-name">

<?php echo $originalName; ?>

</div>


<div class="file-meta-details">

NexaVault Secure Storage

</div>

</div>


</div>


<div class="preview-tools">

<a
href="download.php?id=<?php echo $id; ?>"
class="btn btn-primary btn-sm">

<i class="bi bi-download"></i>

Download

</a>

</div>


</div>


<!-- =====================================================
     PREVIEW
===================================================== -->

<div class="preview-area">


<?php if ($isPdf) { ?>


<!-- PDF -->

<iframe
src="<?php echo htmlspecialchars($browserPath); ?>"
class="pdf-frame"
title="PDF Preview">
</iframe>


<?php } elseif ($isImage && $imageType) { ?>


<!-- IMAGE -->

<div class="image-preview-wrapper">

<img
src="<?php echo htmlspecialchars($browserPath); ?>"
alt="<?php echo $originalName; ?>"
class="image-preview">

</div>


<?php } else { ?>


<!-- UNSUPPORTED -->

<div class="unsupported-preview">

<div class="unsupported-icon">

<i class="bi bi-file-earmark-x"></i>

</div>


<h3>
Preview Not Available
</h3>


<p>

This file type cannot be displayed
directly in the browser.

You can download the file securely
from your NexaVault storage.

</p>


<a
href="download.php?id=<?php echo $id; ?>"
class="btn btn-primary">

<i class="bi bi-download"></i>

Download File

</a>


</div>


<?php } ?>


</div>


<!-- =====================================================
     FILE INFORMATION
===================================================== -->

<div class="preview-info">


<div class="info-item">

<span>
File Type
</span>

<strong>
<?php echo htmlspecialchars(
    $file['file_type'] ?? 'Unknown'
); ?>
</strong>

</div>


<div class="info-item">

<span>
File Size
</span>

<strong>
<?php echo $fileSize; ?> KB
</strong>

</div>


<div class="info-item">

<span>
Uploaded
</span>

<strong>

<?php

if (!empty($file['uploaded_at'])) {

    echo date(
        "d M Y • H:i",
        strtotime(
            $file['uploaded_at']
        )
    );

} else {

    echo "N/A";

}

?>

</strong>

</div>


</div>


</div>


</main>


</body>

</html>
```
