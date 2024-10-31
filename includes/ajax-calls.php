<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;
function revglue_store_subscription_validate() 
{
	global $wpdb;
	$project_table = $wpdb->prefix.'rg_projects';
	$sanitized_sub_id	= sanitize_text_field( $_POST['sub_id'] );
	$sanitized_email	= sanitize_email( $_POST['sub_email'] );
	$password  			= $_POST['sub_pass'];

	$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/validate_subscription_key/$sanitized_email/$password/$sanitized_sub_id", array( 'timeout' => 120, 'sslverify'   => false ) ) ), true); 
	$result = $resp_from_server['response']['result'];
	 // pre($result);
	 // die;
	$project =$result['project'];
 	$iFrameid =$result['iframe_id'];
	$data=array();
	if($iFrameid!=""){
		$data=array(
					'subcription_id' 	=> $sanitized_sub_id,
					'user_name' 		=> $result['user_name'],
					'email' 			=> $result['email'],
					'project' 			=> $result['project'],
'project' => $result['project'] == "Cashback" ? str_replace ("Cashback", "Stores UK", $result['project']) : $result['project'],
					'password'     		=> $password, 
					'expiry_date' 		=> $result['expiry_date'],
					'partner_iframe_id'	=> $result['iframe_id'],  
					'status' 			=> $result['status']
				) ;
	} else{
		$data=array( 
			'subcription_id' 				=> $sanitized_sub_id, 
			'user_name' 					=> $result['user_name'], 
			'email' 						=> $result['email'], 
			'project' 						=> $result['project'],
			'password'     					=> $password, 
			'expiry_date' 					=> $result['expiry_date'],  
			'status' 						=> $result['status']
		) ;
	}
	$string = '';
	if( $resp_from_server['response']['success'] == true )
	{
		$sql = "Select * FROM $project_table Where project like '".$result['project']."' and status = 'active'";
		// echo $sql;
	    $execute_query = $wpdb->get_results( $sql );
	     //pre($execute_query);
	    // die;
		$rows = $wpdb->num_rows;
		if( empty ( $rows ) )
		{
			$string .= "<div class='panel-white mgBot'>";
			if($iFrameid!="" )
			{
				// echo "i,m here";
				// die;
				$string .= "<p><b>Your RevEmbed Free Stores data subscription is ".$result['status']." .&nbsp; </b><img  class='tick-icon' src=".REVGLUE_STORE_ICONS. '/tick_icon.png'." >  </p>";
				$string .= "<p><b>Name = </b> RevEmbed Data </p>";
				$string .= "<p><b>Project = </b>".$result['project']." </p>";
				$string .= "<p><b>Email = </b>".$result['email']."</p>";
			}
			else{
				if($project == " Banners UK" ){
					$string .= "<p><b>Your Stores subscription is ".$result['status']." .&nbsp; </b><img  class='tick-icon' src=".REVGLUE_STORE_ICONS. '/tick_icon.png'." >  </p>";
				}
				else{
				$string .= "<p><b>Your data subscription is ".$result['status']." </b><img  class='tick-icon' src=".REVGLUE_STORE_ICONS. '/tick_icon.png'." /></p>";
				}
				$string .= "<p><b>Name = </b>".$result['user_name']."</p>";
				$string .= "<p><b>Project = </b>".$result['project']."</p>";
				$string .= "<p><b>Email = </b>".$result['email']."</p>";
				$string .= "<p><b>Expiry Date = </b>".date( "d-M-Y" , strtotime($result['expiry_date']))."</p>";
			$string .= "</div>";
			}
			$wpdb->insert( 
				$project_table,
				$data
			
			);
		} else 
		{
			$string .= "<div style='color: green;'>You already have subscription of this project, thankyou! </div>";	
		}
	} else 
	{
		$string .= "<p>&raquo; Your subscription unique ID <b class='grmsg'> ". $sanitized_sub_id ." </b> is Invalid.</p>";
	}
	echo $string;
	wp_die();
}
add_action( 'wp_ajax_revglue_store_subscription_validate', 'revglue_store_subscription_validate' );
function revglue_store_data_import()
{
	global $wpdb;
	$project_table = $wpdb->prefix.'rg_projects';
	$stores_table = $wpdb->prefix.'rg_stores';
	$categories_table = $wpdb->prefix.'rg_categories';
	$date = date("Y-m-d H:i:s");
	$store_category_ids = "store_category_ids";
	$store_category_id = "store_category_id";
	$category_title = "category_title";
	$string = '';
	$import_type = sanitize_text_field( $_POST['import_type'] );
	$sql = "SELECT * FROM $project_table where project like 'Stores UK'";
	$project_detail = $wpdb->get_results($sql);
	$rows = $wpdb->num_rows;
	if( !empty ( $rows ) )
	{
		$subscriptionid 	= 	$project_detail[0]->subcription_id;
		$useremail 			= 	$project_detail[0]->email;
		$userpassword 		= 	$project_detail[0]->password;
		$projectid 			= 	$project_detail[0]->partner_iframe_id;
		if( $import_type == 'rg_stores_import' )
		{
			revglue_store_update_subscription_expiry_date($subscriptionid, $userpassword, $useremail, $projectid);
			$template_type = revglue_store_check_subscriptions();
			if($template_type=="Free"){
				$apiURL ="https://www.revglue.com/partner/cashback_stores/$projectid/json/wp/$subscriptionid";
				$store_category_ids = "cashback_category_ids";
				// die($apiURL);
				$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( $apiURL , array( 'timeout' => 120, 'sslverify'   => false ) ) ), true);
			} else{

			$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/stores/json/".$project_detail[0]->subcription_id, array( 'timeout' => 120, 'sslverify'   => false ) ) ), true); 
			}
			 // pre($resp_from_server);
			 // die;
			$result = $resp_from_server['response']['stores'];
	  		if($resp_from_server['response']['success'] == true )
			{
				foreach($result as $row)
				{
					$sqlinstore = "Select rg_store_id FROM $stores_table Where rg_store_id = '".$row['rg_store_id']."'";
					$rg_store_exists = $wpdb->get_var( $sqlinstore );
					if( empty( $rg_store_exists ) )
					{
						$wpdb->insert( 
							$stores_table, 
							array( 
				'rg_store_id' 					=> $row['rg_store_id'], 
				'mid' 							=> $row['affiliate_network_mid'], 
				'title'							=> $row['store_title'], 
				'url_key' 						=> $row['url_key'], 
				'description'					=> $row['store_description'], 
				'image_url' 					=> $row['image_url'], 
				'affiliate_network' 			=> $row['affiliate_network'], 
				'affiliate_network_link'		=> str_replace("subid-value", "", $row['affiliate_network_link']), 
				'store_base_currency' 			=> $row['store_base_currency'], 
				'store_base_country' 			=> $row['store_base_country'],
				'category_ids'					=> $row[$store_category_ids],
				'date' 							=> $date
							) 
						);
					} else 
					{
						$wpdb->update( 
							$stores_table, 
							array( 
							'rg_store_id' 					=> $row['rg_store_id'], 
							'mid' 							=> $row['affiliate_network_mid'], 
							'title'							=> $row['store_title'], 
							'url_key' 						=> $row['url_key'], 
							'description'					=> $row['store_description'], 
							'image_url' 					=> $row['image_url'], 
							'affiliate_network' 			=> $row['affiliate_network'], 
							'affiliate_network_link'		=> str_replace("subid-value", "", $row['affiliate_network_link']),
							'store_base_currency' 			=> $row['store_base_currency'], 
							'store_base_country' 			=> $row['store_base_country'],
							'category_ids'					=> $row[$store_category_ids], 
							'date' 							=> $date
							),
							array( 'rg_store_id' => $rg_store_exists )
						);
					}
					// echo $wpdb->last_query;
					// die;		
				}
			$wpdb->query( "DELETE FROM $stores_table WHERE `date` != '$date' " );
			$homestoreshow = "SELECT * FROM $stores_table WHERE  homepage_store_tag ='no'";
			$storeIDs = $wpdb->get_results( $homestoreshow ); 
			foreach ($storeIDs as $key => $sID) 
			{ 
				$rg_theme_name= get_option("rg_theme_name"); 
				$update_array = array();
				if ( $rg_theme_name=="salepoint")
				 { 
				 	if( $key < 16 ) 
				 	{
				 		$update_array['homepage_store_tag'] = 'yes'; 
				 	}
				 }
				 elseif ($rg_theme_name=="alphastore") {
				 	if( $key < 10 ) 
				 	{
				 		$update_array['homepage_store_tag'] = 'yes'; 
				 	}
				 }
				 elseif ($rg_theme_name=="freestoretheme") {
				 	if( $key < 10 ) 
				 		{
				 			$update_array['homepage_store_tag'] = 'yes'; 
				 		}
				 	}
				 	elseif ($rg_theme_name=="shopstores") {
				 	if( $key < 20 ) 
				 		{
				 			$update_array['homepage_store_tag'] = 'yes'; 
				 		}
				 	}
				 	elseif ($rg_theme_name=="5store") {
				 	if( $key < 9 ) 
				 		{
				 			$update_array['homepage_store_tag'] = 'yes'; 
				 		}
				 	}
				 	elseif ($rg_theme_name=="timeforsale") {
				 	if( $key < 20 ) 
				 		{
				 			$update_array['homepage_store_tag'] = 'yes'; 
				 		}
				 	}
				 	else 
				 		{
				 			$update_array['homepage_store_tag'] = 'no';  
				 		}
				 		$wpdb->update( 
				 		$stores_table, 
				 		$update_array,
				 		array( 'rg_store_id' => $sID->rg_store_id )
				 	);
				 	// echo $rg_theme_name;
				 }
				 // echo $wpdb->last_query;
				 // die();
				} 
				else 
					{
						$string .= '<p style="color:red">'.$resp_from_server['response']['message'].'</p>';
					}
				} // end of store import call.
				else if( $import_type == 'rg_categories_import' )
					{
						revglue_store_update_subscription_expiry_date($subscriptionid, $userpassword, $useremail, $projectid);
						$template_type = revglue_store_check_subscriptions();
						if($template_type=="Free"){
							$apiURL ="https://www.revglue.com/partner/cashback_categories/$projectid/json/wp/$subscriptionid";
							$store_category_id = "cashback_category_id";
							$category_title = "cashback_cateogry_title";
							$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( $apiURL , array( 'timeout' => 120, 'sslverify'   => false ) ) ), true);
						}else{
							$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/stores_categories/json/".$project_detail[0]->subcription_id, array( 'timeout' => 120, 'sslverify'   => false ) ) ), true);
							}
							 // pre($resp_from_server );
							 // die;

						$resultCategories = $resp_from_server['response']['categories'];
						if($resp_from_server['response']['success'] == true )
							{
								foreach($resultCategories as $row)
									{	
										$sqlincat = "Select rg_category_id FROM $categories_table Where rg_category_id = '".$row[$store_category_id]."'";
					$rg_category_exists = $wpdb->get_var( $sqlincat );
					if( empty( $rg_category_exists ) )
					{					
						$title 		= $row[$category_title];
						$url_key 	= preg_replace('/[^\w\d_ -]/si', '', $title); 	// remove any special character
						$url_key 	= preg_replace('/\s\s+/', ' ', $url_key);		// replacing multiple spaces to signle
						$url_key 	= strtolower(str_replace(" ","-",$url_key));
						$wpdb->insert( 
							$categories_table, 
							array( 
								'rg_category_id' 			=> $row[$store_category_id], 
								'title' 					=> $row[$category_title], 
								'url_key' 					=> $url_key, 
								'date' 			    		=> $date, 
								'parent' 			    	=> $row['parent_category_id']
							) 
						);
					} else 
					{
						$wpdb->update( 
							$categories_table, 
							array( 
								'title' 		=> $row[$category_title], 
								'url_key' 		=> $url_key, 
								'date' 			=> $date, 
								'parent' 		=> $row['parent_category_id']
							),
							array( 'rg_category_id' => $rg_category_exists )
						);
					}
					// echo $wpdb->last_query;
					// die;			
				}
				$wpdb->query( "DELETE FROM $categories_table WHERE `date` != '$date' " );
			    $sqlParentCat = "SELECT * FROM $categories_table ";
				$CateIDs = $wpdb->get_results( $sqlParentCat ); 
				foreach ($CateIDs as $key => $cID) {
								$update_array = array();
								if($cID->parent == '0'){
									$update_array['header_category_tag'] = 'yes';
									$catid = $cID->rg_category_id;
								}else{
									$catid = $cID->parent;
								}
								$update_array['icon_url'] = $catid;
								$update_array['image_url'] = $catid;
								if($key < 12){
									$update_array['popular_category_tag'] = 'yes';
								}
								$wpdb->update( 
										$categories_table, 
										$update_array,
										array( 'rg_category_id' => $cID->rg_category_id )
									); 
								}
			} else 
			{
				$string .= '<p style="color:red">'.$resp_from_server['response']['message'].'</p>';
			}
		}
	} else 
	{
		$string .= "<p style='color:red'>Please subscribe for your RevGlue project first, then you have the facility to import the data";
	}
	$response_array = array();
	$response_array['error_msgs'] = $string;
	$sql = "SELECT MAX(date) FROM $stores_table";
	$last_updated_store = $wpdb->get_var($sql);
	$response_array['last_updated_store'] = ( $last_updated_store ? date( 'l , d-M-Y , h:i A', strtotime( $last_updated_store ) ) : '-' );
	$sql_1 = "SELECT MAX(date) FROM $categories_table";
	$last_updated_category = $wpdb->get_var($sql_1);
	$response_array['last_updated_category'] = ( $last_updated_category ? date( 'l , d-M-Y , h:i A', strtotime( $last_updated_category ) ) : '-' );
	$sql_3 = "SELECT count(*) as stores FROM $stores_table";
	$count_store = $wpdb->get_results($sql_3);
	$sql_2 = "SELECT count(*) as categories FROM $categories_table";
	$count_category = $wpdb->get_results($sql_2);
	$response_array['count_category'] = $count_category[0]->categories;
	$response_array['count_store'] = $count_store[0]->stores;
	// pre($response_array);
	// die("revglue_store_data_import");
	echo json_encode($response_array);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_data_import', 'revglue_store_data_import' );

function revglue_store_banner_data_import()
{
	global $wpdb;
	$project_table = $wpdb->prefix.'rg_projects';
	$banner_table = $wpdb->prefix.'rg_banner';
	$string = '';
	$date_only = date("Y-m-d");
	$import_type = sanitize_text_field( @$_POST['import_type'] );
	$sql = "SELECT *FROM $project_table WHERE project LIKE 'Banners UK'";
	$project_detail = $wpdb->get_results($sql);
	$rows = $wpdb->num_rows;
	if( !empty ( $rows ) )
	{
		if( $import_type == 'rg_banners_import'  )
		{
			$i = 0;
			$page = 1;
			do {
				$resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/banners/json/".$project_detail[0]->subcription_id."/".$page, array( 'timeout' => 120, 'sslverify'   => false ) ) ), true);

				update_option("rg_banners_status", $page);
				$total = ceil( $resp_from_server['response']['banners_total'] / 1000 ) ;
				$result = $resp_from_server['response']['banners'];
				// pre($result);
				// die();
				if($resp_from_server['response']['success'] == true )
				{
					foreach($result as $row)
					{
						$sqlinstore = "SELECT rg_store_banner_id FROM $banner_table WHERE rg_store_banner_id = '".$row['rg_banner_id']."' AND `banner_type` = 'imported'";
						$rg_banner_exists = $wpdb->get_var( $sqlinstore );
						if( empty( $rg_banner_exists ) )
						{
							$wpdb->insert( 
								$banner_table, 
								array( 
									'rg_store_banner_id' 	=> $row['rg_banner_id'], 
									'rg_store_id' 			=> $row['rg_store_id'], 
									'title' 				=> $row['banner_alt_text'], 
									'image_url' 			=> $row['banner_image_url'], 
									'url' 					=> $row['deep_link'], 
									'date' 			    	=> $date_only,
									'rg_size' 			    => $row['width_pixels'].'x'.$row['height_pixels'], 
									'placement' 			=> 'unassigned', 
									'banner_type' 			=> 'imported'
								) 
							);
						} else 
						{
							$wpdb->update( 
								$banner_table, 
								array( 
									'rg_store_id' 			=> $row['rg_store_id'], 
									'title' 				=> $row['banner_alt_text'], 
									'image_url' 			=> $row['banner_image_url'],	
									'date' 			    	=> $date_only,
									'url' 					=> $row['deep_link']
								),
								array( 'rg_store_banner_id' => $rg_banner_exists )
							);
						}										
					}
					
				} else 
				{
					$string .= '<p style="color:red">'.$resp_from_server['response']['message'].'</p>';
				}
				$i++;
				$page++;
			} while ( $i < $total );
			$wpdb->query( "DELETE FROM $banner_table WHERE `date` != '$date_only' " );
			
		}
	} else 
	{
		$string .= "<p style='color:red'>Please subscribe for your RevGlue project first, then you have the facility to import the data";
	}
	$response_array = array();
	$response_array['error_msgs'] = $string;
	$sql1 = "SELECT count(*) as banner FROM $banner_table WHERE banner_type= 'imported'";
	$count_banner = $wpdb->get_results($sql1);
	$response_array['count_banner'] = $count_banner[0]->banner; 
	echo json_encode($response_array);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_banner_data_import', 'revglue_store_banner_data_import' );
function revglue_store_data_delete()
{
	// die("revglue_store_data_delete");
	global $wpdb;
	$stores_table = $wpdb->prefix.'rg_stores';
	$categories_table = $wpdb->prefix.'rg_categories';
	$banner_table = $wpdb->prefix.'rg_banner';
	$data_type = sanitize_text_field( $_POST['data_type'] );
	$response_array = array();
	if( $data_type == 'rg_stores_delete' )
	{
		$response_array['data_type'] = 'rg_stores';
		$wpdb->query( "DELETE FROM $stores_table" );	
		$sql = "SELECT MAX(date) FROM $stores_table";
		$last_updated_store = $wpdb->get_var($sql);
		$response_array['last_updated_store'] = ( $last_updated_store ? date( 'l , d-M-Y, h:i A', strtotime( $last_updated_store ) ) : '-' );
		$sql2 = "SELECT count(*) as stores FROM $stores_table";
		$count_store = $wpdb->get_results($sql2);
		$response_array['count_store'] = $count_store[0]->stores;
	} else if( $data_type == 'rg_categories_delete' )
	{
		$response_array['data_type'] = 'rg_categories';
		$wpdb->query( "DELETE FROM $categories_table" );	
		$sql = "SELECT MAX(date) FROM $categories_table";
		$last_updated_category = $wpdb->get_var($sql);
		$response_array['last_updated_category'] = ( $last_updated_category ? date( 'l , d-M-Y, h:i A', strtotime( $last_updated_category ) ) : '-' );
		$sql2 = "SELECT count(*) as categories FROM $categories_table";
		$count_category = $wpdb->get_results($sql2);
		$response_array['count_category'] = $count_category[0]->categories;
	} else if( $data_type == 'rg_banners_delete' )
	{
		$response_array['data_type'] = 'rg_banners';
		$wpdb->query( "DELETE FROM $banner_table where banner_type='imported'" );	
		$sql1 = "SELECT count(*) as banner FROM $banner_table where banner_type= 'imported'";
		$count_banner = $wpdb->get_results($sql1);
		$response_array['count_banner'] = $count_banner[0]->banner;
	}
	echo json_encode($response_array);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_data_delete', 'revglue_store_data_delete' );
function revglue_store_update_home_store()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_stores';
	$store_id		= absint( $_POST['store_id'] );
	$cat_state 	= sanitize_text_field( $_POST['state'] );
	$wpdb->update( 
		$categories_table, 
		array( 'homepage_store_tag' => $cat_state ), 
		array( 'rg_store_id' => $store_id )
	);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_home_store', 'revglue_store_update_home_store' );
function revglue_store_update_popular_store()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_stores';
	$store_id		= absint( $_POST['store_id'] );
	$cat_state 	= sanitize_text_field( $_POST['state'] );
	$wpdb->update( 
		$categories_table, 
		array( 'popular_store_tag' => $cat_state ), 
		array( 'rg_store_id' => $store_id )
	);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_popular_store', 'revglue_store_update_popular_store' );
function revglue_store_update_header_category()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$cat_state 	= sanitize_text_field( $_POST['state'] );
	$wpdb->update( 
		$categories_table, 
		array( 'header_category_tag' => $cat_state ), 
		array( 'rg_category_id' => $cat_id )
	);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_header_category', 'revglue_store_update_header_category' );
function revglue_store_update_popular_category()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$cat_state 	= sanitize_text_field( $_POST['state'] );
	$wpdb->update( 
		$categories_table, 
		array( 'popular_category_tag' => $cat_state ), 
		array( 'rg_category_id' => $cat_id )
	);
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_popular_category', 'revglue_store_update_popular_category' );
function revglue_store_update_category_icon()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$icon_url 	= esc_url_raw( $_POST['icon_url'] );
	$wpdb->update( 
		$categories_table, 
		array( 'icon_url' => $icon_url ), 
		array( 'rg_category_id' => $cat_id )
	);
	echo $cat_id;
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_category_icon', 'revglue_store_update_category_icon' );
function revglue_store_delete_category_icon()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$wpdb->update( 
		$categories_table, 
		array( 'icon_url' => '' ), 
		array( 'rg_category_id' => $cat_id )
	);
	echo $cat_id;
	wp_die();
}
add_action( 'wp_ajax_revglue_store_delete_category_icon', 'revglue_store_delete_category_icon' );
function revglue_store_update_category_image()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$image_url 	= esc_url_raw( $_POST['image_url'] );
	$wpdb->update( 
		$categories_table, 
		array( 'image_url' => $image_url ), 
		array( 'rg_category_id' => $cat_id )
	);
	echo $cat_id;
	wp_die();
}
add_action( 'wp_ajax_revglue_store_update_category_image', 'revglue_store_update_category_image' );
function revglue_store_delete_category_image()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$cat_id		= absint( $_POST['cat_id'] );
	$wpdb->update( 
		$categories_table, 
		array( 'image_url' => '' ), 
		array( 'rg_category_id' => $cat_id )
	);
	echo $cat_id;
	wp_die();
}
add_action( 'wp_ajax_revglue_store_delete_category_image', 'revglue_store_delete_category_image' );
function revglue_store_load_stores()
{
	global $wpdb; 
	$categories_table = $wpdb->prefix.'rg_categories';
	$sTable = $wpdb->prefix.'rg_stores';
	$aColumns = array( 'rg_store_id', 'affiliate_network', 'mid', 'image_url', 'title', 'store_base_country', 'affiliate_network_link', 'category_ids', 'homepage_store_tag' ); 
	$sIndexColumn = "rg_store_id"; 
	$sLimit = "LIMIT 1, 50";

if ( isset( $_REQUEST['start'] ) && sanitize_text_field($_REQUEST['length'])  != '-1' )
	{
		$sLimit = "LIMIT ".intval( sanitize_text_field($_REQUEST['start']) ).", ".intval( sanitize_text_field($_REQUEST['length'])  );
	}
	$sOrder = "";
	// make order functionality
	$where = "";
	$globalSearch = array();
	$columnSearch = array();
	$dtColumns = $aColumns;
	if ( isset($_REQUEST['search']) && sanitize_text_field($_REQUEST['search']['value'])  != '' ) {

		$str = sanitize_text_field($_REQUEST['search']['value']);


$request_columns = [];
		foreach ($_REQUEST['columns'] as $key => $val ) {
			if(is_array($val)){$request_columns[$key] = $val;}
			else{$request_columns[$key] = sanitize_text_field($val);}
		}


		for ( $i=0, $ien=count($request_columns) ; $i<$ien ; $i++ ) {
			$requestColumn = sanitize_text_field($request_columns[$i]) ;

			$column = sanitize_text_field($dtColumns[ $requestColumn['data'] ]) ;
			if ( $requestColumn['searchable'] == 'true' ) {
				$globalSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}



	/*	for ( $i=0, $ien=count($_REQUEST['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $_REQUEST['columns'][$i];
			$column = $dtColumns[ $requestColumn['data'] ];
			if ( $requestColumn['searchable'] == 'true' ) {
				$globalSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}*/







	}
	// Individual column filtering
	if ( isset( $_REQUEST['columns'] ) ) {


$request_columns = [];
		foreach ($_REQUEST['columns'] as $key => $val ) {
			if(is_array($val)){$request_columns[$key] = $val;}
			else{$request_columns[$key] = sanitize_text_field($val);}
		}

					for ( $i=0, $ien=count($request_columns) ; $i<$ien ; $i++ ) {
			$requestColumn = sanitize_text_field($request_columns[$i]) ;
			//$columnIdx = array_search( $requestColumn['data'], $dtColumns );
			$column = sanitize_text_field($dtColumns[ $requestColumn['data'] ]) ;
			$str = sanitize_text_field($requestColumn['search']['value']) ;
			if ( $requestColumn['searchable'] == 'true' &&
			 $str != '' ) {
				$columnSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}



	/*	for ( $i=0, $ien=count($_REQUEST['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $_REQUEST['columns'][$i];
			$column = $dtColumns[ $requestColumn['data'] ];
			$str = $requestColumn['search']['value'];
			if ( $requestColumn['searchable'] == 'true' &&
			 $str != '' ) {
				$columnSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}*/


	}
	// Combine the filters into a single string
	$where = '';
	if ( count( $globalSearch ) ) {
		$where = '('.implode(' OR ', $globalSearch).')';
	}
	if ( count( $columnSearch ) ) {
		$where = $where === '' ?
			implode(' AND ', $columnSearch) :
			$where .' AND '. implode(' AND ', $columnSearch);
	}
	if ( $where !== '' ) {
		$where = 'WHERE '.$where;
	}
	$sQuery = "SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."` FROM   $sTable $where $sOrder $sLimit";
	$rResult = $wpdb->get_results($sQuery, ARRAY_A);
	$sQuery = "SELECT FOUND_ROWS()";
	$rResultFilterTotal = $wpdb->get_results($sQuery, ARRAY_N); 
	$iFilteredTotal = $rResultFilterTotal [0];
	$sQuery = "SELECT COUNT(`".$sIndexColumn."`) FROM   $sTable";
	$rResultTotal = $wpdb->get_results($sQuery, ARRAY_N); 
	$iTotal = $rResultTotal [0];
	$output = array(
		"draw"            => isset ( $_REQUEST['draw'] ) ? intval( sanitize_text_field($_REQUEST['draw']) ) : 0,
		"recordsTotal"    => $iTotal,
		"recordsFiltered" => $iFilteredTotal,
		"data"            => array()
	);
	foreach($rResult as $aRow)
	{
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if( $i == 3 )
			{
				$row[] = '<div class="revglue-banner-thumb"><img class="revglue-unveil" src="' . RGSTORE_PLUGIN_URL . '/admin/images/loading.gif" data-src="' . esc_url( $aRow[ $aColumns[$i] ] ) . '" /></div>';
			} else if( $i == 6 )
			{
				$row[] = '<a class="" title="'. esc_url( $aRow[ $aColumns[$i] ] ).'" id="'. esc_html( $aRow[ $aColumns[0] ] )  .'"  href="'. esc_url( $aRow[ $aColumns[$i] ] ).'" target="_blank"><img src="'. RGSTORE_PLUGIN_URL .'/admin/images/linkicon.png" style="width:50px;"/><div id="imp_popup'. esc_html( $aRow[ $aColumns[0] ] ).'" style="background: #ececec; left: 60px; margin: 5px 0; padding: 10px; position: absolute; top: 10px; display:none; border-radius: 8px; border: 1px solid #ccc">'. ( $aRow[ $aColumns[$i] ] ? esc_url( $aRow[ $aColumns[$i] ] ) : 'No Link' ) .'</div></a>';
			} else if( $i == 7 )
			{
				$catID = $aRow[ $aColumns[$i] ];
				$pieces = explode(",", $catID);
				$impname = array();
				foreach($pieces as $value){
					if ( !empty($value) )
					{
						$sqlcat = "SELECT *FROM $categories_table where rg_category_id = $value";
						$catrows = $wpdb->get_results($sqlcat);
						foreach($catrows as $storecatnames)
						{
							$impname[] = $storecatnames->title;
						}
					}
				}
				$row[] = ( $impname ? implode(', ',$impname) : 'No Category Available' );
			} else if( $i == 8 )
			{
				if( $aRow[ $aColumns[$i] ] == 'yes' )
				{
					$checked = 'checked="checked"';
				} else
				{
					$checked = '';
				}
				$row[] = '<input '.$checked.' type="checkbox" id="'.$aRow[ $aColumns[0] ].'" class="rg_store_homepage_tag" />';
			} 
			else if ( $aColumns[$i] != ' ' )
			{    
				$row[] = $aRow[ $aColumns[$i] ];
			}
		}
		$output['data'][] = $row;
	}
	echo json_encode( $output );
	die(); 
}
add_action( 'wp_ajax_revglue_store_load_stores', 'revglue_store_load_stores' );
function revglue_store_load_banners()
{
	global $wpdb; 
	$stores_table = $wpdb->prefix.'rg_stores';
	$sTable = $wpdb->prefix.'rg_banner';
	$upload = wp_upload_dir();
	$base_url = $upload['baseurl'];
	$uploadurl = $base_url.'/revglue/stores/banners/';
	$placements = array(
		'home-top'				=> 'Home:: Top Header',
		'home-slider'			=> 'Home:: Main Banners',
		'home-mid'				=> 'Home:: After Categories',
		'home-bottom'			=> 'Home:: Before Footer',
		'cat-top'				=> 'Category:: Top Header',
		'cat-side-top'			=> 'Category:: Top Sidebar',
		'cat-side-bottom'		=> 'Category:: Bottom Sidebar 1',
		'cat-side-bottom-two'	=> 'Category:: Bottom Sidebar 2',
		'cat-bottom'			=> 'Category:: Before Footer',
		'store-top'				=> 'Store:: Top Header',
		'store-side-top'		=> 'Store:: Top Sidebar',
		'store-side-bottom'		=> 'Store:: Bottom Sidebar 1',
		'store-side-bottom-two'	=> 'Store:: Bottom Sidebar 2',
		'store-main-bottom'		=> 'Store:: After Review',
		'store-bottom'			=> 'Store:: Before Footer',
		'unassigned' 			=> 'Unassigned Banners'
	);
	$aColumns = array( 'banner_type', 'placement', 'status', 'title', 'url', 'image_url', 'rg_store_id', 'rg_id', 'rg_store_banner_id', 'rg_store_name', 'rg_size'  ); 
	$sIndexColumn = "rg_store_id"; 
	$sLimit = "LIMIT 1, 50";

if ( isset( $_REQUEST['start'] ) && sanitize_text_field($_REQUEST['length'])  != '-1' )
	{
		$sLimit = "LIMIT ".intval( sanitize_text_field($_REQUEST['start']) ).", ".intval( sanitize_text_field($_REQUEST['length'])  );
	}
	$sOrder = "";
	// make order functionality
	$where = "";
	$globalSearch = array();
	$columnSearch = array();
	$dtColumns = $aColumns;
	if ( isset($_REQUEST['search']) && sanitize_text_field($_REQUEST['search']['value']) != '' ) {
		$str = sanitize_text_field($_REQUEST['search']['value']);

$request_columns = [];
		foreach ($_REQUEST['columns'] as $key => $val ) {
			if(is_array($val)){$request_columns[$key] = $val;}
			else{$request_columns[$key] = sanitize_text_field($val);}
		}


		for ( $i=0, $ien=count($request_columns) ; $i<$ien ; $i++ ) {
			$requestColumn = sanitize_text_field($request_columns[$i]) ;

			$column = sanitize_text_field($dtColumns[ $requestColumn['data'] ]) ;
			if ( $requestColumn['searchable'] == 'true' ) {
				$globalSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}




	/*	for ( $i=0, $ien=count($_REQUEST['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $_REQUEST['columns'][$i];
			$column = $dtColumns[ $requestColumn['data'] ];
			if ( $requestColumn['searchable'] == 'true' ) {
				$globalSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}*/
	}
	// Individual column filtering
	if ( isset( $_REQUEST['columns'] ) ) {

$request_columns = [];
		foreach ($_REQUEST['columns'] as $key => $val ) {
			if(is_array($val)){$request_columns[$key] = $val;}
			else{$request_columns[$key] = sanitize_text_field($val);}
		}


			for ( $i=0, $ien=count($request_columns) ; $i<$ien ; $i++ ) {
			$requestColumn = $request_columns[$i];
			//$columnIdx = array_search( $requestColumn['data'], $dtColumns );
			$column = sanitize_text_field($dtColumns[ $requestColumn['data'] ]);
			$str = sanitize_text_field($requestColumn['search']['value']) ;
			if ( $requestColumn['searchable'] == 'true' &&
			 $str != '' ) {
				$columnSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}


	/*	for ( $i=0, $ien=count($_REQUEST['columns']) ; $i<$ien ; $i++ ) {
			$requestColumn = $_REQUEST['columns'][$i];
			//$columnIdx = array_search( $requestColumn['data'], $dtColumns );
			$column = $dtColumns[ $requestColumn['data'] ];
			$str = $requestColumn['search']['value'];
			if ( $requestColumn['searchable'] == 'true' &&
			 $str != '' ) {
				$columnSearch[] = "`".$column."` LIKE '%".$str."%'";
			}
		}*/
	}
	// Combine the filters into a single string
	$where = '';
	if ( count( $globalSearch ) ) {
		$where = '('.implode(' OR ', $globalSearch).')';
	}
	if ( count( $columnSearch ) ) {
		$where = $where === '' ?
			implode(' AND ', $columnSearch) :
			$where .' AND '. implode(' AND ', $columnSearch);
	}
	if ( $where !== '' ) {
		$where = 'WHERE '.$where;
	}
	$sQuery = "SELECT SQL_CALC_FOUND_ROWS `".str_replace(" , ", " ", implode("`, `", $aColumns))."` FROM   $sTable $where $sOrder $sLimit";
	$rResult = $wpdb->get_results($sQuery, ARRAY_A);
	$sQuery = "SELECT FOUND_ROWS()";
	$rResultFilterTotal = $wpdb->get_results($sQuery, ARRAY_N); 
	$iFilteredTotal = $rResultFilterTotal [0];
	$sQuery = "SELECT COUNT(`".$sIndexColumn."`) FROM   $sTable";
	$rResultTotal = $wpdb->get_results($sQuery, ARRAY_N); 
	$iTotal = $rResultTotal [0];
	$output = array(
		"draw"            => isset ( $_REQUEST['draw'] ) ? intval( sanitize_text_field($_REQUEST['draw']) ) : 0,
		"recordsTotal"    => $iTotal,
		"recordsFiltered" => $iFilteredTotal,
		"data"            => array()
	);
	foreach($rResult as $aRow)
	{
		$row = array();
		for ( $i=0 ; $i<count($aColumns) ; $i++ )
		{
			if( $i == 0 )
			{
				if( $aRow[ $aColumns[5] ] == '' )
				{
					$uploadedbanner = $uploadurl . $aRow[ $aColumns[3] ];
					$row[] = '<div class="revglue-banner-thumb"><img class="revglue-unveil" src="'. RGSTORE_PLUGIN_URL .'/admin/images/loading.gif" data-src="'. esc_url( $uploadedbanner ) .'"/></div>';
				} else
				{
					$row[] = '<div class="revglue-banner-thumb"><img class="revglue-unveil" src="'. RGSTORE_PLUGIN_URL .'/admin/images/loading.gif" data-src="'. esc_url( $aRow[ $aColumns[5] ] ) .'" /></div>';
				}
			}else if( $i == 1 )
			{
				$row[] = $aRow[ $aColumns[8] ];
			} else if( $i == 2 )
			{
				$row[] = $aRow[ $aColumns[9] ];
			} else if( $i == 3 )
			{
				$row[] = ( $aRow[ $aColumns[0] ] == 'local' ? 'Local' : 'RevGlue Banner' );
			} else if( $i == 4 )
			{
				$row[] = $placements[$aRow[ $aColumns[1]]];
			} else if( $i == 5 )
			{
				$row[] = $aRow[ $aColumns[10]];
			} else if( $i == 6 )
			{
				if( ! empty( $aRow[ $aColumns[4]] ) )
				{
					$url_to_show = esc_url( $aRow[ $aColumns[4]] ); 
				} else if( ! empty( $aRow[ $aColumns[6]] ) )
				{
					$sql_1 = "SELECT affiliate_network_link FROM $stores_table where rg_store_id = ".$aRow[ $aColumns[6]];
					$deep_link = $wpdb->get_results($sql_1);
					$url_to_show = ( !empty( $deep_link[0]->affiliate_network_link ) ? esc_url( $deep_link[0]->affiliate_network_link ) : 'No Link'  );
				} else
				{
					$url_to_show = 'No Link';
				}
				$row[] = '<a class="rg_store_link_pop_up" id="'. $aRow[ $aColumns[7]] .'" href="'. $url_to_show .'" target="_blank"><img src="'. RGSTORE_PLUGIN_URL .'/admin/images/linkicon.png" style="width:50px;"/><div id="imp_popup'. $aRow[ $aColumns[7]] .'" style="background: #ececec; left: 60px; margin: 5px 0; padding: 10px; position: absolute; top: 10px; display:none; border-radius: 8px; border: 1px solid #ccc">'.$url_to_show.'</div></a>';
			} else if( $i == 7 )
			{
				$row[] = $aRow[ $aColumns[2]];
			} else if( $i == 8 )
			{
				$row[] = '<a href="'. admin_url( 'admin.php?page=revglue-banners&action=edit&banner_id='.$aRow[ $aColumns[7]] ) .'">Edit</a>';
			} else if ( $aColumns[$i] != ' ' )
			{    
				$row[] = $aRow[ $aColumns[$i] ];
			}
		}
		$output['data'][] = $row;
	}
	echo json_encode( $output );
	die(); 
}
add_action( 'wp_ajax_revglue_store_load_banners', 'revglue_store_load_banners' );

function revglue_store_check_subscriptions(){
	global $wpdb;
	$project_table = $wpdb->prefix.'rg_projects';
	$sql ="SELECT `expiry_date` FROM $project_table WHERE `expiry_date`='Free' ";
	$project = $wpdb->get_var($sql);
	return $project;
}

function revglue_store_update_subscription_expiry_date($purchasekey, $userpassword, $useremail, $projectid){
 global $wpdb;
 $projects_table = $wpdb->prefix.'rg_projects';
 $apiurl = RGSTORE_API_URL."api/validate_subscription_key/$useremail/$userpassword/$purchasekey";
 $resp_from_server = json_decode( wp_remote_retrieve_body( wp_remote_get( $apiurl , array( 'timeout' => 120, 'sslverify' => false ))), true);
 $expiry_date = $resp_from_server['response']['result']['expiry_date'];
 if ( empty($projectid)){
  $sql ="UPDATE $projects_table SET `expiry_date` = '$expiry_date' WHERE `subcription_id` ='$purchasekey'";
  $wpdb->query($sql);
 } 
}