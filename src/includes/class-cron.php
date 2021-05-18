<?php
/**
 * Since we call an external API, let's do everything in a background task.
 */

namespace BH_WC_Address_Validation\includes;

use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\api\Settings_Interface;
use BH_WC_Address_Validation\Psr\Log\LoggerInterface;

class Cron {

	const CHECK_SINGLE_ADDRESS_CRON_JOB     = 'bh_wc_address_validation_check_one_address';
	const CHECK_MULTIPLE_ADDRESSES_CRON_JOB = 'bh_wc_address_validation_check_many_addresses';
	const RECHECK_BAD_ADDRESSES_CRON_JOB    = 'bh_wc_address_validation_recheck_bad_addresses';


	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

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
	public function __construct( $api, $settings, $logger ) {

		$this->logger= $logger;
		$this->settings = $settings;
		$this->api = $api;

	/**
	 * Schedules or deletes the cron as per the settings.
	 *
	 * @see wp_get_schedules()
	 *
	 * @hooked plugins_loaded
	 */
	public function add_cron_jon(): void {

		if ( ! wp_next_scheduled( self::RECHECK_BAD_ADDRESSES_CRON_JOB ) ) {
			wp_schedule_event( time(), 'twicedaily', self::RECHECK_BAD_ADDRESSES_CRON_JOB );
			$this->logger->notice( 'Cron job scheduled' );
		}

	}

	/**
	 * @hooked self::CHECK_ADDRESS_CRON_JOB
	 *
	 * @param int $order_id The order to check.
	 */
	public function check_address_for_single_order( $order_id ) {

		if ( is_array( $order_id ) ) {

			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order ) {

			$this->logger->error( 'Invalid order_id.', array( 'order_id' => $order_id ) );

			return;
		}

		$this->api->check_address_for_order( $order );

	}

	/**
	 * @param int[] $order_ids
	 */
	public function check_address_for_multiple_orders( $order_ids ) {

		if ( ! is_array( $order_ids ) ) {
			return;
		}

		foreach ( $order_ids as $order_id ) {
			$this->check_address_for_single_order( $order_id );
		}

	}

	public function recheck_bad_address_orders() {
		$this->api->recheck_bad_address_orders();
	}
}
