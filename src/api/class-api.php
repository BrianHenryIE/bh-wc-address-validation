<?php


namespace BH_WC_Address_Validation\api;

use WC_Data_Exception;
use WC_Order;
use BH_WC_Address_Validation\includes\BH_WC_Address_Validation;
use BH_WC_Address_Validation\USPS\Address;
use BH_WC_Address_Validation\USPS\AddressVerify;
use BH_WC_Address_Validation\woocommerce\Order_Status;
use BH_WC_Address_Validation\WPPB\WPPB_Object;

class API {
	const BH_WC_ADDRESS_VALIDATION_CHECKED_META = 'bh-wc-address-validation-checked';

	/** @var Settings_Interface */
	protected $settings;

	/**
	 * @var AddressVerify
	 */
	protected $address_verify;

	/**
	 * API constructor.
	 *
	 * @param AddressVerify      $address_verify
	 * @param Settings_Interface $settings
	 */
	public function __construct( $settings, $address_verify = null ) {

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
	public function check_address_for_order( $order ) {

		if ( ! $order instanceof WC_Order ) {
			BH_WC_Address_Validation::log( 'Object passed to check_address_for_order not WC_Order' );
			return;
		}

		if ( empty( $this->settings->get_usps_username() ) ) {
			BH_WC_Address_Validation::log( 'USPS username not set.' );
			return;
		}

		if ( empty( $this->address_verify ) ) {
			BH_WC_Address_Validation::log( 'AddressVerify null' );
			return;
		}

		BH_WC_Address_Validation::log( 'Checking address for order ' . $order->get_id() );

		$order_address              = array();
		$order_address['address_1'] = $order->get_shipping_address_1();
		$order_address['address_2'] = $order->get_shipping_address_2();
		$order_address['city']      = $order->get_shipping_city();
		$order_address['state']     = $order->get_shipping_state();
		$order_address['zip']       = substr( $order->get_shipping_postcode(), 0, 5 );
		$order_address['country']   = $order->get_shipping_country();

		if ( 'US' !== $order_address['country'] ) {
			BH_WC_Address_Validation::log( $order->get_id() . ' – Not a US address: ' . $order->get_shipping_country() );
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
					BH_WC_Address_Validation::log( ' State not set : ' . json_encode( $response ) );

					// This is happening when USPS is returning two results together.
					// The correct address is probably in that array.
					// But since the two requests were made separately, why is USPS returning them as one?!
					// Were the two requests made separately?

					return;
				}

				// TODO: This is a weird decision
				if ( strtoupper( $order->get_shipping_state() ) !== $response_address['State'] ) {
					BH_WC_Address_Validation::log( 'Order ' . $order->get_id() . ' : State returned from USPS ' . $response_address['State'] . 'did not match customer supplied state ' . strtoupper( $order->get_shipping_state() ) . ' : ' . json_encode( $response ), 'alert' );
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

				BH_WC_Address_Validation::log( $message );

				// If this is a re-check.
				if ( Order_Status::BAD_ADDRESS_STATUS === $order->get_status() ) {
					// TODO: Will this cause a repeat check?
					$order->set_status( 'processing' );
				}

				// wp post meta delete 11 bh-wc-address-validation-checked
				$order->add_meta_data( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META, 'true' );
				$order->save();

			}
		} else {

			// TODO: Check the reason for failure... e.g. timeout rather than bad address.

			//
			// "Peer’s Certificate has expired."

			$message = 'USPS Address Information API failed validation: ' . $this->address_verify->getErrorMessage();
			BH_WC_Address_Validation::log( 'Order ' . $order->get_id() . ': ' . $message );

			if ( strpos( $this->address_verify->getErrorMessage(), 'Multiple addresses were found' ) === 0 ) {

				$response = $this->address_verify->convertResponseToArray();

				BH_WC_Address_Validation::log( $response );

			}

			$already_checked = $order->get_meta( self::BH_WC_ADDRESS_VALIDATION_CHECKED_META );
			if ( empty( $already_checked ) || 'true' !== $already_checked ) {
				$order->set_status( Order_Status::BAD_ADDRESS_STATUS );
			} else {
				$message .= "\n\nOrder status not updated.";
			}

			$order->add_order_note( $message );

			$order->save();

			// TODO: Send email? --> do this inside the a WC_Email class.
		}

	}

}
