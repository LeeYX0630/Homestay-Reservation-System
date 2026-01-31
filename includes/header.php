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
          // --- 1. 路径修正逻辑 ---
          if (isset($is_home_root) && $is_home_root === true) {
              $path_prefix_root = "";          
              $path_prefix_module_a = "Module A/"; 
              $logout_redirect = "index.php";   
          } else {
              $path_prefix_root = "../";        
              $path_prefix_module_a = "";       
              $logout_redirect = "../index.php";
          }
          
          // --- 2. 补全缺失的变量定义 (修复报错的关键) ---
          // 定义 Logo 点击事件（通常为空，或者是回首页）
          $logo_link = $path_prefix_root . "index.php";
          $logo_onclick = ""; 

          // 定义登出按钮的确认弹窗属性
          $logout_attr = 'onclick="return confirm(\'Are you sure you want to sign out?\');"';
?>

        <a class="navbar-brand d-flex align-items-center" href="<?php echo $logo_link; ?>" onclick="<?php echo $logo_onclick; ?>">
          <img src="tehtariklogo.jpg" alt="Logo" style="height: 32px; width: auto;" class="d-inline-block align-text-top me-2 rounded">
          <span class="fw-bold">Teh Tarik No Tarik</span>
        </a>
        
        <div class="d-flex align-items-center">
  
        <?php if ($nav_is_logged_in): ?>
             <div class="nav-item d-flex align-items-center me-3">
               <?php 
                 $display_pfp = $nav_profile_pic;
                 if(isset($is_home_root) && $is_home_root) {
                    // 如果头像是默认的 default.png 且不在 uploads/ 下，可能需要特殊处理，
                    // 但通常数据库存的是文件名。这里假设 uploads 文件夹在 Module A 下。
                    if (strpos($nav_profile_pic, 'uploads/') === 0) {
                        $display_pfp = "Module A/" . $nav_profile_pic;
                    }
                 }
               ?>
               <img src="<?php echo $display_pfp; ?>" alt="User" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid white;" />
               <span class="text-white d-none d-sm-block"><?php echo $nav_user_name; ?></span>
            </div>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
               <a class="btn btn-warning btn-sm me-2 fw-bold" href="<?php echo $path_prefix_root; ?>Module C/admin_dashboard.php" title="Go to Admin Dashboard">
                  <i class="bi bi-speedometer2"></i> Dashboard
               </a>
            <?php endif; ?>

            <a class="btn btn-outline-light btn-sm" href="<?php echo $path_prefix_module_a; ?>logout.php" <?php echo $logout_attr; ?>>Sign out</a>
  
        <?php else: ?>
            
            <?php if (!in_array($current_page, $auth_pages)): ?>
              <a class="btn btn-outline-light btn-sm me-2" href="<?php echo $path_prefix_module_a; ?>login.php">Login</a>
              <a class="btn btn-warning btn-sm" href="<?php echo $path_prefix_module_a; ?>register.php">Sign Up</a>
            <?php endif; ?>
      
        <?php endif; ?>

        </div>

        </div>
      </div>
    </nav>