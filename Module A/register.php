<?php
// for register
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
    exit(); 
}

// 2. REGISTRATION LOGIC: Only for guests
require_once '../includes/db_connection.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $full_name = substr(trim($_POST['full_name']), 0, 100);
    $email = strtolower(substr(trim($_POST['email']), 0, 255)); // Convert email to lowercase
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // --- 1. EMAIL DOMAIN VALIDATION START --- [
    $email_parts = explode('@', $email);
    $domain = end($email_parts); // Get the part after @
    $domain_valid = false;

    // List of trusted public email providers
    $trusted_domains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 
        'icloud.com', 'live.com', 'aol.com', 'protonmail.com', 
        'ymail.com', 'msn.com'
    ];

    // Check 1: Is it in the trusted list?
    if (in_array($domain, $trusted_domains)) {
        $domain_valid = true;
    }
    // Check 2: Is it a school/education email? (Ends with .edu, .edu.my, .ac, .sch, etc.)
    elseif (strpos($domain, '.edu') !== false || strpos($domain, '.ac.') !== false || strpos($domain, '.sch') !== false || strpos($domain, 'student') !== false) {
        $domain_valid = true;
    }
    // --- EMAIL VALIDATION END ---


    // --- 2. MALAYSIA PHONE VALIDATION START ---
    $phone_input = trim($_POST['phone']);
    $clean_phone = preg_replace('/[^0-9]/', '', $phone_input); // Remove non-digits
    $phone_valid = false;

    // Rule 1: Starts with 60 (Length 11-12)
    if (substr($clean_phone, 0, 2) === '60') {
        if (strlen($clean_phone) >= 11 && strlen($clean_phone) <= 12) {
            $phone_valid = true;
        }
    }
    // Rule 2: Starts with 01 (Length 10-11)
    elseif (substr($clean_phone, 0, 2) === '01') {
        if (strlen($clean_phone) >= 10 && strlen($clean_phone) <= 11) {
            $phone_valid = true;
        }
    }
    
    $phone = $clean_phone; 
    // --- PHONE VALIDATION END ---

    // --- 3. EXECUTE CHECKS ---
    if (!$domain_valid) {
        // 
        $error = "Registration Failed: We only accept emails from trusted providers (Gmail, Yahoo, Outlook, etc.) or Education/School domains.";
    } elseif (!$phone_valid) {
        $error = "Registration Failed: Strictly for Malaysian number only (e.g. 0123456789 or 60123456789).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check for duplicate email or phone
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
            // Hash password and insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set default role
            $role = 'Customer'; 
            $insertStmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("sssss", $full_name, $email, $hashed_password, $phone, $role);

            if ($insertStmt->execute()) {
                 echo "<script>alert('Registration Successful!'); window.location.href='login.php';</script>";
                 exit();
            } else {
                $error = "System Error. Please try again later.";
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
        <div class="col-md-11 col-lg-10"> <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5"> 
                    
                    <div class="text-center mb-5">
                        <h2 class="fw-bold text-dark display-6">Create Account</h2>
                        <p class="text-muted">Join us to start your journey</p>
                    </div>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger rounded-3"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Full Name</label>
                            <input type="text" name="full_name" class="form-control form-control-lg bg-light border-0 py-3" 
                                   required maxlength="100" placeholder="Your Full Name">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Phone Number (MY Only)</label>
                            <input type="text" name="phone" class="form-control form-control-lg bg-light border-0 py-3" 
                                   placeholder="e.g. 0123456789 or 601..." 
                                   required maxlength="12"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12);">
                            <small class="text-muted" style="font-size: 0.8rem;">Malaysian format only (01x... or 601...)</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-secondary">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0 py-3" 
                                   required maxlength="255" placeholder="name@example.com (Gmail, Yahoo, School, etc.)">
                        </div>

                        <div class="mb-1"> 
                            <label class="form-label fw-bold small text-secondary">Password</label>
                            <input type="password" name="password" id="passwordInput" class="form-control form-control-lg bg-light border-0 py-3" required>
                        </div>
                        
                        <div class="mb-4 d-flex align-items-center flex-wrap">
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
        let missing = []; 

        // 1. Check what is missing
        if (val.length < 6) missing.push("6+ Chars");
        if (!/[A-Z]/.test(val)) missing.push("Uppercase");
        if (!/[0-9]/.test(val)) missing.push("Number");
        if (!/[^A-Za-z0-9]/.test(val)) missing.push("Symbol");

        // 2. Logic to display hint vs success
        if (val.length === 0) {
            strengthText.textContent = "Enter password...";
            strengthText.className = "fw-bold small text-muted";
        } 
        else if (missing.length > 0) {
            // If something is missing, list it out
            strengthText.innerHTML = "Weak <span class='text-muted fw-normal'>(Add: " + missing.join(", ") + ")</span>";
            strengthText.className = "fw-bold small text-danger";
        } 
        else {
            // If nothing missing -> Strong
            strengthText.textContent = "Strong 🟢";
            strengthText.className = "fw-bold small text-success";
        }
    });
</script>

<?php 
include_once '../includes/footer.php'; 
?>