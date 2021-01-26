<?php

class VK_StaticGenerator {

	public static $version = '0.1.3';

	public function __construct() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'save_options' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'submit_functions' ), 10, 2 );
		add_action( 'admin_print_styles-settings_page_vk-static-generator', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	public static function add_admin_menu() {
		// $parent_slug = 'edit.php?post_type=vk-managing-patterns';
		$page_title = 'VK Static Generator';
		$menu_title = __( 'Static Generator', 'vk-block-pattern-plugin-generator' );
		$capability = 'administrator';
		$menu_slug  = 'vk-static-generator';
		$function   = array( __CLASS__, 'setting_page' );
		add_options_page( $page_title, $menu_title, $capability, $menu_slug , $function );
	}

	public static function get_setting_options(){
		$defaults = array(
			'export_dir'           	=> ABSPATH . 'wp-content/plugins/vk-static-generator/static/',
			'extra_url'          	=> home_url(),
			'replace_url'          	=> '/',
			'plugin_name'          	=> '',
			'disable_css_edit'     	=> false,
		);
		$options = get_option( 'vk-static-setting', $defaults );
		$options = wp_parse_args( $options, $defaults );
		return $options;
	}

	public static function setting_page() {
		include( 'view-setting-page.php' );
	}

	public static function admin_enqueue_scripts() {
		$css_url = plugins_url( '', __FILE__ ) . '/admin-style.css';
		global $vkbppg_version;
		wp_enqueue_style( 'vk-static-setting-page-style', $css_url, array(), $vkbppg_version, 'all' );
	}

	public static function save_options() {

		if ( ! empty( $_POST['vk-static-setting'] ) ) {

			if ( check_admin_referer( 'vkbppg-nonce-key', 'vk-static-setting-page' ) ) {

				if ( ! empty( $_POST['vk-static-setting'] ) ) {

					$options = $_POST['vk-static-setting'];

					$update = array();
					foreach ( $options as $key => $value ){
						$update[$key] = esc_html( $value );
					}

					update_option( 'vk-static-setting', $update );

					add_action( 'admin_notices', array(__CLASS__, 'desplay_message_save_success') );

				} else {
					update_option( 'vk-static-setting', '' );
				}

				// CSSが入力してあってCSS保存は無効化されていない場合
				if ( empty( $options['disable_css_edit'] ) ){

					if ( ! empty( $_POST['utl_list'] ) ||  ! empty( $_POST['vkbppg-custom-editor-css'] ) ){

						// 出力先はPOSTでも受けられるが初回プラグインディレクトリ出力だとデータディレクトリのパスがとれないため再取得
						$options = self::get_setting_options();
						$export_dir_path = $options['export_dir'] ;

						// エクスポート先ディレクトリがなければ作成
						if ( ! file_exists( $export_dir_path ) ){
							$mkdir_return = mkdir( $export_dir_path, 0755, true );
							if ( ! $mkdir_return ){
								global $error;
								if ( empty( $error ) ){
									$error = new WP_Error();
								}
								$error->add( "error_no_export_directory",  __( "出力エラー : エクスポート先ディレクトリの作成に失敗しました。", '' ) );
								return;
							}
						}

						if ( ! empty( $_POST['utl_list'] ) ){
							$css = sanitize_textarea_field( wp_unslash( $_POST['utl_list'] ) );
							file_put_contents( $export_dir_path . 'style.css' , $css );
						}
					}

					add_action( 'admin_notices', array(__CLASS__, 'desplay_message_save_css_success') );

				}
			// wp_safe_redirect( menu_page_url( 'vk-static-setting-page', false ) );
			}
		}
	}

	/*
	/* Import and Export
	/* ------------------------------------------- */


	public static function submit_functions() {

		global $error;
		if ( empty( $error ) ){
			$error = new WP_Error();
		}
	
		if ( ! empty( $_POST['generate'] ) ){

			// 新規プラグイン出力
			if ( 'html' === $_POST['generate'] ){
				self::generate_html();
			}
		}

	}

	public static function export_pattern_data(){

		$export_dir_path = self::get_the_export_dir_path();

		// エクスポート先ディレクトリがなければ作成
		if ( ! file_exists( $export_dir_path ) ){
			$mkdir_return = mkdir( $export_dir_path, 0755, true );
			if ( ! $mkdir_return ){
				global $error;
				if ( empty( $error ) ){
					$error = new WP_Error();
				}
				$error->add( "error_no_export_directory",  __( "出力エラー : エクスポート先ディレクトリの作成に失敗しました。", '' ) );
				return;
			}
		}

		self::json_export_category();
		self::export_register_patterns();

		add_action( 'admin_notices', array(__CLASS__, 'desplay_message_export_success') );

	}

	public static function get_vk_blocks_pro_array(){
		$pro_blocks = array( 'outer', 'table-of-contents-new', 'timeline', 'timeline-item', 'step', 'step-item', 'post-list', 'child-page', 'card', 'card-item', 'grid-column', 'grid-column-item', 'icon-card', 'icon-card-item', 'animation', 'slider', 'slider-item' );
		$array = array();
		foreach ( $pro_blocks as $pro_block ){
			$array[] = 'vk-blocks\/' . $pro_block;
		}
		return $array;
	}

	public static function is_post_include_vk_blocks_pro( $post = null ) {

		if ( ! $post ){
			global $post;
		}

		// プロ版のブロック名一式
		$pro_block_list = self::get_vk_blocks_pro_array();
		$is_pro = false;
		foreach ( $pro_block_list as $block_name ){
			preg_match( '/' . $block_name . '/', $post->post_content, $matches );
			if ( $matches ){
				$is_pro = true;
			}
		}
		return $is_pro;
	}

	public static function is_post_include_vk_blocks( $post = null  ){

		if ( ! $post ){
			global $post;
		}

		preg_match( '/vk-blocks/', $post->post_content, $matches );
		$is_vk_blocks = false;
		if ( $matches ){
			$is_vk_blocks = true;
		}

		return $is_vk_blocks;

	}


	/**
	 * category export json
	 */
	public static function json_export_category() {

		$options = self::get_setting_options();

		$args = array(
			'hide_empty'    => false, 
			'orderby'       => 'name', 
		); 
		$terms = get_terms( array( 'vk-managing-patterns-category' ), $args );
		$terms = wp_json_encode( $terms, JSON_PRETTY_PRINT );
		$export_file_path = self::get_the_export_dir_path() . 'category.json';
		file_put_contents( $export_file_path, $terms );	

		$args = array(
			'hide_empty'    => false, 
		); 
		$terms = get_terms( array( 'vk-managing-patterns-language' ), $args );
		$terms = wp_json_encode( $terms, JSON_PRETTY_PRINT );
		$export_file_path = self::get_the_export_dir_path() . 'term-language.json';
		file_put_contents( $export_file_path, $terms );	
	}

	/**
	 * used_image_convert
	 * 
	 * 投稿内で使われている画像の複製＆URL変換
	 */
	public static function used_image_convert( $content ) {
		$extensions = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );
		$file_url_array = array();
		$file_path_array = array();

		// 画像移動先のファイルパス
		$image_dir_path = self::get_the_export_dir_path( 'images/' );

		// 画像移動先ディレクトリがなければ作成
		if ( ! file_exists( $image_dir_path ) ){
			mkdir( $image_dir_path, 0755 );
		}

		// コンテンツ内からファイルURLを取得して配列に格納
		foreach ( $extensions as $extension ){
			// 正規表現で空白以外の1文字（\S）と""に囲まれていて画像拡張子がある部分を検出
			preg_match_all( '/\S+"(\S+\.' . $extension . ')"/', $content, $matches );
			$file_url_array = array_merge( $file_url_array, $matches[1] );
		}

		// ファイル名より前の画像保存ディレクトリ部分を保存用文字列に変換
		foreach ( $file_url_array as $file_url ){
			// ファイル名だけ取得
			$file_name = basename( $file_url );
			// 画像保存先ディレクトリ（URLからファイル名を削除）
			$file_dir_uri = str_replace( $file_name, '' , $file_url );
			// ディレクトリ名を置換
			$content = str_replace( $file_dir_uri, '[pattern_directory]' , $content );

			// 画像URLをサーバーパスに変換
			$original_file_path = str_replace( site_url() , ABSPATH , $file_url );
			// エクスポート先ファイルパス
			$dist_file_path = $image_dir_path . $file_name;

			// 複製実行
			// 画像ファイルがある場合
			if ( file_exists( $original_file_path ) ){
				// ローカル画像を複製
				copy( $original_file_path, $dist_file_path );

			// 画像ファイルがリモートにある場合
			} elseif ( self::is_url_exists( $file_url ) ) {

				// urlから画像を取得
				$img = file_get_contents( $file_url );
				file_put_contents( $dist_file_path, $img );

			} else {

				global $error;
				if ( empty( $error ) ){
					$error = new WP_Error();
				}
				$error->add( "error_no_export_image",  __( "エクスポートエラー : 画像ファイルの複製に失敗しました。", '' ) );
				$error->add( "error_no_export_image",  $file_url );
			}
		}
		
		return $content;
	}

	public static function is_url_exists( $url ){
		if( ! $url && ! is_string( $url ) ){ return false; }
	   
		$headers = @get_headers( $url );

		if ( preg_match( '/404 Not Found/', $headers[10] ) ){
			return false;
		}

		if( preg_match( '/[2][0-9][0-9]|[3][0-9][0-9]/', $headers[0] ) ){
		  return true;
		}else{
		  return false;
		}
	}

	 public static function get_the_export_dir_path( $dir = '' ){
		$options = self::get_setting_options();
		$export_path = $options['export_dir'];
		if ( $dir ){
			$export_path = $export_path . $dir;
		}
		return sanitize_text_field( $export_path );
	 }


	/**
	 * export_register_patterns()
	 * 
	 * パターンデータを読み込んで登録する処理をするためのファイルをデータディレクトリにエクスポートします。
	 * エクスポートの際にnamespaceの置換処理を行います。
	 */
	public static function export_register_patterns(){

		$original_path = dirname(__FILE__) . '/class-register-patterns-from-json.php';
		$target_path = self::get_the_export_dir_path() . 'class-register-patterns-from-json.php';

		copy( $original_path, $target_path );

		// ファイルのnamespaceを発行 ///////////////////

		// ファイルの中身を取得
		$str = file_get_contents( $target_path );
		// エクスポート先からWordPressパスを削除
		$export_dir_path = self::get_the_export_dir_path();
		$replace = str_replace( ABSPATH, '', $export_dir_path );
		// 末尾の/を削除
		$replace = preg_replace( '/\/$/', '', $replace );
		// 最初の/を削除
		$replace = preg_replace( '/^\//', '', $replace );
		$replace = str_replace( '-', '_', $replace );
		$replace = str_replace( '/', '\\', $replace );

		// namespace文字列置換実行
		$str = str_replace( "vk_reegister_patterns_from_json_original", $replace, $str);

		// cssファイルのハンドル名置換
		// $options = self::get_setting_options();
		// $textdomain = self::get_plugin_convert_textdomain( $options['plugin_name'] );
		// $str = str_replace( "style-handle-name", $textdomain, $str);

		// 置換後の内容で書き込み
		file_put_contents( $target_path, $str );
	}

	public static function delete_posts() {
		$args = array(
			'post_type' => 'vk-managing-patterns',
			'posts_per_page' => -1,
		);
		$wp_query = new WP_Query( $args );
		$posts = array();
		if ( $wp_query->have_posts() ) {
			while ( $wp_query->have_posts() ) {
				$wp_query->the_post();
				global $post;
				$force_delete = false;
				wp_delete_post( $post->ID, $force_delete ); 
			}
		}
	}

	public static function delete_category() {
		$args = array(
			'hide_empty'    => false, 
		); 
		$terms = get_terms( array( 'vk-managing-patterns-category' ), $args );
		foreach ( $terms as $term ){
			wp_delete_term( $term->term_id, 'vk-managing-patterns-category' );
		}
	}


	/**
	 * Json import
	 */

	public static function import_pattern_data() {

		$data_dir_path = self::get_the_export_dir_path();

		// import category
		$category_json = $data_dir_path . 'category.json';

		if ( file_exists( $category_json ) ) {
			$json = file_get_contents( $category_json );
			$obj = json_decode( $json, true );
			foreach( $obj as $key => $val) {
				// Create test term
				$args  = array(
					'slug' => $val['slug'],
				);
				$term_info = wp_insert_term( $val['name'], 'vk-managing-patterns-category', $args );
			}
		}

		// import posts
		$posts_json = $data_dir_path . 'template-all.json';

		if ( file_exists( $posts_json ) ) {
			$json = file_get_contents( $posts_json );
			$obj = json_decode( $json, true );

            $image_dir_path = $data_dir_path . 'images/';
            $image_dir_uri = str_replace( ABSPATH, site_url() . '/' , $image_dir_path );

			foreach( $obj as $key => $val) {

				// 画像保存ディレクトリURLを置換
				$val['content'] = str_replace( '[pattern_directory]', $image_dir_uri , $val['content'] );

				// 2021.2月以降削除可
				if ( empty( $val['post_status'] ) ){
					$val['post_status'] = 'publish'; 
				}

				$post = array(
					'post_title'     => $val['title'],
					'post_name'   	 => $val['post_name'],
					'post_content'   => $val['content'],
					'post_excerpt'   => $val['description'],
					'post_status'    => $val['post_status'],
					'post_type'      => 'vk-managing-patterns',
				);
				$post_id = wp_insert_post( $post );
				wp_set_object_terms( $post_id, $val['categories'], 'vk-managing-patterns-category' );
				wp_set_object_terms( $post_id, $val['languages'], 'vk-managing-patterns-language' );
			}

			add_action( 'admin_notices', array(__CLASS__, 'desplay_message_import_success') );

			return true;

		} else {
			global $error;
			if ( empty($error) ){
				$error = new WP_Error();
			}
			$error->add("error_import",  __( "インポートエラー : 指定したディレクトリにデータがありません。", '' ) );
			update_option( 'vkbppg-mode', 'edit-out' );
		}	
	}


	/*
	/* Messages
	/* ------------------------------------------- */

	public static function desplay_message_save_css_success(){
		if ( isset( $_GET['page'] ) && 'vk-static-setting-page' === $_GET['page'] ) {
			echo '<div class="updated"><p>' . __( 'CSSをエクスポートしました。', '' ) . '</p></div>';
		}
	}

	public static function desplay_message_save_success(){
		if ( isset( $_GET['page'] ) && 'vk-static-setting-page' === $_GET['page'] ) {
			echo '<div class="updated"><p>' . __( '設定を保存しました。', '' ) . '</p></div>';
		}
	}

	public static function desplay_message_import_success(){
		if ( isset( $_GET['page'] ) && 'vk-static-setting-page' === $_GET['page'] ) {
			echo '<div class="updated"><p>' . __( 'パターンデータをインポートしました', '' ) . '</p></div>';
		}
	}

	public static function desplay_message_export_success(){
		if ( isset( $_GET['page'] ) && 'vk-static-setting-page' === $_GET['page'] ) {
			echo '<div class="updated"><p>' . __( 'パターンデータをエクスポートしました', '' ) . '</p></div>';
		}
	}

	public static function desplay_message_plugin_generate_success(){
		if ( isset( $_GET['page'] ) && 'vk-static-setting-page' === $_GET['page'] ) {
			$options = self::get_setting_options();
			$name = self::get_plugin_convert_name( $options['plugin_name'] );
			$textdomain = self::get_plugin_convert_textdomain( $options['plugin_name'] );
			$plugin_dir_path = ABSPATH . '/wp-content/plugins/' . $textdomain . '/'; ?>

			<div class="updated">
			<p><?php printf(__( 'プラグイン「%s」を生成しました。', '' ), $name ) . printf( __( '<a href="%s" target="_blank">プラグイン一覧</a>画面で有効化してください。', '' ), admin_url().'/plugins.php' ) ;?></p>
			
			<p><?php printf(__( '以下のディレクトリを開いて %s や readme.txt のバージョン情報などを必要に応じて書き換えてください ', '' ), $textdomain . '.php' ); ?><br>
			<?php echo $plugin_dir_path ;?>
			</p>
			</div>
			<?php
		}
	}


	/*
	/* Plugin Generate
	/* ------------------------------------------- */

	public static function get_plugin_convert_name( $name ) {
		$name = trim( $name );
		$name = mb_convert_kana( $name, "a" );
		
		return sanitize_text_field( $name );
	}

	public static function get_plugin_convert_textdomain( $name ) {
		$name = trim( $name );
		$name = mb_convert_kana( $name, "a" );
		$name = mb_strtolower( $name );
		$name = str_replace( ' ', '-', $name );
		// $name = preg_match_all( '/\W/', '' , $name );
		return sanitize_text_field( $name );
	}
	
	public static function get_plugin_convert_packagename( $name ) {
		$name = trim( $name );
		$name = mb_convert_kana( $name, "a" );
		$name = mb_strtoupper( $name );
		$name = str_replace( ' ', '_', $name );

		return sanitize_text_field( $name );
	}

	public static function generate_html(){

		$options = self::get_setting_options();

		// １行毎に配列に変換
		$array = explode( "\n", $options['utl_list'] );

		$export_dir = $options['export_dir'];

		// 末尾が/じゃなかったらとりあえず追加する
		preg_match( '/\/$/', $export_dir, $match );
		if ( ! $match ){
			$export_dir = $export_dir . '/';
		}

		$new_dir = $export_dir;
		foreach( $array as $url ){
			$url = trim( $url );

			// URLのHTMLを変数に格納
			$target_url = trim( $url );
			// URLのHTMLを変数に格納
			$content = file_get_contents( trim( $target_url ) );
			// 絶対URL部分を置換
			$content = str_replace( $options['extra_url'] , $options['replace_url'], $content );

			// URLの余分な部分を削除
			$new_file_path = str_replace( $options['extra_url'] , '', $url );

			// パスに // があったら / に変更
			$new_file_path = preg_replace( '/\/\//', '/', $new_file_path );

			// print '<pre style="text-align:left">';print_r($new_file_path);print '</pre>';

			// / を元に配列に変換
			$new_path_array = explode('/', $new_file_path);

			// 配列 $new_path_array からファイル名を削除 
			$basename = array_pop( $new_path_array );

			if ( count( $new_path_array ) > 0 ) {

				$new_dir = $export_dir;
				foreach ( $new_path_array as $dir ){

					$new_dir .= '/' . $dir . '/';
					// パスに // があったら / に変更
					$new_dir = preg_replace( '/\/\//', '/', $new_dir );

					if ( ! file_exists(  $new_dir  ) ){
						mkdir( $new_dir, 0777, true );
					}
				}
			}

			$new_full_path = $export_dir . $new_file_path;

			preg_match( '/\/$/', $target_url, $match );
			if ( $match ){
				$new_full_path = $new_full_path . 'index.html';
			}
			file_put_contents( $new_full_path , $content );
		}

	}

}

new VK_StaticGenerator;