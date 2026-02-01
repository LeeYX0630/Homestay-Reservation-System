<?php
// module_c/api_get_bookings.php
header('Content-Type: application/json');
require_once '../includes/db_connection.php';

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$events = [];

if ($room_id) {
    // 1. 查询该房间所有未取消的订单
    $sql = "SELECT check_in_date, check_out_date FROM bookings 
            WHERE room_id = $room_id AND booking_status != 'cancelled'";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        // --- A. 灰色：已预订 (Booked) ---
        // FullCalendar 的 end 日期是“不包含”的，所以如果是 5号退房，日历上 5号那天其实显示为空
        // 为了视觉上占满，通常不需要特殊处理，但为了配合打扫日，我们如下设置：
        $events[] = [
            'title' => 'Booked',
            'start' => $row['check_in_date'],
            'end'   => $row['check_out_date'], // 视觉上会覆盖到退房前一天
            'color' => '#808080', // 灰色
            'display' => 'background' //以此作为背景色显示，不挡住文字
        ];

        // --- B. 蓝色：打扫日 (Cleaning) ---
        // 规则：退房当天定义为打扫日，不可预订
        $events[] = [
            'title' => 'Cleaning',
            'start' => $row['check_out_date'],
            'end'   => $row['check_out_date'], // 看起来只占那一天（FullCalendar需要特殊处理全天事件）
            'allDay' => true,
            'color' => '#3788d8', // 蓝色
            'display' => 'background',
            'overlap' => false // 不允许重叠
        ];
    }
}

echo json_encode($events);
?>