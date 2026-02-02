<?php
// Module C/add_admin.php
session_start();
require_once '../includes/db_connection.php';

// --- 安全检查：只有 Super Admin 才能进这个页面 ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    echo "<script>alert('Access Denied. Super Admin only.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); 
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    $role = 'admin'; 

    // 1. 基本验证
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $msg = "<div class='alert error'>All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "<div class='alert error'>Invalid email format.</div>";
    } elseif (strlen($password) < 6) {
        // 【新增】后端强制检查长度
        $msg = "<div class='alert error'>Password must be at least 6 characters long.</div>";
    } elseif ($password !== $confirm_password) {
        $msg = "<div class='alert error'>Passwords do not match.</div>";
    } else {
        // 2. 检查用户名 OR 邮箱是否已存在
        $check_sql = "SELECT admin_id FROM admins WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = "<div class='alert error'>Username or Email already taken.</div>";
        } else {
            // 3. 密码加密
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. 插入数据库
            $insert_sql = "INSERT INTO admins (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $role);

            if ($stmt->execute()) {
                $msg = "<div class='alert success'>New Admin ($username) added successfully!</div>";
            } else {
                $msg = "<div class='alert error'>Error: " . $conn->error . "</div>";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Admin</title>
    <link rel="stylesheet" href="../Module A/style.css"> 
    <style>
        body { background-color: #f4f4f4; font-family: 'Segoe UI', sans-serif; }
        .form-container { max-width: 500px; margin: 50px auto; padding: 40px; background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .form-group input:focus { border-color: #007bff; outline: none; }
        
        .btn-submit { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; transition: 0.3s; }
        .btn-submit:hover { background-color: #0056b3; }
        
        .alert { padding: 12px; margin-bottom: 20px; border-radius: 6px; text-align: center; font-size: 14px; }
        .error { background-color: #ffebee; color: #c62828; border: 1px solid #ef9a9a; }
        .success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        
        .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #666; font-size: 14px; }
        .back-link:hover { color: #333; text-decoration: underline; }

        /* --- 【新增】密码强度样式 --- */
        .strength-container { margin-top: 8px; height: 5px; background-color: #eee; border-radius: 3px; overflow: hidden; display: flex;}
        .strength-bar { height: 100%; width: 0%; transition: width 0.3s ease, background-color 0.3s ease; }
        .strength-text { font-size: 12px; margin-top: 5px; font-weight: bold; display: block; text-align: right; }
        
        .strength-weak { background-color: #dc3545; }   /* 红 */
        .strength-medium { background-color: #ffc107; } /* 黄 */
        .strength-strong { background-color: #28a745; } /* 绿 */
    </style>
</head>
<body>

<div class="form-container">
    <h2 style="text-align:center; margin-top:0; margin-bottom: 30px; color:#333;">Add New Admin</h2>
    <?php echo $msg; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name (Position)</label>
            <input type="text" name="full_name" required placeholder="e.g. John Manager">
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required placeholder="For display purpose">
        </div>

        <div class="form-group">
            <label>Email Address (For Login)</label>
            <input type="email" name="email" required placeholder="admin@homestay.com">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" id="passwordInput" required placeholder="********">
            
            <div class="strength-container">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
            <span class="strength-text" id="strengthText"></span>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required placeholder="********">
        </div>

        <button type="submit" class="btn-submit">Create Admin Account</button>
    </form>

    <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
</div>

<script>
    const passwordInput = document.getElementById('passwordInput');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');

    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        let score = 0;

        // 1. 基础长度加分
        if (val.length >= 6) score += 1;
        if (val.length >= 10) score += 1;

        // 2. 复杂度加分
        if (/[A-Z]/.test(val)) score += 1; // 包含大写
        if (/[0-9]/.test(val)) score += 1; // 包含数字
        if (/[^A-Za-z0-9]/.test(val)) score += 1; // 包含特殊符号

        // 3. 更新 UI
        // 重置类名
        strengthBar.className = 'strength-bar';
        strengthText.textContent = '';

        if (val.length === 0) {
            strengthBar.style.width = '0%';
        } else if (val.length < 6) {
            // 太短
            strengthBar.style.width = '20%';
            strengthBar.classList.add('strength-weak');
            strengthText.textContent = 'Too Short';
            strengthText.style.color = '#dc3545';
        } else if (score < 3) {
            // 弱 (长度够但太简单)
            strengthBar.style.width = '40%';
            strengthBar.classList.add('strength-weak');
            strengthText.textContent = 'Weak (Password too short (less than 6 characters) or all numbers/all letters)';
            strengthText.style.color = '#dc3545';
        } else if (score === 3 || score === 4) {
            // 中等
            strengthBar.style.width = '70%';
            strengthBar.classList.add('strength-medium');
            strengthText.textContent = 'Medium (Moderate length, containing a mix of numbers and letters)';
            strengthText.style.color = '#ffc107'; // bootstrap warning color (darker yellow)
            strengthText.style.color = '#d39e00'; // visually better on white
        } else {
            // 强
            strengthBar.style.width = '100%';
            strengthBar.classList.add('strength-strong');
            strengthText.textContent = 'Strong (The length exceeds 10 characters and contains uppercase letters, symbols)';
            strengthText.style.color = '#28a745';
        }
    });
</script>

</body>
</html>