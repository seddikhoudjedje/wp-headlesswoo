<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Order_Export_Data_Extractor_UI extends WC_Order_Export_Data_Extractor {
	static $object_type = 'shop_order';
	const  HUGE_SHOP_CUSTOMERS = 1000;// more than 1000 customers

	// ADD custom fields for export
	public static function get_all_order_custom_meta_fields( $sql_order_ids='' ) {
		global $wpdb;
		
		$transient_key = 'woe_get_all_order_custom_meta_fields_results_'.md5( json_encode( $sql_order_ids ) ); // complex key
		$fields = get_transient( $transient_key );
		if($fields  === false) {
			$sql_in_orders = '';
			if( $sql_order_ids )
				$sql_in_orders  = " AND ID IN ($sql_order_ids) ";

			// must show all
			if( !$sql_in_orders ) {
				//rewrite for huge # of users
				$total_users = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
				if( $total_users >= self::HUGE_SHOP_CUSTOMERS ) {
				   	$user_ids = $wpdb->get_col( "SELECT  ID FROM {$wpdb->users} ORDER BY ID DESC LIMIT 1000"); // take last 1000
					$user_ids = join( ",", $user_ids);
					$where_users = "WHERE user_id IN ($user_ids)";
				}	
				else
					$where_users = '';
				$user_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} $where_users" );
				$fields = self::get_order_custom_fields();
			} else	{
				$user_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->posts} INNER JOIN {$wpdb->usermeta} ON {$wpdb->posts}.post_author = {$wpdb->usermeta}.user_id WHERE post_type = '" . self::$object_type . "' {$sql_in_orders}" );
				$fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id WHERE post_type = '" . self::$object_type . "' {$sql_in_orders}" );
			}

			foreach($user_fields as $k=>$v)
				$user_fields[$k] = 'USER_'.$v;
			$fields    = array_unique( array_merge( $fields, $user_fields ) );
			sort( $fields );
			//debug set_transient( $transient_key, $fields, 60 ); //valid for a 1 min
		}	
		return apply_filters( 'woe_get_all_order_custom_meta_fields', $fields );
	}

	//filter attributes by matched orders
	public static function get_all_product_custom_meta_fields_for_orders( $sql_order_ids ) {
		global $wpdb;

		$wc_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN
									(SELECT DISTINCT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id IN ($sql_order_ids))" );

		// WC internal table add attributes
		$wc_attr_fields = $wpdb->get_results( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies" );
		foreach ( $wc_attr_fields as $f ) {
			$wc_fields[] = 'pa_' . $f->attribute_name;
		}

		//sql to gather product id for orders
		$sql_products = "SELECT DISTINCT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key ='_product_id' AND order_item_id IN
									(SELECT DISTINCT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id IN ($sql_order_ids))";

		$wp_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id IN
									(SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND ID IN ($sql_products))" );

		$fields    = array_unique( array_merge( $wp_fields, $wc_fields ) );
		$fields = sort( $fields );

		return apply_filters( 'get_all_product_custom_meta_fields_for_orders', $fields );
	}

	public static function get_order_item_custom_meta_fields_for_orders( $sql_order_ids ) {
		global $wpdb;

		$wc_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id IN
									(SELECT DISTINCT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id IN ($sql_order_ids))" );
		// WC internal table add attributes
		$wc_attr_fields = $wpdb->get_results( "SELECT DISTINCT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies" );
		foreach ( $wc_attr_fields as $f ) {
			$wc_fields[] = 'pa_' . $f->attribute_name;
		}

		$wc_fields = array_unique($wc_fields);
		sort($wc_fields);



		return apply_filters( 'get_order_item_custom_meta_fields_for_orders', $wc_fields );
	}

	public static function get_product_custom_meta_fields_for_orders( $sql_order_ids ) {
		global $wpdb;

		$sql_products = "SELECT DISTINCT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key ='_product_id' AND order_item_id IN
									(SELECT DISTINCT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_type = 'line_item' AND order_id IN ($sql_order_ids))";

		$wp_fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} WHERE post_id IN
									(SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation') AND ID IN ($sql_products))" );

		sort($wp_fields);

		return apply_filters( 'get_product_custom_meta_fields_for_orders', $wp_fields );
	}

	public static function get_all_product_custom_meta_fields() {
		global $wpdb;

		$wc_fields = self::get_product_itemmeta();

		// WC internal table add attributes
		$wc_attr_fields = $wpdb->get_results( "SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies" );
		foreach ( $wc_attr_fields as $f ) {
			$wc_fields[] = 'pa_' . $f->attribute_name;
		}

		// WP internal table	, skip hidden and attributes
		$wp_fields = self::get_product_custom_fields();

		$fields    = array_unique( array_merge( $wp_fields, $wc_fields ) );
		sort( $fields );

		return apply_filters( 'woe_get_all_product_custom_meta_fields', $fields );
	}

	public static function get_all_coupon_custom_meta_fields() {
		global $wpdb;

		// WP internal table	, skip hidden and attributes
		$fields = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} INNER JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
											WHERE post_type = 'shop_coupon'" );
		sort( $fields );

		return apply_filters( 'woe_get_all_coupon_custom_meta_fields', $fields );
	}

	//for FILTERS

	public static function get_products_like( $like ) {
		global $wpdb;
		$like     = $wpdb->esc_like( $like );
		$query    = "
                SELECT      post.ID as id,post.post_title as text,att.ID as photo_id,att.guid as photo_url
                FROM        " . $wpdb->posts . " as post
                LEFT JOIN  " . $wpdb->posts . " AS att ON post.ID=att.post_parent AND att.post_type='attachment'
                WHERE       post.post_title LIKE '%{$like}%'
                AND         post.post_type = 'product'
                AND         post.post_status <> 'trash'
                GROUP BY    post.ID
                ORDER BY    post.post_title
                LIMIT 0,5
                ";
		$products = $wpdb->get_results( $query );
		foreach ( $products as $key => $product ) {
			if ( $product->photo_id ) {
				$photo                       = wp_get_attachment_image_src( $product->photo_id, 'thumbnail' );
				$products[ $key ]->photo_url = $photo[0];
			}
			else
				unset( $products[ $key ]->photo_url );
		}
		return $products;
	}

	public static function get_users_like( $like ) {
		global $wpdb;
		$ret = array();

		$like  = '*' . $wpdb->esc_like( $like ) . '*';
		$users = get_users( array( 'search' => $like , 'orderby' => 'display_name' ) );

		foreach ( $users as $key => $user ) {
			$ret[] = array(
					'id'   => $user->ID,
					'text' => $user->display_name
			);
		}
		return $ret;
	}

	public static function get_coupons_like( $like ) {
		global $wpdb;

		$like  = $wpdb->esc_like( $like );
		$query = "
                SELECT      post.post_title as id, post.post_title as text
                FROM        " . $wpdb->posts . " as post
                WHERE       post.post_title LIKE '%{$like}%'
                AND         post.post_type = 'shop_coupon'
                AND         post.post_status <> 'trash'
                ORDER BY    post.post_title
                LIMIT 0,10
        ";
		return $wpdb->get_results( $query );
	}

	public static function get_categories_like( $like ) {
		$cat = array();
		foreach (get_terms( 'product_cat','hide_empty=0&hierarchical=1&name__like=' . $like . '&number=10' ) as $term ) {
			$cat[] = array( "id" => $term->term_id, "text" => $term->name );
		}
		return $cat;
	}

	public static function get_order_custom_fields_values( $key ) {
		global $wpdb;
		$values  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s  AND post_id IN (SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = '" . self::$object_type . "' )" , $key ) );
		sort( $values );
		return $values;
	}

	public static function get_product_custom_fields_values( $key ) {
		global $wpdb;
		$values  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s    AND post_id IN (SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation' OR post_type = 'product')" , $key ) );
		sort( $values );
		return $values;
	}

	public static function get_products_taxonomies_values( $key ) {
		$values = array();
		$terms = get_terms( array( 'taxonomy' => $key ) );
		if ( ! empty( $terms ) ) {
			$values = array_map( function ( $term ) {
				return $term->name;
			}, $terms );
		}
		sort( $values );
		return $values;
	}

	public static function get_products_itemmeta_values( $key ) {
        global $wpdb;
        $meta_key_ent = esc_html($key);
		$metas = $wpdb->get_col( $wpdb->prepare("SELECT DISTINCT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta where meta_key = '%s' OR meta_key='%s' LIMIT 100", $key, $meta_key_ent ));
		sort( $metas );
		return $metas;
	}

	public static function get_products_attributes_values( $key ) {
		$data = array();
		$attrs = wc_get_attribute_taxonomies();
		foreach ( $attrs as $item ) {
			if ( $item->attribute_label == $key && $item->attribute_type != 'select' ) {
				break;
			} elseif ( $item->attribute_label == $key ) {
				$name = wc_attribute_taxonomy_name( $item->attribute_name );
				$values = get_terms( $name, array( 'hide_empty' => false ) );
				if ( is_array( $values ) ) {
					$data = array_map( function ( $elem ) {
						return $elem->slug;
					}, $values );
				}
				break;
			}
		}
		sort( $data );
		return $data;
	}

	public static function get_order_meta_values( $type, $key ) {
		global $wpdb;
		$query   = $wpdb->prepare( 'SELECT DISTINCT meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s',array( $type . strtolower( $key ) ) );
		$results = $wpdb->get_col( $query );
		$data    = array_filter( $results );
		sort( $data );
		return $data;
	}


	public static function get_order_product_fields( $format ) {
		$map = array(
			'item_id'     => array( 'label' => __( 'Item ID', 'woocommerce-order-export' ), 'checked' => 0 ),
			'line_id'     => array( 'label' => __( 'Item #', 'woocommerce-order-export' ), 'checked' => 1 ),
			'sku'         => array( 'label' => __( 'SKU', 'woocommerce-order-export' ), 'checked' => 1 ),
			'name'        => array( 'label' => __( 'Name', 'woocommerce-order-export' ), 'checked' => 1 ),
			'product_variation' => array( 'label' => __( 'Product Variation', 'woocommerce-order-export' ), 'checked' => 0 ),
			'seller'      => array( 'label' => __( 'Item Seller', 'woocommerce-order-export' ), 'checked' => 0 ),
			'qty'         => array( 'label' => __( 'Quantity', 'woocommerce-order-export' ), 'checked' => 1 ),
			'qty_minus_refund' => array( 'label' => __( 'Quantity (- Refund)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'item_price'  => array( 'label' => __( 'Item Cost', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'price'       => array( 'label' => __( 'Product Current Price', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_no_tax' => array( 'label' => __( 'Order Line (w/o tax)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_tax'    => array( 'label' => __( 'Order Line Tax', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_tax_refunded'=> array( 'label' => __( 'Order Line Tax Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_tax_minus_refund'=> array( 'label' => __( 'Order Line Tax (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_subtotal'=>array( 'label' => __( 'Order Line Subtotal', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_total'  => array( 'label' => __( 'Order Line Total', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_total_plus_tax'  => array( 'label' => __( 'Order Line Total (include tax)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_total_refunded'  => array( 'label' => __( 'Order Line Total Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'line_total_minus_refund'  => array( 'label' => __( 'Order Line Total (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'discount_amount' => array( 'label' => __( 'Item Discount Amount', 'woocommerce-order-export'), 'checked' => 0, 'format'=>'money' ),
			'tax_rate' => array( 'label' => __( 'Item Tax Rate', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'type'        => array( 'label' => __( 'Type', 'woocommerce-order-export' ), 'checked' => 0 ),
			'category'    => array( 'label' => __( 'Category', 'woocommerce-order-export' ), 'checked' => 0 ),
			'tags'        => array( 'label' => __( 'Tags', 'woocommerce-order-export' ), 'checked' => 0 ),
			'width'       => array( 'label' => __( 'Width', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'length'      => array( 'label' => __( 'Length', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'height'      => array( 'label' => __( 'Height', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'weight'      => array( 'label' => __( 'Weight', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'product_url' => array( 'label' => __( 'Product URL', 'woocommerce-order-export' ), 'checked' => 0 ),
			'download_url' => array( 'label' => __( 'Download URL', 'woocommerce-order-export' ), 'checked' => 0 ),
			'image_url'   => array( 'label' => __( 'Image URL', 'woocommerce-order-export' ), 'checked' => 0 ),
			'product_shipping_class' => array( 'label' => __( 'Product Shipping Class', 'woocommerce-order-export' ), 'checked' => 0 ),
			'post_content'=> array( 'label' => __( 'Description', 'woocommerce-order-export' ), 'checked' => 0 ),
			'post_excerpt'=> array( 'label' => __( 'Short Description', 'woocommerce-order-export' ), 'checked' => 0 ),
			'full_category_names' => array( 'label' => __( 'Full names for categories', 'woocommerce-order-export' ), 'checked' => 0 )
		);

		foreach ( $map as $key => $value ) {
			$map[ $key ]['colname'] = $value['label'];
			$map[ $key ]['default'] = 1;
		}

		return apply_filters( 'woe_get_order_product_fields', $map, $format );
	}

	public static function get_order_coupon_fields( $format ) {
		$map = array(
			'code'                => array( 'label' => __( 'Coupon Code', 'woocommerce-order-export' ), 'checked' => 1 ),
			'discount_amount'     => array( 'label' => __( 'Discount Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'discount_amount_tax' => array( 'label' => __( 'Discount Amount Tax', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'discount_amount_plus_tax' => array( 'label' => __( 'Discount Amount + Tax', 'woocommerce-order-export' ), 'checked' => 0 ),
            'excerpt' => array( 'label' => __( 'Coupon Description', 'woocommerce-order-export' ), 'checked' => 0 ),
			'discount_type' => array( 'label' => __( 'Coupon Type', 'woocommerce-order-export' ), 'checked'=> 0 ),
			'coupon_amount' => array( 'label' => __( 'Coupon Amount', 'woocommerce-order-export' ), 'checked'=> 0, 'format'=>'money' ),
		);

		foreach ( $map as $key => $value ) {
			$map[ $key ]['colname'] = $value['label'];
			$map[ $key ]['default'] = 1;
		}

		return apply_filters( 'woe_get_order_coupon_fields', $map, $format );
	}


	public static function get_order_fields( $format ) {
		$map = array();
		foreach ( array( 'common', 'user', 'billing', 'shipping', 'product', 'coupon', 'cart', 'misc' ) as $segment ) {
			$method      = "get_order_fields_" . $segment;
			$map_segment = self::$method();

			foreach ( $map_segment as $key => $value ) {
				$map_segment[ $key ]['segment'] = $segment;
				$map_segment[ $key ]['colname'] = $value['label'];
				$map_segment[ $key ]['default'] = 1; //debug
			}
			// woe_get_order_fields_common	filter
			$map_segment = apply_filters( "woe_$method", $map_segment, $format );
			$map         = array_merge( $map, $map_segment );
		}

		return apply_filters( 'woe_get_order_fields', $map );
	}

	public static function get_order_fields_common() {
		return array(
			'line_number'       => array( 'label' => __( 'Line number', 'woocommerce-order-export' ), 'checked' => 0 ),
			'order_id'          => array( 'label' => __( 'Order ID', 'woocommerce-order-export' ), 'checked' => 0 ),
			'order_number'      => array( 'label' => __( 'Order Number', 'woocommerce-order-export' ), 'checked' => 1 ),
			'order_status'      => array( 'label' => __( 'Order Status', 'woocommerce-order-export' ), 'checked' => 1 ),
			'order_date'        => array( 'label' => __( 'Order Date', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'date' ),
			'modified_date'     => array( 'label' => __( 'Modification Date', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'date' ),
			'transaction_id'    => array( 'label' => __( 'Transaction ID', 'woocommerce-order-export' ), 'checked' => 0 ),
			'completed_date'    => array( 'label' => __( 'Completed Date', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'date' ),
			'paid_date'         => array( 'label' => __( 'Paid Date', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'date' ),
			'first_refund_date' => array( 'label' => __( 'Date of first refund', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'date' ),
			'customer_note'     => array( 'label' => __( 'Customer Note', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'string' ),
			'order_notes'       => array( 'label' => __( 'Order Notes', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'string' ),
		);
	}

	public static function get_order_fields_user() {
		return array(
			'customer_ip_address' => array( 'label' => __( 'Customer IP address', 'woocommerce-order-export' ), 'checked' => 0 ),
			'customer_user'       => array( 'label' => __( 'Customer User ID', 'woocommerce-order-export' ), 'checked' => 0 ),
			'user_login'          => array( 'label' => __( 'Customer Username', 'woocommerce-order-export' ), 'checked' => 0 ),
			'user_email'          => array( 'label' => __( 'Customer User Email', 'woocommerce-order-export' ), 'checked' => 0 ),
			'user_role'           => array( 'label' => __( 'Customer Role', 'woocommerce-order-export' ), 'checked' => 0 ),
		);
	}

	public static function get_order_fields_billing() {
		return array(
			'billing_first_name'   => array( 'label' => __( 'First Name (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_last_name'    => array( 'label' => __( 'Last Name (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_full_name'    => array( 'label' => __( 'Full Name (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_company'      => array( 'label' => __( 'Company (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_address'      => array( 'label' => __( 'Address 1&2 (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_address_1'    => array( 'label' => __( 'Address 1 (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_address_2'    => array( 'label' => __( 'Address 2 (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_city'         => array( 'label' => __( 'City (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_state'        => array( 'label' => __( 'State Code (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_citystatezip' => array( 'label' => __( 'City, State, Zip (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_state_full'   => array( 'label' => __( 'State Name (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_postcode'     => array( 'label' => __( 'Postcode (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_country'      => array( 'label' => __( 'Country Code (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_country_full' => array( 'label' => __( 'Country Name (Billing)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'billing_email'        => array( 'label' => __( 'Email (Billing)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'billing_phone'        => array( 'label' => __( 'Phone (Billing)', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'string' ),
		);
	}

	public static function get_order_fields_shipping() {
		return array(
			'shipping_first_name'   => array( 'label' => __( 'First Name (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_last_name'    => array( 'label' => __( 'Last Name (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_full_name'    => array( 'label' => __( 'Full Name (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_company'      => array( 'label' => __( 'Company (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_address'      => array( 'label' => __( 'Address 1&2 (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_address_1'    => array( 'label' => __( 'Address 1 (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_address_2'    => array( 'label' => __( 'Address 2 (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_city'         => array( 'label' => __( 'City (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_state'        => array( 'label' => __( 'State Code (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_citystatezip' => array( 'label' => __( 'City, State, Zip (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_state_full'   => array( 'label' => __( 'State Name (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
			'shipping_postcode'     => array( 'label' => __( 'Postcode (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_country'      => array( 'label' => __( 'Country Code (Shipping)', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_country_full' => array( 'label' => __( 'Country Name (Shipping)', 'woocommerce-order-export' ), 'checked' => 0 ),
		);
	}

	// meta
	public static function get_order_fields_product() {
		return array(
			'products' => array( 'label' => __( 'Products', 'woocommerce-order-export' ), 'checked' => 1, 'repeat' => 'rows' , 'max_cols'=>10 ),
		);
	}

	// meta
	public static function get_order_fields_coupon() {
		return array(
			'coupons' => array( 'label' => __( 'Coupons', 'woocommerce-order-export' ), 'checked' => 1, 'repeat' => 'rows' , 'max_cols'=>10 ),
		);
	}

	public static function get_order_fields_cart() {
		return array(
			'shipping_method_title' => array( 'label' => __( 'Shipping Method Title', 'woocommerce-order-export' ), 'checked' => 1 ),
			'shipping_method'		=> array( 'label' => __( 'Shipping Method', 'woocommerce-order-export' ), 'checked' => 0 ),
			'payment_method_title'  => array( 'label' => __( 'Payment Method Title', 'woocommerce-order-export' ), 'checked' => 1 ),
			'payment_method'  		=> array( 'label' => __( 'Payment Method', 'woocommerce-order-export' ), 'checked' => 0 ),
			'coupons_used'          => array( 'label' => __( 'Coupons Used', 'woocommerce-order-export' ), 'checked' => 0 ),
			'cart_discount'         => array( 'label' => __( 'Cart Discount Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'cart_discount_tax'     => array( 'label' => __( 'Cart Discount Amount Tax', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_subtotal'        => array( 'label' => __( 'Order Subtotal Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'order_subtotal_minus_discount' => array( 'label' => 'Order Subtotal - Cart Discount', 'colname' => 'Order Subtotal - Cart Discount', 'checked' => 0 ),
			'order_subtotal_refunded'=> array( 'label' => __( 'Order Subtotal Amount Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_subtotal_minus_refund'=> array( 'label' => __( 'Order Subtotal Amount (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_tax'             => array( 'label' => __( 'Order Tax Amount', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_shipping'        => array( 'label' => __( 'Order Shipping Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'order_shipping_refunded' => array( 'label' => __( 'Order Shipping Amount Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_shipping_minus_refund' => array( 'label' => __( 'Order Shipping Amount (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_shipping_tax'    => array( 'label' => __( 'Order Shipping Tax Amount', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_shipping_tax_refunded' => array( 'label' => __( 'Order Shipping Tax Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_shipping_tax_minus_refund' => array( 'label' => __( 'Order Shipping Tax Amount (- Refund)', 'woocommerce-order-export' ), 'checked' => 0 , 'format'=>'money'),
			'order_refund'          => array( 'label' => __( 'Order Refund Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'order_total_inc_refund'=> array( 'label' => __( 'Order Total Amount (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_total'           => array( 'label' => __( 'Order Total Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'order_total_no_tax'    => array( 'label' => __( 'Order Total Amount without Tax', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_total_tax'       => array( 'label' => __( 'Order Total Tax Amount', 'woocommerce-order-export' ), 'checked' => 1, 'format'=>'money' ),
			'order_total_tax_refunded'    => array( 'label' => __( 'Order Total Tax Amount Refunded', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_total_tax_minus_refund' => array( 'label' => __( 'Order Total Tax Amount (- Refund)', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'money' ),
			'order_currency'        => array( 'label' => __( 'Currency', 'woocommerce-order-export' ), 'checked' => 0 ),
		);
	}

	public static function get_order_fields_misc() {
		return array(
			'total_weight_items'    => array( 'label' => __( 'Total weight', 'woocommerce-order-export' ), 'checked' => 0, 'format'=>'number' ),
			'count_total_items'     => array( 'label' => __( 'Total items', 'woocommerce-order-export' ), 'checked' => 0 ),
			'count_unique_products' => array( 'label' => __( 'Total products', 'woocommerce-order-export' ), 'checked' => 0 ),
		);
	}

	// for UI only
	public static function get_visible_segments( $fields ) {
		$sections = array();
		foreach ( $fields as $field ) {
			if ( $field['checked'] ) {
				$sections[ $field['segment'] ] = 1;
			}
		}

		return array_keys( $sections );
	}

	public static function get_order_segments() {
		return array(
			'common'   => __( 'Common', 'woocommerce-order-export' ),
			'user'     => __( 'User', 'woocommerce-order-export' ),
			'billing'  => __( 'Billing', 'woocommerce-order-export' ),
			'shipping' => __( 'Shipping', 'woocommerce-order-export' ),
			'product'  => __( 'Products', 'woocommerce-order-export' ),
			'coupon'   => __( 'Coupons', 'woocommerce-order-export' ),
			'cart'     => __( 'Cart', 'woocommerce-order-export'),
			'misc'     => __( 'Others', 'woocommerce-order-export' )
		);
	}


}