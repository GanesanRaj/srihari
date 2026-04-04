<?php
if (session_status () == PHP_SESSION_NONE) {
    session_start ();
    }

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if ( ! isset ($_SESSION[ 'user_id' ])) {
    header ( "Location: login.php" );
    exit ();
    }
?>
<html lang="en" data-layout="topnav" data-topbar-color="dark" data-menu-color="light" data-layout-width="fluid">

<head>
    <meta charset="utf-8">
    <title>Dashboard | Sri Hari Agencies</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="UBold is a modern, responsive admin dashboard available on ThemeForest. Ideal for building CRM, CMS, project management tools, and custom web applications with a clean UI, flexible layouts, and rich features.">
    <meta name="keywords"
        content="UBold, admin dashboard, ThemeForest, Bootstrap 5 admin, responsive admin, CRM dashboard, CMS admin, web app UI, admin theme, premium admin template">
    <meta name="author" content="Coderthemes">

    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/logo-black.png">

    <!-- Theme Config Js -->
    <script src="assets/js/config.js"></script>

    <!-- Vendor css -->
    <link href="assets/css/vendors.min.css" rel="stylesheet" type="text/css">

    <!-- App css -->
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>
<style>
    * {
        font-family: 'Poppins', sans-serif;
        font-weight: 500 !important;
    }

    .app-topbar {
        height: 48px !important;
        background: #140f37;
    }

    .topbar-menu {
        height: 48px !important;
    }

    .topnav {
        top: 48px !important;
    }

    .card-body {
        padding: 5px 5px;
    }

    .table {
        font-size: 14px;
        text-transform: capitalize;
    }

    td,
    th {
        padding: 5px 5px !important;
    }

    td {
        line-height: 20px !important;
    }

    th {
        background: #140f3721 !important;
        padding: 10px 10px !important;
    }

    .content {
        margin-top: 8px;
    }

    .light {
        color: #9a9a9a !important;
        font-weight: bold;
    }
</style>