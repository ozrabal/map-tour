<?php
/*
 * Plugin Name: Map Tour
 * Plugin URI: http://
 * Description:
 * Version: 1.0.0
 * Author:
 * Author URI: http://webkowski.com
 * Text Domain: mt
*/

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'MAPTOUR_VERSION', '1.0.0');
define( 'MAPTOUR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAPTOUR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MAPTOUR_DEBUG', true );

require_once 'library/class-maptour.php';

add_action( 'plugins_loaded', function() {
    new Maptour();
});