<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WFACP_Compatibility_With_Address_Auto_Populate {
	public function __construct() {

		/* checkout page */
		//add_action( 'wfacp_checkout_page_found', [ $this, 'dequeue_js' ] );

		add_action( 'wfacp_after_checkout_page_found', [ $this, 'dequeue_js' ] );

	}

	public function dequeue_js() {
		if ( class_exists( 'WC_Address_Validation' ) ) {

			add_action( 'woocommerce_before_checkout_form', function () {

				wp_enqueue_script( 'wfacp_address_populate', WFACP_PLUGIN_URL . '/assets/compatibility/js/address-populate.js', [], WFACP_VERSION, true );
			} );
		}
	}

}

WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_Address_Auto_Populate(), 'address-autofill' );
