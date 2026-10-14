<?php
/*
 * Checks for includes/config-writer.php and includes/pages.php.
 * Run: php tests/config_writer_test.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root.'/includes/config-writer.php';

$checks = 0;
$failures = 0;
function check($name, $ok){
    global $checks, $failures;
    $checks++;
    if( !$ok ) $failures++;
    echo ( $ok ? 'ok   - ' : 'FAIL - ' ).$name."\n";
}

$base = "<?php\ndefine('SITE_NAME', 'Old');\ndefine('MEMORIAL_ENABLED', false);\ndefine('HOME_SORT', ['language', 'tafseer', 'quran']);\n";

/* ---- values that used to break config.php ---- */

foreach( array('Name$0X', 'a\\1b', "it's", "x'); define('HACK', 1); //", 'نص عربي )؛ ', '\\') as $value ){
    $code = qfa_config_set($base, 'SITE_NAME', $value);
    check('stores '.json_encode($value, JSON_UNESCAPED_UNICODE).' as one literal', qfa_config_parses($code) && substr_count($code, "define('SITE_NAME'") === 1 && strpos($code, "define('SITE_NAME', ".var_export($value, true).");") !== false);
    // A second save must still find and replace the value written by the first.
    $again = qfa_config_set($code, 'SITE_NAME', 'Clean');
    check('a later save replaces '.json_encode($value, JSON_UNESCAPED_UNICODE), substr_count($again, "define('SITE_NAME'") === 1 && strpos($again, "define('SITE_NAME', 'Clean');") !== false);
}

$code = qfa_config_set($base, 'MEMORIAL_ENABLED', true);
check('booleans are written bare', strpos($code, "define('MEMORIAL_ENABLED', true);") !== false);

$code = qfa_config_set($base, 'DISABLED_PAGES', array('books', 'qibla'));
check('a missing define is appended', strpos($code, "define('DISABLED_PAGES', ['books', 'qibla']);") !== false && qfa_config_parses($code));
$code = qfa_config_set($code, 'DISABLED_PAGES', array());
check('a list can be emptied', substr_count($code, "define('DISABLED_PAGES'") === 1 && strpos($code, "define('DISABLED_PAGES', []);") !== false);

check('broken PHP is rejected before it is written', !qfa_config_parses("<?php\ndefine('X', 'a';\n"));

$odd = "<?php\ndefine(\"SITE_NAME\", \"Old\");\n";
check('a define in another form is left alone, not duplicated', qfa_config_set($odd, 'SITE_NAME', 'New') === $odd);

$file = tempnam(sys_get_temp_dir(), 'qfa');
file_put_contents($file, $base);
check('commit refuses a source that does not parse', !qfa_config_commit($file, "<?php define('X',", array()) && file_get_contents($file) === $base);
$next = qfa_config_set($base, 'SITE_NAME', 'New');
check('commit writes a verified source', qfa_config_commit($file, $next, array('SITE_NAME' => 'New')) && file_get_contents($file) === $next);
unlink($file);

/* ---- disabled pages ---- */

define('DISABLED_PAGES', array('qibla', 'books', 'not-a-page'));
require $root.'/includes/pages.php';

check('unknown page keys are ignored', qfa_disabled_pages() === array('books', 'qibla'));
check('surah views belong to the Quran page', qfa_page_for_action('surah') === 'quran');
check('the home page is never switchable', qfa_page_for_action('home') === '');

$html = '<ul><li class="nav-item"><a class="nav-link" href="https://x.test/qibla.php">القبلة</a></li>'
    .'<li class="nav-item"><a class="nav-link" href="https://x.test/quran.php">القرآن</a></li></ul>'
    .'<a href="https://x.test/?action=book&book_id=3">كتاب</a><a href="https://x.test/book-3.html">كتاب</a>'
    .'<a href="https://x.test/tafseer.php">التفاسير</a>';
$out = qfa_strip_disabled_links($html);
check('links to disabled pages are removed', strpos($out, 'qibla') === false && strpos($out, 'book') === false);
check('links to enabled pages are kept', strpos($out, 'quran.php') !== false && strpos($out, 'tafseer.php') !== false);

check('page content keeps the text of an unwrapped link', qfa_strip_disabled_links('<a href="https://x.test/book-3.html"><b>كتاب</b></a>', 'unwrap') === '<b>كتاب</b>');
$broken = '<td><a href="https://x.test/books-category-1.html">أ<a/></td><td><a href="https://x.test/quran.php">القرآن</a></td>';
check('broken markup does not swallow later links', strpos(qfa_strip_disabled_links($broken), 'quran.php') !== false);

echo "\n$checks checks, $failures failures\n";
exit($failures === 0 ? 0 : 1);
