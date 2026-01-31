<?php
// for logout
session_start();

// Check if we need to redirect to Home (index.php) instead of Login
$redirect_to = "login.php"; // Default
if (isset($_GET['redirect']) && $_GET['redirect'] == 'home') {
    $redirect_to = "index.php?msg=logged_out";
}

// 1. Clear all session variables
$_SESSION = array();

// 2. Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destroy the session
session_destroy();

// 4. Redirect
header("Location: " . $redirect_to);
exit();
?>