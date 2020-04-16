<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) or exit;

/**
 * @class Subscription_Items
 */
class Subscription_Items extends Order_Items {

	public $data_item = 'subscription';

	function init() {
		parent::init();

		$this->title = __( 'Subscription - Items', 'automatewoo' );
	}

}

return new Subscription_Items();
