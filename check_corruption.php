<?php
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('.')) as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strlen($content) > 0 && ord($content[0]) === 0) {
            echo "CORRUPTED: " . $file->getPathname() . " (Starts with NULL byte, Size: " . strlen($content) . ")\n";
        }
    }
}
