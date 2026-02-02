<?php
// for admin to manage user
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// 2. Fetch users from database with optional search
$search = "";
$sql = "SELECT * FROM users WHERE role = 'Customer' ORDER BY user_id DESC"; // Default search customers only

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = trim($_GET['search']);
    // Fuzzy search by name or email
    $sql = "SELECT * FROM users WHERE role = 'Customer' AND (full_name LIKE '%$search%' OR email LIKE '%$search%') ORDER BY user_id DESC";
}

$result = $conn->query($sql);

$page_title = "User Management";
include '../includes/header.php';
?>

<div class="container mt-5 mb-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark">User Management</h2>
            <p class="text-muted">Manage registered customers</p>
        </div>
        
        <form action="" method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search Name or Email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i></button>
            <?php if($search): ?>
                <a href="admin_manage_users.php" class="btn btn-outline-secondary" title="Clear Search"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill me-2"></i> Registered User List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark"> 
                        <tr>
                            <th scope="col" class="ps-4">ID</th>
                            <th scope="col">User Info</th> <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">Registered At</th>
                            <th scope="col" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                    // profile picture path
                                    $pfp = !empty($row['profile_image']) ? "uploads/".$row['profile_image'] : "uploads/default.png";
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?php echo $row['user_id']; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $pfp; ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd;">
                                            <span class="fw-bold"><?php echo $row['full_name']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td class="small text-muted"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                                    <td class="text-end pe-4">
                                        <a href="admin_reset_user.php?id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-warning me-1"
                                           onclick="return confirm('Reset password for <?php echo $row['full_name']; ?> to default (homestay123)?');"
                                           title="Reset Password">
                                            <i class="bi bi-key"></i>
                                        </a>
                                        
                                        <a href="admin_delete_user.php?id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to DELETE this user? This action cannot be undone.');"
                                           title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No users found matching your search.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white text-muted small">
            Total Customers: <?php echo $result->num_rows; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>