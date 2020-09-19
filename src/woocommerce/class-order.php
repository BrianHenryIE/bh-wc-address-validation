<?php
/**
 * When an order is moving to processing, check with USPS for any error in the address.
 */

namespace BH_WC_Address_Validation\woocommerce;

use WC_Order;
use BH_WC_Address_Validation\api\API;
use BH_WC_Address_Validation\includes\Cron;
use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;
use BH_WC_Address_Validation\WPPB\WPPB_Object;

class Order extends WPPB_Object {

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
	public function __construct( $api, $plugin_name, $version ) {
		parent::__construct( $plugin_name, $version );

		$this->api = $api;
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

		// TODO: should this not run when it's a bulk update.
		// if ( isset( $_REQUEST['_wp_http_referer'] ) && '/wp-admin/edit.php?post_type=shop_order' === $_REQUEST['_wp_http_referer'] ) {
		// return;
		// }

		if ( 'processing' === $status_to ) {

			$args = array( $order_id );

			BH_WC_Address_Validation::log( 'Scheduling background process to check order ' . $order_id, 'debug' );

			wp_schedule_single_event( time() - 60, Cron::CHECK_ADDRESS_CRON_JOB, $args );
		}
	}

	/**
	 * @hooked admin_action_mark_processing
	 */
	public function check_address_on_bulk_order_processing() {

		// The bulk update should have an array of post (order) ids.
		if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
			return;
		}

		$args = array( $_REQUEST['post'] );

		BH_WC_Address_Validation::log( 'Scheduling background process to check order ' . implode( ', ', $_REQUEST['post'] ), 'debug' );

		wp_schedule_single_event( time() - 60, Cron::CHECK_ADDRESS_CRON_JOB, $args );

	}

	/**
	 * Add "Validate address" to order actions in admin UI order edit page.
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

		BH_WC_Address_Validation::log( $order->get_id() . ' check address started from edit order page.' );

		$this->api->check_address_for_order( $order );

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
