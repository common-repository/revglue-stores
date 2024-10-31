<?php 

// Exit if accessed directly

if ( !defined( 'ABSPATH' ) ) exit;

global $wpdb;

$banner_table = $wpdb->prefix.'rg_banner';

$stores_table = $wpdb->prefix.'rg_stores';

$categories_table = $wpdb->prefix.'rg_categories';

$project_table = $wpdb->prefix.'rg_projects';

$sql = "SELECT *FROM $project_table where project like 'Stores UK'";

$project_detail = $wpdb->get_results($sql);

$rows = $wpdb->num_rows;

$qry_response = '';

if( !empty ( $rows ) )

{

	$jsonData = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/stores/json/".$project_detail[0]->subcription_id ) ), true); 

	$result = $jsonData['response']['stores'];

	if($jsonData['response']['success'] == true )

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

						'affiliate_network_link'		=> $row['affiliate_network_link'], 

						'store_base_currency' 			=> $row['store_base_currency'], 

						'store_base_country' 			=> $row['store_base_country'], 

						'category_ids'					=> $row['store_category_ids'],

						'date' 							=> date('Y-m-d H:i:s')

					) 

				);

			}					

		}

		echo 'You successfully imported the stores<br>';

	} else 

	{

		$qry_response = '<p style="color:red">'.$jsonData['response']['message'].'</p>';

	}

	$jsonDataCategories = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/stores_categories/json/".$project_detail[0]->subcription_id ) ), true); 

	$resultCategories = $jsonDataCategories['response']['categories'];

	if($jsonDataCategories['response']['success'] == true )

	{

		foreach($resultCategories as $row)

		{	

			$sqlincat = "Select rg_category_id FROM $categories_table Where rg_category_id = '".$row['store_category_id']."'";

			$rg_category_exists = $wpdb->get_var( $sqlincat );

			if( empty( $rg_category_exists ) )

			{					

				$title 		= $row['category_title'];

				$url_key 	= preg_replace('/[^\w\d_ -]/si', '', $title); 	// remove any special character

				$url_key 	= preg_replace('/\s\s+/', ' ', $url_key);		// replacing multiple spaces to signle

				$url_key 	= strtolower(str_replace(" ","-",$url_key));

				$wpdb->insert( 

					$categories_table, 

					array( 

						'category_id' 			=> $row['store_category_id'], 

						'title' 				=> $row['category_title'], 

						'url_key' 						=> $url_key, 

						'parent_id' 			=> $row['parent_category_id'], 

						'date' 							=> date('Y-m-d H:i:s')

					) 

				);

			}

		}

		echo 'You successfully imported the categories<br>';

	} else 

	{

		$qry_response = '<p style="color:red">'.$jsonDataCategories['response']['message'].'</p>';

	}

}

$sql = "SELECT *FROM $project_table where project like 'Banners UK'";

$project_detail = $wpdb->get_results($sql);

$rows = $wpdb->num_rows;

if( !empty ( $rows ) )

{

	$banner_table = $wpdb->prefix.'rg_banner';

	$jsonDataBanners = json_decode( wp_remote_retrieve_body( wp_remote_get( RGSTORE_API_URL . "api/banners/json/".$project_detail[0]->subcription_id ) ), true); 

	$result = $jsonDataBanners['response']['banners'];

	if($jsonDataBanners['response']['success'] == true )

	{

		foreach($result as $row)

		{

			$sqlinstore = "Select rg_store_banner_id FROM $banner_table Where rg_store_banner_id = '".$row['rg_banner_id']."' AND `banner_type` = 'imported'";

			$rg_banner_exists = $wpdb->get_var( $sqlinstore );

			if( empty( $rg_banner_exists ) )

			{

				$wpdb->insert( 

					$banner_table, 

					array( 

						'rg_store_banner_id' 	=> $row['rg_banner_id'], 

						'rg_store_id' 			=> $row['rg_store_id'], 

						'title' 				=> $row['banner_alt_text'],   

						'image_url' 			=> str_replace("http", "https", $row['banner_image_url']), 

						'rg_size' 				=> $row['width_pixels'].'x'.$row['height_pixels'], 

						'placement' 			=> 'unassigned', 

						'banner_type' 			=> 'imported'

					) 

				);

			}					

		}

		echo 'You successfully imported the banners<br>';

	} else 

	{

		$qry_response = '<p style="color:red">'.$jsonDataBanners['response']['message'].'</p>';

	}

	echo $qry_response;

}			

exit();

?>