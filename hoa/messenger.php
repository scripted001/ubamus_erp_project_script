<?php
declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| MESSENGER — FULL-PAGE ERP MESSAGING
|--------------------------------------------------------------------------
| Uses the existing ERP users table as the participant identity.
| Access is restricted to users sharing the same company_id AND location_id.
| Chat tables are created automatically if the database user has CREATE rights.
|--------------------------------------------------------------------------
*/

date_default_timezone_set('Africa/Lagos');

include('../config/db.php');
include('../config/validation.php');
include('user_data.php');
include('../company_data.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$company_id  = (int)($_SESSION['company_id'] ?? 0);
$user_id     = (int)($_SESSION['user_id'] ?? 0);
$location_id = (int)($_SESSION['location_id'] ?? 0);

if (!$company_id || !$user_id || !$location_id) {
    http_response_code(403);
    exit('Your session is incomplete. Please sign in again.');
}

function msg_h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function msg_json(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function msg_require_csrf(): void {
    $sent = (string)($_POST['csrf_token'] ?? '');
    $saved = (string)($_SESSION['messenger_csrf'] ?? '');
    if ($saved === '' || !hash_equals($saved, $sent)) {
        msg_json(['ok' => false, 'error' => 'Your session token expired. Refresh the page and try again.'], 419);
    }
}
function msg_is_admin(array $user): bool {
    return str_contains(strtolower(trim((string)($user['role'] ?? ''))), 'admin');
}
function msg_user(PDO $pdo, int $id, int $companyId, int $locationId, bool $allowCrossLocation = false): ?array {
    $sql = "SELECT id, username, email, role, location_id FROM users WHERE id = ? AND company_id = ?";
    $params = [$id, $companyId];
    if (!$allowCrossLocation) { $sql .= " AND location_id = ?"; $params[] = $locationId; }
    $stmt = $pdo->prepare($sql . " LIMIT 1");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function msg_profile(PDO $pdo, int $id, int $companyId, int $locationId, bool $allowCrossLocation = false): ?array {
    $sql = "SELECT u.id, u.username, u.email, u.role, u.status,
                   s.firstname, s.lastname, s.phone, s.address, s.profile,
                   s.status AS employment_status, s.created_at AS joined_at
            FROM users u
            LEFT JOIN staffs s ON s.company_id = u.company_id AND s.email = u.email
            WHERE u.id = ? AND u.company_id = ?";
    $params = [$id, $companyId];
    if (!$allowCrossLocation) { $sql .= " AND u.location_id = ?"; $params[] = $locationId; }
    $stmt = $pdo->prepare($sql . " ORDER BY s.id DESC LIMIT 1");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['display_name'] = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? '')) ?: ($row['username'] ?? 'Team member');
    $profile = trim((string)($row['profile'] ?? ''));
    $row['avatar'] = '';
    if ($profile !== '' && strpos($profile, '..') === false && !preg_match('~^(?:https?:)?//~i', $profile)) {
        $row['avatar'] = '../' . ltrim(str_replace('\\', '/', $profile), '/');
    }
    unset($row['firstname'], $row['lastname'], $row['profile']);
    return $row;
}
function msg_is_member(PDO $pdo, int $conversationId, int $userId, int $companyId, int $locationId): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM chat_conversations c
        INNER JOIN chat_participants cp ON cp.conversation_id = c.id
        WHERE c.id = ? AND cp.user_id = ? AND cp.participant_type = 'employee'
          AND c.company_id = ? LIMIT 1");
    $stmt->execute([$conversationId, $userId, $companyId]);
    return (bool)$stmt->fetchColumn();
}

/* Create the Messenger tables on first use. */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_conversations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            location_id INT NOT NULL,
            conversation_type ENUM('direct','group') NOT NULL DEFAULT 'direct',
            title VARCHAR(150) NULL,
            created_by INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_chat_scope (company_id, location_id, updated_at),
            INDEX idx_chat_creator (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_participants (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED NOT NULL,
            user_id INT NOT NULL,
            participant_role ENUM('member','admin') NOT NULL DEFAULT 'member',
            joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_read_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY uq_chat_participant (conversation_id, user_id),
            INDEX idx_chat_user (user_id, conversation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED NOT NULL,
            sender_id INT NOT NULL,
            message_type ENUM('text','image','video','audio','file') NOT NULL DEFAULT 'text',
            body TEXT NULL,
            attachment_path VARCHAR(500) NULL,
            attachment_name VARCHAR(255) NULL,
            attachment_mime VARCHAR(150) NULL,
            attachment_size BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_chat_message (conversation_id, id),
            INDEX idx_chat_sender (sender_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $participantColumns = $pdo->query("SHOW COLUMNS FROM chat_participants")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('cleared_before_message_id', $participantColumns, true)) {
        $pdo->exec("ALTER TABLE chat_participants ADD COLUMN cleared_before_message_id BIGINT UNSIGNED NOT NULL DEFAULT 0");
    }
    if (!in_array('hidden_at', $participantColumns, true)) {
        $pdo->exec("ALTER TABLE chat_participants ADD COLUMN hidden_at DATETIME NULL");
    }
    // Message actions: replies, forwarding provenance and per-message deletion.
    $messageColumns = $pdo->query("SHOW COLUMNS FROM chat_messages")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('reply_to_message_id', $messageColumns, true)) {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN reply_to_message_id BIGINT UNSIGNED NULL AFTER body");
    }
    if (!in_array('forwarded_from_message_id', $messageColumns, true)) {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN forwarded_from_message_id BIGINT UNSIGNED NULL AFTER reply_to_message_id");
    }
    if (!in_array('deleted_at', $messageColumns, true)) {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN deleted_at DATETIME NULL AFTER created_at");
    }
    if (!in_array('deleted_for_everyone', $messageColumns, true)) {
        $pdo->exec("ALTER TABLE chat_messages ADD COLUMN deleted_for_everyone TINYINT(1) NOT NULL DEFAULT 0 AFTER deleted_at");
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_message_deletions (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, message_id BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, participant_type ENUM('employee','customer') NOT NULL DEFAULT 'employee', deleted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_chat_message_delete (message_id,user_id,participant_type), INDEX idx_chat_delete_user (user_id,participant_type)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_user_presence (user_id INT NOT NULL, company_id INT NOT NULL, last_seen_at DATETIME NOT NULL, PRIMARY KEY (user_id, company_id), INDEX idx_chat_presence_seen (company_id, last_seen_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_user_activity (conversation_id BIGINT UNSIGNED NOT NULL, user_id INT NOT NULL, company_id INT NOT NULL, activity VARCHAR(20) NOT NULL DEFAULT 'none', updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (conversation_id,user_id), INDEX idx_chat_activity_recent (conversation_id,updated_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $participantColumns = $pdo->query("SHOW COLUMNS FROM chat_participants")->fetchAll(PDO::FETCH_COLUMN, 0);
    if (!in_array('left_at', $participantColumns, true)) {
        $pdo->exec("ALTER TABLE chat_participants ADD COLUMN left_at DATETIME NULL AFTER hidden_at");
    }
} catch (Throwable $e) {
    error_log('Messenger schema error: ' . $e->getMessage());
    if (isset($_GET['action']) || isset($_POST['action'])) {
        msg_json(['ok' => false, 'error' => 'Messenger database tables could not be initialized. Check database CREATE permissions.'], 500);
    }
}

/* AJAX/API actions are handled before any page HTML is emitted. */
$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
if ($action !== '') {
    try {
        if ($action === 'presence_ping') {
            msg_require_csrf();
            $ping = $pdo->prepare('INSERT INTO chat_user_presence (user_id, company_id, last_seen_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE last_seen_at=NOW()');
            $ping->execute([$user_id, $company_id]);
            msg_json(['ok' => true, 'last_seen_at' => date('Y-m-d H:i:s')]);
        }

        if ($action === 'activity_update') {
            msg_require_csrf();
            $conversationId = (int)($_POST['conversation_id'] ?? 0);
            $activity = (string)($_POST['activity'] ?? 'none');
            if (!in_array($activity, ['typing','attaching','uploading','none'], true)) $activity = 'none';
            if (!$conversationId || !msg_is_member($pdo, $conversationId, $user_id, $company_id, $location_id)) {
                msg_json(['ok'=>false,'error'=>'Conversation not found or access denied.'],403);
            }
            $stmt = $pdo->prepare("INSERT INTO chat_user_activity (conversation_id,user_id,company_id,activity,updated_at) VALUES (?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE activity=VALUES(activity),updated_at=NOW()");
            $stmt->execute([$conversationId,$user_id,$company_id,$activity]);
            msg_json(['ok'=>true]);
        }

        if ($action === 'activity_status') {
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            if (!$conversationId || !msg_is_member($pdo, $conversationId, $user_id, $company_id, $location_id)) {
                msg_json(['ok'=>false,'error'=>'Conversation not found or access denied.'],403);
            }
            $stmt = $pdo->prepare("SELECT a.user_id,a.activity,a.updated_at,u.username,COALESCE(NULLIF(TRIM(CONCAT(COALESCE((SELECT s.firstname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1),''),' ',COALESCE((SELECT s.lastname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1),''))),''),u.username) AS display_name FROM chat_user_activity a INNER JOIN users u ON u.id=a.user_id AND u.company_id=a.company_id WHERE a.conversation_id=? AND a.company_id=? AND a.user_id<>? AND a.activity<>'none' AND a.updated_at >= DATE_SUB(NOW(), INTERVAL 8 SECOND) ORDER BY a.updated_at DESC");
            $stmt->execute([$conversationId,$company_id,$user_id]);
            msg_json(['ok'=>true,'activities'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        if ($action === 'bootstrap') {
            $me = msg_user($pdo, $user_id, $company_id, $location_id);
            if (!$me) msg_json(['ok' => false, 'error' => 'Your user account is not available in this location.'], 403);
            $isAdmin = msg_is_admin($me);

            $peopleStmt = $pdo->prepare("
                SELECT u.id, u.username, u.email, u.role,
                       (SELECT p.last_seen_at FROM chat_user_presence p WHERE p.user_id=u.id AND p.company_id=u.company_id LIMIT 1) AS last_seen_at,
                       (SELECT s.firstname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS firstname,
                       (SELECT s.lastname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS lastname,
                       (SELECT s.phone FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS phone,
                       (SELECT s.address FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS address,
                       (SELECT s.profile FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS profile
                FROM users u
                WHERE u.company_id = ? AND u.id <> ?
                  AND (? = 1 OR u.location_id = ?)
                ORDER BY u.username ASC
            ");
            $peopleStmt->execute([$company_id, $user_id, $isAdmin ? 1 : 0, $location_id]);
            $people = $peopleStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($people as &$person) {
                $person['is_online'] = !empty($person['last_seen_at']) && strtotime((string)$person['last_seen_at']) >= (time() - 45);
                $person['display_name'] = trim(($person['firstname'] ?? '') . ' ' . ($person['lastname'] ?? '')) ?: ($person['username'] ?? 'Team member');
                $profilePath = trim((string)($person['profile'] ?? ''));
                $person['avatar'] = ($profilePath !== '' && strpos($profilePath, '..') === false) ? '../' . ltrim(str_replace('\\','/',$profilePath), '/') : '';
                unset($person['firstname'], $person['lastname'], $person['profile']);
            }
            unset($person);

            // CRM customers are loaded from the existing customers directory.
            // They remain distinct from employee users even when numeric IDs overlap.
            $customerStmt = $pdo->prepare("SELECT id, fullname, email, phone, location_id, status
                FROM customers
                WHERE company_id = ? AND (? = 1 OR location_id = ?)
                ORDER BY fullname ASC");
            $customerStmt->execute([$company_id, $isAdmin ? 1 : 0, $location_id]);
            $customers = $customerStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($customers as &$customer) {
                $customer['display_name'] = trim((string)$customer['fullname']) ?: 'Customer';
                $customer['participant_type'] = 'customer';
                $customer['avatar'] = '';
            }
            unset($customer);

            $convStmt = $pdo->prepare("
                SELECT
                    c.id, c.conversation_type, c.title, c.updated_at,
                    cp.last_read_message_id,
                    lm.id AS last_message_id, lm.body AS last_body,
                    lm.message_type AS last_type, lm.sender_id AS last_sender_id,
                    lm.created_at AS last_message_at,
                    su.username AS last_sender_name,
                    (
                        SELECT COUNT(*)
                        FROM chat_messages um
                        WHERE um.conversation_id = c.id
                          AND um.id > GREATEST(cp.last_read_message_id, cp.cleared_before_message_id)
                          AND (um.sender_type = 'customer' OR (um.sender_type = 'employee' AND um.sender_id <> ?))
                    ) AS unread_count
                FROM chat_conversations c
                INNER JOIN chat_participants cp ON cp.conversation_id = c.id AND cp.user_id = ? AND cp.participant_type = 'employee'
                LEFT JOIN chat_messages lm ON lm.id = (
                    SELECT MAX(m2.id) FROM chat_messages m2 WHERE m2.conversation_id = c.id AND m2.id > cp.cleared_before_message_id
                )
                LEFT JOIN users su ON su.id = lm.sender_id AND su.company_id = c.company_id AND lm.sender_type = 'employee'
                WHERE c.company_id = ?
                  AND (cp.hidden_at IS NULL)
                ORDER BY COALESCE(lm.created_at, c.updated_at) DESC, c.id DESC
            ");
            $convStmt->execute([$user_id, $user_id, $company_id]);
            $conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($conversations as &$conv) {
                $memberStmt = $pdo->prepare("
                    SELECT u.id, u.username, cp.participant_role, 'employee' AS participant_type,
                           (SELECT p.last_seen_at FROM chat_user_presence p WHERE p.user_id=u.id AND p.company_id=u.company_id LIMIT 1) AS last_seen_at,
                           (SELECT s.firstname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS firstname,
                           (SELECT s.lastname FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS lastname,
                           (SELECT s.profile FROM staffs s WHERE s.company_id=u.company_id AND s.email=u.email ORDER BY s.id DESC LIMIT 1) AS profile
                    FROM chat_participants cp
                    INNER JOIN users u ON u.id = cp.user_id AND u.company_id = ?
                    WHERE cp.conversation_id = ? AND cp.participant_type = 'employee'
                    ORDER BY u.username ASC
                ");
                $memberStmt->execute([$company_id, (int)$conv['id']]);
                $members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($members as &$member) {
                    $member['is_online'] = !empty($member['last_seen_at']) && strtotime((string)$member['last_seen_at']) >= (time() - 45);
                    $member['display_name'] = trim(($member['firstname'] ?? '') . ' ' . ($member['lastname'] ?? '')) ?: ($member['username'] ?? 'Team member');
                    $profilePath = trim((string)($member['profile'] ?? ''));
                    $member['avatar'] = ($profilePath !== '' && strpos($profilePath, '..') === false) ? '../' . ltrim(str_replace('\\','/',$profilePath), '/') : '';
                    unset($member['firstname'], $member['lastname'], $member['profile']);
                }
                unset($member);
                $customerMemberStmt = $pdo->prepare("SELECT cu.id, cu.fullname AS display_name, cu.email, cu.phone,
                        cp.participant_role, 'customer' AS participant_type
                    FROM chat_participants cp
                    INNER JOIN customers cu ON cu.id = cp.user_id AND cu.company_id = ?
                    WHERE cp.conversation_id = ? AND cp.participant_type = 'customer'");
                $customerMemberStmt->execute([$company_id, (int)$conv['id']]);
                $customerMembers = $customerMemberStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($customerMembers as &$cm) { $cm['username'] = $cm['display_name']; $cm['avatar'] = ''; }
                unset($cm);
                $members = array_merge($members, $customerMembers);
                $conv['members'] = $members;
                if ($conv['conversation_type'] === 'direct') {
                    foreach ($members as $member) {
                        if (($member['participant_type'] ?? 'employee') === 'customer') {
                            $conv['display_title'] = $member['display_name'] ?: 'Customer';
                            $conv['other_customer_id'] = (int)$member['id'];
                            break;
                        }
                        if ((int)$member['id'] !== $user_id) {
                            $conv['display_title'] = $member['display_name'] ?? $member['username'];
                            $conv['other_user_id'] = (int)$member['id'];
                            break;
                        }
                    }
                } else {
                    $conv['display_title'] = $conv['title'] ?: 'Group conversation';
                }
                $conv['unread_count'] = (int)$conv['unread_count'];
            }
            unset($conv);

            msg_json([
                'ok' => true,
                'me' => ['id' => $user_id, 'username' => $me['username']],
                'people' => $people,
                'customers' => $customers,
                'conversations' => $conversations
            ]);
        }

        if ($action === 'create_direct') {
            msg_require_csrf();
            $otherId = (int)($_POST['user_id'] ?? 0);
            $actor = msg_user($pdo, $user_id, $company_id, $location_id);
            $isAdmin = $actor ? msg_is_admin($actor) : false;
            $otherUser = $otherId ? msg_user($pdo, $otherId, $company_id, $location_id, $isAdmin) : null;
            if (!$otherId || $otherId === $user_id || !$otherUser) {
                msg_json(['ok' => false, 'error' => 'That employee is not available to your account.'], 422);
            }

            $find = $pdo->prepare("
                SELECT c.id
                FROM chat_conversations c
                INNER JOIN chat_participants a ON a.conversation_id = c.id AND a.user_id = ? AND a.participant_type = 'employee'
                INNER JOIN chat_participants b ON b.conversation_id = c.id AND b.user_id = ? AND b.participant_type = 'employee'
                WHERE c.company_id = ? AND (? = 1 OR c.location_id = ?)
                  AND c.conversation_type = 'direct'
                  AND (SELECT COUNT(*) FROM chat_participants x WHERE x.conversation_id = c.id) = 2
                LIMIT 1
            ");
            $find->execute([$user_id, $otherId, $company_id, $isAdmin ? 1 : 0, $location_id]);
            $conversationId = (int)($find->fetchColumn() ?: 0);

            if ($conversationId) {
                $restore = $pdo->prepare("UPDATE chat_participants SET hidden_at=NULL WHERE conversation_id=? AND user_id=? AND participant_type='employee'");
                $restore->execute([$conversationId, $user_id]);
            }
            if (!$conversationId) {
                $pdo->beginTransaction();
                $insert = $pdo->prepare("INSERT INTO chat_conversations (company_id, location_id, conversation_type, created_by) VALUES (?, ?, 'direct', ?)");
                $conversationLocation = ($isAdmin && (int)$otherUser['location_id'] !== $location_id) ? null : $location_id;
                $insert->execute([$company_id, $conversationLocation, $user_id]);
                $conversationId = (int)$pdo->lastInsertId();
                $part = $pdo->prepare("INSERT INTO chat_participants (conversation_id, user_id, participant_type, participant_role) VALUES (?, ?, 'employee', 'member')");
                $part->execute([$conversationId, $user_id]);
                $part->execute([$conversationId, $otherId]);
                $pdo->commit();
            }
            msg_json(['ok' => true, 'conversation_id' => $conversationId]);
        }

        if ($action === 'create_customer_chat') {
            msg_require_csrf();
            $customerId = (int)($_POST['customer_id'] ?? 0);
            $actor = msg_user($pdo, $user_id, $company_id, $location_id);
            $isAdmin = $actor ? msg_is_admin($actor) : false;
            $customerSql = "SELECT id, fullname, email, phone, location_id FROM customers WHERE id = ? AND company_id = ?";
            $customerParams = [$customerId, $company_id];
            if (!$isAdmin) { $customerSql .= " AND location_id = ?"; $customerParams[] = $location_id; }
            $customerLookup = $pdo->prepare($customerSql . " LIMIT 1");
            $customerLookup->execute($customerParams);
            $customer = $customerLookup->fetch(PDO::FETCH_ASSOC);
            if (!$customer) msg_json(['ok'=>false,'error'=>'This customer is not available to your account.'], 403);

            $find = $pdo->prepare("SELECT c.id FROM chat_conversations c
                INNER JOIN chat_participants a ON a.conversation_id=c.id AND a.user_id=? AND a.participant_type='employee'
                INNER JOIN chat_participants b ON b.conversation_id=c.id AND b.user_id=? AND b.participant_type='customer'
                WHERE c.company_id=? AND c.conversation_type='direct'
                  AND (SELECT COUNT(*) FROM chat_participants x WHERE x.conversation_id=c.id)=2
                LIMIT 1");
            $find->execute([$user_id, $customerId, $company_id]);
            $conversationId = (int)($find->fetchColumn() ?: 0);
            if ($conversationId) {
                $restore = $pdo->prepare("UPDATE chat_participants SET hidden_at=NULL WHERE conversation_id=? AND user_id=? AND participant_type='employee'");
                $restore->execute([$conversationId, $user_id]);
            } else {
                $pdo->beginTransaction();
                $conversationLocation = ($isAdmin && (int)$customer['location_id'] !== $location_id) ? null : $location_id;
                $insert = $pdo->prepare("INSERT INTO chat_conversations (company_id,location_id,conversation_type,title,created_by) VALUES (?,?,'direct',?,?)");
                $insert->execute([$company_id, $conversationLocation, 'Customer: ' . mb_substr((string)$customer['fullname'],0,135), $user_id]);
                $conversationId = (int)$pdo->lastInsertId();
                $part = $pdo->prepare("INSERT INTO chat_participants (conversation_id,user_id,participant_type,participant_role) VALUES (?,?,'employee','member'), (?,?,'customer','member')");
                $part->execute([$conversationId,$user_id,$conversationId,$customerId]);
                $pdo->commit();
            }
            msg_json(['ok'=>true,'conversation_id'=>$conversationId]);
        }

        if ($action === 'create_group') {
            msg_require_csrf();
            $title = trim((string)($_POST['title'] ?? ''));
            $rawMembers = $_POST['members'] ?? [];
            if (!is_array($rawMembers)) $rawMembers = [];
            if ($title === '' || mb_strlen($title) > 150) msg_json(['ok'=>false,'error'=>'Enter a group name (maximum 150 characters).'],422);
            $typed = [];
            foreach ($rawMembers as $raw) {
                $raw = (string)$raw;
                if (preg_match('/^(employee|customer):(\d+)$/', $raw, $m)) {
                    $key = $m[1] . ':' . (int)$m[2];
                    if ((int)$m[2] > 0 && $key !== 'employee:' . $user_id) $typed[$key] = [$m[1], (int)$m[2]];
                } elseif (ctype_digit($raw) && (int)$raw > 0 && (int)$raw !== $user_id) {
                    $typed['employee:' . (int)$raw] = ['employee', (int)$raw];
                }
            }
            if (!$typed) msg_json(['ok'=>false,'error'=>'Select at least one person for the group.'],422);
            $actor = msg_user($pdo,$user_id,$company_id,$location_id);
            $isAdmin = $actor ? msg_is_admin($actor) : false;
            $validated = []; $hasOtherLocation = false;
            foreach ($typed as [$type,$id]) {
                if ($type === 'employee') {
                    $u = msg_user($pdo,$id,$company_id,$location_id,$isAdmin);
                    if (!$u) msg_json(['ok'=>false,'error'=>'An employee selected for this group is not available.'],403);
                    if ((int)$u['location_id'] !== $location_id) $hasOtherLocation = true;
                } else {
                    $q = $pdo->prepare('SELECT id,location_id FROM customers WHERE id=? AND company_id=?' . ($isAdmin ? '' : ' AND location_id=?') . ' LIMIT 1');
                    $args = [$id,$company_id]; if (!$isAdmin) $args[]=$location_id;
                    $q->execute($args); $u=$q->fetch(PDO::FETCH_ASSOC);
                    if (!$u) msg_json(['ok'=>false,'error'=>'A customer selected for this group is not available.'],403);
                    if ((int)$u['location_id'] !== $location_id) $hasOtherLocation = true;
                }
                $validated[] = [$type,$id];
            }
            $pdo->beginTransaction();
            $groupLocation = ($isAdmin && $hasOtherLocation) ? null : $location_id;
            $ins=$pdo->prepare("INSERT INTO chat_conversations (company_id,location_id,conversation_type,title,created_by,created_by_type) VALUES (?,?,'group',?,?,'employee')");
            $ins->execute([$company_id,$groupLocation,$title,$user_id]);
            $conversationId=(int)$pdo->lastInsertId();
            $part=$pdo->prepare("INSERT INTO chat_participants (conversation_id,user_id,participant_type,participant_role) VALUES (?,?,?,?)");
            $part->execute([$conversationId,$user_id,'employee','admin']);
            foreach ($validated as [$type,$id]) $part->execute([$conversationId,$id,$type,'member']);
            $pdo->commit();
            msg_json(['ok'=>true,'conversation_id'=>$conversationId]);
        }

        if ($action === 'add_group_members') {
            msg_require_csrf();
            $conversationId=(int)($_POST['conversation_id']??0); $raw=$_POST['members']??[];
            if (!is_array($raw)) $raw=[];
            if (!$conversationId || !msg_is_member($pdo,$conversationId,$user_id,$company_id,$location_id)) msg_json(['ok'=>false,'error'=>'Conversation not found.'],403);
            $check=$pdo->prepare("SELECT conversation_type FROM chat_conversations WHERE id=? AND company_id=?"); $check->execute([$conversationId,$company_id]);
            if ($check->fetchColumn() !== 'group') msg_json(['ok'=>false,'error'=>'Members can only be added to groups.'],422);
            $role=$pdo->prepare("SELECT participant_role FROM chat_participants WHERE conversation_id=? AND user_id=? AND participant_type='employee'"); $role->execute([$conversationId,$user_id]);
            if ($role->fetchColumn() !== 'admin') msg_json(['ok'=>false,'error'=>'Only group administrators can add members.'],403);
            $insert=$pdo->prepare("INSERT IGNORE INTO chat_participants (conversation_id,user_id,participant_type,participant_role) VALUES (?,?,?,'member')");
            $added=0;
            foreach ($raw as $value) {
                if (!preg_match('/^(employee|customer):(\d+)$/',(string)$value,$m)) continue;
                $type=$m[1]; $id=(int)$m[2]; if ($id<1) continue;
                if ($type==='employee') $valid=msg_user($pdo,$id,$company_id,$location_id,msg_is_admin(msg_user($pdo,$user_id,$company_id,$location_id)??[]));
                else { $q=$pdo->prepare('SELECT id FROM customers WHERE id=? AND company_id=? AND location_id=?'); $q->execute([$id,$company_id,$location_id]); $valid=$q->fetchColumn(); }
                if (!$valid) continue;
                $insert->execute([$conversationId,$id,$type]); $added += $insert->rowCount();
            }
            $pdo->prepare('UPDATE chat_conversations SET updated_at=NOW() WHERE id=?')->execute([$conversationId]);
            msg_json(['ok'=>true,'added'=>$added]);
        }

        if ($action === 'remove_group_member') {
            msg_require_csrf();
            $conversationId=(int)($_POST['conversation_id']??0); $targetId=(int)($_POST['user_id']??0); $type=(string)($_POST['participant_type']??'employee');
            if (!$conversationId || !$targetId || !in_array($type,['employee','customer'],true) || !msg_is_member($pdo,$conversationId,$user_id,$company_id,$location_id)) msg_json(['ok'=>false,'error'=>'Invalid group member request.'],422);
            $q=$pdo->prepare("SELECT c.conversation_type, cp.participant_role FROM chat_conversations c INNER JOIN chat_participants cp ON cp.conversation_id=c.id AND cp.user_id=? AND cp.participant_type='employee' WHERE c.id=? AND c.company_id=?");
            $q->execute([$user_id,$conversationId,$company_id]); $auth=$q->fetch(PDO::FETCH_ASSOC);
            if (!$auth || $auth['conversation_type']!=='group' || $auth['participant_role']!=='admin') msg_json(['ok'=>false,'error'=>'Only group administrators can remove members.'],403);
            if ($type==='employee' && $targetId===$user_id) msg_json(['ok'=>false,'error'=>'Use Leave group to remove yourself.'],422);
            $del=$pdo->prepare('DELETE FROM chat_participants WHERE conversation_id=? AND user_id=? AND participant_type=?');
            $del->execute([$conversationId,$targetId,$type]);
            msg_json(['ok'=>true,'removed'=>$del->rowCount()]);
        }

        if ($action === 'delete_message') {
            msg_require_csrf();
            $messageId=(int)($_POST['message_id']??0); $forEveryone=(int)($_POST['for_everyone']??0)===1;
            $q=$pdo->prepare("SELECT m.sender_id,m.sender_type,m.conversation_id FROM chat_messages m INNER JOIN chat_conversations c ON c.id=m.conversation_id INNER JOIN chat_participants cp ON cp.conversation_id=c.id AND cp.user_id=? AND cp.participant_type='employee' WHERE m.id=? AND c.company_id=?");
            $q->execute([$user_id,$messageId,$company_id]); $m=$q->fetch(PDO::FETCH_ASSOC);
            if (!$m) msg_json(['ok'=>false,'error'=>'Message not found or access denied.'],404);
            if ($forEveryone && !((int)$m['sender_id']===$user_id && $m['sender_type']==='employee')) msg_json(['ok'=>false,'error'=>'You can only delete your own messages for everyone.'],403);
            if ($forEveryone) $pdo->prepare('UPDATE chat_messages SET deleted_for_everyone=1,deleted_at=NOW(),body=NULL,attachment_path=NULL,attachment_name=NULL,attachment_mime=NULL,attachment_size=NULL WHERE id=?')->execute([$messageId]);
            else $pdo->prepare("INSERT INTO chat_message_deletions (message_id,user_id,participant_type,deleted_at) VALUES (?,?,'employee',NOW()) ON DUPLICATE KEY UPDATE deleted_at=NOW()")->execute([$messageId,$user_id]);
            msg_json(['ok'=>true]);
        }

        if ($action === 'forward_message') {
            msg_require_csrf();
            $messageId=(int)($_POST['message_id']??0); $targetId=(int)($_POST['target_id']??0); $targetType=(string)($_POST['target_type']??'employee');
            if (!$messageId || !$targetId || !in_array($targetType,['employee','customer'],true)) msg_json(['ok'=>false,'error'=>'Choose a valid forwarding recipient.'],422);
            $q=$pdo->prepare("SELECT m.* FROM chat_messages m INNER JOIN chat_participants cp ON cp.conversation_id=m.conversation_id AND cp.user_id=? AND cp.participant_type='employee' INNER JOIN chat_conversations c ON c.id=m.conversation_id WHERE m.id=? AND c.company_id=? AND m.deleted_for_everyone=0");
            $q->execute([$user_id,$messageId,$company_id]); $source=$q->fetch(PDO::FETCH_ASSOC);
            if (!$source) msg_json(['ok'=>false,'error'=>'The message is unavailable.'],404);
            if ($targetType==='employee') $target=msg_user($pdo,$targetId,$company_id,$location_id,msg_is_admin(msg_user($pdo,$user_id,$company_id,$location_id)??[]));
            else { $q=$pdo->prepare('SELECT id FROM customers WHERE id=? AND company_id=? AND location_id=?'); $q->execute([$targetId,$company_id,$location_id]); $target=$q->fetchColumn(); }
            if (!$target) msg_json(['ok'=>false,'error'=>'The selected recipient is not available.'],403);
            $find=$pdo->prepare("SELECT c.id FROM chat_conversations c INNER JOIN chat_participants a ON a.conversation_id=c.id AND a.user_id=? AND a.participant_type='employee' INNER JOIN chat_participants b ON b.conversation_id=c.id AND b.user_id=? AND b.participant_type=? WHERE c.company_id=? AND c.conversation_type='direct' AND (SELECT COUNT(*) FROM chat_participants z WHERE z.conversation_id=c.id)=2 LIMIT 1");
            $find->execute([$user_id,$targetId,$targetType,$company_id]); $cid=(int)($find->fetchColumn()?:0);
            if (!$cid) { $pdo->beginTransaction(); $pdo->prepare("INSERT INTO chat_conversations (company_id,location_id,conversation_type,created_by,created_by_type) VALUES (?,?,'direct',?,'employee')")->execute([$company_id,$location_id,$user_id]); $cid=(int)$pdo->lastInsertId(); $part=$pdo->prepare("INSERT INTO chat_participants (conversation_id,user_id,participant_type,participant_role) VALUES (?,?,?,'member')"); $part->execute([$cid,$user_id,'employee']); $part->execute([$cid,$targetId,$targetType]); $pdo->commit(); }
            $body=trim((string)$source['body']); $body=$body!=='' ? "Forwarded message:\n".$body : 'Forwarded attachment';
            $ins=$pdo->prepare("INSERT INTO chat_messages (conversation_id,sender_id,sender_type,message_type,body,attachment_path,attachment_name,attachment_mime,attachment_size,forwarded_from_message_id) VALUES (?,?,'employee',?,?,?,?,?,?,?)");
            $ins->execute([$cid,$user_id,$source['message_type'],$body,$source['attachment_path'],$source['attachment_name'],$source['attachment_mime'],$source['attachment_size'],$messageId]);
            $pdo->prepare('UPDATE chat_conversations SET updated_at=NOW() WHERE id=?')->execute([$cid]);
            msg_json(['ok'=>true,'conversation_id'=>$cid]);
        }

        if ($action === 'messages') {
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            $targetMessageId = max(0, (int)($_GET['target_message_id'] ?? 0));
            if (!$conversationId || !msg_is_member($pdo, $conversationId, $user_id, $company_id, $location_id)) {
                msg_json(['ok' => false, 'error' => 'Conversation not found or access denied.'], 403);
            }
            $stmt = $pdo->prepare("
                SELECT m.id, m.sender_id, m.sender_type, m.message_type, CASE WHEN m.deleted_for_everyone=1 THEN 'This message was deleted' ELSE m.body END AS body, m.attachment_name,
                       m.attachment_mime, m.attachment_size, m.created_at, m.reply_to_message_id, m.forwarded_from_message_id, m.deleted_for_everyone,
                       (SELECT rb.body FROM chat_messages rb WHERE rb.id=m.reply_to_message_id) AS reply_body,
                       CASE WHEN m.sender_type='customer' THEN cu.fullname ELSE COALESCE(NULLIF(CONCAT_WS(' ',NULLIF(st.firstname,''),NULLIF(st.lastname,'')),''),u.username) END AS sender_name,
                       CASE WHEN m.sender_type='employee' THEN st.profile ELSE NULL END AS sender_profile
                FROM chat_messages m
                LEFT JOIN users u ON m.sender_type='employee' AND u.id=m.sender_id AND u.company_id=?
                LEFT JOIN staffs st ON m.sender_type='employee' AND st.company_id=u.company_id AND st.email=u.email
                    AND st.id=(SELECT MAX(st2.id) FROM staffs st2 WHERE st2.company_id=u.company_id AND st2.email=u.email)
                LEFT JOIN customers cu ON m.sender_type='customer' AND cu.id=m.sender_id
                    AND cu.company_id=(SELECT company_id FROM chat_conversations WHERE id=m.conversation_id)
                WHERE m.conversation_id=? AND m.sender_type IN ('employee','customer')
                  AND NOT EXISTS (SELECT 1 FROM chat_message_deletions md WHERE md.message_id=m.id AND md.user_id=? AND md.participant_type='employee')
                  AND m.id > (SELECT COALESCE(cleared_before_message_id,0) FROM chat_participants WHERE conversation_id=? AND user_id=? AND participant_type='employee' LIMIT 1)
                  AND (? = 0 OR m.id BETWEEN GREATEST(1, ? - 40) AND ? + 60)
                ORDER BY m.id DESC LIMIT 100
            ");
            $stmt->execute([$company_id, $conversationId, $user_id, $conversationId, $user_id, $targetMessageId, $targetMessageId, $targetMessageId]);
            $messages = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
            foreach ($messages as &$message) {
                $profilePath = trim((string)($message['sender_profile'] ?? ''));
                $message['sender_avatar'] = ($profilePath !== '' && strpos($profilePath, '..') === false) ? '../' . ltrim(str_replace('\\','/',$profilePath), '/') : '';
                $message['sender_name'] = trim((string)($message['sender_display_name'] ?? '')) ?: $message['sender_name'];
                unset($message['sender_profile'], $message['sender_display_name']);
            }
            unset($message);

            $lastId = 0;
            if ($messages) $lastId = (int)end($messages)['id'];
            $read = $pdo->prepare("UPDATE chat_participants SET last_read_message_id = GREATEST(last_read_message_id, ?) WHERE conversation_id = ? AND user_id = ? AND participant_type = 'employee'");
            $read->execute([$lastId, $conversationId, $user_id]);

            msg_json(['ok' => true, 'messages' => $messages, 'last_id' => $lastId]);
        }

        if ($action === 'send') {
            msg_require_csrf();
            $conversationId = (int)($_POST['conversation_id'] ?? 0);
            if (!$conversationId || !msg_is_member($pdo, $conversationId, $user_id, $company_id, $location_id)) {
                msg_json(['ok' => false, 'error' => 'Conversation not found or access denied.'], 403);
            }
            $body = trim((string)($_POST['body'] ?? ''));
            $replyTo = (int)($_POST['reply_to_message_id'] ?? 0);
            if ($replyTo) { $rq=$pdo->prepare('SELECT id FROM chat_messages WHERE id=? AND conversation_id=?'); $rq->execute([$replyTo,$conversationId]); if (!$rq->fetchColumn()) msg_json(['ok'=>false,'error'=>'The message being replied to is unavailable.'],422); }
            $type = 'text';
            $relativePath = null;
            $fileName = null;
            $mime = null;
            $size = null;

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['attachment'];
                if ($file['error'] !== UPLOAD_ERR_OK) msg_json(['ok' => false, 'error' => 'The attachment could not be uploaded.'], 422);
                $size = (int)$file['size'];
                if ($size < 1 || $size > 50 * 1024 * 1024) msg_json(['ok' => false, 'error' => 'Files must be 50 MB or smaller.'], 422);

                $originalName = basename((string)$file['name']);
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';

                $imageExt = ['jpg','jpeg','png','gif','webp'];
                $videoExt = ['mp4','webm','mov','m4v'];
                $audioExt = ['mp3','wav','ogg','m4a','aac','webm'];
                $fileExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','csv','zip','rar','7z'];
                if (in_array($extension, $imageExt, true)) $type = 'image';
                elseif (in_array($extension, $videoExt, true)) $type = 'video';
                elseif (in_array($extension, $audioExt, true)) $type = 'audio';
                elseif (in_array($extension, $fileExt, true)) $type = 'file';
                else msg_json(['ok' => false, 'error' => 'This file type is not supported.'], 422);

                $folder = $type === 'image' ? 'images' : ($type === 'video' ? 'videos' : ($type === 'audio' ? 'audio' : 'documents'));
                $baseDir = __DIR__ . '/../uploads/chat/' . $folder . '/';
                if (!is_dir($baseDir) && !mkdir($baseDir, 0755, true) && !is_dir($baseDir)) {
                    msg_json(['ok' => false, 'error' => 'The chat upload directory could not be created.'], 500);
                }
                $safeName = bin2hex(random_bytes(18)) . ($extension ? '.' . $extension : '');
                if (!move_uploaded_file($file['tmp_name'], $baseDir . $safeName)) {
                    msg_json(['ok' => false, 'error' => 'Could not save the uploaded attachment.'], 500);
                }
                $relativePath = 'uploads/chat/' . $folder . '/' . $safeName;
                $fileName = mb_substr($originalName, 0, 255);
            }

            if ($body === '' && !$relativePath) msg_json(['ok' => false, 'error' => 'Write a message or attach a file.'], 422);
            $insert = $pdo->prepare("
                INSERT INTO chat_messages
                    (conversation_id, sender_id, sender_type, message_type, body, attachment_path, attachment_name, attachment_mime, attachment_size, reply_to_message_id)
                VALUES (?, ?, 'employee', ?, ?, ?, ?, ?, ?, ?)
            ");
            $insert->execute([$conversationId, $user_id, $type, $body ?: null, $relativePath, $fileName, $mime, $size, $replyTo ?: null]);
            $messageId = (int)$pdo->lastInsertId();
            $update = $pdo->prepare("UPDATE chat_conversations SET updated_at = NOW() WHERE id = ?");
            $update->execute([$conversationId]);
            msg_json(['ok' => true, 'message_id' => $messageId]);
        }

        if ($action === 'download') {
            $messageId = (int)($_GET['message_id'] ?? 0);
            $stmt = $pdo->prepare("
                SELECT m.attachment_path, m.attachment_name, m.attachment_mime
                FROM chat_messages m
                INNER JOIN chat_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ? AND cp.participant_type = 'employee'
                INNER JOIN chat_conversations c ON c.id = m.conversation_id
                WHERE m.id = ? AND c.company_id = ?
                LIMIT 1
            ");
            $stmt->execute([$user_id, $messageId, $company_id]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$file || !$file['attachment_path']) {
                http_response_code(404);
                exit('Attachment not found.');
            }
            $fullPath = realpath(__DIR__ . '/../' . $file['attachment_path']);
            $allowedRoot = realpath(__DIR__ . '/../uploads/chat');
            if (!$fullPath || !$allowedRoot || strpos($fullPath, $allowedRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($fullPath)) {
                http_response_code(404);
                exit('Attachment not found.');
            }
            $downloadName = $file['attachment_name'] ?: basename($fullPath);
            header('Content-Type: ' . ($file['attachment_mime'] ?: 'application/octet-stream'));
            header('Content-Length: ' . filesize($fullPath));
            header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $downloadName) . '"');
            header('X-Content-Type-Options: nosniff');
            readfile($fullPath);
            exit;
        }

        if ($action === 'user_profile') {
            $targetId = (int)($_GET['user_id'] ?? 0);
            $viewer = msg_user($pdo, $user_id, $company_id, $location_id);
            $profile = msg_profile($pdo, $targetId, $company_id, $location_id, $viewer ? msg_is_admin($viewer) : false);
            if (!$profile) msg_json(['ok'=>false,'error'=>'This employee profile is not available.'], 404);
            msg_json(['ok'=>true,'profile'=>$profile]);
        }

        if ($action === 'search_messages') {
            $conversationId = (int)($_GET['conversation_id'] ?? 0);
            $term = trim((string)($_GET['q'] ?? ''));
            if (!$conversationId || mb_strlen($term) < 2 || !msg_is_member($pdo, $conversationId, $user_id, $company_id, $location_id)) {
                msg_json(['ok'=>false,'error'=>'Enter at least two characters and select a valid conversation.'], 422);
            }
            $stmt = $pdo->prepare("SELECT m.id,m.sender_id,m.message_type,m.body,m.attachment_name,m.created_at,u.username AS sender_name FROM chat_messages m INNER JOIN users u ON u.id=m.sender_id AND u.company_id=? WHERE m.sender_type='employee' AND m.conversation_id=? AND m.id > (SELECT COALESCE(cleared_before_message_id,0) FROM chat_participants WHERE conversation_id=? AND user_id=? AND participant_type='employee' LIMIT 1) AND (m.body LIKE ? OR m.attachment_name LIKE ?) ORDER BY m.id DESC LIMIT 100");
            $like = '%' . $term . '%';
            $stmt->execute([$company_id,$conversationId,$conversationId,$user_id,$like,$like]);
            msg_json(['ok'=>true,'messages'=>array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC))]);
        }

        if ($action === 'clear_chat') {
            msg_require_csrf();
            $conversationId = (int)($_POST['conversation_id'] ?? 0);
            if (!$conversationId || !msg_is_member($pdo,$conversationId,$user_id,$company_id,$location_id)) msg_json(['ok'=>false,'error'=>'Conversation not found.'],403);
            $max = $pdo->prepare("SELECT COALESCE(MAX(id),0) FROM chat_messages WHERE conversation_id=?");
            $max->execute([$conversationId]);
            $upd = $pdo->prepare("UPDATE chat_participants SET cleared_before_message_id=? WHERE conversation_id=? AND user_id=? AND participant_type='employee'");
            $upd->execute([(int)$max->fetchColumn(),$conversationId,$user_id]);
            msg_json(['ok'=>true]);
        }

        if ($action === 'hide_chat') {
            msg_require_csrf();
            $conversationId = (int)($_POST['conversation_id'] ?? 0);
            if (!$conversationId || !msg_is_member($pdo,$conversationId,$user_id,$company_id,$location_id)) msg_json(['ok'=>false,'error'=>'Conversation not found.'],403);
            $upd = $pdo->prepare("UPDATE chat_participants SET hidden_at=NOW() WHERE conversation_id=? AND user_id=? AND participant_type='employee'");
            $upd->execute([$conversationId,$user_id]);
            msg_json(['ok'=>true]);
        }

        if ($action === 'media') {
            $stmt = $pdo->prepare("SELECT m.id,m.conversation_id,m.message_type,m.attachment_name,m.created_at,c.title,c.conversation_type,cp.last_read_message_id FROM chat_messages m INNER JOIN chat_conversations c ON c.id=m.conversation_id INNER JOIN chat_participants cp ON cp.conversation_id=c.id AND cp.user_id=? AND cp.participant_type='employee' WHERE c.company_id=? AND cp.hidden_at IS NULL AND m.id > cp.cleared_before_message_id AND m.attachment_path IS NOT NULL ORDER BY m.id DESC LIMIT 150");
            $stmt->execute([$user_id,$company_id]);
            msg_json(['ok'=>true,'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        }

        msg_json(['ok' => false, 'error' => 'Unknown Messenger action.'], 404);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Messenger action error: ' . $e->getMessage());
        msg_json(['ok' => false, 'error' => 'Messenger could not complete that request. Please try again.'], 500);
    }
}

if (empty($_SESSION['messenger_csrf'])) {
    $_SESSION['messenger_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['messenger_csrf'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= msg_h($company_name ?? 'Company') ?> | Messenger</title>
    <link rel="stylesheet" href="../fontawesome/css/all.css">
    <link href="style.css" rel="stylesheet">
    <style>
        .messenger-page{width:100%;max-width:1600px;margin:0 auto}
        .messenger-shell{height:min(820px,calc(100vh - 155px));min-height:510px;display:grid;grid-template-columns:320px minmax(0,1fr);grid-template-rows:minmax(0,1fr);overflow:hidden;border:1px solid #e5e9ef;border-radius:20px;background:#fff;box-shadow:0 12px 35px #0f172a0b}
        .messenger-sidebar,.messenger-main{min-height:0}
        .messenger-sidebar{height:100%;overflow:hidden}
        .messenger-sidebar{min-width:0;display:flex;flex-direction:column;border-right:1px solid #e9edf2;background:#fff}
        .messenger-side-head{padding:17px 15px 13px;border-bottom:1px solid #edf0f4}
        .messenger-side-title{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:13px}
        .messenger-side-title strong{font-size:14px;color:#172033;font-weight:850}
        .messenger-icon-btn{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border:1px solid #e5e9ef;border-radius:10px;background:#fff;color:#647084;cursor:pointer;transition:.2s}
        .messenger-icon-btn:hover{border-color:#b89a4b;color:#836001;background:#fcfaf5}
        .messenger-search{position:relative}
        .messenger-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9aa3b1;font-size:11px}
        .messenger-search input{width:100%;height:39px;padding:8px 12px 8px 33px;border:1px solid #e6eaf0;border-radius:11px;background:#f8f9fb;color:#273142;outline:none;font-size:11px}
        .messenger-search input:focus{border-color:#b89a4b;background:#fff;box-shadow:0 0 0 3px #83600112}
        .messenger-tabs{display:flex;gap:6px;margin-top:12px}
        .messenger-tab{flex:1;padding:7px 4px;border:0;border-radius:8px;background:transparent;color:#8993a3;font-size:10px;font-weight:800;cursor:pointer}
        .messenger-tab.active{background:#f5f0e4;color:#836001}
        .messenger-list{flex:1;overflow-y:auto;padding:6px}
        .messenger-list-item{width:100%;display:flex;align-items:center;gap:10px;padding:11px 9px;border:0;border-radius:12px;background:transparent;text-align:left;cursor:pointer;transition:.15s}
        .messenger-list-item:hover{background:#f8f9fb}
        .messenger-list-item.active{background:#f7f3e9}
        .messenger-avatar{position:relative;flex:0 0 42px;width:42px;height:42px;display:flex;align-items:center;justify-content:center;border-radius:14px;background:linear-gradient(145deg,#f5efdf,#e8dfc6);color:#836001;font-size:13px;font-weight:900;text-transform:uppercase}
        .messenger-avatar.group{background:linear-gradient(145deg,#e8edf5,#dbe3ef);color:#52647d}
        .messenger-avatar .online-dot{position:absolute;right:-1px;bottom:-1px;width:11px;height:11px;border:2px solid #fff;border-radius:50%;background:#35b779}.messenger-avatar .presence-dot{position:absolute;right:-1px;bottom:-1px;width:11px;height:11px;border:2px solid #fff;border-radius:50%;background:#35b779}.messenger-avatar .presence-dot.offline{background:#aab2bf}.presence-online{color:#219866!important;font-weight:800}.presence-offline{color:#929baa!important}
        .messenger-list-copy{flex:1;min-width:0}
        .messenger-list-top{display:flex;align-items:center;justify-content:space-between;gap:7px}
        .messenger-list-name{overflow:hidden;color:#263041;font-size:11px;font-weight:850;text-overflow:ellipsis;white-space:nowrap}
        .messenger-list-time{flex:0 0 auto;color:#a0a8b5;font-size:9px}
        .messenger-list-preview{display:block;margin-top:5px;overflow:hidden;color:#8993a3;font-size:10px;text-overflow:ellipsis;white-space:nowrap}
        .messenger-unread{min-width:17px;height:17px;padding:0 5px;display:inline-flex;align-items:center;justify-content:center;border-radius:99px;background:#836001;color:#fff;font-size:9px;font-weight:850}
        .messenger-empty-list{padding:35px 15px;text-align:center;color:#9aa3b1;font-size:11px;line-height:1.6}
        .messenger-empty-list i{display:block;margin-bottom:10px;color:#c2ad75;font-size:23px}
        .messenger-main{min-width:0;min-height:0;height:100%;max-height:100%;display:flex;flex:1 1 auto;flex-direction:column;overflow:hidden;background:#fbfcfd}
        .messenger-chat-head{min-height:68px;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid #e9edf2;background:#fff}
        .messenger-chat-person{display:flex;align-items:center;gap:10px;min-width:0}
        .messenger-chat-person .messenger-avatar{width:39px;height:39px;flex-basis:39px}
        .messenger-chat-name{overflow:hidden;color:#172033;font-size:12px;font-weight:850;text-overflow:ellipsis;white-space:nowrap}
        .messenger-chat-presence{margin-top:4px;color:#9aa3b1;font-size:9px}
        .messenger-live-activity{min-height:27px;padding:5px 20px;display:flex;align-items:center;gap:9px;background:#fff;color:#8a6b20;font-size:10px;font-weight:750;border-top:1px solid #f0eee8}.activity-dots{display:inline-flex;align-items:center;gap:3px}.activity-dots i{width:5px;height:5px;border-radius:50%;background:#b38b2e;animation:activityBounce .85s infinite ease-in-out}.activity-dots i:nth-child(2){animation-delay:.14s}.activity-dots i:nth-child(3){animation-delay:.28s}@keyframes activityBounce{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
        .dark .messenger-live-activity{background:#111827;border-color:#263143;color:#e5c66e}
        .messenger-chat-actions{display:flex;align-items:center;gap:7px;position:relative}
        .messenger-avatar img,.profile-avatar img{width:100%;height:100%;object-fit:cover;border-radius:inherit}
        .messenger-avatar{overflow:hidden;cursor:pointer}
        .messenger-menu{position:absolute;right:0;top:43px;z-index:1000;display:none;min-width:190px;padding:6px;border:1px solid #e8ebef;border-radius:12px;background:#fff;box-shadow:0 14px 35px #0f172a20}
        .messenger-menu.open{display:block}
        .messenger-menu button{width:100%;display:flex;align-items:center;gap:10px;padding:10px;border:0;border-radius:8px;background:transparent;color:#394457;text-align:left;font-size:11px;cursor:pointer}
        .messenger-menu button:hover{background:#f7f3e9;color:#836001}
        .messenger-profile-card{display:flex;align-items:center;gap:14px;margin-bottom:16px}
        .profile-avatar{width:76px;height:76px;flex:0 0 76px;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:22px;background:#f5f0e4;color:#836001;font-size:24px;font-weight:850}
        .profile-details{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .profile-field{padding:10px;border:1px solid #edf0f4;border-radius:10px;background:#fafbfc;min-width:0}
        .profile-field small{display:block;margin-bottom:4px;color:#98a1af;font-size:9px;font-weight:800;text-transform:uppercase}
        .profile-field strong{display:block;color:#344054;font-size:11px;overflow-wrap:anywhere}
        .messenger-back{display:none}
        .messenger-messages{display:block;flex:1 1 auto;min-width:0;min-height:0;height:auto;max-height:none;overflow-x:hidden;overflow-y:auto;overscroll-behavior-y:contain;-webkit-overflow-scrolling:touch;touch-action:pan-y;padding:22px clamp(14px,4vw,48px);background-color:#f7f8fa;background-image:radial-gradient(#dce1e8 0.65px,transparent 0.65px);background-size:17px 17px}
        .messenger-welcome{height:100%;min-height:260px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#8b95a5}
        .messenger-welcome-icon{width:70px;height:70px;display:flex;align-items:center;justify-content:center;border:1px solid #e9e2d1;border-radius:23px;background:#f5f0e4;color:#94721b;font-size:27px}
        .messenger-welcome h3{margin:15px 0 5px;color:#344054;font-size:16px;font-weight:850}
        .messenger-welcome p{max-width:310px;margin:0;color:#98a1af;font-size:11px;line-height:1.7}
        .message-day{display:flex;justify-content:center;margin:4px 0 17px}
        .message-day span{padding:6px 10px;border:1px solid #e8ebef;border-radius:99px;background:#fff;color:#8a94a3;font-size:9px;font-weight:750}
        .message-row{display:flex;margin:8px 0;align-items:flex-end;gap:7px}
        .message-row.mine{justify-content:flex-end}
        .message-bubble{max-width:min(76%,560px);padding:10px 12px 7px;border:1px solid #e8ebef;border-radius:14px 14px 14px 4px;background:#fff;box-shadow:0 2px 5px #0f172a06}
        .message-row.mine .message-bubble{border-color:#e9dfc4;border-radius:14px 14px 4px 14px;background:#f8f2e5}
        .message-sender{margin-bottom:6px;color:#836001;font-size:9px;font-weight:850}
        .message-mini-avatar{width:21px;height:21px;flex:0 0 21px;display:inline-flex;align-items:center;justify-content:center;overflow:hidden;border-radius:7px;background:#f2ead7;color:#836001;font-size:8px}
        .message-mini-avatar img{width:100%;height:100%;object-fit:cover}
        .message-mini-avatar-fallback{font-size:8px;font-weight:850}
        .message-body{color:#374151;font-size:11px;line-height:1.65;white-space:pre-wrap;overflow-wrap:anywhere}
        .message-meta{display:flex;align-items:center;justify-content:flex-end;gap:5px;margin-top:5px;color:#a0a8b5;font-size:8px}
        .message-attachment{display:block;max-width:100%;margin:3px 0 6px;border-radius:9px}
        .message-attachment.image{max-width:min(280px,100%);max-height:240px;object-fit:cover}
        .message-attachment.video{width:min(320px,100%);max-height:230px;background:#111}
        .message-file{display:flex;align-items:center;gap:10px;min-width:190px;max-width:100%;padding:10px;border:1px solid #e8ebef;border-radius:10px;background:#f8f9fb;color:#4b5563;text-decoration:none}
        .message-file i{font-size:19px;color:#836001}
        .message-file span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:10px;font-weight:750}
        .messenger-composer-wrap{flex:0 0 auto;min-width:0;padding:12px 16px 14px;border-top:1px solid #e9edf2;background:#fff}
        .messenger-attachment-preview{display:none;align-items:center;justify-content:space-between;gap:10px;margin:0 0 9px;padding:8px 10px;border:1px solid #eee5d0;border-radius:10px;background:#fbf8ef;color:#7b641f;font-size:10px}
        .messenger-composer{display:flex;align-items:flex-end;gap:8px}
        .messenger-compose-tools{display:flex;gap:5px;padding-bottom:2px}
        .messenger-compose-tools .messenger-icon-btn{width:36px;height:36px;border:0;background:#f6f7f9}
        .messenger-compose-input{flex:1;min-width:0;min-height:40px;max-height:120px;resize:none;padding:11px 13px;border:1px solid #e4e8ee;border-radius:13px;background:#f8f9fb;color:#273142;outline:none;font-size:11px;line-height:1.5}
        .messenger-compose-input:focus{border-color:#b89a4b;background:#fff;box-shadow:0 0 0 3px #83600110}
        .messenger-send{width:39px;height:39px;flex:0 0 39px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;background:linear-gradient(145deg,#a9872e,#765700);color:#fff;cursor:pointer;box-shadow:0 5px 12px #83600125}
        .messenger-send:disabled{opacity:.5;cursor:not-allowed}
        .messenger-composer-hint{margin:7px 0 0 4px;color:#a0a8b5;font-size:8px}
        .messenger-modal-backdrop{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:18px;background:#10182780;backdrop-filter:blur(4px)}
        .messenger-modal-backdrop.open{display:flex}
        .messenger-modal{width:min(440px,100%);max-height:85vh;overflow:auto;padding:20px;border:1px solid #e7eaf0;border-radius:18px;background:#fff;box-shadow:0 25px 70px #0f172a35}
        .messenger-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
        .messenger-modal-head strong{color:#172033;font-size:15px;font-weight:850}
        .messenger-modal label{display:block;margin:12px 0 6px;color:#697386;font-size:10px;font-weight:800}
        .messenger-modal input{width:100%;height:41px;padding:9px 11px;border:1px solid #dfe3e9;border-radius:10px;background:#fafbfc;color:#263041;outline:none;font-size:11px}
        .messenger-member-picker{max-height:210px;overflow:auto;border:1px solid #e8ebef;border-radius:10px;padding:7px}
        .messenger-member-option{display:flex!important;align-items:center;gap:9px;margin:0!important;padding:8px;border-radius:8px;color:#465164!important;font-size:11px!important;font-weight:650!important}
        .messenger-member-option:hover{background:#f8f9fb}
        .messenger-member-option input{width:15px;height:15px}
        .messenger-modal-submit{width:100%;height:41px;margin-top:16px;border:0;border-radius:10px;background:#836001;color:#fff;font-size:11px;font-weight:850;cursor:pointer}
        .message-actions{display:flex;justify-content:flex-end;gap:3px;margin-top:4px;opacity:.35;transition:opacity .15s}.message-bubble:hover .message-actions{opacity:1}.message-actions button{width:25px;height:24px;border:0;border-radius:6px;background:transparent;color:#7b8492;cursor:pointer}.message-actions button:hover{background:#eee8d9;color:#765700}.message-reply-preview{display:block;width:100%;text-align:left;padding:7px 10px;margin-bottom:7px;border:0;border-left:3px solid #a9872e;border-radius:5px;background:#f7f4ec;color:#667085;font-size:10px;cursor:pointer}.message-reply-preview:hover{background:#eee8d9}.message-jump-highlight .message-bubble{outline:3px solid #d5a928;box-shadow:0 0 0 6px rgba(213,169,40,.16);animation:messageJumpPulse 1.5s ease-out}@keyframes messageJumpPulse{0%{background:#fff1b8}100%{background:transparent}}.message-reply-preview small{display:block;color:#8a6b20;font-weight:800;margin-bottom:3px}.messenger-reply-compose{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:7px 10px;margin-bottom:8px;border-left:3px solid #a9872e;border-radius:7px;background:#f7f4ec;color:#67521d;font-size:10px}.messenger-reply-compose span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dark .message-reply-preview,.dark .messenger-reply-compose{background:#302b1e;color:#e2d5ad}
        .messenger-toast{position:fixed;right:20px;bottom:20px;z-index:10000;display:none;max-width:min(360px,calc(100vw - 40px));padding:12px 15px;border-radius:11px;background:#172033;color:#fff;font-size:11px;box-shadow:0 10px 30px #0f172a25}
        .messenger-toast.show{display:block}
        .messenger-loading{padding:22px;text-align:center;color:#9aa3b1;font-size:10px}
        .dark .messenger-shell,.dark .messenger-sidebar,.dark .messenger-chat-head,.dark .messenger-composer-wrap,.dark .messenger-modal{background:#111827;border-color:#263143}
        .dark .messenger-side-head,.dark .messenger-chat-head,.dark .messenger-composer-wrap{border-color:#263143}
        .dark .messenger-side-title strong,.dark .messenger-list-name,.dark .messenger-chat-name,.dark .messenger-welcome h3,.dark .messenger-modal-head strong{color:#e2e8f0}
        .dark .messenger-search input,.dark .messenger-compose-input,.dark .messenger-modal input{background:#182131;border-color:#303c4f;color:#f8fafc}
        .dark .messenger-list-item:hover{background:#182131}.dark .messenger-list-item.active{background:#272719}
        .dark .messenger-main{background:#101827}.dark .messenger-messages{background-color:#151f2e;background-image:radial-gradient(#2a3546 0.65px,transparent 0.65px)}
        .dark .message-bubble{background:#1c293a;border-color:#303c4f}.dark .message-row.mine .message-bubble{background:#302b1e;border-color:#544522}
        .dark .message-body{color:#e2e8f0}.dark .message-day span,.dark .messenger-icon-btn,.dark .messenger-compose-tools .messenger-icon-btn{background:#182131;border-color:#303c4f;color:#cbd5e1}
        .dark .message-file{background:#182131;border-color:#303c4f;color:#e2e8f0}
        .dark .messenger-modal label{color:#aab4c3}.dark .messenger-member-option{color:#dbe3ed!important}
        @media(max-width:900px){.messenger-shell{grid-template-columns:285px minmax(0,1fr)}}
        @media(max-width:700px){
            .messenger-shell{height:100%;min-height:0;max-height:none;display:block;position:relative;overflow:hidden;border-radius:0}
            .messenger-sidebar{height:100%;width:100%;border:0}
            .messenger-main{display:none;height:100%;min-height:0;width:100%;overflow:hidden}
            .messenger-shell.chat-open .messenger-sidebar{display:none}
            .messenger-shell.chat-open .messenger-main{display:flex}
            .messenger-chat-head{min-height:60px;padding:9px 11px}
            .messenger-back{display:inline-flex}
            .messenger-chat-actions .desktop-action{display:none}.messenger-menu{top:40px}
            .messenger-messages{padding:17px 11px}
            .message-bubble{max-width:88%}
            .messenger-composer-wrap{padding:9px 8px calc(9px + env(safe-area-inset-bottom))}
            .messenger-compose-tools{gap:1px}.messenger-compose-tools .messenger-icon-btn{width:32px;height:35px}
            .messenger-compose-input{min-height:38px;padding:10px}
            .messenger-send{width:36px;height:36px;flex-basis:36px}
        }
        /* FULL-WORKSPACE LAYOUT: keep the existing sidebar/header/footer,
           while the messenger occupies every pixel of the content area between them. */
        html,body{height:100%;}
        body > .flex.h-screen{height:100dvh;min-height:0;overflow:hidden;}
        body > .flex.h-screen > .relative.flex.flex-1.flex-col{display:flex;flex:1 1 auto;flex-direction:column;min-width:0;min-height:0;height:100%;overflow:hidden;}
        body > .flex.h-screen > .relative.flex.flex-1.flex-col > main{display:flex;flex:1 1 auto;flex-direction:column;min-width:0;min-height:0;height:100%;overflow:hidden;}
        body > .flex.h-screen > .relative.flex.flex-1.flex-col > main > .mx-auto{display:flex;flex:1 1 auto;flex-direction:column;width:100%;max-width:none;min-width:0;min-height:0;height:100%;margin:0;padding:0!important;}
        .messenger-page{display:flex;flex:1 1 auto;width:100%;max-width:none;min-width:0;min-height:0;height:100%;margin:0;}
        .messenger-shell{width:100%;height:100%;max-height:none;min-height:0;flex:1 1 auto;border:0;border-radius:0;box-shadow:none;}
        /* Cross-browser message scrolling (Chromium, Firefox and Edge).
           The grid row and every flex ancestor must be shrinkable; the message
           panel is the only vertically scrolling region in the chat window. */
        #messengerShell{grid-template-rows:minmax(0,1fr)}
        #messengerMain{display:flex;flex-direction:column;min-width:0;min-height:0;height:100%;max-height:100%;overflow:hidden}
        #messageArea{display:block;flex:1 1 auto;min-width:0;min-height:0;height:auto;max-height:none;overflow-x:hidden;overflow-y:auto;overscroll-behavior-y:contain;scroll-behavior:auto;}
        #messageArea::-webkit-scrollbar{width:7px}
        #messageArea::-webkit-scrollbar-thumb{background:rgba(137,147,163,.45);border-radius:20px}
        #messageArea::-webkit-scrollbar-track{background:transparent}
        @media(max-width:700px){
            #messengerShell{height:100%;min-height:0;grid-template-rows:minmax(0,1fr)}
            #messengerMain{display:none;height:100%;min-height:0;max-height:100%;overflow:hidden}
            #messengerShell.chat-open #messengerMain{display:flex}
            #messageArea{flex:1 1 auto;min-height:0;height:auto;max-height:none;overflow-y:auto}
        }
        @media(max-width:380px){.messenger-compose-tools .messenger-icon-btn{width:29px}}
    </style>
</head>
<body x-data="{page:'messenger',loaded:true,darkMode:false,stickyMenu:false,sidebarToggle:false,scrollTop:false}"
      x-init="darkMode = JSON.parse(localStorage.getItem('darkMode') || 'false'); $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))"
      :class="{'dark bg-gray-900': darkMode === true}">
<div class="flex h-screen overflow-hidden">
    <?php include('sidebar.php'); ?>
    <div class="relative flex flex-1 flex-col overflow-x-hidden overflow-y-auto">
        <main>
            <?php include('header.php'); ?>
            <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6">
                <div class="messenger-page">
                    <section class="messenger-shell" id="messengerShell">
                        <aside class="messenger-sidebar" id="messengerSidebar">
                            <div class="messenger-side-head">
                                <div class="messenger-side-title">
                                    <strong>Messages <span id="conversationCount" style="color:#a0a8b5;font-size:10px;font-weight:650">(0)</span></strong>
                                    <div style="display:flex;gap:6px">
                                        <button type="button" class="messenger-icon-btn" id="newChatBtn" title="New chat"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <button type="button" class="messenger-icon-btn" id="newGroupBtn" title="Create group"><i class="fa-solid fa-user-group"></i></button>
                                    </div>
                                </div>
                                <div class="messenger-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="conversationSearch" placeholder="Search conversations..." autocomplete="off"></div>
                                <div class="messenger-tabs">
                                    <button type="button" class="messenger-tab active" data-section="chats">Chats</button>
                                    <button type="button" class="messenger-tab" data-section="groups">Groups</button>
                                    <button type="button" class="messenger-tab" data-section="media">Media</button>
                                    <button type="button" class="messenger-tab" data-section="profile">Profile</button>
                                </div>
                            </div>
                            <div class="messenger-list" id="conversationList"><div class="messenger-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading conversations...</div></div>
                        </aside>

                        <section class="messenger-main" id="messengerMain">
                            <header class="messenger-chat-head">
                                <div class="messenger-chat-person">
                                    <button type="button" class="messenger-icon-btn messenger-back" id="backToChats" title="Back to conversations"><i class="fa-solid fa-arrow-left"></i></button>
                                    <div class="messenger-avatar" id="activeAvatar"><i class="fa-solid fa-comments"></i></div>
                                    <div style="min-width:0">
                                        <div class="messenger-chat-name" id="activeName">Choose a conversation</div>
                                        <div class="messenger-chat-presence" id="activePresence">Your team workspace</div>
                                    </div>
                                </div>
                                <div class="messenger-chat-actions">
                                    <button type="button" class="messenger-icon-btn desktop-action" id="chatSearchBtn" title="Search in conversation"><i class="fa-solid fa-magnifying-glass"></i></button>
                                    <button type="button" class="messenger-icon-btn" id="refreshChatsBtn" title="Refresh"><i class="fa-solid fa-rotate"></i></button>
                                    <button type="button" class="messenger-icon-btn" id="manageGroupBtn" title="Manage group" style="display:none"><i class="fa-solid fa-users-gear"></i></button>
                                    <button type="button" class="messenger-icon-btn" id="chatMenuBtn" title="More options"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                                    <div class="messenger-menu" id="chatMenu">
                                        <button type="button" id="menuContactInfo"><i class="fa-regular fa-address-card"></i> Contact Info</button>
                                        <button type="button" id="menuClearChat"><i class="fa-regular fa-broom"></i> Clear Chat</button>
                                        <button type="button" id="menuDeleteChat"><i class="fa-regular fa-trash-can"></i> Delete Chat</button>
                                        <button type="button" id="menuViewProfile"><i class="fa-regular fa-user"></i> View Profile</button>
                                    </div>
                                </div>
                            </header>
                            <div class="messenger-messages" id="messageArea">
                                <div class="messenger-welcome">
                                    <div class="messenger-welcome-icon"><i class="fa-solid fa-comments"></i></div>
                                    <h3>Good conversations build stronger businesses.</h3>
                                    <p>Select a conversation on the left, start a private chat with a colleague, or create a group for your team.</p>
                                </div>
                            </div>
                            <div class="messenger-live-activity" id="liveActivity" aria-live="polite" style="display:none"><span class="activity-dots"><i></i><i></i><i></i></span><span id="liveActivityText"></span></div>
                            <div class="messenger-composer-wrap">
                                <div id="replyPreview" class="messenger-reply-compose" style="display:none"><span id="replyPreviewText"></span><button type="button" id="cancelReply" class="messenger-icon-btn"><i class="fa-solid fa-xmark"></i></button></div>
                                <div class="messenger-attachment-preview" id="attachmentPreview"><span id="attachmentPreviewName"></span><button type="button" class="messenger-icon-btn" id="removeAttachment" style="width:25px;height:25px"><i class="fa-solid fa-xmark"></i></button></div>
                                <form class="messenger-composer" id="messageForm" enctype="multipart/form-data">
                                    <div class="messenger-compose-tools">
                                        <button type="button" class="messenger-icon-btn" id="attachButton" title="Attach a file"><i class="fa-solid fa-paperclip"></i></button>
                                        <button type="button" class="messenger-icon-btn" id="recordButton" title="Record voice message"><i class="fa-solid fa-microphone"></i></button>
                                    </div>
                                    <input type="file" id="attachmentInput" name="attachment" hidden accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z">
                                    <textarea id="messageInput" class="messenger-compose-input" rows="1" placeholder="Write a message..." disabled></textarea>
                                    <button type="submit" class="messenger-send" id="sendButton" disabled title="Send message"><i class="fa-solid fa-paper-plane"></i></button>
                                </form>
                                <div class="messenger-composer-hint" id="composerHint">Select a conversation to start messaging · Enter to send, Shift + Enter for a new line</div>
                            </div>
                        </section>
                    </section>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="messenger-modal-backdrop" id="directModal">
    <div class="messenger-modal">
        <div class="messenger-modal-head"><strong>Start a private chat</strong><button type="button" class="messenger-icon-btn close-modal"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="messenger-tabs" style="margin:0 0 12px">
            <button type="button" class="messenger-tab active" id="employeePickerTab">Employees</button>
            <button type="button" class="messenger-tab" id="customerPickerTab">Customers <span id="customerPickerCount"></span></button>
            <button type="button" class="messenger-tab" id="supportPickerTab">Support</button>
        </div>
        <div id="employeePickerPanel">
            <label for="directUserSelect">Choose a colleague</label>
            <select id="directUserSelect" style="width:100%;height:41px;padding:8px 10px;border:1px solid #dfe3e9;border-radius:10px;background:#fafbfc;color:#263041;font-size:11px"><option value="">Select a colleague</option></select>
            <button type="button" class="messenger-modal-submit" id="startDirectChat">Open employee conversation</button>
        </div>
        <div id="supportPickerPanel" style="display:none"><div id="supportPickerList" class="messenger-member-picker"><div class="messenger-loading">Loading support team...</div></div></div>
        <div id="customerPickerPanel" style="display:none">
            <label for="customerSearchInput">Find a customer</label>
            <input type="search" id="customerSearchInput" placeholder="Search customer name, email or phone..." autocomplete="off">
            <div class="messenger-member-picker" id="customerPickerList" style="max-height:280px;margin-top:10px"><div class="messenger-loading">Loading customers...</div></div>
        </div>
    </div>
</div>

<div class="messenger-modal-backdrop" id="groupModal">
    <div class="messenger-modal">
        <div class="messenger-modal-head"><strong>Create a team group</strong><button type="button" class="messenger-icon-btn close-modal"><i class="fa-solid fa-xmark"></i></button></div>
        <label for="groupTitle">Group name</label>
        <input type="text" id="groupTitle" maxlength="150" placeholder="e.g. Operations Team">
        <label>Choose group members</label>
        <div class="messenger-member-picker" id="groupMemberPicker"><div class="messenger-loading">Loading team members...</div></div>
        <button type="button" class="messenger-modal-submit" id="createGroupSubmit">Create group</button>
    </div>
</div>

<div class="messenger-modal-backdrop" id="manageGroupModal"><div class="messenger-modal"><div class="messenger-modal-head"><strong>Manage group members</strong><button type="button" class="messenger-icon-btn close-modal"><i class="fa-solid fa-xmark"></i></button></div><div id="groupCurrentMembers" class="messenger-member-picker"></div><label>Add employees or customers</label><div id="groupAddPicker" class="messenger-member-picker"></div><button type="button" class="messenger-modal-submit" id="saveGroupMembers">Add selected members</button></div></div>
<div class="messenger-modal-backdrop" id="profileModal">
    <div class="messenger-modal">
        <div class="messenger-modal-head"><strong>Employee information</strong><button type="button" class="messenger-icon-btn close-modal"><i class="fa-solid fa-xmark"></i></button></div>
        <div id="profileContent"><div class="messenger-loading">Loading profile...</div></div>
    </div>
</div>
<div class="messenger-toast" id="messengerToast"></div>

<?php include('footer.php'); ?>
<script defer src="bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = <?= json_encode($csrfToken) ?>;
    const $ = id => document.getElementById(id);
    const shell = $('messengerShell');
    const list = $('conversationList');
    const messageArea = $('messageArea');
    const messageInput = $('messageInput');
    const sendButton = $('sendButton');
    const attachmentInput = $('attachmentInput');
    let people = [];
    let customers = [];
    let conversations = [];
    let activeConversation = null;
    let activeMessages = [];
    let currentFilter = 'all';
    let activeSection = 'chats';
    let pollTimer = null;
    let activityTimer = null;
    let typingStopTimer = null;
    let lastActivitySent = "none";
    let recorder = null;
    let recordChunks = [];
    let recording = false;
    let selectedFile = null;
    let activeProfileUserId = null;
    let replyToMessageId = 0;

    function avatarMarkup(person, group = false) {
        if (group) return '<i class="fa-solid fa-user-group"></i>';
        const src = person && person.avatar ? escapeHtml(person.avatar) : '';
        return src ? `<img src="${src}" alt="" onerror="this.remove();this.parentNode.textContent='${escapeHtml(initials(person.display_name || person.username))}'">` : escapeHtml(initials(person && (person.display_name || person.username)));
    }
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
    }
    async function showProfile(userId) {
        if (!userId) return;
        activeProfileUserId = Number(userId);
        $('profileContent').innerHTML = '<div class="messenger-loading">Loading employee information...</div>';
        showModal('profileModal');
        try {
            const data = await request(`messenger.php?action=user_profile&user_id=${Number(userId)}`);
            const p = data.profile || {};
            const avatar = p.avatar ? `<img src="${escapeHtml(p.avatar)}" alt="${escapeHtml(p.display_name)}">` : escapeHtml(initials(p.display_name));
            const fields = [['Username',p.username],['Email',p.email],['Phone',p.phone],['Role',p.role],['Address',p.address],['Account status',p.status],['Employment status',p.employment_status],['Date joined',p.joined_at]];
            $('profileContent').innerHTML = `<div class="messenger-profile-card"><div class="profile-avatar">${avatar}</div><div><strong style="display:block;color:#172033;font-size:15px">${escapeHtml(p.display_name)}</strong><span style="color:#98a1af;font-size:10px">${escapeHtml(p.role || 'Team member')}</span></div></div><div class="profile-details">${fields.map(([label,value])=>`<div class="profile-field"><small>${escapeHtml(label)}</small><strong>${escapeHtml(value || 'Not provided')}</strong></div>`).join('')}</div>`;
        } catch (e) { $('profileContent').textContent = e.message; }
    }
    function initials(name) {
        const parts = String(name || '?').trim().split(/\s+/).slice(0,2);
        return parts.map(p => p.charAt(0)).join('').toUpperCase() || '?';
    }
    function toast(message) {
        const el = $('messengerToast');
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(toast.timer);
        toast.timer = setTimeout(() => el.classList.remove('show'), 3200);
    }
    async function request(url, options = {}) {
        const response = await fetch(url, options);
        const data = await response.json().catch(() => ({ok:false,error:'The server returned an invalid response.'}));
        if (!response.ok || !data.ok) throw new Error(data.error || 'Request failed.');
        return data;
    }
    function postData(action, fields = {}) {
        const form = new FormData();
        form.append('action', action);
        form.append('csrf_token', csrf);
        Object.entries(fields).forEach(([key,value]) => {
            if (Array.isArray(value)) value.forEach(v => form.append(key + '[]', v));
            else form.append(key, value);
        });
        return request('messenger.php', {method:'POST',body:form});
    }
    function timeLabel(value) {
        if (!value) return '';
        const d = new Date(String(value).replace(' ','T'));
        if (Number.isNaN(d.getTime())) return '';
        const today = new Date();
        if (d.toDateString() === today.toDateString()) return d.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
        return d.toLocaleDateString([], {day:'2-digit',month:'short'});
    }
    function previewText(conv) {
        if (!conv.last_message_id) return 'Start the conversation';
        if (conv.last_type && conv.last_type !== 'text') {
            const labels = {image:'Photo',video:'Video',audio:'Voice message',file:'Attachment'};
            return (Number(conv.last_sender_id) === Number(window.messengerUserId) ? 'You: ' : '') + (labels[conv.last_type] || 'Attachment');
        }
        return (Number(conv.last_sender_id) === Number(window.messengerUserId) ? 'You: ' : '') + (conv.last_body || '');
    }
    function presenceLabel(person) {
        if (!person) return 'Presence unavailable';
        if (person.is_online) return '● Active';
        if (!person.last_seen_at) return 'Last seen unavailable';
        const seen = new Date(String(person.last_seen_at).replace(' ', 'T'));
        if (Number.isNaN(seen.getTime())) return 'Last seen unavailable';
        const diff = Math.max(0, Math.floor((Date.now() - seen.getTime()) / 60000));
        if (diff < 1) return 'Last seen just now';
        if (diff < 60) return `Last seen ${diff} min ago`;
        const hours = Math.floor(diff / 60);
        if (hours < 24) return `Last seen ${hours} hr${hours===1?'':'s'} ago`;
        return `Last seen ${seen.toLocaleDateString([], {day:'numeric',month:'short'})} ${seen.toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}`;
    }
    function renderConversations() {
        const query = $('conversationSearch').value.trim().toLowerCase();
        const filtered = conversations.filter(c => {
            const name = String(c.display_title || c.title || '').toLowerCase();
            const preview = previewText(c).toLowerCase();
            if (query && !name.includes(query) && !preview.includes(query)) return false;
            if (currentFilter === 'unread' && !Number(c.unread_count)) return false;
            if (currentFilter === 'groups' && c.conversation_type !== 'group') return false;
            return true;
        });
        $('conversationCount').textContent = '(' + conversations.length + ')';
        if (!filtered.length) {
            list.innerHTML = '<div class="messenger-empty-list"><i class="fa-regular fa-comments"></i>' +
                (conversations.length ? 'No conversations match this view.' : 'No conversations yet.<br>Start a private chat or create a group.') + '</div>';
            return;
        }
        list.innerHTML = filtered.map(c => {
            const isGroup = c.conversation_type === 'group';
            const name = c.display_title || c.title || 'Conversation';
            const unread = Number(c.unread_count || 0);
            const active = activeConversation && Number(activeConversation.id) === Number(c.id);
            return `<button type="button" class="messenger-list-item ${active?'active':''}" data-conversation="${Number(c.id)}">
                <div class="messenger-avatar ${isGroup?'group':''}" ${!isGroup && c.other_user_id ? `data-profile="${Number(c.other_user_id)}"` : ''}>${isGroup?'<i class="fa-solid fa-user-group"></i>':avatarMarkup((c.members||[]).find(m=>Number(m.id)===Number(c.other_user_id)) || people.find(p=>Number(p.id)===Number(c.other_user_id)))}${!isGroup && c.other_user_id && (people.find(p=>Number(p.id)===Number(c.other_user_id))?.is_online) ? '<span class="presence-dot" title="Active"></span>' : ''}</div>
                <div class="messenger-list-copy">
                    <div class="messenger-list-top"><span class="messenger-list-name">${escapeHtml(name)}${!isGroup && c.other_user_id ? `<small class="${people.find(p=>Number(p.id)===Number(c.other_user_id))?.is_online?'presence-online':'presence-offline'}" style="display:block;font-size:9px;font-weight:650;margin-top:2px">${escapeHtml(presenceLabel(people.find(p=>Number(p.id)===Number(c.other_user_id))))}</small>` : ''}</span><span class="messenger-list-time">${escapeHtml(timeLabel(c.last_message_at || c.updated_at))}</span></div>
                    <div class="messenger-list-top"><span class="messenger-list-preview">${escapeHtml(previewText(c))}</span>${unread?`<span class="messenger-unread">${unread>99?'99+':unread}</span>`:''}</div>
                </div>
            </button>`;
        }).join('');
        list.querySelectorAll('[data-conversation]').forEach(button => {
            button.addEventListener('click', () => openConversation(Number(button.dataset.conversation)));
        });
    }
    function renderPeople() {
        $('directUserSelect').innerHTML = '<option value="">Select a colleague</option>' +
            people.map(p => `<option value="${Number(p.id)}">${escapeHtml(p.display_name || p.username)}${p.role?' · '+escapeHtml(p.role):''}</option>`).join('');
        const groupOptions = [
            ...people.map(p => ({...p, participant_type:'employee', picker_value:'employee:'+Number(p.id), picker_label:p.display_name || p.username, picker_meta:p.role || 'Employee'})),
            ...customers.map(c => ({...c, participant_type:'customer', picker_value:'customer:'+Number(c.id), picker_label:c.display_name, picker_meta:'Customer'}))
        ];
        $('groupMemberPicker').innerHTML = groupOptions.length ? groupOptions.map(p =>
            `<label class="messenger-member-option"><input type="checkbox" value="${escapeHtml(p.picker_value)}"><span style="display:flex;align-items:center;gap:8px"><span class="messenger-avatar" style="width:28px;height:28px;flex-basis:28px;border-radius:9px;font-size:9px">${p.participant_type==='employee'?avatarMarkup(p):escapeHtml(initials(p.picker_label))}</span>${escapeHtml(p.picker_label)}</span><small style="margin-left:auto;color:#9aa3b1">${escapeHtml(p.picker_meta)}</small></label>`
        ).join('') : '<div class="messenger-loading">No other participants are available in this location.</div>';
        $('customerPickerCount').textContent = customers.length ? `(${customers.length})` : '(0)';
        $('supportPickerList').innerHTML = people.filter(p => /support|customer care|helpdesk/i.test(String(p.role||''))).map(p => `<div class="messenger-member-option" style="justify-content:space-between"><span><strong>${escapeHtml(p.display_name||p.username)}</strong><small style="display:block;color:#929baa">${escapeHtml(p.role||'Support')}</small></span><button type="button" class="messenger-icon-btn" data-start-support="${Number(p.id)}"><i class="fa-solid fa-headset"></i></button></div>`).join('') || '<div class="messenger-loading">No support users are configured. Assign a Support or Customer Care role to an employee.</div>';
        $('supportPickerList').querySelectorAll('[data-start-support]').forEach(btn => btn.addEventListener('click', async()=>{try{const d=await postData('create_direct',{user_id:Number(btn.dataset.startSupport)});hideModals();await refreshBootstrap();await openConversation(d.conversation_id);}catch(e){toast(e.message);}}));
        renderCustomerPicker();
    }
    function renderCustomerPicker() {
        const query = ($('customerSearchInput')?.value || '').trim().toLowerCase();
        const matches = customers.filter(c => [c.display_name,c.email,c.phone].join(' ').toLowerCase().includes(query));
        $('customerPickerList').innerHTML = matches.length ? matches.map(c =>
            `<div class="messenger-member-option" style="justify-content:space-between;gap:10px"><span style="min-width:0"><strong style="display:block;color:#344054">${escapeHtml(c.display_name)}</strong><small style="display:block;color:#929baa">${escapeHtml(c.email || c.phone || 'CRM customer')}</small></span><button type="button" class="messenger-icon-btn" data-start-customer="${Number(c.id)}" title="Start customer chat"><i class="fa-solid fa-message"></i></button></div>`
        ).join('') : '<div class="messenger-loading">No customers found in your company/location.</div>';
        $('customerPickerList').querySelectorAll('[data-start-customer]').forEach(btn => btn.addEventListener('click', async () => {
            const customerId = Number(btn.dataset.startCustomer);
            try {
                const data = await postData('create_customer_chat', {customer_id:customerId});
                hideModals(); await refreshBootstrap(); await openConversation(data.conversation_id);
            } catch (e) { toast(e.message); }
        }));
    }
    function renderMessages(messages, preserveScroll = false) {
        const nearBottom = messageArea.scrollHeight - messageArea.scrollTop - messageArea.clientHeight < 100;
        if (!messages.length) {
            messageArea.innerHTML = '<div class="messenger-welcome"><div class="messenger-welcome-icon"><i class="fa-regular fa-paper-plane"></i></div><h3>Start the conversation</h3><p>Send a message, share a document or record a voice note.</p></div>';
            return;
        }
        let previousDay = '';
        const html = messages.map(m => {
            const day = String(m.created_at || '').substring(0,10);
            let dayHtml = '';
            if (day && day !== previousDay) {
                previousDay = day;
                const d = new Date(day + 'T12:00:00');
                dayHtml = `<div class="message-day"><span>${escapeHtml(d.toLocaleDateString([], {weekday:'short',day:'numeric',month:'short',year:'numeric'}))}</span></div>`;
            }
            const mine = (m.sender_type || 'employee') === 'employee' && Number(m.sender_id) === Number(window.messengerUserId);
            let attachment = '';
            const download = `messenger.php?action=download&message_id=${Number(m.id)}`;
            if (m.message_type === 'image') {
                attachment = `<a href="${download}" target="_blank" rel="noopener"><img class="message-attachment image" src="${download}" alt="${escapeHtml(m.attachment_name || 'Image')}"></a>`;
            } else if (m.message_type === 'video') {
                attachment = `<video class="message-attachment video" controls preload="metadata" src="${download}"></video>`;
            } else if (m.message_type === 'audio') {
                attachment = `<audio class="message-attachment" controls preload="metadata" src="${download}" style="width:min(270px,100%)"></audio>`;
            } else if (m.message_type === 'file') {
                attachment = `<a class="message-file" href="${download}" target="_blank" rel="noopener"><i class="fa-solid fa-file-arrow-down"></i><span>${escapeHtml(m.attachment_name || 'Download attachment')}</span></a>`;
            }
            const senderAvatar = m.sender_avatar ? `<img src="${escapeHtml(m.sender_avatar)}" alt="" onerror="this.remove()">` : `<span class="message-mini-avatar-fallback">${escapeHtml(initials(m.sender_name || 'Team member'))}</span>`;
            const sender = (activeConversation && (activeConversation.conversation_type === 'group' || m.sender_type === 'customer')) ? `<div class="message-sender" data-profile="${Number(m.sender_id)}" style="cursor:pointer;display:flex;align-items:center;gap:6px"><span class="message-mini-avatar">${senderAvatar}</span>${escapeHtml(m.sender_name || (m.sender_type === 'customer' ? 'Customer' : 'Team member'))}${m.sender_type === 'customer' ? ' · Customer' : ''}</div>` : '';
            const reply = m.reply_to_message_id ? `<button type="button" class="message-reply-preview" data-jump-message="${Number(m.reply_to_message_id)}" title="Jump to the original message"><small><i class="fa-solid fa-reply"></i> Replying to message #${Number(m.reply_to_message_id)} · Click to view</small><div>${escapeHtml(m.reply_body || 'Original message')}</div></button>` : '';
            const body = m.body ? `<div class="message-body">${escapeHtml(m.body)}</div>` : '';
            const actions = `<div class="message-actions"><button type="button" data-message-reply="${Number(m.id)}" title="Reply"><i class="fa-solid fa-reply"></i></button><button type="button" data-message-forward="${Number(m.id)}" title="Forward"><i class="fa-solid fa-share"></i></button>${mine?`<button type="button" data-message-delete="${Number(m.id)}" title="Delete"><i class="fa-regular fa-trash-can"></i></button>`:''}</div>`;
            return `${dayHtml}<div class="message-row ${mine?'mine':''}" id="message-${Number(m.id)}" data-message-id="${Number(m.id)}"><div class="message-bubble">${sender}${reply}${attachment}${body}<div class="message-meta"><span>${escapeHtml(timeLabel(m.created_at))}</span>${mine?'<i class="fa-solid fa-check" title="Sent"></i>':''}</div>${actions}</div></div>`;
        }).join('');
        messageArea.innerHTML = html;
        if (!preserveScroll || nearBottom) messageArea.scrollTop = messageArea.scrollHeight;
    }




    async function jumpToMessage(messageId) {
        messageId = Number(messageId || 0);
        if (!messageId || !activeConversation) return;
        let target = document.getElementById(`message-${messageId}`);
        if (!target) {
            try {
                const data = await request(`messenger.php?action=messages&conversation_id=${Number(activeConversation.id)}&target_message_id=${messageId}`);
                const found = (data.messages || []).some(m => Number(m.id) === messageId);
                if (!found) return toast('The original message is no longer available in this chat.');
                activeMessages = data.messages || [];
                // Re-render the target window, then scroll the message container itself (not the page).
                renderMessages(activeMessages, true);
            } catch (e) { return toast(e.message || 'Could not find the original message.'); }
        }
        const scrollToOriginal = () => {
            target = document.getElementById(`message-${messageId}`);
            if (!target) return false;
            const areaRect = messageArea.getBoundingClientRect();
            const targetRect = target.getBoundingClientRect();
            const targetTop = messageArea.scrollTop + targetRect.top - areaRect.top;
            messageArea.scrollTo({top: Math.max(0, targetTop - (messageArea.clientHeight / 2) + (target.offsetHeight / 2)), behavior:'smooth'});
            target.classList.remove('message-jump-highlight');
            void target.offsetWidth;
            target.classList.add('message-jump-highlight');
            return true;
        };
        // Two frames allow images/fonts and the replacement message list to settle before measuring.
        requestAnimationFrame(() => requestAnimationFrame(() => {
            if (!scrollToOriginal()) toast('The original message could not be positioned. Refresh the conversation and try again.');
        }));
    }
    messageArea.addEventListener('click', e => {
        const replyPreview = e.target.closest('[data-jump-message]');
        if (replyPreview) jumpToMessage(replyPreview.dataset.jumpMessage);
    });
    async function loadMessages(silent = false) {
        if (!activeConversation) return;
        try {
            const data = await request(`messenger.php?action=messages&conversation_id=${Number(activeConversation.id)}`);
            const incoming = data.messages || [];
            const oldLast = activeMessages.length ? Number(activeMessages[activeMessages.length-1].id) : 0;
            const newLast = incoming.length ? Number(incoming[incoming.length-1].id) : 0;
            if (!silent || oldLast !== newLast || incoming.length !== activeMessages.length) {
                activeMessages = incoming;
                renderMessages(activeMessages, silent);
            }
            const conv = conversations.find(c => Number(c.id) === Number(activeConversation.id));
            if (conv) conv.unread_count = 0;
            renderConversations();
        } catch (e) {
            if (!silent) toast(e.message);
        }
    }
    async function openConversation(id) {
        const conv = conversations.find(c => Number(c.id) === Number(id));
        if (!conv) return;
        activeConversation = conv;
        activeMessages = [];
        shell.classList.add('chat-open');
        const title = conv.display_title || conv.title || 'Conversation';
        const isGroup = conv.conversation_type === 'group';
        $('activeAvatar').className = 'messenger-avatar' + (isGroup?' group':'');
        $('activeAvatar').innerHTML = isGroup ? '<i class="fa-solid fa-user-group"></i>' : avatarMarkup(customers.find(p=>Number(p.id)===Number(conv.other_customer_id)) || people.find(p=>Number(p.id)===Number(conv.other_user_id)));
        $('activeAvatar').onclick = () => { if (!isGroup && conv.other_user_id) showProfile(conv.other_user_id); else if (conv.other_customer_id) toast('Customer contact: ' + (customers.find(c=>Number(c.id)===Number(conv.other_customer_id))?.email || 'CRM customer')); else if (isGroup) toast('Open a member profile from the group conversation.'); };
        $('activeName').textContent = title;
        const myGroupRole = (conv.members || []).find(m => m.participant_type === 'employee' && Number(m.id) === Number(window.messengerUserId))?.participant_role;
        $('manageGroupBtn').style.display = (isGroup && myGroupRole === 'admin') ? '' : 'none';
        const otherPerson = !isGroup && conv.other_user_id ? (people.find(p=>Number(p.id)===Number(conv.other_user_id)) || (conv.members||[]).find(m=>Number(m.id)===Number(conv.other_user_id))) : null;
        $('activePresence').textContent = isGroup ? `${(conv.members || []).length} participants` : (conv.other_customer_id ? 'CRM customer · presence unavailable from employee portal' : presenceLabel(otherPerson));
        $('activePresence').className = 'messenger-chat-presence ' + (otherPerson?.is_online ? 'presence-online' : 'presence-offline');
        messageInput.disabled = false;
        sendButton.disabled = false;
        $('composerHint').textContent = 'Enter to send · Shift + Enter for a new line';
        messageArea.innerHTML = '<div class="messenger-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Loading messages...</div>';
        renderConversations();
        await loadMessages();
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => loadMessages(true), 2500);
        if (activityTimer) clearInterval(activityTimer);
        activityTimer = setInterval(refreshActivity, 1200);
        lastActivitySent = 'none';
        refreshActivity();
        updateActivePresence();
        messageInput.focus();
    }
    async function sendActivity(activity) {
        if (!activeConversation) return;
        if (activity === lastActivitySent && activity !== 'none') return;
        lastActivitySent = activity;
        try { await postData('activity_update',{conversation_id:Number(activeConversation.id),activity}); } catch (e) { /* next activity event retries */ }
    }
    function showLiveActivity(items) {
        const box = $('liveActivity');
        const text = $('liveActivityText');
        if (!items || !items.length) { box.style.display='none'; text.textContent=''; return; }
        const names = items.map(x => x.display_name || x.username || 'Someone');
        const activity = items[0].activity;
        let label = activity === 'typing' ? 'is typing' : activity === 'attaching' ? 'is attaching a file' : 'is uploading a file';
        text.textContent = names.length > 1 ? `${names.slice(0,2).join(' and ')} ${label}${names.length > 2 ? ` and ${names.length-2} others` : ''}…` : `${names[0]} ${label}…`;
        box.style.display='flex';
    }
    async function refreshActivity() {
        if (!activeConversation) { showLiveActivity([]); return; }
        try {
            const data = await request(`messenger.php?action=activity_status&conversation_id=${Number(activeConversation.id)}`);
            showLiveActivity(data.activities || []);
        } catch (e) { /* transient polling errors are harmless */ }
    }
    function updateActivePresence() {
        if (!activeConversation || activeConversation.conversation_type === 'group' || activeConversation.other_customer_id) return;
        const person = people.find(x=>Number(x.id)===Number(activeConversation.other_user_id)) || (activeConversation.members||[]).find(x=>Number(x.id)===Number(activeConversation.other_user_id));
        $('activePresence').textContent = presenceLabel(person);
        $('activePresence').className = 'messenger-chat-presence ' + (person?.is_online ? 'presence-online' : 'presence-offline');
    }
    async function sendPresencePing() {
        try { await postData('presence_ping'); } catch (e) { /* transient network errors are retried on the next heartbeat */ }
    }
    async function refreshBootstrap() {
        try {
            const data = await request('messenger.php?action=bootstrap');
            window.messengerUserId = Number(data.me.id);
            people = data.people || [];
            customers = data.customers || [];
            conversations = data.conversations || [];
            renderPeople();
            renderConversations();
            if (activeConversation) {
                const refreshed = conversations.find(c => Number(c.id) === Number(activeConversation.id));
                if (refreshed) { activeConversation = refreshed; updateActivePresence(); }
                else {
                    activeConversation = null;
                    shell.classList.remove('chat-open');
                    messageArea.innerHTML = '<div class="messenger-welcome"><div class="messenger-welcome-icon"><i class="fa-solid fa-comments"></i></div><h3>Choose a conversation</h3><p>Select a conversation to continue messaging.</p></div>';
                }
            }
        } catch (e) {
            list.innerHTML = `<div class="messenger-empty-list"><i class="fa-solid fa-triangle-exclamation"></i>${escapeHtml(e.message)}</div>`;
        }
    }
    async function renderMedia() {
        list.style.display = '';
        list.innerHTML = '<div class="messenger-loading">Loading shared media...</div>';
        try {
            const data = await request('messenger.php?action=media');
            const items = data.items || [];
            if (!items.length) { list.innerHTML = '<div class="messenger-empty-list"><i class="fa-regular fa-images"></i>No shared media or documents yet.</div>'; return; }
            list.innerHTML = items.map(item => {
                const icon = item.message_type === 'image' ? 'fa-image' : item.message_type === 'video' ? 'fa-video' : item.message_type === 'audio' ? 'fa-headphones' : 'fa-file-lines';
                return `<button type="button" class="messenger-list-item" data-media-conversation="${Number(item.conversation_id)}"><span class="messenger-avatar"><i class="fa-regular ${icon}"></i></span><span class="messenger-list-copy"><span class="messenger-list-name">${escapeHtml(item.attachment_name || 'Attachment')}</span><span class="messenger-list-preview">${escapeHtml(item.title || 'Conversation')} · ${escapeHtml(timeLabel(item.created_at))}</span></span></button>`;
            }).join('');
            list.querySelectorAll('[data-media-conversation]').forEach(btn => btn.addEventListener('click', async () => { const id=Number(btn.dataset.mediaConversation); activeSection='chats'; document.querySelector('[data-section="chats"]').click(); await openConversation(id); }));
        } catch (e) { list.innerHTML = `<div class="messenger-empty-list">${escapeHtml(e.message)}</div>`; }
    }
    function showModal(id) { $(id).classList.add('open'); }
    function hideModals() { document.querySelectorAll('.messenger-modal-backdrop').forEach(m => m.classList.remove('open')); }

    $('newChatBtn').addEventListener('click', () => showModal('directModal'));
    $('customerPickerTab').addEventListener('click', () => { $('customerPickerTab').classList.add('active'); $('employeePickerTab').classList.remove('active'); $('supportPickerTab').classList.remove('active'); $('employeePickerPanel').style.display='none'; $('supportPickerPanel').style.display='none'; $('customerPickerPanel').style.display=''; renderCustomerPicker(); });
    $('supportPickerTab').addEventListener('click', () => { $('supportPickerTab').classList.add('active'); $('employeePickerTab').classList.remove('active'); $('customerPickerTab').classList.remove('active'); $('employeePickerPanel').style.display='none'; $('customerPickerPanel').style.display='none'; $('supportPickerPanel').style.display=''; });
    $('employeePickerTab').addEventListener('click', () => { $('employeePickerTab').classList.add('active'); $('customerPickerTab').classList.remove('active'); $('supportPickerTab').classList.remove('active'); $('employeePickerPanel').style.display=''; $('customerPickerPanel').style.display='none'; $('supportPickerPanel').style.display='none'; });
    $('customerSearchInput').addEventListener('input', renderCustomerPicker);
    $('newGroupBtn').addEventListener('click', () => showModal('groupModal'));
    document.querySelectorAll('.close-modal').forEach(btn => btn.addEventListener('click', hideModals));
    document.querySelectorAll('.messenger-modal-backdrop').forEach(backdrop => backdrop.addEventListener('click', e => { if (e.target === backdrop) hideModals(); }));
    $('startDirectChat').addEventListener('click', async () => {
        const id = Number($('directUserSelect').value);
        if (!id) return toast('Choose a colleague first.');
        try {
            const data = await postData('create_direct', {user_id:id});
            hideModals();
            await refreshBootstrap();
            await openConversation(data.conversation_id);
        } catch (e) { toast(e.message); }
    });
    $('createGroupSubmit').addEventListener('click', async () => {
        const title = $('groupTitle').value.trim();
        const members = Array.from($('groupMemberPicker').querySelectorAll('input:checked')).map(el => el.value);
        if (!title) return toast('Enter a group name.');
        if (!members.length) return toast('Select at least one group member.');
        try {
            const data = await postData('create_group', {title,members});
            hideModals();
            $('groupTitle').value = '';
            await refreshBootstrap();
            await openConversation(data.conversation_id);
        } catch (e) { toast(e.message); }
    });
    document.querySelectorAll('.messenger-tab').forEach(tab => tab.addEventListener('click', async () => {
        document.querySelectorAll('.messenger-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        activeSection = tab.dataset.section;
        $('conversationSearch').value = '';
        if (activeSection === 'profile') { showProfile(Number(window.messengerUserId)); return; }
        if (activeSection === 'media') { await renderMedia(); return; }
        if (activeSection === 'groups') currentFilter = 'groups';
        else currentFilter = 'all';
        list.style.display = '';
        renderConversations();
    }));
    $('conversationSearch').addEventListener('input', renderConversations);
    $('refreshChatsBtn').addEventListener('click', async () => { await refreshBootstrap(); if (activeConversation) await loadMessages(); });
    $('backToChats').addEventListener('click', () => shell.classList.remove('chat-open'));
    $('attachButton').addEventListener('click', () => {
        if (!activeConversation) return toast('Open a conversation first.');
        attachmentInput.click();
    });
    attachmentInput.addEventListener('change', () => {
        selectedFile = attachmentInput.files[0] || null;
        $('attachmentPreview').style.display = selectedFile ? 'flex' : 'none';
        $('attachmentPreviewName').textContent = selectedFile ? selectedFile.name : '';
        if (selectedFile) sendActivity('attaching'); else sendActivity('none');
    });
    $('removeAttachment').addEventListener('click', () => {
        selectedFile = null;
        attachmentInput.value = '';
        $('attachmentPreview').style.display = 'none';
        sendActivity('none');
    });
    messageInput.addEventListener('input', () => {
        messageInput.style.height = 'auto';
        messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
        if (messageInput.value.trim()) {
            sendActivity('typing');
            clearTimeout(typingStopTimer);
            typingStopTimer = setTimeout(() => sendActivity('none'), 1800);
        } else {
            clearTimeout(typingStopTimer);
            sendActivity('none');
        }
    });
    messageInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $('messageForm').requestSubmit();
        }
    });
    $('messageForm').addEventListener('submit', async e => {
        e.preventDefault();
        if (!activeConversation) return toast('Choose a conversation first.');
        const body = messageInput.value.trim();
        if (!body && !selectedFile) return;
        const form = new FormData();
        form.append('action','send');
        form.append('csrf_token',csrf);
        form.append('conversation_id',activeConversation.id);
        form.append('body',body);
        if (replyToMessageId) form.append('reply_to_message_id', replyToMessageId);
        if (selectedFile) form.append('attachment',selectedFile);
        sendButton.disabled = true;
        clearTimeout(typingStopTimer);
        sendActivity(selectedFile ? 'uploading' : 'none');
        try {
            await request('messenger.php',{method:'POST',body:form});
            messageInput.value = '';
            replyToMessageId = 0; $('replyPreview').style.display='none';
            messageInput.style.height = 'auto';
            selectedFile = null;
            attachmentInput.value = '';
            $('attachmentPreview').style.display = 'none';
            sendActivity('none');
            lastActivitySent = 'none';
            await loadMessages();
            await refreshBootstrap();
        } catch (err) { toast(err.message); }
        finally { sendButton.disabled = false; messageInput.focus(); }
    });
    $('recordButton').addEventListener('click', async () => {
        if (!activeConversation) return toast('Open a conversation first.');
        if (recording && recorder) {
            recorder.stop();
            return;
        }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
            return toast('Voice recording is not supported by this browser.');
        }
        try {
            const stream = await navigator.mediaDevices.getUserMedia({audio:true});
            recordChunks = [];
            recorder = new MediaRecorder(stream);
            recorder.ondataavailable = event => { if (event.data.size) recordChunks.push(event.data); };
            recorder.onstop = async () => {
                stream.getTracks().forEach(track => track.stop());
                recording = false;
                $('recordButton').innerHTML = '<i class="fa-solid fa-microphone"></i>';
                $('recordButton').title = 'Record voice message';
                const blob = new Blob(recordChunks,{type:recorder.mimeType || 'audio/webm'});
                const file = new File([blob],'voice-message.webm',{type:blob.type});
                const form = new FormData();
                form.append('action','send'); form.append('csrf_token',csrf);
                form.append('conversation_id',activeConversation.id); form.append('attachment',file);
                try {
                    sendActivity('uploading');
                    await request('messenger.php',{method:'POST',body:form});
                    sendActivity('none'); lastActivitySent='none';
                    await loadMessages();
                    await refreshBootstrap();
                } catch (err) { toast(err.message); sendActivity('none'); lastActivitySent='none'; }
            };
            recorder.start();
            recording = true;
            sendActivity('attaching');
            $('recordButton').innerHTML = '<i class="fa-solid fa-stop"></i>';
            $('recordButton').title = 'Stop and send voice message';
            toast('Recording voice message… Tap stop to send.');
        } catch (err) { toast('Microphone permission was not granted.'); }
    });
    $('chatSearchBtn').addEventListener('click', async () => {
        if (!activeConversation) return toast('Open a conversation first.');
        const term = prompt('Search all messages in this conversation:');
        if (term === null || term.trim().length < 2) return;
        try {
            const data = await request(`messenger.php?action=search_messages&conversation_id=${Number(activeConversation.id)}&q=${encodeURIComponent(term.trim())}`);
            if (!data.messages.length) return toast('No matching messages found.');
            renderMessages(data.messages);
            toast(`${data.messages.length} matching message(s) found. Refresh the chat to return to the latest messages.`);
        } catch (e) { toast(e.message); }
    });
    $('cancelReply').addEventListener('click',()=>{replyToMessageId=0;$('replyPreview').style.display='none';});
    messageArea.addEventListener('click', async e => {
        const replyBtn=e.target.closest('[data-message-reply]');
        const forwardBtn=e.target.closest('[data-message-forward]');
        const deleteBtn=e.target.closest('[data-message-delete]');
        if(replyBtn){ const id=Number(replyBtn.dataset.messageReply); const m=activeMessages.find(x=>Number(x.id)===id); if(!m)return; replyToMessageId=id; $('replyPreviewText').textContent='Replying to: '+(m.body||m.attachment_name||'Attachment'); $('replyPreview').style.display='flex'; messageInput.focus(); }
        if(forwardBtn){ const id=Number(forwardBtn.dataset.messageForward); const kind=(prompt('Forward to which recipient type? Enter employee or customer:','employee')||'').toLowerCase(); if(!['employee','customer'].includes(kind)) return toast('Enter employee or customer.'); const pool=kind==='customer'?customers:people; if(!pool.length)return toast('No recipients are available in this category.'); const choices=pool.map((p,i)=>`${i+1}. ${p.display_name||p.fullname||p.username} · ID ${p.id}`).join('\n'); const selected=Number(prompt('Choose a recipient by number:\n'+choices)); if(!selected || !pool[selected-1])return; const target=Number(pool[selected-1].id); try{await postData('forward_message',{message_id:id,target_id:target,target_type:kind}); await refreshBootstrap(); toast('Message forwarded.');}catch(err){toast(err.message);} }
        if(deleteBtn){ const id=Number(deleteBtn.dataset.messageDelete); if(!confirm('Delete this message for everyone?'))return; try{await postData('delete_message',{message_id:id,for_everyone:1}); await loadMessages(); toast('Message deleted.');}catch(err){toast(err.message);} }
    });
    $('manageGroupBtn').addEventListener('click',()=>{
        if(!activeConversation || activeConversation.conversation_type!=='group')return;
        const members=activeConversation.members||[];
        $('groupCurrentMembers').innerHTML=members.map(m=>`<div class="messenger-member-option"><span>${escapeHtml(m.display_name||m.username||'Member')} · ${escapeHtml(m.participant_type||'employee')}</span>${Number(m.id)!==Number(window.messengerUserId)?`<button type="button" class="messenger-icon-btn" data-remove-member="${Number(m.id)}" data-remove-type="${escapeHtml(m.participant_type||'employee')}" title="Remove member"><i class="fa-solid fa-user-minus"></i></button>`:''}</div>`).join('')||'No members';
        const opts=[...people.filter(p=>!members.some(m=>m.participant_type==='employee'&&Number(m.id)===Number(p.id))).map(p=>({id:p.id,type:'employee',name:p.display_name||p.username,meta:p.role||'Employee'})),...customers.filter(p=>!members.some(m=>m.participant_type==='customer'&&Number(m.id)===Number(p.id))).map(p=>({id:p.id,type:'customer',name:p.display_name,meta:'Customer'}))];
        $('groupAddPicker').innerHTML=opts.map(p=>`<label class="messenger-member-option"><input type="checkbox" value="${p.type}:${Number(p.id)}"><span>${escapeHtml(p.name)}</span><small style="margin-left:auto">${escapeHtml(p.meta)}</small></label>`).join('')||'<div class="messenger-loading">No additional people available.</div>';
        showModal('manageGroupModal');
    });
    $('groupCurrentMembers').addEventListener('click',async e=>{const b=e.target.closest('[data-remove-member]');if(!b)return;if(!confirm('Remove this member from the group?'))return;try{await postData('remove_group_member',{conversation_id:activeConversation.id,user_id:Number(b.dataset.removeMember),participant_type:b.dataset.removeType});await refreshBootstrap();activeConversation=conversations.find(c=>Number(c.id)===Number(activeConversation.id))||activeConversation;$('manageGroupBtn').click();toast('Member removed.');}catch(err){toast(err.message);}});
    $('saveGroupMembers').addEventListener('click',async()=>{const members=Array.from($('groupAddPicker').querySelectorAll('input:checked')).map(x=>x.value);if(!members.length)return toast('Select at least one person.');try{await postData('add_group_members',{conversation_id:activeConversation.id,members});hideModals();await refreshBootstrap();activeConversation=conversations.find(c=>Number(c.id)===Number(activeConversation.id))||activeConversation;toast('Group members updated.');}catch(err){toast(err.message);}});
    $('chatMenuBtn').addEventListener('click', () => $('chatMenu').classList.toggle('open'));
    document.addEventListener('click', e => { if (!$('chatMenu').contains(e.target) && !$('chatMenuBtn').contains(e.target)) $('chatMenu').classList.remove('open'); });
    $('menuViewProfile').addEventListener('click', () => { $('chatMenu').classList.remove('open'); if (activeConversation?.other_user_id) showProfile(activeConversation.other_user_id); else toast('Select a group member to view their profile.'); });
    $('menuContactInfo').addEventListener('click', () => { $('chatMenu').classList.remove('open'); if (activeConversation?.other_user_id) showProfile(activeConversation.other_user_id); else toast('Group contact information is available through its members.'); });
    $('menuClearChat').addEventListener('click', async () => {
        $('chatMenu').classList.remove('open'); if (!activeConversation) return;
        if (!confirm('Clear this chat from your view? Other participants will keep their messages.')) return;
        try { await postData('clear_chat',{conversation_id:activeConversation.id}); activeMessages=[]; renderMessages([]); await refreshBootstrap(); toast('Chat cleared from your view.'); }
        catch(e){toast(e.message);}
    });
    $('menuDeleteChat').addEventListener('click', async () => {
        $('chatMenu').classList.remove('open'); if (!activeConversation) return;
        if (!confirm('Delete this chat from your conversation list? Other participants will not lose their history.')) return;
        try { await postData('hide_chat',{conversation_id:activeConversation.id}); activeConversation=null; activeMessages=[]; shell.classList.remove('chat-open'); messageInput.disabled=true; sendButton.disabled=true; await refreshBootstrap(); toast('Chat removed from your list.'); }
        catch(e){toast(e.message);}
    });
    document.addEventListener('click', e => { const target=e.target.closest('[data-profile]'); if(target) showProfile(Number(target.dataset.profile)); });
    sendPresencePing();
    refreshBootstrap();
    setInterval(sendPresencePing, 15000);
    setInterval(async () => {
        if (document.visibilityState === 'visible') {
            await refreshBootstrap();
            updateActivePresence();
        }
    }, 20000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') { sendPresencePing(); refreshBootstrap(); } });
    window.addEventListener('beforeunload', () => { if (activeConversation && navigator.sendBeacon) { const f=new FormData(); f.append('action','activity_update'); f.append('csrf_token',csrf); f.append('conversation_id',activeConversation.id); f.append('activity','none'); navigator.sendBeacon('messenger.php',f); } });
});
</script>
</body>
</html>
