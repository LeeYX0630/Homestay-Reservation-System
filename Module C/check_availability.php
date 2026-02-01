<?php
// module_c/check_availability.php - 日历可视化版
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    // 如果没有 user_id，说明没登录
    // 注意：这里的路径 '../Module A/login.php' 假设你的登录页在 Module A 文件夹
    // 如果文件夹名有空格，浏览器通常能处理，但最好确认文件夹名
    echo "<script>
            alert('Please login first to book a room!'); 
            window.location.href='../Module A/login.php';
          </script>";
    exit();
}

// ... 剩下的代码保持不变 ...


$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$msg = "";
$room_details = null;
$max_guests = 4; // 默认

// 获取房间信息
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $room_details = $result->fetch_assoc();
        $max_guests = isset($room_details['capacity']) ? $room_details['capacity'] : 4;
    }
}

// 处理表单提交 (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... 这里保留你之前写的 POST 处理逻辑 (验证+跳转) ...
    // ... 为节省篇幅，请把你上一版 check_availability.php 里的 POST 代码块复制过来 ...
    // ... 包含 $guests, $check_in 等验证，以及跳转到 checkout_payment.php 的逻辑 ...
    // 如果你只是想测试日历，可以暂时只写简单的 echo "Debug"; 
    // 但建议直接复用上一段代码的核心逻辑
    
    // (以下是简化的示例逻辑，请替换为你上一版的完整逻辑)
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'];
    
    // 简单验证演示
    $check_sql = "SELECT * FROM bookings WHERE room_id = '$room_id' AND booking_status != 'cancelled' AND ((check_in_date < '$check_out' AND check_out_date > '$check_in'))";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        $msg = "<div class='alert error'>Date unavailable! Please check the calendar.</div>";
    } else {
        header("Location: checkout_payment.php?room_id=$room_id&check_in=$check_in&check_out=$check_out&guests=$guests");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Dates</title>
    <link rel="stylesheet" href="../css/style.css">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f8f9fa; }
        .container-flex { display: flex; flex-wrap: wrap; max-width: 1000px; margin: 40px auto; gap: 20px; }
        
        /* 左侧：日历区域 */
        .calendar-section { flex: 2; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        #calendar { max-height: 600px; }

        /* 右侧：表单区域 */
        .form-section { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: fit-content; }
        
        .legend { display: flex; gap: 15px; margin-bottom: 15px; font-size: 14px; }
        .dot { width: 12px; height: 12px; display: inline-block; border-radius: 2px; margin-right: 5px; }
        .dot-gray { background: #808080; } /* Booked */
        .dot-blue { background: #3788d8; } /* Cleaning */
        .dot-green { background: #28a745; } /* Selected */

        .btn-book { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 15px; }
        .btn-book:disabled { background: #ccc; cursor: not-allowed; }
        
        input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
    </style>
</head>
<body>

<nav style="background:#333; padding:15px;">
    <div style="max-width:1000px; margin:0 auto;">
        <a href="../index.php" style="color:white; text-decoration:none;">&larr; Back to Home</a>
    </div>
</nav>

<div class="container-flex">
    
    <div class="calendar-section">
        <h3>Availability Calendar</h3>
        <div class="legend">
            <span><span class="dot dot-gray"></span>Booked</span>
            <span><span class="dot dot-blue"></span>Cleaning/Maintenance</span>
            <span><span class="dot dot-green"></span>Your Selection</span>
        </div>
        <div id='calendar'></div>
    </div>

    <div class="form-section">
        <h3><?php echo htmlspecialchars($room_details['room_name']); ?></h3>
        <p style="color:#e74c3c; font-weight:bold;">RM <?php echo $room_details['price_per_night']; ?> / night</p>
        
        <?php echo $msg; ?>

        <form method="POST" id="bookingForm">
            <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
            
            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold;">Check-in</label>
                <input type="date" name="check_in" id="check_in" required readonly style="width:100%; padding:8px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold;">Check-out</label>
                <input type="date" name="check_out" id="check_out" required readonly style="width:100%; padding:8px;">
            </div>

            <div style="margin-bottom:15px;">
                <label style="display:block; font-weight:bold;">Guests</label>
                <input type="number" name="guests" value="1" min="1" max="<?php echo $max_guests; ?>" style="width:100%; padding:8px;">
            </div>

            <p style="font-size:13px; color:#666;">
                <i class="bi bi-info-circle"></i> Drag on the calendar to select your dates.
            </p>

            <button type="submit" class="btn-book" id="bookBtn" disabled>Proceed to Checkout</button>
        </form>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var checkInInput = document.getElementById('check_in');
        var checkOutInput = document.getElementById('check_out');
        var bookBtn = document.getElementById('bookBtn');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            selectable: true, // 允许用户框选日期
            validRange: {
                start: '<?php echo date("Y-m-d"); ?>' // 不能选过去的时间
            },
            // 从刚才写的 PHP API 获取已预订数据
            events: 'api_get_bookings.php?room_id=<?php echo $room_id; ?>',
            
            // 当用户选择日期时触发
            select: function(info) {
                // FullCalendar 的 endStr 是结束日期的后一天（exclusive），这正好符合逻辑
                // 比如选了 1号和2号，endStr 会是 3号。
                // 但是 check-out 如果是 3号，代表3号早上走，所以这里直接用 info.endStr 是对的。
                
                checkInInput.value = info.startStr;
                checkOutInput.value = info.endStr;
                
                // 简单的视觉反馈：激活按钮
                bookBtn.disabled = false;
                bookBtn.innerHTML = "Book " + info.startStr + " to " + info.endStr;
            },

            // 针对“打扫日”或“已预订”日期的特殊样式
            eventDidMount: function(info) {
                if (info.event.display === 'background') {
                    // 如果是背景事件，给它加个提示（可选）
                    info.el.style.pointerEvents = 'none'; // 让它不能被点击
                }
            }
        });

        calendar.render();
    });
</script>

</body>
</html>