<?php

namespace BrianHenryIE\WC_Address_Validation\API;

use WP_CLI;
use WP_CLI_Command;

class CLI extends WP_CLI_Command {

	/** @var API */
	static $api;

	/**
	 * wp validate_address check_order 123
	 */
	public function check_order( $args ) {

		$order_id = $args[0];

		WP_CLI::line( 'checking order ' . $order_id );

		$order = wc_get_order( $order_id );

		self::$api->check_address_for_order( $order );
	}

	/**
	 *
	 * wp validate_address check_address '{"address_1": "123 Main St.", "address_2": "APT 456", "city":"New York", "state:": "NY", "country":"USA"}'
	 */
	public function check_address( $args ) {

	}
}
