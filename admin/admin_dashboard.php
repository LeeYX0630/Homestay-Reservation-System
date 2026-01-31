<?php
// admin/admin_dashboard.php
session_start();
require_once '../includes/db_connection.php';

// --- 处理 Super Admin 验证逻辑 (当在弹窗里提交登录时) ---
$verify_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_super'])) {
    $sa_username = $_POST['sa_username'];
    $sa_password = $_POST['sa_password'];

    $sql = "SELECT * FROM admins WHERE username = ? AND role = 'superadmin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $sa_username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($sa_password, $row['password'])) {
            // 验证成功！将当前 Session 升级为 Super Admin
            $_SESSION['role'] = 'superadmin';
            $_SESSION['admin_id'] = $row['admin_id']; // 可选：变成那个超级管理员的ID
            $_SESSION['username'] = $row['username'];
            
            // 跳转到添加页面
            header("Location: add_admin.php");
            exit();
        } else {
            $verify_msg = "<script>alert('Wrong password for Super Admin!');</script>";
        }
    } else {
        $verify_msg = "<script>alert('Super Admin username not found!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* ... 你之前的样式 ... */
        
        /* 简单的弹窗样式 (Modal) */
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: white; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px; border-radius: 8px; text-align: center; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>
    <?php echo $verify_msg; ?>

    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</p>
            <hr>
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            
            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                <a href="add_admin.php">Add New Admin</a>
            <?php else: ?>
                <a href="#" onclick="openModal()">Add New Admin 🔒</a>
            <?php endif; ?>
            
            <a href="../logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h1>Dashboard</h1>
            
            <div style="background: white; padding: 20px;">
                <h2>Quick Actions</h2>
                <?php if ($_SESSION['role'] === 'superadmin'): ?>
                    <a href="add_admin.php" class="btn-primary">Add Admin</a>
                <?php else: ?>
                    <button onclick="openModal()" class="btn-primary" style="background:#555;">Add Admin (Locked)</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="superAdminModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Super Admin Login</h3>
            <p>Privilege escalation required.</p>
            <form method="POST">
                <input type="hidden" name="verify_super" value="1">
                <div style="margin-bottom:10px;">
                    <input type="text" name="sa_username" placeholder="Super Admin Username" required style="width:100%; padding:8px;">
                </div>
                <div style="margin-bottom:10px;">
                    <input type="password" name="sa_password" placeholder="Password" required style="width:100%; padding:8px;">
                </div>
                <button type="submit" class="btn-primary" style="width:100%;">Verify & Login</button>
            </form>
        </div>
    </div>

    <script>
        // JS 控制弹窗显示/隐藏
        var modal = document.getElementById("superAdminModal");
        function openModal() { modal.style.display = "block"; }
        function closeModal() { modal.style.display = "none"; }
        // 点击窗口外也可以关闭
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>

</body>
</html>