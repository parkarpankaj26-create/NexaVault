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

$user_id = (int)$_SESSION['user_id'];

$drop_id = (int)($_GET['drop_id'] ?? 0);

if ($drop_id <= 0) {
    header("Location: file_drops.php");
    exit();
}


/* =====================================================
   GET DROP
===================================================== */

$dropQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM file_drops
     WHERE id='$drop_id'
     AND user_id='$user_id'
     LIMIT 1"
);

if (
    !$dropQuery ||
    mysqli_num_rows($dropQuery) === 0
) {
    die("File Drop not found.");
}

$drop = mysqli_fetch_assoc($dropQuery);

$dropName = $drop['drop_name'];


/* =====================================================
   GET FILES RECEIVED THROUGH THIS DROP
===================================================== */

$fileQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM files
     WHERE user_id='$user_id'
     AND source_drop_id='$drop_id'
     AND deleted=0
     ORDER BY id DESC"
);

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
    Received Files - NexaVault
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

    margin:0;

    min-height:100vh;

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

.page {

    max-width:1100px;

    margin:auto;

    padding:40px 20px 60px;
}

.panel {

    background:
        linear-gradient(
            145deg,
            rgba(15,31,63,.96),
            rgba(7,20,43,.96)
        );

    border:
        1px solid
        rgba(120,155,220,.14);

    border-radius:22px;

    box-shadow:
        0 20px 55px
        rgba(0,0,0,.28);
}

.page-label {

    color:#6ea1ff;

    font-size:10px;

    font-weight:800;

    letter-spacing:2px;
}

.page-title {

    color:#ffffff;

    font-size:30px;

    font-weight:800;

    margin:5px 0;
}

.page-subtitle {

    color:#91a0bb;

    font-size:12px;
}

.file-row {

    padding:18px 20px;

    border-bottom:
        1px solid
        rgba(255,255,255,.06);
}

.file-row:last-child {

    border-bottom:none;
}

.file-icon {

    width:45px;

    height:45px;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:20px;

    background:
        rgba(59,130,246,.10);

    color:#60a5fa;

}

.file-name {

    color:#ffffff;

    font-size:13px;

    font-weight:700;
}

.file-meta {

    color:#7184a3;

    font-size:10px;

    margin-top:4px;
}

.btn-outline-light {

    border-color:
        rgba(255,255,255,.15);

    color:#cbd5e1;

    border-radius:10px;
}

.empty {

    padding:70px 20px;

    text-align:center;
}

.empty i {

    font-size:48px;

    color:#55739e;
}

.empty h4 {

    color:#ffffff;

    margin-top:14px;
}

.empty p {

    color:#8193af;

    font-size:12px;
}

</style>

</head>

<body>

<main class="page">


<!-- HEADER -->

<div class="d-flex
            justify-content-between
            align-items-start
            flex-wrap
            gap-3
            mb-4">

    <div>

        <span class="page-label">

            FILE DROP

        </span>

        <h1 class="page-title">

            <i class="bi bi-folder-fill"></i>

            Received Files

        </h1>

        <p class="page-subtitle mb-0">

            Files received through:
            <strong>
                <?php echo htmlspecialchars($dropName); ?>
            </strong>

        </p>

    </div>

    <div>

        <a
            href="file_drops.php"
            class="btn btn-outline-light">

            <i class="bi bi-arrow-left"></i>

            Back to File Drops

        </a>

    </div>

</div>


<!-- FILE LIST -->

<div class="panel">

<?php

if (
    $fileQuery &&
    mysqli_num_rows($fileQuery) > 0
) {

    while (
        $file =
        mysqli_fetch_assoc($fileQuery)
    ) {

        $fileName =
            htmlspecialchars(
                $file['original_name']
                ?? 'Unknown File'
            );

        $fileSize =
            round(
                ((int)($file['file_size'] ?? 0))
                / 1024,
                2
            );

        $fileType =
            strtolower(
                $file['file_type']
                ?? ''
            );

        $uploadedAt =
            $file['uploaded_at']
            ?? $file['created_at']
            ?? '';

        $fileIcon =
            'bi-file-earmark-fill';


        if (
            strpos($fileType, 'pdf') !== false
        ) {

            $fileIcon =
                'bi-file-earmark-pdf-fill';

        } elseif (
            strpos($fileType, 'image') !== false
        ) {

            $fileIcon =
                'bi-file-earmark-image-fill';

        } elseif (
            strpos($fileType, 'word') !== false ||
            strpos($fileType, 'document') !== false
        ) {

            $fileIcon =
                'bi-file-earmark-word-fill';

        } elseif (
            strpos($fileType, 'zip') !== false
        ) {

            $fileIcon =
                'bi-file-earmark-zip-fill';
        }

?>

<div class="file-row">

    <div class="d-flex
                align-items-center
                justify-content-between
                gap-3
                flex-wrap">


        <div class="d-flex
                    align-items-center
                    gap-3">

            <div class="file-icon">

                <i class="bi <?php echo $fileIcon; ?>"></i>

            </div>


            <div>

                <div class="file-name">

                    <?php echo $fileName; ?>

                </div>

                <div class="file-meta">

                    <?php echo $fileSize; ?> KB

                    •
                    
                    <?php
                    echo htmlspecialchars(
                        $fileType ?: 'Unknown type'
                    );
                    ?>

                    <?php if ($uploadedAt !== '') { ?>

                        •
                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime($uploadedAt)
                        );
                        ?>

                    <?php } ?>

                </div>

            </div>

        </div>


        <div class="d-flex
                    gap-2
                    flex-wrap">


            <a
                href="preview.php?id=<?php echo (int)$file['id']; ?>"
                class="btn btn-sm btn-outline-light">

                <i class="bi bi-eye"></i>

                Preview

            </a>


            <a
                href="download.php?id=<?php echo (int)$file['id']; ?>"
                class="btn btn-sm btn-outline-light">

                <i class="bi bi-download"></i>

                Download

            </a>


            <a
                href="verify_integrity.php?id=<?php echo (int)$file['id']; ?>"
                class="btn btn-sm btn-outline-info">

                <i class="bi bi-shield-check"></i>

                Integrity

            </a>

        </div>


    </div>

</div>

<?php

    }

} else {

?>

<div class="empty">

    <i class="bi bi-folder2-open"></i>

    <h4>
        No Files Received
    </h4>

    <p>
        This File Drop has not received any files yet.
    </p>

</div>

<?php

}

?>

</div>


</main>

</body>

</html>