<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 * @var $available_methods []
 * @var $package []
 * @var $package_name , String
 * @var $show_package_details Boolean
 * @var $package_details String
 */

defined( 'ABSPATH' ) || exit;

$formatted_destination    = isset( $formatted_destination ) ? $formatted_destination : WC()->countries->get_formatted_address( $package['destination'], ', ' );
$has_calculated_shipping  = ! empty( $has_calculated_shipping );
$show_shipping_calculator = ! empty( $show_shipping_calculator );
$calculator_text          = '';
if ( $available_methods ) {
	foreach ( $available_methods as $method ) {
		if ( $chosen_method == $method->get_id() ) {
			$shippingLabelText = '';
			if ( apply_filters( 'wfacp_enable_shipping_label', false ) ) {
				$shippingLabel     = apply_filters( 'wfacp_shipping_label', sprintf( __( 'via %s', 'woocommerce' ), WFACP_Common::shipping_method_label( $method ) ) );
				$shippingLabelText = '<small>&nbsp;' . $shippingLabel . '</small>';
			}
			?>
            <tr class="shipping_total_fee">
                <td <?php echo $colspan_attr; ?>><?php echo $package_name; ?><?php echo $shippingLabelText ?></td>
                <td style="text-align: right" data-title="<?php echo esc_attr( $package_name ); ?>">
					<?php
					echo WFACP_Common::wc_cart_totals_shipping_method_cost( $method )
					?>
                </td>
            </tr>
			<?php
		}
	}

} else {
	echo '<tr class="shipping_total_fee">';
	echo "<td {$colspan_attr}>" . wp_kses_post( $package_name ) . "</td>";
	echo "<td style=text-align:right;'>";

	if ( ! $has_calculated_shipping || ! $formatted_destination ) {
		esc_html_e( 'Enter your address to view shipping options.', 'woocommerce' );
	} elseif ( ! is_cart() ) {
		echo wp_kses_post( apply_filters( 'woocommerce_no_shipping_available_html', __( 'There are no shipping options available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce' ) ) );
	} else {
		// Translators: $s shipping destination.
		echo wp_kses_post( apply_filters( 'woocommerce_cart_no_shipping_available_html', sprintf( esc_html__( 'No shipping options were found for %s.', 'woocommerce' ) . ' ', '<strong>' . esc_html( $formatted_destination ) . '</strong>' ) ) );
	}
	if ( $show_package_details ) :
		echo '<p class="woocommerce-shipping-contents"><small>' . esc_html( $package_details ) . '</small></p>';
	endif;

	echo "</td></tr>";
}
