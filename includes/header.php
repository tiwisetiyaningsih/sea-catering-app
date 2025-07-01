<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_directory = basename(dirname($_SERVER['PHP_SELF'])); 

$base_url = '/sea-catering/public/'; 
$auth_url = '/sea-catering/auth/';
$admin_url = $base_url . 'admin/';
$pages_url = $base_url; 

function isActive($currentPage, $targetPage, $currentDirectory = '') {
    if ($targetPage === 'index.php' && $currentPage === 'index.php') {
        return 'active';
    }
    if ($currentDirectory === 'public' && $currentPage === $targetPage) {
        return 'active';
    }
    if ($currentDirectory === 'auth' && ($currentPage === $targetPage)) { // For login/register/logout
         return 'active';
    }
    // For pages in public/admin/
    if ($currentDirectory === 'admin' && $currentPage === $targetPage) {
        return 'active';
    }
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEA Catering - Healthy Meals, Anytime, Anywhere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo $pages_url; ?>index.php">SEA Catering</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo $pages_url; ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>" href="<?php echo $pages_url; ?>menu.php">Menu / Meal Plans</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive($current_page, 'subscription.php', $current_directory); ?>" href="<?php echo $pages_url; ?>subscription.php">Subscription</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive($current_page, 'testimonials.php', $current_directory); ?>" href="<?php echo $pages_url; ?>testimonials.php">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive($current_page, 'contact.php', $current_directory); ?>" href="<?php echo $pages_url; ?>contact.php">Contact Us</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item <?php echo isActive($current_page, 'dashboard.php', $current_directory); ?>" href="<?php echo $pages_url; ?>dashboard.php">Dashboard</a></li>
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                                    <li><a class="dropdown-item <?php echo isActive($current_page, 'admin_dashboard.php', $current_directory); ?>" href="<?php echo $admin_url; ?>admin_dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $auth_url; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-success ms-2 <?php echo isActive($current_page, 'login.php', $current_directory); ?>" href="<?php echo $auth_url; ?>login.php">Login</a>
                        </li>
                        
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>