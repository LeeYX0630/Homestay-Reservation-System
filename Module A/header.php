<?php
// for header

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'db_connection.php';

// 1. Get current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// 2. List of pages where Login/Register buttons should be HIDDEN
$auth_pages = [
    'login.php', 
    'register.php', 
    'admin_login.php', 
    'forgot_password.php', 
    'reset_password.php'
];

// Set default page title
if (!isset($page_title)) {
    $page_title = "Teh Tarik No Tarik Homestay";
}

// Check user login status
$nav_is_logged_in = false;
$nav_user_name = "";
$nav_profile_pic = "uploads/default.png";

if (isset($_SESSION['user_id'])) {
    $nav_is_logged_in = true;
    $nav_uid = $_SESSION['user_id'];
    
    // Fetch user details
    $nav_sql = "SELECT full_name, profile_image FROM users WHERE user_id = '$nav_uid'";
    $nav_res = $conn->query($nav_sql);
    
    if ($nav_res && $nav_res->num_rows > 0) {
        $nav_row = $nav_res->fetch_assoc();
        $nav_user_name = $nav_row['full_name'];
        if (!empty($nav_row['profile_image'])) {
            $nav_profile_pic = "uploads/" . $nav_row['profile_image'];
        }
    }
}
?>
<!doctype html>
<html lang="en" class="h-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
      body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
      .bg-brand-dark { background-color: #333333 !important; }
      .profile-img-large { width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 5px solid #fff; box-shadow: 0 0 15px rgba(0,0,0,0.2); }
      .welcome-header { border-bottom: 2px solid #dee2e6; margin-bottom: 30px; padding-bottom: 10px; }
      .welcome-text { font-weight: 700; color: #333; }
    </style>
  </head>
  
  <body class="d-flex flex-column h-100">
    <nav class="navbar navbar-dark p-3 shadow bg-brand-dark">
      <div class="container-fluid px-4">
        
        <?php
          // Default: Logo goes to Home, no popup
          $logo_link = "index.php";
          $logo_onclick = "";

          // only triggered if admin is logged in
          if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
              $logo_link = "logout.php?redirect=home"; 
              $logo_onclick = "return confirm('⚠️ Security Alert:\\n\\nGoing to the Home Page will log you out of the Admin Panel.\\n\\nAre you sure you want to proceed?');";
          }
        ?>

        <a class="navbar-brand d-flex align-items-center" href="<?php echo $logo_link; ?>" onclick="<?php echo $logo_onclick; ?>">
          <img src="tehtariklogo.jpg" alt="Logo" style="height: 32px; width: auto;" class="d-inline-block align-text-top me-2 rounded">
          <span class="fw-bold">Teh Tarik No Tarik</span>
        </a>
        
        <div class="d-flex align-items-center">
  
        <?php if ($nav_is_logged_in): ?>
      
            <div class="nav-item d-flex align-items-center me-3">
               <img src="<?php echo $nav_profile_pic; ?>" alt="User" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid white;" />
               <span class="text-white d-none d-sm-block"><?php echo $nav_user_name; ?></span>
            </div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
               <a class="btn btn-warning btn-sm me-2 fw-bold" href="admin_dashboard.php" title="Go to Admin Dashboard">
                  <i class="bi bi-speedometer2"></i> Dashboard
               </a>
            <?php endif; ?>

            <?php
                // if normal user, just normal logout
                $logout_url = "logout.php";
                $logout_attr = "";

                // if admin user, add redirect and confirmation
                if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
                    // redirect to home after logout (module C)
                    $logout_url = "logout.php?redirect=home";
                    $logout_attr = "onclick=\"return confirm('⚠️ Confirm Logout:\\n\\nAre you sure you want to log out of the Admin Panel?');\"";
                }
            ?>
            <a class="btn btn-outline-light btn-sm" href="<?php echo $logout_url; ?>" <?php echo $logout_attr; ?>>Sign out</a>
  
        <?php else: ?>
            
            <?php if (!in_array($current_page, $auth_pages)): ?>
              <a class="btn btn-outline-light btn-sm me-2" href="login.php">Login</a>
              <a class="btn btn-warning btn-sm" href="register.php">Sign Up</a>
            <?php endif; ?>
      
        <?php endif; ?>

        </div>
      </div>
    </nav>