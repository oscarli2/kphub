<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function ensureNewsfeedSectionTables($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS newsfeed_sections (
        section_id INT PRIMARY KEY AUTO_INCREMENT,
        facility VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_by INT NULL,
        updated_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_facility_sort (facility, sort_order),
        INDEX idx_created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS newsfeed_section_links (
        link_id INT PRIMARY KEY AUTO_INCREMENT,
        section_id INT NOT NULL,
        label VARCHAR(255) NOT NULL,
        url VARCHAR(500) NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        created_by INT NULL,
        updated_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_section_sort (section_id, sort_order),
        INDEX idx_section_id (section_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function getCurrentFacility($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT facility FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $facility = $stmt->fetchColumn();

    return $facility ? trim($facility) : null;
}

function fetchSections($pdo, $facility) {
    if (empty($facility)) {
        return [];
    }

    $stmt = $pdo->prepare("SELECT section_id, facility, title, sort_order, created_by, updated_by, created_at, updated_at
                           FROM newsfeed_sections
                           WHERE facility = ?
                           ORDER BY sort_order ASC, section_id ASC");
    $stmt->execute([$facility]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($sections as &$section) {
        $linkStmt = $pdo->prepare("SELECT link_id, section_id, label, url, sort_order, created_by, updated_by, created_at, updated_at
                                   FROM newsfeed_section_links
                                   WHERE section_id = ?
                                   ORDER BY sort_order ASC, link_id ASC");
        $linkStmt->execute([$section['section_id']]);
        $section['links'] = $linkStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $sections;
}

function getAllFacilities($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT facility FROM users WHERE facility IS NOT NULL AND TRIM(facility) <> '' ORDER BY facility ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function fetchFacilityCards($pdo, $facilities) {
    $cards = [];
    $currentFacility = getCurrentFacility($pdo);

    foreach ($facilities as $facility) {
        $normalizedFacility = trim((string)$facility);
        if ($normalizedFacility === '') {
            continue;
        }

        $cards[] = [
            'facility' => $normalizedFacility,
            'sections' => fetchSections($pdo, $normalizedFacility),
            'can_manage' => isset($_SESSION['user_id']) && strcasecmp($currentFacility ?? '', $normalizedFacility) === 0
        ];
    }

    return $cards;
}

function getFacilityCardsResponse($pdo, $scope = 'all') {
        if ($scope === 'mine') {
            $currentFacility = getCurrentFacility($pdo);
            return $currentFacility ? fetchFacilityCards($pdo, [$currentFacility]) : [];
        }

        $facilities = getAllFacilities($pdo);

        if (isset($_SESSION['user_id'])) {
            $currentFacility = getCurrentFacility($pdo);
            if ($currentFacility && !in_array($currentFacility, $facilities, true)) {
                array_unshift($facilities, $currentFacility);
            }
        }

        return fetchFacilityCards($pdo, $facilities);
    }

function normalizeSectionOrder($pdo, $facility) {
    $stmt = $pdo->prepare("SELECT section_id FROM newsfeed_sections WHERE facility = ? ORDER BY sort_order ASC, section_id ASC");
    $stmt->execute([$facility]);
    $sectionIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $order = 1;
    foreach ($sectionIds as $sectionId) {
        $updateStmt = $pdo->prepare("UPDATE newsfeed_sections SET sort_order = ? WHERE section_id = ?");
        $updateStmt->execute([$order, $sectionId]);
        $order++;
    }
}

function normalizeLinkOrder($pdo, $sectionId) {
    $stmt = $pdo->prepare("SELECT link_id FROM newsfeed_section_links WHERE section_id = ? ORDER BY sort_order ASC, link_id ASC");
    $stmt->execute([$sectionId]);
    $linkIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $order = 1;
    foreach ($linkIds as $linkId) {
        $updateStmt = $pdo->prepare("UPDATE newsfeed_section_links SET sort_order = ? WHERE link_id = ?");
        $updateStmt->execute([$order, $linkId]);
        $order++;
    }
}

function getSectionForFacility($pdo, $sectionId, $facility) {
    $stmt = $pdo->prepare("SELECT section_id, facility, title, sort_order FROM newsfeed_sections WHERE section_id = ? AND facility = ?");
    $stmt->execute([$sectionId, $facility]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLinkForFacility($pdo, $linkId, $facility) {
    $stmt = $pdo->prepare("SELECT l.link_id, l.section_id, s.facility
                           FROM newsfeed_section_links l
                           JOIN newsfeed_sections s ON s.section_id = l.section_id
                           WHERE l.link_id = ? AND s.facility = ?");
    $stmt->execute([$linkId, $facility]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$action = $_REQUEST['action'] ?? 'list';
$facility = getCurrentFacility($pdo);
$scope = ($_REQUEST['scope'] ?? 'all') === 'mine' ? 'mine' : 'all';

try {
    ensureNewsfeedSectionTables($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if ($action !== 'list') {
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
        }

        jsonResponse([
            'success' => true,
            'facility' => $facility,
            'facility_cards' => getFacilityCardsResponse($pdo, $scope)
        ]);
    }

    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }

    if (empty($facility)) {
        jsonResponse(['success' => false, 'message' => 'Facility not found for current user'], 400);
    }

    $userId = (int)$_SESSION['user_id'];

    switch ($action) {
        case 'save_section':
            $sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
            $title = trim($_POST['title'] ?? '');

            if ($title === '') {
                jsonResponse(['success' => false, 'message' => 'Section title is required'], 400);
            }

            if ($sectionId > 0) {
                $section = getSectionForFacility($pdo, $sectionId, $facility);
                if (!$section) {
                    jsonResponse(['success' => false, 'message' => 'Section not found'], 404);
                }

                $stmt = $pdo->prepare("UPDATE newsfeed_sections SET title = ?, updated_by = ? WHERE section_id = ? AND facility = ?");
                $stmt->execute([$title, $userId, $sectionId, $facility]);
            } else {
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM newsfeed_sections WHERE facility = ?");
                $stmt->execute([$facility]);
                $nextOrder = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO newsfeed_sections (facility, title, sort_order, created_by, updated_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$facility, $title, $nextOrder, $userId, $userId]);
            }

            jsonResponse([
                'success' => true,
                'message' => 'Section saved successfully',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        case 'delete_section':
            $sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
            if ($sectionId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Section ID is required'], 400);
            }

            $section = getSectionForFacility($pdo, $sectionId, $facility);
            if (!$section) {
                jsonResponse(['success' => false, 'message' => 'Section not found'], 404);
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM newsfeed_section_links WHERE section_id = ?")->execute([$sectionId]);
            $pdo->prepare("DELETE FROM newsfeed_sections WHERE section_id = ? AND facility = ?")->execute([$sectionId, $facility]);
            normalizeSectionOrder($pdo, $facility);
            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Section deleted successfully',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        case 'move_section':
            $sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
            $direction = $_POST['direction'] ?? '';

            if ($sectionId <= 0 || !in_array($direction, ['up', 'down'], true)) {
                jsonResponse(['success' => false, 'message' => 'Invalid move request'], 400);
            }

            $stmt = $pdo->prepare("SELECT section_id, sort_order FROM newsfeed_sections WHERE facility = ? ORDER BY sort_order ASC, section_id ASC");
            $stmt->execute([$facility]);
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $currentIndex = null;
            foreach ($sections as $index => $section) {
                if ((int)$section['section_id'] === $sectionId) {
                    $currentIndex = $index;
                    break;
                }
            }

            if ($currentIndex === null) {
                jsonResponse(['success' => false, 'message' => 'Section not found'], 404);
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
            if ($targetIndex < 0 || $targetIndex >= count($sections)) {
                jsonResponse(['success' => true, 'message' => 'Section order unchanged', 'sections' => fetchSections($pdo, $facility)]);
            }

            $pdo->beginTransaction();
            $currentSection = $sections[$currentIndex];
            $targetSection = $sections[$targetIndex];

            $updateStmt = $pdo->prepare("UPDATE newsfeed_sections SET sort_order = ? WHERE section_id = ? AND facility = ?");
            $updateStmt->execute([$targetSection['sort_order'], $currentSection['section_id'], $facility]);
            $updateStmt->execute([$currentSection['sort_order'], $targetSection['section_id'], $facility]);
            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Section order updated',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        case 'save_link':
            $sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
            $linkId = isset($_POST['link_id']) ? (int)$_POST['link_id'] : 0;
            $label = trim($_POST['label'] ?? '');
            $url = trim($_POST['url'] ?? '');

            if ($sectionId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Section ID is required'], 400);
            }

            if ($label === '' || $url === '') {
                jsonResponse(['success' => false, 'message' => 'Link label and URL are required'], 400);
            }

            $url = filter_var($url, FILTER_SANITIZE_URL);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                jsonResponse(['success' => false, 'message' => 'Please enter a valid URL'], 400);
            }

            $section = getSectionForFacility($pdo, $sectionId, $facility);
            if (!$section) {
                jsonResponse(['success' => false, 'message' => 'Section not found'], 404);
            }

            if ($linkId > 0) {
                $link = getLinkForFacility($pdo, $linkId, $facility);
                if (!$link || (int)$link['section_id'] !== $sectionId) {
                    jsonResponse(['success' => false, 'message' => 'Link not found'], 404);
                }

                $stmt = $pdo->prepare("UPDATE newsfeed_section_links SET label = ?, url = ?, updated_by = ? WHERE link_id = ?");
                $stmt->execute([$label, $url, $userId, $linkId]);
            } else {
                $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM newsfeed_section_links WHERE section_id = ?");
                $stmt->execute([$sectionId]);
                $nextOrder = (int)$stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO newsfeed_section_links (section_id, label, url, sort_order, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sectionId, $label, $url, $nextOrder, $userId, $userId]);
            }

            jsonResponse([
                'success' => true,
                'message' => 'Link saved successfully',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        case 'delete_link':
            $linkId = isset($_POST['link_id']) ? (int)$_POST['link_id'] : 0;
            if ($linkId <= 0) {
                jsonResponse(['success' => false, 'message' => 'Link ID is required'], 400);
            }

            $link = getLinkForFacility($pdo, $linkId, $facility);
            if (!$link) {
                jsonResponse(['success' => false, 'message' => 'Link not found'], 404);
            }

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM newsfeed_section_links WHERE link_id = ?")->execute([$linkId]);
            normalizeLinkOrder($pdo, (int)$link['section_id']);
            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Link deleted successfully',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        case 'move_link':
            $linkId = isset($_POST['link_id']) ? (int)$_POST['link_id'] : 0;
            $direction = $_POST['direction'] ?? '';

            if ($linkId <= 0 || !in_array($direction, ['up', 'down'], true)) {
                jsonResponse(['success' => false, 'message' => 'Invalid move request'], 400);
            }

            $link = getLinkForFacility($pdo, $linkId, $facility);
            if (!$link) {
                jsonResponse(['success' => false, 'message' => 'Link not found'], 404);
            }

            $stmt = $pdo->prepare("SELECT link_id, sort_order FROM newsfeed_section_links WHERE section_id = ? ORDER BY sort_order ASC, link_id ASC");
            $stmt->execute([(int)$link['section_id']]);
            $links = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $currentIndex = null;
            foreach ($links as $index => $sectionLink) {
                if ((int)$sectionLink['link_id'] === $linkId) {
                    $currentIndex = $index;
                    break;
                }
            }

            if ($currentIndex === null) {
                jsonResponse(['success' => false, 'message' => 'Link not found'], 404);
            }

            $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
            if ($targetIndex < 0 || $targetIndex >= count($links)) {
                jsonResponse(['success' => true, 'message' => 'Link order unchanged', 'sections' => fetchSections($pdo, $facility)]);
            }

            $pdo->beginTransaction();
            $currentLink = $links[$currentIndex];
            $targetLink = $links[$targetIndex];

            $updateStmt = $pdo->prepare("UPDATE newsfeed_section_links SET sort_order = ? WHERE link_id = ?");
            $updateStmt->execute([$targetLink['sort_order'], $currentLink['link_id']]);
            $updateStmt->execute([$currentLink['sort_order'], $targetLink['link_id']]);
            $pdo->commit();

            jsonResponse([
                'success' => true,
                'message' => 'Link order updated',
                'facility_cards' => getFacilityCardsResponse($pdo, $scope)
            ]);
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Newsfeed sections error: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Failed to process newsfeed sections'], 500);
}
?>