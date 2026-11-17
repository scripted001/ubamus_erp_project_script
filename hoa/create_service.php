<?php
include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request');
}

$transactionStarted = false;
$uploadedFiles = [];

try {
    $company_id     = (int)($_SESSION['company_id'] ?? 0);
    $location_id    = (int)($_POST['location_id'] ?? 0);
    $service_name   = trim($_POST['product_name'] ?? '');
    $service_number = trim($_POST['service_number'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $created_by     = (int)($_SESSION['user_id'] ?? 0);
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $raw_materials  = $_POST['raw_materials'] ?? [];
    $services       = $_POST['services'] ?? [];

    if ($company_id <= 0 || $location_id <= 0 || $created_by <= 0 || $category_id <= 0) {
        throw new Exception('Your session or service details are incomplete. Please refresh and try again.');
    }

    if ($service_name === '' || $service_number === '') {
        throw new Exception('Service name and service number are required.');
    }

    if (!is_array($raw_materials) || !is_array($services)) {
        throw new Exception('Invalid materials or external service data submitted.');
    }

    // Validate uploaded images before creating any database records.
    $imageUploads = [];
    if (isset($_FILES['service_images'])) {
        $files = $_FILES['service_images'];

        if (!isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
            throw new Exception('The uploaded image data is invalid.');
        }

        $fileCount = is_array($files['name']) ? count($files['name']) : 0;
        if ($fileCount > 5) {
            throw new Exception('You can upload a maximum of 5 service images.');
        }

        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif'
        ];
        $maxImageSize = 5 * 1024 * 1024; // 5 MB per image
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $fileCount; $i++) {
            $error = (int)$files['error'][$i];

            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new Exception('One of the service images could not be uploaded. Please try again.');
            }

            $tmpName = $files['tmp_name'][$i];
            $fileSize = (int)$files['size'][$i];

            if (!is_uploaded_file($tmpName)) {
                throw new Exception('An invalid service image upload was detected.');
            }
            if ($fileSize <= 0 || $fileSize > $maxImageSize) {
                throw new Exception('Each service image must be smaller than or equal to 5 MB.');
            }

            $mimeType = $finfo->file($tmpName);
            if (!isset($allowedMimeTypes[$mimeType])) {
                throw new Exception('Only JPG, PNG, WEBP and GIF service images are allowed.');
            }

            $imageUploads[] = [
                'tmp_name' => $tmpName,
                'extension' => $allowedMimeTypes[$mimeType]
            ];
        }
    }

    // Confirm that the selected location and category belong to this company.
    $stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$location_id, $company_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('The selected location is invalid.');
    }

    $stmt = $pdo->prepare("SELECT id FROM job_categories WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$category_id, $company_id]);
    if (!$stmt->fetchColumn()) {
        throw new Exception('The selected service category is invalid.');
    }

    // Consolidate duplicate product rows before calculating the service cost.
    $requestedMaterials = [];
    foreach ($raw_materials as $item) {
        if (!is_array($item)) {
            throw new Exception('Invalid raw material entry.');
        }

        $product_id = (int)($item['product_id'] ?? 0);
        $qtyRaw = $item['qty'] ?? 0;
        if (!is_numeric($qtyRaw)) {
            throw new Exception('A raw material quantity is invalid.');
        }

        $qty = (float)$qtyRaw;
        if ($product_id <= 0 && $qty <= 0) {
            continue;
        }
        if ($product_id <= 0 || !is_finite($qty) || $qty < 0) {
            throw new Exception('A raw material selection or quantity is invalid.');
        }
        if ($qty == 0) {
            continue;
        }

        if (!isset($requestedMaterials[$product_id])) {
            $requestedMaterials[$product_id] = 0.0;
        }
        $requestedMaterials[$product_id] += $qty;
    }

    $materials_total = 0.0;
    $skills_total = 0.0;

    $pdo->beginTransaction();
    $transactionStarted = true;

    // Create the service header inside the same transaction as its related records.
    $stmt = $pdo->prepare("
        INSERT INTO services
            (company_id, category_id, location_id, service_number, service_name, description, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $company_id,
        $category_id,
        $location_id,
        $service_number,
        $service_name,
        $description,
        $created_by
    ]);

    $service_id = (int)$pdo->lastInsertId();
    if ($service_id <= 0) {
        throw new Exception('The service record could not be created.');
    }

    // Save service images. The service_image table links to services.id (not products.id).
    if (count($imageUploads) > 0) {
        $uploadDirectory = __DIR__ . '/../uploads/services';
        if (!is_dir($uploadDirectory)) {
            throw new Exception('The service image folder does not exist. Please create ../uploads/services manually before uploading images.');
        }
        if (!is_writable($uploadDirectory)) {
            throw new Exception('The ../uploads/services folder is not writable. Please check its permissions.');
        }

        $imageStmt = $pdo->prepare("
            INSERT INTO service_image (service_id, company_id, image_path, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        foreach ($imageUploads as $image) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $image['extension'];
            $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
            $relativePath = '../uploads/services/' . $fileName;

            if (!move_uploaded_file($image['tmp_name'], $destination)) {
                throw new Exception('A service image could not be saved. Please check the upload folder permissions.');
            }

            $uploadedFiles[] = $destination;
            $imageStmt->execute([$service_id, $company_id, $relativePath]);
        }
    }

    // Save raw-material requirements. Stock is NOT deducted here;
    // consumption happens only when a customer actually renders a job.
    $productStmt = $pdo->prepare("
        SELECT cost_price
        FROM products
        WHERE id = ? AND company_id = ? AND stock_type = 'Service Only'
        LIMIT 1
    ");

    $serviceItemStmt = $pdo->prepare("
        INSERT INTO service_items
            (service_id, product_id, quantity_used, unit_cost, total_cost, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    foreach ($requestedMaterials as $product_id => $qty) {
        $productStmt->execute([$product_id, $company_id]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new Exception("Invalid service material selected (Product ID: {$product_id}).");
        }

        $unit_cost = (float)($product['cost_price'] ?? 0);
        if (!is_finite($unit_cost) || $unit_cost < 0) {
            throw new Exception("The cost price for product ID {$product_id} is invalid.");
        }

        $line_total = $unit_cost * $qty;
        $materials_total += $line_total;
        $serviceItemStmt->execute([
            $service_id,
            $product_id,
            $qty,
            $unit_cost,
            $line_total
        ]);
    }

    // Process external skills/services.
    $skillStmt = $pdo->prepare("
        INSERT INTO service_skills (service_id, skill_name, fee, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    foreach ($services as $srv) {
        if (!is_array($srv)) {
            throw new Exception('An external service entry is invalid.');
        }

        $name = trim($srv['name'] ?? '');
        $feeRaw = $srv['cost'] ?? 0;

        if ($name === '' && ($feeRaw === '' || (is_numeric($feeRaw) && (float)$feeRaw <= 0))) {
            continue;
        }
        if (!is_numeric($feeRaw)) {
            throw new Exception("The fee for '{$name}' is invalid.");
        }

        $fee = (float)$feeRaw;
        if ($name === '' || !is_finite($fee) || $fee < 0) {
            throw new Exception('Each external service must have a name and a valid fee.');
        }
        if ($fee <= 0) {
            continue;
        }

        $skills_total += $fee;
        $skillStmt->execute([$service_id, $name, $fee]);
    }

    // Update service totals.
    $total_cost = $materials_total + $skills_total;
    $stmt = $pdo->prepare("
        UPDATE services
        SET materials_cost = ?, external_skill_cost = ?, total_cost = ?
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([
        $materials_total,
        $skills_total,
        $total_cost,
        $service_id,
        $company_id
    ]);

    $pdo->commit();
    $transactionStarted = false;

    echo "<script>
        alert('Service created successfully. Images and service details have been saved. Raw-material requirements have been saved. Stock will be deducted when the service is rendered.');
        window.location.href = 'existing-service.php';
    </script>";

} catch (Throwable $e) {
    if ($transactionStarted && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Remove any files already moved if the database transaction fails.
    foreach ($uploadedFiles as $filePath) {
        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    error_log('Service registration failed: ' . $e->getMessage());

    $message = json_encode(
        'Service registration failed: ' . $e->getMessage(),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
    );

    echo "<script>
        alert({$message});
        window.history.back();
    </script>";
}
?>
