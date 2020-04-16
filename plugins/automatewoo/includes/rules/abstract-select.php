<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) or exit;

/**
 * @class Abstract_Select
 */
abstract class Abstract_Select extends Rule {

	public $type = 'select';

	/** @var array - leave public for json */
	public $select_choices;

	/** @var bool  */
	public $is_multi = false;


	function __construct() {

		if ( $this->is_multi ) {
			$this->compare_types = [
				'matches_any' => __( 'matches any', 'automatewoo' ),
				'matches_all' => __( 'matches all', 'automatewoo' ),
				'matches_none' => __( 'matches none', 'automatewoo' ),
			];
		}
		else {
			$this->compare_types = [
				'is' => __( 'is', 'automatewoo' ),
				'is_not' => __( 'is not', 'automatewoo' )
			];
		}

		parent::__construct();
	}


	/**
	 * @return array
	 */
	function load_select_choices() {
		return [];
	}


	/**
	 * @return array
	 */
	function get_select_choices() {
		if ( ! isset( $this->select_choices ) ) {
			$this->select_choices = $this->load_select_choices();
		}

		return $this->select_choices;
	}


	/**
	 * Validate a select rule.
	 *
	 * @param string|array $actual Will be an array when is_multi prop is true.
	 * @param string       $compare_type
	 * @param array|string $expected
	 *
	 * @return bool
	 */
	function validate_select( $actual, $compare_type, $expected ) {

		if ( $this->is_multi ) {

			if ( ! $actual ) $actual = []; // actual can be empty
			if ( ! $expected ) return false; // expected must have a value

			$actual = (array) $actual;
			$expected = (array) $expected;

			switch ( $compare_type ) {

				case 'matches_all':
					return count( array_intersect( $expected, $actual ) ) === count( $expected );
					break;

				case 'matches_none':
					return count( array_intersect( $expected, $actual ) ) === 0;
					break;

				case 'matches_any':
					return count( array_intersect( $expected, $actual ) ) >= 1;
					break;
			}
		}
		else {

			// actual must be scalar, but expected could be multiple values
			if ( ! is_scalar( $actual ) ) {
				return false;
			}

			if ( is_array( $expected ) ) {
				$is_equal = in_array( $actual, $expected );
			}
			else {
				$is_equal = $expected == $actual;
			}

			switch ( $compare_type ) {
				case 'is':
					return $is_equal;
					break;

				case 'is_not':
					return ! $is_equal;
					break;
			}
		}

		return false;
	}


	/**
	 * Validate select rule, but case insensitive.
	 *
	 * @since 4.4.0
	 *
	 * @param array|string $actual Will be an array when is_multi prop is true.
	 * @param string       $compare_type
	 * @param array|string $expected
	 *
	 * @return bool
	 */
	public function validate_select_case_insensitive( $actual, $compare_type, $expected ) {
		if ( is_array( $actual ) ) {
			$actual = array_map( 'wc_strtolower', $actual );
		} else {
			$actual = strtolower( (string) $actual );
		}
		$expected = array_map( 'wc_strtolower', (array) $expected );

		return $this->validate_select( $actual, $compare_type, $expected );
	}

}
