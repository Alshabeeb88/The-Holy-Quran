<?php
/*
 * Rebuild includes/data-manifest.json from the data files on disk.
 *
 *   php tools/build-data-manifest.php          write the manifest
 *   php tools/build-data-manifest.php --check  exit 1 if it is out of date
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/includes/data-integrity.php';

$hashes = qfa_data_hashes($root);
$json = json_encode(['algorithm' => 'sha256', 'files' => $hashes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if (in_array('--check', $argv, true)) {
    $result = qfa_data_verify($root);
    if (!$result['manifest']) {
        fwrite(STDERR, "includes/data-manifest.json is missing or unreadable\n");
        exit(1);
    }
    foreach (['changed', 'missing', 'added'] as $kind) {
        foreach ($result[$kind] as $path) {
            fwrite(STDERR, "$kind: $path\n");
        }
    }
    echo $result['checked'] . ' data files, ' . ($result['ok'] ? 'all match the manifest' : 'MISMATCH') . "\n";
    exit($result['ok'] ? 0 : 1);
}

file_put_contents(QFA_DATA_MANIFEST, $json);
echo count($hashes) . " data files written to includes/data-manifest.json\n";
