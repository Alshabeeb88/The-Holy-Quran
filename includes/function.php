<?php
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff', true);
header('Referrer-Policy: strict-origin-when-cross-origin', true);
header('X-Frame-Options: SAMEORIGIN', true);
header('Strict-Transport-Security: max-age=15768000', true);

header(
    "Content-Security-Policy: "
    . "default-src 'self'; "
    . "script-src 'self'; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
    . "font-src 'self' https://fonts.gstatic.com data:; "
    . "img-src 'self' data: https:; "
    . "media-src 'self' https://www.everyayah.com https://server6.mp3quran.net https://server7.mp3quran.net https://server8.mp3quran.net https://server9.mp3quran.net https://server10.mp3quran.net https://server11.mp3quran.net https://server12.mp3quran.net https://server13.mp3quran.net https://download.quranicaudio.com https://download.tvquran.com https://www.qurantranslations.net; "
    . "connect-src 'self' https://api.aladhan.com; "
    . "frame-src https://www.youtube.com https://www.youtube-nocookie.com; "
    . "object-src 'none'; "
    . "base-uri 'self'; "
    . "form-action 'self';",
    true
);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (!is_file(dirname(__DIR__) . '/.qfa-installed') && PHP_SAPI !== 'cli') {
  header('Location: install.php', true, 302);
  exit;
}

define('PATH', getcwd());
define('INC_PATH', PATH.'/includes');

require_once(INC_PATH.'/config.php');
require_once(INC_PATH.'/admin-auth.php');

/*
 * Public pages are anonymous and stateless, so an ordinary visitor no longer
 * gets a PHP session. A session is opened only when this request genuinely
 * needs one:
 *   - the contact page, which embeds a CSRF token in its form;
 *   - any request that already carries our session cookie, i.e. a signed-in
 *     administrator browsing the public site, or a visitor who already holds a
 *     CSRF session.
 *
 * Admin entry points call qfa_auth_start() themselves and are unaffected. The
 * decision is taken here, before a single byte of output, so the session cookie
 * can always still be sent.
 */
$qfa_action = ( isset($_GET['action']) && is_scalar($_GET['action']) ? (string)$_GET['action'] : 'home' );
$qfa_session_needed = ( $qfa_action === 'contact' ) || isset($_COOKIE[QFA_SESSION_NAME]);
if( $qfa_session_needed ){
  qfa_auth_start();
}
unset($qfa_action, $qfa_session_needed);

require_once(INC_PATH.'/MobileDetect.php');
require_once(PATH.'/api/quran/quran-api.php');
require_once(PATH.'/api/books/book-api.php');
require_once(INC_PATH.'/class.php');

$detect = new \Detection\MobileDetect;

function isMobile()
{
	global $detect;
	return ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
}

$checkLang = false;
$get_lang = ( isset($_GET['l']) && !empty($_GET['l']) ? strip_tags($_GET['l']) : '' );

$quranforall = new QuranForAll();
$quranforall->setDefaultLanguage( LANGUAGE );

if( isset($_GET['book_id']) && intval($_GET['book_id']) != 0 ){
  $quranforall->book();
}
if( isset($_GET['category_id']) && intval($_GET['category_id']) != 0 ){
  $quranforall->books_category();
}
if( isset($_GET['name']) && $_GET['name'] != '' ){
  $quranforall->books_language();
}

if( !empty($get_lang) && in_array($get_lang, $quranforall->check_languages) ){
  $path = INC_PATH.'/language/'.$get_lang.'.php';
  if( file_exists($path) ){
    require_once( $path );
    $checkLang = true;
  }
}else{
  // Keep the site interface in the configured default language on every section,
  // including the books directory. Visitors can still explicitly choose another
  // interface language with ?l=xx.
  if( in_array($quranforall->default_language, $quranforall->check_languages) ){
    $path2 = INC_PATH.'/language/'.$quranforall->default_language.'.php';
    if( file_exists($path2) ){
      require_once( $path2 );
      $checkLang = true;
    }
  }
}

if( $checkLang == false ){
  if( $quranforall->is_rtl() == true || $quranforall->is_rtl == true ){
    require_once( INC_PATH.'/language/ar.php' );
  }else{
    require_once( INC_PATH.'/language/en.php' );
  }
}

function word($key=''){
	global $Q_W;
	return ( isset($Q_W[$key]) ? $Q_W[$key] : 'Key ['.htmlentities($key).'] not found' );
}

// Build the public site URL from the physical app folder, so the script works
// correctly both in the domain root and inside a test subfolder such as /new/.
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$appRoot = realpath(PATH);
$publicBasePath = '';
if( $docRoot && $appRoot && strpos($appRoot, $docRoot) === 0 ){
  $publicBasePath = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
}
$publicBasePath = '/' . trim($publicBasePath, '/');
if( $publicBasePath === '/' ){
  $publicBasePath = '';
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$detectedSiteUrl = $scheme . '://' . $host . $publicBasePath;
if (defined('SITE_URL') && trim((string)SITE_URL) !== '') {
  $detectedSiteUrl = rtrim((string)SITE_URL, '/');
}
$quranforall->setSiteUrl( rtrim($detectedSiteUrl, '/') );
$quranforall->path = PATH;
$quranforall->cache_folder = PATH.'/cache/';

$quranforall->cache_active = ( defined('CACHE') ? CACHE : false );
$quranforall->cache_time = ( defined('CACHE_TIME') ? CACHE_TIME : 864000 );
$quranforall->home_sort = ( defined('HOME_SORT') ? HOME_SORT : ['language', 'tafseer', 'quran'] );
$quranforall->home_allow_quran = ( defined('HOME_QURAN') ? HOME_QURAN : true );
$quranforall->home_allow_tafseer = ( defined('HOME_TAFSEER') ? HOME_TAFSEER : true );
$quranforall->home_allow_language = ( defined('HOME_LANGUAGE') ? HOME_LANGUAGE : true );
$quranforall->home_allow_book = ( defined('HOME_BOOK') ? HOME_BOOK : true );

$quranforall->setSiteName( ( defined('SITE_NAME') && !empty(SITE_NAME) ? SITE_NAME : word('site_name') ) );
$quranforall->setSiteDescription( ( defined('SITE_DESCRIPTION') && !empty(SITE_DESCRIPTION) ? SITE_DESCRIPTION : word('site_description') ) );
$quranforall->setTheme( THEME );
$quranforall->setRewriteRules( REWRITE_RULES );
$quranforall->setRandomBooks( RANDOM_BOOKS );
$quranforall->setBreadcrumb( BREADCRUMB );
$quranforall->setAllwReaderForm( READERS );
$quranforall->setAllwListenSurah( LISTEN_SURAH );
$quranforall->setallwFormChangeSurah( SURAH_FORM );
$quranforall->setDefaultReader( DEFAULT_READER );
$quranforall->setDefaultReaderAya( DEFAULT_AYAH_READER );
$quranforall->setTwitterUsername( TWITTER );
$quranforall->setHeaderCode( HEADER_CODE );
$quranforall->setFooterCode( FOOTER_CODE );
$quranforall->setHeaderText( HEADER_TEXT );
$quranforall->setBodyText( BODY_CODE );

$quranforall->setQuranColumn( ( defined('QURANCOLUMN') && !empty(QURANCOLUMN) ? QURANCOLUMN : word('quran_column') ) );
$quranforall->setTafseerColumn( ( defined('TAFSEERCOLUMN') && !empty(TAFSEERCOLUMN) ? TAFSEERCOLUMN : word('tafseer_column') ) );
$quranforall->setLanguageColumn( ( defined('LANGUAGECOLUMN') && !empty(LANGUAGECOLUMN) ? LANGUAGECOLUMN : word('language_column') ) );

if( isMobile() == 'computer' ){
	$quranforall->surah_one_line = false;
}

$quranforall->setBooksSource($quranforall->siteurl.'/api/books/'); //http://quran-for-all.com/api/books
$api_url = $quranforall->siteurl.'/api/quran/'; //http://quran-for-all.com/api/quran/

function get_json($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	//curl_setopt($ch, CURLOPT_SSLVERSION, 3);
	$content = curl_exec($ch);
	curl_close($ch);
	$json = json_decode($content, true);
	return $json;
}

function api_url( $url ){
	global $api_url;
	return trim($api_url, '/').'/'.$url;
}

function pagination_url( $page, $action_name = '' ){
	global $mod_rewrite_allow, $quranforall;

	$action = ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'home' );
	if( !empty($action_name) ){
		$action = $action_name;
	}
	$language_name = ( isset($_GET['name']) ? strip_tags($_GET['name']) : '' );
	$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : '' );

	if( $mod_rewrite_allow == 1 ){
		if( $action == 'books_language' && !empty($language_name) ){
			if( $page == 1 ){
				$create_url = $quranforall->siteurl.'/books-in-%s.html';
				$url = sprintf($create_url, $language_name);
			}else{
				$create_url = $quranforall->siteurl.'/%s-page-%d.html';
				$url = sprintf($create_url, $language_name, $page);
			}
		}elseif( $action == 'books_category' && !empty($category_id) ){
			if( $page == 1 ){
				$create_url = $quranforall->siteurl.'/books-category-%d.html';
				$url = sprintf($create_url, $category_id);
			}else{
				$create_url = $quranforall->siteurl.'/category-%d-page%d.html';
				$url = sprintf($create_url, $category_id, $page);
			}
		}else{
			$url = '#None';
		}
	}else{
		if( $action == 'books_language' && !empty($language_name) ){
			$create_url = $quranforall->siteurl.'/index.php?action=books_language&name='.$language_name.'&page=%d';
			$url = sprintf($create_url, $page);
		}elseif( $action == 'books_category' && !empty($category_id) ){
			$create_url = $quranforall->siteurl.'/index.php?action=books_category&category_id='.$category_id.'&page=%d';
			$url = sprintf($create_url, $page);
		}else{
			$url = $quranforall->siteurl.'/index.php';
		}
	}

	return $url;
}

function pagination_code($posts, $perpage, $page, $urlpage){

	$pagestr = 'page';
	$pagination = array();

	$lastpage = ceil($posts / $perpage);

	$adjacent = 5;
	$prevtext = "&raquo;";
	$nexttext = "&laquo;";
	$prev = $page - 1; //previous page is page - 1
	$next = $page + 1; //next page is page + 1
	$lpm1 = $lastpage - 1; //last page minus 1
	$adjacents = $adjacent; // How many adjacent pages should be shown on each side?

	if($lastpage > 1){

		if ($page > 1){
			$href_1 = pagination_url( $prev );
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="'.$href_1.'" aria-label="Previous"><span aria-hidden="true">'.$prevtext.'</span></a></li>';
		}else{
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">'.$prevtext.'</span></a></li>';
		}

		if($lastpage < 7 + ($adjacents * 2)){
			for ($counter = 1; $counter <= $lastpage; $counter++){
				if ($counter == $page){
					$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
				}else{
					$href_2 = pagination_url( $counter );
					$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_2.'">'.$counter.'</a></li>';
				}
			}
		}elseif($lastpage > 5 + ($adjacents * 2)){
			if($page < 1 + ($adjacents * 2)){
				for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_3 = pagination_url( $counter );
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_3.'">'.$counter.'</a></li>';
					}
				}
				$href_4 = pagination_url( $lpm1 );
				$href_5 = pagination_url( $lastpage );

				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_4.'">'.$lpm1.'</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_5.'">'.$lastpage.'</a></li>';
			}elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)){
				$href_6 = pagination_url( 1 );
				$href_7 = pagination_url( 2 );

				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_6.'">1</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_7.'">2</a></li>';
				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_8 = pagination_url( $counter );
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_8.'">'.$counter.'</a></li>';
					}
				}
				$href_9 = pagination_url( $lpm1 );
				$href_10 = pagination_url( $lastpage );
				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.	$href_9.'">'.$lpm1.'</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_10.'">'.$lastpage.'</a></li>';
			}else{
				$href_11 = pagination_url( 1 );
				$href_12 = pagination_url( 2 );

				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_11.'">1</a></li>';
				$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_12.'">2</a></li>';
				$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
				for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++){
					if ($counter == $page){
						$pagination[] = '<li class="page-item active"><a class="page-link" href="#">'.$counter.' <span class="sr-only">(current)</span></a></li>';
					}else{
						$href_13 = pagination_url( $counter );
						$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_13.'">'.$counter.'</a></li>';
					}
				}
			}
		}

		if ($page < $counter - 1){
			$href_14 = pagination_url( $next );
			$pagination[] = '<li class="page-item"><a class="page-link" href="'.$href_14.'" aria-label="Next"><span aria-hidden="true">'.$nexttext.'</span></a></li>';
		}else{
			$pagination[] = '<li class="page-item disabled"><a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">'.$nexttext.'</span></a></li>';
		}

	}

	return $pagination;
}

function pagination($posts, $perpage, $page, $urlpage){
	$pagination = pagination_code($posts, $perpage, $page, $urlpage);
	if( is_array($pagination) && count($pagination) > 0 ){
		$pagination_code = '<nav aria-label="Page navigation" class="mt-3">';
		$pagination_code .= '<ul class="pagination">';
		foreach ($pagination as $key => $value) {
			$pagination_code .= $value;
		}
		$pagination_code .= '</ul>';
		$pagination_code .= '</nav>';
	}else{
		$pagination_code = '';
	}
	return $pagination_code;
}

if( isset($_POST['change']) && intval($_POST['change']) == 1 && isset($_POST['surah']) && intval($_POST['surah']) < 115 ){
	$surah_id = ( intval($_POST['surah']) == 0 ? 1 : intval($_POST['surah']) );
	$from = ( isset($_POST['f']) && intval($_POST['f']) != 0 ? intval($_POST['f']) : 1 );
	$to = ( isset($_POST['t']) && intval($_POST['t']) != 0 ? intval($_POST['t']) : 0 );
        $reader_id = ( isset($_POST['reader_id']) ? intval($_POST['reader_id']) : 0 );
	$url = $quranforall->url( array( 'action' => 'home', 'surah' => $surah_id, 'f' => $from, 't' => $to, 'reader_id' => $reader_id ) );
	echo '<meta http-equiv="refresh" content="0; url='.$url.'">';
	exit;
}
?>
