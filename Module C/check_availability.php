<?php
// module_c/check_availability.php - 负责检查房源可用性
session_start();

// [修改 1] 引用上一级目录的数据库连接文件
require_once '../includes/db_connection.php';

// 初始化变量
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$msg = "";
$is_available = false;
$room_details = null;
$check_in = '';
$check_out = '';

// 如果有 room_id，先获取房间信息（显示在页面上让用户知道定的是啥）
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $room_details = $result->fetch_assoc();
    }
}

// 处理表单提交 (当用户点击 "Check Availability")
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    
    // 简单的日期验证
    if ($check_in >= $check_out) {
        $msg = "<div class='alert error'>Check-out date must be after Check-in date.</div>";
    } else {
        // --- 核心逻辑：检查数据库是否有冲突的订单 ---
        // 逻辑口诀：(新入 < 旧退) AND (新退 > 旧入) -> 这就是时间重叠
        // 排除已取消 (cancelled) 的订单
        $check_sql = "SELECT * FROM bookings 
                      WHERE room_id = '$room_id' 
                      AND booking_status != 'cancelled'
                      AND (
                          (check_in_date < '$check_out' AND check_out_date > '$check_in')
                      )";
        
        $check_result = $conn->query($check_sql);

        if ($check_result->num_rows > 0) {
            $msg = "<div class='alert error'>Sorry! This room is already booked for the selected dates.</div>";
            $is_available = false;
        } else {
            $msg = "<div class='alert success'>Room is available! Proceed to book.</div>";
            $is_available = true; // 标记为可用，显示“立即预订”按钮
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Availability</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
        .booking-container { max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .room-preview { background: #f9f9f9; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 5px solid #333; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-check { background-color: #333; color: white; border: none; padding: 10px 20px; cursor: pointer; width: 100%; font-size: 16px; border-radius: 4px; }
        .btn-check:hover { background-color: #555; }
        .btn-book { background-color: #28a745; color: white; text-decoration: none; display: block; text-align: center; padding: 12px; margin-top: 10px; border-radius: 4px; font-weight: bold; }
        .btn-book:hover { background-color: #218838; }
    </style>
</head>
<body>

<nav style="background:#333; padding:15px; color:#fff;">
    <div style="max-width:600px; margin:0 auto;">
        <a href="../index.php" style="color:white; text-decoration:none;">&larr; Back to Home</a>
    </div>
</nav>

<div class="booking-container">
    <h2>Check Availability</h2>
    
    <?php if ($room_details): ?>
        <div class="room-preview">
            <h3 style="margin-top:0;"><?php echo htmlspecialchars($room_details['room_name']); ?></h3>
            <p><strong>Price:</strong> RM <?php echo number_format($room_details['price_per_night'], 2); ?> / night</p>
            <p><?php echo htmlspecialchars($room_details['description']); ?></p>
        </div>
    <?php else: ?>
        <p>Room not found. Please select a room from the <a href="../index.php">Home Page</a>.</p>
    <?php endif; ?>

    <?php echo $msg; ?>

    <?php if ($room_details): ?>
    <form method="POST" action="">
        <input type="hidden" name="room_id" value="<?php echo $room_details['room_id']; ?>">
        
        <div class="form-group">
            <label>Check-in Date:</label>
            <input type="date" name="check_in" value="<?php echo htmlspecialchars($check_in); ?>" required min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
            <label>Check-out Date:</label>
            <input type="date" name="check_out" value="<?php echo htmlspecialchars($check_out); ?>" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
        </div>

        <button type="submit" class="btn-check">Check Dates</button>
    </form>
    <?php endif; ?>

    <?php if ($is_available && $check_in && $check_out): ?>
        <hr>
        <a href="checkout_payment.php?room_id=<?php echo $room_id; ?>&check_in=<?php echo $check_in; ?>&check_out=<?php echo $check_out; ?>" class="btn-book">Proceed to Checkout</a>
    <?php endif; ?>
    
</div>

</body>
</html>