<?php
include '../includes/db_connection.php'; 
include '../includes/header.php';

$alertMessage = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // ★ 验证逻辑升级
    if (empty($name)) {
        $alertMessage = "alert('Please tell me your name');";
    } 
    elseif (empty($email)) {
        // 检查是否为空
        $alertMessage = "alert('I haven\'t receive any email.');";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // ★ 新增：检查 Email 格式 (比如有没有 @ 和 .com)
        $alertMessage = "alert('Please enter a valid email address (e.g. user@example.com).');";
    }
    elseif (empty($message)) {
        $alertMessage = "alert('Please enter your any message');";
    } 
    else {
        // 全部通过，写入数据库
        $clean_name = $conn->real_escape_string($name);
        $clean_email = $conn->real_escape_string($email);
        $clean_message = $conn->real_escape_string($message);

        $sql = "INSERT INTO contact_messages (sender_name, sender_email, message_content) VALUES ('$clean_name', '$clean_email', '$clean_message')";

        if ($conn->query($sql) === TRUE) {
            $alertMessage = "alert('Thank you! Your message has been sent successfully.');";
        } else {
            $alertMessage = "alert('Error: " . $conn->error . "');";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Homestay Reservation System</title>
<style>
        /* =========================================
           1. 全局样式 (UI Consistency)
           ========================================= */
        body {
            font-family: 'Segoe UI', Arial, sans-serif; 
            background-color: #FFFFFF;
            margin: 0;
            padding: 0;
            color: #333;
        }

        /* =========================================
           2. 页面内容布局
           ========================================= */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        h1, h2 {
            text-align: center;
            color: #333;
        }

        /* --- 项目介绍部分 --- */
        .intro-section {
            text-align: center;
            margin-bottom: 60px;
            line-height: 1.6;
        }

        /* --- ★ 新增：About Teh Tarik No Tarik 部分 (左右布局) --- */
        .brand-section {
            display: flex;          /* 使用 Flexbox 布局 */
            align-items: center;    /* 垂直居中 */
            gap: 40px;             /* 左右内容的间距 */
            margin-bottom: 60px;    /* 底部留白 */
        }
        
        .brand-text {
            flex: 1;                /* 占用 50% 空间 */
            text-align: justify;       /* 文字左对齐 */
            line-height: 1.8;       /* 行高稍微大一点，易读 */
        }

        .brand-text h3 {
            margin-top: 0;
            font-size: 24px;
            color: #333;
            border-left: 5px solid #333; /* 左边加个装饰线条 */
            padding-left: 15px;
        }

        .brand-image {
            flex: 1;                /* 占用 50% 空间 */
            text-align: center;
        }

        .brand-image img {
            width: 100%;            /* 图片宽度自适应容器 */
            max-width: 300px;       /* ★ 这里改成了 350px (之前是 500px) */
            height: auto;           /* 保持图片比例 */
            border-radius: 10px;    /* 图片圆角 */
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); /* 图片阴影 */
            object-fit: cover;
        }

        /* 手机端响应式：变成上下布局 */
        @media (max-width: 768px) {
            .brand-section {
                flex-direction: column; /* 垂直排列 */
            }
            .brand-text h3 {
                border-left: none;
                border-bottom: 3px solid #333;
                padding-bottom: 10px;
                padding-left: 0;
            }
        }

        /* --- 团队展示部分 --- */
        .team-section {
            display: flex;
            justify-content: center; /* 居中显示三位成员 */
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .team-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 300px;
            text-align: center;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease; /* (额外增加：动画效果) */
        }

        /* (额外增加：鼠标悬停放大效果) */
        .team-card:hover {
            transform: translateY(-5px);
        }

        .team-card img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #333;
        }

        .team-card h5 {
            margin: 10px 0 5px;
            color: #333;
            font-size: 18px;
            font-weight: bold;
        }

        .team-card p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        /* --- (额外增加) 联系我们 & 地图部分 --- */
        .contact-container {
            display: flex;
            justify-content: space-between;
            gap: 40px;
            background-color: #f4f4f4;
            padding: 40px;
            border-radius: 10px;
        }

        .contact-form {
            flex: 1;
        }

        .contact-form input, .contact-form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
        }

        .contact-form button {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .contact-form button:hover {
            background-color: #555;
        }

        .map-container {
            flex: 1;
            height: 300px;
            background-color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            overflow: hidden;
        }
    </style>
  
    <?php if (!empty($alertMessage)) { echo "<script>$alertMessage</script>"; } ?>
</head>
<body>

    <div class="container">
        <section class="intro-section">
            <h1>Welcome</h1>
            <p>
                Experience the best comfort and hospitality in Melaka. Our homestay reservation system 
                is designed to provide users with a seamless booking experience. 
                Whether you are looking for a cozy single room or a luxurious suite, 
                we have everything you need for a perfect getaway.
            </p>
        </section>

        <section class="brand-section">
            <div class="brand-text">
                <h3>About Teh Tarik No Tarik</h3>
                <p>
                    Teh Tarik No Tarik is a unique homestay reservation platform that combines the warmth of traditional hospitality 
                    with modern convenience. Our mission is to provide travelers with an authentic experience while ensuring 
                    ease of use through our user-friendly booking system. Whether you're here for leisure or business, 
                    we strive to make your stay memorable and comfortable.
                </p>
            </div>
            <div class="brand-image">
                <img src="../uploads/tehtariklogo.jpg" alt="Logo">
            </div>
        </section> 
            
        <h2>Teh Tarik No Tarik Team</h2>
        
        <section class="team-section">
            <div class="team-card">
                <img src="../Module B/picture/Lee Prof Pic.png" alt="Mr. Lee">
                <h5>Mr. Lee Yun Xiang</h5>
                <p>Founder & Chief Executive Officer</p>
                <p>ID: 242DT2420T</p>
            </div>

            <div class="team-card">
                <img src="../Module B/picture/Chong Prof Pic.png" alt="Mr. Chong">
                <h5>Mr. Chong Yang Yang</h5>
                <p>Head of Business Development</p>
                <p>ID: 242DT2425H</p>
            </div>

            <div class="team-card">
                <img src="../Module B/picture/Tung Prof Pic.png" alt="Mr. Tung">
                <h5>Mr. Tung Khai Jun</h5>
                <p>Head of Finance</p>
                <p>ID: 242DT242DB</p>
            </div>
        </section>

        <h2>Contact & Location</h2>
        <section class="contact-container">
            <div class="contact-form">
                <h3>Send us a message</h3>
                
                <form action="" method="post" novalidate>
                    <input type="text" name="name" placeholder="Your Name">
                    <input type="email" name="email" placeholder="Your Email">
                    <textarea name="message" rows="5" placeholder="Message"></textarea>
                    <button type="submit" name="submit_contact">Send Message</button>
                </form>
            </div>

            <div class="map-container">
                <iframe 
                    width="100%" 
                    height="100%" 
                    frameborder="0" 
                    scrolling="no" 
                    marginheight="0" 
                    marginwidth="0" 
                    src="https://maps.google.com/maps?q=朕的天下=&z=13&ie=UTF8&iwloc=&output=embed">
                </iframe>
            </div>
        </section>
    </div>

<?php 
include_once '../includes/footer.php'; 
?>

</body>
</html>