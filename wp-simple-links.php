<?php 
/*
Plugin Name: Simple Links
Plugin URI: http://lipeimagination.info/wp-simple-links-docs/
Description: Replacement for Wordpress Links Manager with many added features.
Version: 1.6.2
Author: Mat Lipe
Author URI: http://lipeimagination.info
*/


define( 'SIMPLE_LINKS_IMG_DIR', plugin_dir_url(__FILE__). 'img/' );
define( 'SIMPLE_LINKS_JS_DIR',  plugin_dir_url(__FILE__). 'js/' );
define( 'SIMPLE_LINKS_JS_PATH',  plugin_dir_path(__FILE__). 'js/' );
define( 'SIMPLE_LINKS_CSS_DIR', plugin_dir_url(__FILE__). 'css/' );
define( 'SIMPLE_LINKS_SHORTCODE_DIR', plugin_dir_path(__FILE__). 'shortcode/' );

require( 'wp-simple-links-functions.php' );
require( 'classes/init.php' );
require( 'widgets/init.php');



