<?php

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Order_Status {

	use LoggerAwareTrait;

	const BAD_ADDRESS_STATUS = 'bad-address';

	/**
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Order_Status constructor.
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
	 * Register the order/post status with WordPress.
	 *
	 * @hooked woocommerce_init
	 * @see WooCommerce::init()
	 */
	public function register_status(): void {

		register_post_status(
			'wc-' . self::BAD_ADDRESS_STATUS,
			array(
				'label'                     => 'Bad Address',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Bad Address <span class="count">(%s)</span>', 'Bad Addresses <span class="count">(%s)</span>' ),
			)
		);
	}

	/**
	 * Add "wc-bad-address" to WooCommerce's list of statuses.
	 *
	 * Adds the new order status after "on-hold".
	 *
	 * @hooked wc_order_statuses
	 * @see wc_get_order_statuses()
	 *
	 * @param string[] $order_statuses WooCommerce order statuses.
	 * @return string[]
	 */
	public function add_order_status_to_woocommerce( $order_statuses ): array {

		$new_order_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-on-hold' === $key ) {
				$new_order_statuses[ 'wc-' . self::BAD_ADDRESS_STATUS ] = 'Bad Address';
			}
		}
		return $new_order_statuses;
	}

	/**
	 * Add the status to the list considered "paid" when considered by WooCommerce and other plugins.
	 *
	 * @hooked woocommerce_order_is_paid_statuses
	 * @see wc_get_is_paid_statuses()
	 *
	 * @param string[] $statuses ['processing', completed'] and other custom statuses that apply to paid orders.
	 * @return string[]
	 */
	public function add_to_paid_status_list( $statuses ): array {
		$statuses[] = self::BAD_ADDRESS_STATUS;
		return $statuses;
	}

	/**
	 * WooCommerce's reports do not respect wc_get_is_paid_statuses() so we need to add the status here too.
	 *
	 * @hooked woocommerce_reports_order_statuses
	 * @see \WC_Admin_Report::get_order_report_data()
	 * @see wp-admin/admin.php?page=wc-reports
	 *
	 * @param false|string[] $order_status
	 *
	 * @return false|string[]
	 */
	public function add_to_reports_status_list( $order_status ) {

		// In the refund report it is false.
		if ( false === $order_status || ! is_array( $order_status ) ) {
			return $order_status;
		}

		// In all paid scenarios, there are at least 'completed', 'processing', 'on-hold' already in the list.
		if ( ! ( in_array( 'completed', $order_status, true )
			&& in_array( 'processing', $order_status, true )
			&& in_array( 'on-hold', $order_status, true )
			) ) {
			return $order_status;
		}

		$this->logger->debug( 'Adding order status to reports status list', array( 'hooked' => 'woocommerce_reports_order_statuses' ) );

		$order_status[] = self::BAD_ADDRESS_STATUS;

		return $order_status;
	}
}
