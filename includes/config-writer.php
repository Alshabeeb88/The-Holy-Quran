<?php
declare(strict_types=1);

/*
 * Safe editing of includes/config.php.
 *
 * The settings pages used to rewrite define() lines with preg_replace(), which
 * treats "$0", "\1" and similar sequences in the replacement as back-references,
 * and matched values with a non-greedy ".*?\);" that stopped at the first ");"
 * inside a value. A site name such as "Name$0X" therefore produced a config
 * file PHP could not parse, which took the whole site down.
 *
 * Here every value is written as a PHP literal through a callback (so the text
 * is never interpreted), existing values are matched by their literal syntax,
 * and the result is parsed before it is allowed anywhere near the live file.
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    exit;
}

/** A PHP literal for a config value: string, bool, int or a list of strings. */
function qfa_config_literal($value): string {
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_int($value)) {
        return (string)$value;
    }
    if (is_array($value)) {
        $items = array_map(static function ($item): string {
            return var_export((string)$item, true);
        }, array_values($value));
        return '[' . implode(', ', $items) . ']';
    }
    return var_export((string)$value, true);
}

/**
 * Set one define() in the config source. The existing value is matched by its
 * literal form only (quoted string, bool, null, number or a flat [...] list),
 * so text inside a value can never end the match early. A define that does
 * not exist yet, such as a setting added by a newer release, is appended; one
 * written in any other form is left as it is.
 */
function qfa_config_set(string $code, string $name, $value): string {
    $statement = "define('" . $name . "', " . qfa_config_literal($value) . ");";

    $string = "'(?:[^'\\\\]|\\\\.)*'";
    $literal = "(?:{$string}|true|false|null|-?\\d+|\\[(?:{$string}|[^\\]'])*\\])";
    $pattern = "~define\\(\\s*'" . preg_quote($name, '~') . "'\\s*,\\s*{$literal}\\s*\\);~i";

    $count = 0;
    $updated = preg_replace_callback($pattern, static function () use ($statement): string {
        return $statement;
    }, $code, 1, $count);

    if ($updated === null) {
        return $code;
    }
    if ($count === 1) {
        return $updated;
    }
    // Defined in a form this writer does not rewrite (double quotes, an
    // expression): leave the source alone so the commit check refuses the
    // save, rather than appending a second define that PHP would ignore.
    if (preg_match("~define\\(\\s*['\"]" . preg_quote($name, '~') . "['\"]~i", $code)) {
        return $code;
    }

    $trimmed = rtrim($code);
    if (substr($trimmed, -2) === '?>') {
        return rtrim(substr($trimmed, 0, -2)) . "\n" . $statement . "\n?>\n";
    }
    return $trimmed . "\n" . $statement . "\n";
}

/** True when the source parses as PHP. Nothing in it is executed. */
function qfa_config_parses(string $code): bool {
    try {
        token_get_all($code, TOKEN_PARSE);
        return true;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Atomically replace the config file with $code, after checking that it parses
 * and that every expected define() is present exactly as written.
 *
 * @param array<string, mixed> $expected name => value
 */
function qfa_config_commit(string $file, string $code, array $expected): bool {
    if (!qfa_config_parses($code)) {
        return false;
    }
    foreach ($expected as $name => $value) {
        $statement = "define('" . $name . "', " . qfa_config_literal($value) . ");";
        if (substr_count($code, $statement) !== 1) {
            return false;
        }
    }

    $tmp = $file . '.tmp-' . bin2hex(random_bytes(5));
    if (@file_put_contents($tmp, $code, LOCK_EX) === false) {
        return false;
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    clearstatcache(true, $file);
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }
    return (string)@file_get_contents($file) === $code;
}
