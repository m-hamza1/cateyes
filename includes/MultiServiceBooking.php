<?php

class MultiServiceBooking {
    private $db;
    private $sessionKey = 'multi_service_booking_cart';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Add a service to the booking cart
     */
    public function addServiceToCart($serviceId, $date, $time, $timeSlotId, $quantity = 1) {
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
        
        $cartItem = [
            'id' => uniqid('svc_', true),
            'service_id' => $serviceId,
            'date' => $date,
            'time' => $time,
            'time_slot_id' => $timeSlotId,
            'quantity' => $quantity,
            'added_at' => date('Y-m-d H:i:s')
        ];
        
        $_SESSION[$this->sessionKey][] = $cartItem;
        return $cartItem['id'];
    }
    
    /**
     * Remove a service from the booking cart
     */
    public function removeServiceFromCart($cartItemId) {
        if (isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = array_filter($_SESSION[$this->sessionKey], function($item) use ($cartItemId) {
                return $item['id'] !== $cartItemId;
            });
            return true;
        }
        return false;
    }
    
    /**
     * Get all services in the booking cart
     */
    public function getCartServices() {
        return $_SESSION[$this->sessionKey] ?? [];
    }
    
    /**
     * Get detailed cart items with service information
     */
    public function getDetailedCartItems() {
        $cartItems = $this->getCartServices();
        $detailedItems = [];
        
        foreach ($cartItems as $item) {
            $service = $this->db->fetchOne("
                SELECT s.*, st.name, st.description
                FROM services s
                LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = :lang_id
                WHERE s.id = :service_id
            ", [
                'service_id' => $item['service_id'],
                'lang_id' => 1 // TODO: Get current language
            ]);
            
            if ($service) {
                $item['service'] = $service;
                $item['formatted_date'] = date('F j, Y', strtotime($item['date']));
                $item['formatted_time'] = date('g:i A', strtotime($item['time']));
                $item['total_price'] = $service['price'] * $item['quantity'];
                $detailedItems[] = $item;
            }
        }
        
        return $detailedItems;
    }
    
    /**
     * Get cart total amount
     */
    public function getCartTotal() {
        $items = $this->getDetailedCartItems();
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['total_price'];
        }
        
        return $total;
    }
    
    /**
     * Get cart count
     */
    public function getCartCount() {
        return count($this->getCartServices());
    }
    
    /**
     * Clear the booking cart
     */
    public function clearCart() {
        unset($_SESSION[$this->sessionKey]);
    }
    
    /**
     * Create multiple bookings from cart
     */
    public function createBookingsFromCart($customerData) {
        $cartItems = $this->getCartServices();
        $bookings = [];
        $errors = [];
        
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'Cart is empty'];
        }
        
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            foreach ($cartItems as $item) {
                $bookingData = [
                    'service_id' => $item['service_id'],
                    'customer_name' => $customerData['name'],
                    'customer_email' => $customerData['email'],
                    'customer_phone' => $customerData['phone'] ?? '',
                    'booking_date' => $item['date'],
                    'booking_time' => $item['time'],
                    'time_slot_id' => $item['time_slot_id'],
                    'total_amount' => $this->getServicePrice($item['service_id']) * $item['quantity'],
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'booking_number' => $this->generateBookingNumber(),
                    'notes' => $customerData['notes'] ?? '',
                    'quantity' => $item['quantity']
                ];
                
                $bookingId = $this->db->insert('service_bookings', $bookingData);
                
                if ($bookingId) {
                    $bookingData['id'] = $bookingId;
                    $bookings[] = $bookingData;
                    
                    // Mark time slot as booked
                    $this->markTimeSlotAsBooked($item['time_slot_id']);
                } else {
                    $errors[] = "Failed to create booking for service ID: {$item['service_id']}";
                }
            }
            
            if (empty($errors)) {
                $this->db->commit();
                $this->clearCart();
                return ['success' => true, 'bookings' => $bookings];
            } else {
                $this->db->rollback();
                return ['success' => false, 'error' => implode(', ', $errors)];
            }
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get service price
     */
    private function getServicePrice($serviceId) {
        $service = $this->db->fetchOne("SELECT price FROM services WHERE id = :id", ['id' => $serviceId]);
        return $service ? $service['price'] : 0;
    }
    
    /**
     * Generate unique booking number
     */
    private function generateBookingNumber() {
        do {
            $bookingNumber = 'BK' . date('Y') . strtoupper(substr(md5(uniqid()), 0, 8));
            $existing = $this->db->fetchOne("SELECT id FROM service_bookings WHERE booking_number = :booking_number", ['booking_number' => $bookingNumber]);
        } while ($existing);
        
        return $bookingNumber;
    }
    
    /**
     * Mark time slot as booked
     */
    private function markTimeSlotAsBooked($timeSlotId) {
        $this->db->update('time_slots', ['status' => 'booked'], 'id = :id', ['id' => $timeSlotId]);
    }
    
    /**
     * Check if service can be added (no time conflicts)
     */
    public function canAddService($serviceId, $date, $time) {
        $cartItems = $this->getCartServices();
        
        foreach ($cartItems as $item) {
            // Check if same service at same time already exists
            if ($item['service_id'] == $serviceId && $item['date'] == $date && $item['time'] == $time) {
                return false;
            }
        }
        
        return true;
    }
}
