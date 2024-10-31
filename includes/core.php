<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
function rg_stores_admin_enqueue()
{
	global $hook_suffix;
	// List of Plugin Pages
	$rg_stores_hook_suffixes = array(
		'toplevel_page_revglue-dashboard',
		'revglue-stores_page_revglue-import-stores',
		'revglue-stores_page_revglue-import-banners',
		'revglue-stores_page_revglue-stores',
		'revglue-stores_page_revglue-categories',
		'revglue-stores_page_revglue-banners'
	);
	// Only enqueue if current page is one of plugin pages
	if ( in_array( $hook_suffix, $rg_stores_hook_suffixes ) ) 
	{
		// Enqueue Admin Styles
		wp_register_style( 'rg-store-confirm', RGSTORE_PLUGIN_URL . 'admin/css/jquery-confirm.css' );
		wp_enqueue_style( 'rg-store-confirm' );
		wp_register_style( 'rg-store-confirm-bundled', RGSTORE_PLUGIN_URL . 'admin/css/bundled.css' );
		wp_enqueue_style( 'rg-store-confirm-bundled' );
		wp_register_style( 'rg-store-main', RGSTORE_PLUGIN_URL . 'admin/css/admin_style.css' );
		wp_enqueue_style( 'rg-store-main' );
		wp_register_style( 'rg-store-checkbox', RGSTORE_PLUGIN_URL . 'admin/css/iphone_style.css' );
		wp_enqueue_style( 'rg-store-checkbox' );
		wp_register_style( 'rg-store-datatables', RGSTORE_PLUGIN_URL . 'admin/css/jquery.dataTables.css' );
		wp_enqueue_style( 'rg-store-datatables' );
		wp_register_style( 'rg-store-fontawesome', get_template_directory_uri() . '/assets/css/font-awesome.css' );
		wp_enqueue_style( 'rg-store-fontawesome' );
		// Enqueue Admin Scripts

		wp_register_script( 'rg-store-datatables', RGSTORE_PLUGIN_URL . 'admin/js/jquery.dataTables.js', array ( 'jquery' ) );
		wp_enqueue_script( 'rg-store-datatables' );

		wp_register_script( 'rg-store-unveil', RGSTORE_PLUGIN_URL . 'admin/js/jquery.unveil.js', array ( 'jquery' ) );
		wp_enqueue_script( 'rg-store-unveil' );

		wp_register_script( 'rg-store-checkbox', RGSTORE_PLUGIN_URL . 'admin/js/iphone-style-checkboxes.js', array ( 'jquery' ) );
		wp_enqueue_script( 'rg-store-checkbox' );
		
		wp_register_script( 'rg-notify', RGSTORE_PLUGIN_URL . 'admin/js/notify.js', array ( 'jquery' ) );
		wp_enqueue_script( 'rg-notify' );

		wp_register_script( 'rg-store-confirm', RGSTORE_PLUGIN_URL . 'admin/js/jquery-confirm.js', array ( 'jquery' ) );
		wp_enqueue_script( 'rg-store-confirm' );

		wp_register_script( 'rg-store-main', RGSTORE_PLUGIN_URL . 'admin/js/main.js', array ( 'jquery', 'jquery-form' ) );
		wp_enqueue_script( 'rg-store-main' );

		wp_localize_script( 'rg-store-main', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'rg_stores_admin_enqueue' );
function rg_stores_admin_actions() 
{
	add_menu_page('RevGlue Stores', 'RevGlue Stores', 'manage_options', 'revglue-dashboard', 'rg_stores_main_page', RGSTORE_PLUGIN_URL .'admin/images/menuicon.png' );
	add_submenu_page('revglue-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'revglue-dashboard', 'rg_stores_main_page');
	add_submenu_page('revglue-dashboard', 'Import Stores & Categories', 'Import Stores & Categories', 'manage_options', 'revglue-import-stores', 'rg_stores_store_import_page');
	add_submenu_page('revglue-dashboard', 'Stores', 'Stores', 'manage_options', 'revglue-stores', 'rg_stores_listing_page');
	add_submenu_page('revglue-dashboard', 'Categories', 'Categories', 'manage_options', 'revglue-categories', 'rg_stores_category_listing_page');
	add_submenu_page('revglue-dashboard', 'Import Banners', 'Import Banners', 'manage_options', 'revglue-import-banners', 'rg_stores_banner_import_page');
	add_submenu_page('revglue-dashboard', 'Banners', 'Banners', 'manage_options', 'revglue-banners', 'rg_stores_banner_listing_page');
}
add_action( 'admin_menu', 'rg_stores_admin_actions' );
function rg_stores_create_directory_structures( $dir_structure_array )
{
	$upload = wp_upload_dir();
	$base_dir = $upload['basedir'];
	foreach( $dir_structure_array as $single_dir )
	{
		$create_dir = $base_dir.'/'.$single_dir;
		if ( ! is_dir( $create_dir ) ) 
		{
			mkdir( $create_dir, 0755 );
		}
		$base_dir = $create_dir;
	}
}
function rg_stores_remove_directory_structures()
{
	$upload = wp_upload_dir();
	$base_dir = $upload['basedir'].'\revglue';
	rg_stores_folder_cleanup($base_dir);
}
function rg_stores_folder_cleanup( $dirpath )
{
	if( substr( $dirpath, strlen($dirpath) - 1, 1 ) != '/' )
	{
        $dirpath .= '/';
    }
	$files = glob($dirpath . '*', GLOB_MARK);
	foreach( $files as $file )
	{
		if( is_dir( $file ) )
		{
			deleteDir($file);
		}
		else
		{
			unlink($file);
        }
    }
	rmdir($dirpath);
}
function rg_stores_auto_import_data()
{
    $auto_var = basename( $_SERVER["REQUEST_URI"] );
	if ( $auto_var ==  'auto_import_data') 
	{
		include( RGSTORE_PLUGIN_DIR . 'includes/auto-import-data.php');
	}
}
add_action( 'template_redirect', 'rg_stores_auto_import_data' );
function rg_stores_populate_recursive_categories( $category_object, $parent_title, &$counter )
{
	global $wpdb;
	$categories_table = $wpdb->prefix.'rg_categories';
	$sql = "SELECT *FROM $categories_table WHERE `parent` = $category_object->rg_category_id ORDER BY `title` ASC";
	$subcategories = $wpdb->get_results($sql);
	if ( !empty($parent_title) )
	{
		$title = $parent_title.'->'.$category_object->title;
		$strong_title = $parent_title.'-><strong>'.$category_object->title.'</strong>';
	} else 
	{
		$title = $category_object->title;
		$strong_title = '<strong>'.$title.'</strong>';
	}
	?><tr class="ui-state-default">
		<td>
			<?php esc_html_e( $counter ); ?>
		</td>
		<td style="text-align:left;">
			<?php _e( $strong_title ); ?>
		</td>
		<!-- <td style="text-align:left;">
			<?php //_e( $category_object->status ); ?>
		</td> -->
		<td style="text-align:left;">
			<div class="revglue-banner-thumb rg_store_icon_thumb_<?php echo  $category_object->rg_category_id ?>">
				<?php 
				$iconurl = $category_object->icon_url;
				 if (is_numeric(substr($iconurl, 0, 1))) {
					?><a id="<?php esc_attr_e( $category_object->rg_category_id ); ?>" data-type="image_url" class="rg_category_delete_icons" href="javascript;"><i class="fa fa-times" aria-hidden="true"></i></a>
					<img alt="image" src="<?php echo REVGLUE_STORE_ICONS.'/'.$iconurl.'.png' ; ?>"><?php
				} else { ?>
				<a id="<?php esc_attr_e( $category_object->rg_category_id ); ?>" data-type="image_url" class="rg_category_delete_icons" href="javascript;"><i class="fa fa-times" aria-hidden="true"></i></a>
					<img alt="image" src="<?php  esc_html_e( $category_object->icon_url ) ; ?>">
				<?php }
				?>
			</div>
		</td>
		<td style="text-align:left;">
			<div class="revglue-banner-thumb rg_store_image_thumb_<?php echo  $category_object->rg_category_id ?>">
				<?php 
				$imageurl = $category_object->image_url;
				 if (is_numeric(substr($imageurl, 0, 1))) { 
					?><a id="<?php esc_attr_e( $category_object->rg_category_id ); ?>" data-type="icon_url" class="rg_category_delete_images" href="javascript;"><i class="fa fa-times" aria-hidden="true"></i></a>
					<img alt="image" src="<?php echo  REVGLUE_CATEGORY_BANNERS.'/'.$imageurl.'.jpg' ; ?>"><?php
				} else { ?>
					<a id="<?php esc_attr_e( $category_object->rg_category_id ); ?>" data-type="icon_url" class="rg_category_delete_images" href="javascript;"><i class="fa fa-times" aria-hidden="true"></i></a>
					<img alt="image" src="<?php esc_html_e( $category_object->image_url) ; ?>">
			 <?php	}
				?>
			</div>
		</td>
		<td>
			<?php 
			if( $category_object->header_category_tag == 'yes' )
			{
				$checked = 'checked="checked"';
			} else
			{
				$checked = '';
			}
			?>
			<input <?php echo $checked; ?> type="checkbox" id="<?php echo  $category_object->rg_category_id ?>" class="rg_store_cat_tag_head" />
		</td>
		<td>
			<?php 
			if( $category_object->popular_category_tag == 'yes' )
			{
				$checked = 'checked="checked"';
			} else
			{
				$checked = '';
			}
			?>
			<input <?php echo $checked; ?> type="checkbox" id="<?php echo  $category_object->rg_category_id ?>" class="rg_store_cat_tag" />
		</td>
		<td>
			<a id="<?php echo $category_object->rg_category_id; ?>" class="rg_add_category_icon rg_add_category_icon_<?php echo $category_object->rg_category_id; ?>" href="javascript;">
				<?php if(!empty($category_object->icon_url))
				{
					echo 'Edit Icon |';
				} else 
				{
					echo 'Add Icon |';
				}
				?>
			</a>
			<a id="<?php echo $category_object->rg_category_id; ?>" class="rg_add_category_image rg_add_category_image_<?php echo $category_object->rg_category_id; ?>" href="javascript;">
				<?php if(!empty($category_object->image_url))
				{
					echo 'Edit Image';
				} else 
				{
					echo 'Add Image';
				}
				?>
			</a>
		</td>
	</tr><?php
	if( !empty( $subcategories ) )
	{
		foreach( $subcategories as $single_cateogory )
		{
			++$counter;
			rg_stores_populate_recursive_categories( $single_cateogory, $title, $counter );
		}
	}
}
function rg_admin_notice_if_user_has_not_subscription_id() {
		global $wpdb;
		$rg_projects_table = $wpdb->prefix.'rg_projects'; 
		$sql = "SELECT  email FROM $rg_projects_table where email !='' limit 1";
		$email = $wpdb->get_var($sql);
		if ($email =='') {
			echo '<div class="notice notice-success is-dismissible subscriptiondone " style="text-align:center;">  ';
		echo  '<p>Please read the instructions on  <a href=\"admin.php?page=revglue-dashboard\" target=\"_blank\">RevGlue Dashbaord</a> for importing your RevGlue projects data. </p>';
		echo  '</div>';
		} 
}
add_action( 'admin_notices', 'rg_admin_notice_if_user_has_not_subscription_id' );
/**************************************************************************************************
*
* Remove Wordpress dashboard default widgets
*
***************************************************************************************************/
function rg_remove_default_widgets(){
	remove_action('welcome_panel', 'wp_welcome_panel');
	remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
	remove_meta_box( 'dashboard_quick_press',   'dashboard', 'side' );      //Quick Press widget
	remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );      //Recent Drafts
	remove_meta_box( 'dashboard_primary',       'dashboard', 'side' );      //WordPress.com Blog
	remove_meta_box( 'dashboard_incoming_links','dashboard', 'normal' );    //Incoming Links
	remove_meta_box( 'dashboard_plugins',       'dashboard', 'normal' );    //Plugins
	remove_meta_box('dashboard_activity', 'dashboard', 'normal');
}
add_action('wp_dashboard_setup', 'rg_remove_default_widgets');
/*function remove_core_updates(){
global $wp_version;return(object) array('last_checked'=> time(),'version_checked'=> $wp_version,);
}*/
//add_filter('pre_site_transient_update_core','remove_core_updates'); //hide updates for WordPress itself
//add_filter('pre_site_transient_update_plugins','remove_core_updates'); //hide updates for all plugins
//add_filter('pre_site_transient_update_themes','remove_core_updates'); //hide updates for all themes	
?>