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

<?php
// Module B/room_catalogue.php
session_start();
require_once '../includes/db_connection.php';

// 初始化 SQL
$sql = "SELECT * FROM rooms";
$where_clauses = [];

// 1. 处理搜索功能
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    // 使用 LIKE 进行模糊匹配
    $where_clauses[] = "(room_name LIKE '%$search%' OR description LIKE '%$search%')";
}

// (可选) 如果你还有 category 筛选
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $cat_id = intval($_GET['category_id']);
    $where_clauses[] = "category_id = $cat_id";
}

// 拼接 SQL
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$result = $conn->query($sql);

$page_title = "Rooms & Suites";
include '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4">
        <?php 
        if(isset($_GET['search'])) {
            echo 'Search Results for: "' . htmlspecialchars($_GET['search']) . '"';
        } else {
            echo 'Our Rooms';
        }
        ?>
    </h2>

    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $row['room_name']; ?></h5>
                            </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                <p class="mt-3 text-muted">No rooms found matching your search.</p>
                <a href="room_catalogue.php" class="btn btn-outline-dark mt-2">View All Rooms</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
?>