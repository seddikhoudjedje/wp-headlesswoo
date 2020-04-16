<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class WFACP_Compatibility_With_WCCT {
	public function __construct() {

		add_action( 'template_redirect', [ $this, 'am_before_sticky_bar_call' ], 1 );
		add_action( 'template_redirect', [ $this, 'am_after_sticky_bar_call' ], 3 );
		/* checkout page */
		add_action( 'wfacp_after_checkout_page_found', [ $this, 'am_allow_finale_sticky_campaigns' ] );
	}

	public function am_before_sticky_bar_call() {
		add_filter( 'wcct_force_do_not_run_campaign', [ $this, 'am_force_campaign_run' ], 100, 2 );
	}

	public function am_after_sticky_bar_call() {
		remove_filter( 'wcct_force_do_not_run_campaign', 'am_force_campaign_run', 100 );
	}

	public function am_force_campaign_run() {
		return true;
	}

	public function am_allow_finale_sticky_campaigns() {
		if ( ! class_exists( 'WCCT_Appearance' ) ) {
			return;
		}
		$appearance = WCCT_Appearance::get_instance();
		if ( ! method_exists( $appearance, 'wcct_triggers_sticky_header_and_footer' ) ) {
			return;
		}
		remove_action( 'wp_footer', [ $appearance, 'wcct_triggers_sticky_header_and_footer' ], 50 );
		add_action( 'wfacp_footer_before_print_scripts', [ $appearance, 'wcct_triggers_sticky_header_and_footer' ], 50 );
	}

}

WFACP_Plugin_Compatibilities::register( new WFACP_Compatibility_With_WCCT(), 'wcct' );
