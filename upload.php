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

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed.");
}


$user_id = (int) $_SESSION['user_id'];


/* =====================================================
   FILE UPLOAD + VERSIONING + TAGS + DUPLICATE DETECTION
===================================================== */

if (isset($_POST['upload'])) {

    /* =================================================
       CHECK UPLOAD
    ================================================= */

    if (
        !isset($_FILES['file']) ||
        $_FILES['file']['error'] !== UPLOAD_ERR_OK
    ) {
        echo "<script>
                alert('File upload failed. Please try again.');
              </script>";
        exit();
    }


    $file = $_FILES['file'];

    $originalName = basename($file['name']);
    $tmpName      = $file['tmp_name'];
    $fileSize     = (int) $file['size'];
    $fileType     = $file['type'];

    /* Tags */
    $tags = trim($_POST['tags'] ?? '');

    $tagsSafe = mysqli_real_escape_string(
        $conn,
        $tags
    );


    /* =================================================
       ALLOWED FILE TYPES
    ================================================= */

    $allowed = [
        'pdf',
        'doc',
        'docx',
        'jpg',
        'jpeg',
        'png',
        'zip'
    ];

    $extension = strtolower(
        pathinfo(
            $originalName,
            PATHINFO_EXTENSION
        )
    );


    if (
        !in_array(
            $extension,
            $allowed,
            true
        )
    ) {

        echo "<script>
                alert(
                    'Invalid File Type! Only PDF, DOC, DOCX, JPG, JPEG, PNG and ZIP files are allowed.'
                );
              </script>";

        exit();
    }


    /* =================================================
       FILE HASH
       SHA-256 detects exact duplicate content
    ================================================= */

    $fileHash = hash_file(
        'sha256',
        $tmpName
    );

    if ($fileHash === false) {

        echo "<script>
                alert('Unable to calculate file security hash.');
              </script>";

        exit();
    }

    $fileHashSafe = mysqli_real_escape_string(
        $conn,
        $fileHash
    );


    /* =================================================
       DUPLICATE FILE DETECTION
    ================================================= */

    $duplicateQuery = mysqli_query(
        $conn,
        "SELECT id, original_name
         FROM files
         WHERE user_id='$user_id'
         AND file_hash='$fileHashSafe'
         AND deleted=0
         LIMIT 1"
    );


    if (
        $duplicateQuery &&
        mysqli_num_rows($duplicateQuery) > 0
    ) {

        $duplicateFile =
            mysqli_fetch_assoc(
                $duplicateQuery
            );

        $duplicateName =
            htmlspecialchars(
                $duplicateFile['original_name'],
                ENT_QUOTES
            );

        echo "
        <script>
            alert(
                '⚠️ Duplicate File Detected!\\n\\n' +
                'This exact file already exists in your NexaVault storage.\\n\\n' +
                'Existing File: {$duplicateName}\\n\\n' +
                'The file was not uploaded again.'
            );

            window.location.href = 'dashboard.php';
        </script>
        ";

        exit();
    }


    /* =================================================
       UPLOAD FOLDER
       Only uploaded files should be stored here.
    ================================================= */

    $uploadFolder =
        __DIR__ . "/uploads/";


    if (!is_dir($uploadFolder)) {

        if (
            !mkdir(
                $uploadFolder,
                0777,
                true
            )
        ) {

            echo "<script>
                    alert('Unable to create upload folder.');
                  </script>";

            exit();
        }
    }


    /* =================================================
       CHECK SAME FILE NAME
       Used for VERSIONING
    ================================================= */

    $safeOriginalName =
        mysqli_real_escape_string(
            $conn,
            $originalName
        );


    $existingQuery = mysqli_query(
        $conn,
        "SELECT *
         FROM files
         WHERE user_id='$user_id'
         AND original_name='$safeOriginalName'
         AND deleted=0
         ORDER BY id DESC
         LIMIT 1"
    );


    $existingFile = null;

    if (
        $existingQuery &&
        mysqli_num_rows($existingQuery) > 0
    ) {

        $existingFile =
            mysqli_fetch_assoc(
                $existingQuery
            );
    }


    /* =================================================
       UNIQUE SERVER FILE NAME
    ================================================= */

    $uniqueName =
        bin2hex(
            random_bytes(12)
        )
        . "_"
        . time()
        . "."
        . $extension;


    $destination =
        $uploadFolder . $uniqueName;


    /* =================================================
       MOVE FILE
    ================================================= */

    if (
        !move_uploaded_file(
            $tmpName,
            $destination
        )
    ) {

        echo "<script>
                alert('File Upload Failed.');
              </script>";

        exit();
    }


    /* =================================================
       NEW FILE
    ================================================= */

    if ($existingFile === null) {

        $storedName =
            mysqli_real_escape_string(
                $conn,
                $uniqueName
            );

        $typeSafe =
            mysqli_real_escape_string(
                $conn,
                $fileType
            );


        /* =================================================
           INSERT MAIN FILE
        ================================================= */

        $insertQuery = "
            INSERT INTO files
            (
                user_id,
                file_name,
                original_name,
                file_size,
                file_type,
                file_hash,
                tags
            )
            VALUES
            (
                '$user_id',
                '$storedName',
                '$safeOriginalName',
                '$fileSize',
                '$typeSafe',
                '$fileHashSafe',
                '$tagsSafe'
            )
        ";


        if (
            !mysqli_query(
                $conn,
                $insertQuery
            )
        ) {

            unlink($destination);

            echo "<script>
                    alert(
                        'Database Error while saving file: " .
                        addslashes(mysqli_error($conn)) .
                        "'
                    );
                  </script>";

            exit();
        }


        $fileId =
            mysqli_insert_id($conn);


        /* =================================================
           CREATE VERSION 1
        ================================================= */

        $versionQuery = mysqli_query(
            $conn,
            "INSERT INTO file_versions
            (
                file_id,
                user_id,
                version_number,
                file_name,
                original_name,
                file_size,
                file_type,
                is_current
            )
            VALUES
            (
                '$fileId',
                '$user_id',
                1,
                '$storedName',
                '$safeOriginalName',
                '$fileSize',
                '$typeSafe',
                1
            )"
        );


        if (!$versionQuery) {

            echo "<script>
                    alert(
                        'File uploaded, but version history could not be created.'
                    );
                  </script>";
        }


        /* =================================================
           ACTIVITY
        ================================================= */

        $activityFile =
            __DIR__ . '/includes/activity.php';

        if (file_exists($activityFile)) {

            require_once $activityFile;

            if (
                function_exists('logActivity')
            ) {

                logActivity(
                    $conn,
                    $user_id,
                    "Uploaded file: $originalName (Version 1)"
                );
            }
        }


        /* =================================================
           NOTIFICATION
        ================================================= */

        $notificationFile =
            __DIR__ . '/includes/notification.php';

        if (file_exists($notificationFile)) {

            require_once $notificationFile;

            if (
                function_exists('addNotification')
            ) {

                addNotification(
                    $conn,
                    $user_id,
                    "📤 File Uploaded: $originalName (Version 1)"
                );
            }
        }


        echo "<script>

                alert(
                    'File Uploaded Successfully! Version 1 created.'
                );

                window.location.href =
                    'dashboard.php';

              </script>";

        exit();
    }


    /* =================================================
       EXISTING FILE → NEW VERSION
    ================================================= */

    $fileId =
        (int) $existingFile['id'];


    /* =================================================
       GET LATEST VERSION
    ================================================= */

    $versionQuery = mysqli_query(
        $conn,
        "SELECT MAX(version_number) AS max_version
         FROM file_versions
         WHERE file_id='$fileId'
         AND user_id='$user_id'"
    );


    $versionRow =
        $versionQuery
        ? mysqli_fetch_assoc($versionQuery)
        : null;


    $lastVersion =
        (int) (
            $versionRow['max_version']
            ?? 0
        );


    /* =================================================
       OLD FILE BEFORE VERSIONING
    ================================================= */

    if ($lastVersion === 0) {

        $oldFileName =
            mysqli_real_escape_string(
                $conn,
                $existingFile['file_name']
            );

        $oldOriginalName =
            mysqli_real_escape_string(
                $conn,
                $existingFile['original_name']
            );

        $oldSize =
            (int) $existingFile['file_size'];

        $oldType =
            mysqli_real_escape_string(
                $conn,
                $existingFile['file_type']
            );


        mysqli_query(
            $conn,
            "INSERT INTO file_versions
            (
                file_id,
                user_id,
                version_number,
                file_name,
                original_name,
                file_size,
                file_type,
                is_current
            )
            VALUES
            (
                '$fileId',
                '$user_id',
                1,
                '$oldFileName',
                '$oldOriginalName',
                '$oldSize',
                '$oldType',
                0
            )"
        );


        $lastVersion = 1;
    }


    $newVersion =
        $lastVersion + 1;


    $newStoredName =
        mysqli_real_escape_string(
            $conn,
            $uniqueName
        );

    $typeSafe =
        mysqli_real_escape_string(
            $conn,
            $fileType
        );


    /* =================================================
       OLD VERSION → NOT CURRENT
    ================================================= */

    mysqli_query(
        $conn,
        "UPDATE file_versions
         SET is_current=0
         WHERE file_id='$fileId'
         AND user_id='$user_id'"
    );


    /* =================================================
       INSERT NEW VERSION
    ================================================= */

    $versionInsert = mysqli_query(
        $conn,
        "INSERT INTO file_versions
        (
            file_id,
            user_id,
            version_number,
            file_name,
            original_name,
            file_size,
            file_type,
            is_current
        )
        VALUES
        (
            '$fileId',
            '$user_id',
            '$newVersion',
            '$newStoredName',
            '$safeOriginalName',
            '$fileSize',
            '$typeSafe',
            1
        )"
    );


    if (!$versionInsert) {

        unlink($destination);

        echo "<script>
                alert(
                    'Unable to create new file version.'
                );
              </script>";

        exit();
    }


    /* =================================================
       UPDATE CURRENT FILE
    ================================================= */

    $updateFile = mysqli_query(
        $conn,
        "UPDATE files
         SET
            file_name='$newStoredName',
            original_name='$safeOriginalName',
            file_size='$fileSize',
            file_type='$typeSafe',
            tags='$tagsSafe',
            file_hash='$fileHashSafe'
         WHERE id='$fileId'
         AND user_id='$user_id'"
    );


    if (!$updateFile) {

        unlink($destination);

        echo "<script>
                alert(
                    'Version created, but current file update failed.'
                );
              </script>";

        exit();
    }


    /* =================================================
       ACTIVITY
    ================================================= */

    $activityFile =
        __DIR__ . '/includes/activity.php';

    if (file_exists($activityFile)) {

        require_once $activityFile;

        if (
            function_exists('logActivity')
        ) {

            logActivity(
                $conn,
                $user_id,
                "Uploaded new version of: $originalName (Version $newVersion)"
            );
        }
    }


    /* =================================================
       NOTIFICATION
    ================================================= */

    $notificationFile =
        __DIR__ . '/includes/notification.php';

    if (file_exists($notificationFile)) {

        require_once $notificationFile;

        if (
            function_exists('addNotification')
        ) {

            addNotification(
                $conn,
                $user_id,
                "🔄 New version uploaded: $originalName (Version $newVersion)"
            );
        }
    }


    echo "<script>

            alert(
                'New File Version Uploaded Successfully! Version $newVersion created.'
            );

            window.location.href =
                'dashboard.php';

          </script>";

    exit();
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
    Upload File - NexaVault
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

body {
    min-height: 100vh;
    margin: 0;
    color: #1f2937;
    font-family: "Segoe UI", Arial, sans-serif;

    background:
        radial-gradient(
            circle at 10% 20%,
            rgba(37, 99, 235, 0.08),
            transparent 35%
        ),
        radial-gradient(
            circle at 90% 80%,
            rgba(99, 102, 241, 0.07),
            transparent 35%
        ),
        linear-gradient(
            135deg,
            #f8fbff,
            #eef5ff,
            #f8fafc
        );

    background-attachment: fixed;
}


/* ==============================
   UPLOAD WRAPPER
================================ */

.upload-wrapper {
    min-height: calc(100vh - 80px);

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 40px 15px;
}


/* ==============================
   UPLOAD CARD
================================ */

.upload-card {
    width: 100%;
    max-width: 600px;

    padding: 35px;

    border-radius: 24px;

    background: #ffffff;

    border: 1px solid #dbe5f2;

    box-shadow:
        0 18px 45px rgba(30, 64, 175, 0.10);
}


/* ==============================
   UPLOAD ICON
================================ */

.upload-icon {
    width: 75px;
    height: 75px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin: 0 auto 20px;

    border-radius: 22px;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    color: white;

    font-size: 33px;

    box-shadow:
        0 12px 28px rgba(37, 99, 235, 0.20);
}


/* ==============================
   TITLE
================================ */

.upload-card h2 {
    color: #172554;

    font-weight: 750;
}


/* ==============================
   SUBTITLE
================================ */

.upload-card .subtitle {
    color: #64748b;
}


/* ==============================
   FORM LABEL
================================ */

.form-label {
    color: #334155;

    font-weight: 600;
}


/* ==============================
   FORM INPUT
================================ */

.form-control {
    min-height: 48px;

    background: #ffffff !important;

    color: #1e293b !important;

    border:
        1px solid
        #cbd5e1 !important;

    border-radius: 12px;
}


/* ==============================
   INPUT FOCUS
================================ */

.form-control:focus {
    border-color:
        #2563eb !important;

    box-shadow:
        0 0 0 3px
        rgba(37, 99, 235, 0.10) !important;
}


/* ==============================
   FILE SELECT BUTTON
================================ */

.form-control::file-selector-button {
    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    color: white;

    border: none;

    padding: 10px 15px;

    margin-right: 12px;

    border-radius: 8px;
}


/* ==============================
   UPLOAD BUTTON
================================ */

.upload-btn {
    width: 100%;

    min-height: 49px;

    border: none;

    border-radius: 12px;

    color: white;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #6366f1
        );

    font-weight: 650;

    box-shadow:
        0 10px 25px
        rgba(37, 99, 235, 0.20);

    transition: 0.25s ease;
}


/* ==============================
   UPLOAD BUTTON HOVER
================================ */

.upload-btn:hover {
    transform:
        translateY(-2px);

    color: white;

    box-shadow:
        0 15px 32px
        rgba(37, 99, 235, 0.28);
}


/* ==============================
   ALLOWED FILE NOTE
================================ */

.allowed-note {
    color: #64748b;

    font-size: 11px;
}


/* ==============================
   BACK TO DASHBOARD
================================ */

.back-dashboard {
    color: #2563eb;

    text-decoration: none;

    font-weight: 600;
}


.back-dashboard:hover {
    color: #1d4ed8;

    text-decoration: underline;
}


/* ==============================
   MOBILE
================================ */

@media(max-width:576px) {

    .upload-card {
        padding: 25px 20px;
    }

}


/* ==============================
   PRINT FRIENDLY
================================ */

@media print {

    body {
        background: white !important;

        color: #000 !important;
    }

    .upload-card {
        background: white !important;

        box-shadow: none !important;

        border: 1px solid #ccc !important;
    }

    .upload-card h2,
    .form-label {
        color: #000 !important;
    }

    .subtitle,
    .allowed-note {
        color: #444 !important;
    }

}

</style>

</head>


<body>


<?php

$navbarFile =
    __DIR__ . '/includes/user_navbar.php';

if (file_exists($navbarFile)) {
    require_once $navbarFile;
}

?>


<div class="upload-wrapper">

    <div class="upload-card">


        <div class="text-center">


            <div class="upload-icon">

                <i class="bi bi-cloud-arrow-up-fill"></i>

            </div>


            <h2>
                Upload Your File
            </h2>


            <p class="subtitle">
                Securely store your documents in NexaVault.
            </p>


        </div>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="mb-4">

                <label class="form-label">

                    <i class="bi bi-file-earmark"></i>

                    Select File

                </label>


                <input
                    type="file"
                    name="file"
                    class="form-control"
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                    required
                >


                <div class="allowed-note mt-2">

                    Allowed:
                    PDF, DOC, DOCX, JPG, JPEG, PNG and ZIP

                </div>

            </div>


            <div class="mb-4">

                <label class="form-label">

                    <i class="bi bi-tags-fill"></i>

                    File Tags

                </label>


                <input
                    type="text"
                    name="tags"
                    class="form-control"
                    placeholder="Example: Project, College, Important"
                >


                <div class="allowed-note mt-2">

                    Use commas to separate multiple tags.

                </div>

            </div>


            <button
                type="submit"
                name="upload"
                class="upload-btn"
            >

                <i class="bi bi-cloud-arrow-up-fill me-2"></i>

                Upload File

                <i class="bi bi-arrow-right ms-2"></i>

            </button>


        </form>


        <div class="text-center mt-4">

            <a
                href="dashboard.php"
                class="back-dashboard"
            >

                <i class="bi bi-arrow-left"></i>

                Back to Dashboard

            </a>

        </div>


    </div>

</div>


</body>

</html>