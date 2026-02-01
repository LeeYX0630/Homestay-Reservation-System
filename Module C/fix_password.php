<?php
// admin/fix_password.php
require_once '../includes/db_connection.php';

// 1. 设置我们要重置的账号和新密码
$target_email = 'admin@homestay.com';
$new_password = 'admin123';

// 2. 让 PHP 用当前的算法生成哈希 (这样绝对不会错)
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

// 3. 执行更新
$sql = "UPDATE admins SET password = ? WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $new_hash, $target_email);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo "<h2 style='color:green'>✅ 成功！Success!</h2>";
        echo "<p>Admin user (<strong>$target_email</strong>) password has been reset.</p>";
        echo "<p>New Password: <strong>$new_password</strong></p>";
        echo "<p>Generated Hash: $new_hash</p>";
        echo "<br><a href='admin_login.php'>Go to Login Page</a>";
    } else {
        echo "<h2 style='color:orange'>⚠️ 没有数据被修改 (No changes)</h2>";
        echo "<p>可能是邮箱不匹配，或者密码已经是这个了。</p>";
        echo "<p>请去数据库检查 admins 表里的 email 是否真的是: <strong>$target_email</strong> (注意有无空格)</p>";
    }
} else {
    echo "Error: " . $conn->error;
}
?>