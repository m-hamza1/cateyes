<?php
/**
 * Customer Management Class
 * Handles all customer-related operations
 */
class Customer {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all customers with pagination and filtering
     */
    public function getAllCustomers($page = 1, $perPage = 20, $search = '', $status = '', $group = '') {
        $offset = ($page - 1) * $perPage;
        $where = ["u.role = 'customer'"];
        $params = [];
        
        // Search functionality
        if (!empty($search)) {
            $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        // Status filter
        if ($status !== '') {
            $where[] = "u.is_active = :status";
            $params['status'] = $status;
        }
        
        // Group filter
        if ($group !== '') {
            $where[] = "u.customer_group_id = :group";
            $params['group'] = $group;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get customers
        $sql = "SELECT u.*, cg.name as customer_group_name, cg.discount_percentage,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                MAX(o.created_at) as last_order_date
                FROM users u
                LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE $whereClause
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT $perPage OFFSET $offset";
        
        $customers = $this->db->fetchAll($sql, $params);
        
        // Get total count
        $countSql = "SELECT COUNT(DISTINCT u.id) as total
                    FROM users u
                    LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id
                    WHERE $whereClause";
        
        $totalResult = $this->db->fetchOne($countSql, $params);
        $total = $totalResult['total'] ?? 0;
        
        return [
            'customers' => $customers,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get customer by ID with full details
     */
    public function getCustomerById($id) {
        $sql = "SELECT u.*, cg.name as customer_group_name, cg.discount_percentage,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                MAX(o.created_at) as last_order_date,
                (SELECT COUNT(*) FROM activity_log al WHERE al.user_id = u.id) as activity_count
                FROM users u
                LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE u.id = :id AND u.role = 'customer'
                GROUP BY u.id";
        
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Get customer addresses
     */
    public function getCustomerAddresses($customerId) {
        return $this->db->fetchAll(
            "SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, id ASC",
            ['user_id' => $customerId]
        );
    }
    
    /**
     * Get customer orders
     */
    public function getCustomerOrders($customerId, $limit = 10, $offset = 0) {
        $sql = "SELECT o.*, 
                COUNT(oi.id) as item_count,
                (SELECT COUNT(*) FROM order_status_history osh WHERE osh.order_id = o.id) as status_updates
                FROM orders o
                LEFT JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = :user_id
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT $limit OFFSET $offset";
        
        return $this->db->fetchAll($sql, ['user_id' => $customerId]);
    }
    
    /**
     * Get customer activity log
     */
    public function getCustomerActivity($customerId, $limit = 20) {
        return $this->db->fetchAll(
            "SELECT * FROM activity_log 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT $limit",
            ['user_id' => $customerId]
        );
    }
    
    /**
     * Create new customer
     */
    public function createCustomer($data) {
        try {
            // Validate required fields
            $required = ['first_name', 'last_name', 'email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field $field is required");
                }
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                throw new Exception("Email already exists");
            }
            
            // Prepare customer data
            $customerData = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'customer_group_id' => $data['customer_group_id'] ?? null,
                'role' => 'customer',
                'is_active' => $data['is_active'] ?? 1,
                'email_verified' => $data['email_verified'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add password if provided
            if (!empty($data['password'])) {
                $customerData['password'] = hashPassword($data['password']);
            }
            
            // Insert customer
            $customerId = $this->db->insert('users', $customerData);
            
            // Add address if provided
            if (!empty($data['address'])) {
                $this->addCustomerAddress($customerId, $data['address']);
            }
            
            // Log activity
            $this->logActivity($customerId, 'customer_created', 'New customer account created');
            
            return $customerId;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Update customer
     */
    public function updateCustomer($id, $data) {
        try {
            $customer = $this->getCustomerById($id);
            if (!$customer) {
                throw new Exception("Customer not found");
            }
            
            // Check if email is being changed and if it already exists
            if (isset($data['email']) && $data['email'] !== $customer['email']) {
                if ($this->emailExists($data['email'])) {
                    throw new Exception("Email already exists");
                }
            }
            
            // Prepare update data
            $updateData = [];
            $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'company', 
                            'customer_group_id', 'is_active', 'email_verified'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // Update password if provided
            if (!empty($data['password'])) {
                $updateData['password'] = hashPassword($data['password']);
            }
            
            if (!empty($updateData)) {
                $this->db->update('users', $updateData, 'id = :id', ['id' => $id]);
                $this->logActivity($id, 'customer_updated', 'Customer profile updated');
            }
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Add customer address
     */
    public function addCustomerAddress($customerId, $addressData) {
        $address = [
            'user_id' => $customerId,
            'type' => $addressData['type'] ?? 'billing',
            'address_line_1' => $addressData['address_line_1'],
            'address_line_2' => $addressData['address_line_2'] ?? null,
            'city' => $addressData['city'],
            'state' => $addressData['state'] ?? null,
            'postal_code' => $addressData['postal_code'] ?? null,
            'country' => $addressData['country'] ?? null,
            'is_default' => $addressData['is_default'] ?? 0
        ];
        
        // If this is set as default, unset other default addresses
        if ($address['is_default']) {
            $this->db->update('user_addresses', 
                ['is_default' => 0], 
                'user_id = :user_id AND type = :type', 
                ['user_id' => $customerId, 'type' => $address['type']]
            );
        }
        
        return $this->db->insert('user_addresses', $address);
    }
    
    /**
     * Delete customer (soft delete)
     */
    public function deleteCustomer($id) {
        try {
            $customer = $this->getCustomerById($id);
            if (!$customer) {
                throw new Exception("Customer not found");
            }
            
            // Check if customer has orders
            $orderCount = $this->db->count('orders', 'user_id = :user_id', ['user_id' => $id]);
            if ($orderCount > 0) {
                // Soft delete - deactivate instead
                $this->db->update('users', 
                    ['is_active' => 0, 'email_verified' => 0], 
                    'id = :id', 
                    ['id' => $id]
                );
                $this->logActivity($id, 'customer_deactivated', 'Customer account deactivated');
            } else {
                // Hard delete if no orders
                $this->db->delete('user_addresses', 'user_id = :user_id', ['user_id' => $id]);
                $this->db->delete('users', 'id = :id', ['id' => $id]);
                $this->logActivity($id, 'customer_deleted', 'Customer account deleted');
            }
            
            return true;
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Get customer groups
     */
    public function getCustomerGroups() {
        return $this->db->fetchAll("SELECT * FROM customer_groups ORDER BY name ASC");
    }
    
    /**
     * Get customer statistics
     */
    public function getCustomerStats() {
        $stats = [];
        
        // Total customers
        $stats['total'] = $this->db->count('users', "role = 'customer'");
        
        // Active customers
        $stats['active'] = $this->db->count('users', "role = 'customer' AND is_active = 1");
        
        // New customers this month
        $stats['new_this_month'] = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users 
            WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURRENT_DATE) 
            AND YEAR(created_at) = YEAR(CURRENT_DATE)"
        )['count'] ?? 0;
        
        // Customers with orders
        $stats['with_orders'] = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_id) as count FROM orders"
        )['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Export customers to CSV
     */
    public function exportToCSV($search = '', $status = '', $group = '') {
        $where = ["u.role = 'customer'"];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        if ($status !== '') {
            $where[] = "u.is_active = :status";
            $params['status'] = $status;
        }
        
        if ($group !== '') {
            $where[] = "u.customer_group_id = :group";
            $params['group'] = $group;
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.company,
                u.is_active, u.created_at, cg.name as customer_group,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent
                FROM users u
                LEFT JOIN customer_groups cg ON u.customer_group_id = cg.id
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE $whereClause
                GROUP BY u.id
                ORDER BY u.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Check if email exists
     */
    private function emailExists($email) {
        return $this->db->exists('users', 'email = :email', ['email' => $email]);
    }
    
    /**
     * Log customer activity
     */
    private function logActivity($userId, $action, $description = '') {
        $this->db->insert('activity_log', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
