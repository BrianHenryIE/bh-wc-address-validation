<?php
/**
 * When an order is moving to processing, check with USPS for any error in the address.
 */

namespace BH_WC_Address_Validation\woocommerce;

use BH_WC_Address_Validation\api\Settings_Interface;
use BH_WC_Address_Validation\Psr\Log\LoggerInterface;
use WC_Order;
use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\includes\Cron;
use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;

class Order {

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
	 * Order constructor.
	 *
	 * @param API    $api
	 * @param string $plugin_name
	 * @param string $version
	 */
	public function __construct( $api, $settings, $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * When an order is marked processing, i.e. paid and ready to fulfill, check with USPS
	 * are there problems with the address.
	 *
	 * Do not run on bulk updates.
	 *
	 * @hooked woocommerce_order_status_changed
	 * @see WC_Order::status_transition()
	 *
	 * @param int    $order_id
	 * @param string $status_from
	 * @param string $status_to
	 */
	public function check_address_on_single_order_processing( $order_id, $status_from, $status_to ) {

		// TODO: This is also running on the bulk update action... only one is needed.
		// if ( isset( $_REQUEST['_wp_http_referer'] ) && '/wp-admin/edit.php?post_type=shop_order' === $_REQUEST['_wp_http_referer'] ) {
		// return;
		// }

		if ( 'processing' === $status_to ) {

			$args = array( $order_id );

			$this->logger->debug( 'Scheduling background process to check order ' . $order_id, array( 'order_id' => $order_id ) );

			wp_schedule_single_event( time() - 60, Cron::CHECK_SINGLE_ADDRESS_CRON_JOB, $args );
		}
	}

	/**
	 * @hooked admin_action_marked_processing
	 */
	public function check_address_on_bulk_order_processing() {

		// The bulk update should have an array of post (order) ids.
		if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
			return;
		}

		// TODO: sanitize.
		$order_ids = $_REQUEST['post'];

		$args = array( $order_ids );

		$this->logger->debug( 'Scheduling background process to check orders ' . implode( ', ', $order_ids ), array( 'order_ids' => $order_ids ) );

		wp_schedule_single_event( time() - 60, Cron::CHECK_MULTIPLE_ADDRESSES_CRON_JOB, $args );

	}

	/**
	 * Add "Validate address" to order actions in admin UI order edit page.
	 *
	 * TODO: Do not add if settings are not configured!
	 *
	 * @hooked woocommerce_order_actions
	 * @see class-wc-meta-box-order-actions.php
	 *
	 * @param string[] $actions
	 * @return string[]
	 */
	public function add_admin_ui_order_action( $actions ): array {

		$actions['bh_wc_address_validate'] = __( 'Validate address', 'bh-wc-address-validation' );

		return $actions;
	}

	/**
	 * This runs synchronously.
	 *
	 * @param WC_Order $order
	 */
	public function check_address_on_admin_order_action( $order ) {

		$this->logger->debug( $order->get_id() . ' check address started from edit order page.', array( 'order_id' => $order->get_id() ) );

		$is_manual = true;

		$this->api->check_address_for_order( $order, $is_manual );

		// TODO: Add admin notice.
	}


	/**
	 * @hooked woocommerce_admin_order_data_after_shipping_address
	 * @see class-wc-meta-box-order-data.php
	 *
	 * @param WC_Order $order
	 */
	public function add_link_to_usps_tools_zip_lookup( $order ) {

		// Check order status
		if ( Order_Status::BAD_ADDRESS_STATUS === $order->get_status() ) {

			echo '<a target="_blank" href="https://tools.usps.com/zip-code-lookup.htm?byaddress">USPS Zip Code Lookup Tool</a>';
		}

	}
}
