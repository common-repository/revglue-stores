<?php 
/*
Plugin Name: RevGlue Stores
Description: This plugin connects your wordpress site with RevGlue. It imports the stores, categories and banners you selected at RevGlue to your wordpress website.
Version:     1.0.0
Author:      RevGlue
Author URI:  https://www.revglue.com/
License:     GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: revglue-stores
RevGlue Stores is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
RevGlue Stores is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with RevGlue Stores. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
// Global Variables 
define( 'RGSTORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RGSTORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RGSTORE_API_URL', 'https://www.revglue.com/' );
define( 'REVGLUE_STORE_ICONS', 'https://revglue.com/resources/wp-theme/stores/categoryicons' );
define( 'REVGLUE_CATEGORY_BANNERS', 'https://revglue.com/resources/wp-theme/stores/categorybanners' );
function rg_stores_install() 
{
	// List of folders we need to create in WordPress uploads folder
	$dir_structure_array = array( 'revglue', 'stores', 'banners' );
	rg_stores_create_directory_structures( $dir_structure_array );
	include( RGSTORE_PLUGIN_DIR . 'includes/rg-install.php' );
}
register_activation_hook( __FILE__, 'rg_stores_install' );
function rg_stores_uninstall() 
{
	rg_stores_remove_directory_structures();
	include( RGSTORE_PLUGIN_DIR . 'includes/rg-uninstall.php' );
}
register_uninstall_hook( __FILE__, 'rg_stores_uninstall' );




add_action( 'wp', 'revglue_cron_job' );
add_action( 'revgluecron_hourly_event', 'revglue_do_this_cronjob' );

function revglue_cron_job() {
    if ( !wp_next_scheduled( 'revgluecron_hourly_event' ) ) {
        wp_schedule_event( time(), 'hourly', 'revgluecron_hourly_event' );
    }
}

function revglue_do_this_cronjob() {
   
	  echo "sdfsdfsdf";
	  fopen("testing.txt","a");
     return wp_mail("irfanokr@gmail.com", "Notification TEST", "TEST", null);
}



 
require_once( RGSTORE_PLUGIN_DIR . 'includes/core.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/ajax-calls.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/main-page.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/import-stores-page.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/import-banners-page.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/stores-listing-page.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/categories-listing-page.php' );
require_once( RGSTORE_PLUGIN_DIR . 'includes/pages/banners-listing-page.php' );
?>