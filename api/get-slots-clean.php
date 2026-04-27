<?php
/**
 * API Endpoint: Get Available Time Slots
 * CLEAN VERSION - Returns available time slots for a service for a given week
 * 
 * Usage: get-slots-clean.php?service_id=13&week_start=2026-01-10
 */

// Disable all error output (errors go to log only)
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering to catch any unwanted output
ob_start();

define('API_JSON_RESPONSE', true);

try {
    // Use main config so DB settings match the rest of the site (vertexvaluestore, local, etc.)
    require_once dirname(__DIR__) . '/config.php';
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'message' => 'Unable to load time slots. Please try again later.'
    ]);
    exit;
}

ob_end_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

try {
    // $db is already initialized by config.php
    
    // Get and validate parameters
    $service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;
    $week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d');

    // Validate service ID
    if ($service_id <= 0) {
        echo json_encode(['error' => 'Invalid service ID']);
        exit;
    }

    // Validate date format
    $date_obj = DateTime::createFromFormat('Y-m-d', $week_start);
    if (!$date_obj) {
        echo json_encode(['error' => 'Invalid date format. Use Y-m-d']);
        exit;
    }

    // Get service time slots from database
    $time_slots = $db->fetchAll("
        SELECT 
            id,
            day_of_week,
            start_time,
            end_time,
            max_bookings,
            is_active
        FROM service_time_slots 
        WHERE service_id = :service_id 
        AND is_active = 1
        ORDER BY day_of_week, start_time
    ", ['service_id' => $service_id]);

    // Ensure we have an array
    if (!is_array($time_slots)) {
        $time_slots = [];
    }

    // Calculate week dates (Sunday to Saturday)
    $week_start_date = new DateTime($week_start);
    // Adjust to Sunday of the week
    $week_start_date->modify('sunday this week');

    // Calculate week end date (Saturday)
    $week_end_date = clone $week_start_date;
    $week_end_date->modify('+6 days');

    // Get existing bookings for ALL services in this week (to prevent staff double-booking)
    $existing_bookings = $db->fetchAll("
        SELECT 
            booking_date,
            booking_time,
            COUNT(*) as booking_count
        FROM service_bookings
        WHERE booking_date BETWEEN :start_date AND :end_date
        AND status IN ('pending', 'confirmed', 'completed')
        GROUP BY booking_date, booking_time
    ", [
        'start_date' => $week_start_date->format('Y-m-d'),
        'end_date' => $week_end_date->format('Y-m-d')
    ]);

    // Create a map of bookings: date_time => count
    $booking_map = [];
    foreach ($existing_bookings as $booking) {
        $db_time = substr($booking['booking_time'], 0, 5);
        $key = $booking['booking_date'] . '_' . $db_time;
        $booking_map[$key] = (int)$booking['booking_count'];
    }
    
    error_log("DEBUG: Final booking map: " . json_encode($booking_map));

    // Build response structure
    $response = [
        'success' => true,
        'service_id' => $service_id,
        'week_start' => $week_start_date->format('Y-m-d'),
        'week_end' => $week_end_date->format('Y-m-d'),
        'total_slots' => count($time_slots),
        'days' => []
    ];

    // Build 7 days (Sunday to Saturday)
    for ($i = 0; $i < 7; $i++) {
        $current_date = clone $week_start_date;
        $current_date->modify("+{$i} days");
        
        $day_of_week = (int)$current_date->format('w'); // 0=Sunday, 6=Saturday
        $date_string = $current_date->format('Y-m-d');
        $today = new DateTime('today');
        $is_past = $current_date < $today;
        
        // Find time slots for this day of week
        $day_slots = [];
        foreach ($time_slots as $slot) {
            if ((int)$slot['day_of_week'] === $day_of_week) {
                $day_slots[] = $slot;
            }
        }
        
        // Process each time slot for this day
        $slots = [];
        foreach ($day_slots as $slot) {
            $start_time = substr($slot['start_time'], 0, 5); // HH:MM format
            $end_time = substr($slot['end_time'], 0, 5);
            $max_bookings = (int)$slot['max_bookings'];
            
            // Check how many bookings exist for this specific date + time
            $booking_key = $date_string . '_' . $start_time;
            $current_bookings = isset($booking_map[$booking_key]) ? $booking_map[$booking_key] : 0;
            
            // DEBUG: Show exact matching
            error_log("DEBUG: Checking slot - Date: '$date_string', Time: '$start_time', Key: '$booking_key'");
            error_log("DEBUG: Looking for key in booking map: " . (isset($booking_map[$booking_key]) ? "FOUND" : "NOT FOUND"));
            if (isset($booking_map[$booking_key])) {
                error_log("DEBUG: Found booking count: " . $booking_map[$booking_key]);
            }
            
            // Calculate available spots
            $available_spots = $max_bookings - $current_bookings;
            
            // DEBUG: Log booking info
            error_log("DEBUG: Slot {$date_string} {$start_time} - Max: {$max_bookings}, Current: {$current_bookings}, Available: {$available_spots}");
            
            // Determine if slot is available
            $is_available = ($available_spots > 0) && !$is_past;
            
            // Determine status
            $status = 'available';
            if ($is_past) {
                $status = 'past';
            } elseif ($available_spots <= 0) {
                $status = 'booked';
                error_log("DEBUG: Slot {$date_string} {$start_time} marked as BOOKED - No available spots");
            }
            
            $slots[] = [
                'slot_id' => (int)$slot['id'],
                'start_time' => $start_time,
                'end_time' => $end_time,
                'max_bookings' => $max_bookings,
                'current_bookings' => $current_bookings,
                'available_spots' => max(0, $available_spots),
                'available' => $is_available,
                'status' => $status
            ];
        }
        
        // Add day to response
        $response['days'][] = [
            'date' => $date_string,
            'day_of_week' => $day_of_week,
            'day_name' => $current_date->format('l'),
            'day_short' => $current_date->format('D'),
            'day_number' => (int)$current_date->format('j'),
            'month' => $current_date->format('M'),
            'month_full' => $current_date->format('F'),
            'year' => (int)$current_date->format('Y'),
            'is_past' => $is_past,
            'is_today' => $date_string === $today->format('Y-m-d'),
            'is_weekend' => ($day_of_week === 0 || $day_of_week === 6),
            'slots_count' => count($slots),
            'slots' => $slots
        ];
    }

    // Send JSON response
    echo json_encode($response);

} catch (Exception $e) {
    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Log error to file
    $log_file = dirname(__DIR__) . '/logs/api-error.log';
    $log_message = date('Y-m-d H:i:s') . " - API Error: " . $e->getMessage() . "\n";
    $log_message .= "File: " . $e->getFile() . "\n";
    $log_message .= "Line: " . $e->getLine() . "\n";
    $log_message .= "Trace: " . $e->getTraceAsString() . "\n\n";
    @file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // Send clean JSON error to client
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',
        'message' => 'An error occurred while fetching time slots. Please try again.'
    ]);
}
