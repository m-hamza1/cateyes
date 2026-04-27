<?php
/**
 * User Class
 * Handles user authentication and management
 */

class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Register new user
     */
    public function register($data) {
        // Validate email
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = hashPassword($data['password']);
        
        // Generate verification token
        $verificationToken = generateRandomString();
        
        // Insert user
        $userId = $this->db->insert('users', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'phone' => $data['phone'] ?? null,
            'role' => 'customer',
            'is_active' => 1,
            'email_verified' => 0,
            'verification_token' => $verificationToken
        ]);
        
        if ($userId) {
            // TODO: Send verification email
            return ['success' => true, 'user_id' => $userId];
        }
        
        return ['success' => false, 'message' => 'Registration failed'];
    }
    
    /**
     * Login user
     */
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = :email AND is_active = 1",
            ['email' => $email]
        );
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Update last login
            $this->db->update('users', 
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );
            
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return true;
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        return $this->db->exists('users', 'email = :email', ['email' => $email]);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        return $this->db->update('users', $data, 'id = :id', ['id' => $userId]);
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getById($userId);
        
        if (!verifyPassword($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        $hashedPassword = hashPassword($newPassword);
        $success = $this->db->update('users', 
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $userId]
        );
        
        return ['success' => $success, 'message' => $success ? 'Password changed successfully' : 'Failed to change password'];
    }
}
