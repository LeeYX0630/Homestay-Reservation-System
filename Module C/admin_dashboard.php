<?php
// 开启 Session，用于判断用户是否登录 (Module A 负责写入 Session，这里负责读取)
session_start();

// 1. 引入数据库连接 (根据文档开发规范 [cite: 346])
require_once 'db_connection.php';

// 2. 获取 "推荐房源" (Must Have: 推荐展示 )
// 这里我们简单地取数据库里的前 3 个房间作为推荐
$sql = "SELECT * FROM rooms LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Homestay Reservation System</title>
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Hero Banner 区域 */
        .hero-section {
            background-color: #333333; /* 配合导航栏的主色调 */
            color: #F5F5F5;
            padding: 100px 20px;
            text-align: center;
        }
        
        .hero-section h1 {
            color: #F5F5F5; /* 强制覆盖全局 h1 颜色 */
            font-size: 3em;
            margin-bottom: 20px;
        }

        .cta-button {
            background-color: #f0ad4e; /* 醒目的按钮颜色，可根据需要调整 */
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            font-size: 1.2em;
            border-radius: 5px;
            font-weight: bold;
        }

        .cta-button:hover {
            background-color: #ec971f;
        }

        /* 推荐房源区域 */
        .featured-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            color: #333333;
            margin-bottom: 40px;
        }

        .room-grid {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .room-card {
            background: white;
            border: 1px solid #ddd;
            width: 30%; /* 一行显示三个 */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .room-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background-color: #eee; /* 图片加载前的占位色 */
        }

        .room-info {
            padding: 20px;
        }

        .price {
            color: #e74c3c;
            font-weight: bold;
            font-size: 1.2em;
        }

        /* 响应式调整 */
        @media (max-width: 768px) {
            .room-card {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <nav>
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <div style="color: #F5F5F5; font-size: 24px; font-weight: bold;">HomestaySystem</div>
            <div>
                <a href="index.php">Home</a>
                <a href="room_catalogue.php">Rooms</a>
                <a href="about_us.php">About Us</a>
                
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="user_dashboard.php" style="color: #4CAF50;">Hi, <?php echo htmlspecialchars($_SESSION['full_name']); ?></a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="hero-section">
        <h1>Welcome to Your Perfect Getaway</h1>
        <p style="font-size: 1.2em; margin-bottom: 40px;">Experience comfort and luxury at affordable prices.</p>
        <a href="room_catalogue.php" class="cta-button">Book Now</a>
    </div>

    <div class="featured-container">
        <h2 class="section-title">Recommended Rooms</h2>
        
        <div class="room-grid">
            <?php
            if ($result->num_rows > 0) {
                // 循环输出数据库中的房间
                while($row = $result->fetch_assoc()) {
                    // 处理图片路径，如果没有图片则使用默认占位符
                    $img_path = !empty($row['room_image']) ? "uploads/" . $row['room_image'] : "images/placeholder.jpg";
                    
                    echo '
                    <div class="room-card">
                        <img src="'.$img_path.'" alt="'.$row['room_name'].'" class="room-img">
                        <div class="room-info">
                            <h3>'.$row['room_name'].'</h3>
                            <p class="price">RM '.$row['price_per_night'].' / night</p>
                            <p>'.substr($row['description'], 0, 100).'...</p>
                            <a href="room_details.php?id='.$row['room_id'].'" style="color: #333333; font-weight: bold;">View Details &rarr;</a>
                        </div>
                    </div>
                    ';
                }
            } else {
                echo '<p style="text-align:center; width:100%;">No featured rooms available at the moment.</p>';
            }
            ?>
        </div>
    </div>

    <footer style="background: #333; color: #fff; text-align: center; padding: 20px; margin-top: 50px;">
        <p>&copy; 2026 Homestay Reservation System. All Rights Reserved.</p>
    </footer>

</body>
</html>