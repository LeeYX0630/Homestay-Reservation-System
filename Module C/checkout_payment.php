<?php
// checkout_payment.php - 结账与订单生成 (Module C)
session_start();
require_once 'db_connection.php';

// --- 1. 接收参数与计算逻辑 ---
// 从上一页 (check_availability.php) 获取参数
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$check_in = isset($_GET['check_in']) ? $_GET['check_in'] : '';
$check_out = isset($_GET['check_out']) ? $_GET['check_out'] : '';

// 简单的防呆：如果直接访问这个页面没有参数，跳回首页
if ($room_id == 0 || empty($check_in) || empty($check_out)) {
    header("Location: index.php");
    exit();
}

// 获取房间单价
$sql_room = "SELECT * FROM rooms WHERE room_id = $room_id";
$result_room = $conn->query($sql_room);
$room = $result_room->fetch_assoc();

// 计算天数
$date1 = new DateTime($check_in);
$date2 = new DateTime($check_out);
$interval = $date1->diff($date2);
$days = $interval->days;

// 计算总价 (Must Have) [cite: 245]
$total_price = $room['price_per_night'] * $days;

// --- 2. 处理支付提交 (Must Have: 生成订单) ---
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 模拟：检查用户是否登录 (Module A完成前，我们暂时手动指定 user_id = 1 用于测试)
    // 正式上线时请去掉 "|| 1"，只保留 $_SESSION['user_id']
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; 

    // 获取支付信息 (仅做演示，不存储敏感卡号)
    $card_holder = $_POST['card_holder'];
    $card_number = $_POST['card_number'];

    // 写入数据库 (bookings table) [cite: 247]
    // 状态默认为 'confirmed'，支付状态 'paid' (因为是模拟支付成功)
    $sql_insert = "INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, booking_status, payment_status) 
                   VALUES ('$user_id', '$room_id', '$check_in', '$check_out', '$total_price', 'confirmed', 'paid')";

    if ($conn->query($sql_insert) === TRUE) {
        // 成功反馈 [cite: 340]
        echo "<script>alert('Payment Successful! Your booking is confirmed.'); window.location.href='index.php';</script>";
        exit();
    } else {
        $msg = "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout & Payment</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-container { max-width: 800px; margin: 50px auto; padding: 20px; display: flex; gap: 30px; }
        .order-summary, .payment-form { flex: 1; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff; }
        .order-summary h3 { border-bottom: 2px solid #333; padding-bottom: 10px; margin-top: 0; }
        .total-price { font-size: 1.5em; color: #e74c3c; font-weight: bold; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-pay { background-color: #28a745; color: white; width: 100%; padding: 12px; border: none; font-size: 1.1em; cursor: pointer; border-radius: 4px; }
        .btn-pay:hover { background-color: #218838; }
        
        /* 响应式：手机端上下排列 */
        @media (max-width: 768px) { .checkout-container { flex-direction: column; } }
    </style>
</head>
<body>

<nav style="background:#333; padding:15px; color:#fff;">
    <div style="max-width:1200px; margin:0 auto;">
        <span style="font-size:1.2em; font-weight:bold;">Homestay Payment</span>
        <a href="index.php" style="float:right; color:#fff; text-decoration:none;">Cancel & Exit</a>
    </div>
</nav>

<div class="checkout-container">
    
    <div class="order-summary">
        <h3>Order Summary</h3>
        <p><strong>Room:</strong> <?php echo $room['room_name']; ?></p>
        <p><strong>Check-in:</strong> <?php echo $check_in; ?></p>
        <p><strong>Check-out:</strong> <?php echo $check_out; ?></p>
        <p><strong>Duration:</strong> <?php echo $days; ?> Night(s)</p>
        <p><strong>Price per night:</strong> RM <?php echo $room['price_per_night']; ?></p>
        <hr>
        <div class="total-price">Total: RM <?php echo number_format($total_price, 2); ?></div>
    </div>

    <div class="payment-form">
        <h3>Payment Details</h3>
        <?php echo $msg; ?>
        
        <form method="POST" onsubmit="return confirm('Confirm payment of RM <?php echo $total_price; ?>?');">
            <div class="form-group">
                <label>Cardholder Name</label>
                <input type="text" name="card_holder" placeholder="e.g. John Doe" required>
            </div>
            
            <div class="form-group">
                <label>Card Number (Demo Only)</label>
                <input type="text" name="card_number" placeholder="1234 5678 9876 5432" required maxlength="19">
            </div>

            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Expiry</label>
                    <input type="text" placeholder="MM/YY" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>CVV</label>
                    <input type="text" placeholder="123" required maxlength="3">
                </div>
            </div>

            <button type="submit" class="btn-pay">Pay & Confirm Booking</button>
        </form>
    </div>

</div>

</body>
</html>