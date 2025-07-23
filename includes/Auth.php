<?php
/**
 * Authentication class
 */
class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($email, $password, $role) {
        $user = $this->db->selectOne(
            "SELECT * FROM users WHERE email = ? AND role = ?",
            [$email, $role]
        );
        
        if (!$user) {
            return false;
        }
        
        if (password_verify($password, $user['password'])) {
            $this->setSession($user);
            return true;
        }
        
        return false;
    }
    
    public function register($userData) {
        // Hash password
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            $user = $this->db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            $this->setSession($user);
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        
        // Redirect to login page
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    
    private function setSession($user) {
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        if ($user['role'] === 'technician') {
            $_SESSION['user_zone'] = $user['zone'];
        }
    }
    
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === 'admin';
    }
    
    public function isTechnician() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_role'] === 'technician';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
    
    public function requireTechnician() {
        if (!$this->isTechnician()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
}
