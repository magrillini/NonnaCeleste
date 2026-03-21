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

function google_calendar_link(array $recipe): string
{
    $title = rawurlencode('Ricetta: ' . $recipe['title']);
    $details = rawurlencode("Ricetta {$recipe['title']} - festività {$recipe['holiday']}");
    $dates = gmdate('Ymd', strtotime('next day')) . '/' . gmdate('Ymd', strtotime('+2 day'));
    return "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$title}&details={$details}&dates={$dates}";
}
