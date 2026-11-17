<?php
declare(strict_types=1);
date_default_timezone_set('Africa/Lagos');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

function about_profile_redirect(string $message, string $type = 'success'): void
{
    $_SESSION['about_profile_flash'] = $message;
    $_SESSION['about_profile_flash_type'] = $type;
    header('Location: about.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: about.php');
    exit;
}

$company_id = (int)($_SESSION['company_id'] ?? 0);
if ($company_id <= 0) {
    http_response_code(403);
    exit('Your company session could not be verified. Please sign in again.');
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('About profile save: PDO connection $pdo not found.');
    about_profile_redirect('The database connection could not be verified. Please contact your administrator.', 'error');
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['about_profile_csrf'] ?? '');
if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
    about_profile_redirect('Your session has expired. Refresh the page and try saving again.', 'error');
}

$titles = $_POST['section_title'] ?? null;
$contents = $_POST['section_content'] ?? null;
if (!is_array($titles) || !is_array($contents) || count($titles) !== count($contents)) {
    about_profile_redirect('The submitted profile sections were invalid. Please review and try again.', 'error');
}

$cleanSections = [];
foreach ($titles as $index => $rawTitle) {
    if (!array_key_exists($index, $contents)) {
        about_profile_redirect('A profile section was incomplete. Please review and try again.', 'error');
    }
    $title = trim((string)$rawTitle);
    $content = trim((string)$contents[$index]);
    if ($title === '' && $content === '') {
        continue;
    }
    if ($title === '' || $content === '') {
        about_profile_redirect('Every section needs both a heading and content. Complete it or remove it.', 'error');
    }
    $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
    if ($titleLength > 255) {
        about_profile_redirect('A section heading cannot exceed 255 characters.', 'error');
    }
    $cleanSections[] = ['title' => $title, 'content' => $content, 'display_order' => count($cleanSections) + 1];
}
if (!$cleanSections) {
    about_profile_redirect('Please add at least one completed section to your company profile.', 'error');
}

try {
    $pdo->beginTransaction();

    // The existing application stores one About record per company_id.
    $findAbout = $pdo->prepare('SELECT id FROM about WHERE company_id = :company_id LIMIT 1 FOR UPDATE');
    $findAbout->execute([':company_id' => $company_id]);
    $aboutId = (int)($findAbout->fetchColumn() ?: 0);

    if ($aboutId < 1) {
        $insertAbout = $pdo->prepare('INSERT INTO about (company_id, who_we_are, our_vision, our_mission) VALUES (:company_id, :who_we_are, :our_vision, :our_mission)');
        $insertAbout->execute([':company_id' => $company_id, ':who_we_are' => '', ':our_vision' => '', ':our_mission' => '']);
        $aboutId = (int)$pdo->lastInsertId();
    }

    // Replace only this company's sections for this About record, inside the transaction.
    $delete = $pdo->prepare('DELETE FROM about_sections WHERE company_id = :company_id AND about_id = :about_id');
    $delete->execute([':company_id' => $company_id, ':about_id' => $aboutId]);

    $insert = $pdo->prepare('INSERT INTO about_sections (about_id, company_id, section_title, section_content, display_order) VALUES (:about_id, :company_id, :section_title, :section_content, :display_order)');
    foreach ($cleanSections as $section) {
        $insert->execute([
            ':about_id' => $aboutId,
            ':company_id' => $company_id,
            ':section_title' => $section['title'],
            ':section_content' => $section['content'],
            ':display_order' => $section['display_order'],
        ]);
    }

    // Keep the legacy About columns synchronised for older pages that still read them.
    $legacy = ['who_we_are' => '', 'our_vision' => '', 'our_mission' => ''];
    foreach ($cleanSections as $section) {
        $normalised = function_exists('mb_strtolower') ? mb_strtolower(trim($section['title']), 'UTF-8') : strtolower(trim($section['title']));
        if ($legacy['who_we_are'] === '' && in_array($normalised, ['who we are', 'about us', 'company overview', 'company profile'], true)) {
            $legacy['who_we_are'] = $section['content'];
        } elseif ($legacy['our_vision'] === '' && in_array($normalised, ['our vision', 'vision'], true)) {
            $legacy['our_vision'] = $section['content'];
        } elseif ($legacy['our_mission'] === '' && in_array($normalised, ['our mission', 'mission'], true)) {
            $legacy['our_mission'] = $section['content'];
        }
    }
    $update = $pdo->prepare('UPDATE about SET who_we_are = :who_we_are, our_vision = :our_vision, our_mission = :our_mission WHERE id = :about_id AND company_id = :company_id');
    $update->execute([
        ':who_we_are' => $legacy['who_we_are'],
        ':our_vision' => $legacy['our_vision'],
        ':our_mission' => $legacy['our_mission'],
        ':about_id' => $aboutId,
        ':company_id' => $company_id,
    ]);

    $pdo->commit();
    about_profile_redirect('Your company profile has been saved successfully.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Company profile save error: ' . $exception->getMessage());
    about_profile_redirect('We could not save your company profile. Please check the database structure and try again.', 'error');
}
