<?php
// admin/admin_manage_booking.php
session_start();
require_once '../includes/db_connection.php';

// --- 权限检查 (Module A 完成后请取消注释) ---
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { die("Access Denied"); }

// --- 处理 POST 请求 (取消或删除) ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $booking_id = intval($_POST['booking_id']);

    if ($action === 'cancel') {
        $sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = $booking_id";
        if ($conn->query($sql)) $msg = "<div class='alert success'>Booking #$booking_id cancelled successfully.</div>";
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM bookings WHERE booking_id = $booking_id";
        if ($conn->query($sql)) $msg = "<div class='alert success'>Booking #$booking_id deleted permanently.</div>";
    }
}

// --- 获取所有订单列表 (联表查询：获取用户名和房间名) ---
// 注意：如果你的数据库还没有 users 表，可以先去掉 u.full_name 和 LEFT JOIN users
$sql = "SELECT b.*, r.room_name 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.room_id 
        ORDER BY b.booking_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Bookings</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-container { display: flex; }
        .sidebar { width: 250px; background: #333; color: white; min-height: 100vh; padding: 20px; }
        .sidebar a { display: block; color: #ccc; padding: 10px; text-decoration: none; margin-bottom: 5px; }
        .sidebar a:hover { background: #555; color: white; border-radius: 4px; }
        .sidebar a.active { background: #007bff; color: white; } /* 高亮当前页 */
        .main-content { flex: 1; padding: 30px; background: #f4f4f4; }

        /* 表格样式 */
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        tr:hover { background-color: #f1f1f1; }
        
        /* 状态标签 */
        .badge { padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }

        /* 按钮 */
        .btn-sm { padding: 5px 10px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-cancel { background: #ffc107; color: #333; }
        .btn-delete { background: #dc3545; }
        .alert { padding: 10px; margin-bottom: 15px; background: #d4edda; color: #155724; border-radius: 4px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="sidebar">
        <h2 style="color:white; margin-top:0;">Admin Panel</h2>
        <hr style="border-color:#555;">
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="admin_manage_booking.php" class="active">Manage Bookings</a>
        <a href="../index.php" target="_blank">View Site &rarr;</a>
    </div>

    <div class="main-content">
        <h1>Manage Bookings</h1>
        <?php echo $msg; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Room</th>
                    <th>Dates</th>
                    <th>Total (RM)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                            <td>
                                <div style="font-size:13px; color:#555;">In: <?php echo $row['check_in_date']; ?></div>
                                <div style="font-size:13px; color:#555;">Out: <?php echo $row['check_out_date']; ?></div>
                            </td>
                            <td><?php echo number_format($row['total_price'], 2); ?></td>
                            <td>
                                <?php 
                                    $statusClass = ($row['booking_status'] == 'confirmed') ? 'badge-confirmed' : 'badge-cancelled';
                                    echo "<span class='badge $statusClass'>" . ucfirst($row['booking_status']) . "</span>";
                                ?>
                            </td>
                            <td>
                                <?php if($row['booking_status'] != 'cancelled'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this booking?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn-sm btn-cancel">Cancel</button>
                                </form>
                                <?php endif; ?>

                                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this record?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['booking_id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-sm btn-delete">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No bookings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>