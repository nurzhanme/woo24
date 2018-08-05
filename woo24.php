<?php
/*
Plugin Name: Woo24
Plugin URI: http://страница_с_описанием_плагина_и_его_обновлений
Description: Интеграция WooCommerce и Bitrix24
Version: 1.0
Author: Айтбаев Нуржан
Author URI: abcrm.kz
*/

// create custom plugin settings menu
add_action('admin_menu', 'woo24_create_menu');

function woo24_create_menu() {

	//create new top-level menu
	add_menu_page('Woo24 Plugin Settings', 'Woo24 Settings', 'administrator', __FILE__, 'woo24_settings_page',plugins_url('/images/icon.png', __FILE__));

	//call register settings function
	add_action( 'admin_init', 'register_woo24settings' );
}

function register_woo24settings() {
	//register our settings
	register_setting( 'woo24-settings-group', 'woo24_b24hookurl' );
}

function woo24_settings_page() {
?>

<div class="wrap">
<h2>Woo24</h2>

<form method="post" action="options.php">
<?php settings_fields( 'woo24-settings-group' ); ?>

<table class="form-table">

<tr valign="top">
<th scope="row">WebHook url bitrix24</th>
<td><input type="text" name="woo24_b24hookurl" value="<?php echo get_option('woo24_b24hookurl'); ?>" /></td>
</tr>
 
</table>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="woo24_b24hookurl" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
<?php } 

add_action( 'added_post_meta', 'woo24_on_product_save', 10, 4 );
add_action( 'updated_post_meta', 'woo24_on_product_save', 10, 4 );
function woo24_on_product_save( $meta_id, $post_id, $meta_key, $meta_value ) {
    if ( $meta_key == '_edit_lock' ) { // we've been editing the post
        if ( get_post_type( $post_id ) == 'product' ) { // we've been editing a product
            
			$b24_prod_id = woo24_get_b24_prod_id_db($post_id);
			
			$product = wc_get_product( $post_id );
			
			$b24hookurl = get_option('woo24_b24hookurl');
			
			if($b24_prod_id > 0) {
				$queryUrl = $b24hookurl.'crm.product.update';
				
				$queryData = http_build_query(array( 'id' => $b24_prod_id,  'fields' => array( "NAME" => $product->name, "CURRENCY_ID" => "KZT", "PRICE" => $product->price), 'params' => array("REGISTER_SONET_EVENT" => "Y") )); $curl = curl_init(); curl_setopt_array($curl, array( CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_POST => 1, CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $queryUrl, CURLOPT_POSTFIELDS => $queryData, )); $result = curl_exec($curl); curl_close($curl); $result = json_decode($result, 1);

			} else {
				$queryUrl = $b24hookurl.'crm.product.add';
			
				$queryData = http_build_query(array( 'fields' => array( "NAME" => $product->name, "CURRENCY_ID" => "KZT", "PRICE" => $product->price), 'params' => array("REGISTER_SONET_EVENT" => "Y") )); $curl = curl_init(); curl_setopt_array($curl, array( CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_POST => 1, CURLOPT_HEADER => 0, CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $queryUrl, CURLOPT_POSTFIELDS => $queryData, )); $result = curl_exec($curl); curl_close($curl); $result = json_decode($result, 1);
				
				woo24_add_db($post_id, $result->data());
			}
        }
    }
}

global $woo24_db_version;
$woo24_db_version = '1.0';

function woo24_install() {
	global $wpdb;
	global $woo24_db_version;

	$table_name = $wpdb->prefix . 'woo24';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		wc_prod_id mediumint(9) NOT NULL,
		b24_prod_id mediumint(9) NOT NULL,
		PRIMARY KEY  (wc_prod_id, b24_prod_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'woo24_db_version', $woo24_db_version );
}

register_activation_hook( __FILE__, 'woo24_install' );

function woo24_get_b24_prod_id_db($wc_prod_id) {
	global $wpdb;
	
	$woo24_b24_prod_id =  $wpdb->get_row("SELECT b24_prod_id FROM " . $wpdb->prefix . "woo24 WHERE wc_prod_id = " . $wc_prod_id);
	
	return $woo24_b24_prod_id;
}

function woo24_add_db($wc_prod_id, $b24_prod_id) {
	global $wpdb;
	
	$woo24_b24_prod_id =  $wpdb->get_row("INSERT INTO " . $wpdb->prefix . " (wc_prod_id, b24_prod_id) VALUES (" . $woo24_wc_prod_id . ", " . $b24_prod_id . ");");
}



?>