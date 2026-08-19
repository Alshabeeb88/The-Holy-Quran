<?php
class QuranForAll extends QuranForAll_API {
	public $siteName;
	public $siteDescription;
	public $siteurl;
	public $logo;
	public $og_logo;
	public $twitter_username;
	public $theme_folder;
	public $default_language;
	public $mod_rewrite_allow;
	public $random_books;
	public $allw_readerform;
	public $default_reader;
	public $default_reader_aya;
	public $allw_listen_surah;
	public $allw_formchange_surah;
	public $is_rtl;
	public $language;
	public $author;
	public $publisher;
	public $language_sound = false;

	public $title;
	public $body_title;
	public $description;
	public $url;
	public $image;
	public $footercode;
	public $headercode;
	public $headertext;
	public $bodycode;
	public $copyright;
	public $breadcrumb_parent;
	public $breadcrumb_title;
	public $hide_breadcrumb;
	public $reader_name;
	public $container_class;
	public $books_source;
	public $books_action_array;
	public $books_rtl;

	public $current_page_protocol;
	public $current_page_site;
	public $current_page_thisfile;
	public $current_page_real_directories;
	public $current_page_num_of_real_directories;
	public $current_page_virtual_directories = array();
	public $current_page_num_of_virtual_directories = array();
	public $current_page_baseurl;
	public $current_page_thisurl;

	public $path;
	/*
	 * Cache entries are stored as "<logical name>.php" beginning with this guard,
	 * so a direct HTTP request executes it and returns nothing. See
	 * cache_file_path() for the full reasoning.
	 */
	const CACHE_SUFFIX = '.php';
	const CACHE_GUARD  = "<?php exit; ?>\n";

	public $cache_active;
	public $cache_folder;
	public $sitemap_folder;
	public $cache_time;
	public $cache_allow_create = true;

	public $home_allow_quran = true;
	public $home_allow_tafseer = true;
	public $home_allow_language = true;
	public $home_allow_book = true;
	public $home_sort = array();
	public $quran_col = 3;
	public $tafseer_col = 3;
	public $language_col = 3;
	public $book_parent;
	public $surah_one_line = false;

	public function __construct(){
		$this->logo = 'css/images/og-logo.png';
		$this->og_logo = defined('SOCIAL_SHARE_IMAGE') && SOCIAL_SHARE_IMAGE !== ''
			? SOCIAL_SHARE_IMAGE
			: 'images/og.png';
		$this->container_class = 'container well';
		$this->books_action_array = array('books', 'books_language', 'books_category');
		$this->books_rtl = array('Arabic', 2, 554, 8953, 10698, 10699, 9792, 12779, 12183, 7734, 127, 128, 3536, 11648, 124, 171, 148, 158, 152, 142, 146, 271, 172, 169, 154, 151, 147, 166, 165, 143, 137, 164, 140, 149, 139, 168, 138, 145, 144, 167, 156, 141, 136, 170, 150, 159, 163, 162, 153, 160, 157, 161, 132, 134, 11652, 10225, 125, 179, 176, 178, 175, 173, 177, 180, 174, 126, 181, 182, 185, 184, 183, 130, 129, 473, 133, 131, 10026, 1403, 575, 190, 11691, 195, 187, 428, 193, 9395, 191, 196, 189, 11657, 13345, 192, 188, 14114, 1536, 135, 8988, 13096);
		$this->is_rtl = false;
		/*
		 * Page-level absolute URLs are anchored to the configured SITE_URL, so a
		 * forged Host header cannot re-point them at an attacker domain. The
		 * request-derived values stay only as a pre-install fallback.
		 */
		$configured_site = defined('SITE_URL') ? trim((string)SITE_URL) : '';
		$configured_parts = ( $configured_site !== '' ? parse_url($configured_site) : false );
		if( is_array($configured_parts) && !empty($configured_parts['host']) ){
			$this->current_page_protocol = ( !empty($configured_parts['scheme']) ? strtolower((string)$configured_parts['scheme']) : 'https' );
			$this->current_page_site = $this->current_page_protocol . '://' . $configured_parts['host']
				. ( !empty($configured_parts['port']) ? ':' . (int)$configured_parts['port'] : '' );
		}else{
			$this->current_page_protocol = ( !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off' ? 'https' : 'http' );
			$this->current_page_site = $this->current_page_protocol . '://' . (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
		}
		$this->current_page_thisfile = basename($_SERVER['SCRIPT_FILENAME']);
		$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
		$script_dir = trim(str_replace('\\', '/', dirname($script_name)), '/.');
		$this->current_page_real_directories = ($script_dir === '' ? array() : $this->cleanUp(explode('/', $script_dir)));
		$this->current_page_num_of_real_directories = count($this->current_page_real_directories);
		$this->current_page_virtual_directories = array();
		$this->current_page_num_of_virtual_directories = 0;
		$this->current_page_baseurl = $this->current_page_site . ($script_dir === '' ? '/' : '/' . $script_dir . '/');
		$this->current_page_thisurl = $this->current_page_site . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $script_name);
		parent::__construct();
	}

	public function baseurl(){
		return rtrim($this->current_page_baseurl, "/");
	}

	public function setSiteName( $str ){
		$this->siteName = $str;
	}

	public function setSiteDescription( $str ){
		$this->siteDescription = $str;
	}

	public function setRewriteRules( $allow ){
		$this->mod_rewrite_allow = $allow;
	}

	public function setSiteUrl( $str ){
		$this->siteurl = $str;
	}

	public function setTheme( $str ){
		$this->theme_folder = $str;
	}

	public function setDefaultLanguage( $str ){
		$this->default_language = $str;
	}

	public function setDirection( $direction ){
		$this->is_rtl = ( $direction == 'rtl' ? true : false );
	}

	public function setRandomBooks( $number ){
		$this->random_books = intval($number);
	}

	public function setTwitterUsername( $username ){
		$this->twitter_username = $username;
	}

	public function setFooterCode( $code ){
		$this->footercode = $code;
	}

	public function setHeaderCode( $code ){
		$this->headercode = $code;
	}

	public function setHeaderText( $text ){
		$this->headertext = $text;
	}

	public function setBodyText( $text ){
		$this->bodycode = $text;
	}

	public function setBooksSource( $url ){
		$this->books_source = $url;
	}

	public function bookCoverUrl( $url ){
		return $this->siteurl.'/images/book-open-navy-gold.png';
	}

	public function setBreadcrumb( $allow ){
		$this->hide_breadcrumb = $allow;
	}

	public function setAllwReaderForm( $allow ){
		$this->allw_readerform = $allow;
	}

	public function setAllwListenSurah( $allow ){
		$this->allw_listen_surah = $allow;
	}

	public function setallwFormChangeSurah( $allow ){
		$this->allw_formchange_surah = $allow;
	}

	public function setDefaultReader( $number ){
		$this->default_reader = intval($number);
	}

	public function setDefaultReaderAya( $number ){
		$this->default_reader_aya = intval($number);
	}

	public function setQuranColumn( $number ){
		$this->quran_col = intval($number);
	}

	public function setTafseerColumn( $number ){
		$this->tafseer_col = intval($number);
	}

	public function setLanguageColumn( $number ){
		$this->language_col = intval($number);
	}

	public function get_logo(){
		$logo = $this->siteurl.'/'.$this->theme_folder.'/'.$this->logo;
		return $logo;
	}

	public function get_social_share_version(){
		$relative = $this->theme_folder.'/'.$this->og_logo;
		$local = dirname(__DIR__).'/'.$relative;

		if( is_file($local) ){
			$mtime = @filemtime($local);
			if( $mtime !== false ){
				return (string)$mtime;
			}
		}

		return '1';
	}

	public function get_og_logo(){
		$relative = $this->theme_folder.'/'.$this->og_logo;
		$version = $this->get_social_share_version();
		$logo = $this->siteurl.'/'.$relative.'?v='.$version;
		return $logo;
	}

	public function seo(){
		$action = ( isset($_GET['action']) ? $_GET['action'] : '' );
		$title = $this->title;
		$body_title = $this->body_title;
		$description = ( empty($this->description) ? $title : $this->description );
		$url = ( empty($this->url) ? $this->siteurl : $this->url );
		$image = ( empty($this->image) ? $this->get_logo() : $this->image );
		$og_image = ( empty($this->image) ? $this->get_og_logo() : $this->image );

		$language = $this->language;
		$author = $this->author;
		$publisher = $this->publisher;

		$local = ( $this->is_rtl() ? 'ar' : 'en_US' );

		// SEO: homepage is a website; content pages such as Surahs are articles.
		$is_home_og = ( ($action === '' || $action === 'home') && empty($_GET['surah']) );
		$og_type = ( $is_home_og ? 'website' : 'article' );

		$og = '<meta property="og:locale" content="'.$local.'">'."\n";
		$og .= '<meta property="og:type" content="'.$og_type.'">'."\n";
		$og .= '<meta property="og:title" content="'.$title.'">'."\n";
		$og .= '<meta property="og:description" content="'.$description.'">'."\n";
		$og .= '<meta property="og:url" content="'.$url.'">'."\n";
		$og .= '<meta property="og:site_name" content="'.$this->siteName.'">'."\n";
		if( $og_type === 'article' ){
			$og .= '<meta property="article:publisher" content="'.$this->siteurl.'">'."\n";
		}
		$og_width = 1728;
		$og_height = 910;

		$og_path = parse_url($og_image, PHP_URL_PATH);
		if (is_string($og_path) && $og_path !== '') {
			$local_path = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . $og_path;
			if (is_file($local_path)) {
				$og_size = @getimagesize($local_path);
				if (is_array($og_size) && !empty($og_size[0]) && !empty($og_size[1])) {
					$og_width = (int)$og_size[0];
					$og_height = (int)$og_size[1];
				}
			}
		}

		$og .= '<meta property="og:image" content="'.$og_image.'">'."\n";
		$og .= '<meta property="og:image:width" content="'.$og_width.'">'."\n";
		$og .= '<meta property="og:image:height" content="'.$og_height.'">'."\n";

		$twitter = '<meta name="twitter:card" content="summary_large_image">'."\n";
		$twitter .= '<meta name="twitter:description" content="'.$description.'">'."\n";
		$twitter .= '<meta name="twitter:title" content="'.$title.'">'."\n";
		$twitter .= '<meta name="twitter:image" content="'.$og_image.'">'."\n";

		$output = '';
		if( !empty($description) ){
			$output .= '<meta name="description" content="'.$description.'">'."\n";
		}
		if( !empty($url) ){
			$output .= '<link rel="canonical" href="'.$url.'">'."\n";
		}
		$output .= '<meta name="author" content="Nwahy.com">'."\n";
		$output .= '<meta name="generator" content="Quran For All V'.$this->version.'">'."\n";

		$output .= $og;
		$output .= $twitter;

		// V32 SEO Schema: clean JSON-LD based only on real page data.
		// Removes legacy fixed 2019 dates and the old google.com mainEntityOfPage.
		$schema_graph = array();

		$schema_site_url = rtrim($this->siteurl, '/').'/';
		$schema_page_url = $url;
		$schema_language = $this->get_language();
		$schema_title = html_entity_decode(strip_tags((string)$title), ENT_QUOTES, 'UTF-8');
		$schema_description = html_entity_decode(strip_tags((string)$description), ENT_QUOTES, 'UTF-8');

		if( $is_home_og ){
			$schema_graph[] = array(
				'@type' => 'WebSite',
				'@id' => $schema_site_url.'#website',
				'url' => $schema_site_url,
				'name' => html_entity_decode(strip_tags((string)$this->siteName), ENT_QUOTES, 'UTF-8'),
				'description' => html_entity_decode(strip_tags((string)$this->siteDescription), ENT_QUOTES, 'UTF-8'),
				'inLanguage' => $schema_language
			);
		}

		$webpage_schema = array(
			'@type' => 'WebPage',
			'@id' => $schema_page_url.'#webpage',
			'url' => $schema_page_url,
			'name' => $schema_title,
			'description' => $schema_description,
			'inLanguage' => $schema_language,
			'isPartOf' => array(
				'@id' => $schema_site_url.'#website'
			)
		);

		if( !empty($og_image) ){
			$webpage_schema['primaryImageOfPage'] = array(
				'@type' => 'ImageObject',
				'url' => $og_image
			);
		}
		$schema_graph[] = $webpage_schema;

		// Book pages: only use fields that actually exist on the page.
		if( $action == 'book' ){
			$book_schema = array(
				'@type' => 'Book',
				'@id' => $schema_page_url.'#book',
				'url' => $schema_page_url,
				'name' => $schema_title,
				'description' => $schema_description,
				'bookFormat' => 'https://schema.org/EBook',
				'inLanguage' => $language
			);

			if( !empty($image) ){
				$book_schema['image'] = $image;
			}
			if( !empty($author) ){
				$book_schema['author'] = array(
					'@type' => 'Person',
					'name' => html_entity_decode(strip_tags((string)$author), ENT_QUOTES, 'UTF-8')
				);
			}
			if( !empty($publisher) ){
				$book_schema['publisher'] = array(
					'@type' => 'Organization',
					'name' => html_entity_decode(strip_tags((string)$publisher), ENT_QUOTES, 'UTF-8')
				);
			}
			$schema_graph[] = $book_schema;
		}

		// JSON-LD breadcrumb generated only when a real breadcrumb hierarchy exists.
		if( !$is_home_og && !empty($this->breadcrumb_parent) ){
			$breadcrumb_items = array();
			$position = 1;

			$breadcrumb_items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => 'الرئيسية',
				'item' => $schema_site_url
			);

			$parents = $this->breadcrumb_parent;
			if( isset($parents['title']) && isset($parents['url']) ){
				$parents = array($parents);
			}

			if( is_array($parents) ){
				foreach( $parents as $parent ){
					if( !is_array($parent) ){
						continue;
					}
					$parent_title = isset($parent['title']) ? trim(strip_tags((string)$parent['title'])) : '';
					$parent_url = isset($parent['url']) ? trim((string)$parent['url']) : '';
					if( $parent_title === '' || $parent_url === '' ){
						continue;
					}
					++$position;
					$breadcrumb_items[] = array(
						'@type' => 'ListItem',
						'position' => $position,
						'name' => html_entity_decode($parent_title, ENT_QUOTES, 'UTF-8'),
						'item' => $parent_url
					);
				}
			}

			++$position;
			$breadcrumb_items[] = array(
				'@type' => 'ListItem',
				'position' => $position,
				'name' => $schema_title,
				'item' => $schema_page_url
			);

			if( count($breadcrumb_items) >= 2 ){
				$schema_graph[] = array(
					'@type' => 'BreadcrumbList',
					'@id' => $schema_page_url.'#breadcrumb',
					'itemListElement' => $breadcrumb_items
				);
			}
		}

		$schema_data = array(
			'@context' => 'https://schema.org',
			'@graph' => $schema_graph
		);

		$output .= '<script type="application/ld+json">'.
			json_encode($schema_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).
			'</script>'."\n";

		return $output;
	}

	public function get_version(){
		return $this->version;
	}

	public function get_rtl_languages(){
		return $this->rtl_languages;
	}

	public function get_default_language(){
		return $this->default_language;
	}

	public function get_theme_folder(){
		return $this->theme_folder;
	}

	public function get_theme_folder_url(){
		$site_url = $this->siteurl.'/';
		return $site_url.$this->theme_folder;
	}

	public function get_array_language(){
		return $this->check_languages;
	}

	public function get_language(){
		// The interface language is independent from the language of the book
		// collection being browsed. This keeps the site chrome/header Arabic
		// unless the visitor explicitly selects another UI language with ?l=.
		if( isset($_GET['l']) && !empty($_GET['l']) ){
			if( in_array($_GET['l'], $this->check_languages) ){
				$l = strip_tags($_GET['l']);
			}else{
				$l = 'ar';
			}
		}else{
			if( in_array($this->default_language, $this->check_languages) ){
				$l = $this->default_language;
			}else{
				$l = 'ar';
			}
		}

		return $l;
	}

	public function is_rtl( $check = '' ){
		$lang = $this->get_language();
		// The site chrome follows the UI language first. Arabic must stay RTL
		// even while browsing book collections in other languages.
		if( in_array($lang, $this->get_rtl_languages()) || $lang === 'ar' ){
			$this->is_rtl = true;
			return true;
		}
		$action = ( isset($_GET['action']) ? $_GET['action'] : '' );
		$language_name = ( isset($_GET['name']) ? $_GET['name'] : '' );
		$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : '' );

		if( !empty($check) && in_array($check, $this->books_rtl) ){
			$this->is_rtl = true;
			return true;
		}

		if( in_array($action, $this->books_action_array) ){
			if( in_array($language_name, $this->books_rtl) || in_array($category_id, $this->books_rtl) ){
				$this->is_rtl = true;
				return true;
			}
		}else{
			if( in_array($lang, $this->get_rtl_languages()) || $lang == 'ar' ){
				$this->is_rtl = true;
				return true;
			}else{
				if( !isset($_GET['l']) ){
					if( in_array($this->get_default_language(), $this->get_rtl_languages()) ){
						$this->is_rtl = true;
						return true;
					}
				}
			}
		}

		return false;
	}

	public function tpl_replace( $text = '' ){
		$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		$extra_title = '';
		if( $page > 1 ){
			$extra_title .= ' | '.word('page').' '.$page;
		}

		$title = ( empty($this->title) ? $this->siteName : $this->title ); //htmlentities()
		$description = ( empty($this->description) ? $this->siteDescription : $this->description );
		if( empty($this->reader_name) ){
			$reader_name = '';
		}else{
			if( isset($_GET['reader_id']) ){
				$reader_name = ' | '.$this->reader_name;
			}else{
				$reader_name = '';
			}
		}
		$site_url = $this->siteurl.'/';
		$action = ( isset($_GET['action']) ? $_GET['action'] : 'home' );
		$is_home = ($action === 'home' && !isset($_GET['surah']) && !isset($_GET['reader_id']) && !isset($_GET['f']) && !isset($_GET['t']));
		$page_class = $is_home ? 'home-page' : 'internal-page';
		$allowed_appearance_styles = array('modern');
		$allowed_color_schemes = array('emerald', 'navy', 'burgundy');
		$appearance_style = defined('APPEARANCE_STYLE') ? (string) APPEARANCE_STYLE : 'modern';
		$color_scheme = defined('COLOR_SCHEME') ? (string) COLOR_SCHEME : 'emerald';
		if( !in_array($appearance_style, $allowed_appearance_styles, true) ) $appearance_style = 'modern';
		if( !in_array($color_scheme, $allowed_color_schemes, true) ) $color_scheme = 'emerald';
		$page_class .= ' appearance-'.$appearance_style.' color-'.$color_scheme;
		if( $action === 'morning_adhkar' ){
			$page_class .= ' morning-adhkar-page-body';
		}
		if( $action === 'evening_adhkar' ){
			$page_class .= ' evening-adhkar-page-body';
		}
		if( $action === 'qibla' ){
			$page_class .= ' qibla-page-body';
		}
		if( $action === 'contact' ){
			$page_class .= ' contact-page-body';
		}
		if( $action === 'sadaqah_agent' ){
			$page_class .= ' sadaqah-agent-page-body';
		}

		$text = $this->shortcode($text);

		$headercode = '';
		if( $this->is_rtl() == true || $this->is_rtl == true ){
			$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/bootstrap.rtl.min.css?v=5.1">'."\n";
			$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/style.css?v=58.6">'."\n";
			$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/rtl.css?v='.$this->version.'">'."\n";
		}else{
			$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/bootstrap.min.css?v=5.1">'."\n";
			$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/style.css?v=58.6">'."\n";
		}
		$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/all.min.css?v=5.15.4">'."\n";
		$headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/hover-min.css?v=2.3.2">'."\n";

		$headercode .= $this->get_hreflang( $this->get_language() );

                $this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/qfa-navigation.js?v=1.0" defer></script>';

		$copyright = 'Powered by <a target="_balnk" href="https://nwahy.com">Quran For All</a> V'.$this->get_version().'';

		//$clean = str_replace(array('"', "'", "-", "_", ".", "]", "[", "(", ")", "{", "}", "`", "!", ",", "|", "  ", ";"), array('', "", "", "", "", "", "", "", "", "", "", "", "", "", "", " ", ""), $this->title);
		$admin_nav = '';
		if( function_exists('qfa_auth_logged_in') && qfa_auth_logged_in() ){
			$admin_nav = '<div class="private-admin-shortcut"><a href="'.$site_url.'admin.php"><i class="fas fa-th-large"></i><span>لوحة الإدارة</span></a><a href="'.$site_url.'admin-logout.php" title="تسجيل الخروج" aria-label="تسجيل الخروج"><i class="fas fa-sign-out-alt"></i></a></div>';
		}
		$memorial_enabled = defined('MEMORIAL_ENABLED') && MEMORIAL_ENABLED;
		$memorial_title = defined('MEMORIAL_TITLE') ? (string)MEMORIAL_TITLE : '';
		$memorial_dua = defined('MEMORIAL_DUA') ? (string)MEMORIAL_DUA : '';
		$browser_title = defined('BROWSER_TITLE') ? trim((string)BROWSER_TITLE) : '';
		if( $is_home && $browser_title !== '' ){
			$title = $browser_title;
		}
		$search  = array( '{title}', '{sitename}', '{description}','{style}', '{js}', '{bodycode}', '{footercode}', '{code}', '{nav}', '{copyright}', '{lang}', '{container-class}', '{site_url}', '{seo}', '{page-class}', '{admin-nav}', '{memorial-class}', '{memorial-title}', '{memorial-dua}' );
		$replace = array( $title.$reader_name.$extra_title, $this->siteName, $description.$reader_name.$extra_title, $this->get_theme_folder_url(), $headercode.$this->headercode, $this->bodycode, $this->footercode, $this->headertext, $this->get_navbar(), $copyright.$this->copyright, $this->get_language(), $this->container_class, $site_url, $this->seo(), $page_class, $admin_nav, $memorial_enabled ? '' : ' memorial-bar-disabled', htmlspecialchars($memorial_title, ENT_QUOTES, 'UTF-8'), htmlspecialchars($memorial_dua, ENT_QUOTES, 'UTF-8') );
		return str_replace($search, $replace, $text);
	}

	public function languages(){
		return $this->api_get_languages();
	}

	public function surah_name( $lang = '' ){
		if( empty($lang) ){
			return $this->api_surah( $this->get_language(), true );
		}else{
			return $this->api_surah( $lang, true );
		}
	}

	public function readers( $reader_id = '' ){
		if( empty($reader_id) ){
			return $this->api_get_readers();
		}else{
			return $this->api_get_reader( $reader_id );
		}
	}

	public function ayah_readers( $reader_id = '' ){
		if( empty($reader_id) ){
			return $this->api_get_ayah_readers();
		}else{
			return $this->api_get_ayah_reader( $reader_id );
		}
	}

	public function tafseer_surah_loop() {
		$l = 'ar';
		$type = ( isset($_REQUEST['type']) && intval($_REQUEST['type']) != 0 ? intval($_REQUEST['type']) : 1 );
		$json_tafseer = $this->api_tafseer();

		if( is_array($json_tafseer['data']) && array_key_exists($type, $json_tafseer['data']) ){
			$tafseer_name = ( isset($json_tafseer['data'][$type]['name']) ? $json_tafseer['data'][$type]['name'] : '' );
			$this->title = $tafseer_name;
			$this->body_title = $tafseer_name;
			$this->description = $tafseer_name;
			$this->url = $this->url( array( 'action' => 'tafseer', 'type' => $type ) );
		}else{
			$this->cache_allow_create = false;
		}

		$tafseer_list = '';
		if( !isset($json_tafseer['error']) && isset($json_tafseer['data']) && count($json_tafseer['data']) > 0 ){
			$tafseer_list .= '<div class="tafseer-list">';
			$tafseer_list .= '<label for="tafseer_list"><strong>'.word('select_tafseer').'</strong></label>';
			$tafseer_list .= '<select class="form-control" name="formt" data-qfa-navigate id="tafseer_list">';
			$tafseer_list .= '<option value="#">'.word('select_tafseer').'</option>';
			foreach( $json_tafseer['data'] as $keys => $values ){
				$name = ( isset($values['name']) ? $values['name'] : '' );
				$name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );

				$selected = ( $type == $keys ? ' selected' : '' );

				$tafseer_list .= '<option value="'.$this->url( array( 'action' => 'tafseer', 'type' => $keys ) ).'"'.$selected.'>'.$name.'</option>';
			}
			$tafseer_list .= '</select>';
			$tafseer_list .= '</div>';
		}

		$json = $this->surah_name( $l );
		if( !isset($json['error']) && isset($json['data']) && count($json['data']) > 0 ){
			$language_id = ( isset($json['language_id']) ? $json['language_id'] : '' );
			$language_name = ( isset($json['language_name']) ? $json['language_name'] : '' );
			$language_name_ar = ( isset($json['language_name_ar']) ? $json['language_name_ar'] : '' );
			$language_name_en = ( isset($json['language_name_en']) ? $json['language_name_en'] : '' );
			$language_book = ( isset($json['language_book']) ? $json['language_book'] : '' );
			$language_sound = ( isset($json['language_sound']) ? $json['language_sound'] : '' );

			$code = '';
			$code .= $tafseer_list;
			$code .= $this->post_share($language_name.' | '.$this->siteName, $this->url( array( 'action' => 'translate', 'l' => $l ) ));
			$code .= '<div class="row">';
			foreach( $json['data'] as $key => $value ){
				$surah_number = ( isset($value['n']) ? $value['n'] : 0 );
				$surah_name = ( isset($value['name']) ? $value['name'] : '' );
				$surah_count = ( isset($value['ayat']) ? $value['ayat'] : 0 );
				$surah_image = ( isset($value['image']) ? $value['image'] : '' );
				$surah_url = $this->url( array( 'action' => 'tafseer', 'type' => $type, 'surah' => $surah_number ) );

				$code .= '<div class="col-12 col-sm-6 col-md-4">';
				$code .= '<div class="spacer">';
				$code .= '<h5><a title="'.$surah_name.' - '.word('aya_count').' '.$surah_count.'" href="'.$surah_url.'">'.$surah_number.'- '.word('surah_in_title').' '.$surah_name.'</a></h5>';
				$code .= '</div>';
				$code .= '</div>';
			}
			$code .= '</div>';
		}else{
			$this->cache_allow_create = false;
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}

		return $code;
	}

	public function tafseer_view($surah_id, $ayah_id=1, $tafseer_id=0){
		$json = $this->api_tafseer_view($tafseer_id, $surah_id, $ayah_id, true);
		if( !isset($json['error']) && isset($json['surah_name']) && !empty($json['surah_name']) ){
			$data_tafseer_id = ( isset($json['tafseer_id']) ? $json['tafseer_id'] : 0 );
			$data_tafseer_name = ( isset($json['tafseer_name']) ? $json['tafseer_name'] : '' );
			$data_surah_id = ( isset($json['surah_id']) ? $json['surah_id'] : '' );
			$data_surah_ayat = ( isset($json['surah_ayat']) ? $json['surah_ayat'] : 0 );
			$data_surah_name = ( isset($json['surah_name']) ? $json['surah_name'] : '' );
			$data_ayah_id = ( isset($json['ayah_id']) ? $json['ayah_id'] : 0 );
			$data_aya_text = ( isset($json['aya_text']) ? $json['aya_text'] : '' );
			$data_text = ( isset($json['text']) ? $json['text'] : '' );
			// Preserve the official quotation text but remove the source's private-use
			// ornamental glyphs, which overlap when rendered with regular UI fonts.
			if( intval($data_tafseer_id) === 6 ){
				$data_text = str_replace(array('ﵡ', 'ﵠ'), '', $data_text);
			}

			if( empty($data_text) || empty($data_tafseer_name) || $ayah_id > $data_surah_ayat ){
				$this->cache_allow_create = false;
				return '<div class="alert alert-danger mt-3 mb-3" role="alert">'.word('no_data').'</div>';
			}

			$json_tafseer = $this->api_tafseer();
			$tafseer_list = '';
			if( !isset($json_tafseer['error']) && isset($json_tafseer['data']) && count($json_tafseer['data']) > 0 ){
				$tafseer_list .= '<div class="tafseer-list">';
				$tafseer_list .= '<label for="tafseer_list"><strong>'.word('select_tafseer').'</strong></label>';
				$tafseer_list .= '<select class="form-control" name="formt" data-qfa-navigate id="tafseer_list">';
				$tafseer_list .= '<option value="#">'.word('select_tafseer').'</option>';
				foreach( $json_tafseer['data'] as $keys => $values ){
					$name = ( isset($values['name']) ? $values['name'] : '' );
					$name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );
					$selected = ( $tafseer_id == $keys ? ' selected' : '' );
					if( isset($_GET['ayah']) ){
						$tafseer_url = $this->url( array( 'action' => 'tafseer', 'type' => $keys, 'surah' => $surah_id, 'ayah' => $ayah_id ) );
					}else{
						$tafseer_url = $this->url( array( 'action' => 'tafseer', 'type' => $keys, 'surah' => $surah_id ) );
					}
					$tafseer_list .= '<option value="'.$tafseer_url.'"'.$selected.'>'.$name.'</option>';
				}
				$tafseer_list .= '</select>';
				$tafseer_list .= '</div>';
			}else{
				$tafseer_list = ( isset($json_tafseer['error']) && !empty($json_tafseer['error']) ? $json_tafseer['error'] : 'Unknown error' );
			}

			$ayah_list = '';
			if( $data_surah_ayat > 0 ){
				$ayah_list .= '<div class="ayah-list">';
				$ayah_list .= '<label for="ayah_list"><strong>'.word('select_aya_number').'</strong></label>';
                            $ayah_list .= '<select class="form-control" name="formt" data-qfa-navigate id="ayah_list">';
				$ayah_list .= '<option value="#">'.word('select_aya_number').'</option>';
				for( $a=1; $a <= $data_surah_ayat; ++$a ){
					$ayah_selected = ( $a == $ayah_id ? ' selected' : '' );
					$ayah_list .= '<option value="'.$this->url( array( 'action' => 'tafseer', 'type' => $tafseer_id, 'surah' => $surah_id, 'ayah' => $a ) ).'"'.$ayah_selected.'>'.$a.'</option>';
				}
				$ayah_list .= '</select>';
				$ayah_list .= '</div>';
			}else{
				$ayah_list = '';
			}

			$surah_list = '';
			$json_surah = $this->surah_name( 'ar' );
			if( !isset($json_surah['error']) && isset($json_surah['data']) && count($json_surah['data']) > 0 ){
				$language_id = ( isset($json['language_id']) ? $json['language_id'] : '' );
				$language_name = ( isset($json['language_name']) ? $json['language_name'] : '' );
				$language_name_ar = ( isset($json['language_name_ar']) ? $json['language_name_ar'] : '' );
				$language_name_en = ( isset($json['language_name_en']) ? $json['language_name_en'] : '' );
				$language_book = ( isset($json['language_book']) ? $json['language_book'] : '' );
				$language_sound = ( isset($json['language_sound']) ? $json['language_sound'] : '' );

				$surah_list .= '<div class="surah-list">';
				$surah_list .= '<label for="surah_list"><strong>'.word('select_surah').'</strong></label>';
				$surah_list .= '<select class="form-control" name="formt" data-qfa-navigate id="surah_list">';
				$surah_list .= '<option value="#">'.word('select_surah').'</option>';
				foreach( $json_surah['data'] as $keys => $values ){
					$surah_number = ( isset($values['n']) ? $values['n'] : 0 );
					$surah_name = ( isset($values['name']) ? $values['name'] : '' );
					$surah_count = ( isset($values['ayat']) ? $values['ayat'] : 0 );
					$surah_name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );
					$surah_url = $this->url( array( 'action' => 'tafseer', 'type' => $tafseer_id, 'surah' => $surah_number, 'ayah' => 1 ) );

					$surah_selected = ( $surah_number == $surah_id ? ' selected' : '' );
					$surah_list .= '<option value="'.$surah_url.'" title="'.$surah_name_en.'"'.$surah_selected.'>'.$surah_name.'</option>';

				}
				$surah_list .= '</select>';
				$surah_list .= '</div>';
			}else{
				$surah_list = ( isset($json_surah['error']) && !empty($json_surah['error']) ? $json_surah['error'] : 'Unknown error' );
			}

			$get_list = '<div class="row">';
			$get_list .= '<div class="col-12 col-md-4">';
			$get_list .= $surah_list;
			$get_list .= '</div>';
			$get_list .= '<div class="col-12 col-md-4">';
			$get_list .= $ayah_list;
			$get_list .= '</div>';
			$get_list .= '<div class="col-12 col-md-4">';
			$get_list .= $tafseer_list;
			$get_list .= '</div>';
			$get_list .= '</div>';

			// V33 SEO: distinguish full-surah tafseer pages from single-ayah tafseer pages.
			$is_tafseer_ayah_page = ( isset($_GET['ayah']) && intval($_GET['ayah']) > 0 );

			if( $is_tafseer_ayah_page ){
				$title = word('surah').' '.$data_surah_name.' - '.$data_tafseer_name.' - '.word('aya').' '.$data_ayah_id;
			}else{
				$title = word('surah').' '.$data_surah_name.' - '.$data_tafseer_name;
			}

			$this->title = $title;
			$this->body_title = $title;
			$this->breadcrumb_title = word('aya').' '.$data_ayah_id;

			if( isset($_REQUEST['ayah']) && intval($_REQUEST['ayah']) > 0 ){
				$this->description = $data_tafseer_name.' - '.word('surah').' '.$data_surah_name.' - '.word('aya').' '.$data_ayah_id;
				$this->url = $this->url( array(
					'action' => 'tafseer',
					'type' => $data_tafseer_id,
					'surah' => $data_surah_id,
					'ayah' => $data_ayah_id
				) );
			}else{
				$this->description = $data_tafseer_name.' - '.word('surah').' '.$data_surah_name;
				$this->url = $this->url( array(
					'action' => 'tafseer',
					'type' => $data_tafseer_id,
					'surah' => $data_surah_id
				) );
			}
			$this->breadcrumb_parent = array(
				array('title' => $data_tafseer_name, 'url' => $this->url( array( 'action' => 'tafseer', 'type' => $tafseer_id ) ) ),
				array('title' => $data_surah_name, 'url' => $this->url( array( 'surah' => $surah_id ) ) ) //'action' => 'tafseer', 'type' => $tafseer_id,
			);
			$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/mp3-player-button.css?v=58.3">';
			$this->headercode .= '<script type="text/javascript" src="'.$this->get_theme_folder_url().'/js/soundmanager2-nodebug-jsmin.js"></script>';
			$this->headercode .= '<script type="text/javascript" src="'.$this->get_theme_folder_url().'/js/mp3-player-button.js"></script>';
			$this->headercode .= '<style>.tafseer-ayah-navigation{display:flex;align-items:stretch;justify-content:space-between;gap:12px;margin-top:18px}.tafseer-ayah-navigation>a,.tafseer-ayah-navigation>.is-disabled{display:flex;align-items:center;justify-content:center;gap:9px;min-height:50px;flex:1;padding:10px 14px;border-radius:12px;font-weight:800}.tafseer-ayah-navigation>a{background:var(--q-primary);color:#fff!important;text-decoration:none}.tafseer-ayah-navigation>a:hover{background:var(--q-primary-2)}.tafseer-ayah-navigation>.is-disabled{background:var(--q-soft);color:var(--q-muted)}.tafseer-ayah-navigation small{display:block;font-weight:500;opacity:.85}@media(max-width:575.98px){.tafseer-ayah-navigation{gap:8px}.tafseer-ayah-navigation>a,.tafseer-ayah-navigation>.is-disabled{padding:9px 7px;font-size:.9rem}.tafseer-ayah-navigation small{font-size:.75rem}}html[data-theme="dark"] .tafseer-ayah-navigation>.is-disabled{background:var(--q-dark-card);color:var(--q-dark-muted);border:1px solid var(--q-dark-border)}</style>';
			if( intval($data_tafseer_id) === 6 ){
				$this->headercode .= '<style>.official-tafseer-text{font-family:"Cairo",Tahoma,Arial,sans-serif!important;line-height:2.15;font-size:1.08rem;font-weight:500;letter-spacing:0}.official-tafseer-text .aya{display:inline;color:#0c6a57;font-family:"Cairo",Tahoma,Arial,sans-serif!important;font-weight:700}.official-tafseer-text .aya:before{content:"«"}.official-tafseer-text .aya:after{content:"»"}.official-tafseer-source{margin-top:18px;padding:13px 16px;border:1px solid #d8e7e1;border-radius:12px;background:#f5faf8;color:#42635a;font-family:"Cairo",Tahoma,Arial,sans-serif;font-size:.95rem}.official-tafseer-source a{color:#0c6a57;font-weight:700}@media(max-width:575.98px){.official-tafseer-text{font-size:1rem;line-height:2.05}}html[data-theme="dark"] .official-tafseer-source{background:#152923;border-color:#315047;color:#dceae5}</style>';
			}

			$sound_folder = $this->sound_folder_aya( $this->default_reader_aya );
			$audio_file = $this->sound_check_aya($surah_id, $ayah_id, $sound_folder);

			$button = '<div class="listen_aya hvr-bounce-in"><a href="'.$audio_file.'" class="sm2_button" title="Play &quot;coins&quot;"><i class="fa fa-play"></i></a></div>';

			$code = '<div class="tafseer" itemscope itemtype="http://schema.org/Article">';
			$code .= '<div class="changesoraform">';
			$code .= $get_list;
			$code .= $this->post_share($title, $this->url( array( 'action' => 'tafseer', 'type' => $tafseer_id, 'surah' => $surah_id, 'ayah' => $ayah_id ) ));
			$code .= '</div>';
			$code .= '<div class="ayat4tafseer">';
			$code .= $this->fix_char($data_aya_text).' '.$this->ayah_number( $ayah_id );
			$code .= '<p class="ayat4tafseer_info">';
			$code .= word('surah').' <a href="'.$this->url( array( 'surah' => $surah_id ) ).'"><span class="number">'.$data_surah_name.'</span></a> ';
			$code .= $data_tafseer_name;
			$code .= '</p>';
			$code .= $button;
			$code .= '</div>';
			$official_class = ( intval($data_tafseer_id) === 6 ? ' official-tafseer-text' : '' );
			$code .= '<div class="ayat4tafseer_text'.$official_class.'" id="TAFSEER">'.( empty($data_text) ? word('not_found_tafseer') : nl2br($data_text)).'</div>';
			if( intval($data_tafseer_id) === 6 ){
				$code .= '<div class="official-tafseer-source"><i class="fa fa-check-circle" aria-hidden="true"></i> المصدر الرسمي: <a href="https://qurancomplex.gov.sa/quran-dev/" target="_blank" rel="noopener noreferrer">مجمع الملك فهد لطباعة المصحف الشريف</a> — التفسير الميسّر، الإصدار 3.0.</div>';
			}

			$previous_surah = $data_surah_id;
				$previous_ayah = $data_ayah_id - 1;
				if( $previous_ayah < 1 && $data_surah_id > 1 ){
					$previous_surah = $data_surah_id - 1;
					$previous_ayah = ( isset($this->aya_count[$previous_surah]) ? intval($this->aya_count[$previous_surah]) : 1 );
				}
				$next_surah = $data_surah_id;
				$next_ayah = $data_ayah_id + 1;
				if( $next_ayah > $data_surah_ayat && $data_surah_id < 114 ){
					$next_surah = $data_surah_id + 1;
					$next_ayah = 1;
				}

				$code .= '<nav class="tafseer-ayah-navigation" aria-label="التنقل بين الآيات">';
				if( intval($data_surah_id) === 1 && intval($data_ayah_id) === 1 ){
					$code .= '<span class="is-disabled"><i class="fa fa-arrow-right" aria-hidden="true"></i> الآية السابقة</span>';
				}else{
					$code .= '<a href="'.$this->url(array('action' => 'tafseer', 'type' => $data_tafseer_id, 'surah' => $previous_surah, 'ayah' => $previous_ayah)).'"><i class="fa fa-arrow-right" aria-hidden="true"></i><span>الآية السابقة<small>رقم '.$previous_ayah.'</small></span></a>';
				}
				if( intval($data_surah_id) === 114 && intval($data_ayah_id) === intval($data_surah_ayat) ){
					$code .= '<span class="is-disabled">الآية التالية <i class="fa fa-arrow-left" aria-hidden="true"></i></span>';
				}else{
					$code .= '<a href="'.$this->url(array('action' => 'tafseer', 'type' => $data_tafseer_id, 'surah' => $next_surah, 'ayah' => $next_ayah)).'"><span>الآية التالية<small>رقم '.$next_ayah.'</small></span><i class="fa fa-arrow-left" aria-hidden="true"></i></a>';
				}
			$code .= '</nav>';
			$code .= '</div>';
		}else{
			$this->cache_allow_create = false;
			$code = ( isset($json['error']) && !empty($json['error']) ? ( $json['error'] == 'empty' ? word('not_found_tafseer') : $json['error'] ) : 'Unknown error' );
		}
		return $code;
	}

	public function tafseer(){
		$surah_id = ( isset($_REQUEST['surah']) && intval($_REQUEST['surah']) < 115 ? intval($_REQUEST['surah']) : 1 );
		$x = ( isset($_REQUEST['reader_id']) ? intval($_REQUEST['reader_id']) : $this->default_reader );
		$aya = ( isset($_REQUEST['ayah']) && intval($_REQUEST['ayah']) != 0 ? intval($_REQUEST['ayah']) : 1 );
		$type = ( isset($_REQUEST['type']) && intval($_REQUEST['type']) != 0 ? intval($_REQUEST['type']) : 1 );

		if( isset($_GET['surah']) && $_GET['surah'] != 0 && $_GET['surah'] < 115 ){
			$code = $this->tafseer_view($surah_id, $aya, $type);
		}else{
			$code = $this->tafseer_surah_loop();
		}
		return $code;
	}

	public function translate(){
		if( isset($_GET['surah']) && $_GET['surah'] != 0 && $_GET['surah'] < 115 ){
			$code = $this->translate_view();
		}else{
			$code = $this->surah_loop();
		}
		return $code;
	}

	public function home_readers_form($surah, $l, $reader_id, $place=0){
		$readers = $this->readers();

		$mb = '';
		if( !isset($readers['error']) && isset($readers['data']) && count($readers['data']) > 0 ){
			if( $place == 1 ){
				$mb = ' mt-3';
			}
			$code = '<div class="reader-list'.$mb.'">';
			$code .= '<label for="reader_list"><strong>'.word('select_qaria').'</strong></label>';
			$code .= '<select class="form-control" name="reader_list" data-qfa-navigate id="reader_list">';
			$code .= '<option value="#">'.word('select_qaria').'</option>';
			foreach ($readers['data'] as $key => $value) {
				$name = ( isset($value['name']) ? $value['name'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$description = ( isset($value['description']) ? $value['description'] : '' );
				$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
				$sound_folder = ( isset($value['sound_folder']) ? $value['sound_folder'] : '' );

				if( $place == 1 ){
					$get_url = $this->url( array( 'action' => 'home', 'surah' => $surah, 'reader_id' => $key, 'f' => ( isset($_GET['f']) ? intval($_GET['f']) : 0 ), 't' => ( isset($_GET['t']) ? intval($_GET['t']) : 0 ) ) );
				}else{
					$get_url = $this->url( array( 'action' => 'translate', 'l' => $l, 'surah' => $surah, 'reader_id' => $key ) );
				}

				$get_name = ( in_array($this->get_language(), $this->get_rtl_languages()) ? $name : $name_en );

				$selected = ( $reader_id == $key ? ' selected' : '' );

				if( $reader_id == $key ){
					$this->reader_name = $get_name;
				}
				$code .= '<option value="'.$get_url.'"'.$selected.'>'.$get_name.'</option>';
			}
			$code .= '</select>';
			$code .= '</div>';
		}else{
			$code = ( isset($readers['error']) && !empty($readers['error']) ? $readers['error'] : 'Unknown error' );
		}
		return $code;
	}

	public function home_check_surah($surah, $n, $translatesound=""){
		$readers = $this->readers($n);

		if( !isset($readers['error']) && isset($readers['data']) && count($readers['data']) > 0 ){
			$value = $readers['data'];
			$name = ( isset($value['name']) ? $value['name'] : '' );
			$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
			$description = ( isset($value['description']) ? $value['description'] : '' );
			$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
			$sound_folder = ( isset($value['sound_folder']) ? $value['sound_folder'] : '' );
		}else{
			$sound_folder = '';
		}

		$surah_number = strlen($surah);

		if($surah_number==1){
			$s1 = '00'.$surah;
		}elseif($surah_number==2){
			$s1 = '0'.$surah;
		}elseif($surah_number==3){
			$s1 = $surah;
		}

		if( !empty($sound_folder) ){
			if($translatesound == ""){
				if(preg_match('/:N:/i', $sound_folder)) {
					$s = str_replace(":N:", $s1, $sound_folder).".mp3";
				}else{
					$s = $sound_folder.$s1.'.mp3';
				}
			}else{
				if(preg_match('/:N:/i', $translatesound)) {
					$s = str_replace(":N:",$s1, $translatesound).".mp3";
				}else{
					$s = ''.$translatesound.$s1.'.mp3';
				}
			}
		}else{
			$s = 'None';
		}

		return $s;
	}

	public function sound_folder_aya($n=16){
		$readers = $this->ayah_readers($n);
		if( !isset($readers['error']) && isset($readers['data']) && count($readers['data']) > 0 ){
			$value = $readers['data'];
			$name = ( isset($value['name']) ? $value['name'] : '' );
			$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
			$description = ( isset($value['description']) ? $value['description'] : '' );
			$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
			$sound_folder = ( isset($value['sound_folder']) ? $value['sound_folder'] : '' );
			return $sound_folder;
		}else{
			return '';
		}
	}

	public function sound_check_aya($surah, $aya, $folder){
		$surah_number = strlen($surah);
		$aya_number = strlen($aya);

		if($surah_number==1){
			$s1 = '00'.$surah;
		}elseif($surah_number==2){
			$s1 = '0'.$surah;
		}elseif($surah_number==3){
			$s1 = $surah;
		}

		if($aya_number==1){
			$s2 = '00'.$aya;
		}elseif($aya_number==2){
			$s2 = '0'.$aya;
		}elseif($aya_number==3){
			$s2 = $aya;
		}

		if( !empty($folder) ){
			$s = $folder.$s1.$s2.'.mp3';
		}else{
			$s = 'None';
		}

		return $s;
	}

	public function home_form_change_ayah($surah, $from, $to){
		$json = $this->surah_name();
		$surah_count = 0;

		if(isset($_GET['reader_id']) && intval($_GET['reader_id']) != 0){
			$reader = '<input type="hidden" name="reader_id" value="'.intval($_GET['reader_id']).'">';
		}else{
			$reader = '';
		}

		if( isset($json['data']) && is_array($json['data']) ){
			$s = '<div class="changesoraform mt-3 mb-3">';
			$s .= '<form name="form-change" action="" method="post">';
			$s .= '<input type="hidden" name="change" value="1">';
			$s .= '<input type="hidden" name="surah" value="'.$surah.'">';
			$s .= $reader;
			$i = 0;
			$s .= '<div class="row">';

			$s .= '<div class="col">';
			$s .= '<div class="form-group">';
			$s .= '<label for="surah_names">'.word('the_surah').'</label>';
			$s .= '<select id="surah_names" class="form-control" name="sora_link" data-qfa-navigate>';
			foreach( $json['data'] as $key => $value ){
				++$i;
				$get_surah_name = ( isset($value['name']) ? $value['name'] : '' );
				$get_surah_count = ( isset($value['ayat']) ? $value['ayat'] : 0 );
				$selected = ( $surah == $i ? ' selected' : '' );
				if( $surah == $i ){
					$surah_count = $get_surah_count;
				}
				$s .= '<option value="'.$this->url( array( 'surah' => $i ) ).'"'.$selected.'>'.$i.'- '.$get_surah_name.'</option>';
			}
			$s .= '</select>';
			$s .= '</div>';
			$s .= '</div>';

			$s .= '<div class="col">';
			$s .= '<div class="form-group">';
			$s .= '<label for="surah_from">'.word('from').'</label>';
			$s .= '<select id="surah_from" class="form-control" name="f">';
			for($i2=1; $i2<$surah_count+1; $i2++){
				$selected2 = ( $from == $i2 ? ' selected' : '' );
				$s .= '<option value="'.$i2.'"'.$selected2.'>'.$i2.'</option>';
			}
			$s .= '</select>';
			$s .= '</div>';
			$s .= '</div>';

			$s .= '<div class="col">';
			$s .= '<div class="form-group">';
			$s .= '<label for="surah_to">'.word('to').'</label>';
			$s .= '<select id="surah_to" class="form-control" name="t">';
			for($i3=1; $i3<$surah_count+1; $i3++){
				if($to == 0){
					$to = $surah_count;
				}else{
					$to = $to;
				}
				$selected3 = ( $to == $i3 ? ' selected' : '' );
				$s .= '<option value="'.$i3.'"'.$selected3.'>'.$i3.'</option>';
			}
			$s .= '</select>';
			$s .= '</div>';
			$s .= '</div>';

			$s .= '<div class="col">';
			$s .= '<label for="submit">&nbsp</label>';
			$s .= '<button type="submit" class="btn btn-primary form-control" id="submit">'.word('change').'</button>';
			$s .= '</div>';
			$s .= '</div>';

			$s .= '</form>';
			$s .= '</div>';
		}else{
			$s = '';
		}

		return $s;
	}

	public function get_hreflang($language_code='', $notin=''){
		$json = $this->languages();
		if( !isset($json['error']) && isset($json['data']) && count($json['data']) > 0 ){
			$code = '';

			if( empty($language_code) ){
				foreach( $json['data'] as $key => $value ){
					$id = ( isset($value['id']) ? $value['id'] : 0 );
					$name = ( isset($value['name']) ? $value['name'] : '' );
					$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
					$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
					$file = ( isset($value['file']) ? $value['file'] : '' );
					$book = ( isset($value['book']) ? $value['book'] : '' );
					$more = ( isset($value['more']) ? $value['more'] : '' );
					$source = ( isset($value['source']) ? $value['source'] : '' );
					$lang = ( isset($value['lang']) ? $value['lang'] : '' );
					$getkey = ( isset($value['key']) ? $value['key'] : '' );
					$flag = ( isset($value['flag']) ? $value['flag'] : '' );

					$get_flags = '<img class="flags" src="'.$flag.'" alt="'.$name.'">';

					if($notin != $getkey){
						$code .= '<link rel="alternate" hreflang="'.$getkey.'" title="'.$name.'" href="'.$this->url( array( 'action' => 'translate', 'l' => $getkey ) ).'">';
					}
				}
			}else{
				$code = '<link rel="alternate" hreflang="'.$json['data'][$language_code]['key'].'" title="'.$json['data'][$language_code]['name'].'" href="'.$this->url( array( 'action' => 'translate', 'l' => $json['data'][$language_code]['key'] ) ).'">';
			}
		}else{
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}

		return '';
	}

	public function get_languages(){
		$json = $this->languages();
		if( !isset($json['error']) && isset($json['data']) && count($json['data']) > 0 ){
			$this->title = word('languages');
			$code = '<div class="row">';
			foreach( $json['data'] as $key => $value ){
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$name = ( isset($value['name']) ? $value['name'] : '' );
				$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$file = ( isset($value['file']) ? $value['file'] : '' );
				$book = ( isset($value['book']) ? $value['book'] : '' );
				$more = ( isset($value['more']) ? $value['more'] : '' );
				$source = ( isset($value['source']) ? $value['source'] : '' );
				$lang = ( isset($value['lang']) ? $value['lang'] : '' );
				$getkey = ( isset($value['key']) ? $value['key'] : '' );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );

				$get_flags = '<img class="flags" src="'.$flag.'" alt="'.$name.'">';

				if( $key != 'ar' ){
					$code .= '<div class="col-12 col-sm-4 col-md-3"><div class="spacer"><h5><a title="'.$name_en.' - '.$name_ar.'" href="'.$this->url( array( 'action' => 'translate', 'l' => $getkey ) ).'">'.$get_flags.' '.$name.'</a></h5></div></div>';
				}

			}
			$code .= '</div>';
		}else{
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}

		return $code;
	}

	public function get_navbar(){
		$code = '';
		$code .= '<li class="nav-item"><a class="nav-link" href="'.$this->siteurl.'/#quran">'.word('quran').'</a></li>';
		$code .= '<li class="nav-item"><a class="nav-link" href="'.$this->siteurl.'/#tafseer">'.word('tfaseer').'</a></li>';
		$code .= '<li class="nav-item"><a class="nav-link" href="'.$this->siteurl.'/#languages">'.word('languages').'</a></li>';
		$code .= '<li class="nav-item"><a class="nav-link" href="'.$this->url( array( 'action' => 'books') ).'">'.word('books').'</a></li>';
		$code .= '';

		/*
		$navbar_links = array('en', 'fr', 'de', 'es', 'pt', 'ru', 'zh', 'ko', 'id');
		$json = $this->languages();
		if( !isset($json['error']) && isset($json['data']) && count($json['data']) > 0 ){
			$code = '<ul class="navbar-nav">';
			foreach( $json['data'] as $key => $value ){
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$name = ( isset($value['name']) ? $value['name'] : '' );
				$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$file = ( isset($value['file']) ? $value['file'] : '' );
				$book = ( isset($value['book']) ? $value['book'] : '' );
				$more = ( isset($value['more']) ? $value['more'] : '' );
				$source = ( isset($value['source']) ? $value['source'] : '' );
				$lang = ( isset($value['lang']) ? $value['lang'] : '' );
				$getkey = ( isset($value['key']) ? $value['key'] : '' );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );

				$get_flags = '<img src="'.$flag.'" alt="'.$name.'">';

				if( in_array($getkey, $navbar_links) ){
					$code .= '<li class="nav-item"><a class="nav-link" href="'.$this->url( array( 'action' => 'translate', 'l' => $getkey ) ).'">'.$get_flags.' '.$name.' <span class="sr-only">(current)</span></a></li>';
				}

			}
			$code .= '<li class="nav-item"><a class="nav-link" href="language.html"><img src="'.$this->get_theme_folder_url().'/flags/other.png" alt="'.word('more').'"> '.word('more').'</a></li>';
			$code .= '</ul>';
		}else{
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}
		*/

		return $code;
	}

	public function url($data = ''){
		$mod_rewrite = $this->mod_rewrite_allow;
		$sitr_url = $this->siteurl.'/';
		$fileName = '?'; //index.php
		$allow_action = array( 'home', 'quran', 'tafseer', 'translate', 'language', 'languages', 'books', 'books_language', 'books_category', 'book', 'date_converter', 'contact', 'morning_adhkar', 'evening_adhkar', 'qibla', 'tasbeeh', 'sadaqah_agent' );

		if( is_array($data) ){
			$action = ( isset($data['action']) ? strip_tags($data['action']) : 'home' );
			$type = ( isset($data['type']) ? intval($data['type']) : '' );
			$surah = ( isset($data['surah']) ? intval($data['surah']) : '' );
			$ayah = ( isset($data['ayah']) ? intval($data['ayah']) : '' );
			$language = ( isset($data['l']) ? strip_tags($data['l']) : '' );
			$reader_id = ( isset($data['reader_id']) ? intval($data['reader_id']) : '' );
			$from = ( isset($data['f']) ? intval($data['f']) : '' );
			$to = ( isset($data['t']) ? intval($data['t']) : '' );
			$book_id = ( isset($data['book_id']) ? intval($data['book_id']) : '' );
			$name = ( isset($data['name']) ? strip_tags(strtolower($data['name'])) : '' );
			$category_id = ( isset($data['category_id']) ? intval($data['category_id']) : '' );
			$page = ( isset($data['page']) ? intval($data['page']) : '' );

			$get_action = ( in_array($action, $allow_action) ? 'action='.$action : '' );
			$get_language = ( empty($language) ? '' : '&l='.$language );
			$get_type = ( empty($type) ? '' : '&type='.$type );
			$get_surah = ( empty($surah) ? '' : '&surah='.$surah );
			$get_ayah = ( empty($ayah) ? '' : '&ayah='.$ayah );
			$get_reader_id = ( empty($reader_id) ? '' : '&reader_id='.$reader_id );
			$get_from = ( empty($from) ? '' : '&f='.$from );
			$get_to = ( empty($to) ? '' : '&t='.$to );
			$get_book_id = ( empty($book_id) ? '' : '&book_id='.$book_id );
			$get_name = ( empty($name) ? '' : '&name='.$name );
			$get_category_id = ( empty($category_id) ? '' : '&category_id='.$category_id );
			$get_page = ( empty($page) ? '' : '&page='.$page );

			if( $mod_rewrite ){
				if( $action == 'tafseer' && !empty($surah) && !empty($ayah) && !empty($type) ){
					$url = $sitr_url.sprintf('t-%d-%d-%d.html', $surah, $type, $ayah);
				}elseif( $action == 'tafseer' && !empty($surah) && !empty($type) && empty($ayah) ){
					$url = $sitr_url.sprintf('tafseer-%d-%d.html', $type, $surah);
				}elseif( $action == 'tafseer' && !empty($type) && empty($surah) && empty($ayah) ){
					$url = $sitr_url.sprintf('tafseer-%d.html', $type);
				}elseif( $action == 'tafseer' && empty($type) ){
					$url = $sitr_url.'tafseer.php';
				}elseif( $action == 'translate' && !empty($language) && !empty($surah) && !empty($reader_id) ){
					$url = $sitr_url.sprintf('s-%s-%d-%d.html', $language, $surah, $reader_id);
				}elseif( $action == 'translate' && !empty($language) && !empty($surah) && empty($reader_id) ){
					$url = $sitr_url.sprintf('translate-%s-%d.html', $language, $surah);
				}elseif( $action == 'translate' && !empty($language) && empty($surah) && empty($reader_id) ){
					$url = $sitr_url.sprintf('language-%s.html', $language);
				}elseif( $action == 'languages' ){
					$url = $sitr_url.'languages.html';
				}elseif( $action == 'quran' ){
					$url = $sitr_url.'quran.php';
                           }elseif( $action == 'home' && !empty($from) && !empty($to) && !empty($surah) && !empty($reader_id) ){
                                   $url = $sitr_url.sprintf('view-%d,from-%d,to-%d.html?reader_id=%d', $surah, $from, $to, $reader_id);
				}elseif( $action == 'home' && !empty($from) && !empty($to) && !empty($surah) ){
					$url = $sitr_url.sprintf('view-%d,from-%d,to-%d.html', $surah, $from, $to);
				}elseif( $action == 'home' && !empty($surah) && !empty($reader_id) ){
					$url = $sitr_url.sprintf('reader-%d-%d.html', $surah, $reader_id);
				}elseif( $action == 'home' && !empty($surah) ){
					$url = $sitr_url.sprintf('surah-%d.html', $surah);
				}elseif( $action == 'books_language' && !empty($name) && !empty($page) ){
					$url = $sitr_url.sprintf('%s-page-%d.html', $name, $page);
				}elseif( $action == 'books_language' && !empty($name) ){
					$url = $sitr_url.sprintf('books-in-%s.html', $name);
				}elseif( $action == 'books_category' && !empty($category_id) ){
					$url = $sitr_url.sprintf('books-category-%s.html', $category_id);
				}elseif( $action == 'book' && !empty($book_id) ){
					$url = $sitr_url.sprintf('book-%d.html', $book_id);
				}elseif( $action == 'books' ){
					$url = $sitr_url.'books.php';
				}elseif( $action == 'date_converter' ){
					$url = $sitr_url.'date-converter.php';
				}elseif( $action == 'contact' ){
					$url = $sitr_url.'contact.php';
				}elseif( $action == 'morning_adhkar' ){
					$url = $sitr_url.'morning-adhkar.php';
				}elseif( $action == 'evening_adhkar' ){
					$url = $sitr_url.'evening-adhkar.php';
				}elseif( $action == 'qibla' ){
					$url = $sitr_url.'qibla.php';
				}elseif( $action == 'tasbeeh' ){
					$url = $sitr_url.'tasbeeh.php';
				}elseif( $action == 'sadaqah_agent' ){
					$url = $sitr_url.'sadaqah-agent.php';
				}else{
					$url = 'index.html';
				}
			}else{
				if( empty($get_action) ){
					$url = $sitr_url;
				}else{
					$url = $sitr_url.$fileName.$get_action.$get_language.$get_type.$get_surah.$get_ayah.$get_from.$get_to.$get_book_id.$get_name.$get_category_id.$get_page;
				}
			}
			$output = $url;
		}else{
			$output = $sitr_url;
		}

	return $output;
	}

	public function get_breadcrumb( $arr='' ){
		if( !$this->hide_breadcrumb ){
			$code = '<div class="mt-3"></div>';
		}else{
			if( empty($this->breadcrumb_parent) ){
				$links = $arr;
			}else{
				$links = $this->breadcrumb_parent;
			}

			if( empty($this->breadcrumb_title) ){
				$breadcrumb_title = ( empty($this->body_title) ? $this->title : $this->body_title );
			}else{
				$breadcrumb_title = $this->breadcrumb_title;
			}

			if( empty($this->reader_name) ){
				$reader_name = '';
			}else{
				if( isset($_GET['reader_id']) && intval($_GET['reader_id']) != 0 ){
					$reader_name = ' | '.$this->reader_name;
				}else{
					$reader_name = '';
				}
			}

			$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
			$extra_title = '';
			if( $page > 1 ){
				$extra_title .= ' | '.word('page').' '.$page;
			}

			if( empty($breadcrumb_title) ){
				$code = '';
			}else{
				$code = '<nav aria-label="breadcrumb" class="custom-breadcrumb">';
				$code .= '<ol class="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">';
				$code .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
				$code .= '<a itemprop="item" class="breadcrumb-link-force" style="color:var(--breadcrumb-inline-color,#0b6555)!important" href="'.$this->siteurl.'"><span itemprop="name" style="color:inherit!important">الرئيسية</span></a>';
				$code .= '<meta itemprop="position" content="1" />';
				$code .= '</li>';

				$URLs = '';
				$i=1;

				if( isset($links['title']) && isset($links['url']) ){
					$title = ( isset($links['title']) ? $links['title'] : '' );
					$url = ( isset($links['url']) ? $links['url'] : '' );
					if( !empty($title) && !empty($url) ){
						++$i;
						$URLs .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
						$URLs .= '<a itemprop="item" class="breadcrumb-link-force" style="color:var(--breadcrumb-inline-color,#0b6555)!important" href="'.$url.'"><span itemprop="name" style="color:inherit!important">'.$title.'</span></a>';
						$URLs .= '<meta itemprop="position" content="'.$i.'" />';
						$URLs .= '</li>';
					}
				}else{
					if( is_array($links) && count($links) > 0 ){
						foreach( $links as $key => $value ){
							$title = ( isset($value['title']) ? $value['title'] : '' );
							$url = ( isset($value['url']) ? $value['url'] : '' );
							if( !empty($title) && !empty($url) ){
								++$i;
								$URLs .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
								$URLs .= '<a itemprop="item" class="breadcrumb-link-force" style="color:var(--breadcrumb-inline-color,#0b6555)!important" href="'.$url.'"><span itemprop="name" style="color:inherit!important">'.$title.'</span></a>';
								$URLs .= '<meta itemprop="position" content="'.$i.'" />';
								$URLs .= '</li>';
							}
						}
					}
				}

				$code .= $URLs;
				$code .= '<li class="breadcrumb-item active" style="color:var(--breadcrumb-active-inline-color,#66756f)!important" aria-current="page">';
				$code .= $breadcrumb_title.$reader_name.$extra_title;
				$code .= '</li>';
				$code .= '</ol>';

				$code .= '</nav>';
			}

		}

		return $code;
	}

	public function createfile($filename, $content){
		if (!$handle = fopen($filename, 'w')) {
			$text = '<div class="alert">Can not open this file ('.$filename.')</div>';
		}

		if (fwrite($handle, $content) === FALSE) {
			$text = '<div class="alert">can not write on this file ('.$filename.')</div>';
		}

		$text = '<div class="alert">SiteMap is created<br /><a href="'.$filename.'">'.$filename.'</a></div>';
		fclose($handle);
		return $text;
	}

	public function xml_surah(){
		$xml = '';
		for($i=1; $i<=114; ++$i){
			$xml .= '<url>'."\n";
			$xml .= '<loc>'.htmlentities($this->url( array( 'surah' => $i ) )).'</loc>'."\n";
			$xml .= '</url>'."\n";
		}
		return $xml;
	}

	public function xml_tafseer(){
		$xml = '';
		for($ix=1; $ix<=5; ++$ix){
			$xml .= '<url>'."\n";
			$xml .= '<loc>'.$this->url( array( 'action' => 'tafseer', 'type' => $ix ) ).'</loc>'."\n";
			$xml .= '</url>'."\n";
		}

		for($iz=1; $iz<=5; ++$iz){
			for($i2=1; $i2<=114; ++$i2){
				$xml .= '<url>'."\n";
				$xml .= '<loc>'.$this->url( array( 'action' => 'tafseer', 'type' => $iz, 'surah' => $i2 ) ).'</loc>'."\n";
				$xml .= '</url>'."\n";
			}
		}

		for($i=1; $i<=5; ++$i){
			for($i2=1; $i2<=114; ++$i2){
				for($i3=1; $i3<=$this->aya_count[$i2]; ++$i3){
					$xml .= '<url>'."\n";
					$xml .= '<loc>'.$this->url( array( 'action' => 'tafseer', 'type' => $i, 'surah' => $i2, 'ayah' => $i3 ) ).'</loc>'."\n";
					$xml .= '</url>'."\n";
				}
			}
		}
		return $xml;
	}

	public function xml_language(){
		$xml = '';
		$languages = $this->languages();
		if( !isset($languages['error']) && isset($languages['data']) && count($languages['data']) > 0 ){
			foreach($languages['data'] as $key => $value){
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$name = ( isset($value['name']) ? $value['name'] : '' );
				$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$file = ( isset($value['file']) ? $value['file'] : '' );
				$book = ( isset($value['book']) ? $value['book'] : '' );
				$more = ( isset($value['more']) ? $value['more'] : '' );
				$source = ( isset($value['source']) ? $value['source'] : '' );
				$lang = ( isset($value['lang']) ? $value['lang'] : '' );
				$getkey = ( isset($value['key']) ? $value['key'] : '' );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );

				$t_url = $this->url( array( 'action' => 'translate', 'l' => $getkey ) );

				if($key != "ar"){
					if( !empty($t_url) && $t_url != "index.html" ){
						$xml .= '<url>'."\n";
						$xml .= '<loc>'.$this->url( array( 'action' => 'translate', 'l' => $getkey ) ).'</loc>'."\n";
						$xml .= '</url>'."\n";
					}
				}
			}

			foreach($languages['data'] as $keys => $values){
				$id = ( isset($values['id']) ? $values['id'] : 0 );
				$name = ( isset($values['name']) ? $values['name'] : '' );
				$name_ar = ( isset($values['name_ar']) ? $values['name_ar'] : '' );
				$name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );
				$file = ( isset($values['file']) ? $values['file'] : '' );
				$book = ( isset($values['book']) ? $values['book'] : '' );
				$more = ( isset($values['more']) ? $values['more'] : '' );
				$source = ( isset($values['source']) ? $values['source'] : '' );
				$lang = ( isset($values['lang']) ? $values['lang'] : '' );
				$getkey = ( isset($values['key']) ? $values['key'] : '' );
				$flag = ( isset($values['flag']) ? $values['flag'] : '' );

				if($keys != 'ar'){
					for($i2=1; $i2<=114; ++$i2){
						$xml .= '<url>'."\n";
						$xml .= '<loc>'.$this->url( array( 'action' => 'translate', 'l' => $getkey, 'surah' => $i2 ) ).'</loc>'."\n";
						$xml .= '</url>'."\n";
					}
				}
			}
		}
		return $xml;
	}

	public function xml_body( $code ){
		$xml = '<?xml version="1.0" encoding="utf-8"?>
		<urlset
		      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
		      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
		            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
		<url>
		  <loc>'.$this->siteurl.'</loc>
		</url>'."\n";
		$xml .= $code;
		$xml .= '</urlset>';
		return $xml;
	}

	public function create_xml(){
		$path = $this->path.'/sitemaps';

		$sitemap_surah = 'sitemap-surah.xml';
		$sitemap_tafseer = 'sitemap-tafseer.xml';
		$sitemap_language = 'sitemap-language.xml';

		if( !file_exists($path.'/'.$sitemap_surah) ){
			$this->createfile($path.'/'.$sitemap_surah, $this->xml_body($this->xml_surah()));
		}
		if( !file_exists($path.'/'.$sitemap_tafseer) ){
			$this->createfile($path.'/'.$sitemap_tafseer, $this->xml_body($this->xml_tafseer()));
		}
		if( !file_exists($path.'/'.$sitemap_language) ){
			$this->createfile($path.'/'.$sitemap_language, $this->xml_body($this->xml_language()));
		}
	}

	public function audio_player($url, $auto=0){
		if($auto==1){ $au = ' autoplay'; }else{ $au = ''; }

		$s = '<audio controls'.$au.'>
		  <source src="'.$url.'" type="audio/mpeg">
		  Your browser does not support the audio element.
		</audio>';
		return $s;
	}

	public function json( $url ){
		$json = get_json( $url );
		$status = ( isset($json['status']) ? $json['status'] : '' );
		$msg = ( isset($json['msg']) ? $json['msg'] : '' );

		if( $status == 'ok' && empty($msg) ){
			return $json;
		}else{
			return array( 'error' => $msg );
		}
	}


	public function home_tools_section(){
		// V22: tools live on standalone pages only. Never render them on home.
		return '';
	}


	public function date_converter_page(){
		$this->title = 'محول التاريخ الهجري والميلادي';
		$this->description = 'تحويل التاريخ بين الهجري والميلادي بسهولة باستخدام تقويم أم القرى.';
		$this->url = $this->url(array('action' => 'date_converter'));
		$base = rtrim($this->siteurl, '/').'/';
		return '<section class="utility-page utility-page-date" id="date-converter" aria-labelledby="date-page-title">'
		.'<div class="container utility-page-container">'
		.'<div class="utility-page-heading"><span class="utility-page-icon"><i class="fas fa-calendar-alt"></i></span><div><span class="utility-eyebrow">تقويم أم القرى</span><h1 id="date-page-title">محول التاريخ الهجري والميلادي</h1><p>حوّل التاريخ بين التقويمين الهجري والميلادي في واجهة بسيطة وواضحة.</p></div></div>'
		.'<div class="utility-page-card utility-page-card-wide">'
		.'<div class="converter-tabs" role="tablist" aria-label="نوع التحويل"><button type="button" class="converter-tab is-active" data-converter-tab="g2h" aria-selected="true">ميلادي ← هجري</button><button type="button" class="converter-tab" data-converter-tab="h2g" aria-selected="false">هجري ← ميلادي</button></div>'
		.'<form class="converter-form" id="gregorian-to-hijri-form"><label for="gregorian-input">التاريخ الميلادي</label><div class="converter-input-row"><input type="date" id="gregorian-input" required><button type="submit"><i class="fas fa-exchange-alt"></i><span>تحويل إلى هجري</span></button></div></form>'
		.'<form class="converter-form is-hidden" id="hijri-to-gregorian-form"><label>التاريخ الهجري</label><div class="hijri-fields"><input type="number" id="hijri-day" min="1" max="30" inputmode="numeric" placeholder="اليوم" aria-label="اليوم الهجري" required><select id="hijri-month" aria-label="الشهر الهجري" required><option value="1">محرم</option><option value="2">صفر</option><option value="3">ربيع الأول</option><option value="4">ربيع الآخر</option><option value="5">جمادى الأولى</option><option value="6">جمادى الآخرة</option><option value="7">رجب</option><option value="8">شعبان</option><option value="9">رمضان</option><option value="10">شوال</option><option value="11">ذو القعدة</option><option value="12">ذو الحجة</option></select><input type="number" id="hijri-year" min="1" max="2000" inputmode="numeric" placeholder="السنة" aria-label="السنة الهجرية" required></div><button type="submit" class="converter-submit-wide"><i class="fas fa-exchange-alt"></i><span>تحويل إلى ميلادي</span></button></form>'
		.'<div class="converter-result" id="converter-result" aria-live="polite"><span class="converter-result-label">النتيجة</span><strong id="converter-result-main">اختر تاريخًا لبدء التحويل</strong><small id="converter-result-sub">يتم الحساب باستخدام تقويم أم القرى.</small></div>'
		.'<div class="utility-page-note"><i class="fas fa-info-circle"></i><span>قد يختلف التاريخ الهجري يومًا بحسب ثبوت الرؤية الشرعية في بلدك.</span></div>'
		.'</div></div></section>';
	}

	public function contact_page(){
		$this->title = 'تواصل معنا';
		$this->description = 'أرسل ملاحظاتك واقتراحاتك المتعلقة بموقع القرآن الكريم.';
		$this->url = $this->url(array('action' => 'contact'));
		$base = rtrim($this->siteurl, '/').'/';
		return '<section class="contact-rebuilt" aria-labelledby="contact-page-title">'
		.'<div class="container contact-rebuilt-container">'
		.'<header class="contact-rebuilt-heading"><span class="contact-rebuilt-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span><div><span class="contact-rebuilt-eyebrow">نسعد بملاحظاتكم</span><h1 id="contact-page-title">تواصل معنا</h1><p>للاقتراحات أو الملاحظات المتعلقة بالموقع، أرسل رسالتك وسنطّلع عليها بإذن الله.</p></div></header>'
		.'<div class="contact-rebuilt-card">'
		.'<form class="contact-form contact-form-rebuilt" id="contact-form" action="'.$base.'contact-submit.php" method="post">'
		.'<input type="hidden" name="csrf" value="'.htmlspecialchars(qfa_auth_csrf(), ENT_QUOTES, 'UTF-8').'">'
		.'<div class="contact-form-grid"><div class="contact-field"><label for="contact-name">الاسم</label><div class="field-with-icon"><i class="fas fa-user"></i><input type="text" id="contact-name" name="name" maxlength="80" autocomplete="name" placeholder="اكتب اسمك" required></div></div>'
		.'<div class="contact-field"><label for="contact-email">البريد الإلكتروني</label><div class="field-with-icon"><i class="fas fa-envelope"></i><input type="email" id="contact-email" name="email" maxlength="120" autocomplete="email" placeholder="example@email.com" required></div></div></div>'
		.'<div class="contact-field"><label for="contact-subject">الموضوع</label><div class="field-with-icon"><i class="fas fa-heading"></i><input type="text" id="contact-subject" name="subject" maxlength="120" placeholder="اكتب موضوع الرسالة" required></div><div class="contact-field-hint">اكتب عنوانًا مختصرًا يوضح محتوى الرسالة.</div></div>'
		.'<div class="contact-field"><label for="contact-message">رسالتك</label><textarea id="contact-message" name="message" rows="7" maxlength="2000" placeholder="اكتب رسالتك هنا..." required></textarea><div class="contact-field-hint">الحد الأقصى 2000 حرف.</div></div>'
		.'<div class="contact-honeypot" aria-hidden="true"><label for="contact-website">الموقع</label><input type="text" id="contact-website" name="website" tabindex="-1" autocomplete="off"></div>'
		.'<button type="submit" class="contact-submit"><i class="fas fa-paper-plane"></i><span>إرسال الرسالة</span></button><div class="contact-status" id="contact-status" aria-live="polite"></div>'
		.'</form></div></div></section>';
	}

	public function morning_adhkar_page(){
		$this->title = 'أذكار الصباح';
		$this->description = 'أذكار الصباح الصحيحة في صفحة تفاعلية مع عداد للتكرار ومصادر مختصرة.';
		$this->url = $this->url(array('action' => 'morning_adhkar'));
		$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/morning-adhkar.css?v=1.5">';
		$this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/morning-adhkar.js?v=1.0" defer></script>';

		$items = array(
			array('آية الكرسي', 'اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ الْحَيُّ الْقَيُّومُ ۚ لَا تَأْخُذُهُ سِنَةٌ وَلَا نَوْمٌ ۚ لَهُ مَا فِي السَّمَاوَاتِ وَمَا فِي الْأَرْضِ ۗ مَنْ ذَا الَّذِي يَشْفَعُ عِنْدَهُ إِلَّا بِإِذْنِهِ ۚ يَعْلَمُ مَا بَيْنَ أَيْدِيهِمْ وَمَا خَلْفَهُمْ ۖ وَلَا يُحِيطُونَ بِشَيْءٍ مِنْ عِلْمِهِ إِلَّا بِمَا شَاءَ ۚ وَسِعَ كُرْسِيُّهُ السَّمَاوَاتِ وَالْأَرْضَ ۖ وَلَا يَئُودُهُ حِفْظُهُمَا ۚ وَهُوَ الْعَلِيُّ الْعَظِيمُ.', 1, 'حصن المسلم، رقم 75 • البقرة: 255'),
			array('سور الإخلاص والفلق والناس', 'قُلْ هُوَ اللَّهُ أَحَدٌ ۝ اللَّهُ الصَّمَدُ ۝ لَمْ يَلِدْ وَلَمْ يُولَدْ ۝ وَلَمْ يَكُنْ لَهُ كُفُوًا أَحَدٌ<br><br>قُلْ أَعُوذُ بِرَبِّ الْفَلَقِ ۝ مِنْ شَرِّ مَا خَلَقَ ۝ وَمِنْ شَرِّ غَاسِقٍ إِذَا وَقَبَ ۝ وَمِنْ شَرِّ النَّفَّاثَاتِ فِي الْعُقَدِ ۝ وَمِنْ شَرِّ حَاسِدٍ إِذَا حَسَدَ<br><br>قُلْ أَعُوذُ بِرَبِّ النَّاسِ ۝ مَلِكِ النَّاسِ ۝ إِلَٰهِ النَّاسِ ۝ مِنْ شَرِّ الْوَسْوَاسِ الْخَنَّاسِ ۝ الَّذِي يُوَسْوِسُ فِي صُدُورِ النَّاسِ ۝ مِنَ الْجِنَّةِ وَالنَّاسِ.', 3, 'حصن المسلم، رقم 76 • أبو داود 5082 والترمذي 3575'),
			array('دعاء الصباح', 'اللَّهُمَّ بِكَ أَصْبَحْنَا، وَبِكَ أَمْسَيْنَا، وَبِكَ نَحْيَا، وَبِكَ نَمُوتُ، وَإِلَيْكَ النُّشُورُ.', 1, 'الدرر السنية 1631 • الأدب المفرد 1199'),
			array('على فطرة الإسلام', 'أَصْبَحْنَا عَلَى فِطْرَةِ الإِسْلَامِ، وَكَلِمَةِ الإِخْلَاصِ، وَدِينِ نَبِيِّنَا مُحَمَّدٍ صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ، وَمِلَّةِ أَبِينَا إِبْرَاهِيمَ حَنِيفًا مُسْلِمًا، وَمَا أَنَا مِنَ الْمُشْرِكِينَ.', 1, 'الدرر السنية 1633 • أحمد 15367'),
			array('أصبحنا وأصبح الملك لله', 'أَصْبَحْنَا وَأَصْبَحَ الْمُلْكُ لِلَّهِ، وَالْحَمْدُ لِلَّهِ، لَا إِلَهَ إِلَّا اللَّهُ وَحْدَهُ لَا شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ، وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ. رَبِّ أَسْأَلُكَ خَيْرَ مَا فِي هَذَا الْيَوْمِ وَخَيْرَ مَا بَعْدَهُ، وَأَعُوذُ بِكَ مِنْ شَرِّ مَا فِي هَذَا الْيَوْمِ وَشَرِّ مَا بَعْدَهُ. رَبِّ أَعُوذُ بِكَ مِنَ الْكَسَلِ وَسُوءِ الْكِبَرِ، رَبِّ أَعُوذُ بِكَ مِنْ عَذَابٍ فِي النَّارِ وَعَذَابٍ فِي الْقَبْرِ.', 1, 'الدرر السنية 1634 • صحيح مسلم 2723'),
			array('سيد الاستغفار', 'اللَّهُمَّ أَنْتَ رَبِّي لَا إِلَهَ إِلَّا أَنْتَ، خَلَقْتَنِي وَأَنَا عَبْدُكَ، وَأَنَا عَلَى عَهْدِكَ وَوَعْدِكَ مَا اسْتَطَعْتُ، أَعُوذُ بِكَ مِنْ شَرِّ مَا صَنَعْتُ، أَبُوءُ لَكَ بِنِعْمَتِكَ عَلَيَّ، وَأَبُوءُ لَكَ بِذَنْبِي، فَاغْفِرْ لِي؛ فَإِنَّهُ لَا يَغْفِرُ الذُّنُوبَ إِلَّا أَنْتَ.', 1, 'الدرر السنية 1656 • صحيح البخاري 6306'),
			array('العفو والعافية', 'اللَّهُمَّ إِنِّي أَسْأَلُكَ الْعَافِيَةَ فِي الدُّنْيَا وَالْآخِرَةِ، اللَّهُمَّ إِنِّي أَسْأَلُكَ الْعَفْوَ وَالْعَافِيَةَ فِي دِينِي وَدُنْيَايَ وَأَهْلِي وَمَالِي، اللَّهُمَّ اسْتُرْ عَوْرَاتِي، وَآمِنْ رَوْعَاتِي، اللَّهُمَّ احْفَظْنِي مِنْ بَيْنِ يَدَيَّ، وَمِنْ خَلْفِي، وَعَنْ يَمِينِي، وَعَنْ شِمَالِي، وَمِنْ فَوْقِي، وَأَعُوذُ بِعَظَمَتِكَ أَنْ أُغْتَالَ مِنْ تَحْتِي.', 1, 'الدرر السنية 1649 • أبو داود 5074'),
			array('فاطر السماوات والأرض', 'اللَّهُمَّ فَاطِرَ السَّمَاوَاتِ وَالْأَرْضِ، عَالِمَ الْغَيْبِ وَالشَّهَادَةِ، رَبَّ كُلِّ شَيْءٍ وَمَلِيكَهُ، أَشْهَدُ أَنْ لَا إِلَهَ إِلَّا أَنْتَ، أَعُوذُ بِكَ مِنْ شَرِّ نَفْسِي، وَشَرِّ الشَّيْطَانِ وَشِرْكِهِ، وَأَنْ أَقْتَرِفَ عَلَى نَفْسِي سُوءًا أَوْ أَجُرَّهُ إِلَى مُسْلِمٍ.', 1, 'الدرر السنية 1651–1652 • أبو داود 5067'),
			array('بسم الله الذي لا يضر', 'بِسْمِ اللَّهِ الَّذِي لَا يَضُرُّ مَعَ اسْمِهِ شَيْءٌ فِي الْأَرْضِ وَلَا فِي السَّمَاءِ، وَهُوَ السَّمِيعُ الْعَلِيمُ.', 3, 'الدرر السنية 1664 • أبو داود 5088'),
			array('رضيت بالله ربًا', 'رَضِيتُ بِاللَّهِ رَبًّا، وَبِالإِسْلَامِ دِينًا، وَبِمُحَمَّدٍ صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ نَبِيًّا.', 3, 'حصن المسلم، رقم 87 • أبو داود 5072'),
			array('يا حي يا قيوم', 'يَا حَيُّ يَا قَيُّومُ، بِرَحْمَتِكَ أَسْتَغِيثُ، أَصْلِحْ لِي شَأْنِي كُلَّهُ، وَلَا تَكِلْنِي إِلَى نَفْسِي طَرْفَةَ عَيْنٍ.', 1, 'الدرر السنية 1646 • النسائي في الكبرى 10330'),
			array('تسبيح أول النهار', 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ، عَدَدَ خَلْقِهِ، وَرِضَا نَفْسِهِ، وَزِنَةَ عَرْشِهِ، وَمِدَادَ كَلِمَاتِهِ.', 3, 'الدرر السنية 1660 • صحيح مسلم 2726'),
			array('سبحان الله وبحمده', 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ.', 100, 'الدرر السنية 1642 • صحيح مسلم 2692'),
			array('التهليل', 'لَا إِلَهَ إِلَّا اللَّهُ وَحْدَهُ لَا شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ، وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ.', 10, 'الدرر السنية 1637 • أحمد 23568'),
		);

		$cards = '';
		foreach($items as $index => $item){
			$cards .= '<article class="adhkar-card" data-adhkar-card data-target="'.$item[2].'" data-count="0">'
			.'<div class="adhkar-card-head"><span class="adhkar-number">'.($index + 1).'</span><h2>'.$item[0].'</h2><span class="adhkar-times">'.$item[2].' '.($item[2] === 1 ? 'مرة' : 'مرات').'</span></div>'
			.'<div class="adhkar-text">'.$item[1].'</div>'
			.'<div class="adhkar-card-foot"><p class="adhkar-source"><i class="fas fa-book-open" aria-hidden="true"></i><span>'.$item[3].'</span></p>'
			.'<button class="adhkar-counter" type="button" data-counter aria-label="تسجيل تكرار '.$item[0].'"><span class="counter-label">اضغط بعد القراءة</span><strong data-remaining>'.$item[2].'</strong></button></div>'
			.'</article>';
		}

		return '<section class="adhkar-page" aria-labelledby="adhkar-title"><div class="container adhkar-container">'
		.'<header class="adhkar-hero"><div><span class="adhkar-eyebrow">وِردُ بداية اليوم</span><h1 id="adhkar-title">أذكار الصباح</h1><p>أذكار صحيحة مختارة من الدرر السنية وحصن المسلم، بالنصوص الخاصة بالصباح.</p></div><span class="adhkar-sun" aria-hidden="true"><i class="fas fa-sun"></i></span></header>'
		.'<div class="adhkar-progress-panel" aria-label="تقدم أذكار الصباح"><div class="adhkar-progress-copy"><span>تقدّمك اليوم</span><strong><b data-completed>0</b> من <b data-total>'.count($items).'</b></strong></div><div class="adhkar-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="'.count($items).'" aria-valuenow="0"><span data-progress-bar></span></div><div class="adhkar-actions"><span data-progress-message>ابدأ بالذكر الأول، وتقبّل الله طاعتك.</span><button type="button" data-reset><i class="fas fa-redo-alt" aria-hidden="true"></i> إعادة الأذكار</button></div></div>'
		.'<div class="adhkar-list">'.$cards.'</div>'
		.'<footer class="adhkar-reference"><i class="fas fa-shield-alt" aria-hidden="true"></i><p><strong>منهج التوثيق</strong><span>اقتصرنا على الأذكار الثابتة في المرجعين، ولم ندرج صيغة المساء في المواضع التي يختلف فيها اللفظ.</span></p><a href="https://dorar.net/azkar/adhkar/343" target="_blank" rel="noopener noreferrer">الدرر السنية</a></footer>'
		.'</div></section>';
	}

	public function evening_adhkar_page(){
		$this->title = 'أذكار المساء';
		$this->description = 'أذكار المساء الصحيحة في صفحة تفاعلية مع عداد للتكرار ومصادر مختصرة.';
		$this->url = $this->url(array('action' => 'evening_adhkar'));
		$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/morning-adhkar.css?v=1.5">';
		$this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/morning-adhkar.js?v=1.0" defer></script>';

		$items = array(
			array('آية الكرسي', 'اللَّهُ لَا إِلَٰهَ إِلَّا هُوَ الْحَيُّ الْقَيُّومُ ۚ لَا تَأْخُذُهُ سِنَةٌ وَلَا نَوْمٌ ۚ لَهُ مَا فِي السَّمَاوَاتِ وَمَا فِي الْأَرْضِ ۗ مَنْ ذَا الَّذِي يَشْفَعُ عِنْدَهُ إِلَّا بِإِذْنِهِ ۚ يَعْلَمُ مَا بَيْنَ أَيْدِيهِمْ وَمَا خَلْفَهُمْ ۖ وَلَا يُحِيطُونَ بِشَيْءٍ مِنْ عِلْمِهِ إِلَّا بِمَا شَاءَ ۚ وَسِعَ كُرْسِيُّهُ السَّمَاوَاتِ وَالْأَرْضَ ۖ وَلَا يَئُودُهُ حِفْظُهُمَا ۚ وَهُوَ الْعَلِيُّ الْعَظِيمُ.', 1, 'حصن المسلم، رقم 75 • البقرة: 255'),
			array('سور الإخلاص والفلق والناس', 'قُلْ هُوَ اللَّهُ أَحَدٌ ۝ اللَّهُ الصَّمَدُ ۝ لَمْ يَلِدْ وَلَمْ يُولَدْ ۝ وَلَمْ يَكُنْ لَهُ كُفُوًا أَحَدٌ<br><br>قُلْ أَعُوذُ بِرَبِّ الْفَلَقِ ۝ مِنْ شَرِّ مَا خَلَقَ ۝ وَمِنْ شَرِّ غَاسِقٍ إِذَا وَقَبَ ۝ وَمِنْ شَرِّ النَّفَّاثَاتِ فِي الْعُقَدِ ۝ وَمِنْ شَرِّ حَاسِدٍ إِذَا حَسَدَ<br><br>قُلْ أَعُوذُ بِرَبِّ النَّاسِ ۝ مَلِكِ النَّاسِ ۝ إِلَٰهِ النَّاسِ ۝ مِنْ شَرِّ الْوَسْوَاسِ الْخَنَّاسِ ۝ الَّذِي يُوَسْوِسُ فِي صُدُورِ النَّاسِ ۝ مِنَ الْجِنَّةِ وَالنَّاسِ.', 3, 'حصن المسلم، رقم 76 • أبو داود 5082 والترمذي 3575'),
			array('دعاء المساء', 'اللَّهُمَّ بِكَ أَمْسَيْنَا، وَبِكَ أَصْبَحْنَا، وَبِكَ نَحْيَا، وَبِكَ نَمُوتُ، وَإِلَيْكَ الْمَصِيرُ.', 1, 'الدرر السنية 1632 • الأدب المفرد 1199'),
			array('أمسينا وأمسى الملك لله', 'أَمْسَيْنَا وَأَمْسَى الْمُلْكُ لِلَّهِ، وَالْحَمْدُ لِلَّهِ، لَا إِلَهَ إِلَّا اللَّهُ وَحْدَهُ لَا شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ، وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ. رَبِّ أَسْأَلُكَ خَيْرَ مَا فِي هَذِهِ اللَّيْلَةِ وَخَيْرَ مَا بَعْدَهَا، وَأَعُوذُ بِكَ مِنْ شَرِّ مَا فِي هَذِهِ اللَّيْلَةِ وَشَرِّ مَا بَعْدَهَا. رَبِّ أَعُوذُ بِكَ مِنَ الْكَسَلِ وَسُوءِ الْكِبَرِ، رَبِّ أَعُوذُ بِكَ مِنْ عَذَابٍ فِي النَّارِ وَعَذَابٍ فِي الْقَبْرِ.', 1, 'الدرر السنية 1634 • صحيح مسلم 2723'),
			array('سيد الاستغفار', 'اللَّهُمَّ أَنْتَ رَبِّي لَا إِلَهَ إِلَّا أَنْتَ، خَلَقْتَنِي وَأَنَا عَبْدُكَ، وَأَنَا عَلَى عَهْدِكَ وَوَعْدِكَ مَا اسْتَطَعْتُ، أَعُوذُ بِكَ مِنْ شَرِّ مَا صَنَعْتُ، أَبُوءُ لَكَ بِنِعْمَتِكَ عَلَيَّ، وَأَبُوءُ لَكَ بِذَنْبِي، فَاغْفِرْ لِي؛ فَإِنَّهُ لَا يَغْفِرُ الذُّنُوبَ إِلَّا أَنْتَ.', 1, 'الدرر السنية 1656 • صحيح البخاري 6306'),
			array('العفو والعافية', 'اللَّهُمَّ إِنِّي أَسْأَلُكَ الْعَافِيَةَ فِي الدُّنْيَا وَالْآخِرَةِ، اللَّهُمَّ إِنِّي أَسْأَلُكَ الْعَفْوَ وَالْعَافِيَةَ فِي دِينِي وَدُنْيَايَ وَأَهْلِي وَمَالِي، اللَّهُمَّ اسْتُرْ عَوْرَاتِي، وَآمِنْ رَوْعَاتِي، اللَّهُمَّ احْفَظْنِي مِنْ بَيْنِ يَدَيَّ، وَمِنْ خَلْفِي، وَعَنْ يَمِينِي، وَعَنْ شِمَالِي، وَمِنْ فَوْقِي، وَأَعُوذُ بِعَظَمَتِكَ أَنْ أُغْتَالَ مِنْ تَحْتِي.', 1, 'الدرر السنية 1649 • أبو داود 5074'),
			array('فاطر السماوات والأرض', 'اللَّهُمَّ فَاطِرَ السَّمَاوَاتِ وَالْأَرْضِ، عَالِمَ الْغَيْبِ وَالشَّهَادَةِ، رَبَّ كُلِّ شَيْءٍ وَمَلِيكَهُ، أَشْهَدُ أَنْ لَا إِلَهَ إِلَّا أَنْتَ، أَعُوذُ بِكَ مِنْ شَرِّ نَفْسِي، وَشَرِّ الشَّيْطَانِ وَشِرْكِهِ، وَأَنْ أَقْتَرِفَ عَلَى نَفْسِي سُوءًا أَوْ أَجُرَّهُ إِلَى مُسْلِمٍ.', 1, 'الدرر السنية 1651–1652 • أبو داود 5067'),
			array('أعوذ بكلمات الله التامات', 'أَعُوذُ بِكَلِمَاتِ اللَّهِ التَّامَّاتِ مِنْ شَرِّ مَا خَلَقَ.', 3, 'الدرر السنية 1653–1655 • صحيح مسلم 2709'),
			array('بسم الله الذي لا يضر', 'بِسْمِ اللَّهِ الَّذِي لَا يَضُرُّ مَعَ اسْمِهِ شَيْءٌ فِي الْأَرْضِ وَلَا فِي السَّمَاءِ، وَهُوَ السَّمِيعُ الْعَلِيمُ.', 3, 'الدرر السنية 1664 • أبو داود 5088'),
			array('رضيت بالله ربًا', 'رَضِيتُ بِاللَّهِ رَبًّا، وَبِالإِسْلَامِ دِينًا، وَبِمُحَمَّدٍ صَلَّى اللَّهُ عَلَيْهِ وَسَلَّمَ نَبِيًّا.', 3, 'حصن المسلم، رقم 87 • أبو داود 5072'),
			array('يا حي يا قيوم', 'يَا حَيُّ يَا قَيُّومُ، بِرَحْمَتِكَ أَسْتَغِيثُ، أَصْلِحْ لِي شَأْنِي كُلَّهُ، وَلَا تَكِلْنِي إِلَى نَفْسِي طَرْفَةَ عَيْنٍ.', 1, 'الدرر السنية 1646 • النسائي في الكبرى 10330'),
			array('سبحان الله وبحمده', 'سُبْحَانَ اللَّهِ وَبِحَمْدِهِ.', 100, 'الدرر السنية 1642 • صحيح مسلم 2692'),
			array('التهليل', 'لَا إِلَهَ إِلَّا اللَّهُ وَحْدَهُ لَا شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ، وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ.', 10, 'الدرر السنية 1637 • أحمد 23568'),
		);

		$cards = '';
		foreach($items as $index => $item){
			$cards .= '<article class="adhkar-card" data-adhkar-card data-target="'.$item[2].'" data-count="0">'
			.'<div class="adhkar-card-head"><span class="adhkar-number">'.($index + 1).'</span><h2>'.$item[0].'</h2><span class="adhkar-times">'.$item[2].' '.($item[2] === 1 ? 'مرة' : 'مرات').'</span></div>'
			.'<div class="adhkar-text">'.$item[1].'</div>'
			.'<div class="adhkar-card-foot"><p class="adhkar-source"><i class="fas fa-book-open" aria-hidden="true"></i><span>'.$item[3].'</span></p>'
			.'<button class="adhkar-counter" type="button" data-counter aria-label="تسجيل تكرار '.$item[0].'"><span class="counter-label">اضغط بعد القراءة</span><strong data-remaining>'.$item[2].'</strong></button></div>'
			.'</article>';
		}

		return '<section class="adhkar-page evening-adhkar-page" aria-labelledby="adhkar-title"><div class="container adhkar-container">'
		.'<header class="adhkar-hero"><div><span class="adhkar-eyebrow">وِردُ ختام اليوم</span><h1 id="adhkar-title">أذكار المساء</h1><p>أذكار صحيحة مختارة من الدرر السنية وحصن المسلم، بالنصوص الخاصة بالمساء.</p></div><span class="adhkar-sun" aria-hidden="true"><i class="fas fa-moon"></i></span></header>'
		.'<div class="adhkar-progress-panel" aria-label="تقدم أذكار المساء"><div class="adhkar-progress-copy"><span>تقدّمك هذا المساء</span><strong><b data-completed>0</b> من <b data-total>'.count($items).'</b></strong></div><div class="adhkar-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="'.count($items).'" aria-valuenow="0"><span data-progress-bar></span></div><div class="adhkar-actions"><span data-progress-message>ابدأ بالذكر الأول، وتقبّل الله طاعتك.</span><button type="button" data-reset><i class="fas fa-redo-alt" aria-hidden="true"></i> إعادة الأذكار</button></div></div>'
		.'<div class="adhkar-list">'.$cards.'</div>'
		.'<footer class="adhkar-reference"><i class="fas fa-shield-alt" aria-hidden="true"></i><p><strong>منهج التوثيق</strong><span>اقتصرنا على الأذكار الثابتة في المرجعين، واستعملنا صيغة المساء حيث يختلف اللفظ.</span></p><a href="https://dorar.net/azkar/adhkar/343" target="_blank" rel="noopener noreferrer">الدرر السنية</a></footer>'
		.'</div></section>';
	}

	public function qibla_page(){
		$this->title = 'اتجاه القبلة';
		$this->description = 'تحديد اتجاه القبلة من موقعك ببوصلة واضحة ومتوافقة مع الهاتف.';
		$this->url = $this->url(array('action' => 'qibla'));
		$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/qibla.css?v=1.2">';
		$this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/qibla.js?v=1.3" defer></script>';

		return '<section class="qibla-page" aria-labelledby="qibla-title"><div class="container qibla-container">'
		.'<header class="qibla-heading"><span class="qibla-heading-icon" aria-hidden="true"><i class="fas fa-kaaba"></i></span><div><span class="utility-eyebrow">أينما كنت</span><h1 id="qibla-title">اتجاه القبلة</h1><p>حدّد موقعك لتظهر لك جهة الكعبة المشرفة وزاوية القبلة بدقة.</p></div></header>'
		.'<div class="qibla-card">'
		.'<div class="qibla-compass-wrap"><div class="qibla-compass" data-compass role="img" aria-label="بوصلة اتجاه القبلة"><span class="qibla-mark qibla-north">ش</span><span class="qibla-mark qibla-east">ق</span><span class="qibla-mark qibla-south">ج</span><span class="qibla-mark qibla-west">غ</span><span class="qibla-ring qibla-ring-one"></span><span class="qibla-ring qibla-ring-two"></span><div class="qibla-needle" data-needle><span class="qibla-kaaba"><i class="fas fa-kaaba"></i></span><span class="qibla-arrow"></span></div><span class="qibla-center"></span></div></div>'
		.'<div class="qibla-info"><span class="qibla-status-label">الحالة</span><h2 data-status>اضغط «حدّد موقعي» للبدء</h2><p data-help>سنطلب إذن الموقع مرة واحدة لحساب اتجاه القبلة من مكانك.</p>'
		.'<div class="qibla-result" aria-live="polite"><div><span>زاوية القبلة</span><strong><b data-bearing>—</b>°</strong></div><div><span>الاتجاه التقريبي</span><strong data-direction>—</strong></div><div><span>دقة الموقع</span><strong data-accuracy>—</strong></div></div>'
		.'<div class="qibla-buttons"><button class="qibla-primary" type="button" data-locate><i class="fas fa-location-arrow" aria-hidden="true"></i><span>حدّد موقعي</span></button><button class="qibla-secondary" type="button" data-orientation hidden><i class="fas fa-compass" aria-hidden="true"></i><span>فعّل بوصلة الهاتف</span></button></div>'
		.'<div class="qibla-tip"><i class="fas fa-info-circle" aria-hidden="true"></i><span>لأفضل نتيجة، ابتعد عن المعادن وحرّك الهاتف على شكل الرقم 8 لمعايرة البوصلة.</span></div>'
		.'</div></div>'
		.'<footer class="qibla-note"><i class="fas fa-shield-alt" aria-hidden="true"></i><p><strong>خصوصيتك محفوظة</strong><span>يُستخدم موقعك داخل المتصفح فقط لحساب الاتجاه، ولا يتم إرساله أو حفظه.</span></p></footer>'
		.'</div></section>';
	}

	public function tasbeeh_page(){
		$this->title = 'المسبحة الرقمية';
		$this->description = 'مسبحة رقمية سهلة بعداد تفاعلي وهدف يومي من مئة تسبيحة.';
		$this->url = $this->url(array('action' => 'tasbeeh'));
		$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/tasbeeh.css?v=1.2">';
		$this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/tasbeeh.js?v=1.0" defer></script>';

		return '<section class="tasbeeh-page" aria-labelledby="tasbeeh-title"><div class="container tasbeeh-container">'
		.'<header class="tasbeeh-heading"><span class="tasbeeh-heading-icon" aria-hidden="true"><i class="fas fa-hand-pointer"></i></span><div><span class="utility-eyebrow">ذكرٌ يسير وأجرٌ عظيم</span><h1 id="tasbeeh-title">المسبحة الرقمية</h1><p>اضغط على الدائرة مع كل تسبيحة، وسيُحفظ عدّادك تلقائيًا على هذا الجهاز.</p></div></header>'
		.'<div class="tasbeeh-card" data-tasbeeh data-goal="100">'
		.'<div class="tasbeeh-goal"><span>الهدف</span><strong><b data-goal-value>100</b> تسبيحة</strong></div>'
		.'<button class="tasbeeh-tap" type="button" data-tasbeeh-tap aria-label="إضافة تسبيحة"><span class="tasbeeh-tap-label">اضغط للتسبيح</span><strong data-tasbeeh-count>0</strong><small data-tasbeeh-remaining>باقي 100</small></button>'
		.'<div class="tasbeeh-progress" role="progressbar" aria-label="تقدم التسبيح" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-tasbeeh-progress></span></div>'
		.'<p class="tasbeeh-message" data-tasbeeh-message aria-live="polite">ابدأ بذكر الله</p>'
		.'<button class="tasbeeh-reset" type="button" data-tasbeeh-reset><i class="fas fa-redo-alt" aria-hidden="true"></i><span>إعادة العداد</span></button>'
		.'</div>'
		.'<footer class="tasbeeh-note"><i class="fas fa-mobile-alt" aria-hidden="true"></i><p><strong>عدادك يبقى محفوظًا</strong><span>يمكنك إغلاق الصفحة والعودة لاحقًا لإكمال التسبيح من الرقم نفسه.</span></p></footer>'
		.'</div></section>';
	}

	public function sadaqah_agent_page(){
		$this->title = 'وكيل الصدقة الجارية';
		$this->description = 'معاينة وجدولة محتوى حساب الصدقة الجارية قبل ربطه بمنصة X.';
		$this->url = $this->url(array('action' => 'sadaqah_agent'));
		$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/sadaqah-agent.css?v=1.0">';
		$this->footercode .= '<script src="'.$this->get_theme_folder_url().'/js/sadaqah-agent.js?v=1.0" defer></script>';

		$days = array(
			array('الأحد','3 تغريدات',array(
				array('08:00 ص','آية','﴿وَقُل رَّبِّ زِدْنِي عِلْمًا﴾\n\nدعاء قرآني جامع لطلب العلم النافع والزيادة من فضله سبحانه.\n\n📖 سورة طه: [رابط السورة]'),
				array('01:00 م','ذكر','سبحان الله وبحمده، سبحان الله العظيم 🌿\n\nذكرٌ يسير على اللسان، عظيم في الميزان.'),
				array('08:30 م','دعاء','اللهم اغفر لصاحب هذه الصدقة الجارية وارحمه، واجعل القرآن نورًا له ورفعةً في درجاته.')
			)),
			array('الاثنين','3 تغريدات',array(
				array('08:00 ص','آية','﴿إِنَّ مَعَ الْعُسْرِ يُسْرًا﴾\n\nمهما اشتد الأمر، فرحمة الله أقرب وأوسع.\n\n📖 سورة الشرح: [رابط السورة]'),
				array('05:00 م','من الموقع','حصّن مساءك بذكر الله 🌙\n\nأذكار المساء الصحيحة مع عداد يساعدك على إتمام وردك:\n[رابط أذكار المساء]'),
				array('09:00 م','دعاء','اللهم اجعل هذا الحساب بابًا للأجر الذي لا ينقطع، وارحم صاحبه رحمةً واسعة.')
			)),
			array('الثلاثاء','3 تغريدات',array(
				array('08:00 ص','آية','﴿فَاذْكُرُونِي أَذْكُرْكُمْ﴾\n\nابدأ يومك بذكر الله؛ ففي الذكر طمأنينة القلوب.\n\n📖 سورة البقرة: [رابط السورة]'),
				array('01:00 م','ذكر','لا حول ولا قوة إلا بالله.\n\nكلمة استعانة وتفويض، يطمئن بها القلب ويقوى بها العبد.'),
				array('08:30 م','دعاء','اللهم آنس وحشة صاحب هذه الصدقة الجارية، ونوّر قبره، واجمعه بمن يحب في جنات النعيم.')
			)),
			array('الأربعاء','3 تغريدات',array(
				array('08:00 ص','آية','﴿أَلَا بِذِكْرِ اللَّهِ تَطْمَئِنُّ الْقُلُوبُ﴾\n\n📖 سورة الرعد: [رابط السورة]'),
				array('05:00 م','من الموقع','اقرأ ما تيسر من كتاب الله، واستمع لتلاوة خاشعة من قارئك المفضل.\n\n📖 القرآن الكريم: [رابط الموقع]'),
				array('09:00 م','دعاء','ربنا آتنا في الدنيا حسنة، وفي الآخرة حسنة، وقنا عذاب النار.')
			)),
			array('الخميس','3 تغريدات',array(
				array('08:00 ص','آية','﴿وَمَا تَوْفِيقِي إِلَّا بِاللَّهِ﴾\n\nكل توفيق من الله، فاستعن به وتوكل عليه.\n\n📖 سورة هود: [رابط السورة]'),
				array('05:00 م','ذكر','أستغفر الله العظيم وأتوب إليه 🌿'),
				array('09:00 م','دعاء','اللهم بلّغنا الجمعة بقلوب مطمئنة، وأعمال مقبولة، وذنوب مغفورة.')
			)),
			array('الجمعة','4 تغريدات',array(
				array('08:00 ص','الجمعة','جمعة مباركة 🤍\n\nأكثروا من الصلاة والسلام على رسول الله ﷺ.'),
				array('10:00 ص','سورة الكهف','نورٌ ما بين الجمعتين 🌿\n\nلا تنسَ قراءة سورة الكهف وتدبر آياتها.\n\n📖 سورة الكهف: [رابط السورة]'),
				array('04:30 م','الجمعة','في يوم الجمعة ساعة لا يوافقها عبد مسلم يسأل الله خيرًا إلا أعطاه؛ فالتمسها وأكثر من الدعاء.'),
				array('08:30 م','دعاء','اللهم في يوم الجمعة اغفر لصاحب هذه الصدقة الجارية وارحمه، واجعل منزلته في الفردوس الأعلى.')
			)),
			array('السبت','3 تغريدات',array(
				array('08:30 ص','آية','﴿وَهُوَ مَعَكُمْ أَيْنَ مَا كُنتُمْ﴾\n\nمعية الله سكينةٌ للقلب وأمان.\n\n📖 سورة الحديد: [رابط السورة]'),
				array('01:00 م','من الموقع','تعرّف على معاني الآيات من التفاسير الميسرة والموثوقة.\n\n📚 التفاسير: [رابط التفاسير]'),
				array('08:30 م','دعاء','اللهم اختم لنا أسبوعنا بعفوك ورضاك، وافتح لنا القادم بالخير والبركة.')
			))
		);

		$dayTabs = '';
		$dayPanels = '';
		$postId = 0;
		foreach($days as $dayIndex => $day){
			$dayTabs .= '<button type="button" class="agent-day-tab'.($dayIndex === 0 ? ' is-active' : '').'" data-day-tab="'.$dayIndex.'" aria-selected="'.($dayIndex === 0 ? 'true' : 'false').'"><strong>'.$day[0].'</strong><span>'.$day[1].'</span></button>';
			$posts = '';
			foreach($day[2] as $post){
				$postId++;
				$posts .= '<article class="agent-post" data-post="'.$postId.'"><div class="agent-post-meta"><time>'.$post[0].'</time><span>'.$post[1].'</span><b data-post-status>بانتظار المراجعة</b></div><textarea aria-label="نص تغريدة '.$postId.'">'.$post[2].'</textarea><div class="agent-post-foot"><span><b data-char-count>0</b> / 280 حرفًا</span><div><button type="button" class="agent-edit" data-edit><i class="fas fa-pen"></i> تعديل</button><button type="button" class="agent-approve" data-approve><i class="fas fa-check"></i> اعتماد</button></div></div></article>';
			}
			$dayPanels .= '<section class="agent-day-panel'.($dayIndex === 0 ? ' is-active' : '').'" data-day-panel="'.$dayIndex.'"><div class="agent-day-head"><div><span>خطة يوم '.$day[0].'</span><h2>'.$day[1].' مجدولة</h2></div><button type="button" data-approve-day><i class="fas fa-check-double"></i> اعتماد اليوم</button></div><div class="agent-posts">'.$posts.'</div></section>';
		}

		return '<section class="sadaqah-agent-page" aria-labelledby="agent-title"><div class="container agent-container">'
		.'<header class="agent-hero"><div><span class="agent-eyebrow">نسخة تجريبية — لا يوجد نشر فعلي</span><h1 id="agent-title">وكيل الصدقة الجارية</h1><p>خطة محتوى أسبوعية لحساب الصدقة الجارية، تراجعها وتعتمدها قبل ربط الحساب بمنصة X.</p></div><span class="agent-hero-icon"><i class="fas fa-feather-alt"></i></span></header>'
		.'<div class="agent-private-bar"><span><i class="fas fa-user-shield"></i> دخول خاص: '.htmlspecialchars(defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : '', ENT_QUOTES, 'UTF-8').'</span><div><a href="index.php"><i class="fas fa-external-link-alt"></i> عرض الموقع</a><a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></div></div>'
		.'<div class="agent-summary"><div><i class="fas fa-calendar-week"></i><span>هذا الأسبوع<strong>22 تغريدة</strong></span></div><div><i class="fas fa-clock"></i><span>المعدل اليومي<strong>3–4 تغريدات</strong></span></div><div><i class="fas fa-shield-alt"></i><span>حالة النشر<strong>معاينة فقط</strong></span></div><button type="button" data-reset-plan><i class="fas fa-redo-alt"></i> إعادة التجربة</button></div>'
		.'<nav class="agent-day-tabs" aria-label="أيام الأسبوع">'.$dayTabs.'</nav>'.$dayPanels
		.'<footer class="agent-safety"><i class="fas fa-lock"></i><p><strong>لن يُنشر أي شيء الآن</strong><span>الاعتماد في هذه اللوحة تجريبي ومحفوظ على جهازك فقط. ربط X والنشر التلقائي مرحلة مستقلة لاحقًا.</span></p></footer>'
		.'</div></section>';
	}

	public function home_page(){
		$this->hide_breadcrumb = false;
		$this->title = $this->siteName;
		$this->description = $this->siteDescription;
		$this->url = $this->siteurl;
		$this->container_class = 'container-section';

		$quran = '';
		if( $this->home_allow_quran ){
			$quran .= '<div class="section-1" id="quran">';
			$quran .= '<div class="container">';
			$quran .= '<div class="section-content">';
			$quran .= '<div class="section-title">';
			$quran .= '<h2>'.word('select_surah').'</h2>';
			$quran .= '</div>';
			$quran .= '<div class="section-text">';
			$quran .= $this->quran();
			$quran .= '</div>';
			$quran .= '</div>';
			$quran .= '</div>';
			$quran .= '</div>';
		}

		$tafseer = '';
		if( $this->home_allow_tafseer ){
			if( $this->tafseer_col == 1 ){
				$colClass = 'col-6 col-md-12';
			}elseif( $this->tafseer_col == 2 ){
				$colClass = 'col-6 col-md-6';
			}elseif( $this->tafseer_col == 3 ){
				$colClass = 'col-6 col-md-4';
			}elseif( $this->tafseer_col == 4 ){
				$colClass = 'col-6 col-md-3';
			}elseif( $this->tafseer_col == 6 ){
				$colClass = 'col-6 col-md-2';
			}else{
				$colClass = 'col-12 col-md-4';
			}

			$tafseer .= '<div class="section-2" id="tafseer">';
			$tafseer .= '<div class="container">';
			$tafseer .= '<div class="section-content">';
			$tafseer .= '<div class="section-title">';
			$tafseer .= '<h2>'.word('select_tafseer').'</h2>';
			$tafseer .= '</div>';
			$tafseer .= '<div class="section-text">';
			$json_tafseer = $this->api_tafseer();
			if( !isset($json_tafseer['error']) && isset($json_tafseer['data']) && count($json_tafseer['data']) > 0 ){
				$home_tafseer = '<div class="row">';
				foreach( $json_tafseer['data'] as $keys => $values ){
					$t_name = ( isset($values['name']) ? $values['name'] : '' );
					$t_name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );

					$tafseer_url = $this->url( array( 'action' => 'tafseer', 'type' => $keys ) );

					$home_tafseer .= '<div class="'.$colClass.'"><div class="spacer"><h5><a href="'.$tafseer_url.'">'.$t_name.'</a></h5></div></div>';
				}
				$home_tafseer .= '</div>';
			}else{
				$home_tafseer = ( isset($json_tafseer['error']) && !empty($json_tafseer['error']) ? $json_tafseer['error'] : 'Unknown error' );
			}
			$tafseer .= $home_tafseer;
			$tafseer .= '</div>';
			$tafseer .= '</div>';
			$tafseer .= '</div>';
			$tafseer .= '</div>';
		}

		$language = '';
		if( $this->home_allow_language ){
			if( $this->language_col == 1 ){
				$colClassLang = 'col-6 col-md-12';
			}elseif( $this->language_col == 2 ){
				$colClassLang = 'col-6 col-md-6';
			}elseif( $this->language_col == 3 ){
				$colClassLang = 'col-6 col-md-4';
			}elseif( $this->language_col == 4 ){
				$colClassLang = 'col-6 col-md-3';
			}elseif( $this->language_col == 6 ){
				$colClassLang = 'col-6 col-md-2';
			}else{
				$colClassLang = 'col-12 col-md-3';
			}

			$language .= '<div class="section-3" id="languages">';
			$language .= '<div class="container">';
			$language .= '<div class="section-content">';
			$language .= '<div class="section-title">';
			$language .= '<h2>'.word('select_language').'</h2>';
			$language .= '</div>';
			$language .= '<div class="section-text">';
			$json = $this->languages();
			if( !isset($json['error']) && isset($json['data']) && count($json['data']) > 0 ){
				$home_language = '<div class="row">';
				foreach( $json['data'] as $key => $value ){
					$id = ( isset($value['id']) ? $value['id'] : 0 );
					$name = ( isset($value['name']) ? $value['name'] : '' );
					$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
					$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
					$file = ( isset($value['file']) ? $value['file'] : '' );
					$book = ( isset($value['book']) ? $value['book'] : '' );
					$more = ( isset($value['more']) ? $value['more'] : '' );
					$source = ( isset($value['source']) ? $value['source'] : '' );
					$lang = ( isset($value['lang']) ? $value['lang'] : '' );
					$getkey = ( isset($value['key']) ? $value['key'] : '' );
					$flag = ( isset($value['flag']) ? $value['flag'] : '' );

					$get_flags = '<img class="flags" src="'.$flag.'" alt="'.$name.'">';

					$language_url = $this->url( array( 'action' => 'translate', 'l' => $getkey ) );

					if( $key != 'ar' ){
						$home_language .= '<div class="'.$colClassLang.'"><div class="spacer"><h5><a title="'.$name_en.' - '.$name_ar.'" href="'.$language_url.'">'.$get_flags.' '.$name.'</a></h5></div></div>';
					}

				}
				$home_language .= '</div>';
			}else{
				$home_language = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
			}
			$language .= $home_language;
			$language .= '</div>';
			$language .= '</div>';
			$language .= '</div>';
			$language .= '</div>';
		}

		$data = array('quran' => $quran, 'tafseer' => $tafseer, 'language' => $language);

		$code = '<div class="home-sections">';
		if( is_array($this->home_sort) && count($this->home_sort) > 0 ){
			foreach( $this->home_sort as $key => $value ){
				if( array_key_exists($value, $data) ){
					$code .= $data[$value];
				}
			}
		}else{
			$code .= $quran;
			$code .= $tafseer;
			$code .= $language;
		}
		$code .= '</div>';
		return $code;
	}

	public function quran(){
		$this->hide_breadcrumb = false;
		$json_surah = $this->surah_name();
		if( !isset($json['error']) && isset($json_surah['data']) && count($json_surah['data']) > 0 ){
			$language_id = ( isset($json_surah['language_id']) ? $json_surah['language_id'] : '' );
			$language_name = ( isset($json_surah['language_name']) ? $json_surah['language_name'] : '' );
			$language_name_ar = ( isset($json_surah['language_name_ar']) ? $json_surah['language_name_ar'] : '' );
			$language_name_en = ( isset($json_surah['language_name_en']) ? $json_surah['language_name_en'] : '' );
			$language_book = ( isset($json_surah['language_book']) ? $json_surah['language_book'] : '' );
			$language_sound = ( isset($json_surah['language_sound']) ? $json_surah['language_sound'] : '' );

			if( $this->quran_col == 1 ){
				$colClass = 'col-6 col-md-12';
			}elseif( $this->quran_col == 2 ){
				$colClass = 'col-6 col-md-6';
			}elseif( $this->quran_col == 3 ){
				$colClass = 'col-6 col-md-4';
			}elseif( $this->quran_col == 4 ){
				$colClass = 'col-6 col-md-3';
			}elseif( $this->quran_col == 6 ){
				$colClass = 'col-6 col-md-2';
			}else{
				$colClass = 'col-6 col-md-4';
			}

			$output = '<div class="row">';
			foreach( $json_surah['data'] as $keyx => $valuex ){
				$surah_number = ( isset($valuex['n']) ? $valuex['n'] : 0 );
				$surah_name = ( isset($valuex['name']) ? $valuex['name'] : '' );
				$surah_count = ( isset($valuex['ayat']) ? $valuex['ayat'] : 0 );

				$surah_url = $this->url( array( 'surah' => $surah_number ) );

				$output .= '<div class="'.$colClass.'">';
				$output .= '<div class="spacer">';
				$output .= '<h5><a title="'.word('surah').' '.$surah_name.' - '.word('aya_count').' '.$surah_count.'" href="'.$surah_url.'">'.$surah_number.'- '.$surah_name.'</a></h5>';
				$output .= '</div>';
				$output .= '</div>';
			}
			$output .= '</div>';
		}else{
			$this->cache_allow_create = false;
			$output = ( isset($json_surah['error']) && !empty($json_surah['error']) ? $json_surah['error'] : 'Unknown error' );
		}

		return $output;
	}

	public function convert_numbers_to_arabic($str){
		$western_arabic = array('0','1','2','3','4','5','6','7','8','9');
		$eastern_arabic = array('٠','١','٢','٣','٤','٥','٦','٧','٨','٩');
		$str = str_replace($western_arabic, $eastern_arabic, $str);
		return $str;
	}

	public function fix_char($str){
		$text = str_replace( array('۟'), array('ْ'), $str);
		return $text;
	}

	public function ayah_number( $n ){
		return '<span class="aya-number">﴿'.$this->convert_numbers_to_arabic($n).'﴾</span>';
	}

	public function home_view_surah(){
		$surah_id = ( isset($_REQUEST['surah']) && intval($_REQUEST['surah']) < 115 ? intval($_REQUEST['surah']) : 1 );
		$from = ( isset($_GET['f']) && intval($_GET['f']) != 0 ? intval($_GET['f']) : 1 );
		$to = ( isset($_GET['t']) && intval($_GET['t']) != 0 ? intval($_GET['t']) : 0 );
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : $this->default_reader );
		$row = ( isset($_GET['row']) ? intval($_GET['row']) : 0 );

		if($surah_id > 114){ $surah_id = 114; }

		if( $this->allw_readerform ){
			$readers_form = $this->home_readers_form($surah_id, 0, $reader_id, 1);
		}

		$audio_url = $this->home_check_surah($surah_id, $reader_id);
		$code = '';
		if( $this->allw_listen_surah ){
			$code .= '<div class="listensora">'.$this->audio_player($audio_url, 0).''.$readers_form.'</div>';
		}
		if( $this->allw_formchange_surah ){
			$code .= $this->home_form_change_ayah($surah_id, $from, $to);
		}

		$json = $this->api_surah_loop($surah_id, '', true);
		if( $json != false && isset($json['data']) && count($json['data']) > 0 ){
			$count = ( isset($json['count']) ? $json['count'] : 0 );
			$surah_id = ( isset($json['surah_id']) ? $json['surah_id'] : 0 );
			$surah_name = ( isset($json['surah_name']) ? $json['surah_name'] : '' );
			$surah_image = ( isset($json['surah_image']) ? $json['surah_image'] : '' );

			$this->title = word('surah').' '.$surah_name;
			$this->body_title = word('surah').' '.$surah_name;
			$this->description = word('surah').' '.$surah_name.' '.word('aya_count').' '.$count;
			$this->breadcrumb_parent = array( 'title' => word('quran'), 'url' => $this->url( array( 'action' => 'quran' ) ) );
			$this->image = $surah_image;
			$this->url = $this->url( array( 'l' => 'ar', 'surah' => $surah_id ) );

			$text = '';
			foreach( $json['data'] as $key => $value ){
				$ayah_number = ( isset($value['ayah_number']) ? $value['ayah_number'] : 0 );
				$ayah_text = ( isset($value['ayah_text']) ? $value['ayah_text'] : '' );
				$ayah_url = $this->url( array( 'action' => 'tafseer', 'type' => 5, 'surah' => $surah_id, 'ayah' => $key ) );

				if( $this->surah_one_line ){
					$text .= '<div class="mt-3"><a href="'.$ayah_url.'"><span class="ayat">'.$this->fix_char($ayah_text).'</span> '.$this->ayah_number( $key ).'</a></div>';
				}else{
					$text .= '<a href="'.$ayah_url.'"><span class="ayat">'.$this->fix_char($ayah_text).'</span> '.$this->ayah_number( $key ).'</a> ';
				}
			
			}

			if( $this->surah_one_line ){
				$div_class = 'surah-text-one-line';
			}else{
				$div_class = 'surah-text';
			}

			$code .= '<div class="'.$div_class.'">';
			$code .= $text;
			$code .= '</div>';
		}else{
			$code .= ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}

		return $code;
	}

	public function surah_loop() {
		$l = $this->get_language();
		$classname = ( $l == 'ar' ? 'col-12 col-sm-6 col-md-4' : 'col-12 col-sm-6 col-md-6' );

		$json = $this->surah_name( $l );

		if( $json != false && isset($json['data']) && count($json['data']) > 0 ){
			$language_id = ( isset($json['language_id']) ? $json['language_id'] : '' );
			$language_name = ( isset($json['language_name']) ? $json['language_name'] : '' );
			$language_name_ar = ( isset($json['language_name_ar']) ? $json['language_name_ar'] : '' );
			$language_name_en = ( isset($json['language_name_en']) ? $json['language_name_en'] : '' );
			$language_book = ( isset($json['language_book']) ? $json['language_book'] : '' );
			$language_sound = ( isset($json['language_sound']) ? $json['language_sound'] : '' );
			$language_flag = ( isset($json['language_flag']) ? $json['language_flag'] : '' );

			$this->title = sprintf( word('translation_in'), $language_name_en.' | '.$language_name_en.' | '.$language_name_ar );
			$this->body_title = sprintf( word('translation_in'), $language_name );
			$this->description = sprintf(word('translation_in'), $language_name.' | '.$language_name_en.' | '.$language_name_ar );
			$this->url = $this->url( array( 'action' => 'translate', 'l' => $l ) );
			$this->image = $language_flag;
			$this->breadcrumb_parent = array(
				array('title' => word('languages'), 'url' => $this->url( array( 'action' => 'languages' ) ))
			);

			$code = '';
			if( !empty($language_book) ){
				$code .= '<div class="quran-file"><a title="'.$language_name.' - '.$language_name_en.' - '.$language_name_ar.'" href="'.$language_book.'"><i class="fas fa-file-pdf"></i> '.word('book_download').' '.sprintf( word('translation_in'), $language_name ).'</a></div>';
			}

			$code .= $this->post_share($language_name.' | '.$this->siteName, $this->url( array( 'action' => 'translate', 'l' => $l ) ));
			$code .= '<div class="row translation-surah-grid">';
			foreach( $json['data'] as $key => $value ){
				$surah_number = ( isset($value['n']) ? $value['n'] : 0 );
				$surah_name = ( isset($value['name']) ? $value['name'] : '' );
				$surah_count = ( isset($value['ayat']) ? $value['ayat'] : 0 );

				$surah_url = ( $l == 'ar' ? $this->url( array( 'surah' => $surah_number ) ) : $this->url( array( 'action' => 'translate', 'l' => $l, 'surah' => $surah_number ) ) );

				$code .= '<div class="'.$classname.'">';
				$code .= '<div class="spacer">';
				$code .= '<h5><a title="'.word('surah').' '.$surah_name.' - '.word('aya_count').' '.$surah_count.'" href="'.$surah_url.'">'.$surah_number.'- '.$surah_name.'</a></h5>';
				$code .= '</div>';
				$code .= '</div>';
			}
			$code .= '</div>';
		}else{
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}

		return $code;
	}

	public function translate_view() {
		$lang = $this->get_language();
		$surah_id = ( isset($_GET['surah']) && intval($_GET['surah']) != 0 && intval($_GET['surah']) < 115 ? intval($_GET['surah']) : 1 );
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : $this->default_reader );
		$add_more_trans = '';
		if( $lang == 'en' || $lang == 'en_yusuf_ali' ){
			//$add_more_trans .= ',en_transliteration';
		}

		$sound_folder = $this->sound_folder_aya( $this->default_reader_aya );
		$json = $this->api_surah_loop($surah_id, $lang, true);

		if( $json != false && isset($json['data']) && count($json['data']) > 0 ){
			$count = ( isset($json['count']) ? $json['count'] : 0 );
			$surah_id = ( isset($json['surah_id']) ? $json['surah_id'] : 0 );
			$surah_name = ( isset($json['surah_name']) ? $json['surah_name'] : '' );
			$surah_image = ( isset($json['surah_image']) ? $json['surah_image'] : '' );
			$language_name = ( isset($json[$lang]['language_name']) ? $json[$lang]['language_name'] : '' );
			$language_name_ar = ( isset($json[$lang]['language_name_ar']) ? $json[$lang]['language_name_ar'] : '' );
			$language_name_en = ( isset($json[$lang]['language_name_en']) ? $json[$lang]['language_name_en'] : '' );
			$language_book = ( isset($json[$lang]['language_book']) ? $json[$lang]['language_book'] : '' );
			$language_sound = ( isset($json[$lang]['language_sound']) ? $json[$lang]['language_sound'] : '' );
			$language_found_files = ( isset($json[$lang]['language_found_files']) ? $json[$lang]['language_found_files'] : '' );

			if( empty($language_name) ){
				$language_name = ( isset($json['language_name']) ? $json['language_name'] : '' );
			}
			if( empty($language_name_ar) ){
				$language_name_ar = ( isset($json['language_name_ar']) ? $json['language_name_ar'] : '' );
			}
			if( empty($language_book) ){
				$language_book = ( isset($json['language_name_en']) ? $json['language_name_en'] : '' );
			}
			if( empty($language_name_en) ){
				$language_name_en = ( isset($json['language_book']) ? $json['language_book'] : '' );
			}
			if( empty($language_sound) ){
				$language_sound = ( isset($json['language_sound']) ? $json['language_sound'] : '' );
			}
			if( empty($language_found_files) ){
				$language_found_files = ( isset($json['language_found_files']) ? $json['language_found_files'] : '' );
			}

			if( empty($language_name) ){
				$this->cache_allow_create = false;
				return '<div class="alert alert-danger mt-3 mb-3" role="alert">'.word('no_data').'</div>';
			}

			$reader_menu = '';
			if( $this->allw_readerform ){
				if( empty($language_sound) ){
					$audio_url = $this->home_check_surah($surah_id, $reader_id);
				}else{
					$this->language_sound = true;
					$audio_url = $this->home_check_surah($surah_id, $reader_id, $language_sound);
				}
				$reader_menu .= $this->home_readers_form($surah_id, $lang, $reader_id);
			}

			$player = '<div class="row">';
			$player .= '<div class="col-12">';
			$player .= $this->audio_player($audio_url, 0);
			$player .= '</div>';
			$player .= '</div>';

			if( is_array($language_found_files) && in_array($surah_id, $language_found_files) ){
				$audio_play = $player;
			}else{
				if( !empty($language_sound) && empty($language_found_files) ){
					$audio_play = $player;
				}else{
					$audio_play = '';
				}
			}

			$reader_name = '';
			if( isset($_GET['reader_id']) ){
				$reader_name .= ' | '.$this->reader_name;
			}
			$this->title = word('surah').' '.$surah_name.' | '.$language_name;
			$this->body_title = word('surah').' '.$surah_name;
			$this->description = $surah_name;
			$this->url = $this->url( array( 'action' => 'translate', 'l' => $lang, 'surah' => $surah_id ) );
			$this->image = $surah_image;
			$this->breadcrumb_parent = array(
				array('title' => word('languages'), 'url' => $this->url( array( 'action' => 'languages' ) )),
				array('title' => $language_name, 'url' => $this->url( array( 'action' => 'translate', 'l' => $lang ) ))
			);

			$this->headercode .= '<link rel="stylesheet" type="text/css" href="'.$this->get_theme_folder_url().'/css/mp3-player-button.css">';
			$this->headercode .= '<script type="text/javascript" src="'.$this->get_theme_folder_url().'/js/soundmanager2-nodebug-jsmin.js"></script>';
			$this->headercode .= '<script type="text/javascript" src="'.$this->get_theme_folder_url().'/js/mp3-player-button.js"></script>';

			$share_title = $language_name.' - '.word('surah').' '.$surah_name;

			$surah_list = '';
			$json_all_surah = $this->surah_name( $lang );
			if( $json_all_surah != false && isset($json_all_surah['data']) && count($json_all_surah['data']) > 0 ){
				$surah_list .= '<div class="surah-list">';
				$surah_list .= '<label for="surah_list"><strong>'.word('select_surah').'</strong></label>';
				$surah_list .= '<select class="form-control" name="forma" data-qfa-navigate id="surah_list">';
				$surah_list .= '<option value="#">'.word('select_surah').'</option>';
				foreach( $json_all_surah['data'] as $keys => $values ){
					$n = ( isset($values['n']) ? $values['n'] : 0 );
					$name = ( isset($values['name']) ? $values['name'] : '' );
					$ayat = ( isset($values['ayat']) ? $values['ayat'] : '' );
					$name_en = ( isset($values['name_en']) ? $values['name_en'] : '' );

					$selected = ( $surah_id == $n ? ' selected' : '' );

					$surah_url = ( $lang == 'ar' ? $this->url( array( 'surah' => $n ) ) : $this->url( array( 'action' => 'translate', 'l' => $lang, 'surah' => $n ) ) );

					$surah_list .= '<option value="'.$surah_url.'" title="'.$name_en.'"'.$selected.'>'.$n.' '.$name.' ['.$ayat.']</option>';
				}
				$surah_list .= '</select>';
				$surah_list .= '</div>';
			}

			$languages_list = '';
			$json_languages = $this->languages();
			if( isset($json_languages['data']) && count($json_languages['data']) > 0 ){
				$languages_list .= '<div class="languages-list">';
				$languages_list .= '<label for="languages_list"><strong>'.word('select_language').'</strong></label>';
				$languages_list .= '<select class="form-control" name="forml" data-qfa-navigate id="languages_list">';
				$languages_list .= '<option value="#">'.word('select_language').'</option>';
				foreach( $json_languages['data'] as $keyl => $valuel ){
					$langs_id = ( isset($valuel['id']) ? $valuel['id'] : 0 );
					$langs_name = ( isset($valuel['name']) ? $valuel['name'] : '' );
					$langs_name_ar = ( isset($valuel['name_ar']) ? $valuel['name_ar'] : '' );
					$langs_name_en = ( isset($valuel['name_en']) ? $valuel['name_en'] : '' );
					$langs_file = ( isset($valuel['file']) ? $valuel['file'] : '' );
					$langs_book = ( isset($valuel['book']) ? $valuel['book'] : '' );
					$langs_source = ( isset($valuel['source']) ? $valuel['source'] : '' );
					$langs_lang = ( isset($valuel['lang']) ? $valuel['lang'] : '' );
					$langs_flag = ( isset($valuel['flag']) ? $valuel['flag'] : '' );
					$langs_key = ( isset($valuel['key']) ? $valuel['key'] : '' );
					$langs_book_api = ( isset($valuel['book_api']) ? $valuel['book_api'] : '' );

					$selected = ( $lang == $langs_key ? ' selected' : '' );

					$langs_url = ( $langs_key == 'ar' ? $this->url( array( 'surah' => $surah_id ) ) : $this->url( array( 'action' => 'translate', 'l' => $langs_key, 'surah' => $surah_id ) ) );

					$languages_list .= '<option title="'.$langs_name_en.' - '.$langs_name.' - '.$langs_name_ar.'" value="'.$langs_url.'"'.$selected.'>'.$langs_name.'</option>';
				}
				$languages_list .= '</select>';
				$languages_list .= '</div>';
			}

			if( !empty($surah_list) || !empty($languages_list) || !empty($reader_menu) ){
				if( empty($surah_list) && empty($reader_menu) ){
					$get_list = $languages_list;
				}elseif( empty($surah_list) && empty($languages_list) ){
					$get_list = $reader_menu;
				}else{
					if( empty($language_sound) ){
						$get_list = '<div class="row mb-3">';
						$get_list .= '<div class="col-12 col-md-6">';
						$get_list .= $surah_list;
						$get_list .= '</div>';
						$get_list .= '<div class="col-12 col-md-3">';
						$get_list .= $languages_list;
						$get_list .= '</div>';
						$get_list .= '<div class="col-12 col-md-3">';
						$get_list .= $reader_menu;
						$get_list .= '</div>';
						$get_list .= '</div>';
					}else{
						$get_list = '<div class="row mb-3">';
						$get_list .= '<div class="col-12 col-md-6">';
						$get_list .= $surah_list;
						$get_list .= '</div>';
						$get_list .= '<div class="col-12 col-md-6">';
						$get_list .= $languages_list;
						$get_list .= '</div>';
						$get_list .= '</div>';
					}
				}
			}else{
				$get_list = '';
			}

			$code = '';
			$code .= $get_list;
			$code .= $audio_play;
			$code .= '<div id="translateindex">';
			$code .= '<h1>'.$language_name.'</h1>';
			$code .= '<div class="englishtext">'.word('surah').' '.$surah_name.' - '.word('aya_count').' '.$count.'</div>';
			$code .= $this->post_share($share_title, $this->url( array( 'action' => 'translate', 'l' => $lang, 'surah' => $surah_id ) ));

			$n = 0;
			foreach( $json['data'] as $key => $value ){
				$ayah_number = ( isset($value['ayah_number']) ? $value['ayah_number'] : 0 );
				$ayah_text = ( isset($value['ayah_text']) ? $value['ayah_text'] : '' );

				$trans_ayah_trans = ( isset($json['translate'][$lang][$key]) ? $json['translate'][$lang][$key] : '' );

				$trans_ayah_trans_en_transliteration = ( isset($json['translate']['en_transliteration'][$key]) ? $json['translate']['en_transliteration'][$key] : '' );
				++$n;

				if($n==1){ $classname='ayat shadow p-3 mb-5 bg-white border border-light'; }else{ $classname='ayat2 shadow p-3 mb-5 bg-light border-light'; $n=0; }
				$audio_file = $this->sound_check_aya($surah_id, $ayah_number, $sound_folder);
				$listen = ''; //$this->audio_player($audio_file, 0);

				$button = '<div class="listen_aya hvr-bounce-in"><a href="'.$audio_file.'" class="sm2_button" title="Play &quot;coins&quot;"><i class="fa fa-play"></i></a></div>';

				$code .= '<div class="'.$classname.'"><p class="hvr-grow">'.$this->fix_char($ayah_text).' '.$this->ayah_number($ayah_number).'</p> '.$button;
				$code .= '<div class="translate"><p class="hvr-wobble-horizontal">'.$trans_ayah_trans.'</p></div>';
				if( $lang == 'en' ){
					if( !empty($trans_ayah_trans_en_transliteration) ){
						$code .= '<div class="translate border border-info bg-white p-3 rounded-lg"><p class="hvr-grow mb-0">'.$trans_ayah_trans_en_transliteration.'</p></div>';
					}
				}
				$code .= '</div>';
			}
			$code .= "</div>";
		}else{
			$this->cache_allow_create = false;
			$code = ( isset($json['error']) && !empty($json['error']) ? $json['error'] : 'Unknown error' );
		}


		return $code;
	}

	public function book_template( $books = array() ){
		if( is_array($books['books']) && count($books['books']) > 0 ){
			if( isset($_GET['type']) ){
				$type = intval($_GET['type']);
			}else{
				$type = ( isset($books['type']) ? $books['type'] : 0 );
			}

			$books_count = ( isset($books['count']) ? $books['count'] : 0 );

			$books_ar = array();
			foreach( $books['books'] as $key => $value ){
				$book_id = ( isset($value['id']) ? $value['id'] : '' );
				$book_title = ( isset($value['title']) ? $value['title'] : '' );
				$book_excerpt = ( isset($value['excerpt']) ? $value['excerpt'] : '' );
				$book_source_url = ( isset($value['source']) ? $value['source'] : '' );
				$book_url = ( isset($value['url']) ? $value['url'] : '' );
				$book_image = ( isset($value['image']) ? $value['image'] : '' );
				$book_author_id = ( isset($value['author_id']) ? $value['author_id'] : '' );
				$book_author_name = ( isset($value['author_name']) ? $value['author_name'] : '' );
				$book_author_url = ( isset($value['author_url']) ? $value['author_url'] : '' );
				$book_download = ( isset($value['file']) ? $value['file'] : '' );
				$book_publisher = ( isset($value['publisher']) ? $value['publisher'] : '' );
				$book_translator = ( isset($value['translator']) ? $value['translator'] : '' );
				$book_language = ( isset($value['language']) ? $value['language'] : '' );
				$alt = ( isset($value['alt']) ? $value['alt'] : '' );
				$read = ( isset($value['read']) ? $value['read'] : '' );
				$download = ( isset($value['download']) ? $value['download'] : '' );

				if( $type == 1 ){
					$code = '<div class="col-12 col-md-6">';

					$code .= '<div class="card mb-4">';
					$code .= '<div class="row no-gutters">';
					$code .= '<div class="col-12 col-md-3">';
					$code .= '<div class="book-cover-frame"><a class="book-cover-link" href="'.$book_url.'" aria-label="'.htmlentities($alt).'" title="'.htmlentities($alt).'" style="--book-cover:url(&quot;'.htmlspecialchars($this->bookCoverUrl($book_image), ENT_QUOTES, 'UTF-8').'&quot;);"></a></div>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-9">';
					$code .= '<div class="card-body">';
					$code .= '<h2 class="card-title"><a href="'.$book_url.'">'.$book_title.'</a></h2>';
					if( $book_title != $book_excerpt ){
						$code .= '<p class="card-text">'.$book_excerpt.'</p>';
					}
					if( !empty($book_author_name) ){
						$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> <a target="_blank" href="'.$book_author_url.'">'.$book_author_name.'</a></small></div>';
					}
					if( !empty($book_publisher) ){
						$code .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted hvr-forward"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
					}
					if( !empty($book_translator) ){
						$code .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted hvr-forward"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
					}
					$code .= '<div class="card-text" title="'.word('book_read').'"><small class="text-muted hvr-forward"><i class="fab fa-readme"></i> <a target="_blank" href="'.$read.'">'.word('book_read').'</a></small></div>';
					$code .= '<div class="card-text" title="'.word('book_download').'"><small class="text-muted hvr-forward"><i class="fas fa-download"></i> <a target="_blank" href="'.$download.'">'.word('book_download').'</a></small></div>';
					$code .= '<div class="card-text" title="'.word('book_source').'"><small class="text-muted hvr-forward"><i class="fas fa-link"></i> <a target="_blank" href="'.$book_source_url.'">'.word('book_source').'</a></small></div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '</div>';
				}elseif( $type == 2 ){
					$code = '<div class="col-12 col-md-6">';

					$code .= '<div class="card mb-4">';
					$code .= '<div class="row no-gutters">';
					$code .= '<div class="col-12 col-md-3">';
					$code .= '<div class="book-cover-frame"><a class="book-cover-link" href="'.$book_url.'" aria-label="'.htmlentities($alt).'" title="'.htmlentities($alt).'" style="--book-cover:url(&quot;'.htmlspecialchars($this->bookCoverUrl($book_image), ENT_QUOTES, 'UTF-8').'&quot;);"></a></div>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-9">';
					$code .= '<div class="card-body">';
					$code .= '<h2 class="card-title"><a href="'.$book_url.'">'.$book_title.'</a></h2>';
					if( $book_title != $book_excerpt ){
						$code .= '<p class="card-text">'.$book_excerpt.'</p>';
					}
					if( !empty($book_author_name) ){
						$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> <a target="_blank" href="'.$book_author_url.'">'.$book_author_name.'</a></small></div>';
					}
					if( !empty($book_publisher) ){
						$code .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted hvr-forward"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
					}
					if( !empty($book_translator) ){
						$code .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted hvr-forward"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
					}

					$code .= '<div class="row mt-3">';
					$code .= '<div class="col-4 col-md-4 hvr-wobble-top"><a target="_blank" href="'.$read.'" class="btn btn-secondary btn-lg btn-block" title="'.word('book_read').'"><i class="fab fa-readme"></i></a></div>';
					$code .= '<div class="col-4 col-md-4 hvr-float-shadow"><a target="_blank" href="'.$download.'" class="btn btn-secondary btn-lg btn-block" title="'.word('book_download').'"><i class="fas fa-download"></i></a></div>';
					$code .= '<div class="col-4 col-md-4 hvr-grow-rotate"><a target="_blank" href="'.$book_source_url.'" class="btn btn-secondary btn-lg btn-block" title="'.word('book_source').'"><i class="fas fa-link"></i></a></div>';
					$code .= '</div>';

					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '</div>';
				}elseif( $type == 3 ){
					$code = '<div class="col-12 col-md-2">';
					$code .= '<div class="card mb-4">';
					$code .= '<div class="book-image">';
					$code .= '<div class="book-cover-frame"><a class="book-cover-link" href="'.$book_url.'" aria-label="'.htmlentities($alt).'" title="'.htmlentities($alt).'" style="--book-cover:url(&quot;'.htmlspecialchars($this->bookCoverUrl($book_image), ENT_QUOTES, 'UTF-8').'&quot;);"></a></div>';
					$code .= '<div class="overlay">';
					$code .= '<div class="overlay-text">';
					$code .= '<h2 class="card-title text-center mb-3"><a title="'.htmlentities($book_excerpt).'" href="'.$book_url.'">'.$book_title.'</a></h2>';
					if( $book_title != $book_excerpt ){
						//$code .= '<p class="card-text">'.$book_excerpt.'</p>';
					}
					if( !empty($book_author_name) ){
						$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> <a target="_blank" href="'.$book_author_url.'">'.$book_author_name.'</a></small></div>';
					}
					if( !empty($book_publisher) ){
						$code .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted hvr-forward"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
					}
					if( !empty($book_translator) ){
						$code .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted hvr-forward"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
					}
					$code .= '<div class="row mt-3">';
					$code .= '<div class="col-4 text-center" title="'.word('book_read').'"><small class="text-muted hvr-forward"><a target="_blank" href="'.$read.'"><i class="fab fa-readme"></i></a></small></div>';
					$code .= '<div class="col-4 text-center" title="'.word('book_download').'"><small class="text-muted hvr-forward"><a target="_blank" href="'.$download.'"><i class="fas fa-download"></i></a></small></div>';
					$code .= '<div class="col-4 text-center" title="'.word('book_source').'"><small class="text-muted hvr-forward"><a target="_blank" href="'.$book_source_url.'"><i class="fas fa-link"></i></a></small></div>';
					$code .= '</div>';

					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
					//$code .= '<div class="card-body">';
					//$code .= '<h2 class="card-title"><a title="'.htmlentities($book_excerpt).'" href="'.$book_url.'">'.$book_title.'</a></h2>';
					//$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
				}elseif( $type == 4 ){
					$code = '<div class="col-12 col-md-6">';

					$code .= '<div class="card mb-4">';
					$code .= '<div class="row no-gutters">';
					$code .= '<div class="col-12 col-md-3">';
					$code .= '<div class="book-cover-frame"><a class="book-cover-link" href="'.$book_url.'" aria-label="'.htmlentities($alt).'" title="'.htmlentities($alt).'" style="--book-cover:url(&quot;'.htmlspecialchars($this->bookCoverUrl($book_image), ENT_QUOTES, 'UTF-8').'&quot;);"></a></div>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-9">';
					$code .= '<div class="card-body">';
					$code .= '<h2 class="card-title"><a href="'.$book_url.'">'.$book_title.'</a></h2>';
					if( $book_title != $book_excerpt ){
						$code .= '<p class="card-text">'.$book_excerpt.'</p>';
					}
					if( !empty($book_author_name) ){
						//$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> <a target="_blank" href="'.$book_author_url.'">'.$book_author_name.'</a></small></div>';
						$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> '.$book_author_name.'</small></div>';
					}
					if( !empty($book_publisher) ){
						$code .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted hvr-forward"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
					}
					if( !empty($book_translator) ){
						$code .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted hvr-forward"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
					}
					//$code .= '<div class="card-text" title="'.word('book_read').'"><small class="text-muted hvr-forward"><i class="fab fa-readme"></i> <a target="_blank" href="'.$read.'">'.word('book_read').'</a></small></div>';
					//$code .= '<div class="card-text" title="'.word('book_download').'"><small class="text-muted hvr-forward"><i class="fas fa-download"></i> <a target="_blank" href="'.$download.'">'.word('book_download').'</a></small></div>';
					//$code .= '<div class="card-text" title="'.word('book_source').'"><small class="text-muted hvr-forward"><i class="fas fa-link"></i> <a target="_blank" href="'.$book_source_url.'">'.word('book_source').'</a></small></div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '</div>';
				}else{
					$code = '<div class="col-12 col-md-12">';

					$code .= '<div class="card mb-4">';
					$code .= '<div class="row no-gutters">';
					$code .= '<div class="col-12 col-md-2">';
					$code .= '<div class="book-cover-frame"><a class="book-cover-link" href="'.$book_url.'" aria-label="'.htmlentities($alt).'" title="'.htmlentities($alt).'" style="--book-cover:url(&quot;'.htmlspecialchars($this->bookCoverUrl($book_image), ENT_QUOTES, 'UTF-8').'&quot;);"></a></div>';
					$code .= '</div>';
					$code .= '<div class="col-12 col-md-10">';
					$code .= '<div class="card-body">';
					$code .= '<h2 class="card-title"><a href="'.$book_url.'">'.$book_title.'</a></h2>';
					if( $book_title != $book_excerpt ){
						$code .= '<p class="card-text">'.$book_excerpt.'</p>';
					}
					if( !empty($book_author_name) ){
						$code .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted hvr-forward"><i class="fas fa-user-edit"></i> <a target="_blank" href="'.$book_author_url.'">'.$book_author_name.'</a></small></div>';
					}
					if( !empty($book_publisher) ){
						$code .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted hvr-forward"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
					}
					if( !empty($book_translator) ){
						$code .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted hvr-forward"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
					}
					$code .= '<div class="card-text" title="'.word('book_read').'"><small class="text-muted hvr-forward"><i class="fab fa-readme"></i> <a target="_blank" href="'.$read.'">'.word('book_read').'</a></small></div>';
					$code .= '<div class="card-text" title="'.word('book_download').'"><small class="text-muted hvr-forward"><i class="fas fa-download"></i> <a target="_blank" href="'.$download.'">'.word('book_download').'</a></small></div>';
					$code .= '<div class="card-text" title="'.word('book_source').'"><small class="text-muted hvr-forward"><i class="fas fa-link"></i> <a target="_blank" href="'.$book_source_url.'">'.word('book_source').'</a></small></div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';
					$code .= '</div>';

					$code .= '</div>';
				}

				$books_ar[] = $code;
			}

			if( $type == 1 || $type == 2 || $type == 3 || $type == 4 ){
				$output = '<div class="books books-type-'.$type.'">';
				$output .= '<div class="row">';
				foreach( $books_ar as $key2 => $value2 ) {
					$output .= $value2;
				}
				$output .= '</div>';
				$output .= '</div>';
			}else{
				$output = '<div class="books books-0">';
				$output .= '<div class="row">';
				foreach( $books_ar as $key2 => $value2 ) {
					$output .= $value2;
				}
				$output .= '</div>';
				$output .= '</div>';
			}
		}else{
			$output = '<div class="alert alert-danger mt-3 mb-3" role="alert">Not Array</div>';
		}

		return $output;
	}

	public function books_languages(){
		$this->title = 'الكتب';
		$this->description = 'Browse books with over 10,000 books';

		$ml = new MUSLIM_LIBRARY( true );
		$json = $ml->languages();

		if( $json != false && isset($json['data']) && count($json['data']) > 0 ){
			$data = '<div class="books-languages">';
			$data .= '<div class="row">';
			foreach( $json['data'] as $key => $value ){
				$title = ( isset($value['title']) ? $value['title'] : '' );
				$lang = ( isset($value['key']) ? $value['key'] : '' );
				$url = $this->url( array( 'action' => 'books_language', 'name' => $lang ) );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );
				$books_count = ( isset($value['books_count']) ? $value['books_count'] : 0 );

				$categories = '';
				$categories_button = '';
				if( isset($value['categories']) && is_array($value['categories']) ){
					$categories_button .= '<a href="#category'.$key.'" class="cat" data-bs-toggle="modal" data-bs-target="#category'.$key.'"><i class="far fa-folder-open"></i> '.word('book_categories').'</a>';
					$categories .= '<div class="modal fade" id="category'.$key.'" tabindex="-1" role="dialog" aria-labelledby="category'.$key.'Label" aria-hidden="true">';
					$categories .= '<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">';
					$categories .= '<div class="modal-content">';
					$categories .= '<div class="modal-header">';
					$categories .= '<h5 class="modal-title" id="category'.$key.'Label"><img src="'.$flag.'" alt="'.$title.'" class="mw-100"> '.$title.' categories</h5>';
					$categories .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
					$categories .= '</div>';
					$categories .= '<div class="modal-body">';
					$categories .= '<table class="table table-striped table-hover">';
					$categories .= '<thead class="thead-dark">';
					$categories .= '<tr>';
					$categories .= '<th scope="col">#</th>';
					$categories .= '<th scope="col">'.word('title').'</th>';
					$categories .= '<th scope="col">'.word('books').'</th>';
					$categories .= '</tr>';
					$categories .= '</thead>';
					$categories .= '<tbody>';
					$i=0;
					foreach( $value['categories'] as $key2 => $value2 ){
						$category_id = ( isset($value2['id']) ? $value2['id'] : '' );
						$category_name = ( isset($value2['name']) ? $value2['name'] : '' );
						$category_url = $this->siteurl.'/index.php?action=books_category&category_id='.$category_id;
						$category_count = ( isset($value2['books_count']) ? $value2['books_count'] : 0 );
						++$i;

						$categories .= '<tr>';
						$categories .= '<th scope="row">'.$i.'</th>';
						$categories .= '<td class="categories"><a href="'.$category_url.'">'.$category_name.'<a/></td>';
						$categories .= '<td>'.$category_count.'</td>';
						$categories .= '</tr>';
					}
					$categories .= '</tbody>';
					$categories .= '</table>';
					$categories .= '</div>';
					$categories .= '<div class="modal-footer">';
					$categories .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
					$categories .= '</div>';
					$categories .= '</div>';
					$categories .= '</div>';
					$categories .= '</div>';
				}

				$data .= '<div class="col-12 col-sm-4 col-md-4 col-lg-3 language-loop-bg">';
				$data .= '<div class="language-loop">';
				$data .= '<div class="language-flag hvr-bounce-in"><a href="'.$url.'"><img src="'.$flag.'" alt="'.$title.' books" title="'.$title.' books" class="mw-100"></a></div>';
				$data .= '<h1><a href="'.$url.'" title="'.$title.' books">'.$title.'</a></h1>';
				$data .= '<div class="language-count"><span><i class="far fa-file-pdf"></i> '.$books_count.'</span> '.$categories_button.'</div>';
				$data .= '</div>';
				$data .= $categories;
				$data .= '</div>';
			}
			$data .= '</div>';
			$data .= '</div>';
		}else{
			$error = ( isset($json['error']) ? $json['error'] : ( isset($json['msg']) ? $json['msg'] : word('no_data') ) );
			$data = '<div class="alert alert-danger mt-3 mb-3" role="alert">'.$error.'</div>';
		}
		return $data;
	}

	public function books_language(){
		$language_name = ( isset($_GET['name']) ? strip_tags(ucfirst($_GET['name'])) : '' );
		$ml = new MUSLIM_LIBRARY( true );
		$json = $ml->language( $language_name );

		if( $json != false && !isset($json['error']) ){
			$data = '<div class="languages">';

			$id = ( isset($json['id']) ? $json['id'] : 0 );
			$title = ( isset($json['title']) ? $json['title'] : '' );
			$url = ( isset($json['url']) ? $json['url'] : '' );
			$flag = ( isset($json['flag']) ? $json['flag'] : '' );
			$locale = ( isset($json['locale']) ? $json['locale'] : '' );
			$books_count = ( isset($json['books_count']) ? $json['books_count'] : 0 );
			$books_data = ( isset($json['books_data']) ? $json['books_data'] : '' );

			$this->book_parent = $language_name;
			$this->is_rtl( $language_name );

			$limit = 30;
			$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		  $page = ($page == 0 ? 1 : $page);
		  $perpage = $limit;
		  $startpoint = ($page * $perpage) - $perpage;
			$start = ($startpoint + $limit);

			$lastpage = ceil($books_count / $perpage);

			if( empty($title) || $page > $lastpage ){
				$this->cache_allow_create = false;
				return '<div class="alert alert-danger mt-3 mb-3" role="alert">'.word('no_data').'</div>';
			}

			$this->title = ($language_name === 'Arabic' ? 'كتب باللغة العربية' : 'كتب باللغة '.$title);
			$this->description = ($language_name === 'Arabic' ? 'كتب باللغة العربية' : 'كتب باللغة '.$title).' - عدد الكتب: '.$books_count;
			$this->url = $this->url( array( 'action' => 'books_language', 'name' => $language_name ) );
			$this->image = $flag;

			$this->breadcrumb_parent = array(
				array('title' => 'الكتب', 'url' => $this->url( array( 'action' => 'books' ) ) )
			);

			$books = '';

			$books_arr = array();
			$books_text = '';
			if( is_array($books_data) ){
				for( $x=$startpoint; $x < $start; ++$x ){
					$valueb = ( isset($books_data[$x]) ? $books_data[$x] : '' );
					if( !empty($valueb) ){
						$books_arr[] = $valueb;
						$books_text .= $valueb.',';
					}
				}

				$info = array();
				$info['type'] = 1;
				$json_books = $ml->books( rtrim($books_text, ',') );
				foreach ($json_books['data'] as $key3 => $value3) {
					$book_title = ( isset($value3['title']) ? $value3['title'] : '' );
					$book_id = ( isset($value3['id']) ? $value3['id'] : '' );
					$book_excerpt = ( isset($value3['excerpt']) ? $value3['excerpt'] : '' );
					$book_source_url = ( isset($value3['url']) ? $value3['url'].'?lang='.$language_name : '' );
					$book_url = $this->url( array( 'action' => 'book', 'book_id' => $book_id ) );
					$book_image = ( isset($value3['image']) ? $value3['image'] : '' );
					$book_author_id = ( isset($value3['author_id']) ? $value3['author_id'] : '' );
					$book_author_name = ( isset($value3['author_name']) ? $value3['author_name'] : '' );
					$book_download = ( isset($value3['book_url']) ? $value3['book_url'] : '' );
					$book_publisher = ( isset($value3['publisher']) ? $value3['publisher'] : '' );
					$book_translator = ( isset($value3['translator']) ? $value3['translator'] : '' );

					$alt = $book_title;
					if( $book_title != $book_excerpt ){
						$alt .= "\n";
						$alt .= $book_excerpt;
					}
					if( !empty($book_author_name) ){
						$alt .= "\n";
						$alt .= $book_author_name;
					}

					$info['books'][] = array(
						'id' => $book_id,
						'title' => $book_title,
						'excerpt' => $book_excerpt,
						'source' => $book_source_url,
						'url' => $book_url,
						'image' => $book_image,
						'alt' => $alt,
						'author_id' => $book_author_id,
						'author_name' => $book_author_name,
						'author_url' => 'https://www.muslim-library.com/books-author/?author_id='.$book_author_id.'&lang='.$language_name,
						'file' => $book_download,
						'publisher' => $book_publisher,
						'translator' => $book_translator,
						'language' => $language_name,
						'read' => $book_source_url.'&read_id='.$book_id,
						'download' => $book_source_url.'&download_id='.$book_id
					);

				}

				$pagination_url = $this->url( array( 'action' => 'books_language', 'name' => $language_name, 'page' => $page ) );
				$pagination = pagination( $books_count, $perpage, $page, $pagination_url );

				$books = $pagination;
				$books .= $this->book_template( $info );
				$books .= $pagination;
			}

			if( isset($json['categories']['status']) && $json['categories']['status'] == 'error' ){
				$categories = '';
				$categories_button = '';
				$categories_count = '';
			}else{
				$categories = '';
				$categories_button = '';
				$categories_count = '';

				if( isset($json['categories']) && is_array($json['categories']) && count($json['categories']) > 0 ){
					$categories_count = '<span class="cat">'.count($json['categories']).'</span>';
					$categories_button .= '<a href="#category'.$id.'" class="cat" data-bs-toggle="modal" data-bs-target="#category'.$id.'"><i class="far fa-folder-open"></i> '.word('book_categories').'</a>';
					$categories .= '<div class="modal fade" id="category'.$id.'" tabindex="-1" role="dialog" aria-labelledby="category'.$id.'Label" aria-hidden="true">';
					$categories .= '<div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">';
					$categories .= '<div class="modal-content">';
					$categories .= '<div class="modal-header">';
					$categories .= '<h5 class="modal-title" id="category'.$id.'Label"><img src="'.$flag.'" alt="'.$title.'" class="mw-100"> '.$title.'</h5>';
					$categories .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';

					$categories .= '</div>';
					$categories .= '<div class="modal-body">';
					$categories .= '<table class="table table-striped table-hover">';
					$categories .= '<thead class="thead-dark">';
					$categories .= '<tr>';
					$categories .= '<th scope="col">#</th>';
					$categories .= '<th scope="col">'.word('title').'</th>';
					$categories .= '<th scope="col">'.word('books').'</th>';
					$categories .= '</tr>';
					$categories .= '</thead>';
					$categories .= '<tbody>';
					$i=0;
					foreach( $json['categories'] as $key2 => $value2 ){
						$category_id = ( isset($value2['id']) ? $value2['id'] : '' );
						$category_name = ( isset($value2['name']) ? $value2['name'] : '' );
						$category_url = $this->url( array( 'action' => 'books_category', 'category_id' => $category_id ) );
						$category_count = ( isset($value2['books_count']) ? $value2['books_count'] : 0 );
						++$i;

						$categories .= '<tr>';
						$categories .= '<th scope="row">'.$i.'</th>';
						$categories .= '<td class="categories"><a href="'.$category_url.'">'.$category_name.'<a/></td>';
						$categories .= '<td>'.$category_count.'</td>';
						$categories .= '</tr>';
					}
					$categories .= '</tbody>';
					$categories .= '</table>';
					$categories .= '</div>';
					$categories .= '<div class="modal-footer">';
					$categories .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">close</button>';
					$categories .= '</div>';
					$categories .= '</div>';
					$categories .= '</div>';
					$categories .= '</div>';
				}
			}

			$data .= '<div class="language-loop">';
			$data .= '<div class="language-flag"><a href="'.$url.'"><img src="'.$flag.'" alt="'.$title.' books" title="'.$title.' books" class="mw-100"></a></div>';
			$data .= '<h1><a href="'.$url.'" title="'.$title.' books">'.$title.'</a></h1>';
			$data .= '<div class="language-count"><span><i class="far fa-file-pdf"></i> '.$books_count.'</span>'.$categories_button.'</div>';
			$data .= '</div>';
			$data .= $books;
			$data .= $categories;

			$data .= '</div>';
		}else{
			$this->cache_allow_create = false;
			$error = ( isset($json['error']) ? $json['error'] : ( isset($json['msg']) ? $json['msg'] : word('no_data') ) );
			$data = '<div class="alert alert-danger mt-3 mb-3" role="alert">'.$error.'</div>';
		}
		return $data;
	}

	public function books_category(){
		$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : 0 );
		$ml = new MUSLIM_LIBRARY( true );
		$json = $ml->category( $category_id );

		$books = '';
		if( $json != false && !isset($json['error']) ){

			$id = ( isset($json['id']) ? $json['id'] : 0 );
			$title = ( isset($json['title']) ? $json['title'] : '' );
			$description = ( isset($json['category_description']) ? $json['category_description'] : '' );
			$books_count = ( isset($json['books_count']) ? $json['books_count'] : 0 );
			$books_data = ( isset($json['books_data']) ? $json['books_data'] : '' );
			$parent_id = ( isset($json['parent_id']) ? $json['parent_id'] : '' );
			$parent_key = ( isset($json['parent_key']) ? $json['parent_key'] : '' );
			$parent_name = ( isset($json['parent_name']) ? $json['parent_name'] : '' );
			$parent_flag = ( isset($json['parent_flag']) ? $json['parent_flag'] : '' );
			$parent_url = $this->url( array( 'action' => 'books_language', 'name' => $parent_key ) );

			$limit = 30;
			$page = (int) (!isset($_GET["page"]) ? 1 : $_GET["page"]);
		  $page = ($page == 0 ? 1 : $page);
		  $perpage = $limit;
		  $startpoint = ($page * $perpage) - $perpage;
			$start = ($startpoint + $limit);

			$lastpage = ceil($books_count / $perpage);
			if( empty($title) || $page > $lastpage ){
				$this->cache_allow_create = false;
				return '<div class="alert alert-danger mt-3 mb-3" role="alert">'.word('no_data').'</div>';
			}

			$this->title = $title;
			$this->description = ( empty($description) ? word('books_in').' '.$title.' '.word('contains').' '.$books_count.' '.word('books') : $description );
			$this->url = $this->url( array( 'action' => 'books_category', 'category_id' => $category_id ) );
			$this->image = $parent_flag;

			$this->book_parent = $parent_key;

			$this->is_rtl( $parent_key );

			$this->breadcrumb_parent = array(
				array('title' => 'الكتب', 'url' => $this->url( array( 'action' => 'books' ) ) ),
				array('title' => $parent_name, 'url' => $this->url( array( 'action' => 'books_language', 'name' => $parent_key ) ) ),
			);

			$books_arr = array();
			$books_text = '';
			if( is_array($books_data) ){
				for( $x=$startpoint; $x < $start; ++$x ){
					$valueb = ( isset($books_data[$x]) ? $books_data[$x] : '' );
					if( !empty($valueb) ){
						$books_arr[] = $valueb;
						$books_text .= $valueb.',';
					}
				}

				$info = array();
				$info['type'] = 1;
				$json_books = $ml->books( rtrim($books_text, ',') );
				foreach ($json_books['data'] as $key3 => $value3) {
					$book_id = ( isset($value3['id']) ? $value3['id'] : '' );
					$book_title = ( isset($value3['title']) ? $value3['title'] : '' );
					$book_excerpt = ( isset($value3['excerpt']) ? $value3['excerpt'] : '' );
					$book_source_url = ( isset($value3['url']) ? $value3['url'].'?lang='.$parent_key : '' );
					$book_url = $this->url( array( 'action' => 'book', 'book_id' => $book_id ) );
					$book_image = ( isset($value3['image']) ? $value3['image'] : '' );
					$book_author_id = ( isset($value3['author_id']) ? $value3['author_id'] : '' );
					$book_author_name = ( isset($value3['author_name']) ? $value3['author_name'] : '' );
					$book_download = ( isset($value3['book_url']) ? $value3['book_url'] : '' );
					$book_publisher = ( isset($value3['publisher']) ? $value3['publisher'] : '' );
					$book_translator = ( isset($value3['translator']) ? $value3['translator'] : '' );

					$alt = $book_title;
					if( $book_title != $book_excerpt ){
						$alt .= "\n";
						$alt .= $book_excerpt;
					}
					if( !empty($book_author_name) ){
						$alt .= "\n";
						$alt .= $book_author_name;
					}

					$info['books'][] = array(
						'id' => $book_id,
						'title' => $book_title,
						'excerpt' => $book_excerpt,
						'source' => $book_source_url,
						'url' => $book_url,
						'image' => $book_image,
						'alt' => $alt,
						'author_id' => $book_author_id,
						'author_name' => $book_author_name,
						'author_url' => 'https://www.muslim-library.com/books-author/?author_id='.$book_author_id.'&lang='.$parent_key,
						'file' => $book_download,
						'publisher' => $book_publisher,
						'translator' => $book_translator,
						'language' => $parent_key,
						'read' => $book_source_url.'&read_id='.$book_id,
						'download' => $book_source_url.'&download_id='.$book_id
					);

				}

				$pagination_url = $this->url( array( 'action' => 'books_category', 'category_id' => $category_id, 'page' => $page ) );
				$pagination = pagination( $books_count, $perpage, $page, $pagination_url );

				$books = $pagination;
				$books .= $this->book_template( $info );
				$books .= $pagination;
			}

			$data = '<div class="category-books">';
			$data .= '<div class="alert alert-primary mt-0 mb-4" role="alert">'.$title.' '.$books_count.' '.word('books').'</div>';
			$data .= $books;
			$data .= '</div>';
		}else{
			$this->cache_allow_create = false;
			$error = ( isset($json['error']) ? $json['error'] : ( isset($json['msg']) ? $json['msg'] : word('no_data') ) );
			$data = '<div class="alert alert-danger mt-3 mb-3" role="alert">'.$error.'</div>';
		}
		return $data;
	}

	public function book(){
		$book_id = ( isset($_GET['book_id']) ? intval($_GET['book_id']) : 0 );
		$ml = new MUSLIM_LIBRARY( true );
		$json = $ml->book( $book_id );

		if( $json != false && !isset($json['error']) && isset($json['info'][0]) ){
			$json_info = $json['info'][0];

			$language_id = ( isset($json_info['language_id']) ? $json_info['language_id'] : 0 );
			$language_name = ( isset($json_info['name']) ? $json_info['name'] : '' );
			$language_key = ( isset($json_info['language']) ? $json_info['language'] : '' );

			$categories = ( isset($json_info['categories']) ? $json_info['categories'] : '' );
			$category_name = ( isset($categories[0]['name']) ? $categories[0]['name'] : '' );
			$category_description = ( isset($categories[0]['description']) ? $categories[0]['description'] : '' );
			$category_category_id = ( isset($categories[0]['category_id']) ? $categories[0]['category_id'] : '' );

			// Keep the global site interface language unchanged while allowing
			// the book content itself to use its natural reading direction.
			$this->is_rtl( $language_key );
			$this->book_parent = $language_key;

			$value3 = $json['data'];
			$book_title = ( isset($value3['title']) ? $value3['title'] : '' );
			$book_excerpt = ( isset($value3['excerpt']) ? $value3['excerpt'] : '' );
			$book_url = ( isset($value3['url']) ? $value3['url'].'?lang='.$language_key : '' );
			$book_image = ( isset($value3['image']) ? $value3['image'] : '' );
			$book_author_id = ( isset($value3['author_id']) ? $value3['author_id'] : '' );
			$book_author_name = ( isset($value3['author_name']) ? $value3['author_name'] : '' );
			$book_download = ( isset($value3['book_url']) ? $value3['book_url'] : '' );
			$book_publisher = ( isset($value3['publisher']) ? $value3['publisher'] : '' );
			$book_translator = ( isset($value3['translator']) ? $value3['translator'] : '' );

			$alt = $book_title;
			if( $book_title != $book_excerpt ){
				$alt .= "\n";
				$alt .= $book_excerpt;
			}
			if( !empty($book_author_name) ){
				$alt .= "\n";
				$alt .= $book_author_name;
			}

			$this->breadcrumb_parent = array(
				array( 'title' => word('books'), 'url' => $this->url( array( 'action' => 'books' ) ) ),
				array( 'title' => $language_name, 'url' => $this->url( array( 'action' => 'books_language', 'name' => strip_tags($language_key) ) ) ),
				array( 'title' => $category_name, 'url' => $this->url( array( 'action' => 'books_category', 'category_id' => $category_category_id ) ) )
			);

			$this->title = $book_title;
			$this->description = $book_excerpt;
			$this->url = $this->url( array( 'action' => 'book', 'book_id' => $book_id ) );
			$this->image = $book_image;
			$this->language = $language_key;
			$this->author = $book_author_name;
			$this->publisher = $book_publisher;

			$books = '<div class="books">';
			$books .= '<div class="row">';

			$books .= '<div class="col-12 col-md-12">';

			$books .= '<div class="card mb-4">';
			$books .= '<div class="row no-gutters">';
			$books .= '<div class="col-12 col-md-3">';
			$books .= '<img src="'.$this->bookCoverUrl($book_image).'" class="card-img book-cover-image book-cover-unified" alt="'.htmlentities($alt).'" title="'.htmlentities($alt).'" loading="lazy">';
			$books .= '</div>';
			$books .= '<div class="col-12 col-md-9">';
			$books .= '<div class="card-body">';
			$books .= '<h1 class="card-title">'.$book_title.'</h1>';
			if( $book_title != $book_excerpt ){
				$books .= '<p class="card-text">'.$book_excerpt.'</p>';
			}
			if( !empty($book_author_name) ){
				$books .= '<div class="card-text" title="'.word('book_author').'"><small class="text-muted"><i class="fas fa-user-edit"></i> <a target="_blank" href="https://www.muslim-library.com/books-author/?author_id='.$book_author_id.'&lang='.$language_key.'">'.$book_author_name.'</a></small></div>';
			}
			if( !empty($book_publisher) ){
				$books .= '<div class="card-text" title="'.word('book_publisher').'"><small class="text-muted"><i class="fas fa-globe"></i> '.$book_publisher.'</small></div>';
			}
			if( !empty($book_translator) ){
				$books .= '<div class="card-text" title="'.word('book_translator').'"><small class="text-muted"><i class="fas fa-language"></i> '.$book_translator.'</small></div>';
			}
			$books .= '<div class="row mt-3">';
			$books .= '<div class="col-4 col-md-4 hvr-float-shadow text-center"><a target="_blank" href="'.$book_url.'&read_id='.$book_id.'" class="btn btn-success btn-lg" title="'.word('book_read').'"><i class="fab fa-readme"></i></a></div>';
			$books .= '<div class="col-4 col-md-4 hvr-float-shadow text-center"><a target="_blank" href="'.$book_url.'&download_id='.$book_id.'" class="btn btn-warning btn-lg" title="'.word('book_download').'"><i class="fas fa-download"></i></a></div>';
			$books .= '<div class="col-4 col-md-4 hvr-float-shadow text-center"><a target="_blank" href="'.$book_url.'" class="btn btn-primary btn-lg" title="'.word('book_source').'"><i class="fas fa-link"></i></a></div>';
			$books .= '</div>';

			$books .= '</div>';
			$books .= '</div>';
			$books .= '</div>';
			$books .= '</div>';

			$books .= '</div>';
			$books .= '</div>';
			$books .= '</div>';

			$data = $books;
		}else{
			$error = ( isset($json['error']) ? $json['error'] : ( isset($json['msg']) ? $json['msg'] : word('no_data') ) );
			$data = '<div class="alert alert-danger mt-3 mb-3" role="alert">'.$error.'</div>';
		}
		return $data;
	}

	public function for_sale(){
		$this->title = 'موقع القرآن الكريم للجميع للبيع';
		$this->url = $this->url( array( 'action' => 'for_sale' ) );

		$output = '<div class="for-sale">';
		$output = '';
		/*
		$output .= '<p><img src="'.$this->siteurl.'/images/for-sale.jpg" class="w-100" alt="'.htmlentities($this->title).'" title="'.htmlentities($this->title).'"></p>';
		$output .= '<p>بسم الله والحمدلله والصلاة والسلام على رسول الله</p>';
		$output .= '<p>موقع <a href="http://www.quran-for-all.com">Quran For All</a> يقدم خدمة نشر كتاب الله وترجمة معاني القرآن الكريم للكثير من اللغات وأيضا يحتوي على عدة تفاسير للقرآن الكريم وكتب بأكثر من 100 لغة وجدير بالذكر أن الموقع يعمل منذ ما يقارب ثمان سنوات.</p>';
		$output .= '<p>بفضل الله عز وجل زيارات الموقع عالية تتراوح ما بين 2000 إلى 3800 زائر يوميا وصفحات الموقع موزعة في محركات البحث ومواقع التواصل الإجتماعي والكثير من مواقع الشبكة العنكبوتية.</p>';
		$output .= '<p>الموقع يقوم بتطوير سكربت القرآن الكريم للجميع بشكل دوري ويتم إنزال النسخة الجديدة للجميع للاستفادة منها حتى وصلنا إلى آخر إصدار وهو الإصدار الرابع ويعمل حاليا فقط على الموقع ولم يتم نشره حتى الآن.</p>';
		$output .= '<p>في هذه النسخة الجديدة طورنا برمجيات API بإمكان مطوري لغات البرمجة الإستفادة منها في برمجة تطبيقات أو مواقع أو أي أفكار أخرى كما تم زيادة عدد الكتب المرفقة مع السكربت إلى أكثر من 10000 كتاب مقسمة على أكثير من 100 لغة.</p>';
		$output .= '<p>كل هذه الزيارات والإنتشار الواسع للسكربت يعمل على استهلاك موارد الموقع وتأتينا تنبيها أحيانا من المستضيف بأن الموقع أصبح يستهلك موارد السيرفر بشكل أكبر ويجب ترقية خطة الإستضافة ولكننا نعمل على تقليل الإستهلاك أحيانا برمجيا دون دفع تكاليف أخرى وأحيانا لا تفلح تلك المحاولات.</p>';
		$output .= '<p>وأيضا أسباب أخرى مادية بحته وعدم التفرغ للموقع من الأسباب الرئيسية لعرض الموقع للبيع</p>';
		$output .= '<p>وإليكم بعض الإحصاءات الخاصة بالموقع عن طريق Google Analytics</p>';
		$output .= '<p><img src="'.$this->siteurl.'/images/google-analytics-001.jpg" class="w-100 border shadow" alt="google-analytics-001"></p>';
		$output .= '<p><img src="'.$this->siteurl.'/images/google-analytics-002.jpg" class="w-100 border shadow" alt="google-analytics-002"></p>';
		$output .= '<p><img src="'.$this->siteurl.'/images/google-analytics-003.jpg" class="w-100 border shadow" alt="google-analytics-003"></p>';
		$output .= '<p><img src="'.$this->siteurl.'/images/google-analytics-004.jpg" class="w-100 border shadow" alt="google-analytics-004"></p>';
		$output .= '<p><img src="'.$this->siteurl.'/images/google-analytics-005.jpg" class="w-100 border shadow" alt="google-analytics-005"></p>';
		$output .= '<p class="mt-4">معلومات عن الموقع:</p>';
		$output .= '<ul>
		<li>عنوان الموقع https://www.quran-for-all.com</li>
		<li>مستضيف الموقع https://www.bluehost.com</li>
		<li>تاريخ إنتهاء الإستضافة 09/2024</li>
		<li>المالك: مدير الموقع</li>
		<li>البريد الإلكتروني: <a href="mailto:admin@example.com">admin@example.com</a></li>
		<li>هاتف المالك <a href="tel:+0000000000000">0000000000000</a></li>
		<li>الواتساب <a href="https://api.whatsapp.com/send?phone=0000000000000">0000000000000</a></li>
		<li>السعر يرسل عن طريق وسائل الإتصال ولم نقدر سعر للموقع حتى الآن</li>
		</ul>';
		$output .= '<p>لأي استفسار عن الموقع نرجو التواصل عن طريق الإتصال مباشرة أو عن طريق البريد الإلكتروني أو عن طريق الواتساب</p>';
		$output .= '<p></p>';
		$output .= '<p></p>';
		$output .= '<p></p>';
		$output .= '<p></p>';
		$output .= '<p></p>';
		*/
		$output .= '</div>';

		return $this->tpl_content($this->title, $output);
	}

	public function about_script(){
		$this->title = 'تحميل سكربت القرآن الكريم للجميع الإصدار 5';
		$this->url = $this->url( array( 'action' => 'about_script' ) );

		$output = '<div class="for-sale">';
		//$output .= '<p><img src="'.$this->siteurl.'/images/for-sale.jpg" class="w-100" alt="'.htmlentities($this->title).'" title="'.htmlentities($this->title).'"></p>';
		$output .= '<p>بسم الله والحمدلله والصلاة والسلام على رسول الله</p>';
		$output .= '<p>من نعم الله علينا أن يسر لنا العمل في برمجيات تنفعنا في الدارين بإذن الله</p>';
		$output .= '<p>هذا هو سكربت القرآن الكريم للجميع الإصدار 5 بحلة جديدة وميزات كثيرة وتقنيات جديدة</p>';
		$output .= '<p>السكربت مر بالكثير من المراحل والتطويرات حتى وصلنا لهذا الإصدار وجدير بالذكر أن التطويرات كانت على مدى ثمانية سنوات وبفضل الله عز وجل لاقى السكربت استحسان الكثير من أصحاب المواقع وانتشر انتشارا واسعا.</p>';

		$output .= '<h3 class="mt-4 text-primary">ميزات السكربت</h3>';
		$output .= '<ul>
		<li>القرآن الكريم كاملا مقروء ومسموع</li>
		<li>القرآن الكريم مقسم آية آية مع إمكانية سماع الآية لوحدها أو السورة كاملة</li>
		<li>إمكانية الاستماع للسورة بصوت الكثير من القراء</li>
		<li>إمكانية التنقل بين السور والآيات وتغيير القاريء بكل سهولة</li>
		<li>حاصية تتبع مسار الرابط ما يعرف بالـ breadcrumb</li>
		<li>إمكانية مشاركة السورة أو آية أو تفسير وخلافها</li>
		<li>يحتوى على عدة تفاسير</li>
		<li>يحتوى على الكثير من ترجمات معاني القرآن الكريم</li>
		<li>يحتوي على أكثر من 10000 كتاب موزعة على لغات الكتب</li>
		<li>سهل الاستخدام وسريع التصفح</li>
		<li>متوافق مع جميع الشاشات</li>
		</ul>';
		$output .= '<h3 class="mt-4 text-success">الميزات الجديدة</h3>';
		$output .= '<ul>
		<li>إضافة ميزة Open Graph Meta Tags لترتيب البيانات وعرضها في مواقع التواصل الإجتماعي ومحركات البحث</li>
		<li>إضافة ميزة canonical</li>
		<li>إضافة ميزة schema الخاصة بتنظيم البيانات</li>
		<li>استخدام bootstrap الإصدار الأحدث</li>
		<li>استخدام fontawesome الإصدار الأحدث</li>
		<li>تحسين SEO السكربت</li>
		<li>تغيير الثيم</li>
		<li>إضافة واجة تطبيقات JSON API بالإمكان ربط السكربت بتطبيقات الهواتف الذكية</li>
		<li>تحسن أداء السكربت من حيث السرعة والاستجابة</li>
		</ul>';
		$output .= '<h3 class="mt-4 text-primary">متطلبات السكربت</h3>';
		$output .= '<ul>
		<li>إصدار php 7 فما فوق</li>
		</ul>';

		$output .= '<h3 class="mt-4 text-danger">التركيب</h3>';
		$output .= '<ul>
		<li>حمل السكربت</li>
		<li>فك الضغط أو ارفع الملف المضغوط إلى موقعك ثم فك الضغط</li>
		<li>أدخل على مجلد السكربت من خلال المتصفح</li>
		</ul>';

		$output .= '<iframe style="width: 100%; max-width: 560px; min-height: 315px; margin: 0 auto;" src="https://www.youtube.com/embed/083h9ZRI8Aw?si=k73nKoJ3mjc-NYUP" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';

		$output .= '<h3 class="mt-4 text-danger">ملاحظات</h3>';
		$output .= '<ul>
		<li>جميع الملفات الصوتية يتم استدعاؤها من الخارج وغير مرفقة مع السكربت</li>
		<li>جميع ملفات الكتب والأغلفة عن طريق موقع <a target="_blank" href="https://www.muslim-library.com">المكتبة الإسلامية الإسلامية الإلكترونية الشاملة</a></li>
		<li>السكربت كان مدفوعا وليس مجانيا منذ العام 2019 أما الآن في تاريخ 29/8/2023 سيكون مجانا للجميع ونسأل الله أن يغنينا من فضله ويبارك لنا ويكون هذا العمل خالصا لوجهه الكريم</li>
		<li>مسار الـ API الخاص بالسكربت كالتالي http://yoursite/api/quran/?action=surah</li>
		<li>حجم السكربت كاملا تقريبا 200 ميغابايت</li>
		</ul>';

		$output .= '<h3 class="mt-4 text-success">تحميل السكربت</h3>';
		$output .= '<p><a class="btn btn-primary" target="_blank" href="https://www.quran-for-all.com/download/quranforall-V5.zip" role="button"><i class="fas fa-download"></i> تحميل</a></p>';

		$output .= '<p>والله الموفق</p>';
		$output .= '</div>';

		return $this->tpl_content($this->title, $output);
	}

	public function tpl_check($filename=""){
		$full_path = $this->get_theme_folder().'/'.$filename;

		$report = '';
		$error = 0;
		if( !file_exists($this->get_theme_folder()) ){
			$report .= '<div style="text-align:center;">FOLDER TEMPLATES <b>'.$this->get_theme_folder().'</b> NOT FOUND</div>';
			$error = 1;
		}

		if( !file_exists($full_path) ){
			$report .= '<div style="text-align:center;">FILE TEMPLATE '.strip_tags($filename).' NOT FOUND INSIDE FOLDER '.$this->get_theme_folder().'</div>';
			$error = 1;
		}

		if($error == 1){
			return $report;
		}else{
			return $error;
		}
	}

	public Function shortcode_callback($m) {
		return word($m[1]);
	}

	public Function shortcode($text){
		$reg = "/LANG\[([0-9a-z_]*?)\]/";
		if( empty($text) ){
			$out = $text;
		}else{
			//$out = preg_replace_callback( $reg, "self::shortcode_callback", $text );
			$out = preg_replace_callback($reg, array($this, 'shortcode_callback'), $text);
		}
		return $out;
	}

	public function get_php_version(){
		$code = phpversion();
		return $code;
	}

	public function tpl_header(){
		$filename = 'header.htm';
		if($this->tpl_check($filename) == 0){
			$full_path = $this->get_theme_folder().'/'.$filename;
			$writefile = fopen($full_path,"r");
			$read = fread($writefile,filesize($full_path));

			// V26: hard-fix the Haram live block at render time.
			// This intentionally works even when an older header.htm remains on the server.
			$read = preg_replace(
				'~<div\\s+class=["\\\']haram-live-info["\\\'][^>]*>.*?</div>\\s*(?=<div\\s+class=["\\\']haram-video-wrap["\\\'])~is',
				'',
				$read
			);
			$read = preg_replace(
				'~<a\\b[^>]*class=["\\\'][^"\\\']*haram-youtube-link[^"\\\']*["\\\'][^>]*>.*?</a>~is',
				'',
				$read
			);
			$read = preg_replace(
				'~class=["\\\']haram-live-card(?![^"\\\']*haram-live-card--video-only)([^"\\\']*)["\\\']~i',
				'class="haram-live-card haram-live-card--video-only$1"',
				$read
			);

			if( !empty($this->headertext) ){
				$read = str_replace('q-col-header col-md-12 text-center', 'q-col-header col-12 col-md-4', $read);
			}
			$code = $this->tpl_replace($read);
			fclose ($writefile);
		}else{
			$code = $this->tpl_check($filename);
		}
		return $code;
	}

	public function tpl_footer(){
		$filename = 'footer.htm';
		if($this->tpl_check($filename) == 0){
			$full_path = $this->get_theme_folder().'/'.$filename;
			$writefile = fopen($full_path,"r");
			$read = fread($writefile,filesize($full_path));
			$code = $this->tpl_replace($read);
			fclose ($writefile);
		}else{
			$code = $this->tpl_check($filename);
		}
		return $code;
	}

	public function tpl_content($title, $content){
		$filename = 'content.htm';
		if($this->tpl_check($filename) == 0){
			$full_path = $this->get_theme_folder().'/'.$filename;
			$writefile = fopen($full_path,"r");
			$read = fread($writefile,filesize($full_path));
			$code = str_replace(array('{title}', '{content}', '{style}'), array($title, $content, $this->get_theme_folder_url()), $read);
			fclose ($writefile);
		}else{
			$code = $this->tpl_check($filename);
		}
		return $code;
	}

	public function Template_view($right="", $left=""){
		$code = $this->tpl_header();
		$code .= $this->get_breadcrumb();
		$code .= '<div class="row">';
		$code .= '<div class="col-md-8">';
		$code .= $right;
		$code .= '</div>';
		$code .= '<div class="col-md-4">';
		$code .= $left;
		$code .= '</div>';
		$code .= '</div>';
		$code .= $this->tpl_footer();
		return $code;
	}

	public function Template_view_full($content){
		$code = $this->tpl_header();
		$code .= $this->get_breadcrumb();
		$code .= $content;
		$code .= $this->tpl_footer();
		return $code;
	}

	private function ensure_page_share($html, $action = 'home'){
		if( in_array($action, array('sadaqah_agent', 'contact', 'books', 'books_language', 'books_category'), true) || strpos($html, 'class="share"') !== false ){
			return $html;
		}

		$share_title = trim(strip_tags((string)$this->title));
		if( $share_title === '' && preg_match('~<title[^>]*>(.*?)</title>~is', $html, $match) ){
			$share_title = trim(strip_tags(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8')));
		}
		if( $share_title === '' ){
			$share_title = $this->siteName;
		}

		$share_url = trim((string)$this->url);
		if( $share_url === '' ){
			/*
			 * Share links must point at the real site. Scheme and host come from the
			 * configured site URL so a forged Host header cannot publish links to an
			 * attacker domain; only the path is taken from the request.
			 */
			$site_parts = parse_url((string)$this->siteurl);
			$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
			if( is_array($site_parts) && !empty($site_parts['host']) ){
				$scheme = ( !empty($site_parts['scheme']) ? strtolower((string)$site_parts['scheme']) : 'https' );
				$host = $site_parts['host'] . ( !empty($site_parts['port']) ? ':' . (int)$site_parts['port'] : '' );
			}else{
				$scheme = ( !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off' ) ? 'https' : 'http';
				$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
			}
			$share_url = $scheme.'://'.$host.$request_uri;
		}

		$panel = $this->post_share($share_title, $share_url);

		// Standard content pages: keep sharing inside the same content card,
		// matching the established tafseer layout instead of adding a new block.
		if( preg_match('~<div\s+class="card-body"[^>]*>~i', $html, $card_match, PREG_OFFSET_CAPTURE) ){
			$insert_at = $card_match[0][1] + strlen($card_match[0][0]);
			return substr($html, 0, $insert_at).$panel.substr($html, $insert_at);
		}

		// Custom full-width pages: place sharing within their own page container.
		if( preg_match('~<div\s+class="[^"]*(?:adhkar-container|utility-page-container)[^"]*"[^>]*>~i', $html, $container_match, PREG_OFFSET_CAPTURE) ){
			$insert_at = $container_match[0][1] + strlen($container_match[0][0]);
			return substr($html, 0, $insert_at).$panel.substr($html, $insert_at);
		}

		if( preg_match('~<(?:section|div)\s+class="[^"]*(?:books|languages|qibla|tasbeeh)[^"]*"[^>]*>~i', $html, $content_match, PREG_OFFSET_CAPTURE) ){
			$insert_at = $content_match[0][1] + strlen($content_match[0][0]);
			return substr($html, 0, $insert_at).$panel.substr($html, $insert_at);
		}

		return $html.$panel;
	}

	public function output(){
		$action = ( isset($_GET['action']) ? $_GET['action'] : 'home' );
		$surah_id = ( isset($_GET['surah']) && intval($_GET['surah']) != 0 && intval($_GET['surah']) < 115 ? intval($_GET['surah']) : 1 );
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : $this->default_reader );
		$aya = ( isset($_REQUEST['ayah']) && intval($_REQUEST['ayah']) != 0 ? intval($_REQUEST['ayah']) : 1 );
		$type = ( isset($_REQUEST['type']) && intval($_REQUEST['type']) != 0 ? intval($_REQUEST['type']) : 1 );
		$lang = $this->get_language();
		$language_name = ( isset($_GET['name']) ? strip_tags($_GET['name']) : '' );
		$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : 0 );
		$page =  ( isset($_GET['page']) ? intval($_GET['page']) : 1 );

		if( $action == 'translate' ){
			if( $this->get_language() == 'ar' ){
				$cfl = 'translate-ar.html';
				if( $this->read_cache_file($cfl) == '' ){
					$output = $this->Template_view_full( $this->tpl_content(word('select_surah'), $this->quran()) );
					$this->create_cache_file($cfl, $output);
				}else{
					$output = $this->read_cache_file($cfl);
				}
			}else{
				if( isset($_GET['surah']) ){
					$cfl = 'translate-'.$lang.'-surah-'.$surah_id.'.html';
				}else{
					$cfl = 'translate-'.$lang.'.html';
				}
				if( $this->read_cache_file($cfl) == '' ){
					//word('select_language')
					$translate = $this->translate();
					$output = $this->Template_view_full( $this->tpl_content($this->body_title, $translate) );
					$this->create_cache_file($cfl, $output);
				}else{
					$output = $this->read_cache_file($cfl);
				}
			}
		}elseif( $action == 'tafseer' ){
			if( isset($_GET['surah']) && isset($_REQUEST['ayah']) ){
				$cfl = 'tafseer-'.$type.'-surah-'.$surah_id .'-ayah-'.$aya.'.html';
			}elseif( isset($_GET['surah']) && !isset($_REQUEST['ayah']) ){
				$cfl = 'tafseer-'.$type.'-surah-'.$surah_id .'.html';
			}else{
				$cfl = 'tafseer-'.$type.'.html';
			}
			if( $this->read_cache_file($cfl) == '' ){
				$tafseer = $this->tafseer();
				$output = $this->Template_view_full( $this->tpl_content($this->title, $tafseer) );
				$this->create_cache_file($cfl, $output);
			}else{
				$output = $this->read_cache_file($cfl);
			}
		}elseif( $action == 'languages' ){
			$cfl = 'languages.html';
			if( $this->read_cache_file($cfl) == '' ){
				$output = $this->Template_view_full( $this->tpl_content(word('select_language'), $this->get_languages()) );
				$this->create_cache_file($cfl, $output);
			}else{
				$output = $this->read_cache_file($cfl);
			}
		}elseif( $action == 'quran' ){
			$this->title = word('quran');
			$this->description = word('quran');
			$this->url = $this->url( array( 'action' => 'quran' ) );
			$cfl = 'quran.html';
			if( $this->read_cache_file($cfl) == '' ){
				$output = $this->Template_view_full( $this->tpl_content($this->title, $this->quran()) );
				$this->create_cache_file($cfl, $output);
			}else{
				$output = $this->read_cache_file($cfl);
			}
		}elseif( $action == 'home' && isset($_GET['surah']) ){
			$cfl = 'surah-'.$surah_id.'.html';
			if( $this->read_cache_file($cfl) == '' ){
				$content = $this->home_view_surah();
				$output = $this->Template_view_full( $this->tpl_content($this->body_title, $content ) );
				$this->create_cache_file($cfl, $output);
			}else{
				$output = $this->read_cache_file($cfl);
			}
		}elseif( $action == 'date_converter' ){
			$output = $this->Template_view_full( $this->date_converter_page() );
		}elseif( $action == 'contact' ){
			$output = $this->Template_view_full( $this->contact_page() );
		}elseif( $action == 'morning_adhkar' ){
			$output = $this->Template_view_full( $this->morning_adhkar_page() );
		}elseif( $action == 'evening_adhkar' ){
			$output = $this->Template_view_full( $this->evening_adhkar_page() );
		}elseif( $action == 'qibla' ){
			$output = $this->Template_view_full( $this->qibla_page() );
		}elseif( $action == 'tasbeeh' ){
			$output = $this->Template_view_full( $this->tasbeeh_page() );
		}elseif( $action == 'sadaqah_agent' ){
			$output = $this->Template_view_full( $this->sadaqah_agent_page() );
		}elseif( $action == 'books' ){
			// Books pages are rendered live so legacy cached English headers never leak in.
			$output = $this->Template_view_full( $this->books_languages() );
		}elseif( $action == 'books_language' ){
			$output = $this->Template_view_full( $this->books_language() );
		}elseif( $action == 'books_category' ){
			$books_category = $this->books_category();
			$output = $this->Template_view_full( $books_category );
		}elseif( $action == 'book' ){
			$book = $this->book();
			$output = $this->Template_view_full( $book );
		}elseif( $action == 'for_sale' ){
			$output = $this->Template_view_full( $this->for_sale() );
		}elseif( $action == 'about_script' ){
			$output = $this->Template_view_full( $this->about_script() );
		}else{
			// Render the home page live. Legacy cache/home.html may predate modern
			// homepage tools (date converter/contact form) and must not hide them.
			$output = $this->Template_view_full( $this->home_page() );
			$this->create_xml();
		}

		return $this->ensure_page_share($output, $action);
	}

	public function cleanUp($array){
		$cleaned_array = array();
		foreach($array as $key => $value){
			$qpos = strpos($value, "?");
			if($qpos !== false){ break; }
				if($key != "" && $value != ""){ $cleaned_array[] = $value; }
		}
		return $cleaned_array;
	}

	/*
	 * The cache directory lives inside the document root, so anything written
	 * there is reachable over HTTP unless it is explicitly sealed off. Two
	 * independent guards are used, because neither is available everywhere:
	 *
	 *   .htaccess  denies direct access and switches the PHP engine off, for
	 *              Apache and LiteSpeed.
	 *   index.php  answers 403 on any server that still maps the directory, and
	 *              removes the directory listing even where .htaccess is
	 *              ignored, such as Nginx.
	 *
	 * Both are written only when missing, so an existing cache is never
	 * disturbed and the cached files themselves are never touched.
	 */
	protected function write_cache_guards( $dir_name ){
		$dir = rtrim((string)$dir_name, '/\\').'/';

		if( $dir === '/' || !is_dir($dir) || !is_writable($dir) ){
			return;
		}

		$htaccess = $dir.'.htaccess';
		if( !file_exists($htaccess) ){
			/*
			 * Deny is the primary rule. The PHP engine is deliberately NOT turned
			 * off here any more: cache entries are now stored as .php files whose
			 * first statement is exit, so they rely on PHP running in order to
			 * return nothing. Disabling the engine, or removing the .php handler,
			 * would make Apache serve those files as plain text and hand out the
			 * very content this is meant to protect.
			 */
			$rules = "# Written automatically by the application.\n"
				."# The cache holds rendered page fragments and must never be served\n"
				."# directly. Each entry also carries its own PHP exit guard, so the\n"
				."# protection survives where this file is ignored, such as Nginx.\n"
				."<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
				."<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n"
				."Options -Indexes -ExecCGI\n";
			@file_put_contents($htaccess, $rules, LOCK_EX);
			@chmod($htaccess, 0644);
		}

		$index = $dir.'index.php';
		if( !file_exists($index) ){
			@file_put_contents($index, "<?php http_response_code(403); exit;\n", LOCK_EX);
			@chmod($index, 0644);
		}
	}

	/*
	 * Physical path of a cache entry.
	 *
	 * Entries are addressed logically as "surah-1.html" but stored on disk as
	 * "surah-1.html.php", whose first statement is exit. A direct request for a
	 * cache file therefore returns an empty body on any PHP-enabled server. That
	 * is the only layer that still holds where .htaccess is never read, which is
	 * exactly the Nginx case; the deny rule remains the first line of defence on
	 * Apache. Because the guard runs before anything else in the file, content
	 * that somehow reached the cache can never be executed either.
	 *
	 * The key is reduced to a basename over a conservative character set so a
	 * cache name can never escape the cache directory. When sanitising actually
	 * changes the name, a short digest of the original is appended so two
	 * distinct pages cannot collapse onto one cache entry.
	 */
	protected function cache_file_path( $file_path = '', $folder = '' ){
		$cache_folder = ( empty($folder) ? $this->cache_folder : $folder );
		if( (string)$cache_folder === '' ){
			return '';
		}

		$name = basename(str_replace('\\', '/', (string)$file_path));
		$name = ltrim($name, '.');
		if( $name === '' ){
			return '';
		}

		$safe = preg_replace('~[^A-Za-z0-9._-]~', '_', $name);
		if( $safe === null || $safe === '' ){
			return '';
		}
		if( $safe !== $name ){
			$safe .= '-'.substr(hash('sha256', $name), 0, 12);
		}

		return rtrim((string)$cache_folder, '/\\').'/'.$safe.self::CACHE_SUFFIX;
	}

	/*
	 * Remove the leading exit guard so callers receive exactly the HTML that was
	 * cached. Tolerates a legacy entry written before the guard existed.
	 */
	protected function cache_strip_guard( $raw ){
		$raw = (string)$raw;
		if( strncmp($raw, self::CACHE_GUARD, strlen(self::CACHE_GUARD)) === 0 ){
			return substr($raw, strlen(self::CACHE_GUARD));
		}
		// Same guard saved with different trailing whitespace.
		if( preg_match('~^<\?php\s+exit;\s*\?>\r?\n?~', $raw, $m) ){
			return substr($raw, strlen($m[0]));
		}
		return $raw;
	}

	public function check_cache_dir( $folder = '') {
		$dir_name = ( empty($folder) ? $this->cache_folder : $folder );
		$msg = array();
		if( !file_exists($dir_name) ){
			$msg[] = 'is not found.';
			if( !mkdir($dir_name, 0775, true) ){
				$msg[] = 'Failed to create folder.';
			}
		}
		if( !is_dir($dir_name) ){
			$msg[] = 'is not directory.';
		}
		if( !is_readable($dir_name) ){
			$msg[] = 'is not readable.';
		}
		if( !is_writable($dir_name) ){
			$msg[] = 'is not writable.';
		}
		if( !chmod($dir_name, 0775) ){
			$msg[] = 'must be readable and writeable.';
		}
		// Seal the directory as soon as it exists, before anything is cached in it.
		$this->write_cache_guards($dir_name);
		if( count($msg) > 0 ){
			$code = '<h3>'.strip_tags($dir_name).'</h3>';
			foreach($msg as $value){
				$code .= '<div class="alert alert-danger"><p>'.$value.'</p></div>';
			}
		}else{
			$code = '';
		}
		return $code;
	}

	public function create_cache_file($file_name='', $content='' ){
		if( $this->cache_active && $this->cache_allow_create ){
			$cache_folder = $this->cache_folder;
			$get_file_name = $this->cache_file_path($file_name);

			$msg = '';
			$err = 0;

			$prefix = '<!-- This file is cache ( Name: '.$file_name.') Added '.date('j-n-Y h:i:s A',time()).' -->'."\n";

			if( $cache_folder == "" ){
				return $this->check_cache_dir($cache_folder);
			}else{
				/*
				 * Guarantee the directory exists and carries its guard files
				 * before the first fragment is written into it. check_cache_dir()
				 * was previously only reachable when the folder path was empty,
				 * so in practice the cache directory was never created here and,
				 * where it did exist, it was left unprotected.
				 */
				if( !is_dir($cache_folder) ){
					$this->check_cache_dir($cache_folder);
				}else{
					$this->write_cache_guards($cache_folder);
				}

				if( file_exists($get_file_name) ){
					unlink($get_file_name);
				}

				$handle = fopen($get_file_name, 'w');

				if ( !$handle ){
					$msg .= 'Cannot open file ('.$get_file_name.')';
					$err = 1;
				}

				// The guard must be the first thing in the file, ahead of the payload.
				if ( fwrite($handle, self::CACHE_GUARD.$content) === FALSE ) {
					$msg .= 'Cannot write to file ('.$get_file_name.')';
					$err = 1;
				}

				if( $err != 1 ) {
					$text = 1;
					fclose($handle);
				}else{
					$text = $msg;
				}
				return $get_file_name;
			}
		}else{
			$msg = '';
			return $msg;
		}
	}

	public function expire_cache_file($file_path='', $folder=''){
		$file_name = $this->cache_file_path($file_path, $folder);
		$current_file = ( file_exists($file_name) ? $file_name : '' );
		if( $current_file == '' ){
			return false;
		}else{
			$file_time = filemtime($file_name);
			$file_expire = ( empty($this->cache_time) ? $file_time + 86400 : $file_time + $this->cache_time );
			if( time() > $file_expire ){
				return true;
			}else{
				return false;
			}
		}
	}

	public function read_cache_file($file_path=''){
		if( $this->expire_cache_file($file_path) ){
			return '';
		}
		if( $this->cache_active ){
			$file_name = $this->cache_file_path($file_path);
			$current_file = ( $file_name !== '' && file_exists($file_name) ? $file_name : '' );

			if( $current_file == '' ){
				$get_file_name = '';
			}else{
				$file_time = filemtime($file_name);
				$file_expire = ( empty($this->cache_time) ? $file_time + 86400 : $file_time + $this->cache_time );
				//$file_time2 = filemtime($file_name);
				$prefix = '<!-- Cached copy ( Name: '.$file_path.' ), generated '.date('j-n-Y h:i:s A', $file_time).' '.$file_expire.' == '.time().' -->'."\n";

				if( time() > $file_expire ){
					unlink($file_name);
					$current_file = '';
				}

    			if( !empty($current_file) ){
					if( function_exists('file_get_contents') ){
						$get_content = $this->cache_strip_guard(file_get_contents($current_file));

						// V21: invalidate legacy cached pages that still contain old inline utility links/tools
						// or a breadcrumb generated before the modern Arabic home link was added.
						$legacy_cache = false;
						if( strpos($get_content, '#contact-us') !== false || strpos($get_content, '#date-converter') !== false ){
							$legacy_cache = true;
						}
						if( strpos($get_content, 'site-tools-section') !== false || strpos($get_content, 'خدمات إضافية للزائر') !== false || strpos($get_content, 'gregorian-to-hijri-form') !== false || strpos($get_content, 'id="contact-us"') !== false ){
							$legacy_cache = true;
						}
						if( strpos($get_content, 'custom-breadcrumb') !== false && strpos($get_content, '>الرئيسية<') === false ){
							$legacy_cache = true;
						}
						if( $legacy_cache ){
							@unlink($current_file);
							return '';
						}

						if( trim($get_content) == "" ){
							$get_file_name = '';
						}else{
							$get_file_name = $prefix.$get_content;
						}
						$handle = fopen($current_file, "r");
						$contents = $this->cache_strip_guard(fread($handle, filesize($current_file)));
						$get_file_name = ( trim($contents) == "" ? '' : $prefix.$contents );
						fclose($handle);
						if( $contents == '' ){
							$get_file_name = 'file_get_contents functions are not available and fread function are not working.';
						}
					}
    			}else{
    				$get_file_name = '';
    			}
			}
		}else{
			$get_file_name = '';
		}
		return $get_file_name;
	}

	public function remove_cache_file($file_path='', $folder = ''){
			$file_name = $this->cache_file_path($file_path, $folder);
			$current_file = ( $file_name !== '' && file_exists($file_name) ? $file_name : '' );

			if( $current_file == '' ){
				return '';
			}else{
				if( unlink($current_file) ){
					return ''; //"success";
				}else{
					return '';
				}
			}
	}

	function post_share($titles='', $links=''){
		$share_text = trim(strip_tags(html_entity_decode($titles, ENT_QUOTES, 'UTF-8')));
		$share_url = trim($links);

		// V1.1.2: version shared page URLs with the active social image.
		// This forces platforms such as X to re-fetch cards after the image changes.
		$share_version = $this->get_social_share_version();

		$fragment = '';
		$fragment_pos = strpos($share_url, '#');
		if( $fragment_pos !== false ){
			$fragment = substr($share_url, $fragment_pos);
			$share_url = substr($share_url, 0, $fragment_pos);
		}

		$separator = ( strpos($share_url, '?') === false ? '?' : '&' );
		$share_url .= $separator.'sv='.rawurlencode($share_version).$fragment;

		$title = rawurlencode($share_text);
		$url = rawurlencode($share_url);
		$x_message = rawurlencode(trim($share_text.' '.$share_url));
		$twitter_via = empty($this->twitter_username) ? '' : '&amp;via='.rawurlencode(str_replace('@', '', $this->twitter_username));
		$x_icon = '<svg class="share-x-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24h-6.657l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>';

		$code = '<div class="share">';
		$code .= '<h5>'.word('share').'</h5>';
		$code .= '<div class="share-content" data-share-text="'.htmlspecialchars($share_text, ENT_QUOTES, 'UTF-8').'" data-share-url="'.htmlspecialchars($share_url, ENT_QUOTES, 'UTF-8').'">';
		$code .= '<button class="share-app share-app-native native-share-button" type="button" aria-label="مشاركة الصفحة" title="مشاركة الصفحة"><i class="fas fa-share-alt" aria-hidden="true"></i></button>';
		$code .= '<a class="share-app share-app-facebook" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على فيسبوك" title="المشاركة على فيسبوك" href="https://www.facebook.com/sharer/sharer.php?u='.$url.'&title='.$title.'"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>';
		$code .= '<a class="x-share-link" rel="noopener noreferrer" aria-label="المشاركة على X" title="المشاركة على X" data-x-app-url="twitter://post?message='.$x_message.'" href="https://twitter.com/intent/tweet?text='.$title.'&amp;url='.$url.''.$twitter_via.'">'.$x_icon.'</a>';
		$code .= '<a class="share-app share-app-pinterest" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على Pinterest" title="المشاركة على Pinterest" href="https://pinterest.com/pin/create/bookmarklet/?media=[MEDIA]&url='.$url.'&is_video=false&description='.$title.'"><i class="fab fa-pinterest-p" aria-hidden="true"></i></a>';
		$code .= '<a class="share-app share-app-reddit" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على Reddit" title="المشاركة على Reddit" href="https://www.reddit.com/submit?url='.$url.'&title='.$title.'"><i class="fab fa-reddit-alien" aria-hidden="true"></i></a>';
		$code .= '<a class="share-app share-app-linkedin" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على LinkedIn" title="المشاركة على LinkedIn" href="https://www.linkedin.com/shareArticle?mini=true&url='.$url.'&title='.$title.'&source="><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>';
		$code .= '<a class="share-app share-app-tumblr" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على Tumblr" title="المشاركة على Tumblr" href="https://www.tumblr.com/share?v=3&u='.$url.'&t='.$title.'"><i class="fab fa-tumblr" aria-hidden="true"></i></a>';
		$code .= '<a class="share-app share-app-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="المشاركة على واتساب" title="المشاركة على واتساب" href="https://api.whatsapp.com/send?text='.rawurlencode($share_text.' '.$share_url).'"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>';
		$code .= '<a class="share-app share-app-email" aria-label="المشاركة بالبريد الإلكتروني" title="المشاركة بالبريد الإلكتروني" href="mailto:?subject='.$title.'&amp;body='.$url.'"><i class="fas fa-envelope" aria-hidden="true"></i></a>';
		$code .= '</div>';
		$code .= '</div>';

		return $code;
	}

	public function moshaf_pages_loop(){
		$surah = $this->surah_name();
		$surah = ( isset($surah['data']) && is_array($surah['data']) ? $surah['data'] : '' );
		$aya_count = $this->aya_count;
		$html = '<table  class="table bg-white text-black">';
		$html .= '<tbody>';
		$i=0;
		foreach( $this->moshaf_pages() as $key => $value ){
			$i++;
			foreach( $value as $k => $v ){
				$ayat = ( isset($aya_count[$k]) ? intval($aya_count[$k]) : 0 );
				$surah_name = ( !empty($surah) && isset($surah[$k]['name']) ? $surah[$k]['name'] : $k );
				$f = ( isset($v['f']) ? intval($v['f']) : 1 );
				$t = ( isset($v['t']) ? intval($v['t']) : 2 );
				$html .= '<tr>';
				$html .= '<td>';
				$html .= $i.'- '.$key.': '.$k.' - '.$surah_name.' - from '.$f.' to '.$t;
				$html .= '</td>';
				$html .= '</tr>';
			}
		}
		$html .= '</tbody>';
		$html .= '</table >';
		return $html;
	}
}
?>
