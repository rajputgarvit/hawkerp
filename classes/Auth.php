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
            // If company_id is missing in session (stale session), refresh it from DB
            if (!isset($_SESSION['company_id'])) {
                $user = $this->db->fetchOne("SELECT company_id FROM users WHERE id = ?", [$_SESSION['user_id']]);
                if ($user) {
                    $_SESSION['company_id'] = $user['company_id'];
                }
            }

            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'email' => $_SESSION['email'],
                'company_id' => $_SESSION['company_id'] ?? null,
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

    public function isAdmin() {
        return $this->hasRole('Super Admin');
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

    public function hasModuleAccess($module) {
        if (!$this->isLoggedIn()) return false;

        // Super Admin has access to everything
        if ($this->hasRole('Super Admin')) return true;

        // Company Admin (Role ID 2) has access to everything
        // We can check role name 'Admin' or ID 2. Let's check role name for clarity if possible, 
        // but roles are stored as comma separated string in session or we can query.
        // For now, let's assume if they have 'Admin' role they see everything.
        if ($this->hasRole('Admin')) return true;

        // Check specific module access
        $access = $this->db->fetchOne(
            "SELECT id FROM user_module_access WHERE user_id = ? AND module = ?",
            [$_SESSION['user_id'], $module]
        );

        return $access ? true : false;
    }
    
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['company_id'] = $user['company_id'];
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
        
        // Create new company (using company_settings table)
        $companyId = $this->db->insert('company_settings', [
            'company_name' => $data['company_name'],
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if (!$companyId) {
            return ['success' => false, 'message' => 'Failed to create company'];
        }

        // Hash password
        $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        unset($data['password']);
        
        // Add company_id to user data
        $data['company_id'] = $companyId;
        
        // Insert user
        $userId = $this->db->insert('users', $data);
        
        // Assign default role (Admin for the new company creator)
        // Assuming role_id 2 is Admin (need to verify, but usually 1=Super Admin, 2=Admin, 4=Employee)
        // Let's use 2 (Admin) for the company creator
        $this->db->insert('user_roles', [
            'user_id' => $userId,
            'role_id' => 2 // Admin role
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
    
    /**
     * Send email verification
     */
    public function sendVerificationEmail($userId, $email) {
        // Generate verification token
        $token = bin2hex(random_bytes(32));
        
        // Update user with token
        $this->db->update('users',
            ['email_verification_token' => $token],
            'id = ?',
            [$userId]
        );
        
        // Send email (simplified version - should use proper email service)
        $verificationLink = BASE_URL . "verify-email.php?token=" . $token;
        $subject = "Verify your HawkERP account";
        $message = "Click the link to verify your email: " . $verificationLink;
        
        // In production, use proper email service
        // For now, just return the link
        return ['success' => true, 'link' => $verificationLink];
    }
    
    /**
     * Verify email token
     */
    public function verifyEmail($token) {
        $user = $this->db->fetchOne(
            "SELECT id FROM users WHERE email_verification_token = ?",
            [$token]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid verification token'];
        }
        
        $this->db->update('users',
            [
                'email_verified' => 1,
                'email_verification_token' => null
            ],
            'id = ?',
            [$user['id']]
        );
        
        return ['success' => true, 'user_id' => $user['id']];
    }
    
    /**
     * Check if user has active subscription
     */
    public function checkSubscriptionAccess() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        require_once 'Subscription.php';
        $subscription = new Subscription();
        return $subscription->hasActiveSubscription($_SESSION['user_id']);
    }
    /**
     * Impersonate a user (Admin only)
     */
    public function impersonateUser($userId) {
        if (!$this->isAdmin()) {
            return false;
        }

        $targetUser = $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$targetUser) {
            return false;
        }

        // Save original admin session
        $_SESSION['admin_user_id'] = $_SESSION['user_id'];
        $_SESSION['admin_username'] = $_SESSION['username'];
        $_SESSION['admin_full_name'] = $_SESSION['full_name'];
        $_SESSION['admin_roles'] = $_SESSION['roles'];
        $_SESSION['is_impersonating'] = true;

        // Log audit
        $this->logAudit($_SESSION['user_id'], 'impersonate_start', 'users', $userId);

        // Set session to target user
        $this->setSession($targetUser);
        
        // Fetch roles for target user
        $roles = $this->db->fetchAll(
            "SELECT r.name FROM roles r 
             JOIN user_roles ur ON r.id = ur.role_id 
             WHERE ur.user_id = ?", 
            [$userId]
        );
        $_SESSION['roles'] = implode(',', array_column($roles, 'name'));

        return true;
    }

    /**
     * Stop impersonation and return to admin
     */
    public function stopImpersonation() {
        if (!isset($_SESSION['is_impersonating']) || !$_SESSION['is_impersonating']) {
            return false;
        }

        $adminId = $_SESSION['admin_user_id'];

        // Log audit
        $this->logAudit($adminId, 'impersonate_end', 'users', $_SESSION['user_id']);

        // Restore admin session
        $_SESSION['user_id'] = $_SESSION['admin_user_id'];
        $_SESSION['username'] = $_SESSION['admin_username'];
        $_SESSION['full_name'] = $_SESSION['admin_full_name'];
        $_SESSION['roles'] = $_SESSION['admin_roles'];
        
        // Clear impersonation flags
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_full_name']);
        unset($_SESSION['admin_roles']);
        unset($_SESSION['is_impersonating']);
        unset($_SESSION['company_id']); // Will be reset on next admin action or not needed for global admin

        return true;
    }

    public function isImpersonating() {
        return isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'];
    }
}

