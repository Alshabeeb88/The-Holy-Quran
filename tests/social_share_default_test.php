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

echo "\n$checks checks, $failures failures\n";
exit($failures === 0 ? 0 : 1);
