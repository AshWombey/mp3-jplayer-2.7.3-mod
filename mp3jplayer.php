<?php
/* 
Plugin Name: MP3-jPlayer
Plugin URI: http://mp3-jplayer.com
Description: Easy, Flexible Audio for WordPress. 
Version: 2.7.3
Author: Simon Ward
Author URI: http://www.sjward.org
License: GPL2
Text Domain: mp3-jplayer
	
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation. 
*/


//prevent direct access
if ( ! function_exists( 'get_bloginfo' ) ) { 	
	die();
}


//constants
define( 'MP3J_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'MP3J_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'MP3J_SETTINGS_NAME', 'mp3FoxAdminOptions' );


//includes
include_once( MP3J_PLUGIN_PATH . '/widget-ui.php' );
include_once( MP3J_PLUGIN_PATH . '/widget-sh.php' );
include_once( MP3J_PLUGIN_PATH . '/template-functions.php' );
include_once( MP3J_PLUGIN_PATH . '/main.php' );
include_once( MP3J_PLUGIN_PATH . '/frontend.php' );

if ( is_admin() ) {
	include_once( MP3J_PLUGIN_PATH . '/admin-settings.php');
	include_once( MP3J_PLUGIN_PATH . '/admin-colours.php');
}


//create instance and hookup with wp
if ( class_exists( 'MP3j_Front' ) ) {
	$MP3JP = new MP3j_Front();
}

if ( isset( $MP3JP ) ) {
	$MP3JP->addPrimaryHooks();
	$MP3JP->registerShortcodes();
	$MP3JP->registerTagCallbacks();
}

?>