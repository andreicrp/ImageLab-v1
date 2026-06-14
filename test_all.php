<?php
require_once __DIR__ . '/core/ImageService.php';

$svc = new ImageService();
$src = __DIR__ . '/uploads/2dd7c67d64c1987602471f2486616797.png';

echo "Testing Resize:\n";
$resize = $svc->resizeImage($src, 150, 150, true);
print_r($resize);

echo "\nTesting Compression:\n";
$compress = $svc->compressImage($src, 50);
print_r($compress);

