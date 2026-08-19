<?php
class QuranForAll_API {
	public $folder_translate;
	public $folder_language;
	public $folder_tafseer;
	public $tafseers;
	public $aya_count;
	public $check_languages;
	public $rtl_languages;
	public $child_lang;
	public $ayah_images;
	public $default_language;
	public $default_reader;
	public $default_tafseer;
	public $root_path;
	public $version;

	public function __construct(){
		$this->version = '5.1';
		$this->root_path = dirname(dirname(__DIR__)); //getcwd();
		$this->folder_translate = "includes/translate";
		$this->folder_language = 'includes/language';
		$this->folder_tafseer = 'includes/tafseer';
		$this->rtl_languages = array('ar', 'ur', 'fa', 'ku', 'ug', 'dv', 'sd');
		$this->default_language = 'ar';
		$this->check_languages = array("ar", "en", "en_yusuf_ali", "en_transliteration", "fr", "nl", "tr", "ms", "id", "zh", "ja", "it", "ko", "ml", "pt", "es", "ur", "bn", "ta", "cz", "de", "fa", "ro", "ru", "sv", "sq", "az", "bs", "bg", "ha", "ku", "sj", "pl", "so", "sw", "tg", "tt", "th", "ug", "uz", "dv", "sd", "no");
		$this->aya_count = array(0, 7, 286, 200, 176, 120, 165, 206, 75, 129, 109, 123, 111, 43, 52, 99, 128, 111, 110, 98, 135, 112, 78, 118, 64, 77, 227, 93, 88, 69, 60, 34, 30, 73, 54, 45, 83, 182, 88, 75, 85, 54, 53, 89, 59, 37, 35, 38, 29, 18, 45, 60, 49, 62, 55, 78, 96, 29, 22, 24, 13, 14, 11, 11, 18, 12, 12, 30, 52, 52, 44, 28, 28, 20, 56, 40, 31, 50, 40, 46, 42, 29, 19, 36, 25, 22, 17, 19, 26, 30, 20, 15, 21, 11, 8, 8, 19, 5, 8, 8, 11, 11, 8, 3, 9, 5, 4, 7, 3, 6, 3, 5, 4, 5, 6);
		$this->tafseers = array(
			6 => array('name' => 'التفسير الميسّر — مجمع الملك فهد', 'name_en' => 'Tafseer Muyassar — King Fahd Complex'),
			1 => array('name' => 'تفسير ابن كثير', 'name_en' => 'Tafseer Ibn Khatheer'),
			2 => array('name' => 'تفسير الجلالين', 'name_en' => 'Tafseer Aljlalin'),
			3 => array('name' => 'تفسير الطبري', 'name_en' => 'Tafseer Altabari'),
			4 => array('name' => 'تفسير القرطبي', 'name_en' => 'Tafseer Alqurtubi'),
			5 => array('name' => 'تفسير السعدي', 'name_en' => 'Tafseer Alsaadi')
		);
		$this->default_reader = 37;
		$this->default_tafseer = 6;
		$this->child_lang = array("en_yusuf_ali", "en_transliteration");
		$this->ayah_images = array( 'png' => 'https://www.everyayah.com/data/quranpngs/', 'jpg' => 'https://www.everyayah.com/data/QuranText_jpg/', 'gif' => 'https://www.everyayah.com/data/QuranText/' );
	}

	function addRootPath( $root = false ){
		if( $root ){
			$path = $this->root_path.'/api/quran/';
		}else{
			$path = '';
		}
		return $path;
	}

	function get_ayah_images( $surah, $ayah, $type='jpg' ){
		$ayah_images = $this->ayah_images;

		if( in_array( $type, array('png', 'jpg', 'gif') ) ){
			$sorce = $ayah_images[$type];
			$full_url = $sorce.$surah.'_'.$ayah.'.'.$type;
		}else{
			$sorce = $ayah_images['jpg'];
			$full_url = $sorce.$surah.'_'.$ayah.'.jpg';
		}

		return $full_url;

	}

	function site_url( $url='' ){
		/*
		 * These URLs address assets served by this site itself (surah title images
		 * and language flags). Scheme and host are taken from the configured
		 * SITE_URL, so neither a forged Host header nor a spoofed
		 * X-Forwarded-Proto - which any client may send - can rewrite them.
		 * The API entry points can be reached without the site bootstrap, so the
		 * configuration is loaded here when it has not been loaded already.
		 * The path part keeps the previous SCRIPT_NAME logic unchanged, so URLs
		 * stay identical for both root and sub-folder installations.
		 */
		if( !defined('SITE_URL') ){
			$config_file = dirname(__DIR__, 2).'/includes/config.php';
			if( is_file($config_file) ){
				require_once( $config_file );
			}
		}

		$https = '';
		$HTTP_HOST = '';
		$configured = ( defined('SITE_URL') ? trim((string)SITE_URL) : '' );
		if( $configured !== '' ){
			$parts = parse_url($configured);
			if( is_array($parts) && !empty($parts['host']) ){
				$https = ( !empty($parts['scheme']) ? strtolower((string)$parts['scheme']) : 'https' ).'://';
				$HTTP_HOST = strtolower((string)$parts['host']).( !empty($parts['port']) ? ':'.(int)$parts['port'] : '' );
			}
		}

		if( $HTTP_HOST === '' ){
			// Pre-install fallback only: no trusted site URL has been configured yet.
			$https = ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://' );
			if( isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443 ){
				$https = 'https://';
			}
			$HTTP_HOST = str_replace($https, '', strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost')));
		}

		$script_dir = dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
		$_url = ( empty($url) ? $https.$HTTP_HOST.''.$script_dir : $https.$HTTP_HOST.''.$script_dir.'/'.$url );
		return $_url;
	}

	function action(){
		return ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'error' );
	}

	function get_language(){
		if( isset($_GET['l']) && !empty($_GET['l']) ){
			$get_l = ( in_array($_GET['l'], $this->check_languages) ? strip_tags($_GET['l']) : 'ar' );
			$lang = strip_tags($_GET['l']);
			$lang_split = explode(',', $lang);
			if( is_array($lang_split) && count($lang_split) > 1 ){
				$langs = array();
				foreach( $lang_split as $key => $value ){
					if( in_array($value, $this->check_languages) ){
						$langs[] = $value;
					}
				}
				$l = $langs;
			}else{
				$l = $get_l;
			}
		}else{
			$l = ( in_array($this->default_language, $this->check_languages) ? $this->default_language : 'ar' );
		}
		return $l;
	}

	function tafseer_id( $get_key = 'tafseer_id' ){
		$tafseer = $this->tafseers;
		$tafseer_id = ( isset($_REQUEST[$get_key]) ? intval($_REQUEST[$get_key]) : $this->default_tafseer );
		if( isset($_GET['type']) ){
			$tafseer_id = intval($_GET['type']);
		}

		if( array_key_exists( $tafseer_id, $tafseer) ){
			$type = $tafseer_id;
		}else{
			$type = $this->default_tafseer;
		}
		return $type;
	}

	function ayah_id( $get_key = 'ayah_id' ){
		$surah_id = $this->surah_id();
		$aya = ( isset($_REQUEST[$get_key]) && intval($_REQUEST[$get_key]) != 0 ? intval($_REQUEST[$get_key]) : 1 );
		if( isset($_GET['ayah']) ){
			$aya = intval($_GET['ayah']);
		}

		if($aya > $this->aya_count[$surah_id]){
			$get_aya = $this->aya_count[$surah_id];
		}else{
			$get_aya = $aya;
		}

		return $get_aya;
	}

	function surah_id( $get_key = 'surah_id' ){
		$surah_id = ( isset($_REQUEST[$get_key]) ? intval($_REQUEST[$get_key]) : 1 );
		if( $surah_id > 114 ) $surah_id = 114;
		return $surah_id;
	}

	function surah_id_key(){
		$surah_id = ( isset($_REQUEST['surah_id']) ? intval($_REQUEST['surah_id']) : 1 );
		if( isset($_GET['surah']) ){
			$surah_id = intval($_GET['surah']);
		}

		if( $surah_id > 114 ) $surah_id = 114;
		$surah_id_key = ( $surah_id - 1 );
		return $surah_id_key;
	}

	function surah_name_image( $n = '', $type = 'png', $root = false ){
		$surah_id = ( empty($n) ? $this->surah_id() : $n );
		$path = $this->addRootPath( $root );

		$src = '';
		if( $type == 'svg' ){
			if( file_exists($path.'images/surah-titles/svg/'.$surah_id.'.svg') ){
				$src = $this->site_url( $path.'images/surah-titles/svg/'.$surah_id.'.svg' );
			}
		}else{
			if( file_exists($path.'images/surah-titles/'.$surah_id.'.png') ){
				$src = $this->site_url( $path.'images/surah-titles/'.$surah_id.'.png' );
			}
		}

		return $src;
	}

	function api_surah_name( $lang_key = '', $all = 0 ){
		$surah = array();

		$surah['ar'] = array( 1 => array( 'name' => 'الفاتحة' ), 2 => array( 'name' => 'البقرة' ), 3 => array( 'name' => 'آل عمران' ), 4 => array( 'name' => 'النساء' ), 5 => array( 'name' => 'المائدة' ), 6 => array( 'name' => 'الأنعام' ), 7 => array( 'name' => 'الأعراف' ), 8 => array( 'name' => 'الأنفال' ), 9 => array( 'name' => 'التوبة' ), 10 => array( 'name' => 'يونس' ), 11 => array( 'name' => 'هود' ), 12 => array( 'name' => 'يوسف' ), 13 => array( 'name' => 'الرعد' ), 14 => array( 'name' => 'إبراهيم' ), 15 => array( 'name' => 'الحجر' ), 16 => array( 'name' => 'النحل' ), 17 => array( 'name' => 'الإسراء' ), 18 => array( 'name' => 'الكهف' ), 19 => array( 'name' => 'مريم' ), 20 => array( 'name' => 'طه' ), 21 => array( 'name' => 'الأنبياء' ), 22 => array( 'name' => 'الحج' ), 23 => array( 'name' => 'المؤمنون' ), 24 => array( 'name' => 'النور' ), 25 => array( 'name' => 'الفرقان' ), 26 => array( 'name' => 'الشعراء' ), 27 => array( 'name' => 'النمل' ), 28 => array( 'name' => 'القصص' ), 29 => array( 'name' => 'العنكبوت' ), 30 => array( 'name' => 'الروم' ), 31 => array( 'name' => 'لقمان' ), 32 => array( 'name' => 'السجدة' ), 33 => array( 'name' => 'الأحزاب' ), 34 => array( 'name' => 'سبأ' ), 35 => array( 'name' => 'فاطر' ), 36 => array( 'name' => 'يس' ), 37 => array( 'name' => 'الصافات' ), 38 => array( 'name' => 'ص' ), 39 => array( 'name' => 'الزمر' ), 40 => array( 'name' => 'غافر' ), 41 => array( 'name' => 'فصلت' ), 42 => array( 'name' => 'الشورى' ), 43 => array( 'name' => 'الزخرف' ), 44 => array( 'name' => 'الدخان' ), 45 => array( 'name' => 'الجاثية' ), 46 => array( 'name' => 'الأحقاف' ), 47 => array( 'name' => 'محمد' ), 48 => array( 'name' => 'الفتح' ), 49 => array( 'name' => 'الحجرات' ), 50 => array( 'name' => 'ق' ), 51 => array( 'name' => 'الذاريات' ), 52 => array( 'name' => 'الطور' ), 53 => array( 'name' => 'النجم' ), 54 => array( 'name' => 'القمر' ), 55 => array( 'name' => 'الرحمن' ), 56 => array( 'name' => 'الواقعة' ), 57 => array( 'name' => 'الحديد' ), 58 => array( 'name' => 'المجادلة' ), 59 => array( 'name' => 'الحشر' ), 60 => array( 'name' => 'الممتحنة' ), 61 => array( 'name' => 'الصف' ), 62 => array( 'name' => 'الجمعة' ), 63 => array( 'name' => 'المنافقون' ), 64 => array( 'name' => 'التغابن' ), 65 => array( 'name' => 'الطلاق' ), 66 => array( 'name' => 'التحريم' ), 67 => array( 'name' => 'الملك' ), 68 => array( 'name' => 'القلم' ), 69 => array( 'name' => 'الحاقة' ), 70 => array( 'name' => 'المعارج' ), 71 => array( 'name' => 'نوح' ), 72 => array( 'name' => 'الجن' ), 73 => array( 'name' => 'المزمل' ), 74 => array( 'name' => 'المدثر' ), 75 => array( 'name' => 'القيامة' ), 76 => array( 'name' => 'الإنسان' ), 77 => array( 'name' => 'المرسلات' ), 78 => array( 'name' => 'النبأ' ), 79 => array( 'name' => 'النازعات' ), 80 => array( 'name' => 'عبس' ), 81 => array( 'name' => 'التكوير' ), 82 => array( 'name' => 'الانفطار' ), 83 => array( 'name' => 'المطففين' ), 84 => array( 'name' => 'الانشقاق' ), 85 => array( 'name' => 'البروج' ), 86 => array( 'name' => 'الطارق' ), 87 => array( 'name' => 'الأعلى' ), 88 => array( 'name' => 'الغاشية' ), 89 => array( 'name' => 'الفجر' ), 90 => array( 'name' => 'البلد' ), 91 => array( 'name' => 'الشمس' ), 92 => array( 'name' => 'الليل' ), 93 => array( 'name' => 'الضحى' ), 94 => array( 'name' => 'الشرح' ), 95 => array( 'name' => 'التين' ), 96 => array( 'name' => 'العلق' ), 97 => array( 'name' => 'القدر' ), 98 => array( 'name' => 'البينة' ), 99 => array( 'name' => 'الزلزلة' ), 100 => array( 'name' => 'العاديات' ), 101 => array( 'name' => 'القارعة' ), 102 => array( 'name' => 'التكاثر' ), 103 => array( 'name' => 'العصر' ), 104 => array( 'name' => 'الهمزة' ), 105 => array( 'name' => 'الفيل' ), 106 => array( 'name' => 'قريش' ), 107 => array( 'name' => 'الماعون' ), 108 => array( 'name' => 'الكوثر' ), 109 => array( 'name' => 'الكافرون' ), 110 => array( 'name' => 'النصر' ), 111 => array( 'name' => 'المسد' ), 112 => array( 'name' => 'الإخلاص' ), 113 => array( 'name' => 'الفلق' ), 114 => array( 'name' => 'الناس' ) );
		$surah['en'] = array( 1 => array( 'name' => 'Al-Fatihah ( The Opening )' ), 2 => array( 'name' => 'Al-Baqarah ( The Cow )' ), 3 => array( 'name' => 'Al-Imran ( The Famiy of Imran )' ), 4 => array( 'name' => 'An-Nisa ( The Women )' ), 5 => array( 'name' => 'Al-Maidah ( The Table spread with Food )' ), 6 => array( 'name' => 'Al-An\'am ( The Cattle )' ), 7 => array( 'name' => 'Al-A\'raf (The Heights )' ), 8 => array( 'name' => 'Al-Anfal ( The Spoils of War )' ), 9 => array( 'name' => 'At-Taubah ( The Repentance )' ), 10 => array( 'name' => 'Yunus ( Jonah )' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Yusuf (Joseph )' ), 13 => array( 'name' => 'Ar-Ra\'d ( The Thunder )' ), 14 => array( 'name' => 'Ibrahim ( Abraham )' ), 15 => array( 'name' => 'Al-Hijr ( The Rocky Tract )' ), 16 => array( 'name' => 'An-Nahl ( The Bees )' ), 17 => array( 'name' => 'Al-Isra ( The Night Journey )' ), 18 => array( 'name' => 'Al-Kahf ( The Cave )' ), 19 => array( 'name' => 'Maryam ( Mary )' ), 20 => array( 'name' => 'Taha' ), 21 => array( 'name' => 'Al-Anbiya ( The Prophets )' ), 22 => array( 'name' => 'Al-Hajj ( The Pilgrimage )' ), 23 => array( 'name' => 'Al-Mu\'minoon ( The Believers )' ), 24 => array( 'name' => 'An-Noor ( The Light )' ), 25 => array( 'name' => 'Al-Furqan (The Criterion )' ), 26 => array( 'name' => 'Ash-Shuara ( The Poets )' ), 27 => array( 'name' => 'An-Naml (The Ants )' ), 28 => array( 'name' => 'Al-Qasas ( The Stories )' ), 29 => array( 'name' => 'Al-Ankaboot ( The Spider )' ), 30 => array( 'name' => 'Ar-Room ( The Romans )' ), 31 => array( 'name' => 'Luqman' ), 32 => array( 'name' => 'As-Sajdah ( The Prostration )' ), 33 => array( 'name' => 'Al-Ahzab ( The Combined Forces )' ), 34 => array( 'name' => 'Saba ( Sheba )' ), 35 => array( 'name' => 'Fatir ( The Orignator )' ), 36 => array( 'name' => 'Ya-seen' ), 37 => array( 'name' => 'As-Saaffat ( Those Ranges in Ranks )' ), 38 => array( 'name' => 'Sad ( The Letter Sad )' ), 39 => array( 'name' => 'Az-Zumar ( The Groups )' ), 40 => array( 'name' => 'Ghafir ( The Forgiver God )' ), 41 => array( 'name' => 'Fussilat ( Explained in Detail )' ), 42 => array( 'name' => 'Ash-Shura (Consultation )' ), 43 => array( 'name' => 'Az-Zukhruf ( The Gold Adornment )' ), 44 => array( 'name' => 'Ad-Dukhan ( The Smoke )' ), 45 => array( 'name' => 'Al-Jathiya ( Crouching )' ), 46 => array( 'name' => 'Al-Ahqaf ( The Curved Sand-hills )' ), 47 => array( 'name' => 'Muhammad' ), 48 => array( 'name' => 'Al-Fath ( The Victory )' ), 49 => array( 'name' => 'Al-Hujurat ( The Dwellings )' ), 50 => array( 'name' => 'Qaf ( The Letter Qaf )' ), 51 => array( 'name' => 'Adh-Dhariyat ( The Wind that Scatter )' ), 52 => array( 'name' => 'At-Tur ( The Mount )' ), 53 => array( 'name' => 'An-Najm ( The Star )' ), 54 => array( 'name' => 'Al-Qamar ( The Moon )' ), 55 => array( 'name' => 'Ar-Rahman ( The Most Graciouse )' ), 56 => array( 'name' => 'Al-Waqi\'ah ( The Event )' ), 57 => array( 'name' => 'Al-Hadid ( The Iron )' ), 58 => array( 'name' => 'Al-Mujadilah ( She That Disputeth )' ), 59 => array( 'name' => 'Al-Hashr ( The Gathering )' ), 60 => array( 'name' => 'Al-Mumtahanah ( The Woman to be examined )' ), 61 => array( 'name' => 'As-Saff ( The Row )' ), 62 => array( 'name' => 'Al-Jumu\'ah ( Friday )' ), 63 => array( 'name' => 'Al-Munafiqoon ( The Hypocrites )' ), 64 => array( 'name' => 'At-Taghabun ( Mutual Loss &amp;amp; Gain )' ), 65 => array( 'name' => 'At-Talaq ( The Divorce )' ), 66 => array( 'name' => 'At-Tahrim ( The Prohibition )' ), 67 => array( 'name' => 'Al-Mulk ( Dominion )' ), 68 => array( 'name' => 'Al-Qalam ( The Pen )' ), 69 => array( 'name' => 'Al-Haaqqah ( The Inevitable )' ), 70 => array( 'name' => 'Al-Ma\'arij (The Ways of Ascent )' ), 71 => array( 'name' => 'Nooh' ), 72 => array( 'name' => 'Al-Jinn ( The Jinn )' ), 73 => array( 'name' => 'Al-Muzzammil (The One wrapped in Garments)' ), 74 => array( 'name' => 'Al-Muddaththir ( The One Enveloped )' ), 75 => array( 'name' => 'Al-Qiyamah ( The Resurrection )' ), 76 => array( 'name' => 'Al-Insan ( Man )' ), 77 => array( 'name' => 'Al-Mursalat ( Those sent forth )' ), 78 => array( 'name' => 'An-Naba\' ( The Great News )' ), 79 => array( 'name' => 'An-Nazi\'at ( Those who Pull Out )' ), 80 => array( 'name' => 'Abasa ( He frowned )' ), 81 => array( 'name' => 'At-Takwir ( The Overthrowing )' ), 82 => array( 'name' => 'Al-Infitar ( The Cleaving )' ), 83 => array( 'name' => 'Al-Mutaffifin (Those Who Deal in Fraud)' ), 84 => array( 'name' => 'Al-Inshiqaq (The Splitting Asunder)' ), 85 => array( 'name' => 'Al-Burooj ( The Big Stars )' ), 86 => array( 'name' => 'At-Tariq ( The Night-Comer )' ), 87 => array( 'name' => 'Al-A\'la ( The Most High )' ), 88 => array( 'name' => 'Al-Ghashiya ( The Overwhelming )' ), 89 => array( 'name' => 'Al-Fajr ( The Dawn )' ), 90 => array( 'name' => 'Al-Balad ( The City )' ), 91 => array( 'name' => 'Ash-Shams ( The Sun )' ), 92 => array( 'name' => 'Al-Layl ( The Night )' ), 93 => array( 'name' => 'Ad-Dhuha ( The Forenoon )' ), 94 => array( 'name' => 'As-Sharh ( The Opening Forth)' ), 95 => array( 'name' => 'At-Tin ( The Fig )' ), 96 => array( 'name' => 'Al-\'alaq ( The Clot )' ), 97 => array( 'name' => 'Al-Qadr ( The Night of Decree )' ), 98 => array( 'name' => 'Al-Bayyinah ( The Clear Evidence )' ), 99 => array( 'name' => 'Az-Zalzalah ( The Earthquake )' ), 100 => array( 'name' => 'Al-\'adiyat ( Those That Run )' ), 101 => array( 'name' => 'Al-Qari\'ah ( The Striking Hour )' ), 102 => array( 'name' => 'At-Takathur ( The piling Up )' ), 103 => array( 'name' => 'Al-Asr ( The Time )' ), 104 => array( 'name' => 'Al-Humazah ( The Slanderer )' ), 105 => array( 'name' => 'Al-Fil ( The Elephant )' ), 106 => array( 'name' => 'Quraish' ), 107 => array( 'name' => 'Al-Ma\'un ( Small Kindnesses )' ), 108 => array( 'name' => 'Al-Kauther ( A River in Paradise)' ), 109 => array( 'name' => 'Al-Kafiroon ( The Disbelievers )' ), 110 => array( 'name' => 'An-Nasr ( The Help )' ), 111 => array( 'name' => 'Al-Masad ( The Palm Fibre )' ), 112 => array( 'name' => 'Al-Ikhlas ( Sincerity )' ), 113 => array( 'name' => 'Al-Falaq ( The Daybreak )' ), 114 => array( 'name' => 'An-Nas ( Mankind )' ) );
		$surah['fr'] = array( 1 => array( 'name' => 'Prologue (Al-Fatiha)' ), 2 => array( 'name' => 'La g&eacute;nisse (Al-Baqarah)' ), 3 => array( 'name' => 'La famille d\'Imran (Al-Imran)' ), 4 => array( 'name' => 'Les femmes (An-Nisa\')' ), 5 => array( 'name' => 'La table servie (Al-Maydah)' ), 6 => array( 'name' => 'Les bestiaux (Al-An&rsquo;ame)' ), 7 => array( 'name' => 'Al-A&rsquo;raf' ), 8 => array( 'name' => 'Le butin (Al-Anf&acirc;l)' ), 9 => array( 'name' => 'Le repentir (At-Tawbah)' ), 10 => array( 'name' => 'Jonas (Younouss)' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Joseph (Youso&ucirc;f)' ), 13 => array( 'name' => 'Le tonnerre (Ar-Raad)' ), 14 => array( 'name' => 'Abraham (Ibrahim)' ), 15 => array( 'name' => 'Al-Hijr' ), 16 => array( 'name' => 'Les abeilles (An-Nahl)' ), 17 => array( 'name' => 'Le voyage nocturne (Al-Israh)' ), 18 => array( 'name' => 'La caverne (Al-Kahf)' ), 19 => array( 'name' => 'Marie (Maryem)' ), 20 => array( 'name' => 'T&acirc;-H&acirc;' ), 21 => array( 'name' => 'Les proph&egrave;tes (Al-Anbiya)' ), 22 => array( 'name' => 'Le p&egrave;lerinage (Al-Hajj)' ), 23 => array( 'name' => 'Les croyants (Al-Mouminoune)' ), 24 => array( 'name' => 'La lumi&egrave;re (An-Nour)' ), 25 => array( 'name' => 'Le discernement (Al Fourqane)' ), 26 => array( 'name' => 'Les po&egrave;tes (As-Chouaraa)' ), 27 => array( 'name' => 'Les fourmis (An-Naml)' ), 28 => array( 'name' => 'Le r&egrave;cit (Al-Qassas)' ), 29 => array( 'name' => 'L\'araign&egrave;e (Al-Ankabout)' ), 30 => array( 'name' => 'Les romains (Ar-Roum)' ), 31 => array( 'name' => 'Louqmane' ), 32 => array( 'name' => 'La prosternation (As-Sajda)' ), 33 => array( 'name' => 'Les coalis&eacute;s (Al-Ahzab)' ), 34 => array( 'name' => 'Sabaa' ), 35 => array( 'name' => 'Le Cr&eacute;ateur (Fatir)' ), 36 => array( 'name' => 'Ya-Sin' ), 37 => array( 'name' => 'Les rang&eacute;s (As-Saffat)' ), 38 => array( 'name' => 'S&acirc;d' ), 39 => array( 'name' => 'Les groupes (Az-Zoumar)' ), 40 => array( 'name' => 'Le pardonneur (Ghafir)' ), 41 => array( 'name' => 'Les versets d&eacute;taill&eacute;s (Foussil' ), 42 => array( 'name' => 'La consultation (Achoura)' ), 43 => array( 'name' => 'L\'ornement (Azzoukhrof)' ), 44 => array( 'name' => 'La fum&eacute;e (Ad-Doukhan)' ), 45 => array( 'name' => 'L\'agenouill&eacute;e (Al-Jathya)' ), 46 => array( 'name' => 'Al-Ahq&acirc;f ' ), 47 => array( 'name' => 'Mohammed ' ), 48 => array( 'name' => 'La victoire &eacute;clatante (Al-Fath' ), 49 => array( 'name' => 'Les appartements (Al-Houjourat' ), 50 => array( 'name' => 'Q&acirc;f ' ), 51 => array( 'name' => 'Qui &eacute;parpillent (Ad-Dariyat) ' ), 52 => array( 'name' => 'At-T&ucirc;r ' ), 53 => array( 'name' => 'L\'&eacute;toile (An-Najm) ' ), 54 => array( 'name' => 'La lune (Al-Qamar)' ), 55 => array( 'name' => 'Le Tout Mis&eacute;ricordieux (Ar-Rah' ), 56 => array( 'name' => 'L\'ev&eacute;nement (Al-Waqi\'a) ' ), 57 => array( 'name' => 'Le fer (Al-Hadid) ' ), 58 => array( 'name' => 'La discussion (Al-Moujadalah) ' ), 59 => array( 'name' => 'L\'exode (Al-Hasr) ' ), 60 => array( 'name' => 'L\'&eacute;prouv&eacute;e (Al-Moumtahina) ' ), 61 => array( 'name' => 'Le rang (As-Saff)' ), 62 => array( 'name' => 'Le vendredi (Al-Joumou&rsquo;a) ' ), 63 => array( 'name' => 'Les hypocrites (Al-Mounafiqoun' ), 64 => array( 'name' => 'La grande perte (At-Tagaboun) ' ), 65 => array( 'name' => 'Le divorce (At-Talaq) ' ), 66 => array( 'name' => 'L\'interdiction (At-Tahrim) ' ), 67 => array( 'name' => 'La royaut&eacute; (Al-Moulk) ' ), 68 => array( 'name' => 'La plume (Al-Qalam)' ), 69 => array( 'name' => 'Celle qui montre la v&eacute;rit&eacute; (Al' ), 70 => array( 'name' => 'Les voies d\'ascension (Al- Ma&rsquo;' ), 71 => array( 'name' => 'No&eacute; (Nouh) ' ), 72 => array( 'name' => 'Les djinns (Al-Jinn) ' ), 73 => array( 'name' => 'L\'envelopp&eacute; (Al-Mouzzamil) ' ), 74 => array( 'name' => 'Le rev&ecirc;tu d\'un manteau (Al-Mou' ), 75 => array( 'name' => 'La r&eacute;surrection (Al-Qiyamah) ' ), 76 => array( 'name' => 'L\'homme (Al-Inssane) ' ), 77 => array( 'name' => 'Les envoy&eacute;s (Al-Moursalate)' ), 78 => array( 'name' => 'La nouvelle (An-Nabaa) ' ), 79 => array( 'name' => 'Les anges qui arrachent les &acirc;m' ), 80 => array( 'name' => 'Il s\'est renfrogn&eacute; (Abasa) ' ), 81 => array( 'name' => 'L\'obscurcissement (At-Taqwir) ' ), 82 => array( 'name' => 'La rupture (Al-Infit&acirc;r) ' ), 83 => array( 'name' => 'Les fraudeurs (Al-Moutaffifine' ), 84 => array( 'name' => 'La d&eacute;chirure (Al-Insiqaq) ' ), 85 => array( 'name' => 'Les constellations (Al-Bourouj' ), 86 => array( 'name' => 'L\'astre nocturne (At-Tar&icirc;q) ' ), 87 => array( 'name' => 'Le Tr&egrave;s-Haut (Al-A&rsquo;la)' ), 88 => array( 'name' => 'L\'enveloppante (Al-Ghasiyah) ' ), 89 => array( 'name' => 'L\'aube (Al-Fajr) ' ), 90 => array( 'name' => 'La cit&eacute; (Al-Balad) ' ), 91 => array( 'name' => 'Le soleil (Ach-Chamss) ' ), 92 => array( 'name' => 'La nuit (Al-Layl) ' ), 93 => array( 'name' => 'Le jour montant (Ad-Douha) ' ), 94 => array( 'name' => 'L\'ouverture (As-Sarh) ' ), 95 => array( 'name' => 'Le figuier (At-Tin) ' ), 96 => array( 'name' => 'L\'adh&eacute;rence (Al-Alaq) ' ), 97 => array( 'name' => 'La Destin&eacute;e (Al-Qadr) ' ), 98 => array( 'name' => 'La preuve (Al-Bayinah)' ), 99 => array( 'name' => 'La secousse (Az-Zalzalah) ' ), 100 => array( 'name' => 'Les coursiers (Al-Adiyate) ' ), 101 => array( 'name' => 'Le fracas (Al-Qariah) ' ), 102 => array( 'name' => 'La course aux richesses (At-Ta' ), 103 => array( 'name' => 'Le temps (Al-Asr) ' ), 104 => array( 'name' => 'Les calomniateurs (Al-Houmazah' ), 105 => array( 'name' => 'L\'&eacute;l&eacute;phant (Al-F&icirc;l) ' ), 106 => array( 'name' => 'Qora&iuml;sh ' ), 107 => array( 'name' => 'L\'ustensile (Al-Maoun) ' ), 108 => array( 'name' => 'L\'abondance (Al-Kawtar) ' ), 109 => array( 'name' => 'Les infid&egrave;les (Al-Qafiroune) ' ), 110 => array( 'name' => 'Les secours (An-Nasr) ' ), 111 => array( 'name' => 'Les fibres (Al-Masad) ' ), 112 => array( 'name' => 'Le monoth&eacute;isme pur (Al-Ikhlass' ), 113 => array( 'name' => 'L\'aube naissante (Al-Falaq) ' ), 114 => array( 'name' => 'Les hommes (An-Nass)' ) );
		$surah['tr'] = array( 1 => array( 'name' => '﻿Fatiha Suresi' ), 2 => array( 'name' => 'Bakara Suresi' ), 3 => array( 'name' => '&Acirc;l-i Imran Suresi' ), 4 => array( 'name' => 'Nisa Suresi' ), 5 => array( 'name' => 'Maide Suresi' ), 6 => array( 'name' => 'Enam Suresi' ), 7 => array( 'name' => 'Araf Suresi' ), 8 => array( 'name' => 'Enfal Suresi' ), 9 => array( 'name' => 'Tevbe Suresi' ), 10 => array( 'name' => 'Yunus Suresi' ), 11 => array( 'name' => 'Hud Suresi' ), 12 => array( 'name' => 'Yusuf Suresi' ), 13 => array( 'name' => 'Rad Suresi' ), 14 => array( 'name' => 'Ibrahim Suresi' ), 15 => array( 'name' => 'Hicr Suresi' ), 16 => array( 'name' => 'Nahl Suresi' ), 17 => array( 'name' => 'Isra Suresi' ), 18 => array( 'name' => 'Kehf Suresi' ), 19 => array( 'name' => 'Meryem Suresi' ), 20 => array( 'name' => 'Taha Suresi' ), 21 => array( 'name' => 'Enbiya Suresi' ), 22 => array( 'name' => 'Hac Suresi' ), 23 => array( 'name' => 'M&uuml;minun Suresi' ), 24 => array( 'name' => 'Nur Suresi' ), 25 => array( 'name' => 'Furkan Suresi' ), 26 => array( 'name' => 'Şuara Suresi' ), 27 => array( 'name' => 'Neml Suresi' ), 28 => array( 'name' => 'Kasas Suresi' ), 29 => array( 'name' => 'Ankebut Suresi' ), 30 => array( 'name' => 'Rum Suresi' ), 31 => array( 'name' => 'Lokman Suresi' ), 32 => array( 'name' => 'Secde Suresi' ), 33 => array( 'name' => 'Ahzab Suresi' ), 34 => array( 'name' => 'Sebe Suresi' ), 35 => array( 'name' => 'Fatır Suresi' ), 36 => array( 'name' => 'Yasin Suresi' ), 37 => array( 'name' => 'Saffet Suresi' ), 38 => array( 'name' => 'Sad Suresi' ), 39 => array( 'name' => 'Z&uuml;mer Suresi' ), 40 => array( 'name' => 'M&uuml;min Suresi' ), 41 => array( 'name' => 'Fussilet Suresi' ), 42 => array( 'name' => 'Şura Suresi' ), 43 => array( 'name' => 'Zuhruf Suresi' ), 44 => array( 'name' => 'Duhan Suresi' ), 45 => array( 'name' => 'Casiye Suresi' ), 46 => array( 'name' => 'Ahkaf Suresi' ), 47 => array( 'name' => 'Muhammed Suresi' ), 48 => array( 'name' => 'Fetih Suresi' ), 49 => array( 'name' => 'Hucurat Suresi' ), 50 => array( 'name' => 'Kaf Suresi' ), 51 => array( 'name' => 'Zariyat Suresi' ), 52 => array( 'name' => 'Tur Suresi' ), 53 => array( 'name' => 'Necm Suresi' ), 54 => array( 'name' => 'Kamer Suresi' ), 55 => array( 'name' => 'Rahman Suresi' ), 56 => array( 'name' => 'Vakia Suresi' ), 57 => array( 'name' => 'Hadid Suresi' ), 58 => array( 'name' => 'M&uuml;cadele Suresi' ), 59 => array( 'name' => 'Hasr Suresi' ), 60 => array( 'name' => 'M&uuml;mtehine Suresi' ), 61 => array( 'name' => 'Saf Suresi' ), 62 => array( 'name' => 'C&uuml;ma Suresi' ), 63 => array( 'name' => 'M&uuml;nafikun Suresi' ), 64 => array( 'name' => 'Tegabun Suresi' ), 65 => array( 'name' => 'Talak Suresi' ), 66 => array( 'name' => 'Tahrim Suresi' ), 67 => array( 'name' => 'M&uuml;lk Suresi' ), 68 => array( 'name' => 'Kalem Suresi' ), 69 => array( 'name' => 'Hakka Suresi' ), 70 => array( 'name' => 'Mearic Suresi' ), 71 => array( 'name' => 'Nuh Suresi' ), 72 => array( 'name' => 'Cin Suresi' ), 73 => array( 'name' => 'M&uuml;zemmil Suresi' ), 74 => array( 'name' => 'M&uuml;dahhir Suresi' ), 75 => array( 'name' => 'Kiyame Suresi' ), 76 => array( 'name' => 'Insan Suresi' ), 77 => array( 'name' => 'M&uuml;rselat Suresi' ), 78 => array( 'name' => 'Nebe Suresi' ), 79 => array( 'name' => 'Naziat Suresi' ), 80 => array( 'name' => 'Abese Suresi' ), 81 => array( 'name' => 'Tekvir Suresi' ), 82 => array( 'name' => 'Infitar Suresi' ), 83 => array( 'name' => 'M&uuml;teffifin Suresi' ), 84 => array( 'name' => 'Inşikak Suresi' ), 85 => array( 'name' => 'B&uuml;ruc Suresi' ), 86 => array( 'name' => 'Tarık Suresi' ), 87 => array( 'name' => 'Ala Suresi' ), 88 => array( 'name' => 'Gaşiye Suresi' ), 89 => array( 'name' => 'Fecr Suresi' ), 90 => array( 'name' => 'Beled Suresi' ), 91 => array( 'name' => 'Şems Suresi' ), 92 => array( 'name' => 'Leyl Suresi' ), 93 => array( 'name' => 'Duha Suresi' ), 94 => array( 'name' => 'Inşirah Suresi' ), 95 => array( 'name' => 'Tin Suresi' ), 96 => array( 'name' => 'Alak Suresi' ), 97 => array( 'name' => 'Kadir Suresi' ), 98 => array( 'name' => 'Beyyine Suresi' ), 99 => array( 'name' => 'Zelzele Suresi' ), 100 => array( 'name' => 'Adiat Suresi' ), 101 => array( 'name' => 'Karia Suresi' ), 102 => array( 'name' => 'Tekas&uuml;r Suresi' ), 103 => array( 'name' => 'Asr Suresi' ), 104 => array( 'name' => 'Humeze Suresi' ), 105 => array( 'name' => 'Fil Suresi' ), 106 => array( 'name' => 'Kureyş Suresi' ), 107 => array( 'name' => 'Maun Suresi' ), 108 => array( 'name' => 'Kevser Suresi' ), 109 => array( 'name' => 'Kafirun Suresi' ), 110 => array( 'name' => 'Nasr Suresi' ), 111 => array( 'name' => 'Tebbet Suresi' ), 112 => array( 'name' => 'Ihlas Suresi' ), 113 => array( 'name' => 'Felak Suresi' ), 114 => array( 'name' => 'Nas Suresi' ) );
		$surah['id'] = array( 1 => array( 'name' => 'Surah Al-Fatihah (Pembukaan)' ), 2 => array( 'name' => 'Surah Al-Baqarah (Lembu Betina' ), 3 => array( 'name' => 'Surah Ali &lsquo;Imran (Keluarga Imr' ), 4 => array( 'name' => 'Surah An-Nisaa&rsquo; (Wanita)' ), 5 => array( 'name' => 'Surah Al-Maa&rsquo;idah (Hidangan)' ), 6 => array( 'name' => 'Surah Al-An&rsquo;aam (Binatang Tern' ), 7 => array( 'name' => 'Surah Al-A&rsquo;raaf (Tempat Tertin' ), 8 => array( 'name' => 'Surah Al-Anfaal (Rampasan Pera' ), 9 => array( 'name' => 'Surah At-Taubah (Pengampunan)' ), 10 => array( 'name' => 'Surah Yunus (Nabi Yunus a.s.)' ), 11 => array( 'name' => 'Surah Hud (Nabi Hud a.s.)' ), 12 => array( 'name' => 'Surah Yusuf (Nabi Yusuf a.s.)' ), 13 => array( 'name' => 'Surah Ar-Ra&rsquo;d (Guruh)' ), 14 => array( 'name' => 'Surah Ibrahim (Nabi Ibrahim a.' ), 15 => array( 'name' => 'Surah Al-Hijr (Kawasan Berbatu' ), 16 => array( 'name' => 'Surah An-Nahl (Lebah)' ), 17 => array( 'name' => 'Surah Al-Israa&rsquo; (Perjalanan Ma' ), 18 => array( 'name' => 'Surah Al-Kahfi (Gua)' ), 19 => array( 'name' => 'Surah Maryam (Siti Maryam)' ), 20 => array( 'name' => 'Surah Taahaa' ), 21 => array( 'name' => 'Surah Al-Anbiyaa&rsquo; (Para Nabi)' ), 22 => array( 'name' => 'Surah Al-Hajj (Haji)' ), 23 => array( 'name' => 'Surah Al-Mu&rsquo;minun (Golongan ya' ), 24 => array( 'name' => 'Surah An-Nuur (Cahaya)' ), 25 => array( 'name' => 'Surah Al-Furqaan (Pembeza Kebe' ), 26 => array( 'name' => 'Surah Asy-Syu&rsquo;araa (Para Penya' ), 27 => array( 'name' => 'Surah An-Naml (Semut)' ), 28 => array( 'name' => 'Surah Al-Qasas (Cerita-cerita)' ), 29 => array( 'name' => 'Surah Al-&lsquo;Ankabut (Labah-labah' ), 30 => array( 'name' => 'Surah Ar-Rum (Bangsa Rom)' ), 31 => array( 'name' => 'Surah Luqman (Luqman)' ), 32 => array( 'name' => 'Surah As-Sajdah (Sujud)' ), 33 => array( 'name' => 'Surah Al-Ahzab (Golongan yang ' ), 34 => array( 'name' => 'Surah Saba&rsquo; (Kaum Saba&rsquo;)' ), 35 => array( 'name' => 'Surah Faatir (Pencipta)' ), 36 => array( 'name' => 'Surah Yaasin' ), 37 => array( 'name' => 'Surah As-Saaffat (Yang Teratur' ), 38 => array( 'name' => 'Surah Saad' ), 39 => array( 'name' => 'Surah Az-Zumar (Rombongan)' ), 40 => array( 'name' => 'Surah Al-Ghafir / Al-Mu&rsquo;min (O' ), 41 => array( 'name' => 'Surah Fussilat (Dijelaskan)' ), 42 => array( 'name' => 'Surah Asy-Syuraa (Permesyuarat' ), 43 => array( 'name' => 'Surah Az-Zukhruf (Perhiasan Em' ), 44 => array( 'name' => 'Surah Ad-Dukhaan (Kabut / Asap' ), 45 => array( 'name' => 'Surah Al-Jatsiyah (Yang Berlut' ), 46 => array( 'name' => 'Surah Al-Ahqaaf (Bukit-bukit P' ), 47 => array( 'name' => 'Surah Muhammad (Nabi Muhammad ' ), 48 => array( 'name' => 'Surah Al-Fath (Kemenangan)' ), 49 => array( 'name' => 'Surah Al-Hujurat (Bilik-bilik)' ), 50 => array( 'name' => 'Surah Qaaf' ), 51 => array( 'name' => 'Surah Adz-Dzariyaat (Angin yan' ), 52 => array( 'name' => 'Surah At-Tur (Bukit)' ), 53 => array( 'name' => 'Surah An-Najm (Bintang)' ), 54 => array( 'name' => 'Surah Al-Qamar (Bulan)' ), 55 => array( 'name' => 'Surah Ar-Rahman (Yang Maha Pem' ), 56 => array( 'name' => 'Surah Al-Waqi&rsquo;ah (Peristiwa ya' ), 57 => array( 'name' => 'Surah Al-Hadid (Besi)' ), 58 => array( 'name' => 'Surah Al-Mujadilah (Perempuan ' ), 59 => array( 'name' => 'Surah Al-Hasyr (Pengusiran)' ), 60 => array( 'name' => 'Surah Al-Mumtahanah (Perempuan' ), 61 => array( 'name' => 'Surah As-Saf (Barisan)' ), 62 => array( 'name' => 'Surah Al-Jumu&rsquo;ah (Hari Jumaat)' ), 63 => array( 'name' => 'Surah Al-Munafiquun (Golongan ' ), 64 => array( 'name' => 'Surah At-Taghabun (Dinampakkan' ), 65 => array( 'name' => 'Surah At-Talaq (Cerai / Talak)' ), 66 => array( 'name' => 'Surah At-Tahrim (Mengharamkan)' ), 67 => array( 'name' => 'Surah Al-Mulk (Kerajaan)' ), 68 => array( 'name' => 'Surah Al-Qalam (Pena / Kalam)' ), 69 => array( 'name' => 'Surah Al-Haaqqah (Keadaan Sebe' ), 70 => array( 'name' => 'Surah Al-Ma&rsquo;arij (Tempat-tempa' ), 71 => array( 'name' => 'Surah Nuh (Nabi Nuh a.s.)' ), 72 => array( 'name' => 'Surah A-Jin (Jin)' ), 73 => array( 'name' => 'Surah Al-Muzammil (Yang Bersel' ), 74 => array( 'name' => 'Surah Al-Muddathir (Yang Berse' ), 75 => array( 'name' => 'Surah Al-Qiyaamah (Hari Kebang' ), 76 => array( 'name' => 'Surah Al-Insaan (Manusia)' ), 77 => array( 'name' => 'Surah Al-Mursalat (Malaikat Ya' ), 78 => array( 'name' => 'Surah An-Naba&rsquo; (Berita Besar)' ), 79 => array( 'name' => 'Surah An-Naazi&rsquo;aat (Malaikat Y' ), 80 => array( 'name' => 'Surah &lsquo;Abasa (Dia Bermasam Muk' ), 81 => array( 'name' => 'Surah At-Takwiir (Menggulung)' ), 82 => array( 'name' => 'Surah Al-Infitar (Terpecah &amp; T' ), 83 => array( 'name' => 'Surah Al-Mutaffifiin (Golongan' ), 84 => array( 'name' => 'Surah Al-Insyiqaaq (Terbelah)' ), 85 => array( 'name' => 'Surah Al-Buruj (Gugusan Bintan' ), 86 => array( 'name' => 'Surah At-Taariq (Pengunjung Ma' ), 87 => array( 'name' => 'Surah Al-A&rsquo;laa (Yang Tertinggi' ), 88 => array( 'name' => 'Surah Al-Ghaasyiah (Peristiwa ' ), 89 => array( 'name' => 'Surah Al-Fajr (Fajar / Sinar M' ), 90 => array( 'name' => 'Surah Al-Balad (Negeri)' ), 91 => array( 'name' => 'Surah Asy-Syams (Matahari)' ), 92 => array( 'name' => 'Surah Al-Lail (Malam)' ), 93 => array( 'name' => 'Surah Adh-Dhuha (Pagi yang Cem' ), 94 => array( 'name' => 'Surah Al-Insyirah/An-Nasyrah (' ), 95 => array( 'name' => 'Surah At-Tin (Buah Tin / Buah ' ), 96 => array( 'name' => 'Surah Al-&lsquo;Alaq (Segumpal Darah' ), 97 => array( 'name' => 'Surah Al-Qadr (Kemuliaan)' ), 98 => array( 'name' => 'Surah Al-Baiyinah (Bukti yang ' ), 99 => array( 'name' => 'Surah Al-Zalzalah (Kegoncangan' ), 100 => array( 'name' => 'Surah Al-&lsquo;Aadiyaat (Yang Berla' ), 101 => array( 'name' => 'Surah Al-Qari&rsquo;ah (Hari Yang Hi' ), 102 => array( 'name' => 'Surah At-Takathur (Bermegah-me' ), 103 => array( 'name' => 'Surah Al-&lsquo;Asr (Masa)' ), 104 => array( 'name' => 'Surah Al-Humazah (Pengumpat)' ), 105 => array( 'name' => 'Surah Al-Fiil (Gajah)' ), 106 => array( 'name' => 'Surah Quraisy (Kaum Quraisy)' ), 107 => array( 'name' => 'Surah Al-Ma&rsquo;un (Barangan Bergu' ), 108 => array( 'name' => 'Surah Al-Kauthar (Sungai Di Sy' ), 109 => array( 'name' => 'Surah Al-Kafirun (Golongan Kaf' ), 110 => array( 'name' => 'Surah An-Nasr (Pertolongan)' ), 111 => array( 'name' => 'Surah Al-Masad / Al-Lahab (Nya' ), 112 => array( 'name' => 'Surah Al-Ikhlas (Tulus Ikhlas ' ), 113 => array( 'name' => 'Surah Al-Falaq (Waktu Subuh / ' ), 114 => array( 'name' => 'Surah An-Naas (Manusia)' ) );
		$surah['zh'] = array( 1 => array( 'name' => '开端章' ), 2 => array( 'name' => '黄牛章' ), 3 => array( 'name' => '仪姆兰的家属章' ), 4 => array( 'name' => '妇女章' ), 5 => array( 'name' => '宴席章' ), 6 => array( 'name' => '牲畜章' ), 7 => array( 'name' => '高处章' ), 8 => array( 'name' => '战利品章' ), 9 => array( 'name' => '忏悔章' ), 10 => array( 'name' => '尤努斯章' ), 11 => array( 'name' => '呼德章' ), 12 => array( 'name' => '优素福章' ), 13 => array( 'name' => '雷霆章' ), 14 => array( 'name' => '易卜拉欣章' ), 15 => array( 'name' => '石谷章' ), 16 => array( 'name' => '蜜蜂章' ), 17 => array( 'name' => '夜行章' ), 18 => array( 'name' => '山洞章' ), 19 => array( 'name' => '麦尔彦章' ), 20 => array( 'name' => '塔哈章' ), 21 => array( 'name' => '众先知章' ), 22 => array( 'name' => '朝觐章' ), 23 => array( 'name' => '信士章' ), 24 => array( 'name' => '光明章' ), 25 => array( 'name' => '准则章' ), 26 => array( 'name' => '众诗人章' ), 27 => array( 'name' => '蚂蚁章' ), 28 => array( 'name' => '故事章' ), 29 => array( 'name' => '蜘蛛章' ), 30 => array( 'name' => '罗马人章' ), 31 => array( 'name' => '鲁格曼章' ), 32 => array( 'name' => '叩头章' ), 33 => array( 'name' => '同盟军章' ), 34 => array( 'name' => '赛伯邑章' ), 35 => array( 'name' => '创造者章' ), 36 => array( 'name' => '雅辛章' ), 37 => array( 'name' => '列班者章' ), 38 => array( 'name' => '萨德章' ), 39 => array( 'name' => '队伍章' ), 40 => array( 'name' => '赦宥者章' ), 41 => array( 'name' => '奉妥来特' ), 42 => array( 'name' => '协商章' ), 43 => array( 'name' => '金饰章' ), 44 => array( 'name' => '烟雾章' ), 45 => array( 'name' => '屈膝章' ), 46 => array( 'name' => '沙丘章' ), 47 => array( 'name' => '穆罕默德章' ), 48 => array( 'name' => '胜利章' ), 49 => array( 'name' => '寝室章' ), 50 => array( 'name' => '嘎弗章' ), 51 => array( 'name' => '播种者章' ), 52 => array( 'name' => '山岳章' ), 53 => array( 'name' => '星宿章' ), 54 => array( 'name' => '月亮章' ), 55 => array( 'name' => '至仁主章' ), 56 => array( 'name' => '大事章' ), 57 => array( 'name' => '铁章' ), 58 => array( 'name' => '辩诉着章' ), 59 => array( 'name' => '放逐章' ), 60 => array( 'name' => '受考验的妇人章' ), 61 => array( 'name' => '列阵章' ), 62 => array( 'name' => '聚礼章' ), 63 => array( 'name' => '伪信者章' ), 64 => array( 'name' => '相欺章' ), 65 => array( 'name' => '离婚章' ), 66 => array( 'name' => '禁戒章' ), 67 => array( 'name' => '国权章' ), 68 => array( 'name' => '笔章' ), 69 => array( 'name' => '真灾章' ), 70 => array( 'name' => '天梯章' ), 71 => array( 'name' => '努哈章' ), 72 => array( 'name' => '精灵章' ), 73 => array( 'name' => '披衣的人章' ), 74 => array( 'name' => '盖被的人章' ), 75 => array( 'name' => '复活章' ), 76 => array( 'name' => '人章' ), 77 => array( 'name' => '天使章' ), 78 => array( 'name' => '消息章' ), 79 => array( 'name' => '急掣章' ), 80 => array( 'name' => '皱眉章' ), 81 => array( 'name' => '黯黮章' ), 82 => array( 'name' => '破裂章' ), 83 => array( 'name' => '称量不公章' ), 84 => array( 'name' => '绽裂章' ), 85 => array( 'name' => '十二宫章' ), 86 => array( 'name' => '启明星章' ), 87 => array( 'name' => '至高章' ), 88 => array( 'name' => '大灾章' ), 89 => array( 'name' => '黎明章' ), 90 => array( 'name' => '地方章' ), 91 => array( 'name' => '太阳章' ), 92 => array( 'name' => '黑夜章' ), 93 => array( 'name' => '上午章' ), 94 => array( 'name' => '开拓章' ), 95 => array( 'name' => '无花果章' ), 96 => array( 'name' => '血块章' ), 97 => array( 'name' => '高贵章' ), 98 => array( 'name' => '明证章' ), 99 => array( 'name' => '地震章' ), 100 => array( 'name' => '奔驰的马队章' ), 101 => array( 'name' => '大难章' ), 102 => array( 'name' => '竞赛富庶章' ), 103 => array( 'name' => '时光章' ), 104 => array( 'name' => '诽谤章' ), 105 => array( 'name' => '象章' ), 106 => array( 'name' => '古莱氏章' ), 107 => array( 'name' => '什物章' ), 108 => array( 'name' => '多福章' ), 109 => array( 'name' => '不信道的人们章' ), 110 => array( 'name' => '援助章' ), 111 => array( 'name' => '火焰章' ), 112 => array( 'name' => '忠诚章' ), 113 => array( 'name' => '曙光章' ), 114 => array( 'name' => '世人章' ) );
		$surah['ko'] = array( 1 => array( 'name' => '개경장' ), 2 => array( 'name' => '알-바까라(암소)장' ), 3 => array( 'name' => '알루 이므란(이므란의 가족) 장' ), 4 => array( 'name' => '안-니사(여성) 장' ), 5 => array( 'name' => '알-마이다(잘 차려진 식탁) 장' ), 6 => array( 'name' => '알-안암(가축) 장' ), 7 => array( 'name' => '알-아으라프 장' ), 8 => array( 'name' => '알-안팔(전리품) 장' ), 9 => array( 'name' => '앗-타우바(참회) 장' ), 10 => array( 'name' => '유누스(요나) 장' ), 11 => array( 'name' => '후드 장' ), 12 => array( 'name' => '유수프(요셉) 장' ), 13 => array( 'name' => '알-라으드(천둥) 장' ), 14 => array( 'name' => '이브라힘(아브라함) 장' ), 15 => array( 'name' => '알-히즈르 장' ), 16 => array( 'name' => '안-나흘(꿀벌) 장' ), 17 => array( 'name' => '알-이스라 장' ), 18 => array( 'name' => '알-카흐프(동굴) 장' ), 19 => array( 'name' => '마르얌(마리아) 장' ), 20 => array( 'name' => '따하 장' ), 21 => array( 'name' => '알-안비야(예언자들) 장' ), 22 => array( 'name' => '알-핫즈(성지순례) 장' ), 23 => array( 'name' => '알-무으미눈(신앙인들) 장' ), 24 => array( 'name' => '안-누르(빛) 장' ), 25 => array( 'name' => '알-푸르깐(분별) 장' ), 26 => array( 'name' => '앗-슈아라(시인들) 장' ), 27 => array( 'name' => '안-나믈(개미) 장' ), 28 => array( 'name' => '알-까싸쓰(이야기) 장' ), 29 => array( 'name' => '알-안카부트(거미) 장' ), 30 => array( 'name' => '알-룸(로마) 장' ), 31 => array( 'name' => '루끄만 장' ), 32 => array( 'name' => '앗-싸즈다(부복) 장' ), 33 => array( 'name' => '알-아흐잡(동맹군) 장' ), 34 => array( 'name' => '싸바 장' ), 35 => array( 'name' => '파띠르(창조자) 장' ), 36 => array( 'name' => '야씬 장' ), 37 => array( 'name' => '앗-쌋파트(대열을 갖춘 무리) 장' ), 38 => array( 'name' => '싸드 장' ), 39 => array( 'name' => '앗-주마르(무리, 떼) 장' ), 40 => array( 'name' => '알-가피르(용서하시는 분) 장' ), 41 => array( 'name' => '풋씰라트(상세히 설명된) 장' ), 42 => array( 'name' => '앗-슈라(협의회) 장' ), 43 => array( 'name' => '앗-주크루프(화려한 장식) 장' ), 44 => array( 'name' => '앗-두칸(연기) 장' ), 45 => array( 'name' => '알-자씨야(엎드린) 장' ), 46 => array( 'name' => '알-아흐까프(모래언덕) 장' ), 47 => array( 'name' => '무함마드 장' ), 48 => array( 'name' => '알-파트흐(승리) 장' ), 49 => array( 'name' => '알-후주라트(방들) 장' ), 50 => array( 'name' => '까프 장' ), 51 => array( 'name' => '앗-다리야트(흩뜨리는 바람) 장' ), 52 => array( 'name' => '앗-뚜르(산) 장' ), 53 => array( 'name' => '안-나즘(별) 장' ), 54 => array( 'name' => '알-까마르(달) 장' ), 55 => array( 'name' => '알-라흐만(자비로우신 분) 장' ), 56 => array( 'name' => '알-와끼아(피할 수 없는 날) 장' ), 57 => array( 'name' => '알-하디드(철) 장' ), 58 => array( 'name' => '알-무자딜라(탄원하는 여성) 장' ), 59 => array( 'name' => '알-하슈르(추방) 장' ), 60 => array( 'name' => '알-뭄타하나(시험받는 여성) 장' ), 61 => array( 'name' => '앗-쌋프(대열) 장' ), 62 => array( 'name' => '알-주므아(금요합동예배) 장' ), 63 => array( 'name' => '알-무나피꾼(위선자들) 장' ), 64 => array( 'name' => '앗-타가분(서로 주고받음) 장' ), 65 => array( 'name' => '앗-딸라끄(이혼) 장' ), 66 => array( 'name' => '앗-타흐림(금지) 장' ), 67 => array( 'name' => '알-물크(주권) 장' ), 68 => array( 'name' => '알-깔람(펜) 장' ), 69 => array( 'name' => '알-학까(진실) 장' ), 70 => array( 'name' => '알-마아리즈(하늘로 올라가는 길) 장' ), 71 => array( 'name' => '누흐 장' ), 72 => array( 'name' => '알-진 장' ), 73 => array( 'name' => '알-뭇잠밀(이불에 덮힌 자) 장' ), 74 => array( 'name' => '알-뭇닷씨르(담요로 덮힌 자) 장' ), 75 => array( 'name' => '알-끼야마(부활) 장' ), 76 => array( 'name' => '알-인싼(사람) 장' ), 77 => array( 'name' => '알-무르쌀라트(연이은 바람)' ), 78 => array( 'name' => '안-나바(소식) 장' ), 79 => array( 'name' => '안-나지아트(잡아끄는 자들) 장' ), 80 => array( 'name' => '아바싸(찌푸리다) 장' ), 81 => array( 'name' => '앗-타크위르(말아올림) 장' ), 82 => array( 'name' => '알-인피따르(갈라짐) 장' ), 83 => array( 'name' => '알-무땃피핀(속여 파는 자들) 장' ), 84 => array( 'name' => '알-인쉬까끄(쪼개짐) 장' ), 85 => array( 'name' => '알-부루즈(커다른 별들) 장' ), 86 => array( 'name' => '앗-따리끄(저녁에 솟는 별) 장' ), 87 => array( 'name' => '알-아을라(지고하신) 장' ), 88 => array( 'name' => '알-가쉬야(압도하는)' ), 89 => array( 'name' => '알-파즈르(여명) 장' ), 90 => array( 'name' => '알-발라드(도시) 장' ), 91 => array( 'name' => '앗-샴스(태양) 장' ), 92 => array( 'name' => '알-라일(밤) 장' ), 93 => array( 'name' => '앗-두하(아침) 장' ), 94 => array( 'name' => '앗-샤르흐(열어넓힘) 장' ), 95 => array( 'name' => '앗-틴(무화과) 장' ), 96 => array( 'name' => '알-알라끄(들러붙은 것) 장' ), 97 => array( 'name' => '알-까드르(운명) 장' ), 98 => array( 'name' => '알-바이이나(명백한 증거) 장' ), 99 => array( 'name' => '앗-잘잘라(지진) 장' ), 100 => array( 'name' => '알-아디야트(달리는 것들)장' ), 101 => array( 'name' => '알-까리야(치명적 재앙) 장' ), 102 => array( 'name' => '앗-타카쑤르(재산을 위한 경쟁)' ), 103 => array( 'name' => '알-아쓰르(시간) 장' ), 104 => array( 'name' => '알-후마자(비방하는 자) 장' ), 105 => array( 'name' => '알-필(코끼리) 장' ), 106 => array( 'name' => '꾸라이쉬 장' ), 107 => array( 'name' => '알-마운(사소한 필수품) 장' ), 108 => array( 'name' => '알-카우싸르(천국의 호수) 장' ), 109 => array( 'name' => '알-카피룬(불신자들) 장' ), 110 => array( 'name' => '안-나쓰르(도움) 장' ), 111 => array( 'name' => '알-마사드(동아줄) 장' ), 112 => array( 'name' => '알-이클라쓰(진실성) 장' ), 113 => array( 'name' => '알-팔라끄(새벽) 장' ), 114 => array( 'name' => '안-나스(인류) 장' ) );
		$surah['ml'] = array( 1 => array( 'name' => 'ഫാതിഹ' ), 2 => array( 'name' => 'ബഖറ' ), 3 => array( 'name' => 'ആലുഇംറാന്' ), 4 => array( 'name' => 'നിസാഅ്' ), 5 => array( 'name' => 'മാഇദ' ), 6 => array( 'name' => 'അന്ആം' ), 7 => array( 'name' => 'അഅ്റാഫ്' ), 8 => array( 'name' => 'അന്ഫാല്' ), 9 => array( 'name' => 'തൌബ' ), 10 => array( 'name' => 'യൂനുസ' ), 11 => array( 'name' => 'ഹൂദ്' ), 12 => array( 'name' => 'യൂസുഫ്' ), 13 => array( 'name' => 'റഅ്ദ്' ), 14 => array( 'name' => 'ഇബ്റാഹീം' ), 15 => array( 'name' => 'ഹിജ്റ്' ), 16 => array( 'name' => 'നഹ് ല്' ), 17 => array( 'name' => 'ഇസ് റാഅ്' ), 18 => array( 'name' => 'കഹ്ഫ്' ), 19 => array( 'name' => 'മറ് യം' ), 20 => array( 'name' => 'ത്വഹാ' ), 21 => array( 'name' => 'അന്പിയാ' ), 22 => array( 'name' => 'ഹജ്ജ് ' ), 23 => array( 'name' => 'മുഅ്മിനൂന്' ), 24 => array( 'name' => 'നൂറ് ' ), 25 => array( 'name' => 'ഷുഅറാ' ), 26 => array( 'name' => 'നംല്' ), 27 => array( 'name' => 'ഖസസ്' ), 28 => array( 'name' => 'അന്കബൂത്ത്' ), 29 => array( 'name' => 'റൂം' ), 30 => array( 'name' => 'ലുഖ്മാന്' ), 31 => array( 'name' => 'സജദ' ), 32 => array( 'name' => 'അഹ്സാബ്' ), 33 => array( 'name' => 'സബഅ്' ), 34 => array( 'name' => 'സബഅ്' ), 35 => array( 'name' => 'ഫാത്വിര്' ), 36 => array( 'name' => 'യാസീന്' ), 37 => array( 'name' => 'സ്വാഫാത്ത്' ), 38 => array( 'name' => 'സ്വാദ്' ), 39 => array( 'name' => 'സുമര്' ), 40 => array( 'name' => 'ഗാഫിര്' ), 41 => array( 'name' => 'ഫുസ്വിലത്ത്' ), 42 => array( 'name' => 'ഷൂറാ' ), 43 => array( 'name' => 'Az-Zukhruf ' ), 44 => array( 'name' => 'ദുഖാന്' ), 45 => array( 'name' => 'ജാസിയ' ), 46 => array( 'name' => 'അഹ്ഖാഫ്' ), 47 => array( 'name' => 'മുഹമ്മദ്' ), 48 => array( 'name' => 'ഫതഹ്' ), 49 => array( 'name' => 'ഹുജറാത്ത്' ), 50 => array( 'name' => 'ഖാഫ്' ), 51 => array( 'name' => 'ദ്ദാരിയാത്ത്' ), 52 => array( 'name' => 'ത്വൂര്' ), 53 => array( 'name' => 'നജ്മ്' ), 54 => array( 'name' => 'ഖമറ്' ), 55 => array( 'name' => 'റ്വഹ്മാന്' ), 56 => array( 'name' => 'വാഖിഅ' ), 57 => array( 'name' => 'ഹദീദ്' ), 58 => array( 'name' => 'മുജാദല' ), 59 => array( 'name' => 'ഹഷ്റ്' ), 60 => array( 'name' => 'മുംതഹിന' ), 61 => array( 'name' => 'സ്വഫ്' ), 62 => array( 'name' => 'ജുമുഅ' ), 63 => array( 'name' => 'മുനാഫിഖൂന്' ), 64 => array( 'name' => 'തഗാബുന്' ), 65 => array( 'name' => 'ത്വലാഖ്' ), 66 => array( 'name' => 'തഹ് രീം' ), 67 => array( 'name' => 'മുലക്' ), 68 => array( 'name' => 'ഖലം' ), 69 => array( 'name' => 'ഹാഖ' ), 70 => array( 'name' => 'മആരിജ്' ), 71 => array( 'name' => 'നൂഹ്' ), 72 => array( 'name' => 'ജിന്ന്' ), 73 => array( 'name' => 'മുസ്സമ്മില്' ), 74 => array( 'name' => 'മുദ്ദസിര്' ), 75 => array( 'name' => 'ഖിയാമ' ), 76 => array( 'name' => 'ഇന്സാന്' ), 77 => array( 'name' => 'മുര്സലാത്ത്' ), 78 => array( 'name' => 'നബഅ്' ), 79 => array( 'name' => 'നാസിആത്ത്' ), 80 => array( 'name' => 'അബസ' ), 81 => array( 'name' => 'തക് വീര്' ), 82 => array( 'name' => 'ഇന്ഫിത്വാര്' ), 83 => array( 'name' => 'മുതഫിഫീന്' ), 84 => array( 'name' => 'ഇന്ഷിഖാഖ്' ), 85 => array( 'name' => 'ബുറൂജ്' ), 86 => array( 'name' => 'ത്വാരിഖ്' ), 87 => array( 'name' => 'അഅ് ലാ' ), 88 => array( 'name' => 'ഗാഷിയ' ), 89 => array( 'name' => 'ഫജ്റ്' ), 90 => array( 'name' => 'ബലദ്' ), 91 => array( 'name' => 'ഷംസ്' ), 92 => array( 'name' => 'ലൈല്' ), 93 => array( 'name' => 'ദ്വുഹാ' ), 94 => array( 'name' => 'ഇന്ഷിറാഹ്' ), 95 => array( 'name' => 'തീന്' ), 96 => array( 'name' => 'അലഖ്' ), 97 => array( 'name' => 'ഖദ്റ്' ), 98 => array( 'name' => 'ബയ്യിന' ), 99 => array( 'name' => 'സല്സല' ), 100 => array( 'name' => 'ആദിയാത്ത്' ), 101 => array( 'name' => 'ഖാരിഅ' ), 102 => array( 'name' => 'തകാഥുര്' ), 103 => array( 'name' => 'അസ്വ് റ്' ), 104 => array( 'name' => 'ഹുമസ' ), 105 => array( 'name' => 'ഫീല്' ), 106 => array( 'name' => 'ഖുറൈശ്' ), 107 => array( 'name' => 'മാഊന്' ), 108 => array( 'name' => 'കൌഥര്' ), 109 => array( 'name' => 'കാഫിറൂന്' ), 110 => array( 'name' => 'നസ്വറ്' ), 111 => array( 'name' => 'മസദ്' ), 112 => array( 'name' => 'ഇഖ് ലാസ്' ), 113 => array( 'name' => 'ഫലഖ്' ), 114 => array( 'name' => 'നാസ് ' ) );
		$surah['pt'] = array( 1 => array( 'name' => 'A Abertura' ), 2 => array( 'name' => 'A Vaca' ), 3 => array( 'name' => 'A Fam&iacute;lia de Imran' ), 4 => array( 'name' => 'As Mulheres' ), 5 => array( 'name' => 'A Mesa Servida' ), 6 => array( 'name' => 'O Gado' ), 7 => array( 'name' => 'Os Cimos' ), 8 => array( 'name' => 'Os Esp&oacute;lios' ), 9 => array( 'name' => 'O Arrependimento' ), 10 => array( 'name' => 'Jonas' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Jos&eacute;' ), 13 => array( 'name' => 'O Trov&atilde;o' ), 14 => array( 'name' => 'Abra&atilde;o' ), 15 => array( 'name' => 'Alhijr' ), 16 => array( 'name' => 'As Abelhas' ), 17 => array( 'name' => 'A Viagem Noturna' ), 18 => array( 'name' => 'A Caverna' ), 19 => array( 'name' => 'Maria' ), 20 => array( 'name' => 'Ta Ha' ), 21 => array( 'name' => 'Os profetas' ), 22 => array( 'name' => 'A Peregrina&ccedil;&atilde;o' ), 23 => array( 'name' => 'Os Fi&eacute;is' ), 24 => array( 'name' => 'A luz' ), 25 => array( 'name' => 'O Discernimento' ), 26 => array( 'name' => 'Os poetas' ), 27 => array( 'name' => 'As Formigas' ), 28 => array( 'name' => 'As Narrativas' ), 29 => array( 'name' => 'A Aranha' ), 30 => array( 'name' => 'Os Bizantinos' ), 31 => array( 'name' => 'Lucman' ), 32 => array( 'name' => 'A Prosta&ccedil;&atilde;o' ), 33 => array( 'name' => 'Os Partidos' ), 34 => array( 'name' => 'Sab&aacute;' ), 35 => array( 'name' => 'O Criador' ), 36 => array( 'name' => 'Y&aacute; Sin' ), 37 => array( 'name' => 'Os Enfileirados' ), 38 => array( 'name' => 'A Letra Sad' ), 39 => array( 'name' => 'Os Grupos' ), 40 => array( 'name' => 'O Remiss&oacute;rio' ), 41 => array( 'name' => 'Os Detalhados' ), 42 => array( 'name' => 'A Consulta' ), 43 => array( 'name' => 'Os Ornamentos' ), 44 => array( 'name' => 'A Fuma&ccedil;a' ), 45 => array( 'name' => 'O Genuflexo' ), 46 => array( 'name' => 'As Dunas' ), 47 => array( 'name' => 'Mohammad' ), 48 => array( 'name' => 'O Triunfo' ), 49 => array( 'name' => 'Os Aposentos' ), 50 => array( 'name' => 'A Letra Caf' ), 51 => array( 'name' => 'Os Ventos Disseminadores' ), 52 => array( 'name' => 'O Monte' ), 53 => array( 'name' => 'A Estrela' ), 54 => array( 'name' => 'A Lua' ), 55 => array( 'name' => 'O Clemente' ), 56 => array( 'name' => 'O Eventos Inevit&aacute;vel' ), 57 => array( 'name' => 'O Ferro' ), 58 => array( 'name' => 'A Discuss&atilde;o' ), 59 => array( 'name' => 'O Desterro' ), 60 => array( 'name' => 'A Examinada' ), 61 => array( 'name' => 'As Fileiras' ), 62 => array( 'name' => 'A Sexta-Feira' ), 63 => array( 'name' => 'Os Hip&oacute;critas' ), 64 => array( 'name' => 'As Defrauda&ccedil;&otilde;es Rec&iacute;procas' ), 65 => array( 'name' => 'O Div&oacute;rcio' ), 66 => array( 'name' => 'As Proibi&ccedil;&otilde;es' ), 67 => array( 'name' => 'A Soberania' ), 68 => array( 'name' => 'O c&aacute;lamo' ), 69 => array( 'name' => 'A Realidade' ), 70 => array( 'name' => 'As Vias de Ascens&atilde;o' ), 71 => array( 'name' => 'No&eacute;' ), 72 => array( 'name' => 'Os G&ecirc;nios' ), 73 => array( 'name' => 'O Acobertado' ), 74 => array( 'name' => 'O Emantado' ), 75 => array( 'name' => 'A Ressurrei&ccedil;&atilde;o' ), 76 => array( 'name' => 'O Homem' ), 77 => array( 'name' => 'Os Enviados' ), 78 => array( 'name' => 'A Not&iacute;cia' ), 79 => array( 'name' => 'Os Arrebatadores' ), 80 => array( 'name' => 'O Austero' ), 81 => array( 'name' => 'O Enrolamento' ), 82 => array( 'name' => 'O Fendimento' ), 83 => array( 'name' => 'Os Fraudadores' ), 84 => array( 'name' => 'A Fenda' ), 85 => array( 'name' => 'As Constela&ccedil;&otilde;es' ), 86 => array( 'name' => 'O Visitante Noturno' ), 87 => array( 'name' => 'O Alt&iacute;ssimo' ), 88 => array( 'name' => 'O Evento Assolador' ), 89 => array( 'name' => 'A Aurora' ), 90 => array( 'name' => 'A Metr&oacute;pole' ), 91 => array( 'name' => 'O sol' ), 92 => array( 'name' => 'A Noite' ), 93 => array( 'name' => 'As Horas da Manh&atilde;' ), 94 => array( 'name' => 'O Conforto' ), 95 => array( 'name' => 'O Figo' ), 96 => array( 'name' => 'O Co&aacute;gulo' ), 97 => array( 'name' => 'O Decreto' ), 98 => array( 'name' => 'A Evid&ecirc;ncia' ), 99 => array( 'name' => 'O Terremoto' ), 100 => array( 'name' => 'Os Corc&eacute;is' ), 101 => array( 'name' => 'A Calamidade' ), 102 => array( 'name' => 'A Cobi&ccedil;a' ), 103 => array( 'name' => 'A Era' ), 104 => array( 'name' => 'O Difamador' ), 105 => array( 'name' => 'O Elefante' ), 106 => array( 'name' => 'Os Coraixitas' ), 107 => array( 'name' => 'Os Obs&eacute;quios' ), 108 => array( 'name' => 'A Abund&acirc;ncia' ), 109 => array( 'name' => 'Os Incr&eacute;dulos' ), 110 => array( 'name' => 'O Socorro' ), 111 => array( 'name' => 'O Esparto' ), 112 => array( 'name' => 'A Unicidade' ), 113 => array( 'name' => 'A Alvorada' ), 114 => array( 'name' => 'Os Humanos' ) );
		$surah['es'] = array( 1 => array( 'name' => '﻿La Apertura' ), 2 => array( 'name' => 'La Vaca' ), 3 => array( 'name' => 'La Familia de Imran' ), 4 => array( 'name' => 'Las Mujeres' ), 5 => array( 'name' => 'La Mesa Servida' ), 6 => array( 'name' => 'Los Ganados' ), 7 => array( 'name' => 'El Muro Divisorio' ), 8 => array( 'name' => 'Los Botines' ), 9 => array( 'name' => 'El Arrepentimiento' ), 10 => array( 'name' => 'Jon&aacute;s' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Jos&eacute;' ), 13 => array( 'name' => 'El Trueno' ), 14 => array( 'name' => 'Abraham' ), 15 => array( 'name' => 'Al-Hiyr' ), 16 => array( 'name' => 'Las Abejas' ), 17 => array( 'name' => 'El Viaje Nocturno' ), 18 => array( 'name' => 'La Caverna' ), 19 => array( 'name' => 'Mar&iacute;a' ), 20 => array( 'name' => 'Ta-Ha' ), 21 => array( 'name' => 'Los Profetas' ), 22 => array( 'name' => 'La Peregrinaci&oacute;n' ), 23 => array( 'name' => 'Los Creyentes' ), 24 => array( 'name' => 'La Luz' ), 25 => array( 'name' => 'El Criterio' ), 26 => array( 'name' => 'Los Poetas' ), 27 => array( 'name' => 'Las Hormigas' ), 28 => array( 'name' => 'El Relato' ), 29 => array( 'name' => 'La Ara&ntilde;a' ), 30 => array( 'name' => 'Los Bizantinos' ), 31 => array( 'name' => 'Luqman' ), 32 => array( 'name' => 'La Prosternaci&oacute;n' ), 33 => array( 'name' => 'Los Aliados' ), 34 => array( 'name' => 'Saba' ), 35 => array( 'name' => 'Originador' ), 36 => array( 'name' => 'ia-sin' ), 37 => array( 'name' => 'Los Ordenados en Filas' ), 38 => array( 'name' => 'Sad' ), 39 => array( 'name' => 'Los Tropeles' ), 40 => array( 'name' => 'El Remisorio' ), 41 => array( 'name' => 'Los Preceptos Detallados' ), 42 => array( 'name' => 'El Consejo' ), 43 => array( 'name' => 'Los Ornamentos de Oro' ), 44 => array( 'name' => 'El Humo' ), 45 => array( 'name' => 'La Arrodillada' ), 46 => array( 'name' => 'Las Dunas' ), 47 => array( 'name' => 'Muhammad' ), 48 => array( 'name' => 'La Victoria' ), 49 => array( 'name' => 'Los aposentos' ), 50 => array( 'name' => 'Qaf' ), 51 => array( 'name' => 'Los Vientos' ), 52 => array( 'name' => 'El Monte' ), 53 => array( 'name' => 'La Estrella' ), 54 => array( 'name' => 'La Luna' ), 55 => array( 'name' => 'El Clemente' ), 56 => array( 'name' => 'El Suceso' ), 57 => array( 'name' => 'El Hierro' ), 58 => array( 'name' => 'La Discusi&oacute;n' ), 59 => array( 'name' => 'El Destierro' ), 60 => array( 'name' => 'La Examinada' ), 61 => array( 'name' => 'Las Filas' ), 62 => array( 'name' => 'El Viernes' ), 63 => array( 'name' => 'Los Hip&oacute;critas' ), 64 => array( 'name' => 'El Desenga&ntilde;o' ), 65 => array( 'name' => 'El Divorcio' ), 66 => array( 'name' => 'La Prohibici&oacute;n' ), 67 => array( 'name' => 'El Reino' ), 68 => array( 'name' => 'El C&aacute;lamo' ), 69 => array( 'name' => 'La Verdad Inevitable' ), 70 => array( 'name' => 'Las V&iacute;as de Ascensi&oacute;n' ), 71 => array( 'name' => 'No&eacute;' ), 72 => array( 'name' => 'Los Genios' ), 73 => array( 'name' => 'El Cobijado' ), 74 => array( 'name' => 'El Envuelto en el Manto' ), 75 => array( 'name' => 'La Resurrecci&oacute;n' ), 76 => array( 'name' => 'El Hombre' ), 77 => array( 'name' => 'Los &Aacute;ngeles Enviados' ), 78 => array( 'name' => 'La Noticia' ), 79 => array( 'name' => 'Los &Aacute;ngeles Arrancadores' ), 80 => array( 'name' => 'El Ce&ntilde;o' ), 81 => array( 'name' => 'El Arrollamiento' ), 82 => array( 'name' => 'La Hendidura' ), 83 => array( 'name' => 'Los Defraudadores' ), 84 => array( 'name' => 'La Rasgadura' ), 85 => array( 'name' => 'Las Constelaciones' ), 86 => array( 'name' => 'Los Astros Nocturnos' ), 87 => array( 'name' => 'El Alt&iacute;simo' ), 88 => array( 'name' => 'El D&iacute;a Angustiante' ), 89 => array( 'name' => 'La Aurora' ), 90 => array( 'name' => 'La Ciudad' ), 91 => array( 'name' => 'El Sol' ), 92 => array( 'name' => 'La Noche' ), 93 => array( 'name' => 'La Ma&ntilde;ana' ), 94 => array( 'name' => 'La Abertura del Pecho' ), 95 => array( 'name' => 'La Higuera' ), 96 => array( 'name' => 'El Cigoto' ), 97 => array( 'name' => 'El Decreto' ), 98 => array( 'name' => 'La Evidencia' ), 99 => array( 'name' => 'El Gran Terremoto' ), 100 => array( 'name' => 'Los Corceles' ), 101 => array( 'name' => 'El D&iacute;a Aterrador' ), 102 => array( 'name' => 'La Codicia' ), 103 => array( 'name' => 'El Transcurso del Tiempo' ), 104 => array( 'name' => 'Los que se Burlan del Pr&oacute;jimo' ), 105 => array( 'name' => 'El Elefante' ), 106 => array( 'name' => 'Quraish' ), 107 => array( 'name' => 'La Ayuda M&iacute;nima' ), 108 => array( 'name' => 'Al Kauzar' ), 109 => array( 'name' => 'Los Incr&eacute;dulos' ), 110 => array( 'name' => 'El Socorro' ), 111 => array( 'name' => 'Las Fibras de Palmeras' ), 112 => array( 'name' => 'El Monote&iacute;smo' ), 113 => array( 'name' => 'La Alborada' ), 114 => array( 'name' => 'Los Hombres' ) );
		$surah['ur'] = array( 1 => array( 'name' => 'فاتحه' ), 2 => array( 'name' => 'بقره' ), 3 => array( 'name' => 'آل عمران' ), 4 => array( 'name' => 'نساء' ), 5 => array( 'name' => 'مائده' ), 6 => array( 'name' => 'أنعام' ), 7 => array( 'name' => 'أعراف' ), 8 => array( 'name' => 'أنفال' ), 9 => array( 'name' => 'توبه' ), 10 => array( 'name' => 'یونس' ), 11 => array( 'name' => 'هود' ), 12 => array( 'name' => 'یوسف' ), 13 => array( 'name' => 'رعد' ), 14 => array( 'name' => 'إبراهیم' ), 15 => array( 'name' => 'حجر' ), 16 => array( 'name' => 'نحل' ), 17 => array( 'name' => 'إسراء' ), 18 => array( 'name' => 'كهف' ), 19 => array( 'name' => 'مریم' ), 20 => array( 'name' => 'طه' ), 21 => array( 'name' => 'أنبیاء' ), 22 => array( 'name' => 'حج' ), 23 => array( 'name' => 'مؤمنون' ), 24 => array( 'name' => 'نور' ), 25 => array( 'name' => 'فرقان' ), 26 => array( 'name' => 'شعراء' ), 27 => array( 'name' => 'نمل' ), 28 => array( 'name' => 'قصص' ), 29 => array( 'name' => 'عنكبوت' ), 30 => array( 'name' => 'روم' ), 31 => array( 'name' => 'لقمان' ), 32 => array( 'name' => 'سجده' ), 33 => array( 'name' => 'أحزاب' ), 34 => array( 'name' => 'سبأ' ), 35 => array( 'name' => 'فاطر' ), 36 => array( 'name' => 'یس' ), 37 => array( 'name' => 'صافات' ), 38 => array( 'name' => 'ص' ), 39 => array( 'name' => 'زمر' ), 40 => array( 'name' => 'غافر' ), 41 => array( 'name' => 'فصلت' ), 42 => array( 'name' => 'شورى' ), 43 => array( 'name' => 'زخرف' ), 44 => array( 'name' => 'دخان' ), 45 => array( 'name' => 'جاثیه' ), 46 => array( 'name' => 'أحقاف' ), 47 => array( 'name' => 'محمد' ), 48 => array( 'name' => 'فتح' ), 49 => array( 'name' => 'حجرات' ), 50 => array( 'name' => 'ق' ), 51 => array( 'name' => 'ذاریات' ), 52 => array( 'name' => 'طور' ), 53 => array( 'name' => 'نجم' ), 54 => array( 'name' => 'قمر' ), 55 => array( 'name' => 'رحمن' ), 56 => array( 'name' => 'واقعه' ), 57 => array( 'name' => 'حدید' ), 58 => array( 'name' => 'مجادله' ), 59 => array( 'name' => 'حشر' ), 60 => array( 'name' => 'ممتحنه' ), 61 => array( 'name' => 'صف' ), 62 => array( 'name' => 'جمعه' ), 63 => array( 'name' => 'منافقون' ), 64 => array( 'name' => 'تغابن' ), 65 => array( 'name' => 'طلاق' ), 66 => array( 'name' => 'تحریم' ), 67 => array( 'name' => 'ملك' ), 68 => array( 'name' => 'قلم' ), 69 => array( 'name' => 'حاقه' ), 70 => array( 'name' => 'معارج' ), 71 => array( 'name' => 'نوح' ), 72 => array( 'name' => 'جن' ), 73 => array( 'name' => 'مزمل' ), 74 => array( 'name' => 'مدثر' ), 75 => array( 'name' => 'قیامه' ), 76 => array( 'name' => 'إنسان' ), 77 => array( 'name' => 'مرسلات' ), 78 => array( 'name' => 'نبأ' ), 79 => array( 'name' => 'نازعات' ), 80 => array( 'name' => 'عبس' ), 81 => array( 'name' => 'تكویر' ), 82 => array( 'name' => 'انفطار' ), 83 => array( 'name' => 'مطففین' ), 84 => array( 'name' => 'انشقاق' ), 85 => array( 'name' => 'بروج' ), 86 => array( 'name' => 'طارق' ), 87 => array( 'name' => 'أعلى ' ), 88 => array( 'name' => 'غاشیه' ), 89 => array( 'name' => 'فجر' ), 90 => array( 'name' => 'بلد' ), 91 => array( 'name' => 'شمس' ), 92 => array( 'name' => 'لیل' ), 93 => array( 'name' => 'ضحى' ), 94 => array( 'name' => 'شرح' ), 95 => array( 'name' => 'تین' ), 96 => array( 'name' => 'علق' ), 97 => array( 'name' => 'قدر' ), 98 => array( 'name' => 'بینه' ), 99 => array( 'name' => 'زلزله' ), 100 => array( 'name' => 'عادیات' ), 101 => array( 'name' => 'قارعه' ), 102 => array( 'name' => 'تكاثر' ), 103 => array( 'name' => 'عصر' ), 104 => array( 'name' => 'همزه' ), 105 => array( 'name' => 'فیل' ), 106 => array( 'name' => 'قریش' ), 107 => array( 'name' => 'ماعون' ), 108 => array( 'name' => 'كوثر' ), 109 => array( 'name' => 'كافرون' ), 110 => array( 'name' => 'نصر' ), 111 => array( 'name' => 'مسد' ), 112 => array( 'name' => 'إخلاص' ), 113 => array( 'name' => 'فلق' ), 114 => array( 'name' => 'ناس' ) );
		$surah['bn'] = array( 1 => array( 'name' => 'আল-ফাতিহা' ), 2 => array( 'name' => 'আল-বাকারা' ), 3 => array( 'name' => 'আলে ইমরান' ), 4 => array( 'name' => 'আন-নিসা' ), 5 => array( 'name' => 'আল-মায়েদা' ), 6 => array( 'name' => 'আল-আনআম' ), 7 => array( 'name' => 'আল-আরাফ' ), 8 => array( 'name' => 'আল-আনফাল' ), 9 => array( 'name' => 'আত-তাওবা' ), 10 => array( 'name' => 'ইউনূছ' ), 11 => array( 'name' => 'হুদ' ), 12 => array( 'name' => 'ইউসূফ' ), 13 => array( 'name' => 'আর-রাদ' ), 14 => array( 'name' => 'ইবরাহীম' ), 15 => array( 'name' => 'আল-হিজর' ), 16 => array( 'name' => 'আন-নাহল' ), 17 => array( 'name' => 'আল- ইসরা' ), 18 => array( 'name' => 'আল-কাহাফ' ), 19 => array( 'name' => 'মারইয়াম' ), 20 => array( 'name' => 'ত্বা-হা' ), 21 => array( 'name' => 'আল-আম্বিয়া' ), 22 => array( 'name' => 'আল-হজ্ব' ), 23 => array( 'name' => 'আল-মুমিনুন' ), 24 => array( 'name' => 'আন-নূর' ), 25 => array( 'name' => 'আল-ফোরকান' ), 26 => array( 'name' => 'আশ-শুআরা' ), 27 => array( 'name' => 'আন-নামল' ), 28 => array( 'name' => 'আল-কাসাস' ), 29 => array( 'name' => 'আল-আনকাবুত' ), 30 => array( 'name' => 'আর-রূম' ), 31 => array( 'name' => 'লুকমান' ), 32 => array( 'name' => 'আস-সাজদাহ' ), 33 => array( 'name' => 'আল-আহযাব' ), 34 => array( 'name' => 'সাবা' ), 35 => array( 'name' => 'ফাতির' ), 36 => array( 'name' => 'ইয়াসীন' ), 37 => array( 'name' => 'আস-সাফফাত' ), 38 => array( 'name' => 'সা-দ' ), 39 => array( 'name' => 'আয-যুমার' ), 40 => array( 'name' => 'গাফের' ), 41 => array( 'name' => 'ফুসসিলাত' ), 42 => array( 'name' => 'আশ-শুরা' ), 43 => array( 'name' => 'আয-যুখরুফ' ), 44 => array( 'name' => 'আদ-দুখান' ), 45 => array( 'name' => 'আল-জাসিয়া' ), 46 => array( 'name' => 'আল-আহকাফ' ), 47 => array( 'name' => 'মুহাম্মাদ' ), 48 => array( 'name' => 'আল-ফাতহ' ), 49 => array( 'name' => 'আল-হুজুরাত' ), 50 => array( 'name' => 'ক্বাফ' ), 51 => array( 'name' => 'আজ-জারিয়াত' ), 52 => array( 'name' => 'আত-তূর' ), 53 => array( 'name' => 'আন-নাজম' ), 54 => array( 'name' => 'আল-কামার' ), 55 => array( 'name' => 'আর-রাহমান' ), 56 => array( 'name' => 'আল-ওয়াকিয়াহ' ), 57 => array( 'name' => 'আল-হাদীদ' ), 58 => array( 'name' => 'আল-মুজাদালাহ' ), 59 => array( 'name' => 'আল-হাশর' ), 60 => array( 'name' => 'আল-মুমতাহিনাহ' ), 61 => array( 'name' => 'আস-সাফ' ), 62 => array( 'name' => 'আল-জুমুআ' ), 63 => array( 'name' => 'আল-মুনাফিকুন' ), 64 => array( 'name' => 'আত-তাগাবুন' ), 65 => array( 'name' => 'আত-তালাক' ), 66 => array( 'name' => 'আত-তাহরীম' ), 67 => array( 'name' => 'আল-মুলক' ), 68 => array( 'name' => 'আল-কলম' ), 69 => array( 'name' => 'আল-হাক্কাহ' ), 70 => array( 'name' => 'আল-মাআরিজ' ), 71 => array( 'name' => 'নূহ' ), 72 => array( 'name' => 'আল-জীন' ), 73 => array( 'name' => 'আল-মুযযাম্মিল' ), 74 => array( 'name' => 'আল-মুদ্দাসসির' ), 75 => array( 'name' => 'আল-কিয়ামাহ' ), 76 => array( 'name' => 'আল-ইনসান' ), 77 => array( 'name' => 'আল-মুরসালাত' ), 78 => array( 'name' => 'আন-নাবা' ), 79 => array( 'name' => 'আন-নাযেআত' ), 80 => array( 'name' => 'আবাসা' ), 81 => array( 'name' => 'আত-তাকবীর' ), 82 => array( 'name' => 'আল-ইনফিতার' ), 83 => array( 'name' => 'আল-মুতাফফিফীন' ), 84 => array( 'name' => 'আল-ইনশিকাক' ), 85 => array( 'name' => 'আল-বুরুজ' ), 86 => array( 'name' => 'আত-তারেক' ), 87 => array( 'name' => 'আল-আলা' ), 88 => array( 'name' => 'আল-গাশিয়াহ' ), 89 => array( 'name' => 'আল-ফাজর' ), 90 => array( 'name' => 'আল-বালাদ' ), 91 => array( 'name' => 'আশ-শামস' ), 92 => array( 'name' => 'আল-লাইল' ), 93 => array( 'name' => 'আদ-দুহা' ), 94 => array( 'name' => 'আশ শারহ' ), 95 => array( 'name' => 'আত-তীন' ), 96 => array( 'name' => 'আল-আলাক' ), 97 => array( 'name' => 'আল-কদর' ), 98 => array( 'name' => 'আল-বাইয়েনাহ' ), 99 => array( 'name' => 'আয-যালযালাহ' ), 100 => array( 'name' => 'আল-আদিয়াত' ), 101 => array( 'name' => 'আল-কারিআহ' ), 102 => array( 'name' => 'আত-তাকাসুর' ), 103 => array( 'name' => 'আল-আসর' ), 104 => array( 'name' => 'আল-হুমাযাহ' ), 105 => array( 'name' => 'আল-ফীল' ), 106 => array( 'name' => 'কুরাইশ' ), 107 => array( 'name' => 'আল-মাউন' ), 108 => array( 'name' => 'আল-কাউসার' ), 109 => array( 'name' => 'আল-কাফেরূন' ), 110 => array( 'name' => 'আন-নাসর' ), 111 => array( 'name' => 'আল-মাসাদ' ), 112 => array( 'name' => 'আল-ইখলাছ' ), 113 => array( 'name' => 'আল-ফালাক' ), 114 => array( 'name' => 'আন-নাস' ) );
		$surah['de'] = array( 1 => array( 'name' => 'al-Fatiha (Die Er&ouml;ffnende)' ), 2 => array( 'name' => 'al-Baqara (Die Kuh)' ), 3 => array( 'name' => 'Al-i Imran (Das Haus Imrans)' ), 4 => array( 'name' => 'an-Nisa (Die Frauen)' ), 5 => array( 'name' => 'al-Ma\'ida (Der Tisch)' ), 6 => array( 'name' => 'al-An\'am (Das Vieh)' ), 7 => array( 'name' => 'al-A\'raf (Die H&ouml;hen)' ), 8 => array( 'name' => 'al-Anfal (Die Beute)' ), 9 => array( 'name' => 'at-Tauba (Die Reue)' ), 10 => array( 'name' => 'Yunus (Jonas)' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Yusuf' ), 13 => array( 'name' => 'ar-Ra\'d (Der Donner)' ), 14 => array( 'name' => 'Ibrahim (Abraham)' ), 15 => array( 'name' => 'al-Hijr (Das Felsengebirge)' ), 16 => array( 'name' => 'an-Nahl (Die Bienen)' ), 17 => array( 'name' => 'al-Isra\' (Die Nachtreise)' ), 18 => array( 'name' => 'al-Kahf (Die H&ouml;hle)' ), 19 => array( 'name' => 'Maryam (Maria)' ), 20 => array( 'name' => 'Ta-Ha' ), 21 => array( 'name' => 'al-Anbiya\' (Die Propheten)' ), 22 => array( 'name' => 'Al-Hajj (Die Pilgerfahrt)' ), 23 => array( 'name' => 'al-Mu\'minun (Die Gl&auml;ubigen)' ), 24 => array( 'name' => 'an-Nur (Das Licht)' ), 25 => array( 'name' => 'al-Furqan (Die Unterscheidung)' ), 26 => array( 'name' => 'Asch-Schu\'ara (Die Dichter)' ), 27 => array( 'name' => 'an-Naml (Die Ameisen)' ), 28 => array( 'name' => 'Al-Qasas (Die Geschichten)' ), 29 => array( 'name' => 'al-\'Ankabut (Die Spinne)' ), 30 => array( 'name' => 'ar-Ruum (Die R&ouml;mer)' ), 31 => array( 'name' => 'Luqman' ), 32 => array( 'name' => 'as-Sajda (Die Niederwerfung)' ), 33 => array( 'name' => 'al-Ahzaab (Verb&uuml;ndeten)' ), 34 => array( 'name' => 'Saba\' (Die Stadt Saba\')' ), 35 => array( 'name' => 'Fatir (Der Erschaffer)' ), 36 => array( 'name' => 'Ya-Sin' ), 37 => array( 'name' => 'as-Saffat (Die sich Reihenden)' ), 38 => array( 'name' => 'Sad' ), 39 => array( 'name' => 'az-Zumar (Die Gruppen)' ), 40 => array( 'name' => 'Ghafir (Der Vergebende)' ), 41 => array( 'name' => 'Fussilat (Ausf&uuml;hrlich dargelegt)' ), 42 => array( 'name' => 'ash-Shura (Die Beratung)' ), 43 => array( 'name' => 'az-Zukhruf (Die Verzierung)' ), 44 => array( 'name' => 'ad-Dukhan (Der Rauch)' ), 45 => array( 'name' => 'al-Jathia (Die Kniende)' ), 46 => array( 'name' => 'al-Ahqaf' ), 47 => array( 'name' => 'Muhammad' ), 48 => array( 'name' => 'al-Fath (Der Sieg)' ), 49 => array( 'name' => 'al-Hujurat (Die Gem&auml;cher)' ), 50 => array( 'name' => 'Qaf' ), 51 => array( 'name' => 'adh-Dhadiyat (Die Zerst&ouml;renden)' ), 52 => array( 'name' => 'at-Tur (Der Berg)' ), 53 => array( 'name' => 'an-Najm (Der Stern)' ), 54 => array( 'name' => 'al-Qamar (Der Mond)' ), 55 => array( 'name' => 'ar-Rahman (Der Erbarmer)' ), 56 => array( 'name' => 'al-Uaqi\'a (Das eintreffende Ereignis)' ), 57 => array( 'name' => 'al-Hadid (Das Eisen)' ), 58 => array( 'name' => 'al-Mujadala (Die Diskussion)' ), 59 => array( 'name' => 'al-Haschr (Die Versammlung)' ), 60 => array( 'name' => 'al-Mumtahana (Die Gepr&uuml;fte)' ), 61 => array( 'name' => 'as-Saff (Die Reihe)' ), 62 => array( 'name' => 'al-Jumu\'a (Der Freitag)' ), 63 => array( 'name' => 'al-Munafiqun (die Heuchler)' ), 64 => array( 'name' => 'at-Taghabun (Die &Uuml;bervorteilung)' ), 65 => array( 'name' => 'at-Talaq (Die Scheidung)' ), 66 => array( 'name' => 'at-Tahrim (Das Verbot)' ), 67 => array( 'name' => 'al-Mulk (Die Herrschaft)' ), 68 => array( 'name' => 'al-Qalam (Der Stift)' ), 69 => array( 'name' => 'al-Haqqa (Die f&auml;llig Werdende)' ), 70 => array( 'name' => 'al-Ma\'arij (die Aufstiegswege)' ), 71 => array( 'name' => 'Nuh (Noah)' ), 72 => array( 'name' => 'al-Jinn' ), 73 => array( 'name' => 'al-Muzzammil (Der Eingeh&uuml;llte)' ), 74 => array( 'name' => 'al-Muddathir (Der Zugedeckte)' ), 75 => array( 'name' => 'al-Qiyama (Die Auferstehung)' ), 76 => array( 'name' => 'al-Insan (Der Mensch)' ), 77 => array( 'name' => 'al-Mursalat (Die Entsandten)' ), 78 => array( 'name' => 'an-Naba\' (Die Kunde)' ), 79 => array( 'name' => 'an-Nazi\'at (Die Entrei&szlig;enden)' ), 80 => array( 'name' => '\'Abasa (Der die Stirn runzelt)' ), 81 => array( 'name' => 'at-Takuir (Das Umschlingen)' ), 82 => array( 'name' => 'al-Infitar (Das Zerbrechen)' ), 83 => array( 'name' => 'al-Mutaffifin (Die das Ma&szlig; K&uuml;rzenden)' ), 84 => array( 'name' => 'al-Inschiqaq (Das Spalten)' ), 85 => array( 'name' => 'al-Buruj (Die T&uuml;rme)' ), 86 => array( 'name' => 'at-Tariq (Der Pochende)' ), 87 => array( 'name' => 'al-A\'la (Der H&ouml;chste)' ), 88 => array( 'name' => 'al-Ghashiya (Die &Uuml;berdeckende)' ), 89 => array( 'name' => 'al-Fajr (Die Morgend&auml;mmerung)' ), 90 => array( 'name' => 'al-Balad (Die Ortschaft)' ), 91 => array( 'name' => 'asch-Schams (Die Sonne)' ), 92 => array( 'name' => 'al-Lail (Die Nacht)' ), 93 => array( 'name' => 'ad-Duha (Die Morgenhelle)' ), 94 => array( 'name' => 'asch-Scharh (Das Auftun)' ), 95 => array( 'name' => 'at-Tin (Die Feige)' ), 96 => array( 'name' => 'al-\'Alaq (Das Anh&auml;ngsel)' ), 97 => array( 'name' => 'al-Qadar (Die Bestimmung)' ), 98 => array( 'name' => 'al-Bayyina (Der klare Beweis)' ), 99 => array( 'name' => 'az-Zalzala (Das Beben)' ), 100 => array( 'name' => 'al-\'Adiyat (Die Rennenden)' ), 101 => array( 'name' => 'al-Qari\'a (Das Verh&auml;ngnis)' ), 102 => array( 'name' => 'at-Takathur (Die Vermehrung)' ), 103 => array( 'name' => 'al-\'Asr (Das Zeitalter)' ), 104 => array( 'name' => 'al-Humaza (Der Stichler)' ), 105 => array( 'name' => 'al-Fil (Der Elefant)' ), 106 => array( 'name' => 'Quraish' ), 107 => array( 'name' => 'al-Ma\'un (Die Hilfeleistung)' ), 108 => array( 'name' => 'al-Kauthar (Der &Uuml;berfluss)' ), 109 => array( 'name' => 'al-Kafirun (Die Ungl&auml;ubigen)' ), 110 => array( 'name' => 'an-Nasr (Die Hilfe)' ), 111 => array( 'name' => 'al-Masad (Die Palmfasern)' ), 112 => array( 'name' => 'al-Ikhlas (Die Aufrichtigkeit)' ), 113 => array( 'name' => 'al-Falaq (Der Tagesanbruch)' ), 114 => array( 'name' => 'an-Nas (Die Menschen)' ) );
		$surah['fa'] = array( 1 => array( 'name' => 'فاتحه' ), 2 => array( 'name' => 'بقره' ), 3 => array( 'name' => 'آل عمران' ), 4 => array( 'name' => 'نساء' ), 5 => array( 'name' => 'مائده' ), 6 => array( 'name' => 'انعام' ), 7 => array( 'name' => 'اعراف' ), 8 => array( 'name' => 'انفال' ), 9 => array( 'name' => 'توبه' ), 10 => array( 'name' => 'يونس' ), 11 => array( 'name' => 'هود' ), 12 => array( 'name' => 'يوسف' ), 13 => array( 'name' => 'رعد' ), 14 => array( 'name' => 'ابراهيم' ), 15 => array( 'name' => 'حجر' ), 16 => array( 'name' => 'نحل' ), 17 => array( 'name' => 'اسراء' ), 18 => array( 'name' => 'كهف' ), 19 => array( 'name' => 'مريم' ), 20 => array( 'name' => 'طه' ), 21 => array( 'name' => 'انبياء' ), 22 => array( 'name' => 'حج' ), 23 => array( 'name' => 'مؤمنون' ), 24 => array( 'name' => 'نور' ), 25 => array( 'name' => 'فرقان' ), 26 => array( 'name' => 'شعراء' ), 27 => array( 'name' => 'نمل' ), 28 => array( 'name' => 'قصص' ), 29 => array( 'name' => 'عنكبوت' ), 30 => array( 'name' => 'روم' ), 31 => array( 'name' => 'لقمان' ), 32 => array( 'name' => 'سجده' ), 33 => array( 'name' => 'احزاب' ), 34 => array( 'name' => 'سبأ' ), 35 => array( 'name' => 'فاطر' ), 36 => array( 'name' => 'يس' ), 37 => array( 'name' => 'صافات' ), 38 => array( 'name' => 'ص' ), 39 => array( 'name' => 'زمر' ), 40 => array( 'name' => 'غافر' ), 41 => array( 'name' => 'فصلت' ), 42 => array( 'name' => 'شورى' ), 43 => array( 'name' => 'زخرف' ), 44 => array( 'name' => 'دخان' ), 45 => array( 'name' => 'جاثيه' ), 46 => array( 'name' => 'احقاف' ), 47 => array( 'name' => 'محمد' ), 48 => array( 'name' => 'فتح' ), 49 => array( 'name' => 'حجرات' ), 50 => array( 'name' => 'ق' ), 51 => array( 'name' => 'ذاريات' ), 52 => array( 'name' => 'طور' ), 53 => array( 'name' => 'نجم' ), 54 => array( 'name' => 'قمر' ), 55 => array( 'name' => 'رحمن' ), 56 => array( 'name' => 'واقعه' ), 57 => array( 'name' => 'حديد' ), 58 => array( 'name' => 'مجادله' ), 59 => array( 'name' => 'حشر' ), 60 => array( 'name' => 'ممتحنه' ), 61 => array( 'name' => 'صف' ), 62 => array( 'name' => 'جمعه' ), 63 => array( 'name' => 'منافقون' ), 64 => array( 'name' => 'تغابن' ), 65 => array( 'name' => 'طلاق' ), 66 => array( 'name' => 'تحريم' ), 67 => array( 'name' => 'ملك' ), 68 => array( 'name' => 'قلم' ), 69 => array( 'name' => 'حاقه' ), 70 => array( 'name' => 'معارج' ), 71 => array( 'name' => 'نوح' ), 72 => array( 'name' => 'جن' ), 73 => array( 'name' => 'مزمل' ), 74 => array( 'name' => 'مدثر' ), 75 => array( 'name' => 'قيامه' ), 76 => array( 'name' => 'انسان' ), 77 => array( 'name' => 'مرسلات' ), 78 => array( 'name' => 'نبأ' ), 79 => array( 'name' => 'نازعات' ), 80 => array( 'name' => 'عبس' ), 81 => array( 'name' => 'تكوير' ), 82 => array( 'name' => 'انفطار' ), 83 => array( 'name' => 'مطففين' ), 84 => array( 'name' => 'انشقاق' ), 85 => array( 'name' => 'بروج' ), 86 => array( 'name' => 'طارق' ), 87 => array( 'name' => 'اعلى' ), 88 => array( 'name' => 'غاشيه' ), 89 => array( 'name' => 'فجر' ), 90 => array( 'name' => 'بلد' ), 91 => array( 'name' => 'شمس' ), 92 => array( 'name' => 'ليل' ), 93 => array( 'name' => 'ضحى' ), 94 => array( 'name' => 'شرح' ), 95 => array( 'name' => 'تين' ), 96 => array( 'name' => 'علق' ), 97 => array( 'name' => 'قدر' ), 98 => array( 'name' => 'بينه' ), 99 => array( 'name' => 'زلزله' ), 100 => array( 'name' => 'عاديات' ), 101 => array( 'name' => 'قارعه' ), 102 => array( 'name' => 'تكاثر' ), 103 => array( 'name' => 'عصر' ), 104 => array( 'name' => 'همزه' ), 105 => array( 'name' => 'فيل' ), 106 => array( 'name' => 'قريش' ), 107 => array( 'name' => 'ماعون' ), 108 => array( 'name' => 'كوثر' ), 109 => array( 'name' => 'كافرون' ), 110 => array( 'name' => 'نصر' ), 111 => array( 'name' => 'مسد' ), 112 => array( 'name' => 'اخلاص' ), 113 => array( 'name' => 'فلق' ), 114 => array( 'name' => 'ناس' ) );
		$surah['ru'] = array( 1 => array( 'name' => 'Открывающая Книгу' ), 2 => array( 'name' => 'Корова' ), 3 => array( 'name' => 'Семейство Имрана' ), 4 => array( 'name' => 'Женщины' ), 5 => array( 'name' => 'Трапеза' ), 6 => array( 'name' => 'Скот' ), 7 => array( 'name' => 'Преграды' ), 8 => array( 'name' => 'Добыча' ), 9 => array( 'name' => 'Покаяние' ), 10 => array( 'name' => 'Йунус' ), 11 => array( 'name' => 'Худ' ), 12 => array( 'name' => 'Йуcуф' ), 13 => array( 'name' => 'Гром' ), 14 => array( 'name' => 'Ибpaxим' ), 15 => array( 'name' => 'Aл-Xиджp' ), 16 => array( 'name' => 'Пчелы' ), 17 => array( 'name' => 'Перенес Ночью' ), 18 => array( 'name' => 'Пещера' ), 19 => array( 'name' => 'Мapйaм' ), 20 => array( 'name' => 'Тa Xa' ), 21 => array( 'name' => 'Пророки' ), 22 => array( 'name' => 'Xaдж' ), 23 => array( 'name' => 'Верующие' ), 24 => array( 'name' => 'Свет' ), 25 => array( 'name' => 'Различение' ), 26 => array( 'name' => 'Поэты' ), 27 => array( 'name' => 'Муравьи' ), 28 => array( 'name' => 'Рассказ' ), 29 => array( 'name' => 'Паук' ), 30 => array( 'name' => 'Pумы' ), 31 => array( 'name' => 'Лукмaн' ), 32 => array( 'name' => 'Поклон' ), 33 => array( 'name' => 'Сонмы' ), 34 => array( 'name' => 'Caбa' ), 35 => array( 'name' => 'Ангелы' ), 36 => array( 'name' => 'Йa Cин' ), 37 => array( 'name' => 'Стоящие в ряд' ), 38 => array( 'name' => 'Сод' ), 39 => array( 'name' => 'Толпы' ), 40 => array( 'name' => 'Верующий' ), 41 => array( 'name' => 'Разъяснены' ), 42 => array( 'name' => 'Совет' ), 43 => array( 'name' => 'Украшения' ), 44 => array( 'name' => 'Дым' ), 45 => array( 'name' => 'Коленопреклоненная' ), 46 => array( 'name' => 'Пески' ), 47 => array( 'name' => 'Муxaммaд' ), 48 => array( 'name' => 'Победа' ), 49 => array( 'name' => 'Комнаты' ), 50 => array( 'name' => 'Кaф' ), 51 => array( 'name' => 'Рассеивающие' ), 52 => array( 'name' => 'Гора' ), 53 => array( 'name' => 'Звезда' ), 54 => array( 'name' => 'Месяц' ), 55 => array( 'name' => 'Милостивый' ), 56 => array( 'name' => 'Падающая' ), 57 => array( 'name' => 'Железо' ), 58 => array( 'name' => 'Препирательство' ), 59 => array( 'name' => 'Собрание' ), 60 => array( 'name' => 'Испытуемая' ), 61 => array( 'name' => 'Ряды' ), 62 => array( 'name' => 'аль-Джума' ), 63 => array( 'name' => 'Лицемеры' ), 64 => array( 'name' => 'Взаимное обманывание' ), 65 => array( 'name' => 'Развод' ), 66 => array( 'name' => 'Запрещение' ), 67 => array( 'name' => 'Власть' ), 68 => array( 'name' => 'Письменная трость' ), 69 => array( 'name' => 'Неизбежное' ), 70 => array( 'name' => 'Ступени' ), 71 => array( 'name' => 'Нуx' ), 72 => array( 'name' => 'Джинны' ), 73 => array( 'name' => 'Закутавшийся' ), 74 => array( 'name' => 'Завернувшийся' ), 75 => array( 'name' => 'Воскресение' ), 76 => array( 'name' => 'Человек' ), 77 => array( 'name' => 'Посылаемые' ), 78 => array( 'name' => 'Весть' ), 79 => array( 'name' => 'Вырывающие' ), 80 => array( 'name' => 'Нахмурился' ), 81 => array( 'name' => 'Скручивание' ), 82 => array( 'name' => 'Раскалывание' ), 83 => array( 'name' => 'Обвешивающие' ), 84 => array( 'name' => 'Развержение' ), 85 => array( 'name' => 'Башни' ), 86 => array( 'name' => 'Идущий ночью' ), 87 => array( 'name' => 'Высочайший' ), 88 => array( 'name' => 'Покрывающее' ), 89 => array( 'name' => 'Заря' ), 90 => array( 'name' => 'Город' ), 91 => array( 'name' => 'Солнце' ), 92 => array( 'name' => 'Ночь' ), 93 => array( 'name' => 'Утро' ), 94 => array( 'name' => 'Разве Мы не раскрыли' ), 95 => array( 'name' => 'Смоковница' ), 96 => array( 'name' => 'Сгусток' ), 97 => array( 'name' => 'Ночь предопределения' ), 98 => array( 'name' => 'Ясное знамение' ), 99 => array( 'name' => 'Землетрясение' ), 100 => array( 'name' => 'Мчащиеся' ), 101 => array( 'name' => 'Великое бедствие' ), 102 => array( 'name' => 'Страсть к приумножению' ), 103 => array( 'name' => 'Предвечернее время' ), 104 => array( 'name' => 'Хулитель' ), 105 => array( 'name' => 'Слон' ), 106 => array( 'name' => 'Куpaйш' ), 107 => array( 'name' => 'Подаяние' ), 108 => array( 'name' => 'Изобилие' ), 109 => array( 'name' => 'Неверные' ), 110 => array( 'name' => 'Помощь' ), 111 => array( 'name' => 'Пальмовые волокна' ), 112 => array( 'name' => 'Очищение (Веры)' ), 113 => array( 'name' => 'Рассвет' ), 114 => array( 'name' => 'Люди' ) );
		$surah['sq'] = array( 1 => array( 'name' => 'El Fatiha' ), 2 => array( 'name' => 'El Bekare' ), 3 => array( 'name' => 'Ali Imran' ), 4 => array( 'name' => 'En Nisa' ), 5 => array( 'name' => 'El Maide' ), 6 => array( 'name' => 'El Enam' ), 7 => array( 'name' => 'El A\'raf' ), 8 => array( 'name' => 'El Enfal' ), 9 => array( 'name' => 'Et Tevbe' ), 10 => array( 'name' => 'Junus' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Jusuf' ), 13 => array( 'name' => 'Er Rad' ), 14 => array( 'name' => 'Ibrahim' ), 15 => array( 'name' => 'El Hixhr' ), 16 => array( 'name' => 'En Nahl' ), 17 => array( 'name' => 'El Isra' ), 18 => array( 'name' => 'El Kehf' ), 19 => array( 'name' => 'Merjem' ), 20 => array( 'name' => 'Taha' ), 21 => array( 'name' => 'El Enbija' ), 22 => array( 'name' => 'El Haxh' ), 23 => array( 'name' => 'El Muminun' ), 24 => array( 'name' => 'En Nur' ), 25 => array( 'name' => 'El Furkan' ), 26 => array( 'name' => 'Esh Shuara' ), 27 => array( 'name' => 'En Neml' ), 28 => array( 'name' => 'El Kasas' ), 29 => array( 'name' => 'El Ankebut' ), 30 => array( 'name' => 'Er Rrum' ), 31 => array( 'name' => 'Lukman' ), 32 => array( 'name' => 'Es Sexhde' ), 33 => array( 'name' => 'El Ahzab' ), 34 => array( 'name' => 'Sebe\'' ), 35 => array( 'name' => 'Fatir' ), 36 => array( 'name' => 'Jasin' ), 37 => array( 'name' => 'Es Saffat' ), 38 => array( 'name' => 'Sad' ), 39 => array( 'name' => 'Zumer' ), 40 => array( 'name' => 'Gafir' ), 41 => array( 'name' => 'Fussilet' ), 42 => array( 'name' => 'Esh Shura' ), 43 => array( 'name' => 'Ez Zuhruf' ), 44 => array( 'name' => 'Ed Duhan' ), 45 => array( 'name' => 'El Xhathije' ), 46 => array( 'name' => 'El Ahkaf' ), 47 => array( 'name' => 'Muhamed' ), 48 => array( 'name' => 'El Fet-h' ), 49 => array( 'name' => 'El Huxhurat' ), 50 => array( 'name' => 'Kaf' ), 51 => array( 'name' => 'Edh Dharijat' ), 52 => array( 'name' => 'Et Tur' ), 53 => array( 'name' => 'En Nexhm' ), 54 => array( 'name' => 'El Kamer' ), 55 => array( 'name' => 'Err Rrahman' ), 56 => array( 'name' => 'El Vakia' ), 57 => array( 'name' => 'El Hadid' ), 58 => array( 'name' => 'El Muxhadele' ), 59 => array( 'name' => 'El Hashr' ), 60 => array( 'name' => 'El Mumtehine' ), 61 => array( 'name' => 'Es Saff' ), 62 => array( 'name' => 'El Xhumua' ), 63 => array( 'name' => 'El Munafikun' ), 64 => array( 'name' => 'Et Tegabun' ), 65 => array( 'name' => 'Et Talak' ), 66 => array( 'name' => 'Et Tahrim' ), 67 => array( 'name' => 'El Mulk' ), 68 => array( 'name' => 'El Kalem' ), 69 => array( 'name' => 'El Hakkah' ), 70 => array( 'name' => 'El Mearixh' ), 71 => array( 'name' => 'Nuh' ), 72 => array( 'name' => 'El Xhinn' ), 73 => array( 'name' => 'El Muzzemmil' ), 74 => array( 'name' => 'El Mudethir' ), 75 => array( 'name' => 'El Kijame' ), 76 => array( 'name' => 'Kaptina El Insan' ), 77 => array( 'name' => 'El Murselat' ), 78 => array( 'name' => 'En Nebe\'' ), 79 => array( 'name' => 'En Naziat' ), 80 => array( 'name' => 'Abese' ), 81 => array( 'name' => 'Et Tekvir' ), 82 => array( 'name' => 'El Infitar' ), 83 => array( 'name' => 'El Mutaffifin' ), 84 => array( 'name' => 'El Inshikak' ), 85 => array( 'name' => 'El Buruxh' ), 86 => array( 'name' => 'Et Tarik' ), 87 => array( 'name' => 'El A\'la' ), 88 => array( 'name' => 'El Gashije' ), 89 => array( 'name' => 'El Fexhr' ), 90 => array( 'name' => 'El Beled' ), 91 => array( 'name' => 'Esh Shems' ), 92 => array( 'name' => 'El Lejl' ), 93 => array( 'name' => 'Ed Duha' ), 94 => array( 'name' => 'Esh Sherh' ), 95 => array( 'name' => 'Et Tin' ), 96 => array( 'name' => 'El Alak' ), 97 => array( 'name' => 'El Kadr' ), 98 => array( 'name' => 'El Bejjine' ), 99 => array( 'name' => 'Ez Zelzele' ), 100 => array( 'name' => 'El Adijat' ), 101 => array( 'name' => 'El Karia' ), 102 => array( 'name' => 'Et Tekathur' ), 103 => array( 'name' => 'El Asr' ), 104 => array( 'name' => 'El Hemze' ), 105 => array( 'name' => 'El Fil' ), 106 => array( 'name' => 'Kurejsh' ), 107 => array( 'name' => 'El Maun' ), 108 => array( 'name' => 'El Kevther' ), 109 => array( 'name' => 'El Kafirun' ), 110 => array( 'name' => 'En Nasr' ), 111 => array( 'name' => 'El Mesed' ), 112 => array( 'name' => 'El Ihlas' ), 113 => array( 'name' => 'El Felek' ), 114 => array( 'name' => 'En Nas' ) );
		$surah['bs'] = array( 1 => array( 'name' => 'El Fatiha' ), 2 => array( 'name' => 'El Bekara' ), 3 => array( 'name' => 'Ali Imran' ), 4 => array( 'name' => 'En Nisa' ), 5 => array( 'name' => 'El Maida' ), 6 => array( 'name' => 'El En\'am' ), 7 => array( 'name' => 'El A\'raf' ), 8 => array( 'name' => 'El Enfal' ), 9 => array( 'name' => 'Et Tewba' ), 10 => array( 'name' => 'Junus' ), 11 => array( 'name' => 'Hud' ), 12 => array( 'name' => 'Jusuf' ), 13 => array( 'name' => 'Er Ra\'d' ), 14 => array( 'name' => 'Ibrahim' ), 15 => array( 'name' => 'El Hid&amp;#382;r' ), 16 => array( 'name' => 'En Nahl' ), 17 => array( 'name' => 'El Isra' ), 18 => array( 'name' => 'El Kehf' ), 19 => array( 'name' => 'Merjem' ), 20 => array( 'name' => 'Ta Ha' ), 21 => array( 'name' => 'El Enbija' ), 22 => array( 'name' => 'El Had&amp;#382;d&amp;#382;' ), 23 => array( 'name' => 'El Mu\'minun' ), 24 => array( 'name' => 'En Nur' ), 25 => array( 'name' => 'El Furkan' ), 26 => array( 'name' => 'E&amp;#353; &amp;#352;u\'ara' ), 27 => array( 'name' => 'En Neml' ), 28 => array( 'name' => 'El Kasas' ), 29 => array( 'name' => 'El \'Ankebut' ), 30 => array( 'name' => 'Er Rum' ), 31 => array( 'name' => 'Lukman' ), 32 => array( 'name' => 'Es Sed&amp;#382;de' ), 33 => array( 'name' => 'El Ahzab' ), 34 => array( 'name' => 'Sebe' ), 35 => array( 'name' => 'Fatir' ), 36 => array( 'name' => 'Ja Sin' ), 37 => array( 'name' => 'Es Saffat' ), 38 => array( 'name' => 'Sad' ), 39 => array( 'name' => 'Ez Zumer' ), 40 => array( 'name' => 'Gafir' ), 41 => array( 'name' => 'Fussilet' ), 42 => array( 'name' => 'E&amp;#353; &amp;#352;ura' ), 43 => array( 'name' => 'Ez Zuhruf' ), 44 => array( 'name' => 'Ed Duhan' ), 45 => array( 'name' => 'El D&amp;#382;asije' ), 46 => array( 'name' => 'El Ahkaf' ), 47 => array( 'name' => 'Muhammed' ), 48 => array( 'name' => 'El Feth' ), 49 => array( 'name' => 'El Hud&amp;#382;urat' ), 50 => array( 'name' => 'Kaf' ), 51 => array( 'name' => 'Ez Zarijat' ), 52 => array( 'name' => 'Et Tur' ), 53 => array( 'name' => 'En Ned&amp;#382;m' ), 54 => array( 'name' => 'El Kamer' ), 55 => array( 'name' => 'Er Rahman' ), 56 => array( 'name' => 'El Vakia' ), 57 => array( 'name' => 'El Hadid' ), 58 => array( 'name' => 'El Mud&amp;#382;adela' ), 59 => array( 'name' => 'El Ha&amp;#353;r' ), 60 => array( 'name' => 'El Mumtehina' ), 61 => array( 'name' => 'Es Saff' ), 62 => array( 'name' => 'El D&amp;#382;umu\'a' ), 63 => array( 'name' => 'El Munafikun' ), 64 => array( 'name' => 'Et Tegabun' ), 65 => array( 'name' => 'Et Talak' ), 66 => array( 'name' => 'Et Tahrim' ), 67 => array( 'name' => 'Mulk' ), 68 => array( 'name' => 'El Kalem' ), 69 => array( 'name' => 'El Hakka' ), 70 => array( 'name' => 'El Mea\'rid&amp;#382;' ), 71 => array( 'name' => 'Nuh' ), 72 => array( 'name' => 'El D&amp;#382;in' ), 73 => array( 'name' => 'El Muzemmil' ), 74 => array( 'name' => 'El Muddesir' ), 75 => array( 'name' => 'El Kijama' ), 76 => array( 'name' => 'El Insan' ), 77 => array( 'name' => 'El Murselat' ), 78 => array( 'name' => 'En Nebe' ), 79 => array( 'name' => 'En Nazi\'at' ), 80 => array( 'name' => '\'Abese' ), 81 => array( 'name' => 'Et Tekvir' ), 82 => array( 'name' => 'El Infitar' ), 83 => array( 'name' => 'El Mutaffifun' ), 84 => array( 'name' => 'El In&amp;#353;ikak' ), 85 => array( 'name' => 'El Burud&amp;#382;' ), 86 => array( 'name' => 'Et Tarik' ), 87 => array( 'name' => 'El \'Ala' ), 88 => array( 'name' => 'El Ga&amp;#353;ija' ), 89 => array( 'name' => 'El Fed&amp;#382;r' ), 90 => array( 'name' => 'El Beled' ), 91 => array( 'name' => 'E&amp;#353; &amp;#352;ems' ), 92 => array( 'name' => 'El Lejl' ), 93 => array( 'name' => 'Ed Duha' ), 94 => array( 'name' => 'E&amp;#353; &amp;#352;erh' ), 95 => array( 'name' => 'Et Tin' ), 96 => array( 'name' => 'El \'Alek' ), 97 => array( 'name' => 'El Kadr' ), 98 => array( 'name' => 'El Bejjine' ), 99 => array( 'name' => 'Ez Zelzele' ), 100 => array( 'name' => 'El \'Adijat' ), 101 => array( 'name' => 'El Kari\'ah' ), 102 => array( 'name' => 'Et Tekasur' ), 103 => array( 'name' => 'El \'Asr' ), 104 => array( 'name' => 'El Humeze' ), 105 => array( 'name' => 'El Fil' ), 106 => array( 'name' => 'Kurej&amp;#353;' ), 107 => array( 'name' => 'El Ma\'un' ), 108 => array( 'name' => 'El Kevser' ), 109 => array( 'name' => 'El Kafirun' ), 110 => array( 'name' => 'En Nasr' ), 111 => array( 'name' => 'El Mesed' ), 112 => array( 'name' => 'El Ihlas' ), 113 => array( 'name' => 'El Felek' ), 114 => array( 'name' => 'En Nas' ) );
		$surah['ku'] = array( 1 => array( 'name' => 'الفاتحة&zwnj;' ), 2 => array( 'name' => 'البقرة' ), 3 => array( 'name' => 'آل عمران' ), 4 => array( 'name' => 'النساء' ), 5 => array( 'name' => 'المائدة' ), 6 => array( 'name' => 'الأنعام' ), 7 => array( 'name' => 'الأعراف' ), 8 => array( 'name' => 'الأنفال' ), 9 => array( 'name' => 'التوبة' ), 10 => array( 'name' => 'يونس' ), 11 => array( 'name' => 'هود' ), 12 => array( 'name' => 'يوسف' ), 13 => array( 'name' => ' الرعد' ), 14 => array( 'name' => 'إبراهيم' ), 15 => array( 'name' => 'الحجر' ), 16 => array( 'name' => 'النحل' ), 17 => array( 'name' => 'الإسراء' ), 18 => array( 'name' => 'الكهف' ), 19 => array( 'name' => 'مريم' ), 20 => array( 'name' => 'طه' ), 21 => array( 'name' => 'الأنبياء' ), 22 => array( 'name' => 'الحج' ), 23 => array( 'name' => 'المؤمنون' ), 24 => array( 'name' => 'النور' ), 25 => array( 'name' => 'الفرقان' ), 26 => array( 'name' => 'الشعراء' ), 27 => array( 'name' => 'النمل' ), 28 => array( 'name' => 'القصص' ), 29 => array( 'name' => 'العنكبوت' ), 30 => array( 'name' => 'الروم' ), 31 => array( 'name' => 'لقمان' ), 32 => array( 'name' => 'السجدة' ), 33 => array( 'name' => 'الأحزاب' ), 34 => array( 'name' => 'سبأ' ), 35 => array( 'name' => 'فاطر' ), 36 => array( 'name' => 'يس' ), 37 => array( 'name' => 'الصافات' ), 38 => array( 'name' => 'ص' ), 39 => array( 'name' => 'الزمر' ), 40 => array( 'name' => 'غافر' ), 41 => array( 'name' => 'فصلت' ), 42 => array( 'name' => 'الشورى' ), 43 => array( 'name' => 'سوره&zwnj;تی( الزخرف' ), 44 => array( 'name' => 'الدخان' ), 45 => array( 'name' => 'الجاثية' ), 46 => array( 'name' => ' الأحقاف' ), 47 => array( 'name' => 'محمد' ), 48 => array( 'name' => 'الفتح' ), 49 => array( 'name' => 'الحجرات' ), 50 => array( 'name' => 'ق' ), 51 => array( 'name' => 'الذاريات' ), 52 => array( 'name' => 'الطور' ), 53 => array( 'name' => 'النجم' ), 54 => array( 'name' => 'القمر' ), 55 => array( 'name' => 'الرحمن' ), 56 => array( 'name' => 'الواقعة' ), 57 => array( 'name' => 'الحديد' ), 58 => array( 'name' => 'المجادلة' ), 59 => array( 'name' => 'الحشر' ), 60 => array( 'name' => 'الممتحنة' ), 61 => array( 'name' => 'الصف' ), 62 => array( 'name' => 'الجمعة' ), 63 => array( 'name' => 'المنافقون' ), 64 => array( 'name' => 'التغابن' ), 65 => array( 'name' => 'الطلاق' ), 66 => array( 'name' => 'التحریم' ), 67 => array( 'name' => 'الملك' ), 68 => array( 'name' => 'القلم' ), 69 => array( 'name' => 'الحاقة' ), 70 => array( 'name' => 'المعارج' ), 71 => array( 'name' => 'نوح' ), 72 => array( 'name' => 'الجن' ), 73 => array( 'name' => 'المزمل' ), 74 => array( 'name' => 'المدثر' ), 75 => array( 'name' => 'القيامة' ), 76 => array( 'name' => 'الإنسان' ), 77 => array( 'name' => 'المرسلات' ), 78 => array( 'name' => 'النبأ' ), 79 => array( 'name' => 'النازعات' ), 80 => array( 'name' => 'عبس' ), 81 => array( 'name' => 'التكویر' ), 82 => array( 'name' => 'الإنفطار' ), 83 => array( 'name' => ' سوره&zwnj;تی( المطففين' ), 84 => array( 'name' => 'الانشقاق' ), 85 => array( 'name' => 'البروج' ), 86 => array( 'name' => 'الطارق' ), 87 => array( 'name' => 'الأعلى' ), 88 => array( 'name' => 'الغاشية' ), 89 => array( 'name' => 'الفجر' ), 90 => array( 'name' => 'البلد' ), 91 => array( 'name' => 'الشمس' ), 92 => array( 'name' => 'اللیل' ), 93 => array( 'name' => 'الضحى' ), 94 => array( 'name' => 'الشرح' ), 95 => array( 'name' => 'التین' ), 96 => array( 'name' => 'العلق' ), 97 => array( 'name' => 'القدر' ), 98 => array( 'name' => 'البينة' ), 99 => array( 'name' => 'الزلزلة' ), 100 => array( 'name' => 'العادیات' ), 101 => array( 'name' => 'القارعة' ), 102 => array( 'name' => 'التكاثر' ), 103 => array( 'name' => 'العصر' ), 104 => array( 'name' => 'الهمزة' ), 105 => array( 'name' => 'الفیل' ), 106 => array( 'name' => 'قریش' ), 107 => array( 'name' => 'الماعون' ), 108 => array( 'name' => 'الكوثر' ), 109 => array( 'name' => 'الكافرون' ), 110 => array( 'name' => 'النصر' ), 111 => array( 'name' => 'المسد' ), 112 => array( 'name' => 'الإخلاص' ), 113 => array( 'name' => 'الفلق' ), 114 => array( 'name' => 'الناس' ) );
		$surah['th'] = array( 1 => array( 'name' => 'อัล-ฟาติหะฮฺ' ), 2 => array( 'name' => 'อัล-บะเกาะเราะฮ' ), 3 => array( 'name' => 'อาล อิมรอน' ), 4 => array( 'name' => 'อัน-นิสาอ์' ), 5 => array( 'name' => 'อัล-มาอิดะฮฺ' ), 6 => array( 'name' => 'อัล-อันอาม' ), 7 => array( 'name' => 'อัล-อะอฺรอฟ' ), 8 => array( 'name' => 'อัล-อันฟาล' ), 9 => array( 'name' => 'อัต-เตาบะฮฺ' ), 10 => array( 'name' => 'ยูนุส' ), 11 => array( 'name' => 'ฮูด' ), 12 => array( 'name' => 'ยูสุฟ' ), 13 => array( 'name' => 'อัร-เราะอฺด์' ), 14 => array( 'name' => 'อิบรอฮีม' ), 15 => array( 'name' => 'อัล-หิจญ์รฺ' ), 16 => array( 'name' => 'อัน-นะห์ลฺ' ), 17 => array( 'name' => 'อัล-อิสรออ์' ), 18 => array( 'name' => 'อัล-กะฮ์ฟฺ' ), 19 => array( 'name' => 'มัรยัม' ), 20 => array( 'name' => 'ฏอฮา' ), 21 => array( 'name' => 'อัล-อันบิยาอ์' ), 22 => array( 'name' => 'อัล-หัจญ์' ), 23 => array( 'name' => 'อัล-มุอ์มินูน' ), 24 => array( 'name' => 'อัน-นูร' ), 25 => array( 'name' => 'อัล-ฟุรกอน' ), 26 => array( 'name' => 'อัช-ชุอะรออ์' ), 27 => array( 'name' => 'อัน-นัมล์' ), 28 => array( 'name' => 'อัล-เกาะศ็อศ' ), 29 => array( 'name' => 'อัล-อันกะบูต' ), 30 => array( 'name' => 'อัร-รูม' ), 31 => array( 'name' => 'ลุกมาน' ), 32 => array( 'name' => 'อัส-สัจญ์ดะฮฺ' ), 33 => array( 'name' => 'อัล-อะห์ซาบ' ), 34 => array( 'name' => 'สะบะอ์' ), 35 => array( 'name' => 'ฟาฏิร' ), 36 => array( 'name' => 'ยาสีน' ), 37 => array( 'name' => 'อัศ-ศอฟฟาต' ), 38 => array( 'name' => 'ศอด' ), 39 => array( 'name' => 'อัซ-ซุมัร' ), 40 => array( 'name' => 'ฆอฟิร' ), 41 => array( 'name' => 'ฟุศศิลัต' ), 42 => array( 'name' => 'อัช-ชูรอ' ), 43 => array( 'name' => 'อัช-ซุครุฟ' ), 44 => array( 'name' => 'อัด-ดุคอน' ), 45 => array( 'name' => 'อัล-ญาษิยะฮฺ' ), 46 => array( 'name' => 'อัล-อะห์กอฟ' ), 47 => array( 'name' => 'มุหัมมัด' ), 48 => array( 'name' => 'อัล-ฟัตห์' ), 49 => array( 'name' => 'อัล-หุญุรอต' ), 50 => array( 'name' => 'กอฟ' ), 51 => array( 'name' => 'อัซ-ซาริยาต' ), 52 => array( 'name' => 'อัฏ-ฏูร' ), 53 => array( 'name' => 'อัน-นัจญ์มฺ' ), 54 => array( 'name' => 'อัล-เกาะมัร' ), 55 => array( 'name' => 'อัร-เราะห์มาน' ), 56 => array( 'name' => 'อัล-วากิอะฮฺ' ), 57 => array( 'name' => 'อัล-หะดีด' ), 58 => array( 'name' => 'อัล-มุญาดิละฮฺ' ), 59 => array( 'name' => 'อัล-หัชร์' ), 60 => array( 'name' => 'อัล-มุมตะหะนะฮฺ' ), 61 => array( 'name' => 'อัศ-ศ็อฟ' ), 62 => array( 'name' => 'อัล-ญุมุอะฮฺ' ), 63 => array( 'name' => 'อัล-มุนาฟิกูน' ), 64 => array( 'name' => 'อัต-ตะฆอบุน' ), 65 => array( 'name' => 'อัฏ-เฏาะลาก' ), 66 => array( 'name' => 'อัต-ตะห์รีม' ), 67 => array( 'name' => 'อัล-มุลก์' ), 68 => array( 'name' => 'อัล-เกาะลัม' ), 69 => array( 'name' => 'อัล-ห๊ากเกาะฮฺ' ), 70 => array( 'name' => 'อัล-มะอาริจญ์' ), 71 => array( 'name' => 'นูหฺ' ), 72 => array( 'name' => 'อัล-ญิน' ), 73 => array( 'name' => 'อัล-มุซซัมมิล' ), 74 => array( 'name' => 'อัล-มุดดัษษิร' ), 75 => array( 'name' => 'อัล-กิยามะฮฺ' ), 76 => array( 'name' => 'อัล-อินซาน' ), 77 => array( 'name' => 'อัล-มุรสะลาต' ), 78 => array( 'name' => 'อัน-นะบะอ์' ), 79 => array( 'name' => 'อัน-นาซิอาต' ), 80 => array( 'name' => 'อะบะสะ' ), 81 => array( 'name' => 'อัต-ตักวีร' ), 82 => array( 'name' => 'อัล-อินฟิฏอร' ), 83 => array( 'name' => 'อัล-มุฏ็อฟฟิฟีน' ), 84 => array( 'name' => 'อัล-อินชิกอก' ), 85 => array( 'name' => 'อัล-บุรูจญ์' ), 86 => array( 'name' => 'อัฏ-ฏอริก' ), 87 => array( 'name' => 'อัล-อะอฺลา' ), 88 => array( 'name' => 'อัล-ฆอชิยะฮฺ' ), 89 => array( 'name' => 'อัล-ฟัจญ์รฺ' ), 90 => array( 'name' => 'อัล-บะลัด' ), 91 => array( 'name' => 'อัช-ชัมส์' ), 92 => array( 'name' => 'อัล-ลัยล์' ), 93 => array( 'name' => 'อัฎ-ฎุหา' ), 94 => array( 'name' => 'อัช-ชัรห์' ), 95 => array( 'name' => 'อัต-ตีน' ), 96 => array( 'name' => 'อัล-อะลัก' ), 97 => array( 'name' => 'อัล-ก็อดรฺ' ), 98 => array( 'name' => 'อัล-บัยยินะฮฺ' ), 99 => array( 'name' => 'อัซ-ซัลซะละฮฺ' ), 100 => array( 'name' => 'อัล-อาดิยาต' ), 101 => array( 'name' => 'อัล-กอริอะฮฺ' ), 102 => array( 'name' => 'อัต-ตะกาษุร' ), 103 => array( 'name' => 'อัล-อัศร์' ), 104 => array( 'name' => 'อัล-ฮุมะซะฮฺ' ), 105 => array( 'name' => 'อัล-ฟีล' ), 106 => array( 'name' => 'กุร็อยช์' ), 107 => array( 'name' => 'อัล-มาอูน' ), 108 => array( 'name' => 'อัล-เกาษัร' ), 109 => array( 'name' => 'อัล-กาฟิรูน' ), 110 => array( 'name' => 'อัน-นัศร์' ), 111 => array( 'name' => 'อัล-มะสัด' ), 112 => array( 'name' => 'อัล-อิคลาศ' ), 113 => array( 'name' => 'อัล-ฟะลัก' ), 114 => array( 'name' => 'อัน-นาส' ) );
		$surah['ug'] = array( 1 => array( 'name' => 'پاتىھە' ), 2 => array( 'name' => 'بەقەرە' ), 3 => array( 'name' => 'ئال ئىمران' ), 4 => array( 'name' => 'نىسا' ), 5 => array( 'name' => 'مائىدە' ), 6 => array( 'name' => 'ئەنئام' ), 7 => array( 'name' => 'ئەئراپ' ), 8 => array( 'name' => 'ئەنپال' ), 9 => array( 'name' => 'تەۋبە' ), 10 => array( 'name' => 'يۇنۇس' ), 11 => array( 'name' => 'ھۇد' ), 12 => array( 'name' => 'يۈسۈپ' ), 13 => array( 'name' => 'رەئد' ), 14 => array( 'name' => 'ئىبراھىم' ), 15 => array( 'name' => 'ھىجر' ), 16 => array( 'name' => 'نەھل' ), 17 => array( 'name' => 'ئىسرا' ), 18 => array( 'name' => 'كەھپ' ), 19 => array( 'name' => 'مەريەم' ), 20 => array( 'name' => 'تاھا' ), 21 => array( 'name' => 'ئەنبىيا' ), 22 => array( 'name' => 'ھەج' ), 23 => array( 'name' => 'مۆمىنۇن' ), 24 => array( 'name' => 'نۇر' ), 25 => array( 'name' => 'پۇرقان' ), 26 => array( 'name' => 'شۇئەرا' ), 27 => array( 'name' => 'نەمل' ), 28 => array( 'name' => 'قەسەس' ), 29 => array( 'name' => 'ئەنكەبۇت' ), 30 => array( 'name' => 'رۇم' ), 31 => array( 'name' => 'لوقمان' ), 32 => array( 'name' => 'سەجدە' ), 33 => array( 'name' => 'ئەھزاب' ), 34 => array( 'name' => 'سەبەئ' ), 35 => array( 'name' => 'پاتىر' ), 36 => array( 'name' => 'ياسىن' ), 37 => array( 'name' => 'ساپپات' ), 38 => array( 'name' => 'ساد' ), 39 => array( 'name' => 'زۇمەر' ), 40 => array( 'name' => 'غاپىر' ), 41 => array( 'name' => 'پۇسسىلەت' ), 42 => array( 'name' => 'شۇرا' ), 43 => array( 'name' => 'زۇخرۇپ' ), 44 => array( 'name' => 'دۇخان' ), 45 => array( 'name' => 'جاسىيە' ), 46 => array( 'name' => 'ئەھقاپ' ), 47 => array( 'name' => 'مۇھەممەد' ), 48 => array( 'name' => 'پەتىھ' ), 49 => array( 'name' => 'ھۇجۇرات' ), 50 => array( 'name' => 'قاپ' ), 51 => array( 'name' => 'زارىيات' ), 52 => array( 'name' => 'تۇر' ), 53 => array( 'name' => 'نەجم' ), 54 => array( 'name' => 'قەمەر' ), 55 => array( 'name' => 'رەھمان' ), 56 => array( 'name' => 'ۋاقىئە' ), 57 => array( 'name' => 'ھەدىد' ), 58 => array( 'name' => 'مۇجادەلە' ), 59 => array( 'name' => 'ھەشر' ), 60 => array( 'name' => 'مۇمتەھىنە' ), 61 => array( 'name' => 'سەپ' ), 62 => array( 'name' => 'جۇمۇئە' ), 63 => array( 'name' => 'مۇناپىقۇن' ), 64 => array( 'name' => 'تەغابۇن' ), 65 => array( 'name' => 'تەلاق' ), 66 => array( 'name' => 'تەھرىم' ), 67 => array( 'name' => 'مۈلك' ), 68 => array( 'name' => 'قەلەم' ), 69 => array( 'name' => 'ھاققە' ), 70 => array( 'name' => 'مائارىج' ), 71 => array( 'name' => 'نۇھ' ), 72 => array( 'name' => 'جىن' ), 73 => array( 'name' => 'مۇزەممىل' ), 74 => array( 'name' => 'مۇدەسسىر' ), 75 => array( 'name' => 'قىيامەت' ), 76 => array( 'name' => 'ئىنسان' ), 77 => array( 'name' => 'مۇرسەلات' ), 78 => array( 'name' => 'نەبەئ' ), 79 => array( 'name' => 'نازىئات' ), 80 => array( 'name' => 'ئەبەسە' ), 81 => array( 'name' => 'تەكۋىر' ), 82 => array( 'name' => 'ئىنپىتار' ), 83 => array( 'name' => 'مۇتەپپىپىن' ), 84 => array( 'name' => 'ئىنشىقاق' ), 85 => array( 'name' => 'بۇرۇج' ), 86 => array( 'name' => 'تارىق' ), 87 => array( 'name' => 'ئەئلا' ), 88 => array( 'name' => 'غاشىيە' ), 89 => array( 'name' => 'پەجر' ), 90 => array( 'name' => 'بەلەد' ), 91 => array( 'name' => 'شەمس' ), 92 => array( 'name' => 'لەيل' ), 93 => array( 'name' => 'زۇھا' ), 94 => array( 'name' => 'شەرھ' ), 95 => array( 'name' => 'تىن' ), 96 => array( 'name' => 'ئەلەق' ), 97 => array( 'name' => 'قەدر' ), 98 => array( 'name' => 'بەييىنە' ), 99 => array( 'name' => 'زەلزەلە' ), 100 => array( 'name' => 'ئادىيات' ), 101 => array( 'name' => 'قارىئە' ), 102 => array( 'name' => 'تەكاسۇر' ), 103 => array( 'name' => 'ئەسر' ), 104 => array( 'name' => 'ھۈمەزە' ), 105 => array( 'name' => 'پىل' ), 106 => array( 'name' => 'قۇرەيش' ), 107 => array( 'name' => 'مائۇن' ), 108 => array( 'name' => 'كەۋسەر' ), 109 => array( 'name' => 'كاپىرۇن' ), 110 => array( 'name' => 'نەسر' ), 111 => array( 'name' => 'مەسەد' ), 112 => array( 'name' => 'ئىخلاس' ), 113 => array( 'name' => 'پەلەق' ), 114 => array( 'name' => 'ناس' ) );

		if( $all == 1 ){
			return $surah;
		}else{
			if( empty($lang_key) ){
				$lang = $this->get_language();
			}else{
				$lang = $lang_key;
			}

			if( is_array($lang) && count($lang) > 0 ){
				$get_lang = ( isset($lang[0]) ? $lang[0] : 'en' );
			}else{
				$get_lang = $lang;
			}

			if( array_key_exists( $get_lang, $surah) ){
				$q = $surah[$get_lang];
			}else{
				$q = ( in_array($get_lang, $this->child_lang) ? $surah['en'] : ( in_array($get_lang, $this->rtl_languages) ? $surah['ar'] : $surah['en'] ) );
			}

			return $q;
		}

	}

	function api_ayah_readers(){
		$reader = array();
		$reader[1] = array(
			"name_ar" => "عبدالباسط عبدالصمد",
			"name_en" => "Abdelbaset Abdulsama",
			"folder" => "https://www.everyayah.com/data/Abdul_Basit_Mujawwad_128kbps/",
			"description" => "مصحف مجود"
		);
		$reader[2] = array(
			"name_ar" => "عبدالله بصفر",
			"name_en" => "Abdullah Basfer",
			"folder" => "https://www.everyayah.com/data/Abdullah_Basfar_192kbps/",
			"description" => "جودة 192 كيلوبايت"
		);
		$reader[3] = array(
			"name_ar" => "عبدالرحمن السديس",
			"name_en" => "Abdelrahman Alsodis",
			"folder" => "https://www.everyayah.com/data/Abdurrahmaan_As-Sudais_192kbps/"
		);
		$reader[4] = array(
			"name_ar" => "ابوبكر الشاطري",
			"name_en" => "Abobaker Alshatri",
			"folder" => "https://www.everyayah.com/data/Abu%20Bakr%20Ash-Shaatree_128kbps/"
		);
		$reader[5] = array(
			"name_ar" => "احمد العجمي",
			"name_en" => "Ahmad Alajmi",
			"folder" => "https://www.everyayah.com/data/Ahmed_ibn_Ali_al-Ajamy_128kbps_ketaballah.net/",
			"description" => "جوده 128 كيلوبايت"
		);
		$reader[6] = array(
			"name_ar" => "سعد الغامدي",
			"name_en" => "Saad Aljamedi",
			"folder" => "https://www.everyayah.com/data/Ghamadi_40kbps/"
		);
		$reader[7] = array(
			"name_ar" => "هاني الرفاعي",
			"name_en" => "Hani Alrefae",
			"folder" => "https://www.everyayah.com/data/Hani_Rifai_192kbps/"
		);
		$reader[8] = array(
			"name_ar" => "محمود خليل الحصري",
			"name_en" => "Mahmoud Khalil Alhosari",
			"folder" => "https://www.everyayah.com/data/Husary_128kbps_Mujawwad/",
			"description" => "المصحف المجود"
		);
		$reader[9] = array(
			"name_ar" => "علي الحذيفي",
			"name_en" => "Ali Alhudifi",
			"folder" => "https://www.everyayah.com/data/Hudhaify_128kbps/",
			"description" => "جودة 128 كيلوبايت"
		);
		$reader[10] = array(
			"name_ar" => "ابراهيم الاخضر",
			"name_en" => "Ibrahem Alakhdar",
			"folder" => "https://www.everyayah.com/data/Ibrahim_Akhdar_32kbps/"
		);
		$reader[11] = array(
			"name_ar" => "محمد صديق المشاوي",
			"name_en" => "Mohammed seddeq Almenshawi",
			"folder" => "https://www.everyayah.com/data/Minshawy_Murattal_128kbps/",
			"description" => "المصحف المرتل"
		);
		$reader[12] = array(
			"name_ar" => "محمد الطبلاوي",
			"name_en" => "Mohammed Altablawi",
			"folder" => "https://www.everyayah.com/data/Mohammad_al_Tablaway_128kbps/"
		);
		$reader[13] = array(
			"name_ar" => "محمد أيوب",
			"name_en" => "Mohammed Ayoub",
			"folder" => "https://www.everyayah.com/data/Muhammad_Ayyoub_128kbps/"
		);
		$reader[14] = array(
			"name_ar" => "محمد جبريل",
			"name_en" => "Mohammed Jebrel",
			"folder" => "https://www.everyayah.com/data/Muhammad_Jibreel_128kbps/"
		);
		$reader[15] = array(
			"name_ar" => "سعود الشريم",
			"name_en" => "Saoud Alshorim",
			"folder" => "https://www.everyayah.com/data/Saood%20bin%20Ibraaheem%20Ash-Shuraym_128kbps/"
		);
		$reader[16] = array(
			"name_ar" => "مشاري العفاسي",
			"name_en" => "Mshari Alefasi",
			"folder" => 'https://www.everyayah.com/data/Alafasy_128kbps/', //http://www.quran-for-all.com/sound/versebyverse/alafasy/
			"description" => "من الموقع www.quran-for-all.com"
		);
		return $reader;
	}

	function api_readers(){
		$reader = array();
		$reader[1] = array(
			"name_ar" => "عبدالله بصفر",
			"name_en" => "Abdullah Basfar",
			"folder" => "https://server6.mp3quran.net/bsfr/",
			"description_ar" => "المصحف المرتل للقارئ عبد الله بصفر"
		);
		$reader[2] = array(
			"name_ar" => "محمد عبدالكريم",
			"name_en" => "Shaikh Mohammed Abdul Kareem",
			"folder" => "https://server12.mp3quran.net/m_krm/",
			"description_ar" => "المصحف المرتل للقارئ محمد عبد الكريم: تم تسجيله في السودان عام 2007 م.",
			"description_en" => "Shaikh Mohammed Abdul Kareem's recitation of the Quran, which was recorded in Sudan in 2007. This version is characterized by a high quality [MP3: 128 Kbps]"
		);
		$reader[3] = array(
			"name_ar" => "عبدالرشيد صوفي",
			"name_en" => "Abdul Rasheed Sufi",
			"folder" => "https://server9.mp3quran.net/soufi_klf/",
			"description_ar" => "المصحف المرتل بصوت القارئ عبد الرشيد صوفي برواية حفص عن عاصم، والذي يمتاز بجودة صوتية عالية بتقنية [Mp3 128 Kb].",
			"description_en" => "Reciter Abdul Rasheed Sufi's recitation of the Quran with rewayah Hafs from Asim This version is characterized by a high quality [MP3: 128 Kbps]."
		);
		$reader[4] = array(
			"name_ar" => "أحمد نعينع",
			"name_en" => "Ahmad Ahmad Nuaina",
			"folder" => "https://server11.mp3quran.net/ahmad_nu/",
			"description_ar" => "المصحف المرتل للقارئ أحمد نعينع برواية حفص عن عاصم، والذي يمتاز بجودة صوتية عالية بتقنية [Mp3 128 Kb].",
			"description_en" => "Riciter Ahmad Ahmad Nuaina's recitation of the Quran with riwayah Hafs from Asim, This version is characterized by a high quality [MP3: 128 Kbps]."
		);
		$reader[5] = array(
			"name_ar" => "محمود علي البنا",
			"name_en" => "Shaikh Mahmoud Ali al-Banna",
			"folder" => "https://server8.mp3quran.net/bna/",
			"description_ar" => "المصحف المرتل للقارئ محمود علي البنا، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Shaikh al-Banna (may Allah have mercy on him) is one of the proficient and skillful reciters. This version is characterized by a high quality [MP3 128 Kbps]"
		);
		$reader[6] = array(
			"name_ar" => "ياسر الدوسري",
			"name_en" => "Shaikh Yaser Al-Dawsari",
			"folder" => "https://server11.mp3quran.net/yasser/",
			"description_ar" => "المصحف المرتل للقارئ ياسر الدوسري، والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ] وهذا المصحف هو المصحف الرسمي المعتمد",
			"description_en" => "Shaikh Yaser Al-Dawsari's recitation of the Quran. He is an imam of the Mosque, Riyadh. He is a distinguished reciter with beautiful voice. This version is characterized by a high quality [MP3: 128 Kbps]"
		);
		$reader[7] = array(
			"name_ar" => "سعود الشريم",
			"name_en" => "Shaikh Saud Al-Shuraim",
			"folder" => "https://server7.mp3quran.net/shur/",
			"description_ar" => "المصحف المرتل للقارئ سعود الشريم، والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ]، وتم تسجيله من صلاة التروايح بالمسجد الحرام",
			"description_en" => "Complete Recited Mushaf for Reciter Imam and Khateeb Masjid Al-Haraam Shaikh Saud Al-Shuraim, [High Quality mp3 128 format]."
		);
		$reader[8] = array(
			"name_ar" => "ماهر المعيقلي",
			"name_en" => "Maher Bin Hamd Al-Muayqili",
			"folder" => "https://server12.mp3quran.net/maher/",
			"description_ar" => "المصحف المرتل للقارئ ماهر المعيقلي والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb",
			"description_en" => "Recited Mushaf for Reciter Maher Bin Hamd Al-Muayqili [High Quality mp3 128 format]."
		);
		$reader[9] = array(
			"name_ar" => "خالد القحطاني",
			"name_en" => "Khalid Al-Qahtani",
			"folder" => "https://server10.mp3quran.net/qht/",
			"description_ar" => "المصحف المرتل للقارئ خالد القحطاني ، والذي يمتاز بجودة صوتية عالية بتقنية [Mp3 128 Kb].",
			"description_en" => "Recited Mushaf for Reciter Khalid Al-Qahtani [High Quality mp3 128 format]."
		);
		$reader[10] = array(
			"name_ar" => "عبدالله خياط",
			"name_en" => "Abdullah Khayyat",
			"folder" => "https://server12.mp3quran.net/kyat/",
			"description_ar" => "المصحف المرتل للقارئ عبد الله خياط، والذي يمتاز بجودة صوتية عالية بتقنية mp3 128.",
			"description_en" => "Recited Mushaf for Reciter Abdullah Khayyat [High Quality mp3 128 format]."
		);
		$reader[11] = array(
			"name_ar" => "صلاح الهاشم",
			"name_en" => "Shaikh Salah Alhashim",
			"folder" => "https://server12.mp3quran.net/salah_hashim_m/",
			"description_ar" => "المصحف المرتل للقارئ صلاح الهاشم والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ].",
			"description_en" => "Shaikh Salah Alhashim's recitation of the Quran. He is an imam of the Grand Mosque, Kuwait. He is a distinguished reciter with beautiful voice. This version is characterized by a high quality [MP3: 128 Kbps]"
		);
		$reader[12] = array(
			"name_ar" => "عبدالودود حنيف",
			"name_en" => "Abdul Wadood Maqbool Haneef",
			"folder" => "https://server8.mp3quran.net/wdod/",
			"description_ar" => "المصحف المرتل للقارئ عبد الودود مقبول حنيف، والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ].",
			"description_en" => "Recited Mushaf for Reciter Abdul Wadood Maqbool Haneef [High Quality mp3 128 format]."
		);
		$reader[13] = array(
			"name_ar" => "أحمد العجمي",
			"name_en" => "Ahmad Bin Ali Al-Ajmi",
			"folder" => "https://server10.mp3quran.net/ajm/128/",
			"description_ar" => "المصحف المرتل للقارئ أحمد بن علي العجمي، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Ahmad Bin Ali Al-Ajmi [High Quality mp3 128 format]."
		);
		$reader[14] = array(
			"name_ar" => "فارس عباد",
			"name_en" => "Fares Abbad",
			"folder" => "https://server8.mp3quran.net/frs_a/",
			"description_ar" => "المصحف المرتل للقارئ فارس عبّاد، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Fares Abbad [High Quality mp3 128 format]."
		);
		$reader[15] = array(
			"name_ar" => "هاني الرفاعي",
			"name_en" => "Hani Al-Rifai",
			"folder" => "https://server8.mp3quran.net/hani/",
			"description_ar" => "المصحف المرتل للقارئ هاني الرفاعي، والذي يمتاز بجودة صوتية عالية بتقنية mp3 128.",
			"description_en" => "Recited Mushaf for Reciter Hani Al-Rifai, and which has the advantage of high-quality audio technology mp3 128"
		);
		$reader[16] = array(
			"name_ar" => "سهل بن زين ياسين",
			"name_en" => "Sahl Bin Zain Yaseen",
			"folder" => "https://server6.mp3quran.net/shl/",
			"description_ar" => "المصحف المرتل للقارئ سهل بن زين ياسين، والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ].",
			"description_en" => "Recited Mushaf for Reciter Sahl Bin Zain Yaseen [High Quality mp3 128 format]."
		);
		$reader[17] = array(
			"name_ar" => "علي عبدالله جابر",
			"name_en" => "Ali Abdullah Jaber",
			"folder" => "https://server11.mp3quran.net/a_jbr/",
			"description_ar" => "المصحف المرتل للقارئ الشيخ علي بن عبد الله بن علي جابر - إمام المسجد الحرام سابقاً رحمه الله - والمسجل في أحد المعامل الصوتية في كندا عام 1403هـ ، وهذا التسجيل من النسخة الأصلية عالية النقاء وبجودة صوتية عالية بتقنية MP3 وبدقة 128 كيلوبت في الثانية ، وبفضل الله حصلنا على نسخة منه بالتعاون مع موقع الشيخ - رحمه الله - www.alijaber.net",
			"description_en" => "Recited Mushaf for Reciter Ali Abdullah Jaber and it has been recorded in one of the studios of Canada."
		);
		$reader[18] = array(
			"name_ar" => "محمد أيوب",
			"name_en" => "Muhammad Ayyob",
			"folder" => "https://server8.mp3quran.net/ayyub/",
			"description_ar" => "المصحف المرتل للقارئ محمد أيوب، والذي يمتاز بجودة صوتية عالية بتقنية [ Mp3 128 Kb ].",
			"description_en" => "Recited Mushaf for Reciter Muhammad Ayyob [High Quality mp3 128 format]."
		);
		$reader[19] = array(
			"name_ar" => "سعد الغامدي",
			"name_en" => "Saad Al-Gamdi",
			"folder" => "https://server7.mp3quran.net/s_gmd/",
			"description_ar" => "المصحف المرتل بصوت القارئ سعد الغامدي، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 kb.",
			"description_en" => "Recited Mushaf for Reciter Saad Al-Gamdi [High Quality mp3 128 format]."
		);
		$reader[20] = array(
			"name_ar" => "صلاح بو خاطر",
			"name_en" => "Salah Bu Khater",
			"folder" => "https://server8.mp3quran.net/bu_khtr/",
			"description_ar" => "المصحف المرتل للقارئ صلاح بو خاطر، والذي يمتاز بجودة صوتية عالية بتقنية mp3 128.",
			"description_en" => "Recited Mushaf for Reciter Salah Bu Khater [High Quality mp3 128 format]."
		);
		$reader[21] = array(
			"name_ar" => "عبد الله بن عواد الجهني",
			"name_en" => "Abdullah bin Awwad Al-Juhany",
			"folder" => "https://server13.mp3quran.net/jhn/",
			"description_ar" => "المصحف المرتل للقارئ عبد الله بن عواد الجهني، والذي يمتاز بجودة صوتية عالية بتقنية mp3 128.",
			"description_en" => "Recited Mushaf for Reciter and the Imam of the Holy Mosque in Mekka Abdullah bin Awwad Al-Juhany [High Quality mp3 128 format]."
		);
		$reader[22] = array(
			"name_ar" => "أبو بكر الشاطري",
			"name_en" => "Shaikh AbuBakr As-Shatery",
			"folder" => "https://server11.mp3quran.net/shatri/",
			"description_ar" => "المصحف المرتل للقارئ شيخ أبو بكر الشاطري والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Shaikh AbuBakr As-Shatery [High Quality mp3 128 format]."
		);
		$reader[23] = array(
			"name_ar" => "محمد صديق المنشاوي",
			"name_en" => "Muhammad Siddiq Al-Manshawi",
			"folder" => "https://server10.mp3quran.net/minsh/",
			"description_ar" => "المصحف المجود للقارئ محمد صديق المنشاوي، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Muhammad Siddiq Al-Manshawi, [High Quality mp3 128 format]."
		);
		$reader[24] = array(
			"name_ar" => "محمود خليل الحصري",
			"name_en" => "Mahmood Khaleel Al-Husari",
			"folder" => "https://server13.mp3quran.net/husr/",
			"description_ar" => "المصحف المرتل للقارئ محمود خليل الحصري، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Mahmood Khaleel Al-Husari, [High Quality mp3 128 format]."
		);
		$reader[25] = array(
			"name_ar" => "عبدالباسط عبدالصمد",
			"name_en" => "Abdul Basit Abdus Samad",
			"folder" => "https://server13.mp3quran.net/basit_mjwd/",
			"description_ar" => "المصحف المرتل للقارئ عبد الباسط عبد الصمد، والذي يمتاز بجودة صوتية عالية بتقنية Mp3 128 Kb.",
			"description_en" => "Recited Mushaf for Reciter Abdul Basit Abdus Samad, [High Quality mp3 128 format]."
		);
		$reader[26] = array(
			"name_ar" => "عبدالعزيز الأحمد",
			"name_en" => "AbdulAzeez al-Ahmad",
			"folder" => "https://download.quranicaudio.com/quran/abdulazeez_al-ahmad/"
		);
		$reader[27] = array(
			"name_ar" => "عبدالرحمن السديس",
			"name_en" => "Abdur-Rahman as-Sudais",
			"folder" => "https://download.quranicaudio.com/quran/abdurrahmaan_as-sudays/"
		);
		$reader[28] = array(
			"name_ar" => "علي الحذيفي",
			"name_en" => "Ali Abdur-Rahman al-Huthaify",
			"folder" => "https://download.quranicaudio.com/quran/huthayfi/"
		);
		$reader[29] = array(
			"name_ar" => "حمد سنان",
			"name_en" => "Hamad Sinan",
			"folder" => "https://download.quranicaudio.com/quran/hamad_sinan/"
		);
		$reader[30] = array(
			"name_ar" => "ابراهيم الجبرين",
			"name_en" => "Ibrahim Al-Jibrin",
			"folder" => "https://download.quranicaudio.com/quran/jibreen/"
		);
		$reader[31] = array(
			"name_ar" => "محمد المحيسني",
			"name_en" => "Muhammad al-Mehysni",
			"folder" => "https://download.quranicaudio.com/quran/mehysni/"
		);
		$reader[32] = array(
			"name_ar" => "صالح آل طالب",
			"name_en" => "Saleh al Taleb",
			"folder" => "https://download.quranicaudio.com/quran/saleh_al_taleb/"
		);
		$reader[33] = array(
			"name_ar" => "عبدالباري الثبيتي",
			"name_en" => "AbdulBari ath-Thubaity",
			"folder" => "https://download.quranicaudio.com/quran/thubaity/"
		);
		$reader[34] = array(
			"name_ar" => "عادل الكلباني",
			"name_en" => "Adel Kalbani",
			"folder" => "https://download.quranicaudio.com/quran/adel_kalbani/"
		);
		$reader[35] = array(
			"name_ar" => "محمد اللحيدان",
			"name_en" => "Muhammad al-Luhaidan",
			"folder" => "https://download.quranicaudio.com/quran/muhammad_alhaidan/"
		);
		$reader[36] = array(
			"name_ar" => "صلاح البدير",
			"name_en" => "Salah al-Budair",
			"folder" => "https://download.quranicaudio.com/quran/salahbudair/"
		);
		$reader[37] = array(
			"name_ar" => "مشاري العفاسي",
			"name_en" => "Mshari Alefasi",
			"folder" => 'https://download.tvquran.com/download/TvQuran.com__Alafasi/', //http://www.quran-for-all.com/sound/mushary_alafasy/
			"description_ar" => "من موقع tvquran.com",
			"description_en" => "From tvquran.com"
		);
		return $reader;
	}

	function get_folder_translate( $lang_name = '', $surah_id = 1, $root = false ){
		$QURAN = '';
		$path = $this->addRootPath( $root );
		if( empty($lang_name) ){
			$lang = $this->get_language();
			$file = ( in_array( $lang, $this->check_languages ) ? $lang : 'en' );
			if( file_exists($path.$this->folder_translate.'/'.$file.'/'.$surah_id.'.php') ){
				$current_path = $path.$this->folder_translate.'/'.$file.'/'.$surah_id.'.php';
			}else{
				$current_path = false;
			}
		}else{
			$lang = $lang_name;
			$file = ( in_array( $lang, $this->check_languages ) ? $lang : 'en' );
			if( file_exists($path.$this->folder_translate.'/'.$file.'/'.$surah_id.'.php') ){
				$current_path = $path.$this->folder_translate.'/'.$file.'/'.$surah_id.'.php';
			}else{
				$current_path = false;
			}
		}
		return $current_path;
	}

	function get_tafseer_include( $tafseer_id='', $surah_id='', $ayah_id='', $root = false ){
		$sora = $this->surah_id();
		if( !empty($surah_id) ){
			$sora = $surah_id;
		}
		$type = $this->tafseer_id();
		if( !empty($tafseer_id) ){
			$type = $tafseer_id;
		}
		$aya = $this->ayah_id();
		if( !empty($ayah_id) ){
			$aya = $ayah_id;
		}

		$path = $this->addRootPath( $root );

		if( file_exists($path.$this->folder_tafseer.'/'.$type.'/'.$sora.'.php') ){
			$current_path = $path.$this->folder_tafseer.'/'.$type.'/'.$sora.'.php';
		}else{
			$current_path = false;
		}

		return $current_path;
	}

	function api_languages(){
		$lang_info = array();

		$lang_info['ar'] = array(
			'id' => '1',
			'name' => 'العربية',
			'name_ar' => 'العربية',
			'name_en' => 'Arabic',
			'book' => 'https://www.muslim-library.com/books/ar_altafsier_almoiasar.pdf',
			'sound' => 'https://server8.mp3quran.net/afs/',
			'file' => '',
			'source' => 'ar',
			'lang' => 'ar'
		);

		$lang_info['en'] = array(
			'id' => '1',
			'name' => 'English',
			'name_ar' => 'إنجليزي - صحيح انترناشيونال',
			'name_en' => 'English - Sahih International',
			'book' => 'https://www.muslim-library.com/dl/books/English_Translation_of_the_Holy_Quran_meanings_in_English.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/English/Mishary_Al-Afasy/',
			'file' => 'English_Sahih_International',
			'source' => 'en',
			'lang' => 'en'
		);

		$lang_info['en_yusuf_ali'] = array(
			'id' => '2',
			'name' => 'English - Yusuf Ali',
			'name_ar' => 'إنجليزي - يوسف علي',
			'name_en' => 'English - Yusuf Ali',
			'book' => 'http://www.qurandownload.com/english-quran-with-commentaries(yusuf-ali).pdf',
			'sound' => 'https://www.qurantranslations.net/sound/English/Mishary_Al-Afasy/',
			'file' => 'English_Yusuf_Ali',
			'source' => 'en_yusuf_ali',
			'lang' => 'en'
		);

		$lang_info['en_transliteration'] = array(
			'id' => '3',
			'name' => 'English - Transliteration',
			'name_ar' => 'إنجليزي معرّب',
			'name_en' => 'English - Transliteration',
			'book' => 'http://www.qurandownload.com/quran-transliteration.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/English/Mishary_Al-Afasy/',
			'file' => 'English_Transliteration',
			'source' => 'en_transliteration',
			'lang' => 'en'
		);

		$lang_info['fr'] = array(
			'id' => '4',
			'name' => 'Français',
			'name_ar' => 'فرنسي',
			'name_en' => 'French',
			'file' => 'French',
			'book' => 'https://www.muslim-library.com/dl/books/fr1250.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/French/Abdour-Rahman_Al-Houdhaifi/',
			'source' => 'fr',
			'lang' => 'fr'
		);

		$lang_info['nl'] = array(
			'id' => '5',
			'name' => 'Nederlands',
			'name_ar' => 'هولندي',
			'name_en' => 'Dutch',
			'file' => 'Dutch',
			'book' => 'http://www.qurandownload.com/dutch-quran.pdf',
			'sound' => '',
			'source' => 'nl',
			'lang' => 'nl'
		);

		$lang_info['tr'] = array(
			'id' => '6',
			'name' => 'Türkçe',
			'name_ar' => 'تركي',
			'name_en' => 'Turkish',
			'file' => 'Turkish',
			'book' => 'https://www.muslim-library.com/dl/books/tr1111.pdf',
			'sound' => '',
			'source' => 'tr',
			'lang' => 'tr'
		);

		$lang_info['ms'] = array(
			'id' => '7',
			'name' => 'Melayu',
			'name_ar' => 'ماليزي',
			'name_en' => 'Malay',
			'file' => 'Malay',
			'book' => 'https://www.muslim-library.com/dl/books/ms2392.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Malawi/Holy_Quran_in_the_Malawi_Language/',
			'found_files' => array(58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'ms',
			'lang' => 'ms'
		);

		$lang_info['id'] = array(
			'id' => '8',
			'name' => 'Indonesia',
			'name_ar' => 'اندونيسي',
			'name_en' => 'Indonesian',
			'file' => 'Indonesian',
			'book' => 'https://www.muslim-library.com/dl/books/in4144.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Indonesian/Holy_Quran_in_the_Indonesia_Language_Mashari/',
			'source' => 'id',
			'lang' => 'id'
		);

		$lang_info['zh'] = array(
			'id' => '9',
			'name' => '中文',
			'name_ar' => 'صيني',
			'name_en' => 'Chinese',
			'file' => 'Chinese',
			'book' => 'https://www.muslim-library.com/dl/books/ch4165.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Chinese/Holy_Quran_in_the_Chinese_Language_1/',
			'source' => 'zh',
			'lang' => 'zh'
		);

		$lang_info['ja'] = array(
			'id' => '10',
			'name' => '日本語',
			'name_ar' => 'ياباني',
			'name_en' => 'Japanese',
			'file' => 'Japanese',
			'book' => 'https://www.muslim-library.com/dl/books/jp4166.pdf',
			'sound' => '',
			'source' => 'ja',
			'lang' => 'ja'
		);

		$lang_info['it'] = array(
			'id' => '11',
			'name' => 'Italiano',
			'name_ar' => 'ايطالي',
			'name_en' => 'Italian',
			'file' => 'Italian',
			'book' => 'https://www.muslim-library.com/dl/books/it4137.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Italiano/Italiano-e-arabo/',
			'found_files' => array(1,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'it',
			'lang' => 'it'
		);

		$lang_info['ko'] = array(
			'id' => '12',
			'name' => '한국어',
			'name_ar' => 'كوري',
			'name_en' => 'Korean',
			'file' => 'Korean',
			'book' => 'https://www.muslim-library.com/dl/books/ko4167.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Korean/Holy_Quran_in_the_Korean_Language/',
			'source' => 'ko',
			'lang' => 'ko'
		);

		$lang_info['ml'] = array(
			'id' => '13',
			'name' => 'മലയാളം',
			'name_ar' => 'مالايالام',
			'name_en' => 'Malayalam',
			'file' => 'Malayalam',
			'book' => 'https://www.qurandownload.com/malayalam-quran-t2.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Hindi/Holy_Quran_in_the_Malayalam_Language/',
			'source' => 'ml',
			'lang' => 'ml'
		);

		$lang_info['pt'] = array(
			'id' => '14',
			'name' => 'Português',
			'name_ar' => 'برتغالي',
			'name_en' => 'Portuguese',
			'file' => 'Portuguese',
			'book' => 'https://www.muslim-library.com/dl/books/po4138.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Portuguese/Saad_Al-Ghamdi/',
			'source' => 'pt',
			'lang' => 'pt'
		);

		$lang_info['es'] = array(
			'id' => '15',
			'name' => 'Español',
			'name_ar' => 'إسباني',
			'name_en' => 'Spanish',
			'file' => 'Spanish',
			'book' => 'https://www.muslim-library.com/dl/books/es0701.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Spanish/Mishary_Alafasy/',
			'source' => 'es',
			'lang' => 'es'
		);

		$lang_info['ur'] = array(
			'id' => '16',
			'name' => 'اردو',
			'name_ar' => 'أردو',
			'name_en' => 'Urdu',
			'file' => 'Urdu',
			'book' => 'https://www.qurantranslations.net/quran/pdf/ur_Translation_of_the_meaning_of_the_Holy_Quran_in_Urdu.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Urdu/Sudais_and_Shuraym/',
			'source' => 'ur',
			'lang' => 'ur'
		);

		$lang_info['bn'] = array(
			'id' => '17',
			'name' => 'বাংলা',
			'name_ar' => 'بنغالي',
			'name_en' => 'Bangali',
			'file' => 'Bangali',
			'book' => 'http://www.qurandownload.com/bangla-quran-pdfs.rar',
			'sound' => 'https://www.qurantranslations.net/sound/Bengali/Ali_Abdur-Rahman_Al-Huthaify/',
			'source' => 'bn',
			'lang' => 'bn'
		);

		$lang_info['ta'] = array(
			'id' => '18',
			'name' => 'தமிழ்',
			'name_ar' => 'تاميلي',
			'name_en' => 'Tamil',
			'file' => 'Tamil',
			'book' => 'https://www.qurantranslations.net/quran/pdf/ta_quran_kareem_ma3aneh.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Hindi/Holy_Quran_in_the_Tamil_Language/',
			'source' => 'ta',
			'lang' => 'ta'
		);

		$lang_info['cz'] = array(
			'id' => '19',
			'name' => 'České',
			'name_ar' => 'تشيكي',
			'name_en' => 'Czech',
			'file' => 'Czech',
			'book' => 'https://www.muslim-library.com/dl/books/cz4172.pdf',
			'sound' => '',
			'source' => 'cz',
			'lang' => 'cz'
		);

		$lang_info['de'] = array(
			'id' => '20',
			'name' => 'Deutsch',
			'name_ar' => 'الماني',
			'name_en' => 'German',
			'file' => 'German',
			'book' => 'https://www.muslim-library.com/wp-content/uploads/2015/01/de_translation_of_the_meaning_of_the_holy_quran_in_deutsch.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Deutsch/DER_QURAN_IN_DEUTSCHER_UBERSETZUNG-Saod_Alshurim/',
			'source' => 'de',
			'lang' => 'de'
		);

		$lang_info['fa'] = array(
			'id' => '21',
			'name' => 'فارسى',
			'name_ar' => 'فارسي',
			'name_en' => 'Persian',
			'file' => 'Persian',
			'book' => 'https://www.muslim-library.com/dl/books/fa4145.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Farsi/Holy_Quran_in_the_Farsi_Language/',
			'found_files' => array(58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'fa',
			'lang' => 'fa'
		);

		$lang_info['ro'] = array(
			'id' => '22',
			'name' => 'Română',
			'name_ar' => 'روماني',
			'name_en' => 'Romanian',
			'file' => 'Romanian',
			'book' => 'https://www.muslim-library.com/dl/books/ro2349.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Romanian/translation-of-the-quran-in-romanian-30th-part/',
			'found_files' => array(78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'ro',
			'lang' => 'ro'
		);

		$lang_info['ru'] = array(
			'id' => '23',
			'name' => 'Русский',
			'name_ar' => 'روسي',
			'name_en' => 'Russian',
			'file' => 'Russian',
			'book' => 'https://www.muslim-library.com/books/ru_quran_russian.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Russian/Holy_Quran_in_the_Russian_Language_1/',
			'source' => 'ru',
			'lang' => 'ru'
		);

		$lang_info['sv'] = array(
			'id' => '24',
			'name' => 'Svenska',
			'name_ar' => 'سويدي',
			'name_en' => 'Swedish',
			'file' => 'Swedish',
			'book' => 'https://www.muslim-library.com/dl/books/sw2146.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Swedish/',
			'found_files' => array(1,12,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'sv',
			'lang' => 'sv'
		);

		$lang_info['sq'] = array(
			'id' => '25',
			'name' => 'Shqip',
			'name_ar' => 'الباني',
			'name_en' => 'Albanian',
			'file' => 'Albanian',
			'book' => 'https://www.muslim-library.com/dl/books/al4140.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Albanian/Holy_Quran_in_the_Albanian_Language/',
			'found_files' => array(1,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'sq',
			'lang' => 'sq'
		);

		$lang_info['az'] = array(
			'id' => '26',
			'name' => 'Azəri',
			'name_ar' => 'أذري',
			'name_en' => 'Azerbaijani',
			'file' => 'Azerbaijani',
			'book' => 'https://muslim-library.com/books/az_Quran_oxumagin_savabi.pdf',
			'sound' => '',
			'source' => 'az',
			'lang' => 'az'
		);

		$lang_info['bs'] = array(
			'id' => '27',
			'name' => 'Bosanski',
			'name_ar' => 'بوسني',
			'name_en' => 'Bosnian',
			'file' => 'Bosnian',
			'book' => 'https://www.muslim-library.com/dl/books/bs4139.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Bosnian/Mahmoud_Khalil_Al-Husari/',
			'source' => 'bs',
			'lang' => 'bs'
		);
		$lang_info['bg'] = array(
			'id' => '28',
			'name' => 'Български',
			'name_ar' => 'بلغاري',
			'name_en' => 'Bulgarian',
			'file' => 'Bulgarian',
			'book' => 'https://www.muslim-library.com/dl/books/bu4142.pdf',
			'sound' => '',
			'source' => 'bg',
			'lang' => 'bg'
		);

		$lang_info['ha'] = array(
			'id' => '29',
			'name' => 'Hausa',
			'name_ar' => 'الهاوسا',
			'name_en' => 'Hausa',
			'file' => 'Hausa',
			'book' => 'https://muslim-library.com/books/ha_hausa.pdf',
			'sound' => '',
			'source' => 'ha',
			'lang' => 'ha'
		);
		$lang_info['ku'] = array(
			'id' => '30',
			'name' => 'كوردی',
			'name_ar' => 'كردي',
			'name_en' => 'Kurdish',
			'file' => 'Kurdish',
			'book' => 'https://muslim-library.com/books/ku_Quran_in_Kurdish.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Kurdish/Saad_Al_Ghamedi_with_Kurdish_Translation/',
			'source' => 'ku',
			'lang' => 'ku'
		);

		$lang_info['no'] = array(
			'id' => '31',
			'name' => 'Norwegian',
			'name_ar' => 'نرويجي',
			'name_en' => 'Norwegian',
			'file' => 'Norwegian',
			'book' => 'https://www.muslim-library.com/dl/books/no4173.pdf',
			'sound' => '',
			'source' => 'no',
			'lang' => 'no'
		);

		$lang_info['pl'] = array(
			'id' => '32',
			'name' => 'Polski',
			'name_ar' => 'بولندا',
			'name_en' => 'Polish',
			'file' => 'Polish',
			'book' => 'http://www.qurandownload.com/polish-quran-wb.pdf',
			'sound' => '',
			'source' => 'pl',
			'lang' => 'pl'
		);

		$lang_info['so'] = array(
			'id' => '33',
			'name' => 'soomaali',
			'name_ar' => 'صومالي',
			'name_en' => 'Somali',
			'file' => 'Somali',
			'book' => 'https://www.muslim-library.com/dl/books/so2380.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Soomaali/Holy_Quran_in_the_Soomaali_Language/',
			'found_files' => array(58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95,96,97,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,114),
			'source' => 'so',
			'lang' => 'so'
		);

		$lang_info['sw'] = array(
			'id' => '34',
			'name' => 'Swahili',
			'name_ar' => 'كيني',
			'name_en' => 'Swahili',
			'file' => 'Swahili',
			'book' => 'https://www.muslim-library.com/dl/books/sw4170.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Swahili/',
			'source' => 'sw',
			'lang' => 'sw'
		);

		$lang_info['tg'] = array(
			'id' => '35',
			'name' => 'Тоҷикӣ',
			'name_ar' => 'طاجيكي',
			'name_en' => 'Tajik',
			'file' => 'Tajik',
			'book' => '',
			'sound' => '',
			'source' => 'tg',
			'lang' => 'tg'
		);

		$lang_info['tt'] = array(
			'id' => '36',
			'name' => 'Татарча',
			'name_ar' => 'تتاري',
			'name_en' => 'Tatar',
			'file' => 'Tatar',
			'book' => '',
			'sound' => '',
			'source' => 'tt',
			'lang' => 'tt'
		);

		$lang_info['th'] = array(
			'id' => '37',
			'name' => 'ไทย',
			'name_ar' => 'تايلندي',
			'name_en' => 'Thailand',
			'file' => 'Thailand',
			'book' => 'https://www.muslim-library.com/dl/books/th4164.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Thai/Saad_El_Ghamidi/',
			'source' => 'th',
			'lang' => 'th'
		);

		$lang_info['ug'] = array(
			'id' => '38',
			'name' => 'ئۇيغۇرچە',
			'name_ar' => 'أيغوري',
			'name_en' => 'Uyghur',
			'file' => 'Uyghur',
			'book' => 'https://www.muslim-library.com/dl/books/gy2439.pdf',
			'sound' => 'https://www.qurantranslations.net/sound/Uyghur/Holy_Quran_in_the_Uyghur_Language/',
			'source' => 'ug',
			'lang' => 'ug'
		);

		$lang_info['uz'] = array(
			'id' => '39',
			'name' => 'Ўзбек',
			'name_ar' => 'أوزبكي',
			'name_en' => 'Uzbek',
			'file' => 'Uzbek',
			'book' => '',
			'sound' => '',
			'source' => 'uz',
			'lang' => 'uz'
		);

		$lang_info['dv'] = array(
			'id' => '40',
			'name' => 'ދިވެހި',
			'name_ar' => 'مالديفي',
			'name_en' => 'Divehi',
			'file' => 'Divehi',
			'book' => '',
			'sound' => '',
			'source' => 'dv',
			'lang' => 'dv'
		);

		$lang_info['sd'] = array(
			'id' => '41',
			'name' => 'Sindhi',
			'name_ar' => 'سندي',
			'name_en' => 'Sindhi',
			'file' => 'Sindhi',
			'book' => 'http://www.qurandownload.com/sindhi-quran.pdf',
			'sound' => '',
			'source' => 'sd',
			'lang' => 'sd'
		);

		return $lang_info;
	}

	function api_surah( $lang_key='', $root = false ){
		if( is_array($this->api_surah_name($lang_key)) ){

			if( empty($lang_key) ){
				$lang = $this->get_language();
			}else{
				$lang = $lang_key;
			}

			$langs = $this->api_languages();

			$language = ( isset($langs[$lang]) ? $langs[$lang] : 'ar' );

			$output = array();
			//if( empty($lang_key) ){
				$output['status'] = 'ok';
				$output['action'] = 'surah';
				$output['lang'] = $lang;
				if( isset($language['id']) ){
					$output['language_id'] = $language['id'];
				}
				if( isset($language['name']) ){
					$output['language_name'] = $language['name'];
				}
				if( isset($language['name_ar']) ){
					$output['language_name_ar'] = $language['name_ar'];
				}
				if( isset($language['name_en']) ){
					$output['language_name_en'] = $language['name_en'];
				}
				if( isset($language['book']) ){
					$output['language_book'] = $language['book'];
				}
				if( isset($language['sound']) ){
					$output['language_sound'] = $language['sound'];
				}
				if( isset($language['found_files']) ){
					$output['language_found_files'] = $language['found_files'];
				}
				$output['language_flag'] = $this->site_url( 'images/flags/'.$lang.'.png' );
			//}

			$surah_names = ( in_array($lang, $this->child_lang) ? $this->api_surah_name('en') : $this->api_surah_name() );
			$s_name = $this->api_surah_name('ar');
			$s_name_en = $this->api_surah_name('en');

			$i=0;
			foreach($surah_names as $key => $value){
				++$i;
				$ayat = ( isset($this->aya_count[$i]) ? $this->aya_count[$i] : 'none' );
				$surah_name = ( isset($value['name']) ? $value['name'] : '' );
				//if( empty($lang_key) ){
					$surah_arr = array();
					$surah_arr['n'] = $i;
					$surah_arr['name'] = $surah_name;
					if( $lang == 'en' ){
						$surah_arr['name_ar'] = ( isset($s_name[$i]['name']) ? $s_name[$i]['name'] : 'Not found ar Surah' );
					}else{
						if( $lang != 'ar' ){
							$surah_arr['name_ar'] = ( isset($s_name[$i]['name']) ? $s_name[$i]['name'] : 'Not found ar Surah' );
						}
						$surah_arr['name_en'] = ( isset($s_name_en[$i]['name']) ? $s_name_en[$i]['name'] : 'Not found en Surah' );
					}
					$surah_arr['ayat'] = $ayat;
					$surah_arr['image'] = $this->surah_name_image($i, 'png', $root);
					$surah_arr['image_svg'] = $this->surah_name_image($i, 'svg', $root);

					$output['data'][$i] = $surah_arr;
				//}else{
					//$output[] = array( 'n' => $i, 'name' => $surah_name, 'ayat' => $ayat );
				//}
			}
			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'api_surah_name() function is not array' );
		}
	}

	function api_get_languages(){
		$languages = $this->api_languages();

		$data = array();
		$data['status'] = 'ok';
		$data['count'] = count($languages);
		foreach($languages as $key => $value){
			$id = ( isset($value['id']) ? $value['id'] : 0 );
			$name = ( isset($value['name']) ? $value['name'] : '' );
			$name_ar = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
			$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
			$file = ( isset($value['file']) ? $value['file'] : '' );
			$book = ( isset($value['book']) ? $value['book'] : '' );
			$more = ( isset($value['more']) ? $value['more'] : '' );
			$source = ( isset($value['source']) ? $value['source'] : '' );
			$lang = ( isset($value['lang']) ? $value['lang'] : '' );
			$book_api = ( isset($value['book_api']) ? $value['book_api'] : '' );
			$flag = $this->site_url( 'images/flags/'.$lang.'.png' );

			$data['data'][$key] = array(
				'id' => $id,
				'name' => $name,
				'name_ar' => $name_ar,
				'name_en' => $name_en,
				'file' => $file,
				'book' => $book,
				'more' => $more,
				'lang' => $lang,
				'flag' => $flag,
				'key' => $key,
				'book_api' => $book_api
			);
		}

		return $data;
	}

	function api_surah_loop($set_surah_id='', $set_lang='', $root=false){
		$lang = $this->get_language();
		$surah_id = $this->surah_id();

		if( !empty($set_surah_id) ){
			$surah_id = $set_surah_id;
		}

		if( !empty($set_lang) ){
			$lang = $set_lang;
		}

	  $QURAN = '';

		$path = $this->addRootPath( $root );

		$filename = $path.'includes/uthmani/'.$surah_id.'.php';//quran-uthmani, uthmani, quran
		if( file_exists($filename) ){
			require_once( $filename );
			$QURAN = $q;
		}

		if( isset($QURAN) && is_array($QURAN) ){
			$surah_name = $this->api_surah_name();
			$ayatnumbers = count($QURAN);
			$from = ( isset($_GET['f']) && intval($_GET['f']) != 0 ? intval($_GET['f']) : 1 );
			$to = ( isset($_GET['t']) && intval($_GET['t']) != 0 ? intval($_GET['t']) : $ayatnumbers );
			$reader_id = ( isset($_GET['x']) ? intval($_GET['x']) : $this->default_reader );

			$get_from = ( $from > $to ? 1 : $from );
			$get_to = ( $to > $ayatnumbers || $from > $to ? $ayatnumbers : $to );

			$trans = '';
			if( is_array($lang) && count($lang) > 0 ){
				$multi_trans = array();
				foreach( $lang as $key => $value ){
					if( $this->get_folder_translate($value, $surah_id, $root) != false ){
						include( $this->get_folder_translate($value, $surah_id, $root) );
						$multi_trans[$value] = ( isset($t) ? $t : '' );
					}
				}
				if( is_array($multi_trans) && count($multi_trans) > 0 ){
					$trans_loop = array();
					$trans_loop['multi'] = 1;
					foreach( $multi_trans as $keyt => $valuet ) {
						$trans_loop[$keyt] = $valuet;
					}
				}
				$trans = $trans_loop;
			}else{
				if( $this->get_folder_translate('', $surah_id, $root) != false ){
					include( $this->get_folder_translate('', $surah_id, $root) );
					$trans = ( isset($t) ? $t : '' );
				}
			}

			$output = array();
			$output['status'] = 'ok';
			$output['count'] = $ayatnumbers;
			$output['surah_id'] = $surah_id;
			$output['surah_name'] = ( isset($surah_name[$surah_id]['name']) ? $surah_name[$surah_id]['name'] : 'None' );
			$output['surah_image'] = $this->surah_name_image($surah_id);
			$output['surah_image_svg'] = $this->surah_name_image($surah_id, 'svg');
			if( isset($_GET['l']) ){
				if( is_array($lang) && count($lang) > 0 ){
					//$output['language_lang'] = $this->site_url( 'images/flags/'.$output[0]['lang'].'.png' );
					$langs = $this->api_languages();
					foreach( $lang as $keyl => $valuel ){
						$language = $langs[$valuel];
						$output[$valuel]['lang'] = $valuel;
						if( isset($language['id']) ){
							$output[$valuel]['language_id'] = $language['id'];
						}
						if( isset($language['name']) ){
							$output[$valuel]['language_name'] = $language['name'];
						}
						if( isset($language['name_ar']) ){
							$output[$valuel]['language_name_ar'] = $language['name_ar'];
						}
						if( isset($language['name_en']) ){
							$output[$valuel]['language_name_en'] = $language['name_en'];
						}
						if( isset($language['book']) ){
							$output[$valuel]['language_book'] = $language['book'];
						}
						if( isset($language['sound']) ){
							$output[$valuel]['language_sound'] = $language['sound'];
						}
						if( isset($language['found_files']) ){
							$output[$valuel]['language_found_files'] = $language['found_files'];
						}
					}
				}else{
					$langs = $this->api_languages();
					$language = $langs[$lang];
					$output['lang'] = $lang;
					if( isset($language['id']) ){
						$output['language_id'] = $language['id'];
					}
					if( isset($language['name']) ){
						$output['language_name'] = $language['name'];
					}
					if( isset($language['name_ar']) ){
						$output['language_name_ar'] = $language['name_ar'];
					}
					if( isset($language['name_en']) ){
						$output['language_name_en'] = $language['name_en'];
					}
					$output['language_flag'] = $this->site_url( 'images/flags/'.$lang.'.png' );
					if( isset($language['book']) ){
						$output['language_book'] = $language['book'];
					}
					if( isset($language['sound']) ){
						$output['language_sound'] = $language['sound'];
					}
					if( isset($language['found_files']) ){
						$output['language_found_files'] = $language['found_files'];
					}
				}
				//$output['surah'] = $this->api_surah( $lang );
			}
			for( $i=$get_from; $i<=$get_to; ++$i ){
				$output['data'][$i] = array( 'ayah_number' => $i, 'ayah_text' => $QURAN[$i] );
			}
			if( is_array($trans) && isset($trans['multi']) ){
				$output['translate'] = $trans;
			}elseif( is_array($trans) ){
				$output['translate'][$lang] = $trans;
			}
			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found surah' );
		}
		return $s;
	}

	function api_tafseer(){
		$tafseer = $this->tafseers;
		$data = array();
		$data['status'] = 'ok';
		$data['count'] = count($tafseer);
		foreach($tafseer as $key => $value){
			$name = ( isset($value['name']) ? $value['name'] : '' );
			$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
			$data['data'][$key] = array(
				'name' => $name,
				'name_en' => $name_en
			);
		}
		return $data;
	}

	function api_tafseer_view( $set_tafseer_id = '', $set_surah_id = '', $set_ayah_id = '', $root = false ){
		$surah_id = $this->surah_id();
		if( !empty($set_surah_id) ){
			$surah_id = $set_surah_id;
		}
		$QURAN = '';
		$path = $this->addRootPath( $root );
		$filename = $path.'includes/uthmani/'.$surah_id.'.php';//quran-uthmani, uthmani, quran
		if( file_exists($filename) ){
			require_once( $filename );
			$QURAN = $q;
		}

		$tafseer_list = $this->tafseers;

		$ayah_id = $this->ayah_id();
		if( !empty($set_ayah_id) ){
			$ayah_id = $set_ayah_id;
		}
		$tafseer_id = $this->tafseer_id();
		if( !empty($set_tafseer_id) ){
			$tafseer_id = $set_tafseer_id;
		}

		if( array_key_exists( $tafseer_id, $tafseer_list) ){
			if( isset($QURAN) && is_array($QURAN) ){
				$surah_name = $this->api_surah_name();
				$ayatnumbers = count($QURAN);

				if( $this->get_tafseer_include($tafseer_id, $surah_id, $ayah_id, $root) != false ){
					include( $this->get_tafseer_include($tafseer_id, $surah_id, $ayah_id, $root) );
					$tafseer = ( isset($t) ? $t : '' );
					if( isset($t) && is_array($t) && count($t) > 0 ){
						if( is_array($tafseer) ){
							$tafseer_text = ( isset($tafseer[$ayah_id]) ? $tafseer[$ayah_id] : '' );
							$tafseer_arr = array();
							$tafseer_arr['status'] = 'ok';
							$tafseer_arr['tafseer_id'] = $tafseer_id;
							$tafseer_arr['tafseer_name'] = ( isset($tafseer_list[$tafseer_id]['name']) ? $tafseer_list[$tafseer_id]['name'] : 'Not found tafseer name' );
							$tafseer_arr['surah_id'] = $surah_id;
							$tafseer_arr['surah_ayat'] = $ayatnumbers;
							$tafseer_arr['surah_name'] = ( isset($surah_name[$surah_id]['name']) ? $surah_name[$surah_id]['name'] : 'None' );
							$tafseer_arr['surah_image'] = $this->surah_name_image($surah_id);
							$tafseer_arr['surah_image_svg'] = $this->surah_name_image($surah_id, 'svg');

							$tafseer_arr['ayah_id'] = $ayah_id;
							$tafseer_arr['aya_text'] = ( isset($QURAN[$ayah_id]) ? $QURAN[$ayah_id] : 'Ayah text not found' );
							$tafseer_arr['text'] = $tafseer_text;

								/*
								foreach( $tafseer as $key => $value ){
									$aya_text = ( isset($QURAN[$key]) ? $QURAN[$key] : 'Ayah text not found' );
									$tafseer_arr['data'][$key] = array( 'ayah_text' => $aya_text, 'tafseer_text' => $value );
								}
								*/

							return $tafseer_arr;
						}else{
							return array( 'status' => 'error', 'msg' => 'Not found tafseer surah' );
						}
					}else{
						return array( 'status' => 'error', 'msg' => 'Not found tafseer id' );
					}
				}else{
					return array( 'status' => 'error', 'msg' => 'Not found tafseer file' );
				}
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found surah' );
			}
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found tafseer' );
		}
	}

	function api_get_readers( $set_reader_id = '' ){
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : '' );
		if( !empty($set_reader_id) ){
			$reader_id = $set_reader_id;
		}
		$readers = $this->api_readers();

		if( is_array($readers) && count($readers) > 0 ){
			$data = array();
			$data['status'] = 'ok';
			$data['count'] = count($readers);
			foreach($readers as $key => $value){
				$name = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$description = ( isset($value['description_ar']) ? $value['description_ar'] : '' );
				$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
				$sound_folder = ( isset($value['folder']) ? $value['folder'] : '' );

				$data['data'][$key] = array(
					'name' => $name,
					'name_en' => $name_en,
					'description' => $description,
					'description_en' => $description_en,
					'sound_folder' => $sound_folder
				);
			}
			return $data;

		}else{
			return array( 'status' => 'error', 'msg' => 'Not found readers' );
		}

	}

	function api_get_reader( $set_reader_id = '' ){
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : '' );
		if( !empty($set_reader_id) ){
			$reader_id = $set_reader_id;
		}

		$readers = $this->api_readers();

		if( is_array($readers) && count($readers) > 0 ){
			if( isset($readers[$reader_id]) && array_key_exists($reader_id, $readers) ){
				$value = $readers[$reader_id];
				$data = array();
				$data['status'] = 'ok';

				$name = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$description = ( isset($value['description_ar']) ? $value['description_ar'] : '' );
				$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
				$sound_folder = ( isset($value['folder']) ? $value['folder'] : '' );

				$data['data'] = array(
					'name' => $name,
					'name_en' => $name_en,
					'description' => $description,
					'description_en' => $description_en,
					'sound_folder' => $sound_folder
				);
				return $data;
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found reader ID' );
			}
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found readers' );
		}

	}

	function api_get_ayah_readers( $set_reader_id = '' ){
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : '' );
		if( !empty($set_reader_id) ){
			$reader_id = $set_reader_id;
		}
		$readers = $this->api_ayah_readers();

		if( is_array($readers) && count($readers) > 0 ){
			$data = array();
			$data['status'] = 'ok';
			$data['count'] = count($readers);
			foreach($readers as $key => $value){
				$name = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$description = ( isset($value['description_ar']) ? $value['description_ar'] : '' );
				$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
				$sound_folder = ( isset($value['folder']) ? $value['folder'] : '' );

				$data['data'][$key] = array(
					'name' => $name,
					'name_en' => $name_en,
					'description' => $description,
					'description_en' => $description_en,
					'sound_folder' => $sound_folder
				);
			}
			return $data;
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found readers' );
		}
	}

	function api_get_ayah_reader( $set_reader_id = '' ){
		$reader_id = ( isset($_GET['reader_id']) ? intval($_GET['reader_id']) : '' );
		if( !empty($set_reader_id) ){
			$reader_id = $set_reader_id;
		}
		$readers = $this->api_ayah_readers();

		if( is_array($readers) && count($readers) > 0 ){
			if( isset($readers[$reader_id]) && array_key_exists($reader_id, $readers) ){
				$value = $readers[$reader_id];
				$data = array();
				$data['status'] = 'ok';

				$name = ( isset($value['name_ar']) ? $value['name_ar'] : '' );
				$name_en = ( isset($value['name_en']) ? $value['name_en'] : '' );
				$description = ( isset($value['description_ar']) ? $value['description_ar'] : '' );
				$description_en = ( isset($value['description_en']) ? $value['description_en'] : '' );
				$sound_folder = ( isset($value['folder']) ? $value['folder'] : '' );

				$data['data'] = array(
					'name' => $name,
					'name_en' => $name_en,
					'description' => $description,
					'description_en' => $description_en,
					'sound_folder' => $sound_folder
				);

				return $data;
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found reader ID' );
			}

		}else{
			return array( 'status' => 'error', 'msg' => 'Not found readers' );
		}

	}

	public function moshaf_pages(){
		$p = array();

		$p[1] = array( 1 => array( 'f' => 1, 't' => 7 ) );

		$p[2] = array( 2 => array( 'f' => 1, 't' => 5 ) );
		$p[3] = array( 2 => array( 'f' => 6, 't' => 16 ) );
		$p[4] = array( 2 => array( 'f' => 17, 't' => 24 ) );
		$p[5] = array( 2 => array( 'f' => 25, 't' => 29 ) );
		$p[6] = array( 2 => array( 'f' => 30, 't' => 37 ) );
		$p[7] = array( 2 => array( 'f' => 38, 't' => 48 ) );
		$p[8] = array( 2 => array( 'f' => 49, 't' => 57 ) );
		$p[9] = array( 2 => array( 'f' => 58, 't' => 61 ) );
		$p[10] = array( 2 => array( 'f' => 62, 't' => 69 ) );
		$p[11] = array( 2 => array( 'f' => 70, 't' => 76 ) );
		$p[12] = array( 2 => array( 'f' => 77, 't' => 83 ) );
		$p[13] = array( 2 => array( 'f' => 84, 't' => 88 ) );
		$p[14] = array( 2 => array( 'f' => 89, 't' => 93 ) );
		$p[15] = array( 2 => array( 'f' => 94, 't' => 101 ) );
		$p[16] = array( 2 => array( 'f' => 102, 't' => 105 ) );
		$p[17] = array( 2 => array( 'f' => 106, 't' => 112 ) );
		$p[18] = array( 2 => array( 'f' => 113, 't' => 119 ) );
		$p[19] = array( 2 => array( 'f' => 120, 't' => 126 ) );
		$p[20] = array( 2 => array( 'f' => 127, 't' => 134 ) );
		$p[21] = array( 2 => array( 'f' => 135, 't' => 141 ) );
		$p[22] = array( 2 => array( 'f' => 142, 't' => 145 ) );
		$p[23] = array( 2 => array( 'f' => 146, 't' => 153 ) );
		$p[24] = array( 2 => array( 'f' => 154, 't' => 163 ) );
		$p[25] = array( 2 => array( 'f' => 164, 't' => 169 ) );
		$p[26] = array( 2 => array( 'f' => 170, 't' => 176 ) );
		$p[27] = array( 2 => array( 'f' => 177, 't' => 181 ) );
		$p[28] = array( 2 => array( 'f' => 182, 't' => 186 ) );
		$p[29] = array( 2 => array( 'f' => 187, 't' => 190 ) );
		$p[30] = array( 2 => array( 'f' => 191, 't' => 196 ) );
		$p[31] = array( 2 => array( 'f' => 197, 't' => 202 ) );
		$p[32] = array( 2 => array( 'f' => 203, 't' => 210 ) );
		$p[33] = array( 2 => array( 'f' => 211, 't' => 215 ) );
		$p[34] = array( 2 => array( 'f' => 216, 't' => 219 ) );
		$p[35] = array( 2 => array( 'f' => 220, 't' => 224 ) );
		$p[36] = array( 2 => array( 'f' => 225, 't' => 230 ) );
		$p[37] = array( 2 => array( 'f' => 231, 't' => 233 ) );
		$p[38] = array( 2 => array( 'f' => 234, 't' => 237 ) );
		$p[39] = array( 2 => array( 'f' => 238, 't' => 245 ) );
		$p[40] = array( 2 => array( 'f' => 246, 't' => 248 ) );
		$p[41] = array( 2 => array( 'f' => 249, 't' => 252 ) );
		$p[42] = array( 2 => array( 'f' => 253, 't' => 256 ) );
		$p[43] = array( 2 => array( 'f' => 257, 't' => 259 ) );
		$p[44] = array( 2 => array( 'f' => 260, 't' => 264 ) );
		$p[45] = array( 2 => array( 'f' => 265, 't' => 269 ) );
		$p[46] = array( 2 => array( 'f' => 270, 't' => 274 ) );
		$p[47] = array( 2 => array( 'f' => 275, 't' => 281 ) );
		$p[48] = array( 2 => array( 'f' => 282, 't' => 282 ) );
		$p[49] = array( 2 => array( 'f' => 283, 't' => 286 ) );

		$p[50] = array( 3 => array( 'f' => 1, 't' => 9 ) );
		$p[51] = array( 3 => array( 'f' => 10, 't' => 15 ) );
		$p[52] = array( 3 => array( 'f' => 16, 't' => 22 ) );
		$p[53] = array( 3 => array( 'f' => 23, 't' => 29 ) );
		$p[54] = array( 3 => array( 'f' => 30, 't' => 37 ) );
		$p[55] = array( 3 => array( 'f' => 38, 't' => 45 ) );
		$p[56] = array( 3 => array( 'f' => 46, 't' => 52 ) );
		$p[57] = array( 3 => array( 'f' => 53, 't' => 61 ) );
		$p[58] = array( 3 => array( 'f' => 62, 't' => 70 ) );
		$p[59] = array( 3 => array( 'f' => 71, 't' => 77 ) );
		$p[60] = array( 3 => array( 'f' => 78, 't' => 83 ) );
		$p[61] = array( 3 => array( 'f' => 84, 't' => 91 ) );
		$p[62] = array( 3 => array( 'f' => 92, 't' => 100 ) );
		$p[63] = array( 3 => array( 'f' => 101, 't' => 108 ) );
		$p[64] = array( 3 => array( 'f' => 109, 't' => 115 ) );
		$p[65] = array( 3 => array( 'f' => 116, 't' => 121 ) );
		$p[66] = array( 3 => array( 'f' => 122, 't' => 132 ) );
		$p[67] = array( 3 => array( 'f' => 133, 't' => 140 ) );
		$p[68] = array( 3 => array( 'f' => 141, 't' => 148 ) );
		$p[69] = array( 3 => array( 'f' => 149, 't' => 153 ) );
		$p[70] = array( 3 => array( 'f' => 154, 't' => 157 ) );
		$p[71] = array( 3 => array( 'f' => 158, 't' => 165 ) );
		$p[72] = array( 3 => array( 'f' => 166, 't' => 173 ) );
		$p[73] = array( 3 => array( 'f' => 174, 't' => 180 ) );
		$p[74] = array( 3 => array( 'f' => 181, 't' => 186 ) );
		$p[75] = array( 3 => array( 'f' => 187, 't' => 194 ) );
		$p[76] = array( 3 => array( 'f' => 195, 't' => 200 ) );

		$p[77] = array( 4 => array( 'f' => 1, 't' => 6 ) );
		$p[78] = array( 4 => array( 'f' => 7, 't' => 11 ) );
		$p[79] = array( 4 => array( 'f' => 12, 't' => 14 ) );
		$p[80] = array( 4 => array( 'f' => 15, 't' => 19 ) );
		$p[81] = array( 4 => array( 'f' => 20, 't' => 23 ) );
		$p[82] = array( 4 => array( 'f' => 24, 't' => 26 ) );
		$p[83] = array( 4 => array( 'f' => 27, 't' => 33 ) );
		$p[84] = array( 4 => array( 'f' => 34, 't' => 37 ) );
		$p[85] = array( 4 => array( 'f' => 38, 't' => 44 ) );
		$p[86] = array( 4 => array( 'f' => 45, 't' => 51 ) );
		$p[87] = array( 4 => array( 'f' => 52, 't' => 59 ) );
		$p[88] = array( 4 => array( 'f' => 60, 't' => 65 ) );
		$p[89] = array( 4 => array( 'f' => 66, 't' => 74 ) );
		$p[90] = array( 4 => array( 'f' => 75, 't' => 79 ) );
		$p[91] = array( 4 => array( 'f' => 80, 't' => 86 ) );
		$p[92] = array( 4 => array( 'f' => 87, 't' => 91 ) );
		$p[93] = array( 4 => array( 'f' => 92, 't' => 94 ) );
		$p[94] = array( 4 => array( 'f' => 95, 't' => 101 ) );
		$p[95] = array( 4 => array( 'f' => 102, 't' => 105 ) );
		$p[96] = array( 4 => array( 'f' => 106, 't' => 113 ) );
		$p[97] = array( 4 => array( 'f' => 114, 't' => 121 ) );
		$p[98] = array( 4 => array( 'f' => 122, 't' => 127 ) );
		$p[99] = array( 4 => array( 'f' => 128, 't' => 134 ) );
		$p[100] = array( 4 => array( 'f' => 135, 't' => 140 ) );
		$p[101] = array( 4 => array( 'f' => 141, 't' => 147 ) );
		$p[102] = array( 4 => array( 'f' => 148, 't' => 154 ) );
		$p[103] = array( 4 => array( 'f' => 155, 't' => 162 ) );
		$p[104] = array( 4 => array( 'f' => 163, 't' => 170 ) );
		$p[105] = array( 4 => array( 'f' => 171, 't' => 175 ) );
		$p[106] = array(
			4 => array( 'f' => 176, 't' => 176 ),
			5 => array( 'f' => 1, 't' => 2 )
		);
		$p[107] = array( 5 => array( 'f' => 3, 't' => 5 ) );
		$p[108] = array( 5 => array( 'f' => 6, 't' => 9 ) );
		$p[109] = array( 5 => array( 'f' => 10, 't' => 13 ) );
		$p[110] = array( 5 => array( 'f' => 14, 't' => 17 ) );
		$p[111] = array( 5 => array( 'f' => 18, 't' => 23 ) );
		$p[112] = array( 5 => array( 'f' => 24, 't' => 31 ) );
		$p[113] = array( 5 => array( 'f' => 32, 't' => 36 ) );
		$p[114] = array( 5 => array( 'f' => 37, 't' => 41 ) );
		$p[115] = array( 5 => array( 'f' => 42, 't' => 45 ) );
		$p[116] = array( 5 => array( 'f' => 46, 't' => 50 ) );
		$p[117] = array( 5 => array( 'f' => 51, 't' => 57 ) );
		$p[118] = array( 5 => array( 'f' => 58, 't' => 64 ) );
		$p[119] = array( 5 => array( 'f' => 65, 't' => 70 ) );
		$p[120] = array( 5 => array( 'f' => 71, 't' => 77 ) );
		$p[121] = array( 5 => array( 'f' => 78, 't' => 83 ) );
		$p[122] = array( 5 => array( 'f' => 84, 't' => 90 ) );
		$p[123] = array( 5 => array( 'f' => 91, 't' => 95 ) );
		$p[124] = array( 5 => array( 'f' => 96, 't' => 103 ) );
		$p[125] = array( 5 => array( 'f' => 104, 't' => 108 ) );
		$p[126] = array( 5 => array( 'f' => 109, 't' => 113 ) );
		$p[127] = array( 5 => array( 'f' => 114, 't' => 120 ) );

		$p[128] = array( 6 => array( 'f' => 1, 't' => 8 ) );
		$p[129] = array( 6 => array( 'f' => 9, 't' => 18 ) );
		$p[130] = array( 6 => array( 'f' => 19, 't' => 27 ) );
		$p[131] = array( 6 => array( 'f' => 28, 't' => 35 ) );
		$p[132] = array( 6 => array( 'f' => 36, 't' => 44 ) );
		$p[133] = array( 6 => array( 'f' => 45, 't' => 52 ) );
		$p[134] = array( 6 => array( 'f' => 53, 't' => 59 ) );
		$p[135] = array( 6 => array( 'f' => 60, 't' => 68 ) );
		$p[136] = array( 6 => array( 'f' => 69, 't' => 73 ) );
		$p[137] = array( 6 => array( 'f' => 74, 't' => 81 ) );
		$p[138] = array( 6 => array( 'f' => 82, 't' => 90 ) );
		$p[139] = array( 6 => array( 'f' => 91, 't' => 94 ) );
		$p[140] = array( 6 => array( 'f' => 95, 't' => 101 ) );
		$p[141] = array( 6 => array( 'f' => 102, 't' => 110 ) );
		$p[142] = array( 6 => array( 'f' => 111, 't' => 118 ) );
		$p[143] = array( 6 => array( 'f' => 119, 't' => 124 ) );
		$p[144] = array( 6 => array( 'f' => 125, 't' => 130 ) );
		$p[145] = array( 6 => array( 'f' => 131, 't' => 137 ) );
		$p[146] = array( 6 => array( 'f' => 138, 't' => 142 ) );
		$p[147] = array( 6 => array( 'f' => 143, 't' => 146 ) );
		$p[148] = array( 6 => array( 'f' => 147, 't' => 151 ) );
		$p[149] = array( 6 => array( 'f' => 152, 't' => 157 ) );
		$p[150] = array( 6 => array( 'f' => 158, 't' => 165 ) );

		$p[151] = array( 7 => array( 'f' => 1, 't' => 11 ) );
		$p[152] = array( 7 => array( 'f' => 12, 't' => 22 ) );
		$p[153] = array( 7 => array( 'f' => 23, 't' => 30 ) );
		$p[154] = array( 7 => array( 'f' => 31, 't' => 37 ) );
		$p[155] = array( 7 => array( 'f' => 38, 't' => 43 ) );
		$p[156] = array( 7 => array( 'f' => 44, 't' => 51 ) );
		$p[157] = array( 7 => array( 'f' => 52, 't' => 57 ) );
		$p[158] = array( 7 => array( 'f' => 58, 't' => 67 ) );
		$p[159] = array( 7 => array( 'f' => 68, 't' => 73 ) );
		$p[160] = array( 7 => array( 'f' => 74, 't' => 81 ) );
		$p[161] = array( 7 => array( 'f' => 82, 't' => 87 ) );
		$p[162] = array( 7 => array( 'f' => 88, 't' => 95 ) );
		$p[163] = array( 7 => array( 'f' => 96, 't' => 104 ) );
		$p[164] = array( 7 => array( 'f' => 105, 't' => 120 ) );
		$p[165] = array( 7 => array( 'f' => 121, 't' => 130 ) );
		$p[166] = array( 7 => array( 'f' => 131, 't' => 137 ) );
		$p[167] = array( 7 => array( 'f' => 138, 't' => 143 ) );
		$p[168] = array( 7 => array( 'f' => 144, 't' => 149 ) );
		$p[169] = array( 7 => array( 'f' => 150, 't' => 155 ) );
		$p[170] = array( 7 => array( 'f' => 156, 't' => 159 ) );
		$p[171] = array( 7 => array( 'f' => 160, 't' => 163 ) );
		$p[172] = array( 7 => array( 'f' => 164, 't' => 170 ) );
		$p[173] = array( 7 => array( 'f' => 171, 't' => 178 ) );
		$p[174] = array( 7 => array( 'f' => 179, 't' => 187 ) );
		$p[175] = array( 7 => array( 'f' => 188, 't' => 195 ) );
		$p[176] = array( 7 => array( 'f' => 196, 't' => 206 ) );

		$p[177] = array( 8 => array( 'f' => 1, 't' => 9 ) );
		$p[178] = array( 8 => array( 'f' => 9, 't' => 16 ) );
		$p[179] = array( 8 => array( 'f' => 17, 't' => 25 ) );
		$p[180] = array( 8 => array( 'f' => 26, 't' => 33 ) );
		$p[181] = array( 8 => array( 'f' => 34, 't' => 40 ) );
		$p[182] = array( 8 => array( 'f' => 41, 't' => 45 ) );
		$p[183] = array( 8 => array( 'f' => 46, 't' => 52 ) );
		$p[184] = array( 8 => array( 'f' => 53, 't' => 61 ) );
		$p[185] = array( 8 => array( 'f' => 62, 't' => 69 ) );
		$p[186] = array( 8 => array( 'f' => 70, 't' => 75 ) );

		$p[187] = array( 9 => array( 'f' => 1, 't' => 6 ) );
		$p[188] = array( 9 => array( 'f' => 7, 't' => 13 ) );
		$p[189] = array( 9 => array( 'f' => 14, 't' => 20 ) );
		$p[190] = array( 9 => array( 'f' => 21, 't' => 26 ) );
		$p[191] = array( 9 => array( 'f' => 27, 't' => 31 ) );
		$p[192] = array( 9 => array( 'f' => 32, 't' => 36 ) );
		$p[193] = array( 9 => array( 'f' => 37, 't' => 40 ) );
		$p[194] = array( 9 => array( 'f' => 41, 't' => 47 ) );
		$p[195] = array( 9 => array( 'f' => 48, 't' => 54 ) );
		$p[196] = array( 9 => array( 'f' => 55, 't' => 61 ) );
		$p[197] = array( 9 => array( 'f' => 62, 't' => 68 ) );
		$p[198] = array( 9 => array( 'f' => 69, 't' => 72 ) );
		$p[199] = array( 9 => array( 'f' => 73, 't' => 79 ) );
		$p[200] = array( 9 => array( 'f' => 80, 't' => 86 ) );
		$p[201] = array( 9 => array( 'f' => 87, 't' => 93 ) );
		$p[202] = array( 9 => array( 'f' => 94, 't' => 99 ) );
		$p[203] = array( 9 => array( 'f' => 100, 't' => 106 ) );
		$p[204] = array( 9 => array( 'f' => 107, 't' => 111 ) );
		$p[205] = array( 9 => array( 'f' => 112, 't' => 117 ) );
		$p[206] = array( 9 => array( 'f' => 118, 't' => 122 ) );
		$p[207] = array( 9 => array( 'f' => 123, 't' => 129 ) );

		$p[208] = array( 10 => array( 'f' => 1, 't' => 6 ) );
		$p[209] = array( 10 => array( 'f' => 7, 't' => 14 ) );
		$p[210] = array( 10 => array( 'f' => 15, 't' => 20 ) );
		$p[211] = array( 10 => array( 'f' => 21, 't' => 25 ) );
		$p[212] = array( 10 => array( 'f' => 26, 't' => 33 ) );
		$p[213] = array( 10 => array( 'f' => 34, 't' => 42 ) );
		$p[214] = array( 10 => array( 'f' => 43, 't' => 53 ) );
		$p[215] = array( 10 => array( 'f' => 54, 't' => 61 ) );
		$p[216] = array( 10 => array( 'f' => 62, 't' => 70 ) );
		$p[217] = array( 10 => array( 'f' => 71, 't' => 78 ) );
		$p[218] = array( 10 => array( 'f' => 79, 't' => 88 ) );
		$p[219] = array( 10 => array( 'f' => 89, 't' =>  97) );
		$p[220] = array( 10 => array( 'f' => 98, 't' => 106 ) );
		$p[221] = array(
			10 => array( 'f' => 107, 't' => 109 ),
			11 => array( 'f' => 1, 't' => 5 ),
		);
		$p[222] = array( 11 => array( 'f' => 6, 't' => 12 ) );
		$p[223] = array( 11 => array( 'f' => 13, 't' => 19 ) );
		$p[224] = array( 11 => array( 'f' => 20, 't' => 28 ) );
		$p[225] = array( 11 => array( 'f' => 29, 't' => 37 ) );
		$p[226] = array( 11 => array( 'f' => 38, 't' => 45 ) );
		$p[227] = array( 11 => array( 'f' => 46, 't' => 53 ) );
		$p[228] = array( 11 => array( 'f' => 54, 't' => 62 ) );
		$p[229] = array( 11 => array( 'f' => 63, 't' => 71 ) );
		$p[230] = array( 11 => array( 'f' => 72, 't' => 81 ) );
		$p[231] = array( 11 => array( 'f' => 82, 't' => 88 ) );
		$p[232] = array( 11 => array( 'f' => 89, 't' => 97 ) );
		$p[233] = array( 11 => array( 'f' => 98, 't' => 108 ) );
		$p[234] = array( 11 => array( 'f' => 109, 't' => 117 ) );
		$p[235] = array(
			11 => array( 'f' => 118, 't' => 123 ),
			12 => array( 'f' => 1, 't' => 4 )
		);

		$p[236] = array( 12 => array( 'f' => 5, 't' => 14 ) );
		$p[237] = array( 12 => array( 'f' => 15, 't' => 22 ) );
		$p[238] = array( 12 => array( 'f' => 23, 't' => 30 ) );
		$p[239] = array( 12 => array( 'f' => 31, 't' => 37 ) );
		$p[240] = array( 12 => array( 'f' => 38, 't' => 43 ) );
		$p[241] = array( 12 => array( 'f' => 44, 't' => 52 ) );
		$p[242] = array( 12 => array( 'f' => 53, 't' => 63 ) );
		$p[243] = array( 12 => array( 'f' => 64, 't' => 69 ) );
		$p[244] = array( 12 => array( 'f' => 70, 't' => 78 ) );
		$p[245] = array( 12 => array( 'f' => 79, 't' => 86 ) );
		$p[246] = array( 12 => array( 'f' => 87, 't' => 95 ) );
		$p[247] = array( 12 => array( 'f' => 96, 't' => 103 ) );
		$p[248] = array( 12 => array( 'f' => 104, 't' => 111 ) );

		$p[249] = array( 13 => array( 'f' => 1, 't' => 5 ) );
		$p[250] = array( 13 => array( 'f' => 6, 't' => 13 ) );
		$p[251] = array( 13 => array( 'f' => 14, 't' => 18 ) );
		$p[252] = array( 13 => array( 'f' => 19, 't' => 28 ) );
		$p[253] = array( 13 => array( 'f' => 29, 't' => 34 ) );
		$p[254] = array( 13 => array( 'f' => 35, 't' => 42 ) );
		$p[255] = array(
			13 => array( 'f' => 43, 't' => 43 ),
			14 => array( 'f' => 1, 't' => 5 )
		);

		$p[256] = array( 14 => array( 'f' => 6, 't' => 10 ) );
		$p[257] = array( 14 => array( 'f' => 11, 't' => 18 ) );
		$p[258] = array( 14 => array( 'f' => 19, 't' => 24 ) );
		$p[259] = array( 14 => array( 'f' => 25, 't' => 33 ) );
		$p[260] = array( 14 => array( 'f' => 34, 't' => 42 ) );
		$p[261] = array( 14 => array( 'f' => 43, 't' => 52 ) );

		$p[262] = array( 15 => array( 'f' => 1, 't' => 15 ) );
		$p[263] = array( 15 => array( 'f' => 16, 't' => 31 ) );
		$p[264] = array( 15 => array( 'f' => 32, 't' => 51 ) );
		$p[265] = array( 15 => array( 'f' => 52, 't' => 70 ) );
		$p[266] = array( 15 => array( 'f' => 71, 't' => 90 ) );
		$p[267] = array(
			15 => array( 'f' => 91, 't' => 99 ),
			16 => array( 'f' => 1, 't' => 6 )
		);

		$p[268] = array( 16 => array( 'f' => 7, 't' => 14 ) );
		$p[269] = array( 16 => array( 'f' => 15, 't' => 26 ) );
		$p[270] = array( 16 => array( 'f' => 27, 't' => 34 ) );
		$p[271] = array( 16 => array( 'f' => 35, 't' => 42 ) );
		$p[272] = array( 16 => array( 'f' => 43, 't' => 54 ) );
		$p[273] = array( 16 => array( 'f' => 55, 't' => 64 ) );
		$p[274] = array( 16 => array( 'f' => 65, 't' => 72 ) );
		$p[275] = array( 16 => array( 'f' => 73, 't' => 79 ) );
		$p[276] = array( 16 => array( 'f' => 80, 't' => 87 ) );
		$p[277] = array( 16 => array( 'f' => 88, 't' => 93 ) );
		$p[278] = array( 16 => array( 'f' => 94, 't' => 102 ) );
		$p[279] = array( 16 => array( 'f' => 103, 't' => 110 ) );
		$p[280] = array( 16 => array( 'f' => 111, 't' => 118 ) );
		$p[281] = array( 16 => array( 'f' => 119, 't' => 128 ) );

		$p[282] = array( 17 => array( 'f' => 1, 't' => 7 ) );
		$p[283] = array( 17 => array( 'f' => 8, 't' => 17 ) );
		$p[284] = array( 17 => array( 'f' => 18, 't' => 27 ) );
		$p[285] = array( 17 => array( 'f' => 28, 't' => 38 ) );
		$p[286] = array( 17 => array( 'f' => 39, 't' => 49 ) );
		$p[287] = array( 17 => array( 'f' => 50, 't' => 58 ) );
		$p[288] = array( 17 => array( 'f' => 59, 't' => 66 ) );
		$p[289] = array( 17 => array( 'f' => 67, 't' => 75 ) );
		$p[290] = array( 17 => array( 'f' => 76, 't' => 86 ) );
		$p[291] = array( 17 => array( 'f' => 87, 't' => 96 ) );
		$p[292] = array( 17 => array( 'f' => 97, 't' => 104 ) );
		$p[293] = array(
			17 => array( 'f' => 105, 't' => 111 ),
			18 => array( 'f' => 1, 't' => 4 )
		);

		$p[294] = array( 18 => array( 'f' => 5, 't' => 15 ) );
		$p[295] = array( 18 => array( 'f' => 16, 't' => 20 ) );
		$p[296] = array( 18 => array( 'f' => 21, 't' => 27 ) );
		$p[297] = array( 18 => array( 'f' => 28, 't' => 34 ) );
		$p[298] = array( 18 => array( 'f' => 35, 't' => 45 ) );
		$p[299] = array( 18 => array( 'f' => 46, 't' => 53 ) );
		$p[300] = array( 18 => array( 'f' => 54, 't' => 61 ) );
		$p[301] = array( 18 => array( 'f' => 62, 't' => 74 ) );
		$p[302] = array( 18 => array( 'f' => 75, 't' => 83 ) );
		$p[303] = array( 18 => array( 'f' => 84, 't' => 97 ) );
		$p[304] = array( 18 => array( 'f' => 98, 't' => 110 ) );

		$p[305] = array( 19 => array( 'f' => 1, 't' => 11 ) );
		$p[306] = array( 19 => array( 'f' => 12, 't' => 25 ) );
		$p[307] = array( 19 => array( 'f' => 26, 't' => 38 ) );
		$p[308] = array( 19 => array( 'f' => 39, 't' => 51 ) );
		$p[309] = array( 19 => array( 'f' => 52, 't' => 64 ) );
		$p[310] = array( 19 => array( 'f' => 65, 't' => 76 ) );
		$p[311] = array( 19 => array( 'f' => 77, 't' => 95 ) );
		$p[312] = array(
			19 => array( 'f' => 96, 't' => 98 ),
			20 => array( 'f' => 1, 't' => 12 )
		);

		$p[313] = array( 20 => array( 'f' => 13, 't' => 37 ) );
		$p[314] = array( 20 => array( 'f' => 38, 't' => 51 ) );
		$p[315] = array( 20 => array( 'f' => 52, 't' => 64 ) );
		$p[316] = array( 20 => array( 'f' => 65, 't' => 76 ) );
		$p[317] = array( 20 => array( 'f' => 77, 't' => 87 ) );
		$p[318] = array( 20 => array( 'f' => 88, 't' => 98 ) );
		$p[319] = array( 20 => array( 'f' => 99, 't' => 113 ) );
		$p[320] = array( 20 => array( 'f' => 114, 't' => 125 ) );
		$p[321] = array( 20 => array( 'f' => 126, 't' => 135 ) );

		$p[322] = array( 21 => array( 'f' => 1, 't' => 10 ) );
		$p[323] = array( 21 => array( 'f' => 11, 't' => 24 ) );
		$p[324] = array( 21 => array( 'f' => 25, 't' => 35 ) );
		$p[325] = array( 21 => array( 'f' => 36, 't' => 44 ) );
		$p[326] = array( 21 => array( 'f' => 45, 't' => 57 ) );
		$p[327] = array( 21 => array( 'f' => 58, 't' => 72 ) );
		$p[328] = array( 21 => array( 'f' => 73, 't' => 81 ) );
		$p[329] = array( 21 => array( 'f' => 82, 't' => 90 ) );
		$p[330] = array( 21 => array( 'f' => 91, 't' => 101 ) );
		$p[331] = array( 21 => array( 'f' => 102, 't' => 112 ) );

		$p[332] = array( 22 => array( 'f' => 1, 't' => 5 ) );
		$p[333] = array( 22 => array( 'f' => 6, 't' => 15 ) );
		$p[334] = array( 22 => array( 'f' => 16, 't' => 23 ) );
		$p[335] = array( 22 => array( 'f' => 24, 't' => 30 ) );
		$p[336] = array( 22 => array( 'f' => 31, 't' => 38 ) );
		$p[337] = array( 22 => array( 'f' => 39, 't' => 46 ) );
		$p[338] = array( 22 => array( 'f' => 47, 't' => 55 ) );
		$p[339] = array( 22 => array( 'f' => 56, 't' => 64 ) );
		$p[340] = array( 22 => array( 'f' => 65, 't' => 72 ) );
		$p[341] = array( 22 => array( 'f' => 73, 't' => 78 ) );

		$p[342] = array( 23 => array( 'f' => 1, 't' => 17 ) );
		$p[343] = array( 23 => array( 'f' => 18, 't' => 27 ) );
		$p[344] = array( 23 => array( 'f' => 28, 't' => 42 ) );
		$p[345] = array( 23 => array( 'f' => 43, 't' => 59 ) );
		$p[346] = array( 23 => array( 'f' => 60, 't' => 74 ) );
		$p[347] = array( 23 => array( 'f' => 75, 't' => 89 ) );
		$p[348] = array( 23 => array( 'f' => 90, 't' => 104 ) );
		$p[349] = array( 23 => array( 'f' => 105, 't' => 118 ) );

		$p[350] = array( 24 => array( 'f' => 1, 't' => 10 ) );
		$p[351] = array( 24 => array( 'f' => 11, 't' => 20 ) );
		$p[352] = array( 24 => array( 'f' => 21, 't' => 27 ) );
		$p[353] = array( 24 => array( 'f' => 28, 't' => 31 ) );
		$p[354] = array( 24 => array( 'f' => 32, 't' => 36 ) );
		$p[355] = array( 24 => array( 'f' => 37, 't' => 43 ) );
		$p[356] = array( 24 => array( 'f' => 44, 't' => 53 ) );
		$p[357] = array( 24 => array( 'f' => 54, 't' => 58 ) );
		$p[358] = array( 24 => array( 'f' => 59, 't' => 61 ) );
		$p[359] = array(
			24 => array( 'f' => 62, 't' => 64 ),
			25 => array( 'f' => 1, 't' => 2 )
		);

		$p[360] = array( 25 => array( 'f' => 3, 't' => 11 ) );
		$p[361] = array( 25 => array( 'f' => 12, 't' => 20 ) );
		$p[362] = array( 25 => array( 'f' => 21, 't' => 32 ) );
		$p[363] = array( 25 => array( 'f' => 33, 't' => 43 ) );
		$p[364] = array( 25 => array( 'f' => 44, 't' => 55 ) );
		$p[365] = array( 25 => array( 'f' => 56, 't' => 67 ) );
		$p[366] = array( 25 => array( 'f' => 68, 't' => 77 ) );

		$p[367] = array( 26 => array( 'f' => 1, 't' => 19 ) );
		$p[368] = array( 26 => array( 'f' => 20, 't' => 39 ) );
		$p[369] = array( 26 => array( 'f' => 40, 't' => 60 ) );
		$p[370] = array( 26 => array( 'f' => 61, 't' => 83 ) );
		$p[371] = array( 26 => array( 'f' => 84, 't' => 111 ) );
		$p[372] = array( 26 => array( 'f' => 112, 't' => 136 ) );
		$p[373] = array( 26 => array( 'f' => 137, 't' => 159 ) );
		$p[374] = array( 26 => array( 'f' => 160, 't' => 183 ) );
		$p[375] = array( 26 => array( 'f' => 184, 't' => 206 ) );
		$p[376] = array( 26 => array( 'f' => 207, 't' => 227 ) );

		$p[377] = array( 27 => array( 'f' => 1, 't' => 13 ) );
		$p[378] = array( 27 => array( 'f' => 14, 't' => 22 ) );
		$p[379] = array( 27 => array( 'f' => 23, 't' => 35 ) );
		$p[380] = array( 27 => array( 'f' => 36, 't' => 44 ) );
		$p[381] = array( 27 => array( 'f' => 45, 't' => 55 ) );
		$p[382] = array( 27 => array( 'f' => 56, 't' => 63 ) );
		$p[383] = array( 27 => array( 'f' => 64, 't' => 76 ) );
		$p[384] = array( 27 => array( 'f' => 77, 't' => 88 ) );
		$p[385] = array(
			27 => array( 'f' => 89, 't' => 93 ),
			28 => array( 'f' => 1, 't' => 5 )
		);

		$p[386] = array( 28 => array( 'f' => 6, 't' => 13 ) );
		$p[387] = array( 28 => array( 'f' => 14, 't' => 21 ) );
		$p[388] = array( 28 => array( 'f' => 22, 't' => 28 ) );
		$p[389] = array( 28 => array( 'f' => 29, 't' => 35 ) );
		$p[390] = array( 28 => array( 'f' => 36, 't' => 43 ) );
		$p[391] = array( 28 => array( 'f' => 44, 't' => 50 ) );
		$p[392] = array( 28 => array( 'f' => 51, 't' => 59 ) );
		$p[393] = array( 28 => array( 'f' => 60, 't' => 70 ) );
		$p[394] = array( 28 => array( 'f' => 71, 't' => 77 ) );
		$p[395] = array( 28 => array( 'f' => 78, 't' => 84 ) );
		$p[396] = array(
			28 => array( 'f' => 85, 't' => 88 ),
			29 => array( 'f' => 1, 't' => 6 )
		);

		$p[397] = array( 29 => array( 'f' => 7, 't' => 14 ) );
		$p[398] = array( 29 => array( 'f' => 15, 't' => 23 ) );
		$p[399] = array( 29 => array( 'f' => 24, 't' => 30 ) );
		$p[400] = array( 29 => array( 'f' => 31, 't' => 38 ) );
		$p[401] = array( 29 => array( 'f' => 39, 't' => 45 ) );
		$p[402] = array( 29 => array( 'f' => 46, 't' => 52 ) );
		$p[403] = array( 29 => array( 'f' => 53, 't' => 63 ) );
		$p[404] = array(
			29 => array( 'f' => 64, 't' => 69 ),
			30 => array( 'f' => 1, 't' => 5 )
		);

		$p[405] = array( 30 => array( 'f' => 6, 't' => 15 ) );
		$p[406] = array( 30 => array( 'f' => 16, 't' => 24 ) );
		$p[407] = array( 30 => array( 'f' => 25, 't' => 32 ) );
		$p[408] = array( 30 => array( 'f' => 33, 't' => 41 ) );
		$p[409] = array( 30 => array( 'f' => 42, 't' => 50 ) );
		$p[410] = array( 30 => array( 'f' => 51, 't' => 60 ) );

		$p[411] = array( 31 => array( 'f' => 1, 't' => 11 ) );
		$p[412] = array( 31 => array( 'f' => 12, 't' => 19 ) );
		$p[413] = array( 31 => array( 'f' => 20, 't' => 28 ) );
		$p[414] = array( 31 => array( 'f' => 29, 't' => 34 ) );

		$p[415] = array( 32 => array( 'f' => 1, 't' => 11 ) );
		$p[416] = array( 32 => array( 'f' => 12, 't' => 20 ) );
		$p[417] = array( 32 => array( 'f' => 21, 't' => 30 ) );

		$p[418] = array( 33 => array( 'f' => 1, 't' => 6 ) );
		$p[419] = array( 33 => array( 'f' => 7, 't' => 15 ) );
		$p[420] = array( 33 => array( 'f' => 16, 't' => 22 ) );
		$p[421] = array( 33 => array( 'f' => 23, 't' => 30 ) );
		$p[422] = array( 33 => array( 'f' => 31, 't' => 35 ) );
		$p[423] = array( 33 => array( 'f' => 36, 't' => 43 ) );
		$p[424] = array( 33 => array( 'f' => 44, 't' => 50 ) );
		$p[425] = array( 33 => array( 'f' => 51, 't' => 54 ) );
		$p[426] = array( 33 => array( 'f' => 55, 't' => 62 ) );
		$p[427] = array( 33 => array( 'f' => 63, 't' => 73 ) );

		$p[428] = array( 34 => array( 'f' => 1, 't' => 7 ) );
		$p[429] = array( 34 => array( 'f' => 8, 't' => 14 ) );
		$p[430] = array( 34 => array( 'f' => 15, 't' => 22 ) );
		$p[431] = array( 34 => array( 'f' => 23, 't' => 31 ) );
		$p[432] = array( 34 => array( 'f' => 32, 't' => 39 ) );
		$p[433] = array( 34 => array( 'f' => 40, 't' => 48 ) );
		$p[434] = array(
			34 => array( 'f' => 49, 't' => 54 ),
			35 => array( 'f' => 1, 't' => 3 )
		);

		$p[435] = array( 35 => array( 'f' => 4, 't' => 11 ) );
		$p[436] = array( 35 => array( 'f' => 12, 't' => 18 ) );
		$p[437] = array( 35 => array( 'f' => 19, 't' => 30 ) );
		$p[438] = array( 35 => array( 'f' => 31, 't' => 38 ) );
		$p[439] = array( 35 => array( 'f' => 39, 't' => 44 ) );
		$p[440] = array(
			35 => array( 'f' => 45, 't' => 45 ),
			36 => array( 'f' => 1, 't' => 12 ),
		);

		$p[441] = array( 36 => array( 'f' => 13, 't' => 27 ) );
		$p[442] = array( 36 => array( 'f' => 28, 't' => 40 ) );
		$p[443] = array( 36 => array( 'f' => 41, 't' => 54 ) );
		$p[444] = array( 36 => array( 'f' => 55, 't' => 70 ) );
		$p[445] = array( 36 => array( 'f' => 71, 't' => 83 ) );

		$p[446] = array( 37 => array( 'f' => 1, 't' => 24 ) );
		$p[447] = array( 37 => array( 'f' => 25, 't' => 51 ) );
		$p[448] = array( 37 => array( 'f' => 52, 't' => 76 ) );
		$p[449] = array( 37 => array( 'f' => 77, 't' => 102 ) );
		$p[450] = array( 37 => array( 'f' => 103, 't' => 126 ) );
		$p[451] = array( 37 => array( 'f' => 127, 't' => 153 ) );
		$p[452] = array( 37 => array( 'f' => 154, 't' => 182 ) );

		$p[453] = array( 38 => array( 'f' => 1, 't' => 16 ) );
		$p[454] = array( 38 => array( 'f' => 17, 't' => 26 ) );
		$p[455] = array( 38 => array( 'f' => 27, 't' => 42 ) );
		$p[456] = array( 38 => array( 'f' => 43, 't' => 61 ) );
		$p[457] = array( 38 => array( 'f' => 62, 't' => 83 ) );
		$p[458] = array(
			38 => array( 'f' => 84, 't' => 88 ),
			39 => array( 'f' => 1, 't' => 5 )
		);

		$p[459] = array( 39 => array( 'f' => 6, 't' => 10 ) );
		$p[460] = array( 39 => array( 'f' => 11, 't' => 21 ) );
		$p[461] = array( 39 => array( 'f' => 22, 't' => 31 ) );
		$p[462] = array( 39 => array( 'f' => 32, 't' => 40 ) );
		$p[463] = array( 39 => array( 'f' => 41, 't' => 47 ) );
		$p[464] = array( 39 => array( 'f' => 48, 't' => 56 ) );
		$p[465] = array( 39 => array( 'f' => 57, 't' => 67 ) );
		$p[466] = array( 39 => array( 'f' => 68, 't' => 74 ) );
		$p[467] = array(
			39 => array( 'f' => 75, 't' => 75 ),
			40 => array( 'f' => 1, 't' => 7 )
		);

		$p[468] = array( 40 => array( 'f' => 8, 't' => 16 ) );
		$p[469] = array( 40 => array( 'f' => 17, 't' => 25 ) );
		$p[470] = array( 40 => array( 'f' => 26, 't' => 33 ) );
		$p[471] = array( 40 => array( 'f' => 34, 't' => 40 ) );
		$p[472] = array( 40 => array( 'f' => 41, 't' => 49 ) );
		$p[473] = array( 40 => array( 'f' => 50, 't' => 58 ) );
		$p[474] = array( 40 => array( 'f' => 59, 't' => 66 ) );
		$p[475] = array( 40 => array( 'f' => 67, 't' => 77 ) );
		$p[476] = array( 40 => array( 'f' => 78, 't' => 85 ) );

		$p[477] = array( 41 => array( 'f' => 1, 't' => 11 ) );
		$p[478] = array( 41 => array( 'f' => 12, 't' => 20 ) );
		$p[479] = array( 41 => array( 'f' => 21, 't' => 29 ) );
		$p[480] = array( 41 => array( 'f' => 30, 't' => 38 ) );
		$p[481] = array( 41 => array( 'f' => 39, 't' => 46 ) );
		$p[482] = array( 41 => array( 'f' => 47, 't' => 54 ) );

		$p[483] = array( 42 => array( 'f' => 1, 't' => 10 ) );
		$p[484] = array( 42 => array( 'f' => 11, 't' => 15 ) );
		$p[485] = array( 42 => array( 'f' => 16, 't' => 22 ) );
		$p[486] = array( 42 => array( 'f' => 23, 't' => 31 ) );
		$p[487] = array( 42 => array( 'f' => 32, 't' => 44 ) );
		$p[488] = array( 42 => array( 'f' => 45, 't' => 51 ) );
		$p[489] = array(
			42 => array( 'f' => 52, 't' => 53 ),
			43 => array( 'f' => 1, 't' => 10 )
		);

		$p[490] = array( 43 => array( 'f' => 11, 't' => 22 ) );
		$p[491] = array( 43 => array( 'f' => 23, 't' => 33 ) );
		$p[492] = array( 43 => array( 'f' => 34, 't' => 47 ) );
		$p[493] = array( 43 => array( 'f' => 48, 't' => 60 ) );
		$p[494] = array( 43 => array( 'f' => 61, 't' => 73 ) );
		$p[495] = array( 43 => array( 'f' => 74, 't' => 89 ) );

		$p[496] = array( 44 => array( 'f' => 1, 't' => 18 ) );
		$p[497] = array( 44 => array( 'f' => 19, 't' => 39 ) );
		$p[498] = array( 44 => array( 'f' => 40, 't' => 59 ) );

		$p[499] = array( 45 => array( 'f' => 1, 't' => 13 ) );
		$p[500] = array( 45 => array( 'f' => 14, 't' => 22 ) );
		$p[501] = array( 45 => array( 'f' => 23, 't' => 32 ) );
		$p[502] = array(
			45 => array( 'f' => 33, 't' => 37 ),
			46 => array( 'f' => 1, 't' => 5 ),
		);

		$p[503] = array( 46 => array( 'f' => 6, 't' => 14 ) );
		$p[504] = array( 46 => array( 'f' => 15, 't' => 20 ) );
		$p[505] = array( 46 => array( 'f' => 21, 't' => 28 ) );
		$p[506] = array( 46 => array( 'f' => 29, 't' => 35 ) );

		$p[507] = array( 47 => array( 'f' => 1, 't' => 11 ) );
		$p[508] = array( 47 => array( 'f' => 12, 't' => 19 ) );
		$p[509] = array( 47 => array( 'f' => 20, 't' => 29 ) );
		$p[510] = array( 47 => array( 'f' => 30, 't' => 38 ) );

		$p[511] = array( 48 => array( 'f' => 1, 't' => 9 ) );
		$p[512] = array( 48 => array( 'f' => 10, 't' => 15 ) );
		$p[513] = array( 48 => array( 'f' => 16, 't' => 23 ) );
		$p[514] = array( 48 => array( 'f' => 24, 't' => 28 ) );
		$p[515] = array(
			48 => array( 'f' => 29, 't' => 29 ),
			49 => array( 'f' => 1, 't' => 4 )
		);

		$p[516] = array( 49 => array( 'f' => 5, 't' => 11 ) );
		$p[517] = array( 49 => array( 'f' => 12, 't' => 18 ) );

		$p[518] = array( 50 => array( 'f' => 1, 't' => 15 ) );
		$p[519] = array( 50 => array( 'f' => 16, 't' => 35 ) );
		$p[520] = array(
			50 => array( 'f' => 36, 't' => 45 ),
			51 => array( 'f' => 1, 't' => 6 ),
		);

		$p[521] = array( 51 => array( 'f' => 7, 't' => 30 ) );
		$p[522] = array( 51 => array( 'f' => 31, 't' => 51 ) );
		$p[523] = array(
			51 => array( 'f' => 52, 't' => 60 ),
			52 => array( 'f' => 1, 't' => 14 )
		);

		$p[524] = array( 52 => array( 'f' => 15, 't' => 31 ) );
		$p[525] = array( 52 => array( 'f' => 32, 't' => 49 ) );

		$p[526] = array( 53 => array( 'f' => 1, 't' => 26 ) );
		$p[527] = array( 53 => array( 'f' => 27, 't' => 44 ) );
		$p[528] = array(
			53 => array( 'f' => 45, 't' => 62 ),
			54 => array( 'f' => 1, 't' => 6 ),
		);

		$p[529] = array( 54 => array( 'f' => 7, 't' => 27 ) );
		$p[530] = array( 54 => array( 'f' => 28, 't' => 49 ) );
		$p[531] = array(
			54 => array( 'f' => 50, 't' => 55 ),
			55 => array( 'f' => 1, 't' => 18 ),
		);

		$p[532] = array( 55 => array( 'f' => 19, 't' => 41 ) );
		$p[533] = array( 55 => array( 'f' => 42, 't' => 69 ) );
		$p[534] = array(
			55 => array( 'f' => 70, 't' => 78 ),
			56 => array( 'f' => 1, 't' => 16 )
		);

		$p[535] = array( 56 => array( 'f' => 17, 't' => 50 ) );
		$p[536] = array( 56 => array( 'f' => 51, 't' => 76 ) );
		$p[537] = array(
			56 => array( 'f' => 77, 't' => 96 ),
			57 => array( 'f' => 1, 't' => 3 )
		);

		$p[538] = array( 57 => array( 'f' => 4, 't' => 11 ) );
		$p[539] = array( 57 => array( 'f' => 12, 't' => 18 ) );
		$p[540] = array( 57 => array( 'f' => 19, 't' => 24 ) );
		$p[541] = array( 57 => array( 'f' => 25, 't' => 29 ) );

		$p[542] = array( 58 => array( 'f' => 1, 't' => 6 ) );
		$p[543] = array( 58 => array( 'f' => 7, 't' => 11 ) );
		$p[544] = array( 58 => array( 'f' => 12, 't' => 21 ) );
		$p[545] = array(
			58 => array( 'f' => 22, 't' => 22 ),
			59 => array( 'f' => 1, 't' => 3 )
		);

		$p[546] = array( 59 => array( 'f' => 4, 't' => 9 ) );
		$p[547] = array( 59 => array( 'f' => 10, 't' => 16 ) );
		$p[548] = array( 59 => array( 'f' => 17, 't' => 24 ) );

		$p[549] = array( 60 => array( 'f' => 1, 't' => 5 ) );
		$p[550] = array( 60 => array( 'f' => 6, 't' => 11 ) );
		$p[551] = array(
			60 => array( 'f' => 12, 't' => 13 ),
			61 => array( 'f' => 1, 't' => 5 ),
		);

		$p[552] = array( 61 => array( 'f' => 6, 't' => 14 ) );
		$p[553] = array( 62 => array( 'f' => 1, 't' => 8 ) );
		$p[554] = array(
			62 => array( 'f' => 9, 't' => 11 ),
			63 => array( 'f' => 1, 't' => 4 ),
		);
		$p[555] = array( 63 => array( 'f' => 5, 't' => 11 ) );

		$p[556] = array( 64 => array( 'f' => 1, 't' => 9 ) );
		$p[557] = array( 64 => array( 'f' => 10, 't' => 18 ) );

		$p[558] = array( 65 => array( 'f' => 1, 't' => 5 ) );
		$p[559] = array( 65 => array( 'f' => 6, 't' => 12 ) );

		$p[560] = array( 66 => array( 'f' => 1, 't' => 7 ) );
		$p[561] = array( 66 => array( 'f' => 8, 't' => 12 ) );

		$p[562] = array( 67 => array( 'f' => 1, 't' => 12 ) );
		$p[563] = array( 67 => array( 'f' => 13, 't' => 26 ) );
		$p[564] = array(
			67 => array( 'f' => 27, 't' => 30 ),
			68 => array( 'f' => 1, 't' => 16 )
		);

		$p[565] = array( 68 => array( 'f' => 17, 't' => 42 ) );
		$p[566] = array(
			68 => array( 'f' => 43, 't' => 52 ),
			69 => array( 'f' => 1, 't' => 8 )
		);

		$p[567] = array( 69 => array( 'f' => 9, 't' => 35 ) );
		$p[568] = array(
			69 => array( 'f' => 36, 't' => 52 ),
			70 => array( 'f' => 1, 't' => 10 )
		);

		$p[569] = array( 70 => array( 'f' => 11, 't' => 40 ) );
		$p[570] = array(
			70 => array( 'f' => 41, 't' => 44 ),
			71 => array( 'f' => 1, 't' => 10 )
		);

		$p[571] = array( 71 => array( 'f' => 11, 't' => 28 ) );
		$p[572] = array( 72 => array( 'f' => 1, 't' => 13 ) );
		$p[573] = array( 72 => array( 'f' => 14, 't' => 28 ) );

		$p[574] = array( 73 => array( 'f' => 1, 't' => 19 ) );
		$p[575] = array(
			73 => array( 'f' => 20, 't' => 20 ),
			74 => array( 'f' => 1, 't' => 18 )
		);

		$p[576] = array( 74 => array( 'f' => 19, 't' => 47 ) );
		$p[577] = array(
			74 => array( 'f' => 48, 't' => 56 ),
			75 => array( 'f' => 1, 't' => 19 ),
		);

		$p[578] = array(
			75 => array( 'f' => 20, 't' => 40 ),
			76 => array( 'f' => 1, 't' => 5 )
		);

		$p[579] = array( 76 => array( 'f' => 6, 't' => 25 ) );
		$p[580] = array(
			76 => array( 'f' => 26, 't' => 31 ),
			77 => array( 'f' => 1, 't' => 19 ),
		);

		$p[581] = array( 77 => array( 'f' => 20, 't' => 50 ) );

		$p[582] = array( 78 => array( 'f' => 1, 't' => 30 ) );
		$p[583] = array(
			78 => array( 'f' => 31, 't' => 40 ),
			79 => array( 'f' => 1, 't' => 16 )
		);

		$p[584] = array( 79 => array( 'f' => 17, 't' => 46 ) );

		$p[585] = array( 80 => array( 'f' => 1, 't' => 40 ) );
		$p[586] = array(
			80 => array( 'f' => 41, 't' => 42 ),
			81 => array( 'f' => 1, 't' => 29 )
		);

		$p[587] = array(
			82 => array( 'f' => 1, 't' => 19 ),
			83 => array( 'f' => 1, 't' => 4 ),
		);

		$p[588] = array( 83 => array( 'f' => 5, 't' => 33 ) );
		$p[589] = array(
			83 => array( 'f' => 34, 't' => 36 ),
			84 => array( 'f' => 1, 't' => 24 )
		);

		$p[590] = array(
			84 => array( 'f' => 25, 't' => 25 ),
			85 => array( 'f' => 1, 't' => 22 )
		);

		$p[591] = array(
			86 => array( 'f' => 1, 't' => 17 ),
			87 => array( 'f' => 1, 't' => 10 )
		);

		$p[592] = array(
			87 => array( 'f' => 11, 't' => 19 ),
			88 => array( 'f' => 1, 't' => 22 )
		);

		$p[593] = array(
			88 => array( 'f' => 23, 't' => 26 ),
			89 => array( 'f' => 1, 't' => 22 )
		);

		$p[594] = array(
			89 => array( 'f' => 23, 't' => 30 ),
			90 => array( 'f' => 1, 't' => 18 )
		);

		$p[595] = array(
			90 => array( 'f' => 19, 't' => 20 ),
			91 => array( 'f' => 1, 't' => 15 ),
			92 => array( 'f' => 1, 't' => 9 )
		);

		$p[596] = array(
			92 => array( 'f' => 10, 't' => 21 ),
			93 => array( 'f' => 1, 't' => 11 ),
			94 => array( 'f' => 1, 't' => 2 )
		);

		$p[597] = array(
			94 => array( 'f' => 3, 't' => 8 ),
			95 => array( 'f' => 1, 't' => 8 ),
			96 => array( 'f' => 1, 't' => 12 )
		);

		$p[598] = array(
			96 => array( 'f' => 13, 't' => 19 ),
			97 => array( 'f' => 1, 't' => 5 ),
			98 => array( 'f' => 1, 't' => 5 )
		);

		$p[599] = array(
			98 => array( 'f' => 6, 't' => 8 ),
			99 => array( 'f' => 1, 't' => 8 ),
			100 => array( 'f' => 1, 't' => 5 )
		);

		$p[600] = array(
			100 => array( 'f' => 6, 't' => 11 ),
			101 => array( 'f' => 1, 't' => 11 ),
			102 => array( 'f' => 1, 't' => 8 )
		);

		$p[601] = array(
			103 => array( 'f' => 1, 't' => 3 ),
			104 => array( 'f' => 1, 't' => 9 ),
			105 => array( 'f' => 1, 't' => 5 )
		);

		$p[602] = array(
			106 => array( 'f' => 1, 't' => 4 ),
			107 => array( 'f' => 1, 't' => 7 ),
			108 => array( 'f' => 1, 't' => 3 )
		);

		$p[603] = array(
			109 => array( 'f' => 1, 't' => 6 ),
			110 => array( 'f' => 1, 't' => 3 ),
			111 => array( 'f' => 1, 't' => 5 )
		);

		$p[604] = array(
			112 => array( 'f' => 1, 't' => 4 ),
			113 => array( 'f' => 1, 't' => 5 ),
			114 => array( 'f' => 1, 't' => 6 )
		);

		return $p;
	}

	function output(){
		if( $this->action() == 'error' ){
			$output = array( 'status' => 'error', 'msg' => 'Not found action' );
		}elseif( $this->action() == 'surah' ){
			$output = $this->api_surah();
		}elseif( $this->action() == 'languages' ){
			$output = $this->api_get_languages();
		}elseif( $this->action() == 'readers' ){
			$output = $this->api_get_readers();
		}elseif( $this->action() == 'ayah_readers' ){
			$output = $this->api_get_ayah_readers();
		}elseif( $this->action() == 'surah_loop' ){
			$output = $this->api_surah_loop();
		}elseif( $this->action() == 'tafseer' ){
			$output = $this->api_tafseer();
		}elseif( $this->action() == 'tafseer_view' ){
			$output = $this->api_tafseer_view();
		}else{
			$output = array( 'status' => 'error', 'msg' => 'Action is empty' );
		}

		return $output;
	}

}
?>
