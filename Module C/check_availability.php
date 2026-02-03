<?php
// module_c/check_availability.php
session_start();
require_once '../includes/db_connection.php';

// --- 1. 获取参数 (room_id 或 category_id) ---
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// 【关键修复】如果只有 category_id，通过它找到对应的 room_id
if ($room_id == 0 && $category_id > 0) {
    $cat_sql = "SELECT room_id FROM categories WHERE category_id = '$category_id'";
    $cat_res = $conn->query($cat_sql);
    if ($cat_res->num_rows > 0) {
        $cat_row = $cat_res->fetch_assoc();
        $room_id = $cat_row['room_id'];
    }
}

// --- Admin 拦截 ---
if (isset($_SESSION['admin_id'])) {
    echo "<script>alert('Admins cannot book rooms.'); window.location.href='../Module C/admin_dashboard.php';</script>";
    exit();
}

// --- 登录检查 ---
if (!isset($_SESSION['user_id'])) {
    // 构造正确的回跳 URL (保留 category_id)
    $params = ($category_id > 0) ? "?category_id=$category_id" : "?room_id=$room_id";
    $return_url = urlencode("../Module C/check_availability.php" . $params);
    echo "<script>alert('Please login first!'); window.location.href='../Module A/login.php?redirect=$return_url';</script>";
    exit();
}

$msg = "";
$room_details = null;
$max_guests = 4;
$display_price = 0;
$display_name = "";

// --- 2. 获取数据与价格逻辑 ---
if ($room_id) {
    $sql = "SELECT * FROM rooms WHERE room_id = $room_id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $room_details = $result->fetch_assoc();
        
        // 默认显示房间的基础信息
        $display_name = $room_details['room_name'];
        $display_price = $room_details['price_per_night']; // 默认 fallback
        $max_guests = isset($room_details['capacity']) ? $room_details['capacity'] : 4;

        // ★ 如果选了特定房型 (Category)，覆盖显示特定价格和人数 ★
        if ($category_id > 0) {
            $c_sql = "SELECT * FROM categories WHERE category_id = '$category_id'";
            $c_res = $conn->query($c_sql);
            if ($c_res->num_rows > 0) {
                $cat_data = $c_res->fetch_assoc();
                $display_name = $room_details['room_name'] . " (" . $cat_data['category_name'] . ")";
                $display_price = $cat_data['price_per_night']; // 使用分类价格
                $max_guests = $cat_data['max_pax'];
            }
        }
    }
}

// --- 3. 处理提交 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_id = intval($_POST['room_id']); 
    // 获取隐藏的 category_id
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'];
    
    if (empty($check_in) || empty($check_out)) {
         $msg = "<div class='alert error'>Please select dates from the calendar.</div>";
    } else {
        // 检查房间占用
        $check_sql = "SELECT * FROM bookings 
                      WHERE room_id = '$room_id' 
                      AND booking_status != 'cancelled' 
                      AND ((check_in_date < '$check_out' AND check_out_date > '$check_in'))";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $msg = "<div class='alert error'>Date unavailable! Please check the calendar.</div>";
        } else {
            // 双重预订检查
            $user_conflict = false;
            $conflict_msg = "";
            if (isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                $u_sql = "SELECT r.room_name, b.check_in_date, b.check_out_date FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.user_id = '$uid' AND b.booking_status != 'cancelled' AND (b.check_in_date < '$check_out' AND b.check_out_date > '$check_in')";
                $u_result = $conn->query($u_sql);
                if ($u_result->num_rows > 0) {
                    $user_conflict = true;
                    $eb = $u_result->fetch_assoc();
                    $conflict_msg = "You already have a booking for " . $eb['room_name'] . ".";
                }
            }

            // 跳转时带上 category_id，以便下一步计算正确价格
            $next_url = "checkout_payment.php?room_id=$room_id&category_id=$category_id&check_in=$check_in&check_out=$check_out&guests=$guests";

            if ($user_conflict) {
                echo "<script>if(confirm('⚠️ $conflict_msg Continue?')){window.location.href = '$next_url';}</script>";
            } else {
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
        .calendar-section { flex: 2; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); min-width: 300px; }
        .form-section { flex: 1; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); height: fit-content; min-width: 250px; }
        .legend { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 15px; font-size: 14px; }
        .dot { width: 12px; height: 12px; display: inline-block; border-radius: 2px; margin-right: 5px; }
        .dot-gray { background: #808080; } .dot-blue { background: #3788d8; } .dot-green { background: #28a745; }
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
            <h3><?php echo htmlspecialchars($display_name); ?></h3>
            <p style="color:#e74c3c; font-weight:bold;">RM <?php echo number_format($display_price, 2); ?> / night</p>
            
            <?php echo $msg; ?>

            <form method="POST" id="bookingForm">
                <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                <input type="hidden" name="category_id" value="<?php echo $category_id; ?>">
                
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
            selectable: true, 
            validRange: { start: '<?php echo date("Y-m-d"); ?>' },
            events: 'api_get_bookings.php?room_id=<?php echo $room_id; ?>',
            select: function(info) {
                checkInInput.value = info.startStr;
                checkOutInput.value = info.endStr;
                bookBtn.disabled = false;
                bookBtn.innerHTML = "Book " + info.startStr + " to " + info.endStr;
            },
            eventDidMount: function(info) {
                if (info.event.display === 'background') {
                    info.el.style.pointerEvents = 'none'; 
                }
            }
        });
        calendar.render();
    });
</script>

</body>
</html>