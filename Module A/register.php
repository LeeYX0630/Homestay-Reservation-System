<?php
// Module A/register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. GATEKEEPER: Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    require_once '../includes/db_connection.php';
    
    // Determine redirect link based on role
    $dashboard_link = (isset($_SESSION['role']) && ($_SESSION['role'] === 'superadmin' || $_SESSION['role'] === 'admin')) ? '../Module C/admin_dashboard.php' : 'user_dashboard.php';
    
    $page_title = "Already Logged In";
    include_once '../includes/header.php'; 
?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-10">
                <div class="card shadow-lg border-0 rounded-4 text-center p-5">
                    <div class="mb-4">
                        <i class="bi bi-person-check-fill text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="fw-bold mb-3">You are already logged in!</h2>
                    
                    <p class="text-muted mb-4 fs-5">
                        Hi <strong><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'User'; ?></strong>,<br>
                        you are currently using an active account.
                    </p>
                    
                    <div class="alert alert-light border border-secondary border-opacity-25 rounded-3 mb-4">
                        <small class="text-secondary fw-bold">
                            <i class="bi bi-info-circle me-1"></i> 
                            To register a new account, you must sign out first.
                        </small>
                    </div>

                    <div class="d-grid gap-3">
                        <a href="<?php echo $dashboard_link; ?>" class="btn btn-dark btn-lg rounded-3 py-3">
                            Go to My Dashboard
                        </a>

                        <a href="logout.php" class="btn btn-outline-danger btn-lg rounded-3 py-3" 
                           onclick="return confirm('Confirm Logout:\n\nAre you sure you want to log out to create a new account?');">
                            Sign Out & Register New
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
    include_once '../includes/footer.php';
    exit(); // Stop execution here
}

// 2. REGISTRATION LOGIC: Only for guests
require_once '../includes/db_connection.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $full_name = substr(trim($_POST['full_name']), 0, 100);
    $email = substr(trim($_POST['email']), 0, 255);
    $phone = trim($_POST['phone']); // 暂时不截断，先验证
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // --- 【修复 1】后端验证逻辑 ---

    // A. 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address format.";
    }
    // B. 验证电话号码 (只允许数字，且长度在 9-15 位之间)
    elseif (!preg_match('/^[0-9]{9,15}$/', $phone)) {
        $error = "Invalid phone number. Please enter only digits (9-15 numbers).";
    }
    // C. 验证密码长度
    elseif (strlen($password) < 6) {
        $error = "Password is too short. It must be at least 6 characters.";
    }
    // D. 验证密码匹配
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } 
    else {
        // 全部验证通过，开始查重
        $checkStmt = $conn->prepare("SELECT email, phone FROM users WHERE email = ? OR phone = ?");
        $checkStmt->bind_param("ss", $email, $phone);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            if ($row['email'] === $email) {
                $error = "Email already registered! Please login.";
            } elseif ($row['phone'] === $phone) {
                $error = "Phone number already used! Please use another.";
            }
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'Customer'; 
            
            $insertStmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssss", $full_name, $email, $hashed_password, $phone, $role);

            if ($insertStmt->execute()) {
                 echo "<script>alert('Registration Successful!'); window.location.href='login.php';</script>";
                 exit();
            } else {
                $error = "System Error: " . $conn->error;
            }
        }
        $checkStmt->close();
    }
}

$page_title = "Register - Homestay";
include_once '../includes/header.php'; 
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-11 col-lg-10">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5"> 
                    
                    <div class="text-center mb-5">
                        <h2 class="fw-bold text-dark display-6">Create Account</h2>
                        <p class="text-muted">Join us to start your journey</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger rounded-3 text-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control form-control-lg bg-light border-0 py-3" 
                                   required maxlength="100" placeholder="Your Full Name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Phone Number</label>
                            <input type="tel" name="phone" class="form-control form-control-lg bg-light border-0 py-3" 
                                   placeholder="e.g. 0123456789" required maxlength="15"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0 py-3" 
                                   required maxlength="255" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="mb-1"> 
                            <label class="form-label fw-bold small text-secondary">Password</label>
                            <input type="password" name="password" id="passwordInput" class="form-control form-control-lg bg-light border-0 py-3" required>
                        </div>
                        
                        <div class="mb-4 d-flex align-items-center">
                            <small class="text-muted me-2">Strength:</small> 
                            <span id="strengthText" class="fw-bold small text-muted">Enter password...</span>
                        </div>

                        <div class="mb-5"> 
                            <label class="form-label fw-bold small text-secondary">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control form-control-lg bg-light border-0 py-3" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-dark btn-lg py-3 rounded-3 fw-bold" style="background-color: #333;">
                                Sign Up Now
                            </button>
                        </div>

                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted">Already have an account? <a href="login.php" class="text-warning fw-bold text-decoration-none">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const passwordInput = document.getElementById('passwordInput');
    const strengthText = document.getElementById('strengthText');

    passwordInput.addEventListener('input', function() {
        const val = passwordInput.value;
        let strength = 0;

        // Length Check
        if (val.length >= 6) strength += 1;
        if (val.length >= 10) strength += 1;

        // Complexity Check
        if (/[0-9]/.test(val)) strength += 1; // Numbers
        if (/[A-Z]/.test(val)) strength += 1; // Uppercase
        if (/[^A-Za-z0-9]/.test(val)) strength += 1; // Symbols

        // Output Status
        if (val.length === 0) {
            strengthText.textContent = "Enter password...";
            strengthText.className = "fw-bold small text-muted";
        } else if (val.length < 6) {
            strengthText.textContent = "Too Short 🔴";
            strengthText.className = "fw-bold small text-danger";
        } else if (strength < 3) {
            strengthText.textContent = "Weak 🔴";
            strengthText.className = "fw-bold small text-danger";
        } else if (strength === 3 || strength === 4) {
            strengthText.textContent = "Medium 🟠";
            strengthText.className = "fw-bold small text-warning";
        } else {
            strengthText.textContent = "Strong 🟢";
            strengthText.className = "fw-bold small text-success";
        }
    });
</script>

<?php 
include_once '../includes/footer.php'; 
?>