<?php
session_start();

require_once 'core/Auth.php';
require_once 'core/db.php';

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

// Handle Profile Update Action
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = (new DB())->connect();
    $userId = $_SESSION['user_id'];

    // 1. Update Name and Username
    $name = $_POST['name'] ?? $_SESSION['name'];
    $username = $_POST['username'] ?? $_SESSION['username'];

    $stmt = $db->prepare("UPDATE admin SET name = ?, username = ? WHERE id = ?");
    $stmt->execute([$name, $username, $userId]);

    $_SESSION['name'] = $name;
    $_SESSION['username'] = $username;

    // 2. Handle File Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/assets/img/uploads/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadPath)) {
            $stmt = $db->prepare("UPDATE admin SET avatar = ? WHERE id = ?");
            $stmt->execute([$fileName, $userId]);
            $_SESSION['avatar'] = $fileName;
        } else {
            error_log("Failed to move uploaded file to: " . $uploadPath);
            die("Error: Could not move file. Check folder permissions for: " . $uploadDir);
        }
    }

    header("Location: index.php?page=settings&status=success");
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

    <link rel="stylesheet" href="/acesv2/assets/css/variables.css">
    <link rel="stylesheet" href="/acesv2/assets/css/reset.css">
    <link rel="stylesheet" href="/acesv2/assets/css/main.css">
    <link rel="stylesheet" href="/acesv2/assets/css/member_view.css">


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
        include __DIR__ . '/views/auth/login.php';
    } else {
        $user = [
            'name' => $_SESSION['name'] ?? 'Admin',
            'role' => ucfirst($_SESSION['role'] ?? 'Unassigned'),
            'initials' => strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1) . substr(strrchr($_SESSION['name'] ?? ' ', ' '), 1, 1))
        ];
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

        include __DIR__ . '/views/auth/partials/navbar.php';

        $role = $auth->getRole();
        $page = $_GET['page'] ?? 'dashboard';

        if ($role === 'admin') {
            switch ($page) {
                case 'settings':
                    include __DIR__ . '/views/auth/admin/settings.php';
                    break;
                case 'member_view':
                    include __DIR__ . '/views/auth/admin/member_view.php';
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
    <script type="module" src="/acesv2/assets/js/member_view.js"></script>
</body>

</html>