<?php
/**
 *
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\WC_Address_Validation\API\Validators\No_Validator_Exception;
use BrianHenryIE\WC_Address_Validation\API_Interface;
use BrianHenryIE\WC_Address_Validation\Container;
use BrianHenryIE\WC_Address_Validation\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Cron;
use BrianHenryIE\WC_Address_Validation\WP_Includes\Deactivator;
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

	const BH_WC_ADDRESS_VALIDATION_CHECKED_META = 'bh_wc_address_validation_checked';

	protected Settings_Interface $settings;

	protected Container $container;

	/**
	 * API constructor.
	 *
	 * @param Container          $container
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( Container $container, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings  = $settings;
		$this->container = $container;
	}

	/**
	 * Adds the +4 zip code or marks the order with 'bad-address' status.
	 *
	 * @param WC_Order $order
	 * @throws WC_Data_Exception
	 */
	public function check_address_for_order( WC_Order $order, bool $is_manual = false ): void {

		$checked_meta = (array) $order->get_meta( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, true );
		$reactivating = $order->get_meta( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY, true );

		// Only automatically run once, except when reactivating.
		// Always run when manually run.
		// array_filter is here because casting the meta to (array) results `in array( 0 => "" )` rather than a truly empty array.
		if ( ! empty( array_filter( $checked_meta ) ) && false === $is_manual && empty( $reactivating ) ) {
			return;
		}

		// Clear the reactivating meta key so it only kicks in once and doesn't interfere later.
		if ( ! empty( $reactivating ) ) {
			$order->delete_meta_data( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY );
			$order->save();
		}

		$this->logger->debug( 'Checking address for order ' . $order->get_id(), array( 'order_id', $order->get_id() ) );

		$order_shipping_address              = array();
		$order_shipping_address['address_1'] = $order->get_shipping_address_1();
		$order_shipping_address['address_2'] = $order->get_shipping_address_2();
		$order_shipping_address['city']      = $order->get_shipping_city();
		$order_shipping_address['state']     = $order->get_shipping_state();
		$order_shipping_address['postcode']  = $order->get_shipping_postcode();
		$order_shipping_address['country']   = $order->get_shipping_country();

		try {
			$result = $this->validate_address( $order_shipping_address );
		} catch ( No_Validator_Exception $e ) {
			$this->logger->info( 'No address validator available for address. ' . implode( ',', $order_shipping_address ), array( 'address' => $order_shipping_address ) );
			$order->add_order_note( 'No address validator available for address.' . implode( ',', $order_shipping_address ) );
			$order->save();
			return;
		}

		if ( $result['success'] ) { // Address is valid.

			$order_shipping_address = array_map( 'strtoupper', array_map( 'trim', $order_shipping_address ) );

			/** @var array $updated_address */
			$updated_address = $result['updated_address'];

			$address_was_changed = implode( ',', $order_shipping_address ) !== implode( ',', $updated_address );

			if ( $address_was_changed ) {
				// If the billing address was the same as the shipping address, update it too.
				$order_billing_address              = array();
				$order_billing_address['address_1'] = $order->get_billing_address_1();
				$order_billing_address['address_2'] = $order->get_billing_address_2();
				$order_billing_address['city']      = $order->get_billing_city();
				$order_billing_address['state']     = $order->get_billing_state();
				$order_billing_address['postcode']  = $order->get_billing_postcode();
				$order_billing_address['country']   = $order->get_billing_country();
				$order_billing_address              = array_map( 'strtoupper', array_map( 'trim', $order_billing_address ) );

				// Compare the addresses.
				$billing_equals_shipping = implode( ',', $order_shipping_address ) === implode( ',', $order_billing_address );

				$customer    = null;
				$customer_id = $order->get_customer_id();
				if ( 0 !== $customer_id ) {
					$customer = new \WC_Customer( $customer_id );
				}

				$order->set_shipping_address_1( $updated_address['address_1'] );
				$order->set_shipping_address_2( $updated_address['address_2'] );
				$order->set_shipping_city( $updated_address['city'] );
				$order->set_shipping_state( $updated_address['state'] );
				$order->set_shipping_postcode( $updated_address['postcode'] );

				if ( $billing_equals_shipping ) {
					$order->set_billing_address_1( $updated_address['address_1'] );
					$order->set_billing_address_2( $updated_address['address_2'] );
					$order->set_billing_city( $updated_address['city'] );
					$order->set_billing_state( $updated_address['state'] );
					$order->set_billing_postcode( $updated_address['postcode'] );
				}

				if ( $billing_equals_shipping && ( $customer instanceof \WC_Customer ) ) {
					$customer->set_billing_address_1( $updated_address['address_1'] );
					$customer->set_billing_address_2( $updated_address['address_2'] );
					$customer->set_billing_city( $updated_address['city'] );
					$customer->set_billing_state( $updated_address['state'] );
					$customer->set_billing_postcode( $updated_address['postcode'] );
				}

				if ( $customer instanceof \WC_Customer ) {
					$customer->set_shipping_address_1( $updated_address['address_1'] );
					$customer->set_shipping_address_2( $updated_address['address_2'] );
					$customer->set_shipping_city( $updated_address['city'] );
					$customer->set_shipping_state( $updated_address['state'] );
					$customer->set_shipping_postcode( $updated_address['postcode'] );
					$customer->save();
				}
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

		$checked_meta[ gmdate( DATE_ATOM ) ] = $result;
		$order->update_meta_data( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, $checked_meta );

		$order->save();

	}

	/**
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address_array
	 * @return array{success: bool, original_address: array, updated_address: ?array, message: ?string, error_message: ?string}
	 */
	public function validate_address( array $address_array ): array {

		if ( 'US' === $address_array['country'] && ! empty( $this->settings->get_usps_username() ) ) {
			$address_validator_type = Container::USPS_ADDRESS_VALIDATOR;
		} elseif ( ! empty( $this->settings->get_easypost_api_key() ) ) {
			$address_validator_type = Container::EASYPOST_ADDRESS_VALIDATOR;
		} else {
			throw new No_Validator_Exception( $address_array );
		}

		/**
		 * An interface to the APIs.
		 *
		 * @var Address_Validator_Interface $address_validator
		 */
		$address_validator = $this->container->get( $address_validator_type );

		$result = $address_validator->validate( $address_array );

		return $result;

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
