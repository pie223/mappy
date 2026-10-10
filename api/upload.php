<?php
declare(strict_types=1);
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

// --- Validate coordinates ---
$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lng === false || $lng === null) {
    json_response(['error' => 'Invalid or missing coordinates.'], 422);
}
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    json_response(['error' => 'Coordinates out of range.'], 422);
}

// --- Validate text fields ---
$title = clean_text($_POST['title'] ?? '', 80);
$description = clean_text($_POST['description'] ?? '', 300);
$author = clean_text($_POST['author'] ?? '', 40);

if ($title === '') {
    json_response(['error' => 'A title is required.'], 422);
}

// --- Validate file ---
if (!isset($_FILES['sound']) || $_FILES['sound']['error'] === UPLOAD_ERR_NO_FILE) {
    json_response(['error' => 'No sound file was uploaded.'], 422);
}

$file = $_FILES['sound'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Upload failed (code ' . $file['error'] . ').'], 422);
}

if ($file['size'] <= 0 || $file['size'] > MAX_FILE_BYTES) {
    json_response(['error' => 'File must be between 1 byte and 15MB.'], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!isset(ALLOWED_MIME[$mime])) {
    json_response(['error' => "Unsupported audio type ($mime). Use mp3, wav, ogg, m4a, or webm."], 422);
}

// video/mp4 and application/mp4 are accepted as m4a (see functions.php),
// but reject them if they actually contain a video track.
if (in_array($mime, ['video/mp4', 'application/mp4'], true) && mp4_has_video_track($file['tmp_name'])) {
    json_response(['error' => 'That looks like a video file, not an audio-only m4a.'], 422);
}

$ext = ALLOWED_MIME[$mime];
$id = bin2hex(random_bytes(8));
$filename = $id . '.' . $ext;
$destination = UPLOAD_DIR . $filename;

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    json_response(['error' => 'Could not save the uploaded file.'], 500);
}

$pin = [
    'id' => $id,
    'lat' => $lat,
    'lng' => $lng,
    'title' => $title,
    'description' => $description,
    'author' => $author !== '' ? $author : 'Anonymous',
    'filename' => $filename,
    'url' => UPLOAD_URL_BASE . $filename,
    'createdAt' => time(),
];

$pins = read_pins();
$pins[] = $pin;

if (!write_pins($pins)) {
    // roll back the file if we couldn't persist metadata
    @unlink($destination);
    json_response(['error' => 'Could not save pin data.'], 500);
}

json_response(['pin' => $pin], 201);
