<?php
declare(strict_types=1);
require __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$pins = read_pins();

// newest first
usort($pins, fn($a, $b) => ($b['createdAt'] ?? 0) <=> ($a['createdAt'] ?? 0));

json_response(['pins' => $pins]);
