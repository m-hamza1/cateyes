<?php
require_once 'config.php';
$order = $db->fetchOne("SELECT id, order_number, billing_email, guest_email, user_id, order_status, payment_status FROM orders WHERE id = 8");
echo "ORDER #8 DEBUG:\n";
print_r($order);
