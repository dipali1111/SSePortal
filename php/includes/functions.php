<?php
function read_json_data($filename)
{
    $path = __DIR__ . '/../' . $filename;

    if (!file_exists($path)) {
        return [];
    }

    $content = file_get_contents($path);
    if ($content === false || trim($content) === '') {
        return [];
    }

    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function write_json_data($filename, $data)
{
    $dir = dirname(__DIR__ . '/../' . $filename);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $path = __DIR__ . '/../' . $filename;
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function save_progress_update($entry)
{
    $records = read_json_data('data/progress_updates.json');
    $records[] = $entry;
    write_json_data('data/progress_updates.json', $records);
}

function save_blocker($entry)
{
    $records = read_json_data('data/blockers.json');
    $records[] = $entry;
    write_json_data('data/blockers.json', $records);
}

function upload_photo($fieldName)
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION);
    $filename = 'photo_' . time() . '_' . uniqid() . ($extension ? '.' . $extension : '');
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $targetPath)) {
        return null;
    }

    return 'uploads/' . $filename;
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
