<?php
function addRootPath( $root = false ){
	$root_path = dirname(dirname(__DIR__));
	if( $root ){
		$path = $root_path.'/api/books/';
	}else{
		$path = '';
	}
	return $path;
}

class MUSLIM_LIBRARY {
	public $site_url;
	public $data_categories;
	public $data_sub_categories;
	public $data_IDs;
	public $data_books;
	public $parent_id;
	public $category_id;
	public $book_ids;
	public $root_path;

	public function __construct( $root = false ){
		global $B;
		$this->root_path = addRootPath( $root );

		require_once( $this->root_path.'includes/categories.php' );
		//require_once( $this->root_path.'includes/data.php' );
		require_once( $this->root_path.'includes/books-1.php' );
		require_once( $this->root_path.'includes/books-2.php' );
		require_once( $this->root_path.'includes/books-3.php' );

		$this->parent_id = ( isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0 );
		$this->category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : 0 );
		$this->book_ids = ( isset($_GET['book_ids']) ? strip_tags($_GET['book_ids']) : '' );
		$this->site_url = $this->site_url();
		$this->data_categories = books_categories();
		$this->data_sub_categories = books_sub_categories( $this->parent_id );
		$this->data_IDs = books_IDs( $this->category_id );
		$this->data_books = $B;
		//$this->data_books = books();

	}

	public function site_url( $url='' ){
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

	public function action(){
		return ( isset($_GET['action']) ? strip_tags($_GET['action']) : 'error' );
	}

	public function languages(){
		if( is_array($this->data_categories) ){
			$output = array();
			$output['status'] = 'ok';
			$output['action'] = 'languages';
			$i=0;
			foreach($this->data_categories as $key => $value){
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$title = ( isset($value['title']) ? $value['title'] : 'none' );
				$locale = ( isset($value['locale']) ? $value['locale'] : 'en' );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );
				$cats = $this->categories( $id, 1 );

				$books = $this->IDs( $id );
				$books_count = ( is_array($books) ? count($books) : '' );
				$books_data = ( is_array($books) ? $books : '' );

				++$i;

				if( isset($cats['status']) && $cats['status'] == 'error' ){
					$output['data'][] = array( 'n' => $i, 'key' => $key, 'id' => $id, 'books_count' => $books_count, 'title' => $title, 'locale' => $locale, 'flag' => $this->site_url( 'images/flags/'.$flag ) );
				}else{
					$output['data'][] = array( 'n' => $i, 'key' => $key, 'id' => $id, 'books_count' => $books_count, 'title' => $title, 'locale' => $locale, 'flag' => $this->site_url( 'images/flags/'.$flag ), 'categories' => $this->categories( $id, 1 ) );
				}

			}
			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'Languages function is not array' );
		}
	}

	public function language( $set_name = '' ){
		$name = ( isset($_GET['name']) ? $_GET['name'] : '' );

		if( !empty($set_name) ){
			$name = $set_name;
		}

		if( empty($name) ){
			return array( 'status' => 'error', 'msg' => 'Name is not empty' );
		}else{
			if( is_array($this->data_categories) && array_key_exists($name, $this->data_categories) ){
				$value = $this->data_categories[$name];
				$id = ( isset($value['id']) ? $value['id'] : 0 );
				$title = ( isset($value['title']) ? $value['title'] : 'none' );
				$locale = ( isset($value['locale']) ? $value['locale'] : 'en' );
				$flag = ( isset($value['flag']) ? $value['flag'] : '' );

				$cats = $this->categories( $id, 1 );

				$books = $this->IDs( $id );
				$books_count = ( is_array($books) ? count($books) : '' );
				$books_data = ( is_array($books) ? $books : '' );

				return array( 'status' => 'ok', 'id' => $id, 'books_count' => $books_count, 'books_data' => $books_data, 'title' => $title, 'locale' => $locale, 'flag' => $this->site_url( 'images/flags/'.$flag ), 'categories' => $this->categories( $id, 1 ) );
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found key' );
			}
		}
	}

	public function categories( $parent = '', $type = 0 ){
		if( empty($parent) ){
			$get_parent = $this->parent_id;
		}else{
			$get_parent = $parent;
		}

		if( is_array($this->data_sub_categories) && array_key_exists($get_parent, $this->data_sub_categories) ){
			$categories = $this->data_sub_categories[$get_parent];
			$output = array();
			if( $type != 1 ){
				$output['status'] = 'ok';
				$output['action'] = 'categories';
			}
			$i=0;
			ksort($categories);
			foreach($categories as $key => $value){
				if( is_array($value) ){
					$name = ( isset($value['name']) ? $value['name'] : '' );
					$description = ( isset($value['description']) ? $value['description'] : '' );
				}else{
					$name = $value;
					$description = '';
				}

				$books = $this->IDs( $key );
				$books_count = ( is_array($books) ? count($books) : '' );
				$books_data = ( is_array($books) ? $books : '' );

				++$i;

				if( $name != '' ){
					if( $type == 1 ){
						$output[] = array( 'n' => $i, 'id' => $key, 'books_count' => $books_count, 'name' => $name );
					}else{
						$output['data'][] = array( 'n' => $i, 'id' => $key, 'books_count' => $books_count, 'name' => $name );
					}
				}

			}
			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'Categories function is not array' );
		}
	}

	public function IDs( $category_id = '' ){
		if( empty($category_id) ){
			$get_category_id = $this->category_id;
		}else{
			$get_category_id = $category_id;
		}

		if( is_array($this->data_IDs) && array_key_exists($get_category_id, $this->data_IDs) ){
			sort($this->data_IDs[$get_category_id]);
			return $this->data_IDs[$get_category_id];
		}else{
			return 0;
		}
	}

	public function books( $set_IDs = '' ){
		$books = $this->data_books;

		$IDs = explode(',', $this->book_ids);
		if( !empty($set_IDs) ){
			$IDs = explode(',', $set_IDs);
		}

		if( is_array($IDs) && is_array($books) && count($IDs) > 0 && count($books) > 0 ){
			$i=0;
			$output_books = array();
			foreach( $IDs as $key => $value ){
				if( array_key_exists($value, $books) ){
					$book = $books[$value];
					$id = $value;
					$title = ( isset($book['title']) ? $book['title'] : '' );
					$excerpt = ( isset($book['excerpt']) ? $book['excerpt'] : '' );
					$url = ( isset($book['url']) ? $book['url'] : '' );
					$image = ( isset($book['image']) ? $book['image'] : '' );
					$author_id = ( isset($book['author_id']) ? $book['author_id'] : '' );
					$author_name = ( isset($book['author_name']) ? $book['author_name'] : '' );
					$book_url = ( isset($book['book_url']) ? $book['book_url'] : '' );
					$publisher = ( isset($book['publisher']) ? $book['publisher'] : '' );
					$translator = ( isset($book['translator']) ? $book['translator'] : '' );
					++$i;
					$output_books[] = array( 'id' => $id, 'title' => $title, 'excerpt' => $excerpt, 'url' => $url, 'image' => $image, 'author_id' => $author_id, 'author_name' => $author_name, 'book_url' => $book_url, 'publisher' => $publisher, 'translator' => $translator );
					if( $i == 50 ) break;
				}
			}
			$output = array();
			$output['status'] = 'ok';
			$output['action'] = 'books';
			$output['count'] = count($output_books);
			$output['data'] = $output_books;

			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found books' );
		}
	}

	public function category( $set_category_id = '' ){
		$category_id = ( isset($_GET['id']) ? intval($_GET['id']) : '' );

		if( !empty($set_category_id) ){
			$category_id = $set_category_id;
		}

		if( empty($category_id) ){
			return array( 'status' => 'error', 'msg' => 'Category ID is not empty' );
		}else{
			if( is_array($this->data_sub_categories) ){
				$parent_id = array();
				foreach($this->data_sub_categories as $keyx => $valuex){
					if( is_array($valuex) ){
						foreach( $valuex as $keyr => $valuer ){
							if( $category_id == $keyr ) {
								$parent_id[] = $keyx;
							}
						}
					}
				}
				$get_parent_id = ( isset($parent_id[0]) ? $parent_id[0] : 0 );

				$name = array();
				$name2 = array();
				$flag = array();
				foreach($this->data_categories as $keyl => $valuel){
					$language_id = ( isset($valuel['id']) ? $valuel['id'] : 0 );
					$language_title = ( isset($valuel['title']) ? $valuel['title'] : 'none' );
					$language_locale = ( isset($valuel['locale']) ? $valuel['locale'] : 'en' );
					$language_flag = ( isset($valuel['flag']) ? $valuel['flag'] : '' );
					if( $get_parent_id == $language_id ){
						$name[] = $language_title;
						$name2[] = $keyl;
						$flag[] = $language_flag;
						break;
					}
				}
				$get_name = ( isset($name[0]) ? $name[0] : '' );
				$get_name2 = ( isset($name2[0]) ? $name2[0] : '' );
				$get_flag = ( isset($flag[0]) ? $flag[0] : '' );

				$category_name = '';
				$category_description = '';
				if( isset($this->data_sub_categories[$get_parent_id][$category_id]) ){
					$category_info = $this->data_sub_categories[$get_parent_id][$category_id];
					$category_name = ( is_array($category_info) && isset($category_info['name']) ? $category_info['name'] : $category_info );
					$category_description = ( is_array($category_info) && isset($category_info['description']) ? $category_info['description'] : '' );
				}

				$books = $this->IDs( $category_id );
				$books_count = ( is_array($books) ? count($books) : '' );
				$books_data = ( is_array($books) ? $books : '' );

				return array( 'status' => 'ok', 'action' => 'category', 'id' => $category_id, 'title' => strip_tags($category_name), 'category_description' => strip_tags($category_description), 'books_count' => $books_count, 'books_data' => $books_data, 'parent_id' => $get_parent_id, 'parent_key' => strip_tags($get_name2), 'parent_name' => $get_name, 'parent_flag' => $this->site_url( 'images/flags/'.$get_flag ) );
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found key' );
			}
		}
	}

	public function books_rand(){
		$category_id = ( isset($_GET['category_id']) ? intval($_GET['category_id']) : '' );
		$limit = ( isset($_GET['count']) ? intval($_GET['count']) : 10 );
		if( $limit > 50 ){
			$limit = 50;
		}

		if( empty($category_id) ){
			return array( 'status' => 'error', 'msg' => 'Category ID is not empty' );
		}else{
			if( is_array($this->data_sub_categories) ){
				$parent_id = array();
				foreach($this->data_sub_categories as $keyx => $valuex){
					if( is_array($valuex) ){
						foreach( $valuex as $keyr => $valuer ){
							if( $category_id == $keyr ) {
								$parent_id[] = $keyx;
							}
						}
					}
				}
				$get_parent_id = ( isset($parent_id[0]) ? $parent_id[0] : 0 );

				$name = array();
				$name2 = array();
				$flag = array();
				foreach($this->data_categories as $keyl => $valuel){
					$language_id = ( isset($valuel['id']) ? $valuel['id'] : 0 );
					$language_title = ( isset($valuel['title']) ? $valuel['title'] : 'none' );
					$language_locale = ( isset($valuel['locale']) ? $valuel['locale'] : 'en' );
					$language_flag = ( isset($valuel['flag']) ? $valuel['flag'] : '' );
					if( $get_parent_id == $language_id || $category_id == $language_id ){
						$name[] = $language_title;
						$name2[] = $keyl;
						$flag[] = $language_flag;
						break;
					}
				}
				$get_name = ( isset($name[0]) ? $name[0] : '' );
				$get_name2 = ( isset($name2[0]) ? $name2[0] : '' );
				$get_flag = ( isset($flag[0]) ? $flag[0] : '' );

				$category_name = '';
				$category_description = '';
				if( isset($this->data_sub_categories[$get_parent_id][$category_id]) ){
					$category_info = $this->data_sub_categories[$get_parent_id][$category_id];
					$category_name = ( isset($category_info['name']) ? $category_info['name'] : $category_info );
					$category_description = ( isset($category_info['description']) ? $category_info['description'] : '' );
				}

				$books = $this->IDs( $category_id );
				$books_count = ( is_array($books) ? count($books) : '' );
				//$books_data = ( is_array($books) ? $books : '' );

				$random_number = range(0, $books_count);
				shuffle( $random_number );
				$random_number = array_slice($random_number, 0 ,$limit);

				$random_books = array();
				if( is_array($random_number) ){
					foreach( $random_number as $key => $value ){
						if( isset($books[$value]) ){
							$random_books[] = $books[$value];
						}
					}
				}

				return array( 'status' => 'ok', 'action' => 'rand', 'books_count' => $books_count, 'books_data' => $random_books, 'language_id' => $get_parent_id, 'language_key' => strip_tags($get_name2), 'language_name' => $get_name, 'parent_flag' => $this->site_url( 'images/flags/'.$get_flag ), 'category_id' => $category_id, 'category_title' => $category_name, 'category_description' => $category_description );
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found key' );
			}
		}
	}

	public function book( $set_book_id = '' ){
		$book_id = ( isset($_GET['id']) ? intval($_GET['id']) : '' );
		if( !empty($set_book_id) ){
			$book_id = $set_book_id;
		}
		$books = $this->data_books;
		if( array_key_exists($book_id, $books) ){
			$book = $books[$book_id];
			$title = ( isset($book['title']) ? $book['title'] : '' );
			$excerpt = ( isset($book['excerpt']) ? $book['excerpt'] : '' );
			$url = ( isset($book['url']) ? $book['url'] : '' );
			$image = ( isset($book['image']) ? $book['image'] : '' );
			$author_id = ( isset($book['author_id']) ? $book['author_id'] : '' );
			$author_name = ( isset($book['author_name']) ? $book['author_name'] : '' );
			$book_url = ( isset($book['book_url']) ? $book['book_url'] : '' );
			$publisher = ( isset($book['publisher']) ? $book['publisher'] : '' );
			$translator = ( isset($book['translator']) ? $book['translator'] : '' );

			$langs = array();
			foreach($this->data_categories as $key => $value){
				$language_id = ( isset($value['id']) ? $value['id'] : 0 );
				$language_title = ( isset($value['title']) ? $value['title'] : 'none' );
				$language_locale = ( isset($value['locale']) ? $value['locale'] : 'en' );
				$language_flag = ( isset($value['flag']) ? $value['flag'] : '' );

				$cats = array();
				if( is_array($this->data_sub_categories) && array_key_exists($language_id, $this->data_sub_categories) ){
					$categories = $this->data_sub_categories[$language_id];
					foreach($categories as $keyc => $valuec){
						if( is_array($valuec) ){
							$name = ( isset($valuec['name']) ? $valuec['name'] : '' );
							$description = ( isset($valuec['description']) ? $valuec['description'] : '' );
						}else{
							$name = $valuec;
							$description = '';
						}

						$books_IDS = $this->IDs( $keyc );
						if( in_array($book_id, $books_IDS) ){
							$cats[] = array( 'category_id' => $keyc, 'name' => $name, 'description' => $description );
						}

					}
				}

				$books_IDS2 = $this->IDs( $language_id );
				if( in_array($book_id, $books_IDS2) ){
					$langs[] = array( 'language_id' => $language_id, 'name' => $language_title, 'language' => $key, 'categories' => $cats );
					break;
				}

			}

			$output = array();
			$output['status'] = 'ok';
			$output['action'] = 'book';
			$output['data'] = array( 'id' => $book_id, 'title' => $title, 'excerpt' => $excerpt, 'url' => $url, 'image' => $image, 'author_id' => $author_id, 'author_name' => $author_name, 'book_url' => $book_url, 'publisher' => $publisher, 'translator' => $translator );
			$output['info'] = $langs;
			return $output;
		}else{
			return array( 'status' => 'error', 'msg' => 'Not found book' );
		}
	}

	public function search(){
		$search = ( isset($_GET['text']) ? strip_tags($_GET['text']) : '' );
		$search_author_id = ( isset($_GET['author_id']) ? intval($_GET['author_id']) : 0 );
		$search_author_name = ( isset($_GET['author_name']) ? strip_tags($_GET['author_name']) : '' );

		if( empty($search) && $search_author_id == 0 && empty($search_author_name) ){
			return array( 'status' => 'error', 'msg' => 'Not found search query' );
		}else{
			$books = $this->data_books;
			if( is_array($books) && count($books) > 0 ){
				$data = array();
				foreach( $books as $key => $book ){
					$id = $key;
					$title = ( isset($book['title']) ? $book['title'] : '' );
					$excerpt = ( isset($book['excerpt']) ? $book['excerpt'] : '' );
					$url = ( isset($book['url']) ? $book['url'] : '' );
					$image = ( isset($book['image']) ? $book['image'] : '' );
					$author_id = ( isset($book['author_id']) ? $book['author_id'] : '' );
					$author_name = ( isset($book['author_name']) ? $book['author_name'] : '' );
					$book_url = ( isset($book['book_url']) ? $book['book_url'] : '' );
					$publisher = ( isset($book['publisher']) ? $book['publisher'] : '' );
					$translator = ( isset($book['translator']) ? $book['translator'] : '' );

					$create_data = array( 'id' => $id, 'title' => $title, 'excerpt' => $excerpt, 'url' => $url, 'image' => $image, 'author_id' => $author_id, 'author_name' => $author_name, 'book_url' => $book_url, 'publisher' => $publisher, 'translator' => $translator );

					if( $search_author_id > 0 && empty($search) ){
						if( $search_author_id == $author_id ){
							$data[] = $id;
						}
					}elseif( $search_author_id > 0 && !empty($search) ){
						if( ($search_author_id == $author_id) && (preg_match("/{$search}/i", $title) || preg_match("/{$search}/i", $excerpt) || preg_match("/{$search}/i", $author_name) || preg_match("/{$search}/i", $publisher) || preg_match("/{$search}/i", $translator) ) ){
							$data[] = $id;
						}
					}elseif( !empty($search_author_name) && empty($search) ){
						if( preg_match("/{$search_author_name}/i", $author_name) ){
							$data[] = $id;
						}
					}elseif( !empty($search_author_name) && !empty($search) ){
						if( (preg_match("/{$search_author_name}/i", $author_name)) && (preg_match("/{$search}/i", $title) || preg_match("/{$search}/i", $excerpt) || preg_match("/{$search}/i", $publisher) || preg_match("/{$search}/i", $translator) ) ){
							$data[] = $id;
						}
					}else{
						if( preg_match("/{$search}/i", $title) || preg_match("/{$search}/i", $excerpt) || preg_match("/{$search}/i", $author_name) || preg_match("/{$search}/i", $publisher) || preg_match("/{$search}/i", $translator) ){
							$data[] = $id;
						}
					}
				}

				if( is_array($data) && count($data) > 0 ){
					$output = array();
					$output['status'] = 'ok';
					$output['action'] = 'search';
					$output['count'] = count($data);
					$output['data'] = $data;
					return $output;
				}else{
					return array( 'status' => 'error', 'msg' => 'Not found any result' );
				}
			}else{
				return array( 'status' => 'error', 'msg' => 'Not found books' );
			}
		}
	}

	public function output(){
		if( $this->action() == 'error' ){
			$output = array( 'status' => 'error', 'msg' => 'Not found action' );
		}elseif( $this->action() == 'languages' ){
			$output = $this->languages();
		}elseif( $this->action() == 'language' ){
			$output = $this->language();
		}elseif( $this->action() == 'categories' ){
			$output = $this->categories();
		}elseif( $this->action() == 'category' ){
			$output = $this->category();
		}elseif( $this->action() == 'rand' ){
			$output = $this->books_rand();
		}elseif( $this->action() == 'books' ){
			$output = $this->books();
		}elseif( $this->action() == 'book' ){
			$output = $this->book();
		}elseif( $this->action() == 'search' ){
			$output = $this->search();
		}else{
			$output = array( 'status' => 'error', 'msg' => 'Action is empty' );
		}

		return $output;
	}

}
?>
