<?php

$inputPath = $argv[1] ?? '';
$outputPath = $argv[2] ?? '';

if (str_contains((string) @file_get_contents($inputPath), 'FAIL')) {
    fwrite(STDOUT, json_encode(['ok' => false, 'error' => 'Simulated OCR failure.']));
    exit(1);
}

file_put_contents($outputPath, "--- Page 1 ---\nRecovered scanned PDF text.\n");
fwrite(STDOUT, json_encode(['ok' => true, 'pages' => 1, 'pages_with_text' => 1]));
