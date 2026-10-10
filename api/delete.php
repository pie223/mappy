<?php
declare(strict_types=1);
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? preg_replace('/[^a-f0-9]/', '', (string)$input['id']) : '';

if ($id === '') {
    json_response(['error' => 'Missing pin id.'], 422);
}

$pins = read_pins();
$index = null;
foreach ($pins as $i => $p) {
    if ($p['id'] === $id) { $index = $i; break; }
}

if ($index === null) {
    json_response(['error' => 'Pin not found.'], 404);
}

$filename = $pins[$index]['filename'] ?? '';
unset($pins[$index]);
$pins = array_values($pins);

if (!write_pins($pins)) {
    json_response(['error' => 'Could not update pin data.'], 500);
}

if ($filename !== '') {
    $path = UPLOAD_DIR . basename($filename);
    if (file_exists($path)) {
        @unlink($path);
    }
}

json_response(['ok' => true]);
