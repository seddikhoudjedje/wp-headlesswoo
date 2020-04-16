<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WooFunnels_DB_Tables
 */
class WooFunnels_DB_Tables {

	/**
	 * instance of class
	 * @var null
	 */
	private static $ins = null;
	/**
	 * WPDB instance
	 *
	 * @since 2.0
	 *
	 * @var wp_db
	 */
	protected $wp_db;
	/**
	 * Charector collation
	 *
	 * @since 2.0
	 *
	 * @var string
	 */
	protected $charset_collate;
	/**
	 * Max index length
	 *
	 * @since 2.0
	 *
	 * @var int
	 */
	protected $max_index_length = 191;
	/**
	 * List of missing tables
	 *
	 * @since 2.0
	 *
	 * @var array
	 */
	protected $missing_tables;

	/**
	 * WooFunnels_DB_Tables constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wp_db = $wpdb;
		add_action( 'plugins_loaded', array( $this, 'add_if_needed' ) );
	}

	/**
	 * @return WooFunnels_DB_Tables|null
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self;
		}

		return self::$ins;
	}

	/**
	 * Add CF tables if they are missing
	 *
	 * @since 2.0
	 */
	public function add_if_needed() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$this->missing_tables = $this->find_missing_tables();

		if ( empty( $this->missing_tables ) ) {
			return;
		}

		$search = $this->wp_db->prefix . 'bwf_';
		foreach ( $this->missing_tables as $table ) {
			call_user_func( array( $this, str_replace( $search, '', $table ) ) );
		}
	}

	/**
	 * Find any missing Caldera Forms tables
	 *
	 * @return array
	 */
	protected function find_missing_tables() {

		$db_tables = get_option( '_bwf_created_tables', array() );

		$missing_tables = array();
		foreach ( $this->get_tables_list() as $table ) {
			if ( ! in_array( $table, $db_tables, true ) ) {
				$missing_tables[] = $table;
			}
		}

		return $missing_tables;
	}

	/**
	 * Get the list of woofunnels tables, with wp_db prefix
	 *
	 * @return array
	 * @since 2.0
	 *
	 */
	protected function get_tables_list() {

		$tables = array(
			'bwf_contact',
			'bwf_contact_meta',
			'bwf_wc_customers',
		);
		foreach ( $tables as &$table ) {
			$table = $this->wp_db->prefix . $table;
		}

		return $tables;
	}

	/**
	 * Get list of missing tables
	 *
	 * @return array
	 * @since 2.0
	 *
	 */
	public function get_missing_tables() {
		return $this->missing_tables;
	}

	/**
	 * Add bwf_contact table
	 *
	 *  Warning: check if it exists first, which could cause SQL errors.
	 */
	public function contact() {
		$collate = '';

		if ( $this->wp_db->has_cap( 'collation' ) ) {
			$collate = $this->wp_db->get_charset_collate();
		}
		$values_table = 'CREATE TABLE `' . $this->wp_db->prefix . "bwf_contact` (
				`id` int(12) unsigned NOT NULL AUTO_INCREMENT,
				`wpid` int(12) NOT NULL,
				`uid` varchar(35) NOT NULL DEFAULT '',
				`email` varchar(100) NOT NULL,
				`f_name` varchar(100),
				`l_name` varchar(100),
				`creation_date` DateTime NOT NULL,
				PRIMARY KEY (`id`),
				KEY `id` (`id`),
				KEY `wpid` (`wpid`),
				KEY `uid` (`uid`),
				KEY `email` (`email`)
				
                ) " . $collate . ';';

		dbDelta( $values_table );

		$tables = get_option( '_bwf_created_tables', array() );

		array_push( $tables, $this->wp_db->prefix . 'bwf_contact' );
		$tables = array_unique( $tables );
		update_option( '_bwf_created_tables', $tables );
	}

	/**
	 * Add bwf_contact_meta table
	 *
	 * Warning: check if it exists first, which could cause SQL errors.
	 *
	 * @since 2.0
	 */
	public function contact_meta() {
		$collate = '';

		if ( $this->wp_db->has_cap( 'collation' ) ) {
			$collate = $this->wp_db->get_charset_collate();
		}
		$meta_table = 'CREATE TABLE `' . $this->wp_db->prefix . "bwf_contact_meta` (
			`meta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`contact_id` bigint(20) unsigned NOT NULL DEFAULT '0',
			`meta_key` varchar(50) DEFAULT NULL,    
			`meta_value` longtext,
			PRIMARY KEY (`meta_id`)
            ) " . $collate . ';';

		dbDelta( $meta_table );

		$tables = get_option( '_bwf_created_tables', array() );

		array_push( $tables, $this->wp_db->prefix . 'bwf_contact_meta' );
		$tables = array_unique( $tables );
		update_option( '_bwf_created_tables', $tables, true );
	}

	/**
	 * Add bwf_wc_customers table
	 *
	 *  Warning: check if it exists first, which could cause SQL errors.
	 */
	public function wc_customers() {
		$collate = '';

		if ( $this->wp_db->has_cap( 'collation' ) ) {
			$collate = $this->wp_db->get_charset_collate();
		}
		$values_table = 'CREATE TABLE `' . $this->wp_db->prefix . 'bwf_wc_customers` (
                `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
                `cid` int(12) NOT NULL,
                `l_order_date` DateTime NOT NULL,
                `total_order_count` int(7) NOT NULL,
                `total_order_value` double NOT NULL,
                `purchased_products` longtext,
                `purchased_products_cats` longtext,
                `purchased_products_tags` longtext,
                `used_coupons` longtext,
                PRIMARY KEY (`id`),
                KEY `id` (`id`),
                KEY `cid` (`cid`)               
                ) ' . $collate . ';';

		dbDelta( $values_table );

		$tables = get_option( '_bwf_created_tables', array() );

		array_push( $tables, $this->wp_db->prefix . 'bwf_wc_customers' );
		$tables = array_unique( $tables );
		update_option( '_bwf_created_tables', $tables );
	}
}
