<?php
// admin/admin_dashboard.php
session_start();
require_once '../includes/db_connection.php';

// --- 权限检查 (Module A 完成后请取消注释) ---
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login.php");
//     exit("Access Denied");
// }

// 1. 获取统计数据
// 总订单数
$sql_count = "SELECT COUNT(*) as total FROM bookings";
$res_count = $conn->query($sql_count);
$total_bookings = $res_count->fetch_assoc()['total'];

// 总收入 (只计算已确认/已支付的)
$sql_revenue = "SELECT SUM(total_price) as revenue FROM bookings WHERE booking_status = 'confirmed'";
$res_revenue = $conn->query($sql_revenue);
$total_revenue = $res_revenue->fetch_assoc()['revenue'] ?? 0;

// 待入住 (Confirmed 且入住时间在未来)
$today = date('Y-m-d');
$sql_upcoming = "SELECT COUNT(*) as upcoming FROM bookings WHERE booking_status = 'confirmed' AND check_in_date >= '$today'";
$res_upcoming = $conn->query($sql_upcoming);
$upcoming_bookings = $res_upcoming->fetch_assoc()['upcoming'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css"> <style>
        .dashboard-container { display: flex; }
        .sidebar { width: 250px; background: #333; color: white; min-height: 100vh; padding: 20px; }
        .sidebar a { display: block; color: #ccc; padding: 10px; text-decoration: none; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background: #555; color: white; border-radius: 4px; }
        .main-content { flex: 1; padding: 30px; background: #f4f4f4; }
        
        /* 统计卡片样式 */
        .stats-grid { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { flex: 1; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { margin: 0 0 10px; color: #777; font-size: 14px; text-transform: uppercase; }
        .stat-number { font-size: 32px; font-weight: bold; color: #333; margin: 0; }
        .revenue-text { color: #28a745; }
    </style>
</head>
<body>

<div class="dashboard-container">
<div class="sidebar">
    <h2 style="color:white; margin-top:0;">Admin Panel</h2>
    <hr style="border-color:#555;">
    <a href="admin_dashboard.php" class="active">Dashboard</a>
    <a href="admin_manage_booking.php">Manage Bookings</a>
    <a href="add_admin.php">Add New Admin</a> 
    <a href="../index.php" target="_blank">View Site &rarr;</a>
</div>

    <div class="main-content">
        <h1>Overview</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <p class="stat-number"><?php echo $total_bookings; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p class="stat-number revenue-text">RM <?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Upcoming Stays</h3>
                <p class="stat-number"><?php echo $upcoming_bookings; ?></p>
            </div>
        </div>

<div style="background: white; padding: 20px; border-radius: 8px;">
    <h2>Quick Actions</h2>
    <p>Manage your system efficiently.</p>
    <div style="display: flex; gap: 10px;">
        <a href="admin_manage_booking.php" class="btn-primary" style="background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">Go to Bookings</a>
        
        <a href="add_admin.php" class="btn-primary" style="background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:4px;">+ Add Admin</a>
    </div>
</div>
    </div>
</div>

</body>
</html>