<?php
foreach(glob('*.php') as $f) {
    if (filesize($f) > 0) {
        $fp = fopen($f, 'rb');
        $firstByte = fread($fp, 1);
        fclose($fp);
        if (ord($firstByte) === 0) {
            echo "CORRUPTED: $f\n";
        }
    }
}
