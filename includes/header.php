<?php
// includes/header.php - 支持用户和管理员双重身份
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once 'db_connection.php';

// 1. 获取当前页面文件名
$current_page = basename($_SERVER['PHP_SELF']);

// 2. 在这些页面隐藏 Login/Register 按钮
$auth_pages = [
    'login.php', 
    'register.php', 
    'admin_login.php', 
    'forgot_password.php', 
    'reset_password.php'
];

// 设置默认标题
if (!isset($page_title)) {
    $page_title = "Teh Tarik No Tarik Homestay";
}

// 初始化导航栏变量
$nav_is_logged_in = false;
$nav_user_name = "";
// 默认头像路径 (假设在 Module A/uploads 或 uploads 下，这里先给个通用值)
$nav_profile_pic = "uploads/default.png";

// --- 【修改 1】双重身份检查逻辑 ---

// 检查是否是普通用户
if (isset($_SESSION['user_id'])) {
    $nav_is_logged_in = true;
    $nav_uid = $_SESSION['user_id'];
    
    // 查询用户信息
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
// 【新增】检查是否是管理员
elseif (isset($_SESSION['admin_id'])) {
    $nav_is_logged_in = true;
    // 管理员通常没有头像，使用默认图
    // 从 Session 获取用户名 (在 admin_login.php 里已经设置了 $_SESSION['username'])
    $nav_user_name = isset($_SESSION['username']) ? $_SESSION['username'] . " (Admin)" : "Administrator";
    $nav_profile_pic = "uploads/default.png"; 
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
      .hover-white:hover { color: #fff !important; }
    </style>
  </head>
  
  <body class="d-flex flex-column h-100">
    <nav class="navbar navbar-dark p-3 shadow bg-brand-dark">
      <div class="container-fluid px-4">
        
<?php
          // --- 路径修正逻辑 ---
          // 根据是否在根目录 ($is_home_root)，调整链接前缀
          if (isset($is_home_root) && $is_home_root === true) {
              $path_prefix_root = "";          
              $path_prefix_module_a = "Module A/"; 
              $path_prefix_module_c = "Module C/";
          } else {
              $path_prefix_root = "../";        
              $path_prefix_module_a = ""; // 假设大多数文件都在 Module 文件夹内，跳回上一级再进其他 Module 比较麻烦，这里简化处理
              // 如果我们在 Module A 或 C 内部，去 Module C 是平级或同级
              // 为了保险，统一用 ../Module C/ 这种绝对相对路径
              $path_prefix_module_c = "../Module C/";
              $path_prefix_module_a = "../Module A/";
          }
          
          // 如果已经在 Module C 里面，去 Module C 的文件不需要前缀
          // 这里做一个简单的判断：如果当前文件路径包含 Module C
          if (strpos($_SERVER['PHP_SELF'], 'Module C') !== false) {
             $path_prefix_module_c = "";
             $path_prefix_module_a = "../Module A/";
             $path_prefix_root = "../";
          }
          // 如果在 Module A 里面
          if (strpos($_SERVER['PHP_SELF'], 'Module A') !== false) {
             $path_prefix_module_a = "";
             $path_prefix_module_c = "../Module C/";
             $path_prefix_root = "../";
          }
          
          $logo_link = $path_prefix_root . "index.php";
          $logo_onclick = ""; 
          $logout_attr = 'onclick="return confirm(\'Are you sure you want to sign out?\');"';
?>

        <a class="navbar-brand d-flex align-items-center" href="<?php echo $logo_link; ?>" onclick="<?php echo $logo_onclick; ?>">
          <img src="<?php echo $path_prefix_root; ?>tehtariklogo.jpg" alt="Logo" style="height: 32px; width: auto;" class="d-inline-block align-text-top me-2 rounded">
          <span class="fw-bold">Teh Tarik No Tarik</span>
        </a>
        
        <div class="d-flex align-items-center">
  
        <?php if ($nav_is_logged_in): ?>
             <div class="nav-item d-flex align-items-center me-3">
               <?php 
                 // 简单的头像路径处理
                 $display_pfp = $nav_profile_pic;
                 // 如果是默认头像且我们在模块文件夹内，需要加 ../
                 if (!file_exists($display_pfp) && strpos($display_pfp, '../') === false) {
                     // 尝试加前缀
                     $try_path = $path_prefix_module_a . $display_pfp; // 假设 uploads 在 Module A
                     // 这里不做复杂判断，直接输出一个大致正确的路径
                     // $display_pfp = $path_prefix_module_a . "uploads/default.png";
                 }
               ?>
               <img src="<?php echo $path_prefix_root; ?>images/user_icon.png" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($nav_user_name); ?>&background=random'" alt="User" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover; border: 2px solid white;" />
               <span class="text-white d-none d-sm-block"><?php echo htmlspecialchars($nav_user_name); ?></span>
            </div>

            <?php if (isset($_SESSION['admin_id'])): ?>
               <a class="btn btn-warning btn-sm me-2 fw-bold" href="<?php echo $path_prefix_module_c; ?>admin_dashboard.php" title="Go to Admin Dashboard">
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