<?php
$_POST['action'] = 'add_to_cart';
$_POST['product_id'] = 1;
$_POST['variation_id'] = 1;
$_POST['quantity'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock session
session_start();
$_SESSION['language_id'] = 1;

require_once 'product-ajax.php';
