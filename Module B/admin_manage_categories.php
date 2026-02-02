<?php
include '../includes/db_connection.php';
include '../includes/header.php';

$alertMessage = "";

// =======================================================
//  1. 筛选逻辑 (Filter)
// =======================================================
$where_clauses = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $s = $conn->real_escape_string($_GET['search']);
    // 现在主要搜 categories 表
    $where_clauses[] = "(rooms.room_name LIKE '%$s%' OR categories.category_name LIKE '%$s%')";
}
if (isset($_GET['filter_cat']) && !empty($_GET['filter_cat'])) {
    // 这里的 filter_cat 实际上是筛选 category_name
    $f_cat = $conn->real_escape_string($_GET['filter_cat']);
    $where_clauses[] = "categories.category_name = '$f_cat'";
}
if (isset($_GET['filter_price']) && !empty($_GET['filter_price'])) {
    $f_price = floatval($_GET['filter_price']);
    $where_clauses[] = "categories.price_per_night <= $f_price";
}
if (isset($_GET['filter_desc']) && !empty($_GET['filter_desc'])) {
    $f_desc = $conn->real_escape_string($_GET['filter_desc']);
    $where_clauses[] = "categories.description LIKE '%$f_desc%'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// =======================================================
//  2. 保存逻辑 (Save Data) - 数据主要进 Categories 表
// =======================================================
if (isset($_POST['save_data'])) {
    // 隐藏的 Category ID (如果是 Edit)
    $cat_id = $_POST['cat_id_hidden'] ?? '';
    
    // 表单数据
    $room_id_select = $_POST['homestay_select']; // 选中的 Room ID
    $cat_name = $_POST['category_select'];       // 选中的 Category Name
    $price = $_POST['price_per_night'];
    $pax = $_POST['max_pax'];
    $desc = $conn->real_escape_string($_POST['description']);

    // 1. 处理 Homestay (Rooms 表)
    // 如果还没这间民宿(虽然下拉菜单通常是有的)，或者需要更新价格范围
    // 这里我们假设 Rooms 表已经预先填好了民宿名字，或者你之后通过另一个页面管理
    // 但为了逻辑完整，我们需要计算 Min/Max Price 并更新 Rooms 表
    
    // 2. 处理 Category Name (可以是新输入的)
    if ($cat_name === 'other') {
        $cat_name = $conn->real_escape_string($_POST['category_new_input']);
    }

    if (!empty($cat_id)) {
        // --- UPDATE Existing Category ---
        $sql = "UPDATE categories SET 
                room_id='$room_id_select', 
                category_name='$cat_name', 
                price_per_night='$price', 
                max_pax='$pax', 
                description='$desc' 
                WHERE category_id='$cat_id'";
        
        if ($conn->query($sql) === TRUE) {
            $alertMessage = "alert('Category updated successfully!'); window.location.href='admin_manage_categories.php';";
        }
    } else {
        // --- INSERT New Category ---
        // 检查这个 Homestay 是否已经有了这个分类名
        $check = $conn->query("SELECT category_id FROM categories WHERE room_id='$room_id_select' AND category_name='$cat_name'");
        if ($check->num_rows > 0) {
            $alertMessage = "alert('Error: This Homestay already has a category named $cat_name.');";
        } else {
            $sql = "INSERT INTO categories (room_id, category_name, price_per_night, max_pax, description) 
                    VALUES ('$room_id_select', '$cat_name', '$price', '$pax', '$desc')";
            
            if ($conn->query($sql) === TRUE) {
                $alertMessage = "alert('New Category added to Homestay!'); window.location.href='admin_manage_categories.php';";
            }
        }
    }

    // ★ 自动更新 Rooms 表的 Min/Max Price ★
    if ($conn->affected_rows > 0 || !empty($alertMessage)) {
        // 找出该民宿下最贵和最便宜的价格
        $price_res = $conn->query("SELECT MIN(price_per_night) as min_p, MAX(price_per_night) as max_p FROM categories WHERE room_id='$room_id_select'");
        $p_row = $price_res->fetch_assoc();
        $min = $p_row['min_p'] ?? 0;
        $max = $p_row['max_p'] ?? 0;
        $conn->query("UPDATE rooms SET min_price='$min', max_price='$max' WHERE room_id='$room_id_select'");
    }
}

// Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // 先获取 room_id 以便更新价格
    $get_rid = $conn->query("SELECT room_id FROM categories WHERE category_id='$id'");
    $rid_row = $get_rid->fetch_assoc();
    $r_id = $rid_row['room_id'];

    // 删除
    $conn->query("DELETE FROM categories WHERE category_id='$id'");
    
    // 更新 Rooms 价格
    $price_res = $conn->query("SELECT MIN(price_per_night) as min_p, MAX(price_per_night) as max_p FROM categories WHERE room_id='$r_id'");
    $p_row = $price_res->fetch_assoc();
    $min = $p_row['min_p'] ?? 0;
    $max = $p_row['max_p'] ?? 0;
    $conn->query("UPDATE rooms SET min_price='$min', max_price='$max' WHERE room_id='$r_id'");

    echo "<script>alert('Category Deleted.'); window.location.href='admin_manage_categories.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Homestays</title>
    <style>
        /* =========================================
           UI Styles (保留你满意的样式)
           ========================================= */
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .container { max-width: 1300px; margin: 40px auto; padding: 0 20px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }

        /* Filter Bar */
        .filter-bar { background: #fff; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #555; white-space: nowrap; line-height: 1.2; }
        .filter-group select, .filter-group input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; height: 38px; box-sizing: border-box; width: 200px; margin:0; }
        .filter-actions { display: flex; gap: 10px; }
        .btn-filter { height: 38px; padding: 0 20px; background-color: #28a745 !important; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; box-sizing: border-box; }
        .btn-clear { height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 20px; background-color: #dc3545 !important; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 14px; box-sizing: border-box; border: none; }

        /* Action Bar */
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .list-title { font-size: 20px; font-weight: bold; color: #333; margin: 0; }
        .search-add-group { display: flex; align-items: center; gap: 15px; height: 40px; }
        .search-form { display: flex; height: 100%; align-items: stretch; }
        .search-form input { height: 40px; padding: 0 12px; border: 1px solid #ccc; border-right: none; border-radius: 4px 0 0 4px; width: 250px; outline: none; box-sizing: border-box; margin:0; }
        .search-form button { height: 40px; padding: 0 20px; background: #333; color: #fff; border: 1px solid #333; border-radius: 0 4px 4px 0; cursor: pointer; font-weight: bold; box-sizing: border-box; margin:0; line-height: normal; }
        .btn-add-new { height: 40px; background: #28a745; color: white; padding: 0 20px; border-radius: 4px; font-weight: bold; border: 1px solid #28a745; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; white-space: nowrap; box-sizing: border-box; margin:0; text-decoration: none; font-size: 14px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden; }
        table th { background: #343a40; color: white; padding: 12px; text-align: left; }
        table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; color: #333; font-size: 14px; }
        .homestay-name-cell { font-weight: bold; background-color: #f8f9fa; vertical-align: top; }
        .btn-edit, .btn-del { display: inline-flex; align-items: center; justify-content: center; width: 60px; height: 30px; border-radius: 3px; font-size: 12px; font-weight: bold; cursor: pointer; text-decoration: none; border: none; color: white; margin-right: 5px; }
        .btn-edit { background: #28a745 !important; } 
        .btn-del { background: #dc3545 !important; }

        /* Modal Styles */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 25px; width: 500px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        #modalTitle { margin: 0; font-size: 20px; font-weight: bold; color: #333; }
        .close-btn { background: none; border: none; font-size: 28px; color: #888; cursor: pointer; line-height: 1; padding: 0; }
        .close-btn:hover { color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn-save { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        #newCategoryDiv { display: none; margin-top: 5px; }
        #newCategoryDiv input { border: 1px solid #28a745; background-color: #f0fff4; }
    </style>

    <script>
        function toggleCategorySelect() {
            var select = document.getElementById('catSelect');
            var inputDiv = document.getElementById('newCategoryDiv');
            var inputField = document.getElementById('categoryInput');
            if (select.value === 'other') {
                inputDiv.style.display = 'block';
                inputField.required = true;
            } else {
                inputDiv.style.display = 'none';
                inputField.required = false;
            }
        }

        function openModal(mode, data = {}) {
            var title = document.getElementById('modalTitle');
            var roomSelect = document.getElementById('homestaySelect');
            
            // 填充表单
            document.getElementById('catId').value = data.cat_id || '';
            document.getElementById('roomPrice').value = data.price || '';
            document.getElementById('catDesc').value = data.desc || ''; 
            document.getElementById('catPax').value = data.pax || ''; 
            
            // Category 选择逻辑
            var catSelect = document.getElementById('catSelect');
            var found = false;
            if (data.cat_name) {
                // 尝试在下拉菜单中找到对应的 Option
                for (var i = 0; i < catSelect.options.length; i++) {
                    if (catSelect.options[i].text === data.cat_name) {
                        catSelect.selectedIndex = i;
                        found = true;
                        break;
                    }
                }
                // 如果是新名字或者 "Other"，可能需要特殊处理，这里简化为默认
                if(!found) catSelect.value = ''; 
            } else {
                catSelect.value = '';
            }
            document.getElementById('newCategoryDiv').style.display = 'none';

            // Homestay 选择逻辑
            roomSelect.value = data.room_id || '';

            if (mode === 'edit') {
                title.innerText = 'Edit Category Detail';
                // 编辑时锁定 Homestay 不让改，因为这是分类的属性
                roomSelect.disabled = true; 
                // 为了提交时能拿到值，需要一个 hidden input (或者 JS 在 submit 前 enable)
                // 这里简单起见，我们加个 hidden input 存 room_id
                document.getElementById('homestayIdHidden').value = data.room_id;
            } else {
                title.innerText = 'Add New Category'; 
                roomSelect.disabled = false;
                document.getElementById('homestayIdHidden').value = '';
            }
            document.getElementById('categoryModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById('categoryModal')) closeModal();
        }
    </script>
    <?php if(!empty($alertMessage)) { echo "<script>$alertMessage</script>"; } ?>
</head>
<body>

<div class="container">
    <h2>Manage Homestays & Categories</h2>

    <form class="filter-bar" method="get">
        <div class="filter-group">
            <label>Filter Category</label>
            <select name="filter_cat">
                <option value="">All Categories</option>
                <?php
                // 这里的 categories 表现在只存名字可能重复，所以用 DISTINCT
                $cats = $conn->query("SELECT DISTINCT category_name FROM categories ORDER BY category_name ASC");
                while($c = $cats->fetch_assoc()) echo "<option value='".$c['category_name']."'>".$c['category_name']."</option>";
                ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Max Price (RM)</label>
            <input type="number" name="filter_price" placeholder="150" value="<?php echo $_GET['filter_price'] ?? ''; ?>">
        </div>
        <div class="filter-group">
            <label>Description Keyword</label>
            <input type="text" name="filter_desc" placeholder="e.g. WiFi" value="<?php echo $_GET['filter_desc'] ?? ''; ?>">
        </div>
        
        <div class="filter-group">
            <label style="visibility: hidden;">Action</label> 
            <div class="filter-actions">
                <button type="submit" class="btn-filter">APPLY FILTER</button>
                <?php if(!empty($_GET)) echo '<a href="admin_manage_categories.php" class="btn-clear">CLEAR</a>'; ?>
            </div>
        </div>
    </form>

    <div class="action-bar">
        <h3 class="list-title">Homestay List</h3>
        <div class="search-add-group">
            <form action="" method="get" class="search-form">
                <input type="text" name="search" placeholder="Search Homestay or Category..." value="<?php echo $_GET['search'] ?? ''; ?>">
                <button type="submit">SEARCH</button>
            </form>
            <button type="button" class="btn-add-new" onclick="openModal('add')">+ Add New Category</button>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="25%">Homestay Name</th>
                <th width="20%">Category</th>
                <th width="15%">Price (RM)</th>
                <th width="5%">Pax</th>
                <th width="20%">Description</th>
                <th width="15%">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // ★ 核心查询：以 categories 表为主，JOIN rooms 表获取名字
            $sql = "SELECT categories.*, rooms.room_name 
                    FROM categories 
                    JOIN rooms ON categories.room_id = rooms.room_id 
                    $where_sql 
                    ORDER BY rooms.room_name ASC, categories.category_name ASC";
            
            $result = $conn->query($sql);
            $groupedData = [];
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $groupedData[$row['room_name']][] = $row;
                }
            }

            if (!empty($groupedData)) {
                foreach ($groupedData as $name => $rows) {
                    $count = count($rows);
                    $isFirst = true;
                    
                    foreach ($rows as $row) {
                        echo "<tr>";
                        if ($isFirst) {
                            // Rowspan 显示 Homestay 名字
                            echo "<td rowspan='$count' class='homestay-name-cell'>" . $row['room_name'] . "</td>";
                            $isFirst = false;
                        }
                        echo "<td>" . $row['category_name'] . "</td>";
                        echo "<td>RM " . number_format($row['price_per_night'], 2) . "</td>";
                        echo "<td>" . $row['max_pax'] . "</td>";
                        echo "<td>" . substr($row['description'], 0, 30) . "...</td>";
                        echo "<td>";
                        
                        $descSafe = htmlspecialchars($row['description'], ENT_QUOTES);
                        $jsData = json_encode([
                            'cat_id'=>$row['category_id'], // 这里是 Category 的 ID
                            'room_id'=>$row['room_id'], 
                            'room_name'=>$row['room_name'],
                            'cat_name'=>$row['category_name'],
                            'price'=>$row['price_per_night'],
                            'desc'=>$descSafe,
                            'pax'=>$row['max_pax']
                        ]);
                        
                        echo "<button class='btn-edit' onclick='openModal(\"edit\", $jsData)'>EDIT</button>";
                        echo "<a href='admin_manage_categories.php?delete=" . $row['category_id'] . "' class='btn-del' onclick='return confirm(\"Delete this category?\")'>DEL</a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                }
            } else {
                echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>No results found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<div id="categoryModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Category</h3>
            <button class="close-btn" onclick="closeModal()">&times;</button>
        </div>
        
        <form action="" method="post">
            <input type="hidden" name="cat_id_hidden" id="catId">
            <input type="hidden" name="homestay_select" id="homestayIdHidden">

            <div class="form-group">
                <label>Homestay Name</label>
                <select name="homestay_select" id="homestaySelect" onchange="document.getElementById('homestayIdHidden').value = this.value">
                    <option value="">-- Choose Existing Homestay --</option>
                    <?php
                    $r_res = $conn->query("SELECT * FROM rooms ORDER BY room_name ASC");
                    while($r = $r_res->fetch_assoc()) {
                        echo "<option value='".$r['room_id']."'>".$r['room_name']."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category_select" id="catSelect" onchange="toggleCategorySelect()" required>
                    <option value="">-- Choose Category --</option>
                    <?php
                    $c_res = $conn->query("SELECT DISTINCT category_name FROM categories ORDER BY category_name ASC");
                    while($c = $c_res->fetch_assoc()) {
                        echo "<option value='".$c['category_name']."'>".$c['category_name']."</option>";
                    }
                    ?>
                    <option value="other" style="color:blue; font-weight:bold;">+ Other (Create New Category)</option>
                </select>
                <div id="newCategoryDiv">
                    <input type="text" name="category_new_input" id="categoryInput" placeholder="Enter New Category Name">
                </div>
            </div>

            <div class="form-group">
                <label>Price Per Night (RM)</label>
                <input type="number" step="0.01" name="price_per_night" id="roomPrice" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Max Pax</label>
                <input type="number" name="max_pax" id="catPax" required placeholder="2">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="catDesc" rows="3" placeholder="Enter category description..."></textarea>
            </div>

            <button type="submit" name="save_data" class="btn-save">SAVE</button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

</body>
</html>