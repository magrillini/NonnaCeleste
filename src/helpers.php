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
        if (move_uploaded_file($tmpPath, $destination)) {
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

function home_theme_options(): array
{
    return [
        'classic' => 'Classica',
        'editorial' => 'Editoriale',
        'warm' => 'Calda',
    ];
}

function home_theme(): string
{
    $theme = site_setting('home_hero_theme', 'classic') ?? 'classic';
    return array_key_exists($theme, home_theme_options()) ? $theme : 'classic';
}

function set_home_theme(string $theme): void
{
    set_site_setting('home_hero_theme', array_key_exists($theme, home_theme_options()) ? $theme : 'classic');
}

function uploaded_image_definitions(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];
}

function move_uploaded_file_safely(string $tmpPath, string $destination): bool
{
    if (function_exists('move_uploaded_file') && move_uploaded_file($tmpPath, $destination)) {
        return true;
    }

    if (@rename($tmpPath, $destination)) {
        return true;
    }

    if (@copy($tmpPath, $destination)) {
        @unlink($tmpPath);
        return true;
    }

    return false;
}

function normalize_uploaded_files(array $files): array
{
    if (!isset($files['tmp_name'])) {
        return [];
    }

    if (is_array($files['tmp_name'])) {
        $normalized = [];
        foreach ($files['tmp_name'] as $index => $tmpName) {
            $normalized[] = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $tmpName,
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $normalized;
    }

    return [$files];
}

function store_home_image(array $file, int $userId): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return null;
    }

    $mimeType = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    $allowed = uploaded_image_definitions();
    if (!isset($allowed[$mimeType])) {
        throw new RuntimeException('Formato immagine non supportato. Usa JPG, PNG, WEBP o GIF.');
    }

    $targetDir = STORAGE_PATH . '/home';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $name = sprintf('home_slide_%d_%s.%s', $userId, uniqid('', true), $allowed[$mimeType]);
    $destination = $targetDir . '/' . $name;
    if (!move_uploaded_file_safely($file['tmp_name'], $destination)) {
        throw new RuntimeException('Upload immagine Home non riuscito. Verifica i permessi della cartella storage/home.');
    }

    return 'storage/home/' . $name;
}

function ensure_legacy_home_slide(): void
{
    global $db;

    $slidesCount = (int) $db->query('SELECT COUNT(*) FROM home_slides')->fetchColumn();
    if ($slidesCount > 0) {
        return;
    }

    $legacyPath = site_setting('home_hero_image');
    if (!$legacyPath) {
        return;
    }

    $db->prepare('INSERT INTO home_slides(path,caption,sort_order,uploaded_by_user_id,created_at) VALUES(?,?,?,?,?)')
        ->execute([$legacyPath, 'Foto Home migrata', 1, current_user()['id'] ?? null, date(DATE_ATOM)]);
    delete_site_setting('home_hero_image');
}

function fetch_home_slides(): array
{
    global $db;
    ensure_legacy_home_slide();
    return fetch_all($db, 'SELECT * FROM home_slides ORDER BY sort_order ASC, id ASC');
}

function home_default_slides(): array
{
    return [[
        'id' => 0,
        'path' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
        'caption' => 'Benvenuti nella cucina di Nonna Celeste',
    ]];
}

function home_hero_slides(): array
{
    $slides = fetch_home_slides();
    return $slides ?: home_default_slides();
}

function save_home_slides(array $files, int $userId): int
{
    global $db;
    $normalized = normalize_uploaded_files($files);
    if ($normalized === []) {
        return 0;
    }

    $sortOrder = (int) $db->query('SELECT COALESCE(MAX(sort_order), 0) FROM home_slides')->fetchColumn();
    $stmt = $db->prepare('INSERT INTO home_slides(path,caption,sort_order,uploaded_by_user_id,created_at) VALUES(?,?,?,?,?)');
    $saved = 0;

    foreach ($normalized as $file) {
        $path = store_home_image($file, $userId);
        if ($path === null) {
            continue;
        }
        $sortOrder++;
        $caption = pathinfo((string) ($file['name'] ?? ''), PATHINFO_FILENAME) ?: 'Foto Home';
        $stmt->execute([$path, $caption, $sortOrder, $userId, date(DATE_ATOM)]);
        $saved++;
    }

    return $saved;
}

function delete_home_slide(int $slideId): void
{
    global $db;
    $stmt = $db->prepare('SELECT * FROM home_slides WHERE id = ?');
    $stmt->execute([$slideId]);
    $slide = $stmt->fetch();
    if (!$slide) {
        return;
    }

    if (str_starts_with($slide['path'], 'storage/home/')) {
        $absolutePath = BASE_PATH . '/' . $slide['path'];
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    $db->prepare('DELETE FROM home_slides WHERE id = ?')->execute([$slideId]);
}

function reset_home_slides(): void
{
    foreach (fetch_home_slides() as $slide) {
        if (!empty($slide['id'])) {
            delete_home_slide((int) $slide['id']);
        }
    }
}

function google_calendar_link(array $recipe): string
{
    $title = rawurlencode('Ricetta: ' . $recipe['title']);
    $details = rawurlencode("Ricetta {$recipe['title']} - festività {$recipe['holiday']}");
    $dates = gmdate('Ymd', strtotime('next day')) . '/' . gmdate('Ymd', strtotime('+2 day'));
    return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&dates={$dates}";
}
