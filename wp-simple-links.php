<?php 
/*
Plugin Name: WP Simple Links
Plugin URI: https://github.com/madlandproject/wp-simple-links
Description: Replacement for Wordpress Links Manager with many added features.
Version: 0.1.0
Author: Robin Lambell
Author URI: http://madlandproject.com
*/


define( 'SIMPLE_LINKS_IMG_DIR', plugin_dir_url(__FILE__). 'img/' );
define( 'SIMPLE_LINKS_JS_DIR',  plugin_dir_url(__FILE__). 'js/' );
define( 'SIMPLE_LINKS_JS_PATH',  plugin_dir_path(__FILE__). 'js/' );
define( 'SIMPLE_LINKS_CSS_DIR', plugin_dir_url(__FILE__). 'css/' );
define( 'SIMPLE_LINKS_SHORTCODE_DIR', plugin_dir_path(__FILE__). 'shortcode/' );

require( 'wp-simple-links-functions.php' );
require( 'classes/init.php' );
require( 'widgets/init.php');

// Activation hooks

register_activation_hook(__FILE__, 'activateWPSL');
register_deactivation_hook(__FILE__, 'deactivateWPSL');
register_uninstall_hook(__FILE__, 'uninstallWPSL');


function activateWPSL(){
    $plugin = new Simple_Links();
    $plugin->post_type();

    flush_rewrite_rules();
}

function deactivateWPSL(){
    flush_rewrite_rules();
}

function uninstallWPSL(){

}


