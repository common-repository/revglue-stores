<?php

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

global $wpdb;

global $rg_db_version;

$rg_db_version = '1.0.0';

add_option("rg_db_version", $rg_db_version);

require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

$charset_collate = $wpdb->get_charset_collate();

$table_name = $wpdb->prefix.'rg_projects'; 

$sql= "CREATE TABLE IF NOT EXISTS `$table_name` 

(

	`rg_project_id` int(11) NOT NULL AUTO_INCREMENT,

	`subcription_id` varchar(255) NOT NULL,
	`partner_iframe_id` varchar(255) NOT NULL,

	`user_name` varchar(100) NOT NULL,

	`email` varchar(100) NOT NULL,

	`project` varchar(100) NOT NULL,

	`password` varchar(100) NOT NULL,

	`expiry_date` varchar(100) NOT NULL,

	`status` enum('active','inactive') NOT NULL DEFAULT 'inactive',

	PRIMARY KEY (`rg_project_id`)
) $charset_collate;";

dbDelta($sql);

$table_name = $wpdb->prefix.'rg_stores'; 

$sql = "CREATE TABLE IF NOT EXISTS `$table_name` 

(

	`rg_store_id` int(11) unsigned NOT NULL AUTO_INCREMENT,

	`mid` int(11) NOT NULL,

	`title` varchar(255) DEFAULT NULL,

	`url_key` varchar(255) NOT NULL,

	`description` text,

	`image_url` varchar(255) DEFAULT NULL,

	`affiliate_network` varchar(255) DEFAULT NULL,

	`affiliate_network_link` varchar(255) DEFAULT NULL,

	`store_base_currency` varchar(255) DEFAULT NULL,

	`store_base_country` varchar(255) DEFAULT NULL,

	`category_ids` varchar(128) DEFAULT NULL,

	`homepage_store_tag` enum('yes','no') NOT NULL DEFAULT 'no', 

	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

	`status` enum('active','in-active') NOT NULL DEFAULT 'active',

	PRIMARY KEY (`rg_store_id`)

) $charset_collate;";

dbDelta($sql);

$table_name = $wpdb->prefix.'rg_categories'; 

$sql = "CREATE TABLE IF NOT EXISTS `$table_name` 

(

	`rg_category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,

	`title` varchar(255) DEFAULT NULL,

	`url_key` varchar(255) NOT NULL,

	`parent` int(11) DEFAULT NULL,

	`image_url` varchar(255) DEFAULT NULL,

	`icon_url` varchar(255) DEFAULT NULL,

	`header_category_tag` enum('yes','no') NOT NULL DEFAULT 'no',

	`popular_category_tag` enum('yes','no') NOT NULL DEFAULT 'no',

	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

	`status` enum('active','in-active') NOT NULL DEFAULT 'active',

	PRIMARY KEY (`rg_category_id`)

) $charset_collate;";

dbDelta($sql);

$table_name = $wpdb->prefix.'rg_banner'; 

$sql= "CREATE TABLE IF NOT EXISTS `$table_name` 

(

	`rg_id` int(11) NOT NULL AUTO_INCREMENT,

	`rg_store_banner_id` int(11),

	`rg_store_id` int(11),

	`title` varchar(255) NOT NULL,

	`rg_store_name` varchar(255) NOT NULL,

	`image_url` varchar(255) NOT NULL,

	`url` varchar(255) NOT NULL,

	`placement` varchar(100) NOT NULL,

	`rg_size` varchar(50) NOT NULL,

	`banner_type` enum('local','imported') NOT NULL DEFAULT 'local',

	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

	`status` enum('active','inactive') NOT NULL DEFAULT 'active',

	PRIMARY KEY (`rg_id`)

) $charset_collate;";

dbDelta($sql);

?>