<?php


namespace BH_WC_Address_Validation\api;

use BH_WC_Address_Validation\includes\Deactivator;
use BH_WC_Address_Validation\Psr\Log\LoggerInterface;
use WC_Data_Exception;
use WC_Order;
use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;
use BH_WC_Address_Validation\USPS\Address;
use BH_WC_Address_Validation\USPS\AddressVerify;
use BH_WC_Address_Validation\woocommerce\Order_Status;

class API implements API_Interface {

	const BH_WC_ADDRESS_VALIDATION_CHECKED_META = 'bh-wc-address-validation-checked';

	/** @var LoggerInterface  */
	protected $logger;

	/** @var Settings_Interface */
	protected $settings;

	/**
	 * @var AddressVerify
	 */
	protected $address_verify;

	/**
	 * API constructor.
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 * @param AddressVerify      $address_verify
	 */
	public function __construct( $settings, $logger, $address_verify = null ) {

		$this->logger = $logger;
		$this->settings = $settings;

		if ( ! empty( $this->settings->get_usps_username() ) ) {
			$this->address_verify = new AddressVerify( $this->settings->get_usps_username() );
		}

	}

	/**
	 * Adds the +4 zip code or marks the order BAAADDD!
	 *
	 * @see https://www.usps.com/business/web-tools-apis/address-information-api.htm
	 *
	 * @param WC_Order $order
	 * @throws WC_Data_Exception
	 */
	public function check_address_for_order( $order, $is_manual = false ) {

		if ( ! $order instanceof WC_Order ) {
			$this->logger->debug( 'Object passed to check_address_for_order not WC_Order', array( 'order' => get_class( $order )) );
			return;
		}

		if ( empty( $this->settings->get_usps_username() ) ) {
			$this->logger->debug( 'USPS username not set.' );
			return;
		}

		if ( empty( $this->address_verify ) ) {
			$this->logger->error( 'AddressVerify null' );
			return;
		}

		$already_checked = $order->get_meta( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META );
		$reactivating    = $order->get_meta( Deactivator::DEACTIVATED_BAD_ADDRESS_META_KEY );

		// Only automatically run once, except when reactivating.
		// Always run when manually run.
		if ( ! empty( $already_checked ) && $is_manual === false && empty( $reactivating ) ) {
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
		$order_address['zip']       = substr( $order->get_shipping_postcode(), 0, 5 );
		$order_address['country']   = $order->get_shipping_country();

		if ( 'US' !== $order_address['country'] ) {
			$this->logger->info( $order->get_id() . ' – Not a US address: ' . $order->get_shipping_country() );
			return;
		}

		$this->check_address( $order_address, $order );
	}

	/**
	 * @param string[] $order_address
	 * @param WC_Order $order
	 */
	public function check_address( $order_address, $order ) {

		$address_to_validate = new Address();

		$address_to_validate->setApt( $order_address['address_1'] );
		$address_to_validate->setAddress( $order_address['address_2'] );
		$address_to_validate->setCity( $order_address['city'] );
		$address_to_validate->setState( $order_address['state'] ); // TODO: I hope this returns 2 letter state.
		$address_to_validate->setZip5( substr( $order_address['zip'], 0, 5 ) );
		$address_to_validate->setZip4( '' );

		// Add the address object to the address verify class
		$this->address_verify->addAddress( $address_to_validate );

		// Perform the request and return result
		$response = $this->address_verify->verify();

		// See if it was successful
		if ( $this->address_verify->isSuccess() ) {

			$response = $this->address_verify->getArrayResponse();

			if ( isset( $response['AddressValidateResponse'] )
				&& isset( $response['AddressValidateResponse']['Address'] ) ) {

				// Let's just assume the API consistently returns all fields.
				$response_address = $response['AddressValidateResponse']['Address'];

				$old_address = $order->get_formatted_shipping_address();

				if ( ! isset( $response_address['State'] ) ) {
					$this->logger->info( ' State not set : ' . json_encode( $response ) );

					// This is happening when USPS is returning two results together.
					// The correct address is probably in that array.
					// But since the two requests were made separately, why is USPS returning them as one?!
					// Were the two requests made separately?

					return;
				}

				// TODO: This is a weird scenario.
				if ( strtoupper( $order->get_shipping_state() ) !== $response_address['State'] ) {
					$this->logger->notice( 'Order ' . $order->get_id() . ' : State returned from USPS ' . $response_address['State'] . 'did not match customer supplied state ' . strtoupper( $order->get_shipping_state() ) . ' : ' . json_encode( $response ) );
					// DO NOTHING!
					return;
				}

				$address1 = isset( $response_address['Address1'] ) ? $response_address['Address1'] : '';
				$order->set_shipping_address_1( $address1 );
				$order->set_shipping_address_2( $response_address['Address2'] );
				$order->set_shipping_city( $response_address['City'] );
				$order->set_shipping_state( $response_address['State'] );
				$zip = $response_address['Zip5'] . '-' . $response_address['Zip4'];
				$order->set_shipping_postcode( $zip );

				$message = 'Shipping address updated by USPS Address Verification API. Old address was : ' . implode( ' ', $address_to_validate->getAddressInfo() );
				$order->add_order_note( $message );

				$this->logger->debug( $message );

				// If this is a re-check, update order status from bad-address to processing.
				if ( Order_Status::BAD_ADDRESS_STATUS === $order->get_status() ) {

					$order->set_status( 'processing' );
				}
			}
		} else {

			// TODO: Check the reason for failure... e.g. timeout rather than bad address.

			//
			// "Peer’s Certificate has expired."

			$message = 'USPS Address Information API failed validation: ' . $this->address_verify->getErrorMessage();
			$this->logger->debug( 'Order ' . $order->get_id() . ': ' . $message );

			if ( strpos( $this->address_verify->getErrorMessage(), 'Multiple addresses were found' ) === 0 ) {

				$response = $this->address_verify->convertResponseToArray();

				$this->logger->debug( $response );

			}

			$order->set_status( Order_Status::BAD_ADDRESS_STATUS );

			$order->add_order_note( $message );

		}

		// wp post meta delete 11 bh-wc-address-validation-checked
		$order->add_meta_data( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, time() );
		$order->save();

	}

}
