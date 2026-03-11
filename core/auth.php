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

    private function updateSession(array $user): void
    {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name']     = $user['name'];
        $_SESSION['role']     = 'admin';
        $_SESSION['avatar']   = $user['avatar'];
    }

    public function login(string $username, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE username = :u LIMIT 1");
        $stmt->execute(['u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $this->updateSession($user);
            return true;
        }
        return false;
    }

    public function refreshSession(int $userId): void
    {
        $stmt = $this->db->prepare("SELECT * FROM admin WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->updateSession($user);
        }
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function getRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }
}
