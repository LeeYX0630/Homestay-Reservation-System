<?php
session_start();

require_once 'includes/db_connection.php';

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
    .hero-section {
        position: relative;
        height: 80vh; /* 调整高度 */
        min-height: 500px;
        color: #F5F5F5;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    
    /* 影片背景样式 */
    .video-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -2;
        object-fit: cover;
        opacity: 0;
        transition: opacity 1.5s ease-in-out; /* 切换时的淡入淡出效果 */
    }

    .video-active {
        opacity: 1;
    }

    /* 遮罩层：让文字更清晰 */
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4); /* 40% 黑色的遮罩 */
        z-index: -1;
    }
    
    .hero-section h1 {
        position: relative;
        color: #F5F5F5;
        font-size: 3.5em;
        margin-bottom: 20px;
        text-shadow: 2px 2px 10px rgba(0,0,0,0.5);
    }

    .hero-section p {
        position: relative;
        font-size: 1.5em;
        margin-bottom: 40px;
        text-shadow: 1px 1px 5px rgba(0,0,0,0.5);
    }

    .cta-button {
        position: relative;
        background-color: #f0ad4e;
        color: white;
        padding: 15px 40px;
        text-decoration: none;
        font-size: 1.2em;
        border-radius: 5px;
        font-weight: bold;
        transition: 0.3s;
    }

    .cta-button:hover {
        background-color: #ec971f;
        transform: scale(1.05);
    }

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
            width: 30%;
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
            background-color: #eee;
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

<?php
$page_title = "Home";
$is_home_root = true; 

include 'includes/header.php';
?>

<div class="hero-section">
    <div class="hero-overlay"></div>

    <video class="video-background video-active" id="video1" muted playsinline>
        <source src="images/Homestay video1.mp4" type="video/mp4">
    </video>
    <video class="video-background" id="video2" muted playsinline>
        <source src="images/Homestay video2.mp4" type="video/mp4">
    </video>
    <video class="video-background" id="video3" muted playsinline>
        <source src="images/Homestay video3.mp4" type="video/mp4">
    </video>

    <h1>Welcome to Your Perfect Getaway</h1>
    <p>Experience comfort and luxury at affordable prices.</p>
    <a href="Module B/room_catalogue.php" class="cta-button">Book Now</a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const videos = [
            document.getElementById('video1'),
            document.getElementById('video2'),
            document.getElementById('video3')
        ];
        let currentVideoIndex = 0;

        // 预加载所有视频，防止切换时黑屏
        videos.forEach(v => v.load());

        // 初始化播放第一个影片
        videos[0].play();

        function playNextVideo() {
            let nextIndex = (currentVideoIndex + 1) % videos.length;
            
            // 准备播放下一个影片
            videos[nextIndex].currentTime = 0;
            videos[nextIndex].play();
            
            // 切换显示状态（淡入淡出）
            videos[currentVideoIndex].classList.remove('video-active');
            videos[nextIndex].classList.add('video-active');
            
            // 更新索引
            currentVideoIndex = nextIndex;
        }

        // 为每个影片添加结束监听事件
        videos.forEach((video) => {
            video.addEventListener('ended', playNextVideo);
        });
    });
</script>

<div class="featured-container">
        <h2 class="section-title">Recommended Rooms</h2>
        
        <div class="room-grid">
            <?php
            // 【修改】SQL 查询：确保读取 min_price 和 max_price
            $sql = "SELECT * FROM rooms LIMIT 3";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $img_path = !empty($row['room_image']) ? "uploads/" . $row['room_image'] : "images/placeholder.jpg";
                    
                    // --- 【核心逻辑】处理价格显示 ---
                    $min = $row['min_price'];
                    $max = $row['max_price'];
                    $price_display = "";
                    
                    if ($min == 0 && $max == 0) {
                        $price_display = "Check Details";
                    } elseif ($min == $max) {
                        $price_display = "RM " . number_format($min, 2);
                    } else {
                        // 显示范围格式
                        $price_display = "RM " . number_format($min, 2) . " - " . number_format($max, 2);
                    }
                    // ---------------------------------

                    echo '
                    <div class="room-card">
                        <img src="'.$img_path.'" alt="'.$row['room_name'].'" class="room-img">
                        <div class="room-info">
                            <h3>'.$row['room_name'].'</h3>
                            <p class="price">'.$price_display.' <span style="font-size: 0.7em; color: #999;">/ night</span></p>
                            <p>'.substr($row['description'], 0, 100).'...</p>
                            <a href="Module C/check_availability.php?room_id='.$row['room_id'].'">View Details &rarr;</a>
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

<?php 
include_once 'includes/footer.php'; 
?>

</body>
</html>