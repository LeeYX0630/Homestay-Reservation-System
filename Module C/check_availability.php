<?php
// module_c/check_availability.php
session_start();
require_once '../includes/db_connection.php';

// 1. 获取 room_id
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

// --- 【新增】Admin 拦截逻辑 ---
if (isset($_SESSION['admin_id'])) {
    echo "<script>
            alert('⚠️ Administrator Access Restriction:\\n\\nAdmins cannot book rooms directly.\\nPlease log out and sign in with a Customer account to make a booking.');            
            window.location.href='../Module C/admin_dashboard.php';
          </script>";
    exit();
}

// 2. 强制登录检查 (带回跳参数 redirect)
if (!isset($_SESSION['user_id'])) {
    // 构造回跳地址：登录成功后，跳回当前页面并带上 room_id
    $return_url = "../Module C/check_availability.php?room_id=" . $room_id;
    $encoded_url = urlencode($return_url);

    echo "<script>
            alert('Please login first to book a room!'); 
            window.location.href='../Module A/login.php?redirect=$encoded_url';
          </script>";
    exit();
}

$msg = "";
$room_details = null;
$max_guests = 4; // 默认

// 3. 获取房间信息
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $room_details = $result->fetch_assoc();
        $max_guests = isset($room_details['capacity']) ? $room_details['capacity'] : 4;
    }
}

// 4. 处理表单提交 (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 【关键】强制从 POST 表单中获取 room_id，防止 URL 参数丢失
    $room_id = intval($_POST['room_id']); 
    
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'];
    
    // 基础防呆：防止日期为空
    if (empty($check_in) || empty($check_out)) {
         $msg = "<div class='alert error'>Please select dates from the calendar.</div>";
    } else {
        // A. 检查房间是否被占用
        $check_sql = "SELECT * FROM bookings 
                      WHERE room_id = '$room_id' 
                      AND booking_status != 'cancelled' 
                      AND ((check_in_date < '$check_out' AND check_out_date > '$check_in'))";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $msg = "<div class='alert error'>Date unavailable! Please check the calendar.</div>";
        } else {
            // B. 房间是空的 -> 检查用户是否“多重预订” (Double Booking Check)
            $user_conflict = false;
            $conflict_msg = "";

            if (isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                
                // 查询该用户在同一时段是否有其他未取消的订单
                $u_sql = "SELECT r.room_name, b.check_in_date, b.check_out_date 
                          FROM bookings b 
                          JOIN rooms r ON b.room_id = r.room_id
                          WHERE b.user_id = '$uid' 
                          AND b.booking_status != 'cancelled'
                          AND (b.check_in_date < '$check_out' AND b.check_out_date > '$check_in')";
                
                $u_result = $conn->query($u_sql);

                if ($u_result->num_rows > 0) {
                    $user_conflict = true;
                    $existing_booking = $u_result->fetch_assoc();
                    $conflict_msg = "You already have a booking for " . addslashes($existing_booking['room_name']) . 
                                    " (" . $existing_booking['check_in_date'] . " to " . $existing_booking['check_out_date'] . ").";
                }
            }

            // 构建跳转 URL
            $next_url = "checkout_payment.php?room_id=$room_id&check_in=$check_in&check_out=$check_out&guests=$guests";

            if ($user_conflict) {
                // 【情况 1】有冲突：弹出 JS 确认框
                // 如果用户点确定，JS 控制跳转；如果点取消，这就什么都不做
                echo "<script>
                    var userChoice = confirm('⚠️ Double Booking Warning:\\n\\n$conflict_msg\\n\\nAre you sure you want to book another room for the same dates?');
                    
                    if (userChoice) {
                        window.location.href = '$next_url';
                    }
                </script>";
            } else {
                // 【情况 2】无冲突：直接跳转
                header("Location: $next_url");
                exit();
            }
        }
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
        .container-flex { display: flex; flex-wrap: wrap; max-width: 1000px; margin: 40px auto; gap: 20px; padding: 0 20px; }
        
        /* 左侧：日历区域 */
        .calendar-section { flex: 2; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-width: 300px; }
        #calendar { max-height: 600px; }

        /* 右侧：表单区域 */
        .form-section { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: fit-content; min-width: 250px; }
        
        .legend { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; font-size: 14px; }
        .dot { width: 12px; height: 12px; display: inline-block; border-radius: 2px; margin-right: 5px; }
        .dot-gray { background: #808080; } /* Booked */
        .dot-blue { background: #3788d8; } /* Cleaning */
        .dot-green { background: #28a745; } /* Selected */

        .btn-book { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 15px; transition: 0.3s; }
        .btn-book:hover:not(:disabled) { background: #218838; }
        .btn-book:disabled { background: #ccc; cursor: not-allowed; }
        
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; }

        input[readonly] { background-color: #e9ecef; cursor: not-allowed; border: 1px solid #ced4da; }
        input[type=number] { border: 1px solid #ced4da; }
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
            <span><span class="dot dot-blue"></span>Cleaning</span>
            <span><span class="dot dot-green"></span>Selected</span>
        </div>
        <div id='calendar'></div>
    </div>

    <div class="form-section">
        <?php if ($room_details): ?>
            <h3><?php echo htmlspecialchars($room_details['room_name']); ?></h3>
            <p style="color:#e74c3c; font-weight:bold;">RM <?php echo number_format($room_details['price_per_night'], 2); ?> / night</p>
            
            <?php echo $msg; ?>

            <form method="POST" id="bookingForm">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                
                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Check-in</label>
                    <input type="date" name="check_in" id="check_in" required readonly style="width:100%; padding:8px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Check-out</label>
                    <input type="date" name="check_out" id="check_out" required readonly style="width:100%; padding:8px;">
                </div>

                <div style="margin-bottom:15px;">
                    <label style="display:block; font-weight:bold; margin-bottom:5px;">Guests</label>
                    <input type="number" name="guests" value="1" min="1" max="<?php echo $max_guests; ?>" style="width:100%; padding:8px;">
                    <small class="text-muted">Max: <?php echo $max_guests; ?></small>
                </div>

                <p style="font-size:13px; color:#666;">
                    <i class="bi bi-info-circle"></i> Drag on the calendar to select dates.
                </p>

                <button type="submit" class="btn-book" id="bookBtn" disabled>Proceed to Checkout</button>
            </form>
        <?php else: ?>
            <p>Room not found.</p>
        <?php endif; ?>
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
            selectable: true, // 允许框选
            validRange: {
                start: '<?php echo date("Y-m-d"); ?>' // 不能选过去日期
            },
            // 读取 API
            events: 'api_get_bookings.php?room_id=<?php echo $room_id; ?>',
            
            // 选中日期触发
            select: function(info) {
                checkInInput.value = info.startStr;
                checkOutInput.value = info.endStr;
                
                // 激活按钮
                bookBtn.disabled = false;
                bookBtn.innerHTML = "Book " + info.startStr + " to " + info.endStr;
            },

            // 样式处理
            eventDidMount: function(info) {
                if (info.event.display === 'background') {
                    info.el.style.pointerEvents = 'none'; // 禁用点击已预订日期
                }
            }
        });

        calendar.render();
    });
</script>

</body>
</html>