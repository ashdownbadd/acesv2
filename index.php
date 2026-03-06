<?php
session_start();

require_once 'core/Auth.php';

$auth = new Auth();
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Handle Login Action
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['login_error'] = "Invalid security credentials.";
        header("Location: index.php");
        exit();
    }
}

// Handle Logout Action
if ($action === 'logout') {
    $auth->logout();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>ACES Management System</title>

    <link rel="stylesheet" href="assets/css/variables.css">
    <link rel="stylesheet" href="assets/css/reset.css">
    <link rel="stylesheet" href="assets/css/main.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>

<body>

    <?php
    /**
     * Main Routing Controller
     */
    if (!$auth->isLoggedIn()) {
        // Authentication View
        include __DIR__ . '/views/auth/login.php';
    } else {
        $role = $auth->getRole();

        if ($role === 'admin') {
            include __DIR__ . '/views/auth/admin/dashboard.php';
        } else {
            include __DIR__ . '/views/auth/member/profile.php';
        }
    }
    ?>

    <script src="assets/js/theme.js"></script>
</body>

</html>