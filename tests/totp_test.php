<?php
/*
 * Checks for includes/totp.php against the RFC 6238 test vectors.
 * Run: php tests/totp_test.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/includes/totp.php';

$checks = 0;
$failures = 0;
function check($name, $ok){
    global $checks, $failures;
    $checks++;
    if( !$ok ) $failures++;
    echo ( $ok ? 'ok   - ' : 'FAIL - ' ).$name."\n";
}

$secret = qfa_base32_encode('12345678901234567890');
check('base32 matches RFC 4648', $secret === 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ');
check('base32 round trip', qfa_base32_decode($secret) === '12345678901234567890');
check('base32 ignores spaces and case', qfa_base32_decode('gezd gnbv gy3t qojq gezd gnbv gy3t qojq') === '12345678901234567890');

// RFC 6238 appendix B, SHA-1, last six digits.
foreach (array(59 => '287082', 1111111109 => '081804', 1111111111 => '050471', 1234567890 => '005924', 2000000000 => '279037', 20000000000 => '353130') as $time => $code) {
    check("code at $time", qfa_totp_code($secret, intdiv($time, 30)) === $code);
}

check('a code one step old is accepted', qfa_totp_match($secret, '287082', 0, 59 + 30) === 1);
check('a code two steps old is refused', qfa_totp_match($secret, '287082', 0, 59 + 60) === null);
check('a used code is refused', qfa_totp_match($secret, '287082', 1, 59) === null);
check('spaces in a typed code are ignored', qfa_totp_match($secret, '287 082', 0, 59) === 1);
check('a wrong code is refused', qfa_totp_match($secret, '123456', 0, 59) === null);
check('a new secret is 32 base32 characters', (bool)preg_match('/^[A-Z2-7]{32}$/', qfa_totp_new_secret()));

list($plain, $hashes) = qfa_recovery_codes_new(3);
check('three recovery codes', count($plain) === 3 && count($hashes) === 3);
check('recovery code format', (bool)preg_match('/^[A-Z2-9]{4}-[A-Z2-9]{4}$/', $plain[0]));
check('recovery code matches typed without dash or case', qfa_recovery_match($hashes, strtolower(str_replace('-', '', $plain[1]))) === 1);
check('unknown recovery code is refused', qfa_recovery_match($hashes, 'AAAA-AAAA') === null);
check('otpauth link', strpos(qfa_totp_uri($secret, 'a@b.co', 'القرآن'), 'otpauth://totp/') === 0);

echo "\n$checks checks, $failures failures\n";
exit($failures === 0 ? 0 : 1);
