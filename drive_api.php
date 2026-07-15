<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

function jsonResponse($data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function normalizeDriveId(string $id): string
{
    return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
}

function getDriveBrowseCacheTtl(): int
{
    $ttl = (int)(getenv('DRIVE_BROWSE_CACHE_TTL') ?: 45);
    if ($ttl < 30) {
        return 30;
    }
    if ($ttl > 60) {
        return 60;
    }
    return $ttl;
}

function getDriveCacheDir(): string
{
    $dir = __DIR__ . '/logs/drive_cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function getDriveCacheFilePath(string $cacheKey): string
{
    return getDriveCacheDir() . '/' . sha1($cacheKey) . '.json';
}

function driveCacheRead(string $cacheKey, int $ttl): ?array
{
    $file = getDriveCacheFilePath($cacheKey);
    if (!is_file($file)) {
        return null;
    }

    $content = json_decode((string)file_get_contents($file), true);
    if (!is_array($content) || !isset($content['expires_at']) || !array_key_exists('payload', $content)) {
        @unlink($file);
        return null;
    }

    if ((int)$content['expires_at'] < time()) {
        @unlink($file);
        return null;
    }

    return $content['payload'];
}

function driveCacheWrite(string $cacheKey, $payload, int $ttl): void
{
    $file = getDriveCacheFilePath($cacheKey);
    $data = [
        'expires_at' => time() + $ttl,
        'payload' => $payload,
        'cache_key' => $cacheKey,
    ];
    @file_put_contents($file, json_encode($data));
}

function clearUserDriveCache(int $userId): void
{
    $dir = getDriveCacheDir();
    $prefix = 'u' . $userId . ':';

    foreach (glob($dir . '/*.json') ?: [] as $file) {
        $content = json_decode((string)file_get_contents($file), true);
        if (!is_array($content)) {
            @unlink($file);
            continue;
        }

        $key = $content['cache_key'] ?? '';
        if (is_string($key) && strpos($key, $prefix) === 0) {
            @unlink($file);
        }
    }
}

function getCurrentUserRootFolder(PDO $pdo): ?string
{
    if (!empty($_SESSION['folder_id'])) {
        return normalizeDriveId((string)$_SESSION['folder_id']);
    }

    $stmt = $pdo->prepare('SELECT folder_id FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();

    if (!empty($row['folder_id'])) {
        $_SESSION['folder_id'] = $row['folder_id'];
        return normalizeDriveId((string)$row['folder_id']);
    }

    return null;
}

function loadServiceAccountConfig(): array
{
    $rawJson = getenv('GOOGLE_SERVICE_ACCOUNT_JSON');
    if ($rawJson) {
        $decoded = json_decode($rawJson, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $filePath = getenv('GOOGLE_SERVICE_ACCOUNT_FILE');
    if ($filePath && is_file($filePath)) {
        $decoded = json_decode((string)file_get_contents($filePath), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    $fallbackFiles = [
        __DIR__ . '/config/google_service_account.json',
        __DIR__ . '/google_service_account.json',
    ];

    foreach ($fallbackFiles as $candidate) {
        if (is_file($candidate)) {
            $decoded = json_decode((string)file_get_contents($candidate), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }

    jsonResponse([
        'error' => 'Drive API is not configured. Set GOOGLE_SERVICE_ACCOUNT_FILE or GOOGLE_SERVICE_ACCOUNT_JSON.',
    ], 500);

    return [];
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function curlJsonRequest(string $url, array $headers, ?string $body = null, string $method = 'POST'): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno !== 0) {
        return ['ok' => false, 'status' => 500, 'error' => $error];
    }

    $decoded = json_decode((string)$response, true);
    if ($status >= 400) {
        $message = $decoded['error']['message'] ?? ('Drive API request failed with status ' . $status);
        return ['ok' => false, 'status' => $status, 'error' => $message, 'raw' => $decoded];
    }

    return ['ok' => true, 'status' => $status, 'data' => is_array($decoded) ? $decoded : []];
}

function getDriveAccessToken(): string
{
    $cfg = loadServiceAccountConfig();
    $clientEmail = $cfg['client_email'] ?? '';
    $privateKey = $cfg['private_key'] ?? '';
    $tokenUri = $cfg['token_uri'] ?? 'https://oauth2.googleapis.com/token';

    if (!$clientEmail || !$privateKey) {
        jsonResponse(['error' => 'Invalid service account configuration.'], 500);
    }

    $cacheFile = __DIR__ . '/logs/drive_token_cache.json';
    if (is_file($cacheFile)) {
        $cached = json_decode((string)file_get_contents($cacheFile), true);
        if (
            is_array($cached)
            && !empty($cached['access_token'])
            && !empty($cached['expires_at'])
            && (int)$cached['expires_at'] > (time() + 60)
        ) {
            return (string)$cached['access_token'];
        }
    }

    $now = time();
    $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims = base64UrlEncode(json_encode([
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/drive',
        'aud' => $tokenUri,
        'iat' => $now,
        'exp' => $now + 3600,
    ]));

    $unsignedJwt = $header . '.' . $claims;
    $signature = '';
    $ok = openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    if (!$ok) {
        jsonResponse(['error' => 'Unable to sign service account JWT.'], 500);
    }

    $jwt = $unsignedJwt . '.' . base64UrlEncode($signature);
    $payload = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    $tokenRes = curlJsonRequest(
        $tokenUri,
        ['Content-Type: application/x-www-form-urlencoded'],
        $payload,
        'POST'
    );

    if (!$tokenRes['ok'] || empty($tokenRes['data']['access_token'])) {
        jsonResponse(['error' => $tokenRes['error'] ?? 'Failed to obtain Drive access token'], 500);
    }

    $accessToken = (string)$tokenRes['data']['access_token'];
    $expiresIn = (int)($tokenRes['data']['expires_in'] ?? 3600);

    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }

    @file_put_contents($cacheFile, json_encode([
        'access_token' => $accessToken,
        'expires_at' => time() + $expiresIn,
    ]));

    return $accessToken;
}

function driveRequest(string $method, string $path, string $accessToken, array $query = [], ?string $body = null, array $extraHeaders = []): array
{
    $url = 'https://www.googleapis.com/drive/v3/' . ltrim($path, '/');
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $headers = array_merge([
        'Authorization: Bearer ' . $accessToken,
    ], $extraHeaders);

    return curlJsonRequest($url, $headers, $body, $method);
}

function driveUploadMultipart(string $accessToken, string $metadataJson, string $binaryContent, string $mimeType): array
{
    $boundary = '-------kphub-' . bin2hex(random_bytes(8));
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
    $body .= $metadataJson . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$mimeType}\r\n\r\n";
    $body .= $binaryContent . "\r\n";
    $body .= "--{$boundary}--";

    return curlJsonRequest(
        'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,mimeType,webViewLink&supportsAllDrives=true',
        [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: multipart/related; boundary=' . $boundary,
        ],
        $body,
        'POST'
    );
}

function getFileMeta(string $accessToken, string $id): ?array
{
    $res = driveRequest(
        'GET',
        'files/' . rawurlencode($id),
        $accessToken,
        [
            'fields' => 'id,name,mimeType,parents,trashed',
            'supportsAllDrives' => 'true',
        ]
    );

    if (!$res['ok']) {
        return null;
    }

    return $res['data'];
}

function isWithinRootScope(string $accessToken, string $nodeId, string $rootId, array &$cache = []): bool
{
    $nodeId = normalizeDriveId($nodeId);
    $rootId = normalizeDriveId($rootId);

    if ($nodeId === '' || $rootId === '') {
        return false;
    }

    if ($nodeId === $rootId) {
        return true;
    }

    if (isset($cache[$nodeId])) {
        return $cache[$nodeId];
    }

    $cursor = $nodeId;
    $seen = [];

    while ($cursor !== '' && !isset($seen[$cursor])) {
        $seen[$cursor] = true;

        if ($cursor === $rootId) {
            $cache[$nodeId] = true;
            return true;
        }

        $meta = getFileMeta($accessToken, $cursor);
        if (!$meta || !is_array($meta)) {
            break;
        }

        $parents = $meta['parents'] ?? [];
        if (!is_array($parents) || count($parents) === 0) {
            break;
        }

        $cursor = normalizeDriveId((string)$parents[0]);
    }

    $cache[$nodeId] = false;
    return false;
}

function listDriveItemsByQuery(string $accessToken, string $query, string $fields): array
{
    $items = [];
    $pageToken = null;

    do {
        $params = [
            'q' => $query,
            'fields' => 'nextPageToken,files(' . $fields . ')',
            'pageSize' => 1000,
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
            'corpora' => 'allDrives',
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        $res = driveRequest('GET', 'files', $accessToken, $params);
        if (!$res['ok']) {
            jsonResponse(['error' => $res['error'] ?? 'Failed to list Drive items'], 500);
        }

        $files = $res['data']['files'] ?? [];
        if (is_array($files)) {
            $items = array_merge($items, $files);
        }

        $pageToken = $res['data']['nextPageToken'] ?? null;
    } while ($pageToken);

    return $items;
}

function countFilesByTypeRecursively(string $accessToken, string $rootId): array
{
    $stack = [$rootId];
    $visited = [];
    $counts = [];

    while (!empty($stack)) {
        $folderId = array_pop($stack);
        if (isset($visited[$folderId])) {
            continue;
        }
        $visited[$folderId] = true;

        $items = listDriveItemsByQuery(
            $accessToken,
            "'{$folderId}' in parents and trashed=false",
            'id,name,mimeType,fileExtension'
        );

        foreach ($items as $item) {
            $mimeType = (string)($item['mimeType'] ?? '');
            if ($mimeType === 'application/vnd.google-apps.folder') {
                if (!empty($item['id'])) {
                    $stack[] = (string)$item['id'];
                }
                continue;
            }

            $ext = strtoupper((string)($item['fileExtension'] ?? ''));
            if ($ext === '') {
                if (strpos($mimeType, 'application/vnd.google-apps.') === 0) {
                    $ext = strtoupper(str_replace('application/vnd.google-apps.', '', $mimeType));
                } elseif ($mimeType !== '') {
                    $parts = explode('/', $mimeType);
                    $ext = strtoupper(end($parts));
                } else {
                    $ext = 'UNKNOWN';
                }
            }

            if (!isset($counts[$ext])) {
                $counts[$ext] = 0;
            }
            $counts[$ext]++;
        }
    }

    ksort($counts);
    return $counts;
}

function searchItemsRecursively(string $accessToken, string $rootId, string $queryText): array
{
    $needle = mb_strtolower(trim($queryText));
    if ($needle === '') {
        return ['folders' => [], 'files' => []];
    }

    $stack = [$rootId];
    $visited = [];
    $folders = [];
    $files = [];

    while (!empty($stack)) {
        $folderId = array_pop($stack);
        if (isset($visited[$folderId])) {
            continue;
        }
        $visited[$folderId] = true;

        $items = listDriveItemsByQuery(
            $accessToken,
            "'{$folderId}' in parents and trashed=false",
            'id,name,mimeType,size,webViewLink,modifiedTime,createdTime'
        );

        foreach ($items as $item) {
            $name = (string)($item['name'] ?? '');
            $mimeType = (string)($item['mimeType'] ?? '');
            $isFolder = $mimeType === 'application/vnd.google-apps.folder';
            $nameMatch = mb_strpos(mb_strtolower($name), $needle) !== false;

            if ($isFolder) {
                if (!empty($item['id'])) {
                    $stack[] = (string)$item['id'];
                }
                if ($nameMatch) {
                    $folders[] = [
                        'id' => (string)($item['id'] ?? ''),
                        'name' => $name,
                        'mimeType' => $mimeType,
                        'modifiedTime' => (string)($item['modifiedTime'] ?? ''),
                        'createdTime' => (string)($item['createdTime'] ?? ''),
                    ];
                }
                continue;
            }

            if (!$nameMatch) {
                continue;
            }

            $id = (string)($item['id'] ?? '');
            $view = (string)($item['webViewLink'] ?? '');
            if ($view === '' && $id !== '') {
                $view = 'https://drive.google.com/file/d/' . $id . '/view';
            }

            $files[] = [
                'id' => $id,
                'name' => $name,
                'mimeType' => $mimeType,
                'size' => (string)($item['size'] ?? '0'),
                'url' => $view,
                'modifiedTime' => (string)($item['modifiedTime'] ?? ''),
                'createdTime' => (string)($item['createdTime'] ?? ''),
            ];
        }
    }

    usort($folders, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });
    usort($files, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });

    return ['folders' => $folders, 'files' => $files];
}

$rootFolderId = getCurrentUserRootFolder($pdo);
if (!$rootFolderId) {
    jsonResponse(['error' => 'No Drive folder configured for this user.'], 400);
}

$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));
if ($action === '') {
    jsonResponse(['error' => 'Missing action parameter.'], 400);
}

$accessToken = getDriveAccessToken();
$scopeCache = [];
$browseCacheTtl = getDriveBrowseCacheTtl();
$cacheUserId = (int)$_SESSION['user_id'];

if ($action === 'getFolders') {
    $parentFolderId = normalizeDriveId((string)($_GET['parentFolderId'] ?? $_POST['parentFolderId'] ?? $rootFolderId));
    if (!$parentFolderId) {
        jsonResponse(['error' => 'Missing parentFolderId'], 400);
    }

    if (!isWithinRootScope($accessToken, $parentFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $cacheKey = 'u' . $cacheUserId . ':getFolders:' . $parentFolderId;
    $cached = driveCacheRead($cacheKey, $browseCacheTtl);
    if ($cached !== null) {
        jsonResponse($cached);
    }

    $folders = listDriveItemsByQuery(
        $accessToken,
        "'{$parentFolderId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
        'id,name,modifiedTime,createdTime,mimeType'
    );

    usort($folders, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });

    driveCacheWrite($cacheKey, $folders, $browseCacheTtl);
    jsonResponse($folders);
}

if ($action === 'getFolderContents') {
    $parentFolderId = normalizeDriveId((string)($_GET['parentFolderId'] ?? $_POST['parentFolderId'] ?? ''));
    if (!$parentFolderId) {
        jsonResponse(['error' => 'Missing parentFolderId'], 400);
    }

    if (!isWithinRootScope($accessToken, $parentFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $cacheKey = 'u' . $cacheUserId . ':getFolderContents:' . $parentFolderId;
    $cached = driveCacheRead($cacheKey, $browseCacheTtl);
    if ($cached !== null) {
        jsonResponse($cached);
    }

    $folders = listDriveItemsByQuery(
        $accessToken,
        "'{$parentFolderId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false",
        'id,name,modifiedTime,createdTime,mimeType'
    );
    $files = listDriveItemsByQuery(
        $accessToken,
        "'{$parentFolderId}' in parents and mimeType!='application/vnd.google-apps.folder' and trashed=false",
        'id,name,mimeType,size,fileExtension,webViewLink,webContentLink,modifiedTime,createdTime'
    );

    usort($folders, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });
    usort($files, static function ($a, $b) {
        return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
    });

    $mappedFiles = array_map(static function ($f) {
        $id = (string)($f['id'] ?? '');
        $view = (string)($f['webViewLink'] ?? '');
        if ($view === '' && $id !== '') {
            $view = 'https://drive.google.com/file/d/' . $id . '/view';
        }
        return [
            'id' => $id,
            'name' => (string)($f['name'] ?? ''),
            'mimeType' => (string)($f['mimeType'] ?? ''),
            'size' => (string)($f['size'] ?? '0'),
            'url' => $view,
            'modifiedTime' => (string)($f['modifiedTime'] ?? ''),
            'createdTime' => (string)($f['createdTime'] ?? ''),
        ];
    }, $files);

    $payload = ['folders' => $folders, 'files' => $mappedFiles];
    driveCacheWrite($cacheKey, $payload, $browseCacheTtl);
    jsonResponse($payload);
}

if ($action === 'createFolder') {
    $parentFolderId = normalizeDriveId((string)($_POST['parentFolderId'] ?? ''));
    $folderName = trim((string)($_POST['folderName'] ?? ''));

    if (!$parentFolderId || $folderName === '') {
        jsonResponse(['error' => 'Missing parentFolderId or folderName'], 400);
    }

    if (!isWithinRootScope($accessToken, $parentFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $payload = json_encode([
        'name' => $folderName,
        'mimeType' => 'application/vnd.google-apps.folder',
        'parents' => [$parentFolderId],
    ]);

    $res = driveRequest(
        'POST',
        'files',
        $accessToken,
        ['supportsAllDrives' => 'true', 'fields' => 'id,name'],
        $payload,
        ['Content-Type: application/json']
    );

    if (!$res['ok']) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to create folder'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse($res['data']);
}

if ($action === 'uploadFile') {
    $folderId = normalizeDriveId((string)($_POST['folderId'] ?? ''));
    $fileName = trim((string)($_POST['fileName'] ?? ''));
    $mimeType = trim((string)($_POST['mimeType'] ?? 'application/octet-stream'));
    $base64 = (string)($_POST['file'] ?? '');

    if (!$folderId || $fileName === '' || $base64 === '') {
        jsonResponse(['error' => 'Missing folderId, fileName, or file'], 400);
    }

    if (!isWithinRootScope($accessToken, $folderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $decoded = base64_decode($base64, true);
    if ($decoded === false) {
        jsonResponse(['error' => 'Invalid base64 file payload'], 400);
    }

    $safeMime = preg_match('/^[a-zA-Z0-9\.\-\+\/]+$/', $mimeType) ? $mimeType : 'application/octet-stream';
    $metadataJson = json_encode([
        'name' => $fileName,
        'parents' => [$folderId],
    ]);

    $res = driveUploadMultipart($accessToken, (string)$metadataJson, $decoded, $safeMime);
    if (!$res['ok']) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to upload file'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse($res['data']);
}

if ($action === 'deleteFile') {
    $fileId = normalizeDriveId((string)($_POST['fileId'] ?? ''));
    if (!$fileId) {
        jsonResponse(['error' => 'Missing fileId'], 400);
    }

    if (!isWithinRootScope($accessToken, $fileId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'File is outside your Drive scope.'], 403);
    }

    $res = driveRequest(
        'DELETE',
        'files/' . rawurlencode($fileId),
        $accessToken,
        ['supportsAllDrives' => 'true']
    );

    if (!$res['ok'] && (int)$res['status'] !== 204) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to delete file'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse(['success' => true]);
}

if ($action === 'deleteItem') {
    $itemId = normalizeDriveId((string)($_POST['itemId'] ?? ''));
    if (!$itemId) {
        jsonResponse(['error' => 'Missing itemId'], 400);
    }

    if (!isWithinRootScope($accessToken, $itemId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Item is outside your Drive scope.'], 403);
    }

    $res = driveRequest(
        'DELETE',
        'files/' . rawurlencode($itemId),
        $accessToken,
        ['supportsAllDrives' => 'true']
    );

    if (!$res['ok'] && (int)$res['status'] !== 204) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to delete item'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse(['success' => true]);
}

if ($action === 'renameItem') {
    $itemId = normalizeDriveId((string)($_POST['itemId'] ?? ''));
    $newName = trim((string)($_POST['newName'] ?? ''));

    if (!$itemId || $newName === '') {
        jsonResponse(['error' => 'Missing itemId or newName'], 400);
    }

    if (!isWithinRootScope($accessToken, $itemId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Item is outside your Drive scope.'], 403);
    }

    $res = driveRequest(
        'PATCH',
        'files/' . rawurlencode($itemId),
        $accessToken,
        [
            'supportsAllDrives' => 'true',
            'fields' => 'id,name,modifiedTime',
        ],
        json_encode(['name' => $newName]),
        ['Content-Type: application/json']
    );

    if (!$res['ok']) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to rename item'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse([
        'success' => true,
        'id' => $res['data']['id'] ?? $itemId,
        'name' => $res['data']['name'] ?? $newName,
    ]);
}

if ($action === 'moveItem') {
    $itemId = normalizeDriveId((string)($_POST['itemId'] ?? ''));
    $targetFolderId = normalizeDriveId((string)($_POST['targetFolderId'] ?? ''));

    if (!$itemId || !$targetFolderId) {
        jsonResponse(['error' => 'Missing itemId or targetFolderId'], 400);
    }

    if (!isWithinRootScope($accessToken, $itemId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Item is outside your Drive scope.'], 403);
    }
    if (!isWithinRootScope($accessToken, $targetFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Target folder is outside your Drive scope.'], 403);
    }

    $meta = getFileMeta($accessToken, $itemId);
    if (!$meta) {
        jsonResponse(['error' => 'Item metadata not found'], 404);
    }

    $parents = $meta['parents'] ?? [];
    if (!is_array($parents)) {
        $parents = [];
    }

    foreach ($parents as $parentId) {
        if (normalizeDriveId((string)$parentId) === $targetFolderId) {
            jsonResponse(['success' => true, 'message' => 'Item is already in target folder']);
        }
    }

    $removeParents = implode(',', array_map(static function ($id) {
        return normalizeDriveId((string)$id);
    }, $parents));

    $query = [
        'supportsAllDrives' => 'true',
        'addParents' => $targetFolderId,
        'fields' => 'id,name,parents',
    ];
    if ($removeParents !== '') {
        $query['removeParents'] = $removeParents;
    }

    $res = driveRequest(
        'PATCH',
        'files/' . rawurlencode($itemId),
        $accessToken,
        $query,
        json_encode(new stdClass()),
        ['Content-Type: application/json']
    );

    if (!$res['ok']) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to move item'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse(['success' => true, 'item' => $res['data']]);
}

if ($action === 'addDriveFile') {
    $folderId = normalizeDriveId((string)($_POST['folderId'] ?? ''));
    $fileId = normalizeDriveId((string)($_POST['fileId'] ?? ''));
    $fileName = trim((string)($_POST['fileName'] ?? 'Shared File'));

    if (!$folderId || !$fileId) {
        jsonResponse(['error' => 'Missing folderId or fileId'], 400);
    }

    if (!isWithinRootScope($accessToken, $folderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $payload = json_encode([
        'name' => $fileName,
        'mimeType' => 'application/vnd.google-apps.shortcut',
        'parents' => [$folderId],
        'shortcutDetails' => ['targetId' => $fileId],
    ]);

    $res = driveRequest(
        'POST',
        'files',
        $accessToken,
        ['supportsAllDrives' => 'true', 'fields' => 'id,name'],
        $payload,
        ['Content-Type: application/json']
    );

    if (!$res['ok']) {
        jsonResponse(['error' => $res['error'] ?? 'Failed to add Drive file'], 500);
    }

    clearUserDriveCache($cacheUserId);
    jsonResponse(['success' => true, 'id' => $res['data']['id'] ?? null, 'name' => $res['data']['name'] ?? $fileName]);
}

if ($action === 'getAllFiles') {
    $parentFolderId = normalizeDriveId((string)($_GET['parentFolderId'] ?? $_POST['parentFolderId'] ?? $rootFolderId));
    if (!$parentFolderId) {
        jsonResponse(['error' => 'Missing parentFolderId'], 400);
    }

    if (!isWithinRootScope($accessToken, $parentFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $cacheKey = 'u' . $cacheUserId . ':getAllFiles:' . $parentFolderId;
    $cached = driveCacheRead($cacheKey, $browseCacheTtl);
    if ($cached !== null) {
        jsonResponse($cached);
    }

    $counts = countFilesByTypeRecursively($accessToken, $parentFolderId);
    driveCacheWrite($cacheKey, $counts, $browseCacheTtl);
    jsonResponse($counts);
}

if ($action === 'searchFolderItems') {
    $parentFolderId = normalizeDriveId((string)($_GET['parentFolderId'] ?? $_POST['parentFolderId'] ?? $rootFolderId));
    $queryText = trim((string)($_GET['query'] ?? $_POST['query'] ?? ''));

    if (!$parentFolderId) {
        jsonResponse(['error' => 'Missing parentFolderId'], 400);
    }
    if ($queryText === '') {
        jsonResponse(['folders' => [], 'files' => []]);
    }

    if (!isWithinRootScope($accessToken, $parentFolderId, $rootFolderId, $scopeCache)) {
        jsonResponse(['error' => 'Folder is outside your Drive scope.'], 403);
    }

    $cacheKey = 'u' . $cacheUserId . ':searchFolderItems:' . $parentFolderId . ':' . sha1(mb_strtolower($queryText));
    $cached = driveCacheRead($cacheKey, $browseCacheTtl);
    if ($cached !== null) {
        jsonResponse($cached);
    }

    $results = searchItemsRecursively($accessToken, $parentFolderId, $queryText);
    driveCacheWrite($cacheKey, $results, $browseCacheTtl);
    jsonResponse($results);
}

jsonResponse(['error' => 'Unsupported action: ' . $action], 400);
