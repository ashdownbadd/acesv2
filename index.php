<?php
session_start();

require_once 'core/Auth.php';
require_once 'core/db.php';

$auth = new Auth();
$action = $_POST['action'] ?? $_GET['action'] ?? null;

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
    $userId = $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

    if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? $_SESSION['name'];
        $username = $_POST['username'] ?? $_SESSION['username'];

        $stmt = $db->prepare("UPDATE admin SET name = ?, username = ? WHERE id = ?");
        $stmt->execute([$name, $username, $userId]);

        $_SESSION['name'] = $name;
        $_SESSION['username'] = $username;

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/assets/img/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $fileName = 'user_' . $userId . '_' . time() . '.' . $fileExt;

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $fileName)) {
                $stmt = $db->prepare("UPDATE admin SET avatar = ? WHERE id = ?");
                $stmt->execute([$fileName, $userId]);
                $_SESSION['avatar'] = $fileName;
            }
        }
        header("Location: index.php?page=settings&status=success");
        exit();
    }

    if ($action === 'delete_member' && $isAdmin) {
        $id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: index.php?page=dashboard&status=deleted");
        exit();
    }

    if ($action === 'save_member' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'] ?? null;
        $data = [
            $_POST['first_name'],
            $_POST['middle_name'],
            $_POST['last_name'],
            $_POST['membership_type'],
            $_POST['status'],
            $_POST['balance'],
            $id
        ];

        if ($id) {
            $sql = "UPDATE members SET first_name=?, middle_name=?, last_name=?, membership_type=?, status=?, balance=? WHERE id=?";
        } else {
            array_pop($data);
            $sql = "INSERT INTO members (first_name, middle_name, last_name, membership_type, status, balance) VALUES (?, ?, ?, ?, ?, ?)";
        }

        $db->prepare($sql)->execute($data);
        header("Location: index.php?page=dashboard&status=saved");
        exit();
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
        $user = [
            'name' => $_SESSION['name'] ?? 'Admin',
            'role' => ucfirst($_SESSION['role'] ?? 'Unassigned'),
            'initials' => strtoupper(substr($_SESSION['name'] ?? 'A', 0, 1) . substr(strrchr($_SESSION['name'] ?? ' ', ' '), 1, 1))
        ];

        include __DIR__ . '/views/auth/partials/navbar.php';

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
    ?>
    <script type="module" src="/acesv2/assets/js/theme.js"></script>
    <script type="module" src="/acesv2/assets/js/app.js"></script>
    <script type="module" src="/acesv2/assets/js/member.js"></script>
</body>

</html>