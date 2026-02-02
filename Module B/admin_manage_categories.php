<?php
/**
 * =========================================================
 * 1. 包含数据库连接和头部
 * =========================================================
 */
include '../includes/db_connection.php';
include '../includes/header.php';

// 初始化变量
$alertMessage = "";
$search_keyword = "";
$where_sql = "";

// 处理搜索
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_keyword = $conn->real_escape_string($_GET['search']);
    $where_sql = "WHERE category_name LIKE '%$search_keyword%' OR description LIKE '%$search_keyword%'";
}

/**
 * =========================================================
 * 2. 图片上传函数 (Function to handle Image Upload)
 * =========================================================
 */
function uploadImage($file) {
    // 检查是否有文件上传
    if(isset($file) && $file['error'] == 0) {
        $target_dir = "../uploads/";
        // 如果文件夹不存在，创建它
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . basename($file["name"]); // 加时间戳防止重名
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // 允许的格式
        $allowed = array("jpg", "jpeg", "png", "gif");
        if(!in_array($imageFileType, $allowed)) {
            return ["status" => false, "msg" => "Only JPG, JPEG, PNG & GIF allowed."];
        }

        // 移动文件
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return ["status" => true, "filename" => $file_name];
        }
    }
    return ["status" => false, "msg" => "No file uploaded or error occurred."];
}

/**
 * =========================================================
 * 3. 处理表单提交 (CREATE & UPDATE)
 * =========================================================
 */
if (isset($_POST['save_category'])) {
    $name = $conn->real_escape_string($_POST['category_name']);
    $price = $_POST['price_per_night'];
    $max = $_POST['max_pax'];
    $desc = $conn->real_escape_string($_POST['description']);
    
    // 检查是新增还是修改
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        // --- UPDATE (修改模式) ---
        $id = $_POST['category_id'];
        $sql = "UPDATE categories SET category_name='$name', price_per_night='$price', max_pax='$max', description='$desc' WHERE category_id='$id'";
        
        // 如果有上传新图片，则更新图片字段
        if (!empty($_FILES['category_image']['name'])) {
            $upload = uploadImage($_FILES['category_image']);
            if ($upload['status']) {
                $img = $upload['filename'];
                $conn->query("UPDATE categories SET category_image='$img' WHERE category_id='$id'");
            } else {
                $alertMessage = "alert('Image Error: " . $upload['msg'] . "');";
            }
        }
        
        if ($conn->query($sql) === TRUE) {
            $alertMessage = "alert('Category updated successfully.'); window.location.href='admin_manage_categories.php';";
        }
        
    } else {
        // --- CREATE (新增模式) ---
        $img_name = "";
        $upload = uploadImage($_FILES['category_image']);
        
        if ($upload['status']) {
            $img_name = $upload['filename'];
            $sql = "INSERT INTO categories (category_name, price_per_night, max_pax, description, category_image) 
                    VALUES ('$name', '$price', '$max', '$desc', '$img_name')";
            
            if ($conn->query($sql) === TRUE) {
                $alertMessage = "alert('New category created successfully.'); window.location.href='admin_manage_categories.php';";
            } else {
                $alertMessage = "alert('Database Error: " . $conn->error . "');";
            }
        } else {
            $alertMessage = "alert('Image is required: " . $upload['msg'] . "');";
        }
    }
}

// 处理删除
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM categories WHERE category_id='$id'");
    echo "<script>alert('Category deleted.'); window.location.href='admin_manage_categories.php';</script>";
}

// 处理编辑数据的读取
$edit_row = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $res = $conn->query("SELECT * FROM categories WHERE category_id='$id'");
    $edit_row = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Categories | Admin</title>
    <style>
        /* =========================================
           CSS 样式 (中性色 + 垂直布局 + 无简写)
           ========================================= */
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f4; /* 浅灰背景 */
            margin-top: 0px;
            margin-bottom: 0px;
            margin-left: 0px;
            margin-right: 0px;
            padding-top: 0px;
            padding-bottom: 0px;
            padding-left: 0px;
            padding-right: 0px;
            color: #333333;
        }

        .container {
            max-width: 1000px;
            margin-top: 40px;
            margin-bottom: 40px;
            margin-left: auto;
            margin-right: auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        h2 {
            text-align: center;
            color: #333333;
            margin-bottom: 30px;
            font-weight: bold;
        }

        /* --- 表单区域 (Vertical Layout) --- */
        .form-container {
            background-color: #ffffff;
            padding-top: 30px;
            padding-bottom: 30px;
            padding-left: 30px;
            padding-right: 30px;
            border-width: 1px;
            border-style: solid;
            border-color: #dddddd;
            border-radius: 4px;
            margin-bottom: 40px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
        }

        .form-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom-width: 1px;
            border-bottom-style: solid;
            border-bottom-color: #eeeeee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555555;
            font-size: 14px;
        }

        .form-group input[type="text"], 
        .form-group input[type="number"], 
        .form-group input[type="file"],
        .form-group textarea {
            width: 100%;
            padding-top: 10px;
            padding-bottom: 10px;
            padding-left: 10px;
            padding-right: 10px;
            border-width: 1px;
            border-style: solid;
            border-color: #cccccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: inherit;
        }

        .btn-submit {
            background-color: #333333; /* 黑色按钮 */
            color: #ffffff;
            padding-top: 12px;
            padding-bottom: 12px;
            padding-left: 20px;
            padding-right: 20px;
            border-width: 0px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
            font-size: 16px;
        }

        .btn-submit:hover {
            background-color: #555555;
        }
        
        .btn-cancel {
            background-color: #999999;
            color: #ffffff;
            padding-top: 12px;
            padding-bottom: 12px;
            border-width: 0px;
            border-radius: 4px;
            text-align: center;
            display: block;
            text-decoration: none;
            width: 100%;
            margin-top: 10px;
            box-sizing: border-box;
        }

        /* --- 列表区域 --- */
        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom-width: 2px;
            border-bottom-style: solid;
            border-bottom-color: #333333;
            padding-bottom: 10px;
        }

        .search-box input {
            padding-top: 6px;
            padding-bottom: 6px;
            padding-left: 10px;
            padding-right: 10px;
            border-width: 1px;
            border-style: solid;
            border-color: #cccccc;
        }

        .search-box button {
            background-color: #333333;
            color: #ffffff;
            border-width: 0px;
            padding-top: 7px;
            padding-bottom: 7px;
            padding-left: 15px;
            padding-right: 15px;
            cursor: pointer;
        }

        /* 表格样式 */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
        }

        table th {
            background-color: #444444; /* 深灰表头 */
            color: #ffffff;
            padding-top: 12px;
            padding-bottom: 12px;
            padding-left: 12px;
            padding-right: 12px;
            text-align: left;
            border-width: 1px;
            border-style: solid;
            border-color: #555555;
        }

        table td {
            padding-top: 12px;
            padding-bottom: 12px;
            padding-left: 12px;
            padding-right: 12px;
            border-width: 1px;
            border-style: solid;
            border-color: #dddddd;
            color: #333333;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table img {
            width: 80px;
            height: 50px;
            object-fit: cover;
            border-radius: 3px;
        }

        .action-link {
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            margin-right: 8px;
            padding-top: 5px;
            padding-bottom: 5px;
            padding-left: 10px;
            padding-right: 10px;
            border-radius: 3px;
            display: inline-block;
        }

        .link-edit {
            background-color: #666666;
            color: #ffffff;
        }

        .link-delete {
            background-color: #cc0000; /* 深红 */
            color: #ffffff;
        }
    </style>
    <?php if(!empty($alertMessage)) { echo "<script>$alertMessage</script>"; } ?>
</head>
<body>

<div class="container">
    <h2>Manage Room Categories</h2>

    <div class="form-container">
        <div class="form-title">
            <?php echo isset($edit_row) ? 'Edit Category' : 'Add New Category'; ?>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
            <?php if(isset($edit_row)): ?>
                <input type="hidden" name="category_id" value="<?php echo $edit_row['category_id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Category Name (e.g. Deluxe Suite)</label>
                <input type="text" name="category_name" required value="<?php echo isset($edit_row) ? $edit_row['category_name'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Price Per Night (RM)</label>
                <input type="number" step="0.01" name="price_per_night" required value="<?php echo isset($edit_row) ? $edit_row['price_per_night'] : ''; ?>">
            </div>

            <div class="form-group">
                <label>Max Pax</label>
                <input type="number" name="max_pax" required value="<?php echo isset($edit_row) ? $edit_row['max_pax'] : '2'; ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo isset($edit_row) ? $edit_row['description'] : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label>Category Image</label>
                <input type="file" name="category_image" <?php echo isset($edit_row) ? '' : 'required'; ?>>
                <?php if(isset($edit_row) && !empty($edit_row['category_image'])): ?>
                    <p style="font-size:12px; color:#666;">Current: <?php echo $edit_row['category_image']; ?></p>
                <?php endif; ?>
            </div>

            <?php if(isset($edit_row)): ?>
                <button type="submit" name="save_category" class="btn-submit">UPDATE CATEGORY</button>
                <a href="admin_manage_categories.php" class="btn-cancel">CANCEL</a>
            <?php else: ?>
                <button type="submit" name="save_category" class="btn-submit">ADD CATEGORY</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="list-header">
        <h3>Category List</h3>
        <div class="search-box">
            <form action="" method="get">
                <input type="text" name="search" placeholder="Search category..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                <button type="submit">SEARCH</button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="15%">Image</th>
                <th width="20%">Name</th>
                <th width="15%">Price (RM)</th>
                <th width="10%">Pax</th>
                <th width="25%">Description</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM categories $where_sql ORDER BY category_id DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $img = !empty($row['category_image']) ? "../uploads/" . $row['category_image'] : "../assets/images/placeholder.jpg";
                    echo "<tr>";
                    echo "<td><img src='$img' alt='Img'></td>";
                    echo "<td>" . $row['category_name'] . "</td>";
                    echo "<td>" . number_format($row['price_per_night'], 2) . "</td>";
                    echo "<td>" . $row['max_pax'] . "</td>";
                    echo "<td>" . substr($row['description'], 0, 50) . "...</td>";
                    echo "<td>";
                    echo "<a href='admin_manage_categories.php?edit=" . $row['category_id'] . "' class='action-link link-edit'>EDIT</a>";
                    echo "<a href='admin_manage_categories.php?delete=" . $row['category_id'] . "' class='action-link link-delete' onclick='return confirm(\"Delete this category?\")'>DEL</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center;'>No categories found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>