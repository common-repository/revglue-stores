<?php 
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
global $wpdb;
$banner_table 					= $wpdb->prefix.'rg_banner';
$stores_table 					= $wpdb->prefix.'rg_stores';
$categories_table 				= $wpdb->prefix.'rg_categories';
$project_table 					= $wpdb->prefix.'rg_projects';
delete_option('rg_db_version');
require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
$sql = "DROP TABLE IF EXISTS `$stores_table`";
$wpdb->query($sql);
$sql = "DROP TABLE IF EXISTS `$categories_table`";
$wpdb->query($sql);
$sql = "DROP TABLE IF EXISTS `$project_table`";
$wpdb->query($sql);
$sql = "DROP TABLE IF EXISTS `$banner_table`";
$wpdb->query($sql);
?>