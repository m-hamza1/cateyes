<?php
$mojibake = "Ø¹Ø´Ø±Ø©";
echo "Original: $mojibake\n";
echo "Decoded (utf8_decode): " . utf8_decode($mojibake) . "\n";
echo "Converted (iconv): " . iconv("UTF-8", "ISO-8859-1", $mojibake) . "\n";
echo "Hex: " . bin2hex($mojibake) . "\n";
?>
