<?php
/*
 * Covers the neutral default share image: the installer records it when no
 * image is uploaded, and resolve_og_logo() falls back to it -- never to a
 * site-specific image -- when the configured one is missing.
 *
 * Run: php -n tests/social_share_default_test.php
 */

$root = dirname(__DIR__);
$failures = 0;
$checks = 0;

function check($label, $condition, $detail = ''){
    global $failures, $checks;
    $checks++;
    if( $condition ){
        echo "ok   - $label\n";
        return;
    }
    $failures++;
    echo "FAIL - $label" . ( $detail !== '' ? " ($detail)" : '' ) . "\n";
}

/* ---- the installer's default when nothing is uploaded ---- */

$install = file_get_contents($root.'/install.php');
check(
    'install.php saves images/social-share-default.jpg when no image is uploaded',
    preg_match(
        "~\\\$socialImageCheck\['uploaded'\]\s*\r?\n\s*\?[^\n]*\r?\n\s*:\s*'images/social-share-default\.jpg';~",
        $install
    ) === 1
);

/* ---- no stale default left in the edited PHP files ---- */

foreach( array('install.php', 'includes/class.php') as $file ){
    $source = file_get_contents($root.'/'.$file);
    check(
        "$file holds no default pointing at social-share.jpg or og.png",
        strpos($source, "'images/social-share.jpg'") === false
            && strpos($source, "'images/og.png'") === false
    );
}

/* ---- the shipped asset ---- */

$asset = $root.'/style/default/images/social-share-default.jpg';
check('the neutral default asset exists', is_file($asset));

$size = ( is_file($asset) ? @getimagesize($asset) : false );
check('the asset is a JPEG', is_array($size) && $size[2] === IMAGETYPE_JPEG,
    is_array($size) ? 'type '.$size[2] : 'unreadable');
check('the asset is 1200x630', is_array($size) && $size[0] === 1200 && $size[1] === 630,
    is_array($size) ? $size[0].'x'.$size[1] : 'unreadable');

/* ---- resolve_og_logo() ordering ---- */

if( !class_exists('QuranForAll_API') ){
    class QuranForAll_API {}
}
require_once $root.'/includes/class.php';

function resolve_with($og_logo){
    $page = ( new ReflectionClass('QuranForAll') )->newInstanceWithoutConstructor();
    $page->og_logo = $og_logo;
    $page->theme_folder = 'style/default';

    $method = new ReflectionMethod('QuranForAll', 'resolve_og_logo');
    if( PHP_VERSION_ID < 80100 ){
        // Required on PHP 7.4, and deprecated from 8.5 onwards.
        $method->setAccessible(true);
    }
    return $method->invoke($page);
}

/*
 * social-share.jpg and og.png both still ship with the theme, so if either were
 * still in the chain these two cases would land on them instead.
 */
check(
    'a configured image that exists stays the first candidate',
    resolve_with('images/social-share.jpg') === 'images/social-share.jpg',
    resolve_with('images/social-share.jpg')
);
check(
    'a missing configured image falls back to social-share-default.jpg',
    resolve_with('images/no-such-share-image.jpg') === 'images/social-share-default.jpg',
    resolve_with('images/no-such-share-image.jpg')
);
check(
    'with nothing configured the fallback is social-share-default.jpg, not social-share.jpg or og.png',
    resolve_with('') === 'images/social-share-default.jpg',
    resolve_with('')
);

/* ---- licensing and rights notices ---- */

$license = ( is_file($root.'/LICENSE') ? file_get_contents($root.'/LICENSE') : '' );
check('LICENSE exists', $license !== '');
check(
    'LICENSE is PolyForm Noncommercial 1.0.0',
    strpos($license, 'PolyForm Noncommercial License 1.0.0') !== false
        && strpos($license, 'polyformproject.org/licenses/noncommercial/1.0.0') !== false
);
check(
    'LICENSE carries the required attribution notices',
    strpos($license, 'Required Notice: Original Developer: Ahmed Al-Anazi') !== false
        && strpos($license, 'Required Notice: المطور الأصلي: أحمد العنزي') !== false
);

foreach( array('README.md', 'ATTRIBUTION.md', 'NOTICE-AR.md') as $doc ){
    $text = file_get_contents($root.'/'.$doc);
    check(
        "$doc points at the PolyForm Noncommercial license",
        strpos($text, 'PolyForm Noncommercial License 1.0.0') !== false
    );
}

check(
    'ATTRIBUTION.md records the original developer\'s written permission',
    strpos(file_get_contents($root.'/ATTRIBUTION.md'), 'موافقة كتابية صريحة') !== false
);

/*
 * The permission is private evidence: the documents may say that it exists,
 * but must never carry the original developer's address or the correspondence.
 */
foreach( array('README.md', 'ATTRIBUTION.md', 'NOTICE-AR.md', 'LICENSE') as $doc ){
    check(
        "$doc publishes no email address",
        preg_match('~[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}~', file_get_contents($root.'/'.$doc)) !== 1
    );
}

/* ---- the rights line shown in the footer of every public page ---- */

$class = file_get_contents($root.'/includes/class.php');
check(
    'the Arabic footer carries the full rights line',
    strpos($class, 'المطور الأصلي: <a target="_blank" rel="noopener noreferrer" href="https://quran-for-all.com/">أحمد العنزي</a> | تطوير وتحسين النسخة: تركي بن عبيد | \'.$toa_credit.\' | استخدام مجاني غير تجاري | V') !== false
        && strpos($class, '© TOA Digital</span>') !== false
);
check(
    'the English footer carries the noncommercial notice',
    strpos($class, 'Enhanced and maintained by: Turki Bin Obaid | \'.$toa_credit.\' | Free for noncommercial use | V') !== false
);

echo "\n$checks checks, $failures failures\n";
exit($failures === 0 ? 0 : 1);
