<?php
/**
 * Since we call an external API, let's do everything in a background task.
 */

namespace BrianHenryIE\WC_Address_Validation\WP_Includes;

use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

class Cron {

	use LoggerAwareTrait;

	const CHECK_SINGLE_ADDRESS_CRON_JOB     = 'bh_wc_address_validation_check_one_address';
	const CHECK_MULTIPLE_ADDRESSES_CRON_JOB = 'bh_wc_address_validation_check_many_addresses';
	const RECHECK_BAD_ADDRESSES_CRON_JOB    = 'bh_wc_address_validation_recheck_bad_addresses';

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Cron constructor.
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}


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
			$this->logger->info( 'Cron job scheduled: ' . self::RECHECK_BAD_ADDRESSES_CRON_JOB );
		}
	}

	/**
	 * @hooked self::CHECK_ADDRESS_CRON_JOB
	 *
	 * @param int $order_id The order to check.
	 */
	public function check_address_for_single_order( int $order_id ): void {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {

			$this->logger->error( 'Invalid order_id.', array( 'order_id' => $order_id ) );

			return;
		}

		$this->api->check_address_for_order( $order, false );
	}

	/**
	 * @see Cron::CHECK_MULTIPLE_ADDRESSES_CRON_JOB
	 * @hooked bh_wc_address_validation_check_many_addresses
	 *
	 * @param int[] $order_ids
	 */
	public function check_address_for_multiple_orders( array $order_ids ): void {

		foreach ( $order_ids as $order_id ) {
			$this->check_address_for_single_order( $order_id );
		}
	}

	/**
	 * Sometimes the check fails – e.g. sometimes the USPS API is offline.
	 */
	public function recheck_bad_address_orders(): void {
		$this->api->recheck_bad_address_orders();
	}
}
