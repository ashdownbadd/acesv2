<?php
require_once __DIR__ . '/db.php';

class Auth
{
    private $db;

    public function __construct()
    {
        $database = new DB();
        $this->db = $database->connect();
    }

    /**
     * Set session variables based on user type
     */
    private function updateSession(array $user, string $type): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($type === 'admin') {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['name']      = $user['name'] ?? $user['username'];
            $_SESSION['role']      = 'admin';
            $_SESSION['avatar']    = $user['avatar'] ?? '';
        } else {
            // Member Logic
            $_SESSION['member_id'] = $user['id'];
            $_SESSION['username']  = $user['username'];
            // Combine names for members
            $_SESSION['name']      = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
            $_SESSION['role']      = 'Member';
            $_SESSION['avatar']    = $user['avatar'] ?? '';
        }
    }

    public function login(string $username, string $password): bool
    {
        // 1. Try Admin Table
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            $this->updateSession($admin, 'admin');
            return true;
        }

        // 2. Try Members Table
        $stmt = $this->db->prepare("SELECT * FROM members WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($member && password_verify($password, $member['password'])) {
            $this->updateSession($member, 'member');
            return true;
        }

        return false;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']) || isset($_SESSION['member_id']);
    }

    public function getRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_unset();
        session_destroy();
    }
}
