<?php
require_once '../vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;

$barcode_value = $_GET['barcode'] ?? '';
$width = (int)($_GET['width'] ?? 2);
$height = (int)($_GET['height'] ?? 50);

if (empty($barcode_value)) {
    http_response_code(400);
    exit;
}

try {
    $generator = new BarcodeGeneratorPNG();
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: image/png');
    header('Cache-Control: max-age=3600');
    echo $generator->getBarcode($barcode_value, $generator::TYPE_CODE_128, $width, $height);
    
} catch (Exception $e) {
    http_response_code(500);
}
exit;
?> 