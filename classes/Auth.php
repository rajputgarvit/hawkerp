<?php
require_once 'Database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login($username, $password) {
        $user = $this->db->fetchOne(
            "SELECT u.*, GROUP_CONCAT(r.name) as roles 
             FROM users u 
             LEFT JOIN user_roles ur ON u.id = ur.user_id 
             LEFT JOIN roles r ON ur.role_id = r.id 
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1 
             GROUP BY u.id",
            [$username, $username]
        );
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$user['id']]
            );
            
            // Set session
            $this->setSession($user);
            
            // Log audit
            $this->logAudit($user['id'], 'login', 'users', $user['id']);
            
            return true;
        }
        
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logAudit($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email'],
                'roles' => $_SESSION['roles'] ?? []
            ];
        }
        return null;
    }
    
    public function hasRole($role) {
        if (!$this->isLoggedIn()) return false;
        $roles = explode(',', $_SESSION['roles'] ?? '');
        return in_array($role, $roles) || in_array('Super Admin', $roles);
    }
    
    public function hasPermission($module, $action) {
        if (!$this->isLoggedIn()) return false;
        
        // Super Admin has all permissions
        if ($this->hasRole('Super Admin')) return true;
        
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count 
             FROM user_roles ur
             JOIN role_permissions rp ON ur.role_id = rp.role_id
             JOIN permissions p ON rp.permission_id = p.id
             WHERE ur.user_id = ? AND p.module = ? AND p.action = ?",
            [$_SESSION['user_id'], $module, $action]
        );
        
        return $result['count'] > 0;
    }
    
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['roles'] = $user['roles'];
    }
    
    public function register($data) {
        // Check if username or email exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$data['username'], $data['email']]
        );
        
        if ($existing) {
            return ['success' => false, 'message' => 'Username or email already exists'];
        }
        
        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        // Insert user
        $userId = $this->db->insert('users', $data);
        
        // Assign default role (Employee)
        $this->db->insert('user_roles', [
            'user_id' => $userId,
            'role_id' => 4 // Employee role
        ]);
        
        return ['success' => true, 'user_id' => $userId];
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->db->fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
        
        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        $this->db->update('users', 
            ['password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)],
            'id = ?',
            [$userId]
        );
        
        $this->logAudit($userId, 'password_change', 'users', $userId);
        
        return ['success' => true, 'message' => 'Password changed successfully'];
    }
    
    private function logAudit($userId, $action, $table = null, $recordId = null, $oldValues = null, $newValues = null) {
        $this->db->insert('audit_logs', [
            'user_id' => $userId,
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }
}
