<?php
session_start();

require_once 'core/Auth.php';
require_once 'core/db.php';

$auth = new Auth();
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// --- LOGIN/LOGOUT LOGIC ---
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

if ($action === 'logout') {
    $auth->logout();
    header("Location: index.php");
    exit();
}

if ($auth->isLoggedIn()) {
    $db = (new DB())->connect();
    $userId = $_SESSION['user_id'] ?? $_SESSION['member_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
    $isMember = ($_SESSION['role'] ?? '') === 'member';

    // Handle Member Credentials Update
    if ($action === 'update_member_credentials' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $newUsername = $_POST['username'] ?? '';
        $newPassword = $_POST['password'] ?? '';
        $memberId = $_SESSION['member_id'] ?? 0;

        if ($memberId > 0 && !empty($newUsername)) {
            try {
                if (!empty($newPassword)) {
                    // Update both Username and Password (Hashed)
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE members SET username = ?, password = ? WHERE id = ?");
                    $stmt->execute([$newUsername, $hashedPassword, $memberId]);
                } else {
                    // Update Username only
                    $stmt = $db->prepare("UPDATE members SET username = ? WHERE id = ?");
                    $stmt->execute([$newUsername, $memberId]);
                }

                header("Location: index.php?status=success");
            } catch (PDOException $e) {
                header("Location: index.php?status=error");
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google" content="notranslate">
    <title>ACES Management System</title>
    <link rel="stylesheet" href="/acesv2/assets/css/variables.css">
    <link rel="stylesheet" href="/acesv2/assets/css/reset.css">
    <link rel="stylesheet" href="/acesv2/assets/css/main.css">
    <link rel="stylesheet" href="/acesv2/assets/css/member.css">
    <link rel="stylesheet" href="/acesv2/assets/css/profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
        })();
    </script>
</head>

<body>
    <?php
    if (!$auth->isLoggedIn()) {
        include __DIR__ . '/views/auth/login.php';
    } else {
        // Prepare navbar data
        $user = [
            'name' => $_SESSION['name'] ?? 'User',
            'role' => ucfirst($_SESSION['role'] ?? 'Unassigned'),
            'initials' => strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1))
        ];

        include __DIR__ . '/views/auth/partials/navbar.php';

        // --- ROLE-BASED ROUTING ---
        if (strtolower($_SESSION['role'] ?? '') === 'member') {

            // 1. MEMBER VIEW
            include __DIR__ . '/views/auth/member/profile.php';
        } else {

            // 2. ADMIN VIEW
            $page = $_GET['page'] ?? 'dashboard';

            switch ($page) {
                case 'amortization':
                    include __DIR__ . '/amortization.php';
                    break;
                case 'settings':
                    include __DIR__ . '/views/auth/admin/settings.php';
                    break;
                case 'member_view':
                    include __DIR__ . '/views/auth/admin/member_view.php';
                    break;
                case 'member_add':
                    include __DIR__ . '/views/auth/admin/member_add.php';
                    break;
                case 'dashboard':
                default:
                    include __DIR__ . '/views/auth/admin/dashboard.php';
                    break;
            }
        }
    }
    ?>
    <script type="module" src="/acesv2/assets/js/theme.js"></script>
    <script type="module" src="/acesv2/assets/js/app.js"></script>
    <script type="module" src="/acesv2/assets/js/member.js"></script>
</body>

</html>