
<?php

include('../config/db.php');


/**
 * ============================================================
 * SLIDE / ADVERT UPDATE PROCESSOR
 * ============================================================
 *
 * Behaviour:
 *
 * 1. Gets company_id from the submitted form.
 * 2. Processes only submitted slide forms.
 * 3. Existing slides are updated only when something changed.
 * 4. Existing images are preserved when no new image is uploaded.
 * 5. When a new image is uploaded, the old image is deleted
 *    only after the database update succeeds.
 * 6. New slides are inserted.
 * 7. No unnecessary image files are created.
 * 8. Database transaction protects the operation.
 */


// ============================================================
// ONLY POST REQUESTS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo "<script>
        window.location.href='home.php';
    </script>";

    exit;
}


// ============================================================
// GET FORM DATA
// ============================================================

$company_id = isset($_POST['company_id'])
    ? (int)$_POST['company_id']
    : 0;

$created_by = trim($_POST['created_by'] ?? '');

$slides = $_POST['slides'] ?? [];


// ============================================================
// BASIC VALIDATION
// ============================================================

if ($company_id <= 0) {

    echo "<script>
        alert('Invalid company.');
        window.history.back();
    </script>";

    exit;
}


if ($created_by === '') {

    echo "<script>
        alert('Created by information is missing.');
        window.history.back();
    </script>";

    exit;
}


if (!is_array($slides) || empty($slides)) {

    echo "<script>
        alert('No slide information was submitted.');
        window.history.back();
    </script>";

    exit;
}


// ============================================================
// UPLOAD DIRECTORY
// ============================================================

$uploadDir = '../uploads/';

if (!is_dir($uploadDir)) {

    if (!mkdir($uploadDir, 0755, true)) {

        echo "<script>
            alert('Unable to create upload directory.');
            window.history.back();
        </script>";

        exit;
    }
}


// ============================================================
// ALLOWED IMAGE TYPES
// ============================================================

$allowedExtensions = [
    'jpg',
    'jpeg',
    'png',
    'webp'
];

$allowedMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/webp'
];


// ============================================================
// TRACK OLD FILES
// ============================================================
//
// We do not physically delete old files immediately.
//
// First the database operation succeeds.
// After commit, the old files are deleted.
//
// This prevents accidental loss if the database transaction fails.
//

$filesToDelete = [];


// ============================================================
// TRACK NEW FILES
// ============================================================
//
// If something fails before commit, newly uploaded files are
// removed so we don't leave orphan files behind.
//

$newFilesCreated = [];


try {

    // ========================================================
    // START TRANSACTION
    // ========================================================

    $pdo->beginTransaction();


    // ========================================================
    // VERIFY COMPANY EXISTS
    // ========================================================

    $stmtCompany = $pdo->prepare("
        SELECT id
        FROM companies
        WHERE id = ?
        LIMIT 1
    ");

    $stmtCompany->execute([
        $company_id
    ]);

    if (!$stmtCompany->fetchColumn()) {

        throw new Exception(
            "The selected company could not be found."
        );
    }


    // ========================================================
    // CURRENT TIME
    // ========================================================

    $created_at = (new DateTime(
        'now',
        new DateTimeZone('Africa/Lagos')
    ))->format('Y-m-d H:i:s');


    // ========================================================
    // PROCESS EACH SUBMITTED SLIDE
    // ========================================================

    foreach ($slides as $index => $slide) {

        if (!is_array($slide)) {
            continue;
        }


        // ====================================================
        // SLIDE ORDER
        // ====================================================

        $slide_order = isset($slide['slide_order'])
            ? (int)$slide['slide_order']
            : 0;


        if ($slide_order <= 0) {

            throw new Exception(
                "Invalid slide order."
            );
        }


        // ====================================================
        // TEXT VALUES
        // ====================================================

        $submittedHeadline = trim(
            $slide['headline'] ?? ''
        );

        $submittedDescription = trim(
            $slide['description'] ?? ''
        );


        // ====================================================
        // GET EXISTING SLIDE
        // ====================================================

        $stmtExisting = $pdo->prepare("
            SELECT
                id,
                company_id,
                slide_order,
                headline,
                description,
                desktop_image,
                mobile_image,
                created_by
            FROM slides
            WHERE company_id = ?
              AND slide_order = ?
            LIMIT 1
        ");

        $stmtExisting->execute([
            $company_id,
            $slide_order
        ]);

        $existingSlide = $stmtExisting->fetch(
            PDO::FETCH_ASSOC
        );


        // ====================================================
        // CURRENT VALUES
        // ====================================================

        if ($existingSlide) {

            $headline = $submittedHeadline !== ''
                ? $submittedHeadline
                : $existingSlide['headline'];

            $description = $submittedDescription !== ''
                ? $submittedDescription
                : $existingSlide['description'];

            $desktopName =
                $existingSlide['desktop_image'];

            $mobileName =
                $existingSlide['mobile_image'];

        } else {

            // New slide

            if ($submittedHeadline === '') {

                throw new Exception(
                    "Headline is required for slide {$slide_order}."
                );
            }

            $headline = $submittedHeadline;

            $description = $submittedDescription;

            $desktopName = null;

            $mobileName = null;
        }


        // ====================================================
        // DESKTOP IMAGE
        // ====================================================

        $desktopUploaded = false;

        if (
            isset($_FILES['slides']['error'][$index]['desktop_image'])
        ) {

            $desktopError =
                $_FILES['slides']['error'][$index]['desktop_image'];

            if ($desktopError !== UPLOAD_ERR_NO_FILE) {

                if ($desktopError !== UPLOAD_ERR_OK) {

                    throw new Exception(
                        "There was a problem uploading the desktop image for slide {$slide_order}."
                    );
                }


                $desktopTmp =
                    $_FILES['slides']['tmp_name'][$index]['desktop_image'];

                $desktopOriginalName =
                    $_FILES['slides']['name'][$index]['desktop_image'];

                $desktopSize =
                    $_FILES['slides']['size'][$index]['desktop_image'];


                // --------------------------------------------
                // SIZE
                // --------------------------------------------

                if ($desktopSize > 5 * 1024 * 1024) {

                    throw new Exception(
                        "Desktop image for slide {$slide_order} must not exceed 5MB."
                    );
                }


                // --------------------------------------------
                // VERIFY IMAGE
                // --------------------------------------------

                $imageInfo = getimagesize($desktopTmp);

                if ($imageInfo === false) {

                    throw new Exception(
                        "Desktop image for slide {$slide_order} is not a valid image."
                    );
                }


                // --------------------------------------------
                // MIME TYPE
                // --------------------------------------------

                $mimeType = $imageInfo['mime'] ?? '';

                if (!in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )) {

                    throw new Exception(
                        "Invalid desktop image format for slide {$slide_order}."
                    );
                }


                // --------------------------------------------
                // EXTENSION
                // --------------------------------------------

                $extension = strtolower(
                    pathinfo(
                        $desktopOriginalName,
                        PATHINFO_EXTENSION
                    )
                );

                if (!in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )) {

                    throw new Exception(
                        "Invalid desktop image extension for slide {$slide_order}."
                    );
                }


                // --------------------------------------------
                // GENERATE NEW FILE
                // --------------------------------------------

                $newDesktopName =
                    'desktop_' .
                    $company_id .
                    '_' .
                    $slide_order .
                    '_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $extension;

                $newDesktopPath =
                    $uploadDir . $newDesktopName;


                if (!move_uploaded_file(
                    $desktopTmp,
                    $newDesktopPath
                )) {

                    throw new Exception(
                        "Unable to save desktop image for slide {$slide_order}."
                    );
                }


                $newFilesCreated[] =
                    $newDesktopPath;


                // Remember old image for deletion after commit

                if (
                    $existingSlide &&
                    !empty($existingSlide['desktop_image'])
                ) {

                    $oldDesktopPath =
                        $uploadDir .
                        basename(
                            $existingSlide['desktop_image']
                        );

                    $filesToDelete[] =
                        $oldDesktopPath;
                }


                $desktopName =
                    $newDesktopName;

                $desktopUploaded = true;
            }
        }


        // ====================================================
        // MOBILE IMAGE
        // ====================================================

        $mobileUploaded = false;

        if (
            isset($_FILES['slides']['error'][$index]['mobile_image'])
        ) {

            $mobileError =
                $_FILES['slides']['error'][$index]['mobile_image'];

            if ($mobileError !== UPLOAD_ERR_NO_FILE) {

                if ($mobileError !== UPLOAD_ERR_OK) {

                    throw new Exception(
                        "There was a problem uploading the mobile image for slide {$slide_order}."
                    );
                }


                $mobileTmp =
                    $_FILES['slides']['tmp_name'][$index]['mobile_image'];

                $mobileOriginalName =
                    $_FILES['slides']['name'][$index]['mobile_image'];

                $mobileSize =
                    $_FILES['slides']['size'][$index]['mobile_image'];


                // --------------------------------------------
                // SIZE
                // --------------------------------------------

                if ($mobileSize > 5 * 1024 * 1024) {

                    throw new Exception(
                        "Mobile image for slide {$slide_order} must not exceed 5MB."
                    );
                }


                // --------------------------------------------
                // VERIFY IMAGE
                // --------------------------------------------

                $imageInfo = getimagesize($mobileTmp);

                if ($imageInfo === false) {

                    throw new Exception(
                        "Mobile image for slide {$slide_order} is not a valid image."
                    );
                }


                // --------------------------------------------
                // MIME
                // --------------------------------------------

                $mimeType = $imageInfo['mime'] ?? '';

                if (!in_array(
                    $mimeType,
                    $allowedMimeTypes,
                    true
                )) {

                    throw new Exception(
                        "Invalid mobile image format for slide {$slide_order}."
                    );
                }


                // --------------------------------------------
                // EXTENSION
                // --------------------------------------------

                $extension = strtolower(
                    pathinfo(
                        $mobileOriginalName,
                        PATHINFO_EXTENSION
                    )
                );

                if (!in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )) {

                    throw new Exception(
                        "Invalid mobile image extension for slide {$slide_order}."
                    );
                }


                // --------------------------------------------
                // NEW FILE
                // --------------------------------------------

                $newMobileName =
                    'mobile_' .
                    $company_id .
                    '_' .
                    $slide_order .
                    '_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $extension;

                $newMobilePath =
                    $uploadDir . $newMobileName;


                if (!move_uploaded_file(
                    $mobileTmp,
                    $newMobilePath
                )) {

                    throw new Exception(
                        "Unable to save mobile image for slide {$slide_order}."
                    );
                }


                $newFilesCreated[] =
                    $newMobilePath;


                // Remember old mobile image

                if (
                    $existingSlide &&
                    !empty($existingSlide['mobile_image'])
                ) {

                    $oldMobilePath =
                        $uploadDir .
                        basename(
                            $existingSlide['mobile_image']
                        );

                    $filesToDelete[] =
                        $oldMobilePath;
                }


                $mobileName =
                    $newMobileName;

                $mobileUploaded = true;
            }
        }


        // ====================================================
        // DETERMINE WHETHER ANYTHING ACTUALLY CHANGED
        // ====================================================

        if ($existingSlide) {

            $textChanged =
                $headline !==
                    $existingSlide['headline']
                ||
                $description !==
                    $existingSlide['description'];

            $imageChanged =
                $desktopUploaded
                ||
                $mobileUploaded;


            $anythingChanged =
                $textChanged
                ||
                $imageChanged;


            // =================================================
            // NOTHING CHANGED
            // =================================================

            if (!$anythingChanged) {

                // Absolutely nothing is changed.
                //
                // Do not UPDATE.
                // Do not create files.
                // Do not touch the existing database record.

                continue;
            }


            // =================================================
            // UPDATE EXISTING SLIDE
            // =================================================

            $stmtUpdate = $pdo->prepare("
                UPDATE slides
                SET
                    headline = :headline,
                    description = :description,
                    desktop_image = :desktop_image,
                    mobile_image = :mobile_image,
                    created_by = :created_by
                WHERE id = :id
                  AND company_id = :company_id
            ");

            $stmtUpdate->execute([

                ':headline' =>
                    $headline,

                ':description' =>
                    $description,

                ':desktop_image' =>
                    $desktopName,

                ':mobile_image' =>
                    $mobileName,

                ':created_by' =>
                    $created_by,

                ':id' =>
                    $existingSlide['id'],

                ':company_id' =>
                    $company_id
            ]);
        }


        // ====================================================
        // INSERT NEW SLIDE
        // ====================================================

        else {

            // New slides require a desktop image

            if (empty($desktopName)) {

                throw new Exception(
                    "Desktop image is required for new slide {$slide_order}."
                );
            }


            $stmtInsert = $pdo->prepare("
                INSERT INTO slides (
                    company_id,
                    created_by,
                    slide_order,
                    headline,
                    description,
                    desktop_image,
                    mobile_image,
                    created_at
                )
                VALUES (
                    :company_id,
                    :created_by,
                    :slide_order,
                    :headline,
                    :description,
                    :desktop_image,
                    :mobile_image,
                    :created_at
                )
            ");

            $stmtInsert->execute([

                ':company_id' =>
                    $company_id,

                ':created_by' =>
                    $created_by,

                ':slide_order' =>
                    $slide_order,

                ':headline' =>
                    $headline,

                ':description' =>
                    $description,

                ':desktop_image' =>
                    $desktopName,

                ':mobile_image' =>
                    $mobileName,

                ':created_at' =>
                    $created_at
            ]);
        }
    }


    // ========================================================
    // COMMIT
    // ========================================================

    $pdo->commit();


    // ========================================================
    // DELETE OLD FILES AFTER SUCCESSFUL COMMIT
    // ========================================================

    foreach ($filesToDelete as $oldFile) {

        if (
            is_file($oldFile) &&
            file_exists($oldFile)
        ) {

            @unlink($oldFile);
        }
    }


    // ========================================================
    // SUCCESS
    // ========================================================

    echo "<script>

        alert('Adverts saved successfully!');

        window.location.href='index.php';

    </script>";

    exit;


} catch (Throwable $e) {


    // ========================================================
    // ROLLBACK DATABASE
    // ========================================================

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    // ========================================================
    // DELETE NEW FILES CREATED DURING FAILED OPERATION
    // ========================================================

    foreach ($newFilesCreated as $newFile) {

        if (
            is_file($newFile) &&
            file_exists($newFile)
        ) {

            @unlink($newFile);
        }
    }


    // ========================================================
    // ERROR
    // ========================================================

    $message = htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    );


    echo "<script>

        alert('Error saving slides: {$message}');

        window.history.back();

    </script>";

    exit;
}
