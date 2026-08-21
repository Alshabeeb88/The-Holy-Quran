<?php
declare(strict_types=1);

/**
 * Storage layer for the X publishing studio.
 *
 * Holds the live weekly plan in a single protected file next to the other
 * runtime state of this project, using the same protections those files use:
 * a "<?php exit; ?>" guard as the first line, mode 0640, a directory that
 * Apache denies, and an entry in .gitignore.
 *
 * This file only reads, validates and writes. It knows nothing about HTTP,
 * sessions or the page that will eventually use it: no endpoint, no output,
 * no side effects on include.
 */

// ---------------------------------------------------------------------------
// Result statuses
// ---------------------------------------------------------------------------

/** The call did what was asked. */
const QFA_X_OK = 'OK';
/** No plan file exists yet. Distinct from a damaged one on purpose. */
const QFA_X_NOT_FOUND = 'NOT_FOUND';
/** The file exists but cannot be parsed: empty, wrong guard, or broken JSON. */
const QFA_X_CORRUPT = 'CORRUPT';
/** The file parses but the data breaks the schema or a state invariant. */
const QFA_X_INVALID = 'INVALID';
/** The file exists but could not be opened for reading. */
const QFA_X_UNREADABLE = 'UNREADABLE';
/** The file was written by a newer version of this layer. */
const QFA_X_UNSUPPORTED_SCHEMA = 'UNSUPPORTED_SCHEMA';
/** Someone else changed the plan since the caller last read it. */
const QFA_X_CONFLICT = 'CONFLICT';
/** The write could not be completed; the live file was left untouched. */
const QFA_X_WRITE_FAILED = 'WRITE_FAILED';
/** Seeding found a plan already in place and left it alone. */
const QFA_X_ALREADY_EXISTS = 'ALREADY_EXISTS';
/** The live plan already covers the current week, so there is nothing to roll. */
const QFA_X_CURRENT_WEEK_ACTIVE = 'CURRENT_WEEK_ACTIVE';
/** A different plan is already archived under this week id; both are kept. */
const QFA_X_ARCHIVE_CONFLICT = 'ARCHIVE_CONFLICT';

// ---------------------------------------------------------------------------
// Schema limits
// ---------------------------------------------------------------------------

const QFA_X_SCHEMA = 1;
const QFA_X_MAX_POSTS = 21;              // 3 posts a day across 7 days
const QFA_X_MAX_TEXT = 2000;             // characters, not bytes
const QFA_X_MAX_PLACEHOLDERS = 8;
const QFA_X_MAX_PLACEHOLDER_LEN = 64;
const QFA_X_MAX_POST_ID_LEN = 32;

const QFA_X_STATUSES = ['draft', 'active', 'closed'];
const QFA_X_TYPES = ['ayah', 'dhikr', 'dua', 'tafseer', 'recitation', 'adhkar', 'site', 'friday', 'kahf'];
const QFA_X_SOURCE_TYPES = ['surah', 'page', 'adhkar', 'none'];
const QFA_X_ADHKAR_REFS = ['morning', 'evening'];

const QFA_X_SURAH_MIN = 1;
const QFA_X_SURAH_MAX = 114;
const QFA_X_PAGE_MIN = 1;
const QFA_X_PAGE_MAX = 604;

// ---------------------------------------------------------------------------
// Paths
// ---------------------------------------------------------------------------

/**
 * The one canonical location of the live plan. Fixed at include time from
 * __DIR__, never assembled from anything a request can influence.
 */
function qfa_x_store_path(): string {
    return __DIR__ . '/x_studio_current.php';
}

/** Single rollback copy of the last known-good plan. */
function qfa_x_store_backup_path(): string {
    return qfa_x_store_path() . '.bak';
}

/**
 * Resolve the file a call should act on.
 *
 * Tests need to work against a throwaway file, but a path argument that an
 * HTTP request could reach would be a path traversal waiting to happen. So the
 * override is honoured only under the CLI SAPI: over HTTP this function always
 * returns the canonical path, whatever it is handed. There is no filtering to
 * get wrong, because the untrusted transport simply cannot select a path.
 */
function qfa_x_store_resolve(?string $file): string {
    if ($file === null || $file === '' || PHP_SAPI !== 'cli') {
        return qfa_x_store_path();
    }
    if (strpos($file, "\0") !== false) {
        return qfa_x_store_path();
    }
    return $file;
}

// ---------------------------------------------------------------------------
// Small helpers
// ---------------------------------------------------------------------------

/** The guard every runtime data file in this project starts with. */
function qfa_x_guard(): string {
    return "<?php exit; ?>\n";
}

/**
 * The studio's own time zone.
 *
 * The plan is written and read in Riyadh time because that is what its posting
 * times mean, and because "which week is it" must not change with the server's
 * configuration. This is applied per call inside the studio's own functions;
 * the project's global time zone is deliberately left alone, so nothing else on
 * the site is affected.
 */
const QFA_X_TIMEZONE = 'Asia/Riyadh';

function qfa_x_timezone(): DateTimeZone {
    return new DateTimeZone(QFA_X_TIMEZONE);
}

/**
 * Server-side timestamp. Client-supplied times are never trusted for this.
 *
 * The format stays offset-aware ISO-8601 exactly as before; pinning the zone
 * only fixes which offset is written, so already stored timestamps remain valid.
 */
function qfa_x_now(): string {
    return (new DateTimeImmutable('now', qfa_x_timezone()))->format(DATE_ATOM);
}

/**
 * Character count that does not depend on mbstring, which this project treats
 * as optional: every byte that is not a UTF-8 continuation byte starts one
 * character.
 */
function qfa_x_text_length(string $text): int {
    if (function_exists('mb_strlen')) {
        return (int)mb_strlen($text, 'UTF-8');
    }
    return (int)preg_match_all('~[^\x80-\xBF]~', $text);
}

/** True when the value is a string holding well-formed UTF-8. */
function qfa_x_is_utf8($value): bool {
    return is_string($value) && preg_match('//u', $value) === 1;
}

/** True for a real calendar date written exactly as YYYY-MM-DD. */
function qfa_x_is_date($value): bool {
    if (!is_string($value) || !preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $value, $m)) {
        return false;
    }
    // checkdate rejects 2026-02-30 and friends, which the pattern alone allows.
    return checkdate((int)$m[2], (int)$m[3], (int)$m[1]);
}

/** True for an ISO-8601 timestamp that round-trips exactly. */
function qfa_x_is_timestamp($value): bool {
    if (!is_string($value) || $value === '') {
        return false;
    }
    $parsed = DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
    return $parsed !== false && $parsed->format(DATE_ATOM) === $value;
}

/**
 * True for an ISO-8601 week that actually exists, written as YYYY-Www.
 *
 * The shape alone is not enough: most years have 52 weeks, so a value such as
 * 2027-W53 is well-formed and still names a week that never happens. Asking the
 * calendar settles it, because setISODate rolls an out-of-range week into the
 * following year, which the round-trip then catches.
 */
function qfa_x_is_iso_week($value): bool {
    if (!is_string($value) || !preg_match('~^(\d{4})-W(\d{2})$~', $value, $m)) {
        return false;
    }

    $year = (int)$m[1];
    $week = (int)$m[2];
    if ($week < 1 || $week > 53) {
        return false;
    }

    $date = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->setISODate($year, $week, 1);
    return $date->format('o') === $m[1] && $date->format('W') === $m[2];
}

/** Days between two YYYY-MM-DD dates, or null when either is unusable. */
function qfa_x_day_span(string $from, string $to): ?int {
    $a = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
    $b = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
    if ($a === false || $b === false) {
        return null;
    }
    return (int)$a->diff($b)->format('%r%a');
}

/** A strict integer: rejects "3", 3.5 and true, which is_numeric would allow. */
function qfa_x_is_int($value): bool {
    return is_int($value);
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

/**
 * Check a plan against the schema and the state invariants.
 *
 * Returns a list of human-readable problems; an empty list means valid. Every
 * problem is reported rather than only the first, so a caller can show the
 * whole picture instead of one error at a time.
 */
function qfa_x_store_validate($plan): array {
    $errors = [];

    if (!is_array($plan)) {
        return ['plan: expected an object'];
    }

    // --- schema -----------------------------------------------------------
    if (!array_key_exists('schema', $plan)) {
        $errors[] = 'schema: missing';
    } elseif (!qfa_x_is_int($plan['schema'])) {
        $errors[] = 'schema: must be an integer';
    } elseif ($plan['schema'] !== QFA_X_SCHEMA) {
        $errors[] = 'schema: unsupported version ' . $plan['schema'];
    }

    // --- week -------------------------------------------------------------
    $week = $plan['week'] ?? null;
    if (!is_array($week)) {
        $errors[] = 'week: missing or not an object';
        $week = [];
    }

    if (!qfa_x_is_iso_week($week['week_id'] ?? null)) {
        $errors[] = 'week.week_id: must be a real ISO week written as 2026-W35';
    }

    $start = $week['start_date'] ?? null;
    $end = $week['end_date'] ?? null;
    if (!qfa_x_is_date($start)) {
        $errors[] = 'week.start_date: must be a real YYYY-MM-DD date';
    }
    if (!qfa_x_is_date($end)) {
        $errors[] = 'week.end_date: must be a real YYYY-MM-DD date';
    }
    if (qfa_x_is_date($start) && qfa_x_is_date($end)) {
        $span = qfa_x_day_span((string)$start, (string)$end);
        if ($span !== 6) {
            // A weekly plan that does not span a week is a bug upstream, not a
            // preference: the seven day tabs would no longer match the dates.
            $errors[] = 'week: end_date must be exactly 6 days after start_date';
        }
    }

    if (!is_string($week['status'] ?? null) || !in_array($week['status'], QFA_X_STATUSES, true)) {
        $errors[] = 'week.status: must be one of ' . implode(', ', QFA_X_STATUSES);
    }

    foreach (['created_at', 'updated_at'] as $field) {
        if (!qfa_x_is_timestamp($week[$field] ?? null)) {
            $errors[] = 'week.' . $field . ': must be an ISO-8601 timestamp';
        }
    }
    if (qfa_x_is_timestamp($week['created_at'] ?? null) && qfa_x_is_timestamp($week['updated_at'] ?? null)) {
        if (strtotime((string)$week['updated_at']) < strtotime((string)$week['created_at'])) {
            $errors[] = 'week: updated_at is before created_at';
        }
    }

    if (!qfa_x_is_int($week['revision'] ?? null) || (int)($week['revision'] ?? -1) < 0) {
        $errors[] = 'week.revision: must be an integer of 0 or more';
    }

    // --- posts ------------------------------------------------------------
    $posts = $plan['posts'] ?? null;
    if (!is_array($posts)) {
        $errors[] = 'posts: missing or not an array';
        return $errors;
    }
    // A JSON object would arrive as an associative array and quietly break any
    // caller that assumes a list, so require real sequential keys.
    if ($posts !== [] && array_keys($posts) !== range(0, count($posts) - 1)) {
        $errors[] = 'posts: must be a list, not an object';
        return $errors;
    }
    if (count($posts) > QFA_X_MAX_POSTS) {
        $errors[] = 'posts: at most ' . QFA_X_MAX_POSTS . ' entries, got ' . count($posts);
    }

    $seenIds = [];
    $seenSlots = [];
    foreach ($posts as $index => $post) {
        $at = 'posts[' . $index . ']';

        if (!is_array($post)) {
            $errors[] = $at . ': expected an object';
            continue;
        }

        // post_id
        $postId = $post['post_id'] ?? null;
        if (!is_string($postId)
            || strlen($postId) > QFA_X_MAX_POST_ID_LEN
            || !preg_match('~^\d{4}-W\d{2}-\d{2}$~', $postId)) {
            $errors[] = $at . '.post_id: must look like 2026-W35-01';
        } else {
            if (isset($seenIds[$postId])) {
                $errors[] = $at . '.post_id: duplicate of posts[' . $seenIds[$postId] . ']';
            }
            $seenIds[$postId] = $index;
        }

        // day
        $day = $post['day'] ?? null;
        if (!qfa_x_is_int($day) || $day < 0 || $day > 6) {
            $errors[] = $at . '.day: must be an integer from 0 to 6';
        }

        // time
        $time = $post['time'] ?? null;
        if (!is_string($time) || !preg_match('~^([01]\d|2[0-3]):[0-5]\d$~', $time)) {
            $errors[] = $at . '.time: must be HH:MM on a 24 hour clock';
        }

        // Two posts scheduled at the same minute of the same day is a planning
        // mistake rather than an intention, and it makes the day view ambiguous.
        if (qfa_x_is_int($day) && is_string($time) && $day >= 0 && $day <= 6) {
            $slot = $day . ' ' . $time;
            if (isset($seenSlots[$slot])) {
                $errors[] = $at . ': same day and time as posts[' . $seenSlots[$slot] . ']';
            }
            $seenSlots[$slot] = $index;
        }

        // type
        if (!is_string($post['type'] ?? null) || !in_array($post['type'], QFA_X_TYPES, true)) {
            $errors[] = $at . '.type: must be one of ' . implode(', ', QFA_X_TYPES);
        }

        // text
        $text = $post['text'] ?? null;
        if (!qfa_x_is_utf8($text)) {
            $errors[] = $at . '.text: must be a valid UTF-8 string';
        } elseif (trim((string)$text) === '') {
            $errors[] = $at . '.text: must not be empty';
        } elseif (qfa_x_text_length((string)$text) > QFA_X_MAX_TEXT) {
            $errors[] = $at . '.text: longer than ' . QFA_X_MAX_TEXT . ' characters';
        }

        // approved / published flags and their timestamps
        foreach (['approved' => 'approved_at', 'published' => 'published_at'] as $flag => $stamp) {
            $flagValue = $post[$flag] ?? null;
            if (!is_bool($flagValue)) {
                $errors[] = $at . '.' . $flag . ': must be true or false';
                continue;
            }

            $stampValue = $post[$stamp] ?? null;
            if ($stampValue !== null && !qfa_x_is_timestamp($stampValue)) {
                $errors[] = $at . '.' . $stamp . ': must be null or an ISO-8601 timestamp';
                continue;
            }
            // A timestamp without the flag is a contradiction: it claims an
            // event that the record simultaneously denies happened.
            if ($flagValue === false && $stampValue !== null) {
                $errors[] = $at . '.' . $stamp . ': must be null while ' . $flag . ' is false';
            }
        }

        /*
         * intent_opened_at records only that the X composer was opened. It is
         * deliberately not tied to published in either direction: opening the
         * composer proves nothing was published, and an administrator may mark
         * a post published that went out by some other route.
         */
        $intent = $post['intent_opened_at'] ?? null;
        if ($intent !== null && !qfa_x_is_timestamp($intent)) {
            $errors[] = $at . '.intent_opened_at: must be null or an ISO-8601 timestamp';
        }

        // source_type / source_ref
        $sourceType = $post['source_type'] ?? null;
        $sourceRef = $post['source_ref'] ?? null;
        if (!is_string($sourceType) || !in_array($sourceType, QFA_X_SOURCE_TYPES, true)) {
            $errors[] = $at . '.source_type: must be one of ' . implode(', ', QFA_X_SOURCE_TYPES);
        } else {
            switch ($sourceType) {
                case 'surah':
                    if (!qfa_x_is_int($sourceRef) || $sourceRef < QFA_X_SURAH_MIN || $sourceRef > QFA_X_SURAH_MAX) {
                        $errors[] = $at . '.source_ref: surah number must be ' . QFA_X_SURAH_MIN . '-' . QFA_X_SURAH_MAX;
                    }
                    break;
                case 'page':
                    if (!qfa_x_is_int($sourceRef) || $sourceRef < QFA_X_PAGE_MIN || $sourceRef > QFA_X_PAGE_MAX) {
                        $errors[] = $at . '.source_ref: page number must be ' . QFA_X_PAGE_MIN . '-' . QFA_X_PAGE_MAX;
                    }
                    break;
                case 'adhkar':
                    if (!is_string($sourceRef) || !in_array($sourceRef, QFA_X_ADHKAR_REFS, true)) {
                        $errors[] = $at . '.source_ref: must be one of ' . implode(', ', QFA_X_ADHKAR_REFS);
                    }
                    break;
                case 'none':
                    if ($sourceRef !== null) {
                        $errors[] = $at . '.source_ref: must be null when source_type is none';
                    }
                    break;
            }
        }

        // link_placeholders
        $placeholders = $post['link_placeholders'] ?? null;
        if (!is_array($placeholders)) {
            $errors[] = $at . '.link_placeholders: must be an array';
        } elseif ($placeholders !== [] && array_keys($placeholders) !== range(0, count($placeholders) - 1)) {
            $errors[] = $at . '.link_placeholders: must be a list, not an object';
        } elseif (count($placeholders) > QFA_X_MAX_PLACEHOLDERS) {
            $errors[] = $at . '.link_placeholders: at most ' . QFA_X_MAX_PLACEHOLDERS . ' entries';
        } else {
            foreach ($placeholders as $slot => $placeholder) {
                if (!qfa_x_is_utf8($placeholder) || $placeholder === '') {
                    $errors[] = $at . '.link_placeholders[' . $slot . ']: must be a non-empty UTF-8 string';
                } elseif (qfa_x_text_length((string)$placeholder) > QFA_X_MAX_PLACEHOLDER_LEN) {
                    $errors[] = $at . '.link_placeholders[' . $slot . ']: longer than ' . QFA_X_MAX_PLACEHOLDER_LEN . ' characters';
                }
            }
        }

        // content_hash: absent for now, but validated whenever it is present.
        $hash = $post['content_hash'] ?? null;
        if ($hash !== null && (!is_string($hash) || !preg_match('~^sha256:[0-9a-f]{64}$~', $hash))) {
            $errors[] = $at . '.content_hash: must be null or sha256:<64 hex characters>';
        }
    }

    return $errors;
}

// ---------------------------------------------------------------------------
// Reading
// ---------------------------------------------------------------------------

/**
 * Decode a raw file body into a plan.
 *
 * The guard line is required rather than merely stripped: a file that does not
 * start with it is not one of ours, and treating it as data would be guessing.
 *
 * @return array{status:string, data:?array, errors:string[]}
 */
function qfa_x_store_decode(string $raw): array {
    if (trim($raw) === '') {
        return ['status' => QFA_X_CORRUPT, 'data' => null, 'errors' => ['file is empty']];
    }

    if (!preg_match('~^<\?php\s+exit;\s*\?>\s*~', $raw, $guard)) {
        return ['status' => QFA_X_CORRUPT, 'data' => null, 'errors' => ['missing or malformed guard line']];
    }

    $json = substr($raw, strlen($guard[0]));
    if (trim($json) === '') {
        return ['status' => QFA_X_CORRUPT, 'data' => null, 'errors' => ['no JSON after the guard line']];
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['status' => QFA_X_CORRUPT, 'data' => null, 'errors' => ['broken JSON: ' . json_last_error_msg()]];
    }

    /*
     * A newer schema is reported separately from invalid data. Older code must
     * refuse to touch a file it does not fully understand rather than rewrite
     * it and drop whatever fields it did not know about.
     */
    if (array_key_exists('schema', $data) && is_int($data['schema']) && $data['schema'] > QFA_X_SCHEMA) {
        return [
            'status' => QFA_X_UNSUPPORTED_SCHEMA,
            'data' => null,
            'errors' => ['written by schema ' . $data['schema'] . ', this layer understands ' . QFA_X_SCHEMA],
        ];
    }

    $errors = qfa_x_store_validate($data);
    if ($errors !== []) {
        return ['status' => QFA_X_INVALID, 'data' => null, 'errors' => $errors];
    }

    return ['status' => QFA_X_OK, 'data' => $data, 'errors' => []];
}

/**
 * Read the live plan.
 *
 * A missing file and a damaged one are different answers on purpose: only the
 * first means "no plan yet". Reporting corruption as absence would invite a
 * later caller to create a fresh week straight over a file that still holds
 * the real one.
 *
 * @return array{status:string, data:?array, errors:string[]}
 */
function qfa_x_store_read(?string $file = null): array {
    $path = qfa_x_store_resolve($file);

    clearstatcache(true, $path);
    if (!is_file($path)) {
        return ['status' => QFA_X_NOT_FOUND, 'data' => null, 'errors' => []];
    }

    $handle = @fopen($path, 'r');
    if ($handle === false) {
        return ['status' => QFA_X_UNREADABLE, 'data' => null, 'errors' => ['cannot open the plan for reading']];
    }

    // A shared lock keeps a concurrent replacement from being read half-written.
    @flock($handle, LOCK_SH);
    $raw = stream_get_contents($handle);
    @flock($handle, LOCK_UN);
    fclose($handle);

    if ($raw === false) {
        return ['status' => QFA_X_UNREADABLE, 'data' => null, 'errors' => ['cannot read the plan']];
    }

    return qfa_x_store_decode((string)$raw);
}

// ---------------------------------------------------------------------------
// Writing
// ---------------------------------------------------------------------------

/**
 * Put bytes at a path atomically: write a sibling temporary file, then rename
 * over the target. A reader therefore sees either the whole old file or the
 * whole new one, never a partial write.
 *
 * The temporary file is created in the target's own directory so the rename
 * stays within one filesystem, where it is atomic. On any failure the
 * temporary file is removed and the target is left exactly as it was.
 *
 * This is the shared primitive the archive will reuse.
 */
function qfa_x_store_atomic_put(string $path, string $payload): bool {
    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        return false;
    }

    try {
        $suffix = bin2hex(random_bytes(5));
    } catch (Exception $e) {
        return false;
    }
    $tmp = $path . '.tmp-' . $suffix;

    $handle = @fopen($tmp, 'x');   // exclusive create: never reuse a stray file
    if ($handle === false) {
        return false;
    }

    $written = @fwrite($handle, $payload);
    if ($written === false || $written !== strlen($payload)) {
        fclose($handle);
        @unlink($tmp);
        return false;
    }

    // Reach the filesystem before the rename, so a crash cannot leave the new
    // name pointing at an empty file.
    @fflush($handle);
    fclose($handle);
    @chmod($tmp, 0640);

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }

    clearstatcache(true, $path);
    return true;
}

/** Wrap a plan in the guard line and encode it. Returns null if it cannot. */
function qfa_x_store_encode(array $plan): ?string {
    $json = json_encode(
        $plan,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );
    if (!is_string($json)) {
        return null;
    }
    return qfa_x_guard() . $json . "\n";
}

/**
 * Replace the live plan, refusing if it changed since the caller read it.
 *
 * $expected_revision is the revision the caller believes is on disk; pass null
 * to mean "there should be no plan yet". A mismatch returns CONFLICT and the
 * file is not touched, so a second tab cannot silently overwrite the first.
 *
 * The stored revision, created_at and updated_at are always decided here. The
 * caller's values for them are ignored: a client that could name its own
 * revision could defeat the conflict check simply by claiming a higher number.
 *
 * @return array{status:string, revision:?int, errors:string[]}
 */
function qfa_x_store_write(array $plan, ?int $expected_revision, ?string $file = null): array {
    $path = qfa_x_store_resolve($file);

    $fail = static function (string $status, array $errors = [], ?int $revision = null): array {
        return ['status' => $status, 'revision' => $revision, 'errors' => $errors];
    };

    // Validate before touching anything, so a bad plan cannot reach the disk.
    // revision and the timestamps are filled in below, so seed them for the
    // check and let the real values replace them afterwards.
    $candidate = $plan;
    $candidate['week']['revision'] = 0;
    $candidate['week']['created_at'] = qfa_x_now();
    $candidate['week']['updated_at'] = $candidate['week']['created_at'];
    $errors = qfa_x_store_validate($candidate);
    if ($errors !== []) {
        return $fail(QFA_X_INVALID, $errors);
    }

    $directory = dirname($path);
    if (!is_dir($directory) || !is_writable($directory)) {
        return $fail(QFA_X_WRITE_FAILED, ['storage directory is not writable']);
    }

    /*
     * Hold an exclusive lock across the read-compare-write cycle so two writers
     * cannot both pass the revision check. The lock is taken on the live file
     * itself; "c+" creates it when absent without truncating an existing one.
     *
     * Whether the file was already there is noted first, because "c+" is about
     * to create it either way and an empty file has to mean different things in
     * the two cases: nothing yet when we just made it, but damage when it was
     * already on disk.
     */
    clearstatcache(true, $path);
    $existed = is_file($path);

    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        return $fail(QFA_X_WRITE_FAILED, ['cannot open the plan for writing']);
    }
    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return $fail(QFA_X_WRITE_FAILED, ['cannot lock the plan']);
    }

    $release = static function ($handle): void {
        @flock($handle, LOCK_UN);
        fclose($handle);
    };

    /*
     * Between opening and locking, another writer may already have renamed a
     * new file into place; the handle would then point at an unlinked inode and
     * every check below would run against a plan that is no longer live. Detect
     * that and report a conflict instead of writing from stale state.
     */
    $onDisk = @fstat($handle);
    clearstatcache(true, $path);
    $atPath = @stat($path);
    if (is_array($onDisk) && is_array($atPath) && !empty($onDisk['ino']) && !empty($atPath['ino'])) {
        if ($onDisk['ino'] !== $atPath['ino'] || $onDisk['dev'] !== $atPath['dev']) {
            $release($handle);
            return $fail(QFA_X_CONFLICT, ['the plan was replaced while this write was starting']);
        }
    }

    rewind($handle);
    $raw = stream_get_contents($handle);
    if ($raw === false) {
        $release($handle);
        return $fail(QFA_X_WRITE_FAILED, ['cannot read the current plan']);
    }
    $raw = (string)$raw;

    $current = null;
    $currentRevision = null;

    /*
     * An empty file that was already on disk is reported as damage, matching
     * what a read of it would say. It is never treated as "no plan yet": that
     * would let a write silently fill in a file whose contents went missing,
     * which is exactly the case a caller most needs to be told about.
     */
    if ($existed && trim($raw) === '') {
        $release($handle);
        return $fail(QFA_X_CORRUPT, ['the plan file is empty']);
    }

    if (trim($raw) !== '') {
        $decoded = qfa_x_store_decode($raw);
        if ($decoded['status'] !== QFA_X_OK) {
            /*
             * Never write over a file we could not understand. Whatever is in
             * there may be the only copy of real work, and overwriting it would
             * turn a recoverable problem into a permanent one.
             */
            $release($handle);
            return $fail($decoded['status'], $decoded['errors']);
        }
        $current = $decoded['data'];
        $currentRevision = (int)$current['week']['revision'];
    }

    // Compare-and-swap on the revision.
    if ($currentRevision !== $expected_revision) {
        $release($handle);
        return $fail(
            QFA_X_CONFLICT,
            [
                'expected revision ' . var_export($expected_revision, true)
                . ' but the stored plan is at ' . var_export($currentRevision, true),
            ],
            $currentRevision
        );
    }

    $now = qfa_x_now();
    $plan['schema'] = QFA_X_SCHEMA;
    $plan['week']['revision'] = $currentRevision === null ? 0 : $currentRevision + 1;
    $plan['week']['updated_at'] = $now;
    // created_at belongs to the file, not to whoever is writing now.
    $plan['week']['created_at'] = $current === null ? $now : $current['week']['created_at'];

    // Re-check with the final server-set values in place.
    $errors = qfa_x_store_validate($plan);
    if ($errors !== []) {
        $release($handle);
        return $fail(QFA_X_INVALID, $errors);
    }

    $payload = qfa_x_store_encode($plan);
    if ($payload === null) {
        $release($handle);
        return $fail(QFA_X_WRITE_FAILED, ['cannot encode the plan as JSON']);
    }

    /*
     * Refresh the rollback copy from the bytes currently on disk, which have
     * just been parsed successfully, so the backup can only ever hold a plan
     * that was valid. If it cannot be written, stop: continuing would replace
     * the live plan while leaving no way back, and the caller can retry safely
     * because nothing has changed yet.
     */
    if ($current !== null) {
        // Derived from the file actually being written, never from the canonical
        // path, so a test against a throwaway file cannot drop a .bak beside the
        // real plan.
        if (!qfa_x_store_atomic_put($path . '.bak', $raw)) {
            $release($handle);
            return $fail(QFA_X_WRITE_FAILED, ['cannot refresh the rollback copy; the plan was left unchanged']);
        }
    }

    if (!qfa_x_store_atomic_put($path, $payload)) {
        $release($handle);
        return $fail(QFA_X_WRITE_FAILED, ['cannot replace the plan; it was left unchanged']);
    }

    $release($handle);

    return ['status' => QFA_X_OK, 'revision' => $plan['week']['revision'], 'errors' => []];
}

/**
 * Read the rollback copy, if there is one. Same statuses as a normal read, so
 * a damaged backup is reported rather than silently offered as good data.
 *
 * @return array{status:string, data:?array, errors:string[]}
 */
function qfa_x_store_read_backup(?string $file = null): array {
    $backup = qfa_x_store_resolve($file) . '.bak';

    clearstatcache(true, $backup);
    if (!is_file($backup)) {
        return ['status' => QFA_X_NOT_FOUND, 'data' => null, 'errors' => []];
    }

    $raw = @file_get_contents($backup);
    if ($raw === false) {
        return ['status' => QFA_X_UNREADABLE, 'data' => null, 'errors' => ['cannot read the rollback copy']];
    }

    return qfa_x_store_decode((string)$raw);
}

// ---------------------------------------------------------------------------
// Seeding the first week
// ---------------------------------------------------------------------------

/**
 * Build the week metadata for the week that contains a given moment.
 *
 * The studio's week runs Sunday to Saturday, which is not the ISO week, so the
 * two are kept apart deliberately:
 *
 *   - start_date is the Sunday of the operating week and end_date is start + 6.
 *   - week_id is only an identifier. It is taken from the Monday that falls
 *     inside the operating week, so the label stays a real ISO week and every
 *     operating week maps to exactly one id, even across a year boundary.
 *
 * @return array{week_id:string, start_date:string, end_date:string}
 */
function qfa_x_store_week_bounds(?DateTimeImmutable $now = null): array {
    $now = ($now ?? new DateTimeImmutable('now'))->setTimezone(qfa_x_timezone());

    // 'w' is 0 for Sunday, so this lands on the Sunday that opened this week.
    $start = $now->setTime(0, 0, 0)->modify('-' . (int)$now->format('w') . ' days');
    $end = $start->modify('+6 days');

    // The Monday inside the operating week decides the ISO label.
    $isoReference = $start->modify('+1 day');

    return [
        'week_id' => $isoReference->format('o-\WW'),
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d'),
    ];
}

/**
 * Turn the shared weekly template into a plan for a specific week.
 *
 * Every post starts untouched: nothing approved, nothing published, no intent
 * recorded and no content hash, because none of those things have happened yet.
 */
function qfa_x_store_build_week(array $bounds): array {
    require_once __DIR__ . '/x-studio-plan.php';

    $now = qfa_x_now();
    $posts = [];
    $sequence = 0;

    foreach (qfa_x_studio_plan_posts() as $template) {
        $sequence++;

        $posts[] = [
            // Derived from the week and the position in it, so re-seeding the
            // same week produces the same ids rather than new ones.
            'post_id' => sprintf('%s-%02d', $bounds['week_id'], $sequence),
            'day' => (int)$template['day'],
            'time' => (string)$template['time'],
            'type' => (string)$template['type'],
            'text' => (string)$template['text'],
            'approved' => false,
            'approved_at' => null,
            'intent_opened_at' => null,
            'published' => false,
            'published_at' => null,
            'source_type' => (string)$template['source_type'],
            'source_ref' => $template['source_ref'],
            'link_placeholders' => array_values($template['link_placeholders']),
            'content_hash' => null,
        ];
    }

    return [
        'schema' => QFA_X_SCHEMA,
        'week' => [
            'week_id' => $bounds['week_id'],
            'start_date' => $bounds['start_date'],
            'end_date' => $bounds['end_date'],
            'status' => 'draft',
            'created_at' => $now,
            'updated_at' => $now,
            'revision' => 0,
        ],
        'posts' => $posts,
    ];
}

/**
 * Create the plan for the current week, but only when there is none.
 *
 * This is create-if-absent and nothing else. It never repairs, replaces or
 * falls back: if a plan is already there it reports ALREADY_EXISTS, and if one
 * is there but cannot be understood it reports that damage unchanged. Writing
 * over a file this layer could not parse would turn a recoverable problem into
 * a permanent one.
 *
 * The absence check is not a separate look before the write. It is the storage
 * layer's own compare-and-swap: passing null as the expected revision means
 * "there must be no plan yet", and that is settled under the exclusive lock,
 * so two callers racing to seed cannot both succeed.
 *
 * @return array{status:string, revision:?int, errors:string[]}
 */
function qfa_x_store_seed_week(?string $file = null, ?DateTimeImmutable $now = null): array {
    $plan = qfa_x_store_build_week(qfa_x_store_week_bounds($now));

    $result = qfa_x_store_write($plan, null, $file);

    /*
     * A conflict here can only mean one thing: the expected revision was null,
     * so the plan the write found was one that already existed.
     */
    if ($result['status'] === QFA_X_CONFLICT) {
        return [
            'status' => QFA_X_ALREADY_EXISTS,
            'revision' => $result['revision'],
            'errors' => ['a plan already exists; seeding leaves it untouched'],
        ];
    }

    return $result;
}

// ---------------------------------------------------------------------------
// Archiving a finished week
// ---------------------------------------------------------------------------

/**
 * Where finished weeks are kept.
 *
 * Derived from the live plan's own directory, so a test working against a
 * throwaway file archives beside that file and never near the real one. The
 * directory sits inside includes/, which Apache denies wholesale, and every
 * file written into it carries the same "<?php exit; ?>" guard as the live plan.
 */
function qfa_x_archive_dir(?string $file = null): string {
    return dirname(qfa_x_store_resolve($file)) . '/x_studio_archive';
}

/**
 * The archive file for a week, or null when the week id is not one we produced.
 *
 * The name is built from a week id that has already been checked against the
 * calendar, so it can only ever be four digits, a W and two digits. Nothing a
 * request could influence reaches this, and there is no separator to escape:
 * traversal is not filtered here, it is impossible.
 */
function qfa_x_archive_path(string $weekId, ?string $file = null): ?string {
    if (!qfa_x_is_iso_week($weekId)) {
        return null;
    }
    return qfa_x_archive_dir($file) . '/x_studio_' . $weekId . '.php';
}

/**
 * Create the archive directory if it is missing, with its own guards.
 *
 * The directory is already unreachable through includes/.htaccess; these are the
 * second and third layers, in the same spirit as the cache directory, so the
 * protection does not rest on one file.
 */
function qfa_x_archive_prepare(?string $file = null): bool {
    $dir = qfa_x_archive_dir($file);

    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        return false;
    }

    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess)) {
        @file_put_contents(
            $htaccess,
            "# Written automatically. Archived weeks are private data and must never\n"
            . "# be served. Each file also carries its own PHP exit guard.\n"
            . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
            . "<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
            . "Options -Indexes -ExecCGI\n",
            LOCK_EX
        );
        @chmod($htaccess, 0640);
    }

    $index = $dir . '/index.php';
    if (!is_file($index)) {
        @file_put_contents($index, "<?php http_response_code(403); exit;\n", LOCK_EX);
        @chmod($index, 0640);
    }

    return is_dir($dir) && is_writable($dir);
}

/**
 * Read one archived week. Same statuses as reading the live plan, so a damaged
 * archive is reported rather than quietly treated as absent.
 *
 * @return array{status:string, data:?array, errors:string[]}
 */
function qfa_x_archive_read(string $weekId, ?string $file = null): array {
    $path = qfa_x_archive_path($weekId, $file);
    if ($path === null) {
        return ['status' => QFA_X_INVALID, 'data' => null, 'errors' => ['not a valid week id']];
    }

    clearstatcache(true, $path);
    if (!is_file($path)) {
        return ['status' => QFA_X_NOT_FOUND, 'data' => null, 'errors' => []];
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        return ['status' => QFA_X_UNREADABLE, 'data' => null, 'errors' => ['cannot read the archived week']];
    }

    return qfa_x_store_decode((string)$raw);
}

/**
 * Write an archive file only if that week has never been archived.
 *
 * "x" mode is what makes this safe: the create and the existence check are the
 * same operation in the kernel, so two callers racing to archive the same week
 * cannot both believe they created it.
 */
function qfa_x_archive_create(string $path, string $payload): bool {
    $handle = @fopen($path, 'x');
    if ($handle === false) {
        return false;   // already there, or unwritable
    }

    $written = @fwrite($handle, $payload);
    if ($written === false || $written !== strlen($payload)) {
        fclose($handle);
        @unlink($path);
        return false;
    }

    @fflush($handle);
    fclose($handle);
    @chmod($path, 0640);
    clearstatcache(true, $path);

    return true;
}

/**
 * Move to the current week, keeping the week that is being replaced.
 *
 * The order is chosen so that no failure can lose a plan:
 *
 *   1. read and validate the live plan; a damaged one is never archived
 *   2. refuse if it already covers the current week, so nothing is rolled twice
 *   3. write the archive, creating it only if that week is not archived yet
 *   4. read the archive back and compare it to what was meant to be stored
 *   5. only then replace the live plan with a fresh week
 *
 * The live plan is replaced, never deleted first, and replacement happens after
 * the archive is on disk and verified. If step 5 fails the old plan is still
 * there and the archive already holds an identical copy, so repeating the call
 * simply takes the same path again.
 *
 * @return array{status:string, archived_week_id:?string, week:?array, revision:?int, errors:string[]}
 */
function qfa_x_store_rollover_week(?string $file = null, ?DateTimeImmutable $now = null): array {
    $fail = static function (string $status, array $errors = [], array $extra = []): array {
        return array_merge(
            ['status' => $status, 'archived_week_id' => null, 'week' => null, 'revision' => null, 'errors' => $errors],
            $extra
        );
    };

    $read = qfa_x_store_read($file);
    if ($read['status'] !== QFA_X_OK) {
        // CORRUPT, INVALID, UNREADABLE, NOT_FOUND: nothing is archived and
        // nothing is created. A plan we cannot read is not one we may replace.
        return $fail($read['status'], $read['errors']);
    }

    $plan = $read['data'];
    $weekId = (string)$plan['week']['week_id'];
    $bounds = qfa_x_store_week_bounds($now);

    if ($weekId === $bounds['week_id']) {
        return $fail(QFA_X_CURRENT_WEEK_ACTIVE, ['the stored plan already covers the current week']);
    }

    $path = qfa_x_archive_path($weekId, $file);
    if ($path === null) {
        return $fail(QFA_X_INVALID, ['the stored plan carries a week id this layer cannot archive']);
    }

    $payload = qfa_x_store_encode($plan);
    if ($payload === null) {
        return $fail(QFA_X_WRITE_FAILED, ['cannot encode the plan for the archive']);
    }

    if (!qfa_x_archive_prepare($file)) {
        return $fail(QFA_X_WRITE_FAILED, ['the archive directory is not available']);
    }

    clearstatcache(true, $path);
    if (is_file($path)) {
        /*
         * Already archived. Repeating a rollover that failed later on must be
         * safe, so an archive holding exactly this plan is accepted and the run
         * continues. Anything else is left strictly alone: two different weeks
         * under one id is a question only a person can answer.
         */
        $existing = qfa_x_archive_read($weekId, $file);
        if ($existing['status'] !== QFA_X_OK) {
            return $fail(QFA_X_ARCHIVE_CONFLICT, ['an archived week with this id exists but cannot be read']);
        }
        if ($existing['data'] !== $plan) {
            return $fail(QFA_X_ARCHIVE_CONFLICT, ['a different plan is already archived under this week id']);
        }
    } elseif (!qfa_x_archive_create($path, $payload)) {
        /*
         * Another caller may have created it between the check and here, which
         * "x" mode turns into a failure rather than an overwrite. Look again
         * before reporting a problem.
         */
        clearstatcache(true, $path);
        if (!is_file($path)) {
            return $fail(QFA_X_WRITE_FAILED, ['cannot write the archived week']);
        }
        $existing = qfa_x_archive_read($weekId, $file);
        if ($existing['status'] !== QFA_X_OK || $existing['data'] !== $plan) {
            return $fail(QFA_X_ARCHIVE_CONFLICT, ['a different plan is already archived under this week id']);
        }
    } else {
        // Read back what was just written rather than trusting the write.
        $verify = qfa_x_archive_read($weekId, $file);
        if ($verify['status'] !== QFA_X_OK || $verify['data'] !== $plan) {
            return $fail(QFA_X_WRITE_FAILED, ['the archived week did not read back as written']);
        }
    }

    /*
     * The old week is safe on disk, so the live plan can now be replaced. The
     * revision keeps counting rather than restarting: a client holding an older
     * number must not be able to match the new plan by coincidence.
     */
    $fresh = qfa_x_store_build_week($bounds);
    $write = qfa_x_store_write($fresh, (int)$plan['week']['revision'], $file);

    if ($write['status'] !== QFA_X_OK) {
        // The old plan is untouched and the archive is in place; calling again
        // repeats the same steps.
        return $fail($write['status'], $write['errors'], ['archived_week_id' => $weekId]);
    }

    return [
        'status' => QFA_X_OK,
        'archived_week_id' => $weekId,
        'week' => [
            'week_id' => $bounds['week_id'],
            'start_date' => $bounds['start_date'],
            'end_date' => $bounds['end_date'],
            'post_count' => count($fresh['posts']),
        ],
        'revision' => $write['revision'],
        'errors' => [],
    ];
}

// ---------------------------------------------------------------------------
// Reading the archive
// ---------------------------------------------------------------------------

/**
 * The one pattern an archive file may be named.
 *
 * Listing walks the directory rather than trusting a caller, and only names
 * matching this are ever opened, so the directory's own guards (.htaccess,
 * index.php) and anything else that finds its way in there are never read as
 * data, let alone shown.
 */
function qfa_x_archive_week_id_from_name(string $basename): ?string {
    if (!preg_match('~^x_studio_(\d{4}-W\d{2})\.php$~', $basename, $m)) {
        return null;
    }
    return qfa_x_is_iso_week($m[1]) ? $m[1] : null;
}

/**
 * Summarise every archived week, newest first.
 *
 * Each file is decoded and validated before it contributes anything: a damaged
 * archive is listed with its status so the page can say so plainly, and its
 * contents are never treated as usable data.
 *
 * Sorting is a plain string comparison, which is correct here because the ids
 * are zero padded (2026-W09 sorts before 2026-W10).
 *
 * @return array<int, array{week_id:string, status:string, start_date:?string,
 *   end_date:?string, post_count:?int, published:?int, approved:?int, draft:?int}>
 */
function qfa_x_archive_list(?string $file = null): array {
    $dir = qfa_x_archive_dir($file);
    if (!is_dir($dir)) {
        return [];
    }

    $weeks = [];
    foreach ((glob($dir . '/x_studio_*.php') ?: []) as $path) {
        $weekId = qfa_x_archive_week_id_from_name(basename($path));
        if ($weekId === null) {
            continue;   // not one of ours: never opened
        }

        $read = qfa_x_archive_read($weekId, $file);
        if ($read['status'] !== QFA_X_OK) {
            $weeks[$weekId] = [
                'week_id' => $weekId,
                'status' => $read['status'],
                'start_date' => null,
                'end_date' => null,
                'post_count' => null,
                'published' => null,
                'approved' => null,
                'draft' => null,
            ];
            continue;
        }

        $plan = $read['data'];
        $published = 0;
        $approved = 0;
        foreach ($plan['posts'] as $post) {
            if (($post['published'] ?? false) === true) {
                $published++;
            } elseif (($post['approved'] ?? false) === true) {
                $approved++;
            }
        }

        $weeks[$weekId] = [
            'week_id' => $weekId,
            'status' => QFA_X_OK,
            'start_date' => (string)$plan['week']['start_date'],
            'end_date' => (string)$plan['week']['end_date'],
            'post_count' => count($plan['posts']),
            'published' => $published,
            'approved' => $approved,
            'draft' => count($plan['posts']) - $published - $approved,
        ];
    }

    krsort($weeks, SORT_STRING);

    return array_values($weeks);
}

/**
 * One archived week, ready to display.
 *
 * The week id is the only thing a caller may name, and it has to survive the
 * calendar check before any path is built from it, so a filename or a traversal
 * cannot be expressed here at all. A week that is missing or damaged comes back
 * as a status, never as partial data.
 *
 * @return array{status:string, week:?array, posts:?array}
 */
function qfa_x_archive_week(string $weekId, ?string $file = null): array {
    if (!qfa_x_is_iso_week($weekId)) {
        return ['status' => QFA_X_INVALID, 'week' => null, 'posts' => null];
    }

    $read = qfa_x_archive_read($weekId, $file);
    if ($read['status'] !== QFA_X_OK) {
        return ['status' => $read['status'], 'week' => null, 'posts' => null];
    }

    return [
        'status' => QFA_X_OK,
        'week' => $read['data']['week'],
        'posts' => $read['data']['posts'],
    ];
}
