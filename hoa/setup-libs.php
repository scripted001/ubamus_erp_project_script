<?php
// setup-libs.php — automatically installs barcode + QR libraries

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Directories
$base = __DIR__;
$barcodeDest = $base . '/lib/php-barcode-generator';
$qrDest      = $base . '/lib/phpqrcode';
$barcodesOut = $base . '/barcodes';
$qrcodesOut  = $base . '/qrcodes';

function downloadAndExtract($url, $dest) {
    $tmpZip = tempnam(sys_get_temp_dir(), 'libzip_') . '.zip';
    file_put_contents($tmpZip, fopen($url, 'r'));
    $zip = new ZipArchive();
    if ($zip->open($tmpZip) === true) {
        // extract root folder into dest
        $zip->extractTo($dest);
        $zip->close();
        unlink($tmpZip);
        return true;
    }
    unlink($tmpZip);
    return false;
}

// 1. Barcode library
echo "Installing php-barcode-generator...\n";
@mkdir($barcodeDest, 0755, true);
// Note: use "master.zip" for latest stable branch
$barcodeUrl = 'https://github.com/picqer/php-barcode-generator/archive/refs/heads/master.zip';
if (!downloadAndExtract($barcodeUrl, $barcodeDest)) {
    die("Failed to download/extract php-barcode-generator.\n");
}

// 2. QR Code library
echo "Installing phpqrcode (chillerlan)...\n";
@mkdir($qrDest, 0755, true);
$qrUrl = 'https://github.com/chillerlan/php-qrcode/archive/refs/heads/master.zip';
if (!downloadAndExtract($qrUrl, $qrDest)) {
    die("Failed to download/extract phpqrcode.\n");
}

// 3. Create output folders
@mkdir($barcodesOut, 0755, true);
@mkdir($qrcodesOut, 0755, true);

echo "Setup complete.\nFolders created:\n";
echo "- $barcodeDest\n";
echo "- $qrDest\n";
echo "- $barcodesOut\n";
echo "- $qrcodesOut\n";
echo "You can now update your create-stock.php to use them.\n";
