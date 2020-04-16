<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WOE_Export_Http extends WOE_Export {

	public function run_export( $filename, $filepath ) {
		$args     = apply_filters( 'wc_order_export_http_args', array(
			'timeout'     => 5,
			'redirection' => 0,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'blocking'    => true,
			'body'        => file_get_contents( $filepath ),
			'cookies'     => array(),
			'user-agent'  => "WordPress " . $GLOBALS['wp_version'],
		), $filename, $filepath );
		
		$this->destination['http_post_url'] = str_replace('{filename}', $filename, $this->destination['http_post_url']);//replace tag
		$this->destination['http_post_url'] = apply_filters( 'woe_export_http_post_url', $this->destination['http_post_url'], $args);// adjust url
		
		// try run custom http query?
		$response = apply_filters( 'woe_export_http_custom_action', false, $this->destination['http_post_url'], $args);
		if( !$response )
			$response = wp_remote_post( $this->destination['http_post_url'], $args );
		
		$response  = apply_filters('woe_export_http_response', $response );

		// check for errors
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}
		

		return apply_filters('woe_export_http_result', $response['body'] );
	}

}
