<?php
/**
 * Since we call an external API, let's do everything in a background task.
 */

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\WPPB\WPPB_Object;

class Cron extends WPPB_Object {

	const CHECK_ADDRESS_CRON_JOB = 'bh_wc_address_validation_check_address';

	/**
	 * @var API
	 */
	protected $api;

	/**
	 * Cron constructor.
	 *
	 * @param API    $api
	 * @param string $plugin_name
	 * @param string $version
	 */
	public function __construct( $api, $plugin_name, $version ) {
		parent::__construct( $plugin_name, $version );

		$this->api = $api;
	}

	/**
	 * @hooked self::CHECK_ADDRESS_CRON_JOB
	 *
	 * @param int $order_id The order to check.
	 */
	public function check_address_for_single_order( $order_id ) {

		if ( is_array( $order_id ) ) {

			$this->check_address_for_multiple_orders( $order_id );
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {

			BH_WC_Address_Validation::log( 'Invalid $order_id parameter passed to ' . __CLASS__ . '->' . __FUNCTION__ . '(' . json_encode( $order_id ) . ')', 'error' );

			return;
		}

		$this->api->check_address_for_order( $order );

	}

	public function check_address_for_multiple_orders( $order_ids ) {

		if ( ! is_array( $order_ids ) ) {
			return;
		}

		foreach ( $order_ids as $order_id ) {
			$this->check_address_for_single_order( $order_id );
		}

	}
}
