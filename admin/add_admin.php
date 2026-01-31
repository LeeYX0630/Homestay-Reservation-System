<?php
session_start();
require_once '../includes/db_connection.php';

// --- 安全检查：只有 Super Admin 才能进这个页面 ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    // 如果不是 superadmin，直接踢回 dashboard，或者显示“权限不足”
    echo "<script>alert('Access Denied. Super Admin only.'); window.location.href='admin_dashboard.php';</script>";
    exit();
}

// ... 剩下的代码 ...
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. 基本验证
    if (empty($username) || empty($password) || empty($full_name)) {
        $msg = "<div class='alert error'>All fields are required.</div>";
    } elseif ($password !== $confirm_password) {
        $msg = "<div class='alert error'>Passwords do not match.</div>";
    } else {
        // 2. 检查用户名是否已存在
        $check_sql = "SELECT admin_id FROM admins WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = "<div class='alert error'>Username already taken.</div>";
        } else {
            // 3. 密码加密 (Hashing) - 非常重要！
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. 插入数据库
            $insert_sql = "INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sss", $username, $hashed_password, $full_name);

            if ($stmt->execute()) {
                $msg = "<div class='alert success'>New Admin added successfully!</div>";
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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #f4f4f4; }
        .form-container { max-width: 400px; margin: 50px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .btn-submit { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-submit:hover { background-color: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
        .error { background-color: #f8d7da; color: #721c24; }
        .success { background-color: #d4edda; color: #155724; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #555; }
    </style>
</head>
<body>

<div class="form-container">
    <h2 style="text-align:center; margin-top:0;">Add New Admin</h2>
    <?php echo $msg; ?>

    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" required placeholder="e.g. System Manager">
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required placeholder="Login username">
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn-submit">Create Admin</button>
    </form>

    <a href="admin_dashboard.php" class="back-link">&larr; Back to Dashboard</a>
</div>

</body>
</html>