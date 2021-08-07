<?php
/**
 *
 * `wp post meta delete 11 bh-wc-address-validation-checked`
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\WC_Address_Validation\Container;
use BrianHenryIE\WC_Address_Validation\Includes\Cron;
use BrianHenryIE\WC_Address_Validation\Includes\Deactivator;
use BrianHenryIE\WC_Address_Validation\WooCommerce\Order_Status;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Data_Exception;
use WC_Order;

/**
 * Class API
 *
 * @package BrianHenryIE\WC_Address_Validation\API
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	const BH_WC_ADDRESS_VALIDATION_CHECKED_META = 'bh-wc-address-validation-checked';

	protected Settings_Interface $settings;

	protected Container $container;

	/**
	 * API constructor.
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( Container $container, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings  = $settings;
		$this->container = $container;
	}

	/**
	 * Adds the +4 zip code or marks the order BAAADDD!
	 *
	 * @param WC_Order $order
	 * @throws WC_Data_Exception
	 */
	public function check_address_for_order( WC_Order $order, bool $is_manual = false ): void {

		if ( empty( $this->settings->get_usps_username() ) ) {
			$this->logger->debug( 'USPS username not set.' );
			return;
		}

		$checked_meta = (array) $order->get_meta( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, true );
		$reactivating = $order->get_meta( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY );

		// Only automatically run once, except when reactivating.
		// Always run when manually run.
		// array_filter is here because (array) results in array( 0 => "" ) rather than a trully empty array.
		if ( ! empty( array_filter( $checked_meta ) ) && false === $is_manual && empty( $reactivating ) ) {
			return;
		}

		// Clear the reactivating meta key so it only kicks in once and doesn't interfere later.
		if ( ! empty( $reactivating ) ) {
			$order->delete_meta_data( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY );
			$order->save();
		}

		$this->logger->debug( 'Checking address for order ' . $order->get_id(), array( 'order_id', $order->get_id() ) );

		$order_address              = array();
		$order_address['address_1'] = $order->get_shipping_address_1();
		$order_address['address_2'] = $order->get_shipping_address_2();
		$order_address['city']      = $order->get_shipping_city();
		$order_address['state']     = $order->get_shipping_state();
		$order_address['postcode']  = substr( $order->get_shipping_postcode(), 0, 5 );
		$order_address['country']   = $order->get_shipping_country();

		$order_address = array_map( 'trim', $order_address );

		/**
		 * An interface to the APIs.
		 *
		 * @var Address_Validator_Interface $address_validator
		 */

		if ( 'US' === $order_address['country'] ) {
			$address_validator = $this->container->get( Container::USPS_ADDRESS_VALIDATOR );
		} elseif ( ! empty( $this->settings->get_easypost_api_key() ) ) {
			$address_validator = $this->container->get( Container::EASYPOST_ADDRESS_VALIDATOR );
		}

		if ( ! isset( $address_validator ) || is_null( $address_validator ) ) {
			// TODO: Log.
			return;
		}

		$result = $address_validator->validate( $order_address );

		if ( $result['success'] ) {

			$updated_address = $result['updated_address'];

			if ( $updated_address['address_1'] !== $order_address['address_1'] ) {
				$order->set_shipping_address_1( $updated_address['address_1'] );
			}
			if ( $updated_address['address_2'] !== $order_address['address_2'] ) {
				$order->set_shipping_address_2( $updated_address['address_2'] );
			}
			if ( $updated_address['city'] !== $order_address['city'] ) {
				$order->set_shipping_city( $updated_address['city'] );
			}
			// TODO: Is it OK to ever change the state?
			if ( $updated_address['state'] !== $order_address['state'] ) {
				$order->set_shipping_state( $updated_address['state'] );
			}
			if ( $updated_address['postcode'] !== $order_address['postcode'] ) {
				$order->set_shipping_postcode( $updated_address['postcode'] );
			}

			// If this is a re-check, update order status from bad-address to processing.
			if ( Order_Status::BAD_ADDRESS_STATUS === $order->get_status() ) {

				// TODO: Use previous status from meta.

				$new_status = 'processing';
				if ( ! empty( $checked_meta ) ) {
					$most_recent = array_pop( $checked_meta );
					if ( isset( $most_recent['previous_status'] ) ) {
						$new_status = $most_recent['previous_status'];
					}
				}

				$order->set_status( $new_status );
			}

			$message = $result['message'];
			$order->add_order_note( $message );

			// TODO Update customer address (if they have a user account).

			// TODO update billing address (if originals matched).

		} else {

			$error_message = $result['error_message'];

			if ( Order_Status::BAD_ADDRESS_STATUS !== $order->get_status() ) {
				$result['previous_status'] = $order->get_status();
			}

			$order->set_status( Order_Status::BAD_ADDRESS_STATUS );
			$order->add_order_note( $error_message );

			// Try again in a few hours.
			$args = array( $order->get_id() );
			wp_schedule_single_event( time() + HOUR_IN_SECONDS * 6, Cron::CHECK_SINGLE_ADDRESS_CRON_JOB, $args );

		}

		$checked_meta[ gmdate( 'c' ) ] = $result;
		$order->update_meta_data( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, $checked_meta );

		$order->save();

	}

	/**
	 * Often the USPS API returns "no address" but later a manual invokation will validate the address.
	 *
	 * This function is intended to be hooked on a regular cron (~4 hours) to re-run the check.
	 */
	public function recheck_bad_address_orders(): void {

		$orders = wc_get_orders(
			array(
				'limit'  => -1,
				'type'   => 'shop_order',
				'status' => array( 'wc-' . Order_Status::BAD_ADDRESS_STATUS ),
			)
		);

		// Probably enabled 'paginate' in the args.
		if ( ! is_array( $orders ) ) {
			return;
		}

		foreach ( $orders as $order ) {

			$this->check_address_for_order( $order, true );
		}
	}
}
