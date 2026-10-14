<?php
declare(strict_types=1);

/*
 * Time-based one-time passwords (RFC 6238, the codes shown by Google
 * Authenticator, Microsoft Authenticator, 1Password and similar apps) and
 * single-use recovery codes for the administrator's two-step sign-in.
 *
 * The secret and the recovery-code hashes live in the auth store
 * (includes/admin_auth_store.php) under "totp":
 *   secret      base32 secret shared with the authenticator app
 *   last_step   last accepted 30-second step, so a code cannot be used twice
 *   recovery    password_hash() of each unused recovery code
 *   enabled_at  when two-step sign-in was switched on
 */

if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(403);
    exit;
}

const QFA_TOTP_PERIOD = 30;
const QFA_TOTP_DIGITS = 6;
// Codes from one step either side are accepted, for phones whose clock drifts.
const QFA_TOTP_WINDOW = 1;
const QFA_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function qfa_base32_encode(string $bytes): string {
    $bits = '';
    foreach (str_split($bytes) as $char) {
        $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 5) as $chunk) {
        $out .= QFA_BASE32[bindec(str_pad($chunk, 5, '0'))];
    }
    return $out;
}

function qfa_base32_decode(string $text): string {
    $text = strtoupper(preg_replace('/[\s=-]+/', '', $text) ?? '');
    $bits = '';
    foreach (str_split($text) as $char) {
        $pos = strpos(QFA_BASE32, $char);
        if ($pos === false) {
            return '';
        }
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $out = '';
    foreach (str_split($bits, 8) as $byte) {
        if (strlen($byte) === 8) {
            $out .= chr(bindec($byte));
        }
    }
    return $out;
}

/** A new 160-bit secret, base32 encoded (32 characters). */
function qfa_totp_new_secret(): string {
    return qfa_base32_encode(random_bytes(20));
}

function qfa_totp_code(string $secret, int $step): string {
    $key = qfa_base32_decode($secret);
    $hmac = hash_hmac('sha1', pack('N2', ($step >> 32) & 0xffffffff, $step & 0xffffffff), $key, true);
    $offset = ord($hmac[19]) & 0x0f;
    $value = ((ord($hmac[$offset]) & 0x7f) << 24)
        | (ord($hmac[$offset + 1]) << 16)
        | (ord($hmac[$offset + 2]) << 8)
        | ord($hmac[$offset + 3]);
    return str_pad((string)($value % (10 ** QFA_TOTP_DIGITS)), QFA_TOTP_DIGITS, '0', STR_PAD_LEFT);
}

/**
 * The step a code belongs to, or null when it is wrong, outside the clock
 * window, or not newer than $lastStep (already used).
 */
function qfa_totp_match(string $secret, string $code, int $lastStep = 0, ?int $now = null): ?int {
    $code = preg_replace('/\D+/', '', $code) ?? '';
    if (strlen($code) !== QFA_TOTP_DIGITS || $secret === '') {
        return null;
    }
    $current = intdiv($now ?? time(), QFA_TOTP_PERIOD);
    for ($i = -QFA_TOTP_WINDOW; $i <= QFA_TOTP_WINDOW; $i++) {
        $step = $current + $i;
        if ($step > $lastStep && hash_equals(qfa_totp_code($secret, $step), $code)) {
            return $step;
        }
    }
    return null;
}

/** The link authenticator apps read from the QR code. */
function qfa_totp_uri(string $secret, string $account, string $issuer): string {
    return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account)
        . '?secret=' . $secret . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=' . QFA_TOTP_DIGITS . '&period=' . QFA_TOTP_PERIOD;
}

/**
 * Ten recovery codes such as "7KQ2-M9XD", shown once. Only their hashes are
 * kept, so the store never holds a usable code.
 *
 * @return array{0: string[], 1: string[]} [plain codes, hashes]
 */
function qfa_recovery_codes_new(int $count = 10): array {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $plain = [];
    $hashes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = '';
        for ($j = 0; $j < 8; $j++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $code = substr($code, 0, 4) . '-' . substr($code, 4);
        $plain[] = $code;
        $hashes[] = password_hash($code, PASSWORD_DEFAULT);
    }
    return [$plain, $hashes];
}

function qfa_recovery_normalize(string $code): string {
    $code = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $code) ?? '');
    return strlen($code) === 8 ? substr($code, 0, 4) . '-' . substr($code, 4) : '';
}

/** Index of the matching unused recovery code, or null. */
function qfa_recovery_match(array $hashes, string $code): ?int {
    $code = qfa_recovery_normalize($code);
    if ($code === '') {
        return null;
    }
    foreach ($hashes as $i => $hash) {
        if (is_string($hash) && password_verify($code, $hash)) {
            return (int)$i;
        }
    }
    return null;
}

function qfa_totp_enabled(array $data): bool {
    return !empty($data['totp']['secret']) && is_string($data['totp']['secret']);
}

/**
 * Check a sign-in code (authenticator or recovery) and record its use in the
 * same locked update, so two requests can never both spend one code.
 * Returns 'totp', 'recovery' or '' when the code is not accepted.
 */
function qfa_totp_consume(string $code): string {
    $used = '';
    $ok = qfa_auth_update(static function (array $data) use ($code, &$used) {
        if (!qfa_totp_enabled($data)) {
            return $data;
        }
        $totp = $data['totp'];
        $step = qfa_totp_match((string)$totp['secret'], $code, (int)($totp['last_step'] ?? 0));
        if ($step !== null) {
            $data['totp']['last_step'] = $step;
            $used = 'totp';
            return $data;
        }
        $recovery = is_array($totp['recovery'] ?? null) ? $totp['recovery'] : [];
        $index = qfa_recovery_match($recovery, $code);
        if ($index !== null) {
            unset($recovery[$index]);
            $data['totp']['recovery'] = array_values($recovery);
            $used = 'recovery';
        }
        return $data;
    });
    return $ok ? $used : '';
}
