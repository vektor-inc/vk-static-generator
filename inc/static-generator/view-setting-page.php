<div class="wrap">
    <h2>VK Static Generator</h2>

    <?php 


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



    global $error;
    if ( empty( $error ) ){
        $error = new WP_Error();
    }

    if ( $error->get_error_codes() ) {
        echo "<div class='error'>";
        echo "<ul>";
        foreach ( $error->get_error_messages() as $value ) {
            echo "<li>" . esc_html($value) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    ?>
    <form id="vk-static-setting-form" method="post" action="">
        <?php wp_nonce_field( 'vkbppg-nonce-key', 'vk-static-setting-page' );?>

        <?php

        $mode = get_option('vk-static-setting');
?>
        <h3>
        <?php _e( 'HTML出力設定', 'vk-block-pattern-plugin-generator' );
      
       ?>

        </h3>
        


        <?php $options = self::get_setting_options();?>

        <table class="form-table">
        <tr>
        <th>URLから削除する階層</th>
        <td>
        <input type="text" name="vk-static-setting[extra_url]" value="<?php echo esc_textarea( $options['extra_url'] ); ?>" style="width:100%;">
        <p>例) https://localhost:8888/</p>
        </td>
        </tr>
        <tr>
        <th>URLで置換する文字列</th>
        <td>
        <input type="text" name="vk-static-setting[replace_url]" value="<?php echo esc_textarea( $options['replace_url'] ); ?>" style="width:100%;">
        <p>例) /</p>
        </td>
        </tr>
        <tr>
        <th>出力先ディレクトリ</th>
        <td>
        <input type="text" name="vk-static-setting[export_dir]" value="<?php echo esc_textarea( $options['export_dir'] ); ?>" style="width:100%;">
        <p><?php echo __('例) ', 'vk-block-pattern-plugin-generator' ) . ABSPATH . 'wp-content/plugins/vk-block-pattern-plugin-generator/patterns-data-sample/';?></p>
        </td>
        </tr>
        <tr>
        <th>カスタムCSS</th>
        <td>

            <h5>URL リスト</h5>
            <textarea name="vk-static-setting[utl_list]" cols="70" rows="10" id="vk-static-setting[utl_list]"><?php
                if ( ! empty( $options['utl_list'] ) ){
                    echo $options['utl_list'];
                }
            ?></textarea>

        </td>
        </tr>
        </table>
        <div>
            <button class="button button-large" type="submit" name="submit" value="directory">
                <span class="dashicons dashicons-admin-generic"></span>
                設定を保存
            </button>
            <button class="button button-primary button-large" type="submit" name="generate" value="html">
                <span class="dashicons dashicons-migrate"></span>
                HTML出力
        </button>
        </div>

    </form> 

</div>