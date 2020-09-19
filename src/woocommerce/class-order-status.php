<?php

namespace BH_WC_Address_Validation\woocommerce;

use BH_WC_Address_Validation\WPPB\WPPB_Object;

class Order_Status extends WPPB_Object {

	const BAD_ADDRESS_STATUS = 'bad-address';

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
	 * Add "wc-usps-bad-address" to WooCommerce's list of statuses.
	 *
	 * Adds the new order status before "processing".
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
			if ( 'wc-processing' === $key ) {
				$new_order_statuses[ 'wc-' . self::BAD_ADDRESS_STATUS ] = 'Bad Address';
			}
			$new_order_statuses[ $key ] = $status;
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
}
