<?php
defined( 'ABSPATH' ) || exit;

class WFACP_public {
	public static $is_checkout = null;
	private static $ins = null;
	public $page_id = 0;
	public $added_products = [];
	public $products_in_cart = [];
	public $applied_coupon_in_cart = '';
	public $product_settings = [];
	public $variable_product = false;
	public $is_hide_qty = false;
	public $is_checkout_override = false;
	public $billing_details = false;
	public $paypal_billing_address = false;
	public $paypal_shipping_address = false;
	public $shipping_details = false;
	public $is_paypal_express_active_session = false;
	public $is_amazon_express_active_session = false;
	protected $products = [];
	protected $settings = [];
	protected $image_src = [];
	protected $is_cart_virtual = false;
	protected $already_discount_apply = [];
	protected $products_count = [];
	protected $add_to_cart_via_url = false;
	private $have_product = false;

	protected function __construct() {

		add_action( 'wfacp_changed_default_woocommerce_page', [ $this, 'wfacp_changed_default_woocommerce_page' ] );
		/**
		 * We only process checkout page data if header is valid
		 * @since 1.6.0
		 */
		if ( $this->check_valid_header_of_page() ) {
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'check_advanced_setting' ], 0 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'maybe_pass_no_cache_header' ], 0 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'get_page_data' ], 1 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'add_to_cart' ], 2 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'apply_matched_coupons' ], 3 );
			add_action( 'wfacp_after_checkout_page_found', [ $this, 'other_hooks' ] );
		}


		add_action( 'wfacp_before_add_to_cart', [ $this, 'best_value_via_url' ] );
		add_action( 'wfacp_before_add_to_cart', [ $this, 'add_to_cart_via_url' ] );
		add_action( 'wfacp_before_add_to_cart', [ $this, 'default_value_via_url' ] );
		add_action( 'wfacp_before_add_to_cart', [ $this, 'wfacp_before_add_to_cart' ] );
		add_action( 'wfacp_after_add_to_cart', [ $this, 'wfacp_after_add_to_cart' ] );

		add_action( 'woocommerce_before_calculate_totals', [ $this, 'calculate_totals' ], 1 );
		add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'calculate_totals' ], 2 );
		add_action( 'woocommerce_before_cart', [ $this, 'apply_matched_coupons' ] );
		add_filter( 'woocommerce_order_item_quantity_html', [ $this, 'change_woocommerce_checkout_cart_item_quantity' ], 999, 2 );
		add_filter( 'woocommerce_email_order_item_quantity', [ $this, 'change_woocommerce_email_quantity' ], 999, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_meta_cart_data' ], 10, 4 );
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', [ $this, 'hide_out_meta_data' ], 10, 4 );
		add_filter( 'woocommerce_coupon_message', [ $this, 'hide_coupon_msg' ], 959 );
		add_filter( 'woocommerce_get_checkout_url', [ $this, 'woocommerce_get_checkout_url' ], 99999 );
		add_action( 'woocommerce_checkout_process', [ $this, 'set_session_when_place_order_btn_pressed' ], - 1 );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'reset_session_when_order_processed' ] );
		add_action( 'woocommerce_checkout_update_user_meta', [ $this, 'woocommerce_checkout_process' ] );
		//      add_action( 'woocommerce_cart_item_subtotal', [ $this, 'display_proper_subtotal' ], 10, 2 );
		add_action( 'woocommerce_applied_coupon', [ $this, 'set_session_when_coupon_applied' ] );
		add_action( 'woocommerce_removed_coupon', [ $this, 'reset_session_when_coupon_removed' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'global_script' ] );
		add_filter( 'wfacp_form_section', [ $this, 'remove_shipping_method' ], 10, 3 );
		add_filter( 'wfacp_hide_section', [ $this, 'skip_empty_section' ], 10, 2 );

		/**
		 * @since 1.6.0
		 */
		if ( apply_filters( 'wfacp_remove_persistent_cart_after_merging', true ) ) {
			/**
			 * We store the cart items into session when user is not logged in
			 * after logged in we restore the stored cart for preventing the persistent cart issue in woocommerce             *
			 **/
			add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'save_wfacp_session' ], 99 );
			add_filter( 'woocommerce_cart_contents_changed', [ $this, 'set_save_session' ], 99 );
		}

		add_action( 'wfacp_after_checkout_page_found', [ $this, 'remove_canonical_link' ], 99 );
		add_action( 'woocommerce_thankyou', [ $this, 'reset_our_localstorage' ] );

		add_action( 'woocommerce_cart_is_empty', [ $this, 'woocommerce_cart_is_empty' ] );

		add_filter( 'woocommerce_order_item_name', [ $this, 'change_item_name' ], 9, 2 );
		add_filter( 'woocommerce_cart_item_name', [ $this, 'change_item_name' ], 9, 2 );

		add_filter( 'woocommerce_before_order_itemmeta', [ $this, 'change_order_item_name_edit_screen' ], 9, 2 );
		add_filter( 'woocommerce_email_order_items_args', [ $this, 'disabled_show_sku' ], 9, 2 );

		add_filter( 'wfacp_default_product', [ $this, 'merge_default_product' ], 10, 3 );
		add_action( 'wfacp_page_is_cached', [ $this, 'wfacp_page_is_cached' ] );

		/**
		 * Change woocommerce ajax endpoint only for our checkout pages only
		 * not for every page
		 *
		 */
		add_action( 'wfacp_after_checkout_page_found', function () {
			add_filter( 'woocommerce_ajax_get_endpoint', [ $this, 'woocommerce_ajax_get_endpoint' ], 0, 2 );
		} );


		add_action( 'woocommerce_add_to_cart_sold_individually_found_in_cart', [ $this, 'restrict_sold_individual' ], 10, 2 );
	}

	/**
	 * Check valid header of the page (Text/Html)
	 * We only process text/html header
	 * If client enqueue script like this /wfacp_age/?script=frontend
	 * then we not process this call for our checkout page
	 * This issue occur with Oxygen Builder
	 * @return bool
	 * @since 1.6.0
	 *
	 */
	public function check_valid_header_of_page() {

		if ( wp_doing_ajax() ) {
			return true;
		}

		if ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( $_SERVER['HTTP_ACCEPT'], 'text/html' ) ) {
			return true;
		}

		return false;

	}

	public static function get_instance() {
		if ( is_null( self::$ins ) ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function check_advanced_setting( $page_id ) {

		if ( $this->is_checkout_override ) {

			return;
		}
		$this->settings               = WFACP_Common::get_page_settings( $page_id );
		$close_checkout_redirect_url  = ( '' != $this->settings['close_checkout_redirect_url'] ) ? $this->settings['close_checkout_redirect_url'] : home_url();
		$total_purchased_redirect_url = ( '' != $this->settings['total_purchased_redirect_url'] ) ? $this->settings['total_purchased_redirect_url'] : home_url();

		do_action( 'wfacp_before_checking_advanced_settings', $this->settings, $this );

		if ( wc_string_to_bool( $this->settings['close_after_x_purchase'] ) ) {
			if ( '' !== $this->settings['total_purchased_allowed'] && $this->settings['total_purchased_allowed'] > 0 ) {
				global $wpdb;
				$result = $wpdb->get_results( "SELECT count(*) as c FROM `{$wpdb->prefix}postmeta` WHERE `meta_key`= '_wfacp_post_id' and meta_value='{$page_id}';", ARRAY_A );
				if ( count( $result ) > 0 && isset( $result [0]['c'] ) ) {
					$total_purchased = absint( $result [0]['c'] );
					if ( $total_purchased > 0 && $total_purchased >= $this->settings['total_purchased_allowed'] ) {
						wp_redirect( $total_purchased_redirect_url );
						exit;
					}
				}
			}
		}
		if ( wc_string_to_bool( $this->settings['close_checkout_after_date'] ) ) {

			if ( '' !== $this->settings['close_checkout_on'] && time() > strtotime( $this->settings['close_checkout_on'] ) ) {
				wp_redirect( $close_checkout_redirect_url );
				exit;
			}
		}
		do_action( 'wfacp_after_checking_advanced_settings', $this->settings, $this );
	}

	public function wfacp_changed_default_woocommerce_page() {
		WC()->session->set( 'removed_cart_contents', [] );
		$this->is_checkout_override = true;

	}

	public function other_hooks() {
		add_action( 'wfacp_header_print_in_head', [ $this, 'wp_footer' ] );
	}

	public function wfacp_before_add_to_cart() {
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'split_product_individual_cart_items' ), 10, 1 );
	}

	public function wfacp_after_add_to_cart() {
		remove_filter( 'woocommerce_add_cart_item_data', array( $this, 'split_product_individual_cart_items' ), 10 );
	}

	public function get_page_data( $page_id ) {

		$this->products         = WFACP_Common::get_page_product( $page_id );
		$this->products_count   = ! empty( $this->products ) ? count( $this->products ) : 0;
		$this->product_settings = WFACP_Common::get_page_product_settings( $page_id );
		$this->settings         = WFACP_Common::get_page_settings( $page_id );

		WFACP_Common::pc( 'WFACP global settings' );
		WFACP_Common::pc( $this->settings );
		WFACP_Common::pc( 'Product settings ' );
		WFACP_Common::pc( $this->product_settings );

	}

	public function get_settings() {
		return $this->settings;
	}

	public function get_product_list() {
		return $this->products;
	}

	public function get_product_settings() {
		return $this->product_settings;
	}

	/**
	 * add to cart product after checkout page is found
	 * checkout page id
	 *
	 * @param $page_id
	 */
	public function add_to_cart( $page_id ) {
		do_action( 'wfacp_add_to_cart_init', $this );

		if ( isset( $_GET['cancel_order'] ) ) {
			return;
		}
		if ( WFACP_Common::is_customizer() && false == WC()->cart->is_empty() && 0 == $this->get_product_count() ) {
			return;
		}

		$wfacp_woocommerce_applied_coupon = WC()->session->get( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id(), [] );

		if ( $page_id > 0 && isset( $wfacp_woocommerce_applied_coupon[ $page_id ] ) ) {
			return;
		} else {
			WC()->session->set( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id(), [] );
		}
		if ( ! is_super_admin() ) {

			$wfacp_checkout_processed = WC()->session->get( 'wfacp_checkout_processed_' . WFACP_Common::get_Id() );
			if ( isset( $wfacp_checkout_processed ) ) {

				$session_return         = false;
				$add_checkout_parameter = $this->aero_add_to_checkout_parameter();

				if ( isset( $_GET[ $add_checkout_parameter ] ) && '' != $_GET[ $add_checkout_parameter ] ) {
					$session_aero_add_to_checkout_parameter = WC()->session->get( 'aero_add_to_checkout_parameter_' . WFACP_Common::get_Id(), false );
					if ( true !== $session_aero_add_to_checkout_parameter && $session_aero_add_to_checkout_parameter == $_GET[ $add_checkout_parameter ] ) {
						$session_return = true;
					}
				} else {
					$session_return = true;
				}
				if ( $session_return ) {
					$this->merge_session_product_with_actual_product();

					return;
				}
			}
		}
		// for third party system
		if ( apply_filters( 'wfacp_skip_add_to_cart', false, $this ) ) {
			return;
		}

		if ( $this->is_checkout_override ) {
			if ( WC()->cart->is_empty() ) {
				// case of default checkout and no cart is empty then i  redirect to cart native way
				wp_redirect( get_the_permalink( wc_get_page_id( 'cart' ) ) );
				exit;
			}
			WC()->session->set( 'wfacp_id', WFACP_Common::get_id() );
			WC()->session->set( 'wfacp_is_override_checkout', WFACP_Common::get_id() );

			return;
		} else {
			if ( ! wp_doing_ajax() ) {
				WC()->session->set( 'wfacp_is_override_checkout', 0 );
			}
		}
		if ( isset( $_REQUEST['wc-ajax'] ) ) {
			return;
		}
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return;
		}

		if ( ! is_array( $this->products ) || $this->get_product_count() == 0 ) {
			// case of no product found in our checkout page now i redirect to cart page

			wp_redirect( get_the_permalink( wc_get_page_id( 'cart' ) ) );
			exit;
		}

		WC()->cart->empty_cart();

		$this->push_product_to_cart();
	}

	/**
	 * @since 1.5.2
	 */
	public function merge_session_product_with_actual_product() {
		$session_products = WC()->session->get( 'wfacp_product_data_' . WFACP_Common::get_id(), [] );
		if ( ! empty( $session_products ) && ! empty( $this->products ) ) {

			$merge_session_product = [];
			foreach ( $session_products as $pkey => $session_product ) {
				if ( ! isset( $this->products[ $pkey ] ) ) {
					continue;
				}
				if ( isset( $session_product['is_added_cart'] ) ) {
					$merge_session_product[ $pkey ] = $session_product;
				} else {
					$merge_session_product[ $pkey ]                 = $this->products[ $pkey ];
					$merge_session_product[ $pkey ]['org_quantity'] = $this->products[ $pkey ]['quantity'];
				}
			}

			if ( ! empty( $merge_session_product ) ) {
				WC()->session->set( 'wfacp_id', WFACP_Common::get_id() );
				WC()->session->set( 'wfacp_product_data_' . WFACP_Common::get_id(), $merge_session_product );
			}
		}
	}

	public function apply_matched_coupons() {
		if ( WFACP_Common::is_customizer() ) {
			return;
		}
		if ( isset( $this->settings['enable_coupon'] ) && 'true' === $this->settings['enable_coupon'] && isset( $this->settings['coupons'] ) && $this->settings['coupons'] != '' ) {
			$coupon_id = $this->settings['coupons'];

			WFACP_Common::pc( 'Wfacp Coupon Applied coupon is' . $coupon_id );
			WC()->cart->add_discount( $coupon_id );
		}
	}

	public function default_value_via_url() {
		if ( wp_doing_ajax() ) {
			return;
		}
		$default_value_parameter = $this->aero_default_value_parameter();
		if ( isset( $_GET[ $default_value_parameter ] ) && '' != $_GET[ $default_value_parameter ] ) {
			$best_value = $_GET[ $default_value_parameter ];
			WC()->session->set( 'wfacp_product_default_value_parameter_' . WFACP_Common::get_id(), $best_value );
		} else {
			WC()->session->set( 'wfacp_product_default_value_parameter_' . WFACP_Common::get_id(), '' );
		}
	}

	public function best_value_via_url() {
		if ( wp_doing_ajax() ) {
			return;
		}
		$best_value_parameter = $this->aero_best_value_parameter();
		if ( isset( $_GET[ $best_value_parameter ] ) && '' != $_GET[ $best_value_parameter ] ) {
			$best_value = $_GET[ $best_value_parameter ];
			WC()->session->set( 'wfacp_product_best_value_by_parameter_' . WFACP_Common::get_id(), $best_value );
		} else {
			WC()->session->set( 'wfacp_product_best_value_by_parameter_' . WFACP_Common::get_id(), '' );
		}
	}

	public function add_to_cart_via_url() {

		$add_checkout_parameter = $this->aero_add_to_checkout_parameter();

		if ( isset( $_GET[ $add_checkout_parameter ] ) && '' != $_GET[ $add_checkout_parameter ] ) {

			$this->add_to_cart_via_url = true;
			WC()->session->set( 'aero_add_to_checkout_parameter_' . WFACP_Common::get_Id(), $_GET[ $add_checkout_parameter ] );
			$products     = explode( ',', $_GET[ $add_checkout_parameter ] );
			$products_qty = [];

			$quantity_parameter = $this->aero_add_to_checkout_product_quantity_parameter();

			if ( isset( $_GET[ $quantity_parameter ] ) ) {
				$products_qty = explode( ',', $_GET[ $quantity_parameter ] );
			}

			if ( is_array( $products ) && count( $products ) > 0 ) {
				$new_products = [];
				foreach ( $products as $pid_index => $pid ) {
					$unique_id     = uniqid( 'wfacp_' );
					$existing_data = $this->find_existing_match_product( $pid );
					if ( ! is_null( $existing_data ) ) {
						$existing_data['data']['whats_included']      = '';
						$existing_data['data']['org_quantity']        = ( isset( $products_qty[ $pid_index ] ) && $products_qty[ $pid_index ] > 0 ) ? ( absint( $products_qty[ $pid_index ] ) ) : 1;
						$existing_data['data']['add_to_cart_via_url'] = true;
						$new_products[ $existing_data['key'] ]        = $existing_data['data'];

						continue;
					}
					$product = wc_get_product( $pid );
					if ( $product instanceof WC_Product ) {
						$product_type                 = $product->get_type();
						$image_id                     = $product->get_image_id();
						$default                      = WFACP_Common::get_default_product_config();
						$default['image']             = wp_get_attachment_image_src( $image_id )[0];
						$default['type']              = $product_type;
						$default['id']                = $product->get_id();
						$default['parent_product_id'] = $product->get_parent_id();
						$default['title']             = $product->get_title();

						$default['org_quantity'] = ( isset( $products_qty[ $pid_index ] ) && $products_qty[ $pid_index ] > 0 ) ? ( absint( $products_qty[ $pid_index ] ) ) : 1;

						if ( 'variable' === $product_type ) {
							$default['variable'] = 'yes';
							$default['price']    = $product->get_price_html();
						} else {
							$row_data                 = $product->get_data();
							$sale_price               = $row_data['sale_price'];
							$default['price']         = wc_price( $row_data['price'] );
							$default['regular_price'] = wc_price( $row_data['regular_price'] );
							if ( '' != $sale_price ) {
								$default['sale_price'] = wc_price( $sale_price );
							}
						}
						$default                        = WFACP_Common::remove_product_keys( $default );
						$default['add_to_cart_via_url'] = true;
						$default['whats_included']      = '';
						$new_products[ $unique_id ]     = $default;

					}
				}

				if ( count( $new_products ) > 0 ) {
					$this->products = $new_products;
				}
			}
		}
	}

	private function find_existing_match_product( $pid ) {
		foreach ( $this->products as $index => $data ) {
			if ( $pid == $data['id'] ) {
				return array(
					'key'  => $index,
					'data' => $data,
				);
			}
		}

		return null;
	}

	public function split_product_individual_cart_items( $cart_item_data ) {
		$cart_item_data['unique_key'] = uniqid();

		return $cart_item_data;
	}

	/**
	 * @param $ins WC_Cart
	 */
	public function calculate_totals( $ins ) {

		if ( WFACP_Common::get_id() == 0 ) {
			return;
		}
		$cart_content = $ins->get_cart_contents();

		if ( count( $cart_content ) > 0 ) {
			foreach ( $cart_content as $key => $item ) {
				if ( isset( $item['_wfacp_product'] ) ) {
					$item                       = $this->modify_calculate_price_per_session( $item );
					$item                       = apply_filters( 'wfacp_after_discount_added_to_item', $item );
					$ins->cart_contents[ $key ] = $item;
				}
			}
		}
	}

	/**
	 * Apply discount on basis of input for product raw prices
	 *
	 * @param $item WC_cart;
	 *
	 * @return mixed
	 */

	public function modify_calculate_price_per_session( $item ) {
		if ( ! isset( $item['_wfacp_product'] ) ) {
			return $item;
		}
		if ( isset( $item['_wfacp_options']['add_to_cart_via_url'] ) ) {
			return $item;
		}

		/**
		 * @var $product WC_product;
		 */
		$product  = $item['data'];
		$raw_data = $product->get_data();

		$raw_data = apply_filters( 'wfacp_product_raw_data', $raw_data, $product );

		$regular_price   = apply_filters( 'wfacp_discount_regular_price_data', $raw_data['regular_price'] );
		$price           = apply_filters( 'wfacp_discount_price_data', $raw_data['price'] );
		$discount_amount = apply_filters( 'wfacp_discount_amount_data', $item['_wfacp_options']['discount_amount'], $item['_wfacp_options']['discount_type'] );

		$discount_data = [
			'wfacp_product_rp'      => $regular_price,
			'wfacp_product_p'       => $price,
			'wfacp_discount_amount' => $discount_amount,
			'wfacp_discount_type'   => $item['_wfacp_options']['discount_type'],
		];

		WFACP_Common::pc( 'Discount apply started' );
		$new_price = WFACP_Common::calculate_discount( $discount_data );
		WFACP_Common::pc( $discount_data );
		WFACP_Common::pc( 'Calculated discount is ' . $new_price );
		$this->already_discount_apply[ $item['_wfacp_product_key'] ] = true;
		if ( is_null( $new_price ) ) {
			return $item;
		} else {

			$item['data']->set_regular_price( $regular_price );
			$item['data']->set_price( $new_price );
			$item['data']->set_sale_price( $new_price );

		}

		return $item;
	}

	/**
	 *
	 * @param $cart WC_Cart;
	 */
	public function save_wfacp_session( $cart ) {
		if ( ! is_user_logged_in() ) {
			$cart_content = $cart->get_cart_contents();
			if ( ! empty( $cart_content ) ) {
				WC()->session->set( 'wfacp_sustain_cart_content_' . WFACP_Common::get_Id(), $cart_content );
			}
		}

	}

	public function global_script() {
		if ( WFACP_Common::is_customizer() ) {
			add_filter( 'woocommerce_checkout_show_terms', function () {
				return false;
			} );
		}
	}

	/**
	 *
	 * @param $item_data WC_Order_Item
	 *
	 * @return String
	 */
	public function change_item_name( $item_name, $item_data ) {


		if ( $this->is_checkout_override() ) {
			return $item_name;
		}
		if ( ! isset( $item_data['_wfacp_product'] ) ) {
			return $item_name;
		}

		if ( $item_data instanceof WC_Order_Item_Product ) {
			$data     = $item_data->get_data();
			$order_id = $data['order_id'];
			$aero_id  = get_post_meta( $order_id, '_wfacp_post_id', true );
			$aero_id  = absint( $aero_id );
			WFACP_Common::set_id( $aero_id );
			$switcher_settings = WFACP_Common::get_product_switcher_data( $aero_id );

		} else {
			$switcher_settings = WFACP_Common::get_product_switcher_data( WFACP_Common::get_id() );
		}
		if ( empty( $switcher_settings ) ) {
			return $item_name;
		}

		if ( isset( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) && true !== wc_string_to_bool( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) ) {

			return $item_name;

		}

		$temp_item_name = $item_data['_wfacp_options']['title'];
		if ( '' == $temp_item_name ) {
			return $item_name;
		}

		if ( isset( $item_data['variation_id'] ) && $item_data['variation_id'] > 0 ) {
			$item_name = strip_tags( $item_name );
			$position  = strpos( $item_name, '-' );
			if ( false !== $position ) {
				$substr = trim( substr( $item_name, $position, strlen( $item_name ) ) );
				if ( apply_filters( 'wfacp_variation_order_summary_attributes', true, $substr, $temp_item_name ) ) {
					if ( '' !== $substr && false == stripos( $temp_item_name, $substr ) ) {
						return $temp_item_name . $substr;
					}
				}

			}
		}

		return $temp_item_name;
	}


	public function change_order_item_name_edit_screen( $item_id, $item ) {
		global $post;
		if ( is_null( $post ) ) {
			return '';
		}

		if ( ! isset( $item['_wfacp_options'] ) || '' == $item['_wfacp_options']['title'] ) {
			return '';
		}
		$data     = $item->get_data();
		$order_id = $data['order_id'];
		$aero_id  = get_post_meta( $order_id, '_wfacp_post_id', true );
		$aero_id  = absint( $aero_id );
		WFACP_Common::set_id( $aero_id );
		$switcher_settings = WFACP_Common::get_product_switcher_data( $aero_id );
		if ( empty( $switcher_settings ) ) {
			return '';
		}

		if ( isset( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) && true === wc_string_to_bool( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) ) {
			$item_name_is = $item['_wfacp_options']['title'];
			if ( isset( $item['_wfacp_options']['old_title'] ) && $item['_wfacp_options']['title'] !== $item['_wfacp_options']['old_title'] ) {
				echo '<div class="wc-order-item-sku"><strong>Aero Custom Title: </strong><span>' . $item_name_is . '</span></div>';
			}
		}


	}


	public function disabled_show_sku( $args ) {
		$args['show_sku'] = false;

		return $args;
	}

	public function get_image_src( $image_id, $size = 'full' ) {

		if ( isset( $this->image_src[ $image_id ][ $size ] ) && ! empty( $this->image_src[ $image_id ][ $size ] ) ) {
			return $this->image_src[ $image_id ][ $size ];
		} else {
			if ( $image_id == '' ) {
				return;
			}
			$img_src_arr = wp_get_attachment_image_src( $image_id, $size );
			$img_src     = $img_src_arr[0];
			if ( ! isset( $this->image_src[ $image_id ][ $size ] ) ) {
				$this->image_src[ $image_id ][ $size ] = $img_src;
			}

			return $img_src;
		}
	}

	/**
	 * @var $cart_item WC_Order_Item
	 * this function for using hiding quantity in order review
	 */
	public function change_woocommerce_checkout_cart_item_quantity( $text, $cart_item ) {

		if ( isset( $cart_item['wfacp_product'] ) ) {
			if ( $this->is_hide_qty || isset( $cart_item['wfacp_hide_quantity'] ) ) {
				return '';
			}

			$data              = $cart_item->get_data();
			$order_id          = $data['order_id'];
			$aero_id           = get_post_meta( $order_id, '_wfacp_post_id', true );
			$aero_id           = absint( $aero_id );
			$wfacp_options     = $cart_item['_wfacp_options'];
			$switcher_settings = WFACP_Common::get_product_switcher_data( $aero_id );
			if ( isset( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) && wc_string_to_bool( $switcher_settings['settings']['enable_custom_name_in_order_summary'] ) && $wfacp_options['title'] !== $wfacp_options['old_title'] ) {
				$wfacp_qty = absint( $wfacp_options['org_quantity'] );
				$cart_qty  = absint( $cart_item['quantity'] );
				if ( $wfacp_qty > 0 && $cart_qty > 0 ) {
					return ' <strong class="product-quantity">' . sprintf( '&times; %s', ( $cart_qty / $wfacp_qty ) ) . '</strong>';
				}
			}
		}

		return $text;
	}

	public function change_woocommerce_email_quantity( $quantity, $cart_item ) {
		if ( isset( $cart_item['wfacp_product'] ) ) {
			if ( $this->is_hide_qty || isset( $cart_item['wfacp_hide_quantity'] ) ) {
				return '';
			}
			$wfacp_options = $cart_item['_wfacp_options'];
			$wfacp_qty     = absint( $wfacp_options['quantity'] );
			$cart_qty      = absint( $cart_item['org_quantity'] );
			if ( $wfacp_qty > 0 && $cart_qty > 0 ) {
				return ( $cart_qty / $wfacp_qty );
			}
		}

		return $quantity;
	}

	/**
	 * @param $item WC_Order_Item
	 * @param $cart_item_key String
	 * @param $values Object
	 * @param $order WC_Order
	 */
	public function save_meta_cart_data( $item, $cart_item_key, $values, $order ) {
		if ( $order instanceof WC_Order && ! empty( $values ) ) {
			foreach ( $values as $key => $value ) {
				if ( false !== strpos( $key, 'wfacp_' ) ) {
					$item->add_meta_data( $key, $value );
				}
			}
		}

		if ( $this->is_hide_qty ) {
			$item->add_meta_data( 'wfacp_hide_quantity', 1 );
		}

	}

	/**
	 * @param $formatted_meta Array
	 * @param $instance WC_Order_Item
	 */

	public function hide_out_meta_data( $formatted_meta, $instance ) {
		if ( $instance instanceof WC_Order_Item && ! empty( $formatted_meta ) ) {
			foreach ( $formatted_meta as $key => $value ) {
				if ( false !== strpos( $value->key, 'wfacp_' ) && apply_filters( 'wfacp_hide_out_meta_data', true, $key, $value ) ) {
					unset( $formatted_meta[ $key ] );
				}
			}
		}

		return $formatted_meta;
	}

	public function hide_coupon_msg( $msg ) {

		if ( isset( $this->settings['disable_coupon'] ) && 'true' === $this->settings['disable_coupon'] ) {
			$msg = '';
		}

		return $msg;

	}

	public function woocommerce_template_single_add_to_cart() {
		global $product;

		do_action( 'wfacp_woocommerce_' . $product->get_type() . '_add_to_cart' );
	}

	public function woocommerce_variable_add_to_cart() {
		global $product;

		// Enqueue variation scripts.

		// Get Available variations?
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

		$available_variations = $get_variations ? $product->get_available_variations() : false;
		$attributes           = $product->get_variation_attributes();
		$selected_attributes  = $product->get_default_attributes();

		include WFACP_TEMPLATE_COMMON . '/quick-view/add-to-cart/variable.php';
	}

	public function woocommerce_variable_subscription_add_to_cart() {
		global $product;

		// Enqueue variation scripts.

		// Get Available variations?
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

		$available_variations = $get_variations ? $product->get_available_variations() : false;
		$attributes           = $product->get_variation_attributes();
		$selected_attributes  = $product->get_default_attributes();

		include WFACP_TEMPLATE_COMMON . '/quick-view/add-to-cart/variable-subscription.php';
	}

	public function woocommerce_simple_add_to_cart() {
		include WFACP_TEMPLATE_COMMON . '/quick-view/add-to-cart/simple.php';

	}

	public function woocommerce_subscription_add_to_cart() {
		include WFACP_TEMPLATE_COMMON . '/quick-view/add-to-cart/subscription.php';
	}

	public function woocommerce_single_variation_add_to_cart_button() {
		include WFACP_TEMPLATE_COMMON . '/quick-view/add-to-cart/variation-add-to-cart-button.php';
	}

	public function is_checkout_override() {
		if ( is_null( WC()->session ) ) {
			return $this->is_checkout_override;
		}

		$wfacp_is_override_checkout = WC()->session->get( 'wfacp_is_override_checkout', 0 );

		if ( $wfacp_is_override_checkout > 0 ) {
			$this->is_checkout_override = true;
		}

		if ( isset( $_REQUEST['wfacp_is_checkout_override'] ) && 'yes' == $_REQUEST['wfacp_is_checkout_override'] ) {
			$this->is_checkout_override = true;
		}

		if ( isset( $_REQUEST['wfacp_is_checkout_override'] ) && 'no' == $_REQUEST['wfacp_is_checkout_override'] ) {
			$this->is_checkout_override = false;
		}


		return $this->is_checkout_override;
	}

	public function wp_footer() {

		include( WFACP_TEMPLATE_COMMON . '/quick-view/quick-view-container.php' );
	}


	public function woocommerce_ajax_get_endpoint( $url, $request ) {
		if ( WFACP_Common::get_id() > 0 ) {
			$query = [
				'wfacp_id'                   => WFACP_Common::get_id(),
				'wfacp_is_checkout_override' => ( $this->is_checkout_override ) ? 'yes' : 'no',
			];
			if ( isset( $_REQUEST['currency'] ) ) {
				$query['currency'] = $_REQUEST['currency'];
			}
			if ( isset( $_REQUEST['lang'] ) ) {
				$query['lang'] = $_REQUEST['lang'];
			}
			$query['wc-ajax'] = $request;
			$url              = add_query_arg( $query, $url );
		}

		return $url;
	}

	public function unset_wcct_campaign( $status, $instance ) {

		if ( $this->get_product_count() > 0 ) {

			foreach ( $this->products as $index => $data ) {
				$product_id = absint( $data['id'] );
				if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
					$product_id = absint( $data['parent_product_id'] );
				}
				unset( $instance->single_campaign[ $product_id ] );
				$status = false;
			}
		}

		return $status;

	}

	public function maybe_pass_no_cache_header() {

		$this->set_nocache_constants();
		nocache_headers();

	}

	/**
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set_nocache_constants() {

		$this->maybe_define_constant( 'DONOTCACHEPAGE', true );
		$this->maybe_define_constant( 'DONOTCACHEOBJECT', true );
		$this->maybe_define_constant( 'DONOTCACHEDB', true );

		return null;
	}

	function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	public function woocommerce_template_single_excerpt() {

		global $product;
		$type = $product->get_type();
		if ( in_array( $type, WFACP_Common::get_variable_product_type() ) ) {
			return '';
		}
		if ( in_array( $type, WFACP_Common::get_variation_product_type() ) ) {
			return '';
		}


		include WFACP_TEMPLATE_COMMON . '/quick-view/short-description.php';
	}

	public function woocommerce_get_checkout_url( $url ) {
		$id = WFACP_Common::get_id();
		if ( $id > 0 ) {
			$posts  = get_post( $id );
			$loader = WFACP_Core()->template_loader;
			if ( ! is_null( $posts ) && $posts->post_status == 'publish' && $loader->is_valid_state_for_data_setup() ) {
				$override_checkout_page_id = WFACP_Common::get_checkout_page_id();
				if ( $override_checkout_page_id !== $id ) {
					return get_the_permalink( $id );
				}
			}
		}

		return $url;
	}

	public function remove_shipping_method( $section, $section_index, $step ) {

		if ( ! is_array( $section ) || count( $section ) == 0 || ! isset( $section['fields'] ) || count( $section['fields'] ) == 0 ) {
			return $section;
		}
		$shipping_calculator_index = false;

		foreach ( $section['fields'] as $index => $field ) {
			if ( isset( $field['id'] ) && 'shipping_calculator' == $field['id'] ) {
				$shipping_calculator_index = $index;
				break;
			}
		}

		if ( false !== $shipping_calculator_index ) {

			WC()->session->set( 'wfacp_shipping_method_parent_fields_count_' . WFACP_Common::get_id(), [
				'count' => count( $section['fields'] ),
				'index' => $section_index,
				'step'  => $step,
			] );
		}

		return $section;
	}

	public function skip_empty_section( $status, $section ) {
		if ( ! is_array( $section ) || count( $section ) == 0 || ! isset( $section['fields'] ) || count( $section['fields'] ) == 0 ) {
			return true;
		}

		return $status;
	}

	public function set_session_when_place_order_btn_pressed() {
		WC()->session->set( 'wfacp_checkout_processed_' . WFACP_Common::get_Id(), true );
		if ( ! empty( $_POST ) && isset( $_POST['_wfacp_post_id'] ) ) {
			WC()->session->set( 'wfacp_posted_data', $_POST );
		}
	}

	public function reset_session_when_order_processed() {
		$checkout_id = WFACP_Common::get_Id();
		WC()->session->__unset( 'wfacp_checkout_processed_' . $checkout_id );
		WC()->session->__unset( 'aero_add_to_checkout_parameter_' . $checkout_id );
		WC()->session->__unset( 'wfacp_cart_hash' );
		WC()->session->__unset( 'wfacp_product_objects_' . $checkout_id );
		WC()->session->__unset( 'wfacp_product_data_' . $checkout_id );
		WC()->session->__unset( 'wfacp_is_override_checkout' );
		WC()->session->__unset( 'wfacp_product_best_value_by_parameter_' . $checkout_id );
		WC()->session->__unset( 'wfacp_sustain_cart_content_' . $checkout_id );
		WC()->session->__unset( 'removed_cart_contents' );
		WC()->session->__unset( 'wfacp_woocommerce_applied_coupon_' . $checkout_id );
	}

	public function set_session_when_coupon_applied() {

		$c = WC()->session->get( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id(), [] );
		if ( isset( $_REQUEST['wfacp_id'] ) ) {
			$id       = absint( $_REQUEST['wfacp_id'] );
			$c[ $id ] = true;
		}
		WC()->session->set( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id(), $c );
	}

	public function reset_session_when_coupon_removed() {
		WC()->session->__unset( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id() );
	}

	/**
	 * validate cart hash of multiple checkout page when open in same browser
	 * Make sure latest open checkout page is open
	 */
	public function woocommerce_checkout_process() {

		if ( isset( $_POST['wfacp_has_active_multi_checkout'] ) && $_POST['wfacp_has_active_multi_checkout'] != 'no' ) {
			return;
		}
		if ( isset( $_POST['wfacp_cart_hash'] ) && '' !== $_POST['wfacp_cart_hash'] ) {
			$form_cart_hash = trim( $_POST['wfacp_cart_hash'] );
			$cart_hash      = trim( WC()->session->get( 'wfacp_cart_hash', '' ) );
			if ( '' !== $cart_hash && ( $form_cart_hash !== $cart_hash ) ) {
				/**
				 * We found two separate cart hash now send reload trigger to checkout.js
				 */
				wp_send_json( [
					'reload' => true,
				] );
			}
		}
	}

	/**
	 * Display proper subtotal in case pack or 2,4
	 *
	 */
	public function display_proper_subtotal( $subtotal, $cart_item ) {
		if ( $this->is_checkout_override ) {
			return $subtotal;
		}
		if ( wp_doing_ajax() ) {
			return $subtotal;
		}
		if ( isset( $cart_item['_wfacp_product'] ) ) {
			$subtotal = WC()->cart->get_product_subtotal( $cart_item['data'], 1 );
		}

		return $subtotal;
	}

	public function set_save_session( $cart_content ) {
		if ( is_user_logged_in() ) {

			$cart_conm = WC()->session->get( 'wfacp_sustain_cart_content_' . WFACP_Common::get_Id(), [] );
			if ( ! empty( $cart_conm ) ) {
				WC()->session->__unset( 'wfacp_sustain_cart_content_' . WFACP_Common::get_Id() );

				return $cart_conm;

			}
		}

		return $cart_content;
	}

	/**
	 * Remove all canonical in our page
	 * Because of checkout page not for seo
	 * IN firefox <link rel='next' href="URL">
	 * Load our current page in network
	 * and this cause to wrong behaviour of page
	 * this issue occur with account.buildwoofunnels.com
	 */
	public function remove_canonical_link() {
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
		remove_action( 'wp_head', 'rel_canonical' );
	}

	public function reset_our_localstorage() {
		?>
        <script>

            if (typeof Storage !== 'undefined') {
                window.localStorage.removeItem('wfacp_checkout_page_id');
            }
        </script>
		<?php
	}

	/**
	 * Return true if all product is virtual
	 * @return bool
	 */
	public function is_cart_virtual() {
		return $this->is_cart_virtual;
	}

	public function woocommerce_cart_is_empty() {
		WC()->session->__unset( 'wfacp_sustain_cart_content_' . WFACP_Common::get_Id() );
		WC()->session->__unset( 'wfacp_woocommerce_applied_coupon_' . WFACP_Common::get_Id() );

	}

	private function push_product_to_cart() {

		do_action( 'wfacp_before_add_to_cart', $this->products );
		$product_switcher_data = WFACP_Common::get_product_switcher_data( WFACP_Common::get_id() );

		$add_to_cart_setting = isset( $product_switcher_data['product_settings']['add_to_cart_setting'] ) ? $product_switcher_data['product_settings']['add_to_cart_setting'] : '';
		$default_products    = [];

		if ( isset( $product_switcher_data['default_products'] ) ) {
			if ( is_string( $product_switcher_data['default_products'] ) ) {
				$default_products[] = trim( $product_switcher_data['default_products'] );
			} elseif ( is_array( $product_switcher_data['default_products'] ) ) {
				$default_products = $product_switcher_data['default_products'];
			}
		}

		if ( ( 2 == $add_to_cart_setting || 3 == $add_to_cart_setting ) && $this->get_product_count() > 1 ) {

			if ( is_array( $default_products ) && count( $default_products ) > 0 ) {
				$may_be_skip_product = [];
				if ( 2 == $add_to_cart_setting && count( $default_products ) > 1 ) {
					$temp_first = $default_products[0];
					unset( $default_products );
					$default_products[] = $temp_first;
				}

				foreach ( $default_products as $dpk => $dp ) {
					if ( isset( $this->products[ $dp ] ) ) {
						$product_available = $this->product_available_form_purchase( $this->products[ $dp ], $dp );
						if ( false == $product_available ) {
							unset( $default_products[ $dpk ] );
							$may_be_skip_product[] = $dp;
						}
					} else {
						unset( $default_products[ $dpk ] );
					}
				}

				if ( empty( $default_products ) ) {
					foreach ( $this->products as $index => $data ) {
						if ( in_array( $index, $may_be_skip_product ) ) {
							continue;
						}
						$product_available = $this->product_available_form_purchase( $data, $index );
						if ( true == $product_available ) {
							$default_products[] = $index;
							break;
						}
					}
				}
			}
			unset( $data, $product_id, $quantity, $variation_id, $product_obj );
		} else {
			if ( ! empty( $this->products ) ) {
				$key                    = key( $this->products );
				$value                  = reset( $this->products );
				$value['is_default']    = true;
				$this->products[ $key ] = $value;
			}
		}

		$hide_best_value     = wc_string_to_bool( $product_switcher_data['settings']['hide_best_value'] );
		$best_value_product  = trim( $product_switcher_data['settings']['best_value_product'] );
		$best_value_text     = trim( $product_switcher_data['settings']['best_value_text'] );
		$best_value_position = trim( $product_switcher_data['settings']['best_value_position'] );

		if ( function_exists( 'WCCT_Core' ) && class_exists( 'WCCT_discount' ) ) {
			add_filter( 'wcct_force_do_not_run_campaign', [ $this, 'unset_wcct_campaign' ], 10, 2 );
		}

		$virtual_product       = 0;
		$best_value_by_session = WC()->session->get( 'wfacp_product_best_value_by_parameter_' . WFACP_Common::get_id(), '' );

		$best_value_by_parameter = [];
		if ( '' !== $best_value_by_session ) {
			$best_value_by_parameter = explode( ',', $best_value_by_session );
		}

		$best_value_counter = 1;

		$product_count                 = $this->get_product_count();
		$apply_best_value_by_parameter = false;

		if ( ! empty( $best_value_by_parameter ) && count( $best_value_by_parameter ) <= $product_count ) {
			$apply_best_value_by_parameter = true;
			$best_value_product            = '';
		}


		foreach ( $this->products as $index => $data ) {
			$product_id   = absint( $data['id'] );
			$quantity     = absint( $data['quantity'] );
			$variation_id = 0;
			if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
				$product_id   = absint( $data['parent_product_id'] );
				$variation_id = absint( $data['id'] );
			}

			$product_obj = WFACP_Common::wc_get_product( ( $variation_id > 0 ? $variation_id : $product_id ), $index );
			if ( ! $product_obj instanceof WC_Product ) {
				continue;
			}
			if ( $product_obj->is_virtual() ) {
				$virtual_product ++;
			}

			//force all condition
			if ( 1 == $add_to_cart_setting || empty( $default_products ) ) {
				// make all product default
				$data['is_default'] = true;
			} elseif ( 2 == $add_to_cart_setting || 3 == $add_to_cart_setting ) {
				if ( in_array( $index, $default_products ) ) {
					$data['is_default'] = true;
				}
			}

			if ( ! isset( $data['add_to_cart_via_url'] ) ) {
				$data['org_quantity'] = $quantity;
				$data['quantity']     = 1;
			}

			// merger product switcher data
			if ( isset( $product_switcher_data['products'][ $index ] ) ) {
				$data = wp_parse_args( $product_switcher_data['products'][ $index ], $data );
			}

			if ( false == $hide_best_value && '' != $best_value_text ) {

				if ( $apply_best_value_by_parameter && in_array( $best_value_counter, $best_value_by_parameter ) ) {
					$data['best_value']          = true;
					$data['best_value_text']     = $best_value_text;
					$data['best_value_position'] = $best_value_position;
				} else {
					if ( $index == $best_value_product ) {
						$data['best_value']          = true;
						$data['best_value_text']     = $best_value_text;
						$data['best_value_position'] = $best_value_position;
					}
				}
			}

			$data['item_key']         = $index;
			$this->products[ $index ] = $data;

			$product_obj->add_meta_data( 'wfacp_data', $data );
			$this->added_products[ $index ] = $product_obj;
			if ( in_array( $product_obj->get_type(), WFACP_Common::get_variable_product_type() ) ) {
				$this->variable_product = true;
			}
			$best_value_counter ++;
		}

		if ( $this->get_product_count() == $virtual_product ) {
			$this->is_cart_virtual = true;
		}

		$is_product_added_to_cart = false;

		$all_notices = wc_get_notices();
		$success     = [];
		foreach ( $this->added_products as $index => $product_obj ) {
			$data = $product_obj->get_meta( 'wfacp_data' );

			if ( ! is_array( $data ) ) {
				continue;
			}

			if ( ! isset( $data['is_default'] ) ) {
				continue;
			}

			$product_id   = absint( $data['id'] );
			$quantity     = absint( $data['org_quantity'] );
			$variation_id = 0;
			if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
				$product_id   = absint( $data['parent_product_id'] );
				$variation_id = absint( $data['id'] );
			}
			try {
				$attributes  = [];
				$custom_data = [];
				if ( isset( $data['variable'] ) ) {
					$variation_id                             = absint( $data['default_variation'] );
					$attributes                               = $data['default_variation_attr'];
					$custom_data['wfacp_variable_attributes'] = $attributes;
					$default_variation                        = WFACP_Common::get_default_variation( $product_obj );
					if ( count( $default_variation ) > 0 ) {
						$variation_id = absint( $default_variation['variation_id'] );
						$attributes   = $default_variation['attributes'];
					}
				} else if ( in_array( $product_obj->get_type(), WFACP_Common::get_variation_product_type() ) ) {
					$attributes = $product_obj->get_attributes();
					if ( ! empty( $attributes ) ) {
						$new_attributes = [];
						foreach ( $attributes as $ts => $attribute ) {
							$ts                    = 'attribute_' . $ts;
							$new_attributes[ $ts ] = $attribute;
						}
						$attributes = $new_attributes;
					}
				}
				$custom_data['_wfacp_product']     = true;
				$custom_data['_wfacp_product_key'] = $index;
				$custom_data['_wfacp_options']     = $data;
				$cart_key                          = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes, $custom_data );
				if ( is_string( $cart_key ) ) {
					$success[] = $cart_key;
					WFACP_Common::pc( [
						'msg'        => 'Product added to cart  ',
						'wfacp_data' => $data,
						'cart_key'   => $cart_key,
					] );

					$this->products_in_cart[ $index ] = 1;
					$data['is_added_cart']            = $cart_key;
					$this->added_products[ $index ]->update_meta_data( 'wfacp_data', $data );;
					$this->products[ $index ]['is_added_cart'] = $cart_key;
					$this->have_product                        = true;
					$is_product_added_to_cart                  = true;
				} else {
					unset( $this->added_products[ $index ], $this->products[ $index ] );
				}
			} catch ( Exception $e ) {

			}
		}


		if ( false == $is_product_added_to_cart ) {
			$all_notices = array_merge( wc_get_notices(), $all_notices );
			WC()->session->set( 'wc_notices', $all_notices );
		} else {
			WC()->session->set( 'wc_notices', $all_notices );
		}

		do_action( 'wfacp_after_add_to_cart' );
		if ( count( $success ) > 0 ) {
			WC()->cart->removed_cart_contents = [];
			WC()->session->set( 'wfacp_id', WFACP_Common::get_id() );
			WC()->session->set( 'wfacp_cart_hash', md5( maybe_serialize( WC()->cart->get_cart_contents() ) ) );
			WC()->session->set( 'wfacp_product_objects_' . WFACP_Common::get_id(), $this->added_products );
			WC()->session->set( 'wfacp_product_data_' . WFACP_Common::get_id(), $this->products );
		}

	}

	public function aero_add_to_checkout_parameter() {
		return apply_filters( 'wfacp_aero_add_to_checkout_parameter', 'aero-add-to-checkout' );
	}

	public function aero_add_to_checkout_product_quantity_parameter() {
		return apply_filters( 'wfacp_add_to_checkout_product_quantity_parameter', 'aero-qty' );
	}


	public function aero_default_value_parameter() {
		return apply_filters( 'wfacp_aero_default_value_parameter', 'aero-default' );
	}

	public function aero_best_value_parameter() {
		return apply_filters( 'wfacp_aero_best_value_parameter', 'aero-best-value' );
	}

	public function merge_default_product( $default_products, $products, $settings ) {
		$default = $this->aero_default_value_parameter();
		if ( isset( $_GET[ $default ] ) && '' !== $_GET[ $default ] ) {

			$data = WC()->session->get( 'wfacp_product_default_value_parameter_' . WFACP_Common::get_id(), '' );

			if ( '' !== $data ) {

				$default_data = explode( ',', $_GET[ $default ] );

				if ( ! empty( $default_data ) ) {
					$default_products = [];
				}

				if ( true == $this->add_to_cart_via_url ) {
					$products = $this->products;

				}
				$counter = 1;
				foreach ( $products as $key => $product ) {
					if ( in_array( $counter, $default_data ) ) {
						$default_products[] = $key;
					}
					$counter ++;
				}
				$default_products = array_unique( $default_products );

			}
		}

		return $default_products;
	}

	/**
	 * @param $data product data arrray;
	 */
	public function product_available_form_purchase( $data, $unique_key ) {

		$product_available = true;
		$product_id        = absint( $data['id'] );
		$quantity          = absint( $data['quantity'] );
		$variation_id      = 0;
		if ( $data['parent_product_id'] && $data['parent_product_id'] > 0 ) {
			$product_id   = absint( $data['parent_product_id'] );
			$variation_id = absint( $data['id'] );
		}
		$product_obj = WFACP_Common::wc_get_product( ( $variation_id > 0 ? $variation_id : $product_id ), $unique_key );
		if ( ! $product_obj instanceof WC_Product ) {
			return false;
		}
		$stock_status = WFACP_Common::check_manage_stock( $product_obj, $quantity );

		if ( ! $product_obj->is_purchasable() || false == $stock_status ) {
			$product_available = false;
		}

		return $product_available;
	}

	public function get_product_count() {
		return $this->products_count;
	}

	public function wfacp_page_is_cached( $page_id ) {
		$page_id = absint( $page_id );
		WFACP_Common::set_id( $page_id );
		$this->get_page_data( $page_id );
		$this->push_product_to_cart();
		$this->apply_matched_coupons();
	}

	public function restrict_sold_individual( $status, $product_id ) {

		$cart_content = WC()->cart->get_cart_contents();
		if ( ! empty( $cart_content ) ) {
			foreach ( $cart_content as $item_key => $item_data ) {
				if ( $item_data['product_id'] == $product_id ) {
					$status = true;
					break;
				}
			}
		}

		return $status;
	}


}

if ( class_exists( 'WFACP_Core' ) && ! WFACP_Common::is_disabled() ) {
	WFACP_Core::register( 'public', 'WFACP_public' );
}


