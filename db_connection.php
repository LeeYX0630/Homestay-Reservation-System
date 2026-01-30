<?php
// db_connection.php
// 所有成员必须 include 这个文件，不要在各自页面单独写连接代码 

$servername = "localhost";
$username = "root";
$password = ""; // XAMPP 默认密码为空
$dbname = "homestay_db"; // 请确保你们的数据库名称统一为这个

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>