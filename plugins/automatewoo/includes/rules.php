<?php
// phpcs:ignoreFile

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Rules
 * @since 2.6
 */
class Rules extends Registry {

	/** @var array */
	static $includes;

	/** @var array  */
	static $loaded = [];


	/**
	 * @return array
	 */
	static function load_includes() {

		$path = AW()->path( '/includes/rules/' );

		$includes = [
			'customer_is_guest' => $path . 'customer-is-guest.php',
			'customer_email' => $path . 'customer-email.php',
			'customer_role' => $path . 'customer-role.php',
			'customer_tags' => $path . 'customer-tags.php',
			'customer_country' => $path . 'customer-country.php',
			'customer_state' => $path . 'customer-state.php',
			'customer_state_text_match' => $path . 'customer-state-text-match.php',
			'customer_postcode' => $path . 'customer-postcode.php',
			'customer_city' => $path . 'customer-city.php',
			'customer_phone' => $path . 'customer-phone.php',
			'customer_company' => $path . 'customer-company.php',
			'customer_order_count' => $path . 'customer-order-count.php',
			'customer_total_spent' => $path . 'customer-total-spent.php',
			'customer_review_count' => $path . 'customer-review-count.php',
			'customer_first_order_date' => $path . 'customer-first-order-date.php',
			'customer_last_order_date' => $path . 'customer-last-order-date.php',
			'customer_order_statuses' => $path . 'customer-order-statuses.php',
			'customer_purchased_products' => $path . 'customer-purchased-products.php',
			'customer_purchased_categories' => $path . 'customer-purchased-categories.php',
			'customer_meta' => $path . 'customer-meta.php',
			'customer_last_review_date' => $path . 'customer-last-review-date.php',
			'customer_account_created_date' => $path . 'customer-account-created-date.php',

			'order_status' => $path . 'order-status.php',
			'order_total' => $path . 'order-total.php',
			'order_items' => $path . 'order-items.php',
			'order_item_categories' => $path . 'order-item-categories.php',
			'order_item_tags' => $path . 'order-item-tags.php',
			'order_items_text_match' => $path . 'order-items-text-match.php',
			'order_item_count' => $path . 'order-item-count.php',
			'order_line_count' => $path . 'order-line-count.php',
			'order_coupons' => $path . 'order-coupons.php',
			'order_coupons_text_match' => $path . 'order-coupons-text-match.php',
			'order_coupon_count' => $path . 'order-coupon-count.php',
			'order_payment_gateway' => $path . 'order-payment-gateway.php',
			'order_shipping_country' => $path . 'order-shipping-country.php',
			'order_billing_country' => $path . 'order-billing-country.php',
			'order_shipping_method' => $path . 'order-shipping-method.php',
			'order_shipping_method_string' => $path . 'order-shipping-method-string.php',
			'order_created_via' => $path . 'order-created-via.php',
			'order_meta' => $path . 'order-meta.php',
			'order_has_cross_sells' => $path . 'order-has-cross-sells.php',
			'order_is_customers_first' => $path . 'order-is-customers-first.php',
			'order_is_guest_order' => $path . 'order-is-guest-order.php',
			'order_customer_provided_note' => $path . 'order-customer-provided-note.php',
			'order_paid_date' => $path . 'order-paid-date.php',
			'order_created_date' => $path . 'order-created-date.php',

			'review_rating' => $path . 'review-rating.php',

			'product' => $path . 'product.php',
			'product_categories' => $path . 'product-categories.php',

			'order_item_meta' => $path . 'order-item-meta.php',
			'order_item_quantity' => $path . 'order-item-quantity.php',

			'cart_total' => $path . 'cart-total.php',
			'cart_count' => $path . 'cart-count.php',
			'cart_items' => $path . 'cart-items.php',
			'cart_item_categories' => $path . 'cart-item-categories.php',
			'cart_item_tags' => $path . 'cart-item-tags.php',
			'cart_coupons' => $path . 'cart-coupons.php',
			'cart_created_date' => $path . 'cart-created-date.php',

			'guest_email' => $path . 'guest-email.php',
			'guest_order_count' => $path . 'guest-order-count.php',

			'customer_run_count' => $path . 'customer-run-count.php',
			'workflow_last_customer_run_date' => $path . 'workflow-last-customer-run-date.php',
			'order_run_count' => $path . 'order-run-count.php',
			'guest_run_count' => $path . 'guest-run-count.php',

		];

		if ( Integrations::is_subscriptions_active() ) {
			$includes[ 'customer_has_active_subscription' ] = $path . 'customer-has-active-subscription.php';
			$includes[ 'order_is_subscription_renewal' ] = $path . 'order-is-subscription-renewal.php';
			$includes[ 'order_is_subscription_parent' ] = $path . 'order-is-subscription-parent.php';

			if ( class_exists( 'WCS_Retry_Manager' ) && \WCS_Retry_Manager::is_retry_enabled() ) {
				$includes[ 'order_subscription_payment_retry_count' ] = $path . 'order-subscription-payment-retry-count.php';
			}

			$includes[ 'subscription_status' ] = $path . 'subscription-status.php';
			$includes[ 'subscription_payment_count' ] = $path . 'subscription-payment-count.php';
			$includes[ 'subscription_payment_method' ] = $path . 'subscription-payment-method.php';
			$includes[ 'subscription_meta' ] = $path . 'subscription-meta.php';
			$includes[ 'subscription_items' ] = $path . 'subscription-items.php';
			$includes[ 'subscription_item_categories' ] = $path . 'subscription-item-categories.php';
			$includes[ 'subscription_coupons' ] = $path . 'subscription-coupons.php';
			$includes[ 'subscription_coupons_text_match' ] = $path . 'subscription-coupons-text-match.php';
			$includes[ 'subscription_coupon_count' ] = $path . 'subscription-coupon-count.php';
			$includes[ 'subscription_next_payment_date' ] = $path . 'subscription-next-payment-date.php';
			$includes[ 'subscription_last_payment_date' ] = $path . 'subscription-last-payment-date.php';
			$includes[ 'subscription_created_date' ] = $path . 'subscription-created-date.php';
			$includes[ 'subscription_trial_end_date' ] = $path . 'subscription-trial-end-date.php';
			$includes[ 'subscription_end_date' ] = $path . 'subscription-end-date.php';

			/**
			 * @since 4.5.0
			 */
			if ( Integrations::is_subscriptions_active( '2.3' ) ) {
				$includes[ 'subscription_can_renew_early' ]    = $path . 'subscription-can-renew-early.php';
			}

			if ( Integrations::is_subscriptions_active( '2.5' ) ) {
				$includes[ 'subscription_has_payment_method' ] = $path . 'subscription-has-payment-method.php';
			}

			$includes[ 'subscription_requires_manual_renewal' ] = $path . 'subscription-requires-manual-renewal.php';
		}

		if ( Integrations::is_memberships_enabled() ) {
			$includes[ 'customer_active_membership_plans' ] = $path . 'customer-active-membership-plans.php';
		}

		if ( Integrations::is_woo_pos() ) {
			$includes[ 'order_is_pos' ] = $path . 'order-is-pos.php';
		}

		if ( Options::mailchimp_enabled() ) {
			$includes[ 'customer_is_mailchimp_subscriber' ] = $path . 'customer-is-mailchimp-subscriber.php';
		}

		return apply_filters( 'automatewoo/rules/includes', $includes );
	}


	/**
	 * @return Rules\Rule[]
	 */
	static function get_all() {
		return parent::get_all();
	}


	/**
	 * @param $rule_name
	 * @return Rules\Rule|false
	 */
	static function get( $rule_name ) {
		return parent::get( $rule_name );
	}


	/**
	 * @param $rule_name
	 *
	 * @return bool
	 */
	static function load( $rule_name ) {
		include_once AW()->path( '/includes/rules/deprecated.php' );
		return parent::load( $rule_name );
	}


	/**
	 * @param string $rule_name
	 * @param Rules\Rule $rule
	 */
	static function after_loaded( $rule_name, $rule ) {
		$rule->name = $rule_name;
	}

}
