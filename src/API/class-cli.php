<?php

namespace BrianHenryIE\WC_Address_Validation\API;

use WC_Order;
use WP_CLI;
use WP_CLI_Command;

class CLI extends WP_CLI_Command {

	/** @var API_Interface */
	static API_Interface $api;

	/**
	 * wp validate_address check_order 123
	 */
	public function check_order( array $args ): void {

		$order_id = $args[0];

		WP_CLI::line( 'checking order ' . $order_id );

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			WP_CLI::error( 'Invalid order id: ' . $order_id );
			return;
		}

		self::$api->check_address_for_order( $order, true );
	}

	/**
	 *
	 * wp validate_address check_address '{"address_1": "123 Main St.", "address_2": "APT 456", "city":"New York", "state:": "NY", "country":"USA"}'
	 */
	public function check_address( array $args ): void {

	}
}
