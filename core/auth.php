<?php
// Use __DIR__ to ensure it finds db.php in the same 'core' folder
require_once __DIR__ . '/db.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new DB();
        $this->db = $database->connect();
    }

    public function login($username, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // This is the critical check
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = 'admin';
            $_SESSION['avatar'] = $user['avatar'];
            return true;
        }
        return false;
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public function getRole()
    {
        return $_SESSION['role'] ?? null;
    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }
}
