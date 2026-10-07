<?php

session_start();


// ========================================
// CHECK LOGIN
// ========================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}


// ========================================
// DATABASE
// ========================================

include 'includes/db.php';

$user_id = (int) $_SESSION['user_id'];


// ========================================
// GET FAVORITE FILES
// ========================================

$sql = "SELECT *
        FROM files
        WHERE user_id='$user_id'
        AND favorite=1
        AND deleted=0
        ORDER BY uploaded_at DESC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>NexaVault - Favorites</title>


<!-- Bootstrap -->

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
      rel="stylesheet">


<!-- Bootstrap Icons -->

<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


<!-- Dashboard CSS -->

<link rel="stylesheet"
      href="assets/css/dashboard.css?v=300">


<style>

body {
    min-height: 100vh;
}

.favorite-page {
    padding: 35px 0 60px;
}

.favorite-panel {

    background:
        linear-gradient(
            145deg,
            rgba(16, 32, 65, 0.96),
            rgba(8, 21, 45, 0.96)
        );

    border: 1px solid rgba(120, 155, 220, 0.15);

    border-radius: 20px;

    padding: 25px;

    box-shadow:
        0 15px 40px rgba(0,0,0,0.25);
}

.favorite-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 25px;
}

.favorite-header h2 {

    margin: 0;

    color: #f5f8ff;

    font-weight: 700;
}

.favorite-header p {

    margin: 5px 0 0;

    color: #91a0bb;
}


.favorite-file {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 18px;

    margin-bottom: 12px;

    border-radius: 14px;

    background: rgba(8, 22, 47, 0.80);

    border: 1px solid rgba(255,255,255,0.07);

    transition: 0.25s ease;
}

.favorite-file:hover {

    background: rgba(25, 50, 95, 0.65);

    transform: translateY(-2px);

    border-color: rgba(76,125,255,0.30);
}


.favorite-file-info {

    display: flex;

    align-items: center;

    gap: 15px;

    min-width: 0;
}


.favorite-file-icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background: rgba(245,158,11,0.14);

    color: #fbbf24;

    font-size: 22px;

    flex-shrink: 0;
}


.favorite-file-name {

    min-width: 0;
}


.favorite-file-name strong {

    display: block;

    color: #eaf1ff;

    font-size: 15px;

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;
}


.favorite-file-name small {

    color: #8495b3;

}


.favorite-actions {

    display: flex;

    align-items: center;

    gap: 8px;

    flex-shrink: 0;
}


.favorite-action {

    width: 38px;

    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    text-decoration: none;

    border: 1px solid rgba(255,255,255,0.08);

    background: rgba(255,255,255,0.05);

    color: #b8c7df;

    transition: 0.2s ease;
}

.favorite-action:hover {

    transform: translateY(-2px);

    color: white;
}


.favorite-action.star {

    color: #fbbf24;
}

.favorite-action.preview:hover {

    background: rgba(37,99,235,0.20);

    color: #60a5fa;
}

.favorite-action.download:hover {

    background: rgba(16,185,129,0.20);

    color: #34d399;
}

.favorite-action.remove:hover {

    background: rgba(239,68,68,0.20);

    color: #f87171;
}


.empty-favorites {

    text-align: center;

    padding: 70px 20px;
}


.empty-favorites-icon {

    width: 80px;

    height: 80px;

    margin: 0 auto 20px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 50%;

    background: rgba(245,158,11,0.10);

    color: #fbbf24;

    font-size: 35px;
}


.empty-favorites h4 {

    color: #f3f6ff;

    margin-bottom: 8px;
}


.empty-favorites p {

    color: #91a0bb;

}


@media (max-width: 768px) {

    .favorite-file {

        align-items: flex-start;

        flex-direction: column;
    }

    .favorite-actions {

        width: 100%;
    }

}

</style>

</head>


<body>


<!-- ========================================
     NAVBAR
======================================== -->

<nav class="nv-dashboard-navbar">

<div class="container-fluid px-lg-5">

<div class="d-flex align-items-center justify-content-between">


<a href="dashboard.php"
   class="nv-dashboard-brand text-decoration-none">

<img src="assets/images/logo.png"
     alt="NexaVault"
     class="dashboard-logo">

<div class="dashboard-brand-text">

<strong>
Nexa<span>Vault</span>
</strong>

<small>
Secure • Share • Trust
</small>

</div>

</a>


<div class="dashboard-nav-right">

<a href="dashboard.php"
   class="dashboard-nav-link">

<i class="bi bi-arrow-left"></i>

Dashboard

</a>

</div>


</div>

</div>

</nav>


<!-- ========================================
     MAIN
======================================== -->

<main class="favorite-page">

<div class="container-fluid px-lg-5">


<div class="favorite-panel">


<div class="favorite-header">

<div>

<h2>
<i class="bi bi-star-fill"
   style="color:#fbbf24;"></i>

Favorite Files
</h2>

<p>
Your saved and frequently accessed files
</p>

</div>


<a href="dashboard.php"
   class="btn btn-primary">

<i class="bi bi-arrow-left"></i>

Back to Dashboard

</a>

</div>


<?php

if ($result && mysqli_num_rows($result) > 0) {

    while ($row = mysqli_fetch_assoc($result)) {

        $fileId = (int) $row['id'];

        $fileName = htmlspecialchars(
            $row['original_name'] ?? 'Unknown File'
        );

        $fileType = strtolower(
            $row['file_type'] ?? ''
        );

        $fileSize = round(
            ($row['file_size'] ?? 0) / 1024,
            2
        );


        // File icon

        $fileIcon = "bi-file-earmark-fill";


        if (strpos($fileType, 'pdf') !== false) {

            $fileIcon = "bi-file-earmark-pdf-fill";

        }

        elseif (strpos($fileType, 'image') !== false) {

            $fileIcon = "bi-file-earmark-image-fill";

        }

        elseif (
            strpos($fileType, 'word') !== false ||
            strpos($fileType, 'document') !== false
        ) {

            $fileIcon = "bi-file-earmark-word-fill";

        }

        elseif (strpos($fileType, 'zip') !== false) {

            $fileIcon = "bi-file-earmark-zip-fill";

        }

?>



<div class="favorite-file">


<div class="favorite-file-info">


<div class="favorite-file-icon">

<i class="bi <?php echo $fileIcon; ?>"></i>

</div>


<div class="favorite-file-name">

<strong
title="<?php echo $fileName; ?>">

<?php echo $fileName; ?>

</strong>

<small>

<?php echo $fileSize; ?> KB

</small>

</div>


</div>


<!-- ACTIONS -->

<div class="favorite-actions">


<!-- Preview -->

<a href="preview.php?id=<?php echo $fileId; ?>"
   class="favorite-action preview"
   title="Preview">

<i class="bi bi-eye"></i>

</a>


<!-- Download -->

<a href="download.php?id=<?php echo $fileId; ?>"
   class="favorite-action download"
   title="Download">

<i class="bi bi-download"></i>

</a>


<!-- Remove Favorite -->

<a href="favorite.php?id=<?php echo $fileId; ?>"
   class="favorite-action star"
   title="Remove from Favorites">

<i class="bi bi-star-fill"></i>

</a>


</div>


</div>


<?php

    }

}

else {

?>


<div class="empty-favorites">

<div class="empty-favorites-icon">

<i class="bi bi-star"></i>

</div>


<h4>
No Favorite Files
</h4>


<p>
You haven't added any files to your favorites yet.
</p>


<a href="dashboard.php"
   class="btn btn-primary mt-2">

<i class="bi bi-folder2-open"></i>

Browse My Files

</a>

</div>


<?php

}

?>


</div>

</div>

</main>


</body>

</html>