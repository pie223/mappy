<?php
declare(strict_types=1);

define('DATA_FILE', __DIR__ . '/../data/pins.json');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL_BASE', 'uploads/');

const ALLOWED_MIME = [
    'audio/mpeg'      => 'mp3',
    'audio/mp3'       => 'mp3',
    'audio/wav'        => 'wav',
    'audio/x-wav'      => 'wav',
    'audio/wave'       => 'wav',
    'audio/ogg'        => 'ogg',
    'audio/mp4'        => 'm4a',
    'audio/x-m4a'      => 'm4a',
    'audio/aac'        => 'm4a',
    // .m4a is an MP4 container. fileinfo can't tell "audio-only MP4" apart
    // from a video MP4 just from the header, so it very often reports these
    // as video/mp4 or application/mp4 even though there's no video track.
    // We accept them here and double-check for a video track below.
    'video/mp4'        => 'm4a',
    'application/mp4'  => 'm4a',
    'audio/webm'       => 'webm',
];

const MAX_FILE_BYTES = 15 * 1024 * 1024; // 15MB

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function read_pins(): array {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $fp = fopen(DATA_FILE, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $contents = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    $data = json_decode($contents ?: '[]', true);
    return is_array($data) ? $data : [];
}

function write_pins(array $pins): bool {
    $fp = fopen(DATA_FILE, 'c+');
    if (!$fp) return false;
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($pins, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

/**
 * .m4a files are MP4 containers, so fileinfo often reports them as
 * video/mp4 / application/mp4 even though they hold no video track.
 * We accept those mimetypes as m4a, but first do a cheap scan for an
 * MP4 "hdlr" (handler) atom whose handler_type is "vide" — a real
 * video track — so an actual video file can't sneak in via that gap.
 */
function mp4_has_video_track(string $path): bool {
    $data = file_get_contents($path, false, null, 0, 5 * 1024 * 1024);
    if ($data === false) return false;
    $offset = 0;
    while (($pos = strpos($data, 'hdlr', $offset)) !== false) {
        // handler_type sits 8 bytes after the "hdlr" tag
        // (4 bytes version+flags, 4 bytes pre_defined)
        $handlerType = substr($data, $pos + 4 + 8, 4);
        if ($handlerType === 'vide') {
            return true;
        }
        $offset = $pos + 4;
    }
    return false;
}

function clean_text(string $s, int $maxLen): string {
    $s = trim($s);
    $s = strip_tags($s);
    if (function_exists('mb_strlen')) {
        if (mb_strlen($s) > $maxLen) {
            $s = mb_substr($s, 0, $maxLen);
        }
    } elseif (strlen($s) > $maxLen) {
        $s = substr($s, 0, $maxLen);
    }
    return $s;
}
