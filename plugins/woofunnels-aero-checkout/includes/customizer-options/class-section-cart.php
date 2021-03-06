<?php
defined( 'ABSPATH' ) || exit;

class WFACP_SectionCart {

	public static $customizer_key_prefix = 'wfacp_';
	public static $_instance = null;

	/**
	 * @var $template_common  WFACP_Template_Common
	 */
	public $template_common;

	protected function __construct( $template_common = null ) {
		if ( ! is_null( $template_common ) ) {
			$this->template_common = $template_common;
		}
	}

	public static function get_instance( $template_common ) {
		if ( self::$_instance == null ) {
			self::$_instance = new self( $template_common );
		}

		return self::$_instance;
	}

	public function cart_settings() {

		$section_data_keys = [];

		$selected_template_slug = $this->template_common->get_template_slug();
		$fields                 = $this->template_common->get_checkout_fields();

		/** PANEL: Form Setting */
		$form_cart_panel = array();
		if ( ! is_array( $fields ) || count( $fields ) == 0 ) {
			return;
		}

		$cartTitle      = esc_attr__( 'Your Cart', 'woofunnels-aero-checkout' );

		$pageID         = WFACP_Common::get_id();
		$_wfacp_version = WFACP_Common::get_post_meta_data( $pageID, '_wfacp_version' );
		if ( $_wfacp_version == WFACP_VERSION ) {
			$cartTitle = __( 'Order Summary', 'woofunnels-aero-checkout' );

		}

		$form_cart_panel['wfacp_form_cart'] = array(
			'panel'    => 'no',
			'data'     => array(
				'priority'    => 40,
				'title'       => __( $cartTitle, 'woofunnels-aero-checkout' ),
				'description' => '',
			),
			'sections' => array(
				'section' => array(
					'data'   => array(
						'title'    => __($cartTitle, 'woofunnels-aero-checkout' ),
						'priority' => 20,
					),
					'fields' => [
						/* Cart Section Setting */
						'ct_section_cart' => array(
							'type'     => 'custom',
							'default'  => '<div class="options-title-divider">' . esc_html__( 'Section', 'woofunnels-aero-checkout' ) . '</div>',
							'priority' => 20,
						),

						$selected_template_slug . '_enable_heading'      => [
							'type'        => 'checkbox',
							'label'       => __( 'Enable Section Heading', 'woofunnels-aero-checkout' ),
							'description' => '',
							'default'     => true,
							'priority'    => 20,
						],
						'heading'                                        => [
							'type'            => 'text',
							'label'           => __( 'Heading', 'woofunnels-aero-checkout' ),
							'description'     => '',
							'default'         => $cartTitle,
							'transport'       => 'postMessage',
							'wfacp_partial'   => [
								'elem' => '.wfacp_form_cart .wfacp_section_title',
							],
							'active_callback' => [
								[
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_enable_heading',
									'operator' => '==',
									'value'    => true,
								],
							],
							'priority'        => 20,
						],
						$selected_template_slug . '_heading_fs'          => [
							'type'            => 'wfacp-responsive-font',
							'label'           => __( 'Font Size', 'woofunnels-aero-checkout' ),
							'default'         => [
								'desktop' => 18,
								'tablet'  => 18,
								'mobile'  => 18,
							],
							'input_attrs'     => [
								'step' => 1,
								'min'  => 12,
								'max'  => 32,
							],
							'units'           => [
								'px' => 'px',
								'em' => 'em',
							],
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal'   => true,
									'responsive' => true,
									'type'       => 'css',
									'prop'       => [ 'font-size' ],
									'elem'       => 'body .wfacp_form_cart .wfacp_section_title',
								],
							],
							'active_callback' => [
								[

									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_enable_heading',
									'operator' => '==',
									'value'    => true,
								],
							],
							'priority'        => 20,
						],
						$selected_template_slug . '_heading_talign'      => [
							'type'    => 'radio-buttonset',
							'label'   => __( 'Text Alignment', 'woofunnels-aero-checkout' ),
							'default' => 'wfacp-text-left',
							'choices' => [
								'wfacp-text-left'   => 'Left',
								'wfacp-text-center' => 'Center',
								'wfacp-text-right'  => 'Right',
							],

							'active_callback' => [
								[
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_enable_heading',
									'operator' => '==',
									'value'    => true,
								],
							],
							'priority'        => 20,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'type'   => 'add_class',
									'direct' => 'true',
									'remove' => [ 'wfacp-text-left', 'wfacp-text-center', 'wfacp-text-right' ],
									'elem'   => '.wfacp_form_cart .wfacp_section_title',
								],
							],

						],
						$selected_template_slug . '_heading_font_weight' => [
							'type'    => 'radio-buttonset',
							'label'   => __( 'Font Weight', 'woofunnels-aero-checkout' ),
							'default' => 'wfacp-normal',
							'choices' => [
								'wfacp-bold'   => 'Bold',
								'wfacp-normal' => 'Normal',
							],

							'active_callback' => [
								[
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_enable_heading',
									'operator' => '==',
									'value'    => true,
								],
							],
							'priority'        => 20,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'type'   => 'add_class',
									'direct' => 'true',
									'remove' => [ 'wfacp-bold', 'wfacp-normal' ],
									'elem'   => '.wfacp_form_cart .wfacp_section_title',
								],
							],
						],
						/* Product Cart Setting */
						'ct_product_cart'                                => [
							'type'     => 'custom',
							'default'  => '<div class="options-title-divider">' . esc_html__( 'Product', 'woofunnels-aero-checkout' ) . '</div>',
							'priority' => 20,
						],
						$selected_template_slug . '_order_hide_img'      => [
							'type'        => 'checkbox',
							'label'       => __( 'Hide Image', 'woofunnels-aero-checkout' ),
							'description' => __( 'Enable if you want to hide the image', 'woofunnels-aero-checkout' ),
							'default'     => false,
							'priority'    => 20,
						],

						'ct_product_cart_coupon'                                  => [
							'type'     => 'custom',
							'default'  => '<div class="options-title-divider">' . esc_html__( 'Coupon', 'woofunnels-aero-checkout' ) . '</div>',
							'priority' => 20,
						],
						$selected_template_slug . '_order_hide_right_side_coupon' => [
							'type'        => 'checkbox',
							'label'       => __( 'Hide Coupon from sidebar Order Summary', 'woofunnels-aero-checkout' ),
							'description' => __( 'Enable if you want to hide the Coupon from the cart', 'woofunnels-aero-checkout' ),
							'default'     => false,
							'priority'    => 20,
						],

						/* Cart  Advance Setting */
						$selected_template_slug . '_advanced_setting'             => [
							'type'     => 'custom',
							'default'  => '<div class="options-title-divider">' . esc_html__( 'Advanced', 'woofunnels-aero-checkout' ) . '</div>',
							'priority' => 190,
						],
						$selected_template_slug . '_rbox_border_type'             => [
							'type'            => 'select',
							'label'           => esc_attr__( 'Border Type', 'woofunnels-aero-checkout' ),
							'default'         => 'none',
							'choices'         => array(
								'none'   => 'None',
								'solid'  => 'Solid',
								'double' => 'Double',
								'dotted' => 'Dotted',
								'dashed' => 'Dashed',
							),
							'priority'        => 200,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-style' ],
									'elem'     => '.wfacp_form_cart',
								],
								[
									'type'   => 'add_class',
									'direct' => 'true',
									'remove' => [ 'none', 'solid', 'double', 'dotted', 'dashed' ],
									'elem'   => '.wfacp_form_cart',
								],
							],
						],
						$selected_template_slug . '_rbox_border_width'            => [
							'type'            => 'slider',
							'label'           => esc_attr__( 'Border Width', 'woofunnels-aero-checkout' ),
							'default'         => 1,
							'choices'         => array(
								'min'  => '1',
								'max'  => '12',
								'step' => '1',
							),
							'priority'        => 210,
							'active_callback' => array(
								array(
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_rbox_border_type',
									'operator' => '!=',
									'value'    => 'none',
								),
							),
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-width' ],
									'elem'     => '.wfacp_form_cart',
								],
							],
						],
						$selected_template_slug . '_rbox_border_color'            => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Border Color', 'woofunnels-aero-checkout' ),
							'default'         => '#e2e2e2',
							'choices'         => array(
								'alpha' => true,
							),
							'priority'        => 220,
							'active_callback' => array(
								array(
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_rbox_border_type',
									'operator' => '!=',
									'value'    => 'none',
								),
							),
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-color' ],
									'elem'     => '.wfacp_form_cart',
								],
							],
						],
						$selected_template_slug . '_rbox_padding'                 => [
							'type'            => 'number',
							'label'           => __( 'Padding', 'woofunnels-aero-checkout' ),
							'default'         => 20,
							'priority'        => 220,
							'active_callback' => array(
								array(
									'setting'  => 'wfacp_form_cart_section_' . $selected_template_slug . '_rbox_border_type',
									'operator' => '!=',
									'value'    => 'none',
								),
							),
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'padding' ],
									'elem'     => '.wfacp_form_cart',
								],
							],
						],
						/* Header Color Setting */
						'ct_colors'                                               => [
							'type'     => 'custom',
							'default'  => '<div class="options-title-divider">' . esc_html__( 'Colors', 'woofunnels-aero-checkout' ) . '</div>',
							'priority' => 230,
						],
						$selected_template_slug . '_sec_bg_color'                 => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Section Background Color', 'woofunnels-aero-checkout' ),
							'default'         => 'transparent',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'background-color' ],
									'elem'     => 'body .wfacp_form_cart',
								],
							],
						],
						$selected_template_slug . '_sec_heading_color'            => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Section Title', 'woofunnels-aero-checkout' ),
							'default'         => '#333333',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => 'body .wfacp_form_cart .wfacp_section_title',
								],
							],
						],
						$selected_template_slug . '_label_price_color'            => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Label & Price', 'woofunnels-aero-checkout' ),
							'default'         => '#666666',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr:not(:last-child) th',
								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr:not(:last-child) td',
								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tbody tr.cart_item td',
								],
							],
						],
						$selected_template_slug . '_total_value_color'            => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Total Value', 'woofunnels-aero-checkout' ),
							'default'         => '#323232',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr.order-total th',

								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr.order-total td',
								],
							],
						],
						$selected_template_slug . '_divider_line_color'           => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Divider Line', 'woofunnels-aero-checkout' ),
							'default'         => '#dddddd',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.cart_item',

								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.order-total',
								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-color' ],
									'elem'     => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.cart-subtotal',
								],
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'border-color' ],
									'elem'     => '.wfacp_mb_mini_cart_wrap .wfacp_mb_cart_accordian',
								],
							],
						],
						$selected_template_slug . '_coupon_btn_bg_color'          => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Coupon Button Background', 'woofunnels-aero-checkout' ),
							'default'         => '#999999',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'background-color' ],
									'elem'     => '.wfacp_form_cart button.wfacp-coupon-btn',
								],

							],
						],
						$selected_template_slug . '_coupon_btn_label_color'       => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Coupon Button Label', 'woofunnels-aero-checkout' ),
							'default'         => '#ffffff',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart button.wfacp-coupon-btn',
								],
							],
						],
						$selected_template_slug . '_coupon_btn_bg_hover_color'    => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Coupon Button Background Hover', 'woofunnels-aero-checkout' ),
							'default'         => '#878484',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'background-color' ],
									'elem'     => '.wfacp_form_cart button.wfacp-coupon-btn:hover',
								],
							],
						],
						$selected_template_slug . '_coupon_btn_label_hover_color' => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Coupon Button Label Hover', 'woofunnels-aero-checkout' ),
							'default'         => '#ffffff',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart button.wfacp-coupon-btn:hover',
								],
							],
						],
						$selected_template_slug . '_qty_bg_color'                 => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Quantity Background', 'woofunnels-aero-checkout' ),
							'default'         => '#999999',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'background-color' ],
									'elem'     => '.wfacp_form_cart .wfacp-qty-count',
								],
							],
						],
						$selected_template_slug . '_qty_text_color'               => [
							'type'            => 'color',
							'label'           => esc_attr__( 'Quantity Text Color', 'woofunnels-aero-checkout' ),
							'default'         => '#fff',
							'choices'         => [
								'alpha' => true,
							],
							'priority'        => 250,
							'transport'       => 'postMessage',
							'wfacp_transport' => [
								[
									'internal' => true,
									'type'     => 'css',
									'prop'     => [ 'color' ],
									'elem'     => '.wfacp_form_cart .wfacp-qty-count',
								],
							],
						],

					],
				),
			),
		);

		$section_data_keys['colors'] = [
			$selected_template_slug . '_label_price_color'            => [
				[
					'type'   => 'color',
					'class'  => 'body .wfacp_form_cart .wfacp_section_title',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_sec_bg_color'                 => [
				[
					'type'   => 'background-color',
					'class'  => 'body .wfacp_form_cart',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_label_price_color'            => [
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr:not(:last-child) th',
					'device' => 'desktop',
				],
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr:not(:last-child) td',
					'device' => 'desktop',
				],
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tbody tr.cart_item td',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_total_value_color'            => [
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr.order-total th',
					'device' => 'desktop',
				],
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tfoot tr.order-total td',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_divider_line_color'           => [
				[
					'type'   => 'border-color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.cart_item',
					'device' => 'desktop',
				],
				[
					'type'   => 'border-color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.order-total',
					'device' => 'desktop',
				],
				[
					'type'   => 'border-color',
					'class'  => '.wfacp_form_cart table.shop_table.woocommerce-checkout-review-order-table_' . $selected_template_slug . ' tr.cart-subtotal',
					'device' => 'desktop',
				],
				[
					'type'   => 'border-color',
					'class'  => '.wfacp_mb_mini_cart_wrap .wfacp_mb_cart_accordian',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_coupon_btn_bg_color'          => [
				[
					'type'   => 'background-color',
					'class'  => '.wfacp_form_cart button.wfacp-coupon-btn',
					'device' => 'desktop',
				],

			],
			$selected_template_slug . '_coupon_btn_label_color'       => [
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart button.wfacp-coupon-btn',
					'device' => 'desktop',
				],

			],
			$selected_template_slug . '_coupon_btn_bg_hover_color'    => [
				[
					'type'   => 'background-color',
					'class'  => '.wfacp_form_cart button.wfacp-coupon-btn:hover',
					'device' => 'desktop',
				],

			],
			$selected_template_slug . '_coupon_btn_label_hover_color' => [
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart button.wfacp-coupon-btn:hover',
					'device' => 'desktop',
				],

			],
			$selected_template_slug . '_qty_bg_color'                 => [
				[
					'type'   => 'background-color',
					'class'  => '.wfacp_form_cart .wfacp-qty-count',
					'device' => 'desktop',
				],
			],
			$selected_template_slug . '_qty_text_color'               => [
				[
					'type'   => 'color',
					'class'  => '.wfacp_form_cart .wfacp-qty-count',
					'device' => 'desktop',
				],
			],

		];

		$this->template_common->set_section_keys_data( 'wfacp_form_cart', $section_data_keys );

		$form_cart_panel = apply_filters( 'wfacp_checkout_form_customizer_field', $form_cart_panel, $this );

		$form_cart_panel['wfacp_form_cart'] = apply_filters( 'wfacp_layout_default_setting', $form_cart_panel['wfacp_form_cart'], 'wfacp_form_cart' );

		return $form_cart_panel;
	}
}
