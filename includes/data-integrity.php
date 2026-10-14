<?php
/*
 * Fingerprints of the Quran, tafseer, translation and book data files.
 *
 * includes/data-manifest.json holds the SHA-256 of every file under the two
 * data folders as published in the repository. The control panel checks the
 * live files against it, so any change to a single letter of the sources, by
 * mistake or by an attacker, shows up at once. Build the manifest from the
 * terminal after a deliberate data update:
 *
 *   php tools/build-data-manifest.php
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    exit;
}

const QFA_DATA_DIRS = ['api/quran/includes', 'api/books/includes'];
const QFA_DATA_MANIFEST = __DIR__ . '/data-manifest.json';

/** path relative to the site root => sha256, for every data file on disk. */
function qfa_data_hashes(string $root): array {
    $hashes = [];
    foreach (QFA_DATA_DIRS as $dir) {
        if (!is_dir($root . '/' . $dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile()) {
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
                $hashes[$rel] = (string)hash_file('sha256', $file->getPathname());
            }
        }
    }
    ksort($hashes, SORT_STRING);
    return $hashes;
}

/**
 * Compare the data files with the manifest.
 *
 * @return array{ok: bool, manifest: bool, checked: int, changed: string[], missing: string[], added: string[]}
 */
function qfa_data_verify(string $root): array {
    $raw = is_file(QFA_DATA_MANIFEST) ? json_decode((string)file_get_contents(QFA_DATA_MANIFEST), true) : null;
    $expected = is_array($raw['files'] ?? null) ? $raw['files'] : null;
    if ($expected === null) {
        return ['ok' => false, 'manifest' => false, 'checked' => 0, 'changed' => [], 'missing' => [], 'added' => []];
    }

    $actual = qfa_data_hashes($root);
    $changed = $missing = [];
    foreach ($expected as $path => $hash) {
        if (!isset($actual[$path])) {
            $missing[] = $path;
        } elseif (!hash_equals((string)$hash, $actual[$path])) {
            $changed[] = $path;
        }
    }
    // index.php guards and similar are fine; anything else new is reported.
    $added = array_values(array_diff(array_keys($actual), array_keys($expected)));

    return [
        'ok' => $changed === [] && $missing === [] && $added === [],
        'manifest' => true,
        'checked' => count($expected),
        'changed' => $changed,
        'missing' => $missing,
        'added' => $added,
    ];
}
