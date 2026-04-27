<?php
require_once 'config.php';
echo "Session language: " . ($_SESSION['language'] ?? 'not set') . "\n";
echo "Current code: " . $lang->getCurrentCode() . "\n";
