<?php
session_start();

require_once 'includes/db_connection.php';

// 【修改 1】SQL 查询：获取最新 6 个房源 (ORDER BY room_id DESC)
$sql = "SELECT * FROM rooms ORDER BY room_id DESC LIMIT 6";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Homestay Reservation System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
    /* --- Hero Section 样式保持不变 --- */
    .hero-section {
        position: relative;
        height: 80vh; 
        min-height: 500px;
        color: #F5F5F5;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
    }
    
    .video-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: -2;
        object-fit: cover;
        opacity: 0;
        transition: opacity 1.5s ease-in-out;
    }

    .video-active { opacity: 1; }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4); 
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

    /* --- 【修改 2】新的滑动列表样式 --- */
    .featured-container {
        max-width: 1200px;
        margin: 50px auto;
        padding: 0 20px;
        position: relative; /* 为了定位左右箭头(如果需要) */
    }

    .section-title {
        text-align: center;
        color: #333333;
        margin-bottom: 40px;
    }
    
    .section-subtitle {
        text-align: center;
        color: #666;
        margin-top: -30px;
        margin-bottom: 40px;
        font-size: 0.9em;
    }

/* 滑动容器 */
    .room-grid {
        display: flex;
        overflow-x: auto;      /* 开启横向滚动 */
        gap: 25px;
        padding-bottom: 25px;  /* ★ 底部留出足够空间给滚动条 */
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        cursor: grab;
        
        /* 禁止选中文字 */
        user-select: none;
        -webkit-user-select: none;
        
        /* ★ Firefox 浏览器专用设置 ★ */
        scrollbar-width: thin;          /* 滚动条宽度 */
        scrollbar-color: #ccc #f1f1f1;  /* 滑块颜色 轨道颜色 */
    }
    
    .room-grid:active {
        cursor: grabbing;
    }

    /* =========================================
       ★ Chrome, Edge, Safari 滚动条美化 (核心) ★
       ========================================= */
    
    /* 1. 滚动条整体 (加高一点，方便鼠标点击) */
    .room-grid::-webkit-scrollbar {
        height: 14px; 
    }

    /* 2. 滚动条轨道 (背景) */
    .room-grid::-webkit-scrollbar-track {
        background: #f5f5f5; 
        border-radius: 10px;
        margin: 0 20px; /* 左右留白，不要顶到头 */
        border: 1px solid #e0e0e0; /* 给轨道加个边框，更清晰 */
    }

    /* 3. 滚动条滑块 (可拖动的部分) */
    .room-grid::-webkit-scrollbar-thumb {
        background-color: #bbb; /* 默认深灰色 */
        border-radius: 10px;
        border: 3px solid #f5f5f5; /* 制造“悬浮”效果 */
        min-width: 50px; /* 防止滑块太短 */
    }

    /* 4. 鼠标悬停在滑块上时 */
    .room-grid::-webkit-scrollbar-thumb:hover {
        background-color: #f0ad4e; /* 变成橙色，提示可拖动 */
        cursor: pointer;
    }

    /* 卡片样式调整：固定宽度，不再伸缩 */
    .room-card {
        background: white;
        border: 1px solid #ddd;
        min-width: 320px; /* ★ 固定最小宽度，确保有东西可滑 */
        max-width: 320px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 8px;
        overflow: hidden;
        flex: 0 0 auto; /* 禁止压缩 */
    }

    .room-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .room-img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        background-color: #eee;
    }

    .room-info {
        padding: 20px;
    }
    
    .room-info h3 {
        margin-top: 0;
        font-size: 1.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .price {
        color: #28a745; /* 改用绿色，符合整体风格 */
        font-weight: bold;
        font-size: 1.2em;
        margin: 10px 0;
    }
    
    .btn-details {
        display: block;
        width: 100%;
        text-align: center;
        background: #333;
        color: white;
        padding: 10px 0;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 15px;
        font-weight: bold;
    }
    .btn-details:hover { background: #000; }

    /* New Badge (可选：给最新房源加个标签) */
    .badge-new {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #dc3545;
        color: white;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: bold;
        border-radius: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    /* 响应式 */
    @media (max-width: 768px) {
        .room-card {
            min-width: 280px; /* 手机端稍微窄一点 */
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
        // --- 视频轮播逻辑 (保持不变) ---
        const videos = [
            document.getElementById('video1'),
            document.getElementById('video2'),
            document.getElementById('video3')
        ];
        let currentVideoIndex = 0;
        videos.forEach(v => v.load());
        videos[0].play();

        function playNextVideo() {
            let nextIndex = (currentVideoIndex + 1) % videos.length;
            videos[nextIndex].currentTime = 0;
            videos[nextIndex].play();
            videos[currentVideoIndex].classList.remove('video-active');
            videos[nextIndex].classList.add('video-active');
            currentVideoIndex = nextIndex;
        }
        videos.forEach((video) => {
            video.addEventListener('ended', playNextVideo);
        });

        // --- 【修改 3】鼠标拖动滑动功能 (Drag to Scroll) ---
        const slider = document.querySelector('.room-grid');
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('active');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', () => {
            isDown = false;
            slider.classList.remove('active');
        });
        slider.addEventListener('mouseup', () => {
            isDown = false;
            slider.classList.remove('active');
        });
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2; // *2 是滑动速度系数
            slider.scrollLeft = scrollLeft - walk;
        });
    });
</script>

<div class="featured-container">
    <h2 class="section-title">Recommended Rooms</h2>
    <p class="section-subtitle"><i class="bi bi-arrow-left-right"></i> Drag or scroll to see more latest homestays</p>
    
    <div class="room-grid">
        <?php
        if ($result->num_rows > 0) {
            $count = 0;
            while($row = $result->fetch_assoc()) {
                $count++;
                $img_path = !empty($row['room_image']) ? "uploads/" . $row['room_image'] : "images/placeholder.jpg";
                
                // 价格逻辑
                $min = $row['min_price'];
                $max = $row['max_price'];
                $price_display = "";
                
                if ($min == 0 && $max == 0) {
                    $price_display = "Check Details";
                } elseif ($min == $max) {
                    $price_display = "RM " . number_format($min, 2);
                } else {
                    $price_display = "RM " . number_format($min, 2) . " - " . number_format($max, 2);
                }
                
                // 给最新的 2 个加上 "NEW" 标签
                $newBadge = ($count <= 2) ? '<span class="badge-new">NEW</span>' : '';

                echo '
                <div class="room-card" style="position:relative;">
                    '.$newBadge.'
                    <img src="'.$img_path.'" alt="'.$row['room_name'].'" class="room-img">
                    <div class="room-info">
                        <h3>'.$row['room_name'].'</h3>
                        <p class="price">'.$price_display.' <span style="font-size: 0.7em; color: #999;">/ night</span></p>
                        <p style="color:#666; font-size:0.9em;">'.substr($row['description'], 0, 80).'...</p>
                        <a href="Module C/check_availability.php?room_id='.$row['room_id'].'" class="btn-details">View Details</a>
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