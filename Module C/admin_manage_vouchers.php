<?php
// Module C/admin_manage_vouchers.php
session_start();
require_once '../includes/db_connection.php';

// 权限检查
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../Module A/admin_login.php");
    exit();
}

$msg = "";

// --- 1. 处理添加优惠券 ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code'])); // 强制大写
    $discount_value = floatval($_POST['discount_value']);
    $discount_type = $_POST['discount_type'];
    $min_spend = floatval($_POST['min_spend']);
    $expiry_date = $_POST['expiry_date'];

    // 【新增】后端数值验证
    if ($discount_value <= 0) {
        $msg = "<div class='alert alert-danger'>Error: Discount value must be greater than 0.</div>";
    } elseif ($min_spend < 0) {
        $msg = "<div class='alert alert-danger'>Error: Minimum spend cannot be negative.</div>";
    } elseif ($discount_type == 'percent' && $discount_value > 100) {
        $msg = "<div class='alert alert-danger'>Error: Percentage discount cannot exceed 100%.</div>";
    } else {
        // 数值正常，继续查重
        $check = $conn->query("SELECT * FROM coupons WHERE code = '$code'");
        if ($check->num_rows > 0) {
            $msg = "<div class='alert alert-danger'>Error: Coupon Code '$code' already exists!</div>";
        } else {
            $sql = "INSERT INTO coupons (code, discount_value, discount_type, min_spend, expiry_date) 
                    VALUES ('$code', '$discount_value', '$discount_type', '$min_spend', '$expiry_date')";
            
            if ($conn->query($sql)) {
                $msg = "<div class='alert alert-success'>Voucher '$code' created successfully! Don't forget to distribute it.</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
            }
        }
    }
}

// --- 2. 处理删除优惠券 ---
if (isset($_POST['delete_coupon'])) {
    $id = intval($_POST['coupon_id']);
    $conn->query("DELETE FROM coupons WHERE coupon_id = $id");
    $msg = "<div class='alert alert-warning'>Voucher deleted.</div>";
}

// --- 3. 处理分发优惠券 ---
if (isset($_POST['distribute_coupon'])) {
    $cid = intval($_POST['coupon_id']);
    
    $sql_dist = "INSERT INTO user_coupons (user_id, coupon_id, status)
                 SELECT user_id, '$cid', 'active' FROM users 
                 WHERE role = 'Customer'
                 AND user_id NOT IN (SELECT user_id FROM user_coupons WHERE coupon_id = '$cid')";
                 
    if ($conn->query($sql_dist)) {
        $count = $conn->affected_rows;
        $msg = "<div class='alert alert-success'>Success! Voucher sent to $count new users.</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error distributing: " . $conn->error . "</div>";
    }
}

// 获取所有优惠券
$coupons = $conn->query("SELECT * FROM coupons ORDER BY expiry_date DESC");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Manage Vouchers | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background: white; border-right: 1px solid #dee2e6; }
        .nav-link { color: #333; }
        .nav-link.active { background-color: #e9ecef; font-weight: bold; }
        .card { border: none; shadow: 0 2px 5px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-light pt-3">
            <h5 class="px-3 mb-3 text-muted">Admin Panel</h5>
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">← Back to Dashboard</a></li>
            </ul>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 pt-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Vouchers & Coupons</h1>
            </div>

            <?php echo $msg; ?>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm rounded-4">
                        <div class="card-header bg-dark text-white fw-bold">
                            <i class="bi bi-plus-circle me-2"></i>Create New Voucher
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="add_coupon" value="1">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Voucher Code</label>
                                    <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. SAVE20" required>
                                </div>

                                <div class="row">
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Discount Value</label>
                                        <input type="number" step="0.01" min="0.01" name="discount_value" class="form-control" placeholder="10" required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label small fw-bold">Type</label>
                                        <select name="discount_type" class="form-select">
                                            <option value="fixed">RM (Fixed)</option>
                                            <option value="percent">% (Percent)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Min Spend (RM)</label>
                                    <input type="number" name="min_spend" class="form-control" value="0" min="0">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>

                                <button type="submit" class="btn btn-primary w-100 fw-bold">Create Voucher</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm rounded-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Code</th>
                                            <th>Discount</th>
                                            <th>Min Spend</th>
                                            <th>Expiry</th>
                                            <th class="text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($coupons->num_rows > 0): ?>
                                            <?php while($c = $coupons->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="ps-4 fw-bold text-primary"><?php echo $c['code']; ?></td>
                                                    <td>
                                                        <?php echo ($c['discount_type'] == 'percent') ? intval($c['discount_value'])."%" : "RM ".number_format($c['discount_value'],0); ?> OFF
                                                    </td>
                                                    <td>RM <?php echo $c['min_spend']; ?></td>
                                                    <td>
                                                        <?php 
                                                            $is_expired = (strtotime($c['expiry_date']) < time());
                                                            echo $is_expired ? "<span class='text-danger'>Expired</span>" : $c['expiry_date']; 
                                                        ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <?php if (!$is_expired): ?>
                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Send this voucher to ALL registered customers?');">
                                                            <input type="hidden" name="coupon_id" value="<?php echo $c['coupon_id']; ?>">
                                                            <button type="submit" name="distribute_coupon" class="btn btn-sm btn-success me-1" title="Send to All Users">
                                                                <i class="bi bi-gift-fill"></i> Send
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>

                                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this voucher permanently?');">
                                                            <input type="hidden" name="coupon_id" value="<?php echo $c['coupon_id']; ?>">
                                                            <button type="submit" name="delete_coupon" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No vouchers found. Create one!</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

</body>
</html>