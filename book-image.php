<?php
$src = isset($_GET['src']) ? trim($_GET['src']) : '';
$fallback = __DIR__ . '/images/book-open-navy-gold.png';

function qfa_book_fallback($path){
    if (is_file($path)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
    } else {
        http_response_code(404);
    }
    exit;
}

if ($src === '' || !filter_var($src, FILTER_VALIDATE_URL)) {
    qfa_book_fallback($fallback);
}

$parts = parse_url($src);
$host = isset($parts['host']) ? strtolower($parts['host']) : '';
$scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
$allowedHosts = array('muslim-library.com', 'www.muslim-library.com');
if (!in_array($host, $allowedHosts, true) || !in_array($scheme, array('http','https'), true)) {
    qfa_book_fallback($fallback);
}

$ch = curl_init($src);
curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_MAXFILESIZE => 8 * 1024 * 1024,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AlshabeebQuran/1.0)',
    CURLOPT_REFERER => 'https://www.muslim-library.com/',
));
$data = curl_exec($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$type = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err = curl_errno($ch);
curl_close($ch);

if ($err || $code < 200 || $code >= 300 || !$data || stripos($type, 'image/') !== 0) {
    qfa_book_fallback($fallback);
}

header('Content-Type: '.$type);
header('Cache-Control: public, max-age=604800');
header('X-Content-Type-Options: nosniff');
echo $data;
