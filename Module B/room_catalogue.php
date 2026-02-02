<?php
/**
 * =========================================================
 * 1. 路径修复：加上 ../ 返回上一级
 * =========================================================
 */
include '../includes/db_connection.php'; 
include '../includes/header.php';

// 初始化搜索
$search_query = "";
$where_clause = "";

// 搜索逻辑
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    // 既搜名字，也搜描述
    $where_clause = "WHERE rooms.room_name LIKE '%$search_query%' OR rooms.description LIKE '%$search_query%'";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Catalogue</title>
    <style>
        /* =========================================
           CSS 样式 (Tung's Standard: No Shorthand)
           ========================================= */

        /* 全局 */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f6f9;
            margin-top: 0px;
            margin-bottom: 0px;
            margin-left: 0px;
            margin-right: 0px;
            padding-top: 0px;
            padding-bottom: 0px;
            padding-left: 0px;
            padding-right: 0px;
            color: #333333;
        }

        .catalogue-container {
            max-width: 1200px;
            margin-top: 40px;
            margin-bottom: 40px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        /* 标题 */
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .page-header h2 { font-size: 32px; color: #333; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 16px; }

        /* ★ 网格布局：横向 3 列 ★ */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        /* 卡片样式 */
        .room-card {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            border-width: 1px;
            border-style: solid;
            border-color: #e0e0e0;
        }
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0px 8px 25px rgba(0,0,0,0.1);
        }

        /* 图片区域 */
        .card-image {
            width: 100%;
            height: 220px;
            background-color: #eeeeee;
            overflow: hidden;
        }
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* 内容区域 */
        .card-content {
            padding-top: 20px;
            padding-bottom: 20px;
            padding-left: 20px;
            padding-right: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .category-badge {
            background-color: #f8f9fa;
            color: #666;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            padding-top: 4px;
            padding-bottom: 4px;
            padding-left: 8px;
            padding-right: 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 10px;
            width: fit-content;
            border: 1px solid #ddd;
        }

        .room-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-top: 0px;
            margin-bottom: 10px;
        }

        .room-price {
            font-size: 18px;
            color: #28a745;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .room-price span { font-size: 14px; color: #999; font-weight: normal; }

        .room-details {
            list-style: none;
            padding-left: 0px;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
        .room-details li { margin-bottom: 5px; }

        .card-footer { margin-top: auto; }
        .btn-view {
            display: block;
            width: 100%;
            background-color: #333;
            color: #fff;
            text-align: center;
            padding-top: 12px;
            padding-bottom: 12px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn-view:hover { background-color: #000; }
        
        .no-results { grid-column: 1 / -1; text-align: center; padding: 50px; color: #999; }
    </style>
</head>
<body>

<div class="catalogue-container">
    <div class="page-header">
        <h2>Our Room Catalogue</h2>
        <p>Find your perfect homestay from our selection.</p>
    </div>

    <div class="room-grid">
        <?php
        /**
         * =========================================================
         * ★ 核心修改：完全按照你的 Database 截图来写 SQL ★
         * 1. 主要数据来自 rooms 表 (rooms.*)
         * 2. 只 JOIN categories 表拿一个 category_name (名字)
         * =========================================================
         */
        $sql = "SELECT rooms.*, categories.category_name 
                FROM rooms 
                LEFT JOIN categories ON rooms.category_id = categories.category_id 
                $where_clause 
                ORDER BY rooms.room_name ASC";
        
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // ★ 1. 图片路径：直接读取 rooms 表的 room_image
                // 注意：如果你的图片在 uploads 文件夹，这里需要 ../uploads/
                $img_path = !empty($row['room_image']) ? "../uploads/" . $row['room_image'] : "../assets/images/placeholder.jpg";
                
                // ★ 2. 设施：直接读取 rooms 表的 facilities
                $facilities = $row['facilities'] ? $row['facilities'] : "Standard Amenities";
                if (strlen($facilities) > 50) { $facilities = substr($facilities, 0, 50) . "..."; }
                
                // ★ 3. 价格：直接读取 rooms 表的 price_per_night
                $price = $row['price_per_night'];

                // ★ 4. 分类名：如果没分类显示 "Homestay"
                $catName = $row['category_name'] ? $row['category_name'] : "Homestay";
                ?>
                
                <div class="room-card">
                    <div class="card-image">
                        <img src="<?php echo $img_path; ?>" alt="<?php echo $row['room_name']; ?>">
                    </div>
                    
                    <div class="card-content">
                        <div class="category-badge"><?php echo $catName; ?></div>

                        <h3 class="room-title"><?php echo $row['room_name']; ?></h3>

                        <div class="room-price">
                            RM <?php echo number_format($price, 2); ?>
                            <span>/ night</span>
                        </div>

                        <ul class="room-details">
                            <li><strong>Description:</strong> <?php echo substr($row['description'], 0, 60) . '...'; ?></li>
                            <li><strong>Facilities:</strong> <?php echo $facilities; ?></li>
                        </ul>

                        <div class="card-footer">
                            <a href="booking_details.php?room_id=<?php echo $row['room_id']; ?>" class="btn-view">
                                VIEW DETAILS
                            </a>
                        </div>
                    </div>
                </div>

                <?php
            }
        } else {
            echo "<div class='no-results'>No rooms found in database.</div>";
        }
        ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>