<?php
$file = 'checkout.php';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $len = strlen($content);
    $nonZeroCount = 0;
    for ($i = 0; $i < $len; $i++) {
        if (ord($content[$i]) !== 0) {
            $nonZeroCount++;
        }
    }
    echo "Total bytes: $len\n";
    echo "Non-zero bytes: $nonZeroCount\n";
}
