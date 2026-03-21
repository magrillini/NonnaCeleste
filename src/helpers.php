<?php

declare(strict_types=1);

function current_user(): ?array
{
    global $db;
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user && in_array($user['role'], ['admin', 'superadmin'], true);
}

function is_superadmin(): bool
{
    $user = current_user();
    return $user && $user['role'] === 'superadmin';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function fetch_all(PDO $db, string $sql, array $params = []): array
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

function query(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function consume_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function current_request_path(): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = parse_url($requestUri, PHP_URL_PATH);
    return is_string($path) && $path !== '' ? $path : '/';
}

function register_active_session(?array $user): void
{
    global $db;

    $sessionId = session_id();
    if ($sessionId === '') {
        return;
    }

    $db->prepare('DELETE FROM active_sessions WHERE last_seen < ?')
        ->execute([time() - 900]);

    $db->prepare('INSERT INTO active_sessions(session_id,user_id,current_path,last_seen) VALUES(?,?,?,?) ON CONFLICT(session_id) DO UPDATE SET user_id = excluded.user_id, current_path = excluded.current_path, last_seen = excluded.last_seen')
        ->execute([$sessionId, $user['id'] ?? null, current_request_path(), time()]);
}

function unregister_active_session(): void
{
    global $db;

    $sessionId = session_id();
    if ($sessionId === '') {
        return;
    }

    $db->prepare('DELETE FROM active_sessions WHERE session_id = ?')->execute([$sessionId]);
}

function increment_page_views(): int
{
    $current = (int) (site_setting('page_views', '0') ?? '0');
    $updated = $current + 1;
    set_site_setting('page_views', (string) $updated);
    return $updated;
}

function active_logged_in_users_count(): int
{
    global $db;

    return (int) $db->query('SELECT COUNT(DISTINCT user_id) FROM active_sessions WHERE user_id IS NOT NULL AND last_seen >= ' . (time() - 900))->fetchColumn();
}

function media_url(?string $path): string
{
    $trimmedPath = trim((string) $path);
    if ($trimmedPath === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $trimmedPath) === 1) {
        return $trimmedPath;
    }

    $normalizedPath = ltrim($trimmedPath, '/');
    if (str_starts_with($normalizedPath, 'storage/')) {
        return '/?action=media&path=' . rawurlencode($normalizedPath);
    }

    return str_starts_with($trimmedPath, '/') ? $trimmedPath : '/' . $normalizedPath;
}

function media_storage_path(?string $path): ?string
{
    $trimmedPath = ltrim(trim((string) $path), '/');
    if ($trimmedPath === '' || !str_starts_with($trimmedPath, 'storage/')) {
        return null;
    }

    $relativePath = substr($trimmedPath, strlen('storage/'));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }

    $absolutePath = STORAGE_PATH . '/' . ltrim($relativePath, '/');
    $realPath = realpath($absolutePath);
    $storageRoot = realpath(STORAGE_PATH);
    if ($realPath === false || $storageRoot === false) {
        return null;
    }

    if (!str_starts_with($realPath, $storageRoot . DIRECTORY_SEPARATOR)) {
        return null;
    }

    return is_file($realPath) ? $realPath : null;
}

function save_uploaded_images(array $files, int $recipeId, int $userId): void
{
    global $db;
    if (!isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
        return;
    }

    $targetDir = STORAGE_PATH . '/gallery';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $stmt = $db->prepare('INSERT INTO recipe_images(recipe_id,path,caption,uploaded_by_user_id,created_at) VALUES(?,?,?,?,?)');

    foreach ($files['tmp_name'] as $index => $tmpPath) {
        if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !$tmpPath) {
            continue;
        }
        $extension = pathinfo($files['name'][$index] ?? 'jpg', PATHINFO_EXTENSION) ?: 'jpg';
        $name = sprintf('recipe_%d_%s.%s', $recipeId, uniqid('', true), $extension);
        $destination = $targetDir . '/' . $name;
        if (move_uploaded_file_safely($tmpPath, $destination)) {
            $stmt->execute([$recipeId, 'storage/gallery/' . $name, null, $userId, date(DATE_ATOM)]);
        }
    }
}

function site_setting(string $key, ?string $default = null): ?string
{
    global $db;
    $stmt = $db->prepare('SELECT value FROM site_settings WHERE key_name = ?');
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value === false ? $default : (string) $value;
}

function set_site_setting(string $key, string $value): void
{
    global $db;
    $db->prepare('INSERT INTO site_settings(key_name,value,updated_at) VALUES(?,?,?) ON CONFLICT(key_name) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at')
        ->execute([$key, $value, date(DATE_ATOM)]);
}

function delete_site_setting(string $key): void
{
    global $db;
    $db->prepare('DELETE FROM site_settings WHERE key_name = ?')->execute([$key]);
}

function home_hero_image_path(): string
{
    return site_setting('home_hero_image', 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80') ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80';
}

function save_home_hero_image(array $file, int $userId): bool
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return false;
    }

    $mimeType = mime_content_type($file['tmp_name']) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mimeType])) {
        throw new RuntimeException('Formato immagine non supportato. Usa JPG, PNG, WEBP o GIF.');
    }

    $targetDir = STORAGE_PATH . '/home';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $currentPath = site_setting('home_hero_image');
    if ($currentPath && str_starts_with($currentPath, 'storage/home/')) {
        $absoluteCurrentPath = BASE_PATH . '/' . $currentPath;
        if (is_file($absoluteCurrentPath)) {
            unlink($absoluteCurrentPath);
        }
    }

    $name = sprintf('home_hero_user_%d_%s.%s', $userId, uniqid('', true), $allowed[$mimeType]);
    $destination = $targetDir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Upload immagine non riuscito.');
    }

    set_site_setting('home_hero_image', 'storage/home/' . $name);
    return true;
}

function reset_home_hero_image(): void
{
    $currentPath = site_setting('home_hero_image');
    if ($currentPath && str_starts_with($currentPath, 'storage/home/')) {
        $absoluteCurrentPath = BASE_PATH . '/' . $currentPath;
        if (is_file($absoluteCurrentPath)) {
            unlink($absoluteCurrentPath);
        }
    }

    delete_site_setting('home_hero_image');
}

function google_calendar_link(array $recipe): string
{
    $title = rawurlencode('Ricetta: ' . $recipe['title']);
    $details = rawurlencode("Ricetta {$recipe['title']} - festività {$recipe['holiday']}");
    $dates = gmdate('Ymd', strtotime('next day')) . '/' . gmdate('Ymd', strtotime('+2 day'));
    return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&dates={$dates}";
}
