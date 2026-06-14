<?php
$base_url = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if (substr($base_url, -7) === '/public') {
    $base_url = substr($base_url, 0, -7);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ImageLab - A professional, scalable web application for image conversion, compression, resizing, enhancement, and batch operations.">
    <title>ImageLab - Modern Image Suite</title>
    <!-- Favicon Packages -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= $base_url ?>/public/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>/public/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= $base_url ?>/public/favicon-16x16.png">
    <link rel="manifest" href="<?= $base_url ?>/public/site.webmanifest">
    <link rel="shortcut icon" href="<?= $base_url ?>/public/favicon.ico">

    
    <!-- Google Fonts: IBM Plex Sans & IBM Plex Mono for precision technical design -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@300;400;500;600&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
    
    <!-- Dynamic Base URL for relative AJAX calls -->
    <script>
        const ImageLabBaseUrl = '<?= $base_url ?>';
    </script>
    <!-- Fabric.js Canvas Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
</head>
<body>
    <!-- App Root Wrapper -->
    <div class="app-wrapper">
