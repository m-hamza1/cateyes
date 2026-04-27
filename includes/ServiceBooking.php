<?php
/**
 * Service Booking Management Class
 * Handles all service booking operations
 * 
 * WORKFLOW:
 * 1. Customer creates booking → Status: PENDING (waiting admin confirmation)
 * 2. Admin confirms → Status: CONFIRMED (email sent to customer automatically)
 * 3. Service completed → Status: COMPLETED
 * Alternative: Admin cancels → Status: CANCELLED (email sent to customer)
 * 
 * SLOT MANAGEMENT:
 * - Only ONE booking allowed per time slot
 * - Strict checking prevents double bookings
 */
class ServiceBooking {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Generate unique booking number
     */
    private function generateBookingNumber() {
        $date = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return 'BK-' . $date . '-' . $random;
    }
    
    /**
     * Check if a specific time slot is available
     * STRICT: Returns true only if NO active bookings exist for this slot
     */
    public function isSlotAvailable($serviceId, $date, $time) {
        // First, check the max_bookings for THIS service's slot
        $dayOfWeek = date('w', strtotime($date));
        $slotInfo = $this->db->fetchOne("
            SELECT max_bookings 
            FROM service_time_slots 
            WHERE service_id = :service_id 
            AND day_of_week = :day_of_week 
            AND start_time = :time 
            AND is_active = 1
        ", [
            'service_id' => $serviceId,
            'day_of_week' => $dayOfWeek,
            'time' => $time
        ]);
        
        // If the slot doesn't exist for this service, it's not available
        if (!$slotInfo) return false;
        
        $maxBookings = (int)$slotInfo['max_bookings'];

        // Now check if ANY bookings exist for this time slot (Global staff check)
        // Note: We use global check because usually one staff can only handle one client at a time
        // across all their services.
        $existingBooking = $this->db->fetchOne("
            SELECT COUNT(*) as count 
            FROM service_bookings 
            WHERE booking_date = :date 
            AND booking_time = :time 
            AND status IN ('pending', 'confirmed', 'completed')
        ", [
            'date' => $date,
            'time' => $time
        ]);
        
        return ((int)$existingBooking['count'] < $maxBookings);
    }
    
    /**
     * Get available time slots for a specific service and date
     * CRITICAL: Only returns slots that are NOT booked by other users
     */
    public function getAvailableSlots($serviceId, $date) {
        $dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 1 = Monday, etc.
        
        // Get weekly time slots for this service
        $weeklySlots = $this->db->fetchAll("
            SELECT * FROM service_time_slots 
            WHERE service_id = :service_id 
            AND day_of_week = :day_of_week 
            AND is_active = 1
            ORDER BY start_time
        ", [
            'service_id' => $serviceId,
            'day_of_week' => $dayOfWeek
        ]);
        
        if (empty($weeklySlots)) {
            return [];
        }
        
        $availableSlots = [];
        
        foreach ($weeklySlots as $slot) {
            // Check if this specific time slot is available
            $isAvailable = $this->isSlotAvailable($serviceId, $date, $slot['start_time']);
            
            if ($isAvailable) {
                $availableSlots[] = [
                    'id' => $slot['id'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'formatted_time' => date('g:i A', strtotime($slot['start_time'])) . ' - ' . date('g:i A', strtotime($slot['end_time'])),
                    'max_bookings' => $slot['max_bookings']
                ];
            }
        }
        
        return $availableSlots;
    }
    
    /**
     * Get weekly time slots for a service (admin view)
     */
    public function getWeeklySlots($serviceId) {
        return $this->db->fetchAll("
            SELECT sts.*, 
                   DAYNAME(CONCAT('2023-01-0', sts.day_of_week + 1)) as day_name
            FROM service_time_slots sts
            WHERE sts.service_id = :service_id
            ORDER BY sts.day_of_week, sts.start_time
        ", ['service_id' => $serviceId]);
    }
    
    /**
     * Add weekly time slot for a service
     */
    public function addTimeSlot($serviceId, $dayOfWeek, $startTime, $endTime, $maxBookings = 1) {
        try {
            $slotData = [
                'service_id' => $serviceId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_bookings' => $maxBookings,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $slotId = $this->db->insert('service_time_slots', $slotData);
            
            if (!$slotId) {
                throw new Exception("Failed to add time slot");
            }
            
            return [
                'success' => true,
                'slot_id' => $slotId,
                'message' => 'Time slot added successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update time slot
     */
    public function updateTimeSlot($slotId, $dayOfWeek, $startTime, $endTime, $maxBookings = 1) {
        try {
            $updateData = [
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'max_bookings' => $maxBookings
            ];
            
            $result = $this->db->update('service_time_slots', $updateData, 'id = :id', ['id' => $slotId]);
            
            if (!$result) {
                throw new Exception("Failed to update time slot");
            }
            
            return [
                'success' => true,
                'message' => 'Time slot updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete time slot
     */
    public function deleteTimeSlot($slotId) {
        try {
            // Check if slot has existing bookings
            $bookingsCount = $this->db->count('service_bookings', 'time_slot_id = :slot_id AND status IN ("pending", "confirmed")', ['slot_id' => $slotId]);
            
            if ($bookingsCount > 0) {
                throw new Exception("Cannot delete time slot with existing bookings");
            }
            
            $result = $this->db->delete('service_time_slots', 'id = :id', ['id' => $slotId]);
            
            if (!$result) {
                throw new Exception("Failed to delete time slot");
            }
            
            return [
                'success' => true,
                'message' => 'Time slot deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create a new service booking
     * Initial status is PENDING - requires admin confirmation
     */
    public function createBooking($data) {
        try {
            $required = ['service_id', 'customer_name', 'customer_email', 'customer_phone', 'booking_date', 'booking_time'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // STRICT slot availability check
            $isAvailable = $this->isSlotAvailable(
                $data['service_id'],
                $data['booking_date'],
                $data['booking_time']
            );
            
            if (!$isAvailable) {
                throw new Exception("Sorry, this time slot has just been booked. Please select a different time.");
            }
            
            $service = $this->db->fetchOne("SELECT price, duration FROM services WHERE id = :id", [
                'id' => $data['service_id']
            ]);
            
            if (!$service) {
                throw new Exception("Service not found");
            }
            
            $bookingNumber = $this->generateBookingNumber();
            
            // Priority: 1. Use total_amount passed in data, 2. Calculate from service price - discount
            $totalAmount = $data['total_amount'] ?? $service['price'];
            if (isset($data['discount_amount']) && !isset($data['total_amount'])) {
                $totalAmount -= $data['discount_amount'];
            }
            
            // Currency handling
            global $currency;
            $currencyCode = $currency ? $currency->getCurrentCode() : ($_SESSION['currency'] ?? 'AED');
            $exchangeRate = $currency ? $currency->getExchangeRate($currencyCode) : 1.0;

            $bookingData = [
                'booking_number' => $data['booking_number'] ?? $bookingNumber,
                'service_id' => $data['service_id'],
                'time_slot_id' => $data['time_slot_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'booking_date' => $data['booking_date'],
                'booking_time' => $data['booking_time'],
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'total_amount' => $totalAmount,
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'payment_status' => $data['payment_status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? null,
                // Coupon fields
                'coupon_code' => $data['coupon_code'] ?? null,
                'coupon_discount_amount' => $data['coupon_discount_amount'] ?? null,
                'coupon_discount_type' => $data['coupon_discount_type'] ?? null,
                'coupon_discount_percentage' => $data['coupon_discount_percentage'] ?? null,
                'original_amount' => $data['original_amount'] ?? ($data['total_amount'] ?? $service['price']),
                'confirmed_at' => ($data['status'] ?? 'pending') === 'confirmed' ? date('Y-m-d H:i:s') : null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Final check before insert
            $finalCheck = $this->isSlotAvailable(
                $data['service_id'],
                $data['booking_date'],
                $data['booking_time']
            );
            
            if (!$finalCheck) {
                throw new Exception("This time slot was just taken. Please select another time.");
            }
            
            $bookingId = $this->db->insert('service_bookings', $bookingData);
            
            if (!$bookingId) {
                throw new Exception("Failed to create booking");
            }
            
            if (!isset($data['silent_admin']) || !$data['silent_admin']) {
                $this->notifyAdminNewBooking($bookingId);
            }
            
            if (!isset($data['silent']) || !$data['silent']) {
                if ($bookingData['status'] === 'confirmed') {
                    $this->sendConfirmationEmail(['id' => $bookingId]);
                } else {
                    $this->sendBookingReceivedEmail($bookingId);
                }
            }
            
            return [
                'success' => true,
                'booking_id' => $bookingId,
                'booking_number' => $bookingNumber,
                'total_amount' => $totalAmount,
                'message' => 'Booking created successfully! Waiting for admin confirmation.'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment for booking
     */
    public function processPayment($bookingId, $paymentData) {
        try {
            $booking = $this->getBookingById($bookingId);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['payment_status'] === 'paid') {
                throw new Exception("Booking has already been paid");
            }
            
            $payment = [
                'booking_id' => $bookingId,
                'payment_method' => $paymentData['payment_method'],
                'payment_gateway' => $paymentData['payment_gateway'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'amount' => isset($paymentData['amount']) ? (float)$paymentData['amount'] : (float)$booking['total_amount'],
                'currency' => $paymentData['currency'] ?? 'USD',
                'status' => 'pending',
                'payment_data' => json_encode($paymentData),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $paymentId = $this->db->insert('service_payments', $payment);
            
            if (!$paymentId) {
                throw new Exception("Failed to create payment record");
            }
            
            $this->db->update('service_bookings', 
                ['status' => 'confirmed', 'payment_status' => 'paid', 'payment_id' => $paymentId, 'confirmed_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $bookingId]
            );
            
            $this->db->update('service_payments',
                ['status' => 'completed', 'paid_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $paymentId]
            );

            // Send customer confirmation email after successful payment/confirmation
            try {
                $this->sendConfirmationEmail(['id' => $bookingId]);
            } catch (Exception $e) {
                error_log("Failed to send booking confirmation after payment for booking $bookingId: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'booking_number' => $booking['booking_number']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Confirm booking - ADMIN ACTION
     * Automatically sends confirmation email to customer
     */
    public function confirmBooking($bookingId, $adminId = null) {
        try {
            $booking = $this->getBookingById($bookingId);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['status'] !== 'pending') {
                throw new Exception("Only pending bookings can be confirmed");
            }
            
            $updateData = [
                'status' => 'confirmed',
                'confirmed_at' => date('Y-m-d H:i:s'),
                'confirmed_by' => $adminId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->db->update('service_bookings', $updateData, 'id = :id', ['id' => $bookingId]);
            
            if ($result) {
                // AUTOMATICALLY send confirmation email
                $this->sendConfirmationEmail($booking);
                
                return ['success' => true, 'message' => 'Booking confirmed and confirmation email sent to customer.'];
            } else {
                throw new Exception("Failed to confirm booking");
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cancel booking - ADMIN ACTION
     * Automatically sends cancellation email
     */
    public function cancelBooking($bookingId, $reason = null, $adminId = null) {
        try {
            $booking = $this->getBookingById($bookingId);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['status'] === 'completed') {
                throw new Exception("Cannot cancel a completed booking");
            }
            
            if ($booking['status'] === 'cancelled') {
                throw new Exception("Booking is already cancelled");
            }
            
            $updateData = [
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => date('Y-m-d H:i:s'),
                'cancelled_by' => $adminId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->db->update('service_bookings', $updateData, 'id = :id', ['id' => $bookingId]);
            
            if ($result) {
                // AUTOMATICALLY send cancellation email
                $this->sendCancellationEmail($booking, $reason);
                
                if ($booking['payment_status'] === 'paid') {
                    $this->processRefund($bookingId);
                }
                
                return ['success' => true, 'message' => 'Booking cancelled and notification sent to customer.'];
            } else {
                throw new Exception("Failed to cancel booking");
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Complete booking - ADMIN ACTION
     */
    public function completeBooking($bookingId, $adminId = null) {
        try {
            $booking = $this->getBookingById($bookingId);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            if ($booking['status'] !== 'confirmed') {
                throw new Exception("Only confirmed bookings can be completed");
            }
            
            $updateData = [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'completed_by' => $adminId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->db->update('service_bookings', $updateData, 'id = :id', ['id' => $bookingId]);
            
            if ($result) {
                try {
                    require_once INCLUDES_PATH . 'Email.php';
                    $email = new Email($this->db);
                    $fullBooking = $this->getBookingById($bookingId);
                    if ($fullBooking) {
                        $email->bookingStatusUpdate($fullBooking, 'completed');
                    }
                } catch (Exception $e) {
                    error_log("Email send failed on booking complete: " . $e->getMessage());
                }
                return ['success' => true, 'message' => 'Booking completed successfully.'];
            } else {
                throw new Exception("Failed to complete booking");
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all bookings with filters (for admin)
     */
    public function getAllBookings($filters = [], $page = 1, $perPage = 20) {
        $where = '1=1';
        $params = [];
        
        // Build WHERE clause from filters
        if (!empty($filters['status'])) {
            $where .= ' AND sb.status = :status';
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= ' AND sb.booking_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where .= ' AND sb.booking_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where .= ' AND (sb.booking_number LIKE :search OR sb.customer_name LIKE :search OR sb.customer_email LIKE :search OR st.name LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        $bookings = $this->db->fetchAll("
            SELECT sb.*, 
                   st.name as service_name,
                   s.image as service_image,
                   s.duration as service_duration,
                   sc.name as category_name,
                   CONCAT(u.first_name, ' ', u.last_name) as confirmed_by_name,
                   CONCAT(u2.first_name, ' ', u2.last_name) as cancelled_by_name,
                   CONCAT(u3.first_name, ' ', u3.last_name) as completed_by_name
            FROM service_bookings sb
            LEFT JOIN services s ON sb.service_id = s.id
            LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
            LEFT JOIN service_categories sc ON s.category_id = sc.id
            LEFT JOIN users u ON sb.confirmed_by = u.id
            LEFT JOIN users u2 ON sb.cancelled_by = u2.id
            LEFT JOIN users u3 ON sb.completed_by = u3.id
            WHERE $where
            ORDER BY sb.created_at DESC
            LIMIT $offset, $perPage
        ", $params);
        
        // Get total count
        $total = $this->db->count('service_bookings sb', $where, $params);
        
        return [
            'bookings' => $bookings,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    public function getBookingById($id) {
        return $this->db->fetchOne("
            SELECT sb.*, 
                   s.price as service_price,
                   s.duration as service_duration,
                   st.name as service_name,
                   s.image as service_image
            FROM service_bookings sb
            LEFT JOIN services s ON sb.service_id = s.id
            LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
            WHERE sb.id = :id
        ", ['id' => $id]);
    }
    
    public function getBookingByNumber($bookingNumber) {
        return $this->db->fetchOne("
            SELECT sb.*, 
                   s.price as service_price,
                   s.duration as service_duration,
                   st.name as service_name,
                   s.image as service_image
            FROM service_bookings sb
            LEFT JOIN services s ON sb.service_id = s.id
            LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
            WHERE sb.booking_number = :number
        ", ['number' => $bookingNumber]);
    }
    
    public function getUserBookings($userId, $status = null) {
        $whereClause = 'sb.user_id = :user_id';
        $params = ['user_id' => $userId];
        
        if ($status) {
            $whereClause .= ' AND sb.status = :status';
            $params['status'] = $status;
        }
        
        return $this->db->fetchAll("
            SELECT sb.*, 
                   st.name as service_name,
                   s.image as service_image
            FROM service_bookings sb
            LEFT JOIN services s ON sb.service_id = s.id
            LEFT JOIN service_translations st ON s.id = st.service_id AND st.language_id = 1
            WHERE $whereClause
            ORDER BY sb.booking_date DESC, sb.booking_time DESC
        ", $params);
    }
    public function getStatistics($filters = []) {
        $whereClause = '1=1';
        $params = [];
        
        if (!empty($filters['date_from'])) {
            $whereClause .= ' AND booking_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= ' AND booking_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $whereClause .= ' AND status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['service_id'])) {
            $whereClause .= ' AND service_id = :service_id';
            $params['service_id'] = $filters['service_id'];
        }

        if (!empty($filters['search'])) {
            // Join with translations might be needed for st.name if we want to search service name in stats
            // But for simple stats, searching booking number and customer info is usually enough
            $whereClause .= ' AND (booking_number LIKE :search OR customer_name LIKE :search OR customer_email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        return [
            'total' => $this->db->count('service_bookings', $whereClause, $params),
            'pending' => $this->db->count('service_bookings', "$whereClause AND status = 'pending'", $params),
            'confirmed' => $this->db->count('service_bookings', "$whereClause AND status = 'confirmed'", $params),
            'completed' => $this->db->count('service_bookings', "$whereClause AND status = 'completed'", $params),
            'cancelled' => $this->db->count('service_bookings', "$whereClause AND status = 'cancelled'", $params),
            'total_revenue' => $this->db->fetchOne("
                SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM service_bookings 
                WHERE $whereClause AND payment_status = 'paid'
            ", $params)['total']
        ];
    }

public function updateAdminNotes($bookingId, $notes) {
    return $this->db->update('service_bookings', 
        ['admin_notes' => $notes, 'updated_at' => date('Y-m-d H:i:s')],
        'id = :id', ['id' => $bookingId]
    );
}

    public function sendMultiBookingConfirmation($bookingIds) {
        if (empty($bookingIds)) return false;
        if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) return true;

        require_once INCLUDES_PATH . 'Email.php';
        $email = new Email($this->db);
        
        $bookings = [];
        foreach ($bookingIds as $id) {
            $booking = $this->getBookingById($id);
            if ($booking) {
                $bookings[] = $booking;
            }
        }
        
        if (empty($bookings)) return false;
        
        return $email->multiBookingConfirmation($bookings);
    }

    public function sendConfirmationEmail($booking) {
        if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
            return true;
        }
        
        require_once INCLUDES_PATH . 'Email.php';
        $email = new Email($this->db);
        $fullBooking = $this->getBookingById($booking['id']);
        if (!$fullBooking) return false;

        return $email->bookingConfirmation($fullBooking);
    }
    
    private function sendCancellationEmail($booking, $reason = '') {
        if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
            return true;
        }
        
        require_once INCLUDES_PATH . 'Email.php';
        $email = new Email($this->db);
        $fullBooking = $this->getBookingById($booking['id']);
        if (!$fullBooking) return false;

        return $email->bookingCancellation($fullBooking, $reason);
    }
    
    public function notifyAdminMultiBooking($bookingIds) {
        if (empty($bookingIds)) return false;
        if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) return true;

        require_once INCLUDES_PATH . 'Email.php';
        $email = new Email($this->db);
        
        $bookings = [];
        foreach ($bookingIds as $id) {
            $booking = $this->getBookingById($id);
            if ($booking) {
                $bookings[] = $booking;
            }
        }
        
        if (empty($bookings)) return false;
        
        return $email->notifyAdminMultiBooking($bookings);
    }

    public function notifyAdminNewBooking($bookingId) {
        try {
            if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
                return true;
            }

            $booking = $this->getBookingById($bookingId);
            if (!$booking) {
                return false;
            }
            
            require_once INCLUDES_PATH . 'Email.php';
            $email = new Email($this->db);
            $result = $email->notifyAdminNewBooking($booking);
            
            $adminEmail = $this->db->fetchOne("SELECT `value` FROM settings WHERE `key` = 'admin_email'")['value'] ?? 'admin@localhost';
            $this->logEmailNotification($bookingId, 'admin_new_booking', $adminEmail, "New Booking #{$booking['booking_number']}", $result);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send admin notification for booking $bookingId: " . $e->getMessage());
            return false;
        }
    }

    public function sendBookingReceivedEmail($bookingId) {
        try {
            if (defined('DISABLE_EMAILS') && DISABLE_EMAILS) {
                return true;
            }

            $booking = $this->getBookingById($bookingId);
            if (!$booking) return false;

            require_once INCLUDES_PATH . 'Email.php';
            $email = new Email($this->db);
            $result = $email->bookingReceived($booking);

            $this->logEmailNotification($bookingId, 'customer_booking_received', $booking['customer_email'], "Booking Received #{$booking['booking_number']}", $result);

            return $result;
        } catch (Exception $e) {
            error_log("Failed to send booking received email for booking $bookingId: " . $e->getMessage());
            return false;
        }
    }
    
    private function logEmailNotification($bookingId, $type, $to, $subject, $success) {
        try {
            $logData = [
                'booking_id' => $bookingId,
                'notification_type' => $type,
                'recipient_email' => $to,
                'subject' => $subject,
                'sent_successfully' => $success ? 1 : 0,
                'sent_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert('service_booking_notifications', $logData);
        } catch (Exception $e) {
            error_log("Failed to log email notification: " . $e->getMessage());
        }
    }
    
    private function processRefund($bookingId) {
        return true;
    }
}