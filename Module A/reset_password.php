<?php
// for reset password
session_start();
require_once '../includes/db_connection.php';

$error = "";
$token = "";
$token_valid = false; //remark whether token is valid

// 1. Token Validation
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    // Check token expired or not
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $token_valid = true;
    }
} 

// 2. Process new password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
    $token = $_POST['token'];
    $pass1 = $_POST['password'];
    $pass2 = $_POST['confirm_password'];

    // Check token validity again
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 1) {
        if ($pass1 !== $pass2) {
            $error = "Passwords do not match.";
            $token_valid = true; 
        } elseif (strlen($pass1) < 6) {
            $error = "Password must be at least 6 characters.";
            $token_valid = true;
        } else {
            $hashed_password = password_hash($pass1, PASSWORD_DEFAULT);
            // Update password and clear token
            $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
            $update->bind_param("ss", $hashed_password, $token);
            
            if ($update->execute()) {
                echo "<script>alert('Password reset successful! Please login.'); window.location.href='login.php';</script>";
                exit();
            } else {
                $error = "System error.";
            }
        }
    } else {
        $token_valid = false; // submit invalid token
    }
}

$page_title = "Reset Password";
include_once '../includes/header.php'; 
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5"> 
                    
                    <div class="text-center mb-5">
                        <div class="mb-3"><i class="bi bi-lock-fill text-dark" style="font-size: 3rem;"></i></div>
                        <h2 class="fw-bold text-dark">Reset Password</h2>
                        <p class="text-muted">Create a new strong password</p>
                    </div>

                    <?php if (!$token_valid): ?>
                        <div class="alert alert-danger text-center rounded-3 p-4">
                            <h4 class="alert-heading fw-bold"><i class="bi bi-x-circle-fill"></i> Invalid or Expired Link</h4>
                            <p>This password reset link is invalid or has expired.</p>
                            <hr>
                            <a href="forgot_password.php" class="btn btn-outline-danger fw-bold">Request New Link</a>
                        </div>
                    
                    <?php else: ?>
                        
                        <?php if($error): ?>
                            <div class="alert alert-danger text-center rounded-3 mb-4"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-secondary">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 px-3"><i class="bi bi-key fs-5"></i></span>
                                    <input type="password" name="password" class="form-control form-control-lg bg-light border-0 py-3" required>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-bold small text-secondary">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 px-3"><i class="bi bi-key-fill fs-5"></i></span>
                                    <input type="password" name="confirm_password" class="form-control form-control-lg bg-light border-0 py-3" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-dark btn-lg py-3 rounded-3 fw-bold">Update Password</button>
                            </div>
                        </form>

                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>