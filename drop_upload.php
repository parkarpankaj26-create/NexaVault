<?php

session_start();

include 'includes/db.php';

$message = "";
$message_type = "";

$drop = null;


/* =====================================================
   GET TOKEN
===================================================== */

$token = trim($_GET['token'] ?? '');


if ($token === '') {

    die("Invalid File Drop link.");
}


/* =====================================================
   FETCH FILE DROP
===================================================== */

$tokenSafe = mysqli_real_escape_string(
    $conn,
    $token
);

$dropQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM file_drops
     WHERE drop_token='$tokenSafe'
     LIMIT 1"
);


if (
    !$dropQuery ||
    mysqli_num_rows($dropQuery) === 0
) {

    die("File Drop not found.");
}


$drop = mysqli_fetch_assoc($dropQuery);


/* =====================================================
   BASIC DROP VALIDATION
===================================================== */

$dropId =
    (int)$drop['id'];

$ownerId =
    (int)$drop['user_id'];

$dropName =
    $drop['drop_name'];

$description =
    $drop['description'] ?? '';

$maxFiles =
    (int)$drop['max_files'];

$uploadedFiles =
    (int)$drop['uploaded_files'];

$expiresAt =
    $drop['expires_at'];

$isActive =
    (int)$drop['is_active'];


/* =====================================================
   EXPIRY / STATUS CHECK
===================================================== */

if (!$isActive) {

    die("This File Drop has been disabled.");
}


if (
    empty($expiresAt) ||
    strtotime($expiresAt) <= time()
) {

    mysqli_query(
        $conn,
        "UPDATE file_drops
         SET is_active=0
         WHERE id='$dropId'"
    );

    die("This File Drop has expired.");
}


if ($uploadedFiles >= $maxFiles) {

    die(
        "This File Drop has reached its upload limit."
    );
}


/* =====================================================
   HANDLE FILE UPLOAD
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['upload_file'])
) {


    /* =================================================
       RE-CHECK DROP STATUS
    ================================================= */

    $currentDropQuery = mysqli_query(
        $conn,
        "SELECT *
         FROM file_drops
         WHERE id='$dropId'
         LIMIT 1"
    );


    if (
        !$currentDropQuery ||
        mysqli_num_rows($currentDropQuery) === 0
    ) {

        die("File Drop is no longer available.");
    }


    $drop = mysqli_fetch_assoc(
        $currentDropQuery
    );


    $uploadedFiles =
        (int)$drop['uploaded_files'];

    $maxFiles =
        (int)$drop['max_files'];

    $expiresAt =
        $drop['expires_at'];


    if (
        (int)$drop['is_active'] !== 1
    ) {

        die("This File Drop is inactive.");
    }


    if (
        strtotime($expiresAt) <= time()
    ) {

        mysqli_query(
            $conn,
            "UPDATE file_drops
             SET is_active=0
             WHERE id='$dropId'"
        );

        die("This File Drop has expired.");
    }


    if ($uploadedFiles >= $maxFiles) {

        die(
            "Upload limit reached."
        );
    }


    /* =================================================
       CHECK FILE
    ================================================= */

    if (
        !isset($_FILES['drop_file']) ||
        $_FILES['drop_file']['error'] !== UPLOAD_ERR_OK
    ) {

        $message =
            "Please select a valid file.";

        $message_type =
            "danger";

    } else {


        $file =
            $_FILES['drop_file'];


        $originalName =
            basename(
                $file['name']
            );

        $tmpName =
            $file['tmp_name'];

        $fileSize =
            (int)$file['size'];

        $fileType =
            $file['type'] ?? 'application/octet-stream';


        /* =============================================
           FILE SIZE LIMIT
           10 MB
        ============================================= */

        $maxFileSize =
            10 * 1024 * 1024;


        if ($fileSize <= 0) {

            $message =
                "The selected file is empty.";

            $message_type =
                "danger";

        } elseif (
            $fileSize > $maxFileSize
        ) {

            $message =
                "File size must not exceed 10 MB.";

            $message_type =
                "danger";

        } else {


            /* =========================================
               SAFE FILE EXTENSION
            ========================================= */

            $extension =
                strtolower(
                    pathinfo(
                        $originalName,
                        PATHINFO_EXTENSION
                    )
                );


            $allowedExtensions = [

                'pdf',
                'doc',
                'docx',
                'txt',
                'jpg',
                'jpeg',
                'png',
                'gif',
                'zip',
                'xlsx',
                'xls',
                'ppt',
                'pptx'

            ];


            if (
                !in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {

                $message =
                    "This file type is not allowed.";

                $message_type =
                    "danger";

            } else {


                /* =====================================
                   UPLOAD DIRECTORY
                ===================================== */

                $uploadDir =
                    __DIR__ .
                    DIRECTORY_SEPARATOR .
                    'uploads' .
                    DIRECTORY_SEPARATOR;


                if (
                    !is_dir($uploadDir)
                ) {

                    mkdir(
                        $uploadDir,
                        0777,
                        true
                    );
                }


                /* =====================================
                   GENERATE UNIQUE STORED FILE NAME
                ===================================== */

                $storedName =
                    bin2hex(
                        random_bytes(16)
                    )
                    . '.'
                    . $extension;


                $destination =
                    $uploadDir .
                    $storedName;


                /* =====================================
                   MOVE FILE
                ===================================== */

                if (
                    move_uploaded_file(
                        $tmpName,
                        $destination
                    )
                ) {


                    /* =================================
                       SHA-256 HASH
                    ================================= */

                    $fileHash =
                        hash_file(
                            'sha256',
                            $destination
                        );


                    /* =================================
                       SAFE VALUES
                    ================================= */

                    $originalNameSafe =
                        mysqli_real_escape_string(
                            $conn,
                            $originalName
                        );

                    $storedNameSafe =
                        mysqli_real_escape_string(
                            $conn,
                            $storedName
                        );

                    $fileTypeSafe =
                        mysqli_real_escape_string(
                            $conn,
                            $fileType
                        );

                    $fileHashSafe =
                        mysqli_real_escape_string(
                            $conn,
                            $fileHash
                        );


                    /* =================================
   INSERT INTO OWNER FILES
================================= */

$fileInsert =
    mysqli_query(
        $conn,
        "INSERT INTO files
        (
            user_id,
            source_drop_id,
            file_name,
            original_name,
            file_size,
            file_type,
            file_hash,
            favorite,
            downloaded,
            deleted
        )
        VALUES
        (
            '$ownerId',
            '$dropId',
            '$storedNameSafe',
            '$originalNameSafe',
            '$fileSize',
            '$fileTypeSafe',
            '$fileHashSafe',
            0,
            0,
            0
        )"
    );


                    if (!$fileInsert) {


                        /* =========================
                           DELETE PHYSICAL FILE
                        ========================= */

                        if (
                            file_exists(
                                $destination
                            )
                        ) {

                            unlink(
                                $destination
                            );
                        }


                        $message =
                            "File could not be saved.";

                        $message_type =
                            "danger";

                    } else {


                        /* =========================
                           UPDATE DROP COUNT
                        ========================= */

                        mysqli_query(
                            $conn,
                            "UPDATE file_drops
                             SET uploaded_files =
                                 uploaded_files + 1
                             WHERE id='$dropId'"
                        );


                        /* =========================
                           ACTIVITY LOG
                        ========================= */

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
                                    $ownerId,
                                    "File received through File Drop: " .
                                    $originalName
                                );
                            }
                        }
/* =====================================================
   SECURITY ALERT - FILE DROP UPLOAD
===================================================== */

$alertTitle =
    "File Received Through File Drop";

$alertMessage =
    "File '" .
    $originalName .
    "' was received through the File Drop '" .
    $dropName .
    "'.";

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


/* =====================================================
   PREVENT DUPLICATE ALERT
===================================================== */

$alertExistsQuery = mysqli_query(
    $conn,
    "SELECT id
     FROM security_alerts
     WHERE user_id='$ownerId'
     AND alert_type='file_drop_upload'
     AND message='$alertMessageSafe'
     AND created_at >= DATE_SUB(
         NOW(),
         INTERVAL 10 MINUTE
     )
     LIMIT 1"
);


if (
    $alertExistsQuery &&
    mysqli_num_rows(
        $alertExistsQuery
    ) === 0
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
            '$ownerId',
            'file_drop_upload',
            'medium',
            '$alertTitleSafe',
            '$alertMessageSafe',
            0
        )"
    );
}

                        /* =========================
                           CHECK DROP LIMIT
                        ========================= */

                        $newCount =
                            $uploadedFiles + 1;


                        if (
                            $newCount >= $maxFiles
                        ) {

                            mysqli_query(
                                $conn,
                                "UPDATE file_drops
                                 SET is_active=0
                                 WHERE id='$dropId'"
                            );
                        }


                        $message =
                            "File uploaded successfully.";

                        $message_type =
                            "success";


                        /*
                         * Refresh values for page display
                         */

                        $uploadedFiles =
                            $newCount;
                    }

                } else {

                    $message =
                        "Unable to upload the file.";

                    $message_type =
                        "danger";
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
    File Drop - NexaVault
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
    box-sizing:border-box;
}


body {

    min-height:100vh;

    margin:0;

    display:flex;

    align-items:center;

    justify-content:center;

    padding:25px;

    font-family:
        "Segoe UI",
        Arial,
        sans-serif;

    color:#e8eefc;

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


.drop-card {

    width:100%;

    max-width:600px;

    padding:35px;

    border-radius:24px;

    background:
        linear-gradient(
            145deg,
            rgba(15,31,63,.97),
            rgba(7,20,43,.97)
        );

    border:
        1px solid
        rgba(120,155,220,.15);

    box-shadow:
        0 25px 65px
        rgba(0,0,0,.40);

}


.drop-icon {

    width:75px;

    height:75px;

    display:flex;

    align-items:center;

    justify-content:center;

    margin:0 auto 20px;

    border-radius:20px;

    font-size:32px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

}


.drop-title {

    color:#ffffff;

    font-size:28px;

    font-weight:800;

}


.drop-description {

    color:#91a0bb;

    font-size:13px;

    line-height:1.7;

}


.drop-info {

    display:flex;

    justify-content:center;

    gap:10px;

    flex-wrap:wrap;

    margin-top:18px;

}


.info-badge {

    padding:7px 11px;

    border-radius:20px;

    background:
        rgba(255,255,255,.05);

    border:
        1px solid
        rgba(255,255,255,.08);

    color:#9eb1d0;

    font-size:10px;

}


.upload-box {

    margin-top:25px;

    padding:22px;

    border-radius:15px;

    background:
        rgba(255,255,255,.035);

    border:
        1px solid
        rgba(120,155,220,.12);

}


.form-label {

    color:#dce6f5;

    font-size:12px;

    font-weight:700;

}


.form-control {

    min-height:50px;

    padding:12px;

    color:#ffffff;

    background:
        rgba(5,18,40,.8);

    border:
        1px solid
        rgba(120,155,220,.18);

    border-radius:12px;

}


.form-control:focus {

    color:#ffffff;

    background:
        rgba(5,18,40,.8);

    border-color:#4d7cff;

    box-shadow:
        0 0 0 3px
        rgba(77,124,255,.10);

}


.upload-btn {

    width:100%;

    min-height:50px;

    border:none;

    border-radius:12px;

    color:#ffffff;

    font-weight:700;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

}


.security-note {

    margin-top:18px;

    padding:14px;

    border-radius:13px;

    background:
        rgba(16,185,129,.06);

    border:
        1px solid
        rgba(16,185,129,.15);

    color:#9eb1d0;

    font-size:10px;

    line-height:1.7;

}


.success-result {

    margin-top:20px;

    padding:18px;

    border-radius:14px;

    background:
        rgba(16,185,129,.07);

    border:
        1px solid
        rgba(16,185,129,.18);

    text-align:center;

}


.success-result strong {

    color:#34d399;

    font-size:13px;

}


</style>

</head>


<body>


<div class="drop-card">


<div class="text-center">

<div class="drop-icon">

<i class="bi bi-cloud-arrow-up-fill"></i>

</div>


<h1 class="drop-title">

<?php
echo htmlspecialchars(
    $dropName
);
?>

</h1>


<p class="drop-description">

<?php

if ($description !== '') {

    echo nl2br(
        htmlspecialchars(
            $description
        )
    );

} else {

    echo
        "Upload files securely to the NexaVault owner's storage.";
}

?>

</p>


<div class="drop-info">


<span class="info-badge">

<i class="bi bi-files"></i>

<?php
echo $uploadedFiles;
?>
/
<?php
echo $maxFiles;
?>

Files

</span>


<span class="info-badge">

<i class="bi bi-clock"></i>

Expires

<?php
echo date(
    "d M Y, h:i A",
    strtotime($expiresAt)
);
?>

</span>


<span class="info-badge">

<i class="bi bi-shield-check"></i>

Secure Drop

</span>


</div>

</div>


<?php if ($message !== '') { ?>

<div
    class="alert alert-<?php echo $message_type; ?> mt-4">

    <?php
    echo htmlspecialchars(
        $message
    );
    ?>

</div>

<?php } ?>


<?php

if (
    $isActive &&
    strtotime($expiresAt) > time() &&
    $uploadedFiles < $maxFiles
) {

?>


<div class="upload-box">


<form
    method="POST"
    enctype="multipart/form-data"
>


<div class="mb-3">

<label class="form-label">

Select File

</label>


<input
    type="file"
    name="drop_file"
    class="form-control"
    required
>


<div
    class="form-text"
    style="
        color:#7184a3;
        font-size:10px;
        margin-top:7px;
    "
>

Maximum file size: 10 MB

</div>

</div>


<button
    type="submit"
    name="upload_file"
    class="upload-btn"
>

<i class="bi bi-cloud-arrow-up"></i>

Upload Securely

</button>


</form>


</div>


<div class="security-note">

<i class="bi bi-shield-lock"></i>

Your file is uploaded directly to the
NexaVault owner's storage. No login is required.
A SHA-256 fingerprint is generated automatically
for file integrity verification.

</div>


<?php

} else {

?>


<div class="success-result">

<strong>

<i class="bi bi-check-circle-fill"></i>

This File Drop is no longer accepting uploads.

</strong>

</div>


<?php } ?>


</div>


</body>

</html>