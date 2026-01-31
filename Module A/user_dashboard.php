<?php
// for user dahsboard 

// 1.set timezone Malaysia
date_default_timezone_set("Asia/Kuala_Lumpur");

session_start();
require_once '../includes/db_connection.php';

// 2. Safety check: must be Customer
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = ""; 

// 3. Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_name = $_POST['full_name'];
    $new_phone = $_POST['phone'];
    
    $conn->query("UPDATE users SET full_name='$new_name', phone='$new_phone' WHERE user_id='$user_id'");
    $_SESSION['user_name'] = $new_name; 
    $msg = "Profile Updated Successfully!";
    $msg_type = "success";

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $filename = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $filename;
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        
        if($check !== false) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $conn->query("UPDATE users SET profile_image='$filename' WHERE user_id='$user_id'");
            } else {
                $msg = "Error uploading file."; $msg_type = "danger";
            }
        } else {
            $msg = "File is not an image."; $msg_type = "danger";
        }
    }
}

// 4. Fetch user data
$user_res = $conn->query("SELECT * FROM users WHERE user_id='$user_id'");
$user = $user_res->fetch_assoc();
$profile_pic = !empty($user['profile_image']) ? "uploads/".$user['profile_image'] : "uploads/default.png";

$page_title = "My Dashboard";
include 'header.php'; 
?>

<div class="container-fluid px-md-5 mt-5 mb-5">
  
  <div class="d-flex justify-content-between align-items-center welcome-header border-bottom pb-3 mb-4">
     <div>
        <h2 class="welcome-text fw-bold text-dark">Welcome, <?php echo $user['full_name']; ?>! 👋</h2>
        <p class="text-muted mb-0">Manage your profile and view your latest bookings here.</p>
     </div>
     <div class="text-end d-none d-md-block">
        <h5 class="mb-0 fw-bold text-secondary" id="live-clock"><?php echo date("h:i:s A"); ?></h5>
        <small class="text-muted" id="live-date"><?php echo date("l, d F Y"); ?></small>
     </div>
  </div>

  <div class="row justify-content-center">
    
    <div class="col-md-4 mb-4">
        <div class="card text-center p-4 shadow-sm border-0 h-100 rounded-4">
            <div class="mb-3">
                <img src="<?php echo $profile_pic; ?>" alt="Profile Image" class="profile-img-large">
            </div>
            <h4><?php echo $user['full_name']; ?></h4>
            <p class="badge bg-secondary"><?php echo $user['role']; ?></p>
            <p class="text-muted small"><?php echo $user['email']; ?></p>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card p-4 shadow-sm border-0 mb-4 rounded-4">
            <h4 class="mb-4 fw-bold">Edit Profile</h4>
            
            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?>" role="alert">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-secondary">Full Name</label>
                        <input type="text" class="form-control bg-light border-0 py-2" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-secondary">Phone Number</label>
                        <input type="text" class="form-control bg-light border-0 py-2" name="phone" value="<?php echo $user['phone']; ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold small text-secondary">Upload New Profile Picture</label>
                    <input type="file" class="form-control bg-light border-0" name="profile_image" accept="image/*">
                </div>

                <button type="submit" class="btn btn-warning text-dark fw-bold px-4 py-2">
                    Update Profile
                </button>
            </form>
        </div>
        
        <div class="card p-4 shadow-sm border-0 rounded-4">
            <h5 class="mb-3 fw-bold">Booking History</h5>
            <div class="alert alert-light border">
                <i class="bi bi-info-circle-fill text-warning"></i> 
                Currently, no booking data is available. (Module C Integration Pending)
            </div>
        </div>
    </div>
  </div>

</div> 

<script>
    function updateClock() {
        const now = new Date();
        
        // 1. set time format
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        const timeString = now.toLocaleTimeString('en-US', timeOptions);
        
        // 2. set date format
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateString = now.toLocaleDateString('en-US', dateOptions);
        
        // 3. update html
        document.getElementById('live-clock').textContent = timeString;
        document.getElementById('live-date').textContent = dateString;
    }

    // update every second
    setInterval(updateClock, 1000);
    updateClock();
</script>

<?php 
include 'footer.php'; 
?>