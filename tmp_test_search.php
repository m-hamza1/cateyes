<?php
$_GET['search'] = 'a';
$_GET['warehouse_id'] = '1';
$_SESSION = ['admin_id' => 1, 'language_id' => 1];
require_once 'includes/Database.php';
// Need to mock auth.php if possible
// But search-products.php has auth.php included.
// So let's just make it simpler.
include 'admin/inventory/supplier-returns/search-products.php';
