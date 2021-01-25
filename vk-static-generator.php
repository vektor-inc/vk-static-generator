<?php
/**
 * Plugin Name:     VK Static Generator
 * Plugin URI:      https://www.vektor-inc.co.jp/
 * Description:    
 * Author:          Vektor,Inc.
 * Author URI:      https://www.vektor-inc.co.jp/
 * Text Domain:     vk-static-generator
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         VK_STATIC_GENERATOR
 */

// Your code starts here.
// Get Plugin version

$data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
global $vkbppg_version;
$vkbppg_version = $data['version'];

define( 'VK_STATIC_DIRECTORY_PATH', dirname( __FILE__ ) );

include( dirname( __FILE__ ) . '/inc/static-generator/class-static-generator.php' );