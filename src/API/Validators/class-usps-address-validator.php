<?php
/**
 * @see https://www.usps.com/business/web-tools-apis/address-information-api.htm
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\WC_Address_Validation\API\Address_Validator_Interface;
use BrianHenryIE\WC_Address_Validation\Container;
use BrianHenryIE\WC_Address_Validation\USPS\Address;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class USPS_Address_Validator implements Address_Validator_Interface {

	use LoggerAwareTrait;

	/**
	 * @var AddressVerify
	 */
	protected AddressVerify $address_verify;

	public function __construct( Container $container, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->address_verify = $container->get( Container::USPS_API_ADDRESS_VERIFY );

		$this->address_verify->setRevision( 1 );
	}

	/**
	 * TODO: AE (military) addresses cannot be validated.
	 *
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address: ?array, message: ?string, error_message: ?string}
	 */
	public function validate( array $address ): array {

		$result                     = array();
		$result['original_address'] = $address;

		$address_to_validate = new Address();

		// This needs to be set in order.
		$address_to_validate->setApt( $address['address_2'] );
		$address_to_validate->setAddress( $address['address_1'] );
		$address_to_validate->setCity( $address['city'] );
		$address_to_validate->setState( $address['state'] );
		$address_to_validate->setZip5( substr( $address['postcode'], 0, 5 ) );
		$address_to_validate->setZip4( '' );

		// Add the address object to the address verify class
		$this->address_verify->addAddress( $address_to_validate );

		// Perform the request and return result
		$xml_response   = $this->address_verify->verify();
		$array_response = $this->address_verify->convertResponseToArray();

		// See if it was successful
		if ( $this->address_verify->isSuccess() ) {

			if ( ! isset( $array_response['AddressValidateResponse'] ) || ! isset( $array_response['AddressValidateResponse']['Address'] ) ) {

				$this->logger->error( 'Unexpected API response', array( 'array_response' => $array_response ) );

				$result['success']       = false;
				$result['error_message'] = 'Unexpected API response';

				return $result;
			}

			$response_address = $array_response['AddressValidateResponse']['Address'];

			// If one address was returned.
			if ( isset( $response_address['@attributes'] ) ) {
				$returned_addresses = array( $response_address );
			} else {
				$returned_addresses = $response_address;
			}

			foreach ( $returned_addresses as $usps_address ) {

				// TODO: This is a weird scenario, but it has happened.
				if ( strtoupper( $address['state'] ) !== $usps_address['State'] ) {
					$error_message = 'State returned from USPS ' . $usps_address['State'] . ' did not match customer supplied state ' . strtoupper( $address['state'] );
					$this->logger->notice( $error_message, array( 'array_response' => $array_response ) );

					$result['error_message'] = $error_message;
					$result['success']       = false;

					return $result;
				}

				if ( isset( $usps_address['Error'] ) ) {

					$error_message = $usps_address['Error']['Description'];

					$result['error_message'] = $error_message;
					$result['success']       = false;

					$this->logger->error( $error_message );

					return $result;
				}
			}

			if ( count( $returned_addresses ) > 1 ) {

				$error_message = 'USPS returned more than one address for a single address check';
				$this->logger->error( $error_message );

				$result['error_message'] = $error_message;
				$result['success']       = false;

				return $result;
			}

			$response_address = $returned_addresses[0];

			$updated_address = array();

			// WooCommerce address_1 is the building an stree, whereas USPS Address1 is the apartment number.
			$updated_address['address_1'] = $response_address['Address2'];
			$updated_address['address_2'] = isset( $response_address['Address1'] ) ? $response_address['Address1'] : '';
			$updated_address['city']      = $response_address['City'];
			$updated_address['state']     = $response_address['State'];
			$updated_address['country']   = 'US';

			$zip = $response_address['Zip5'];

			// Not all addresses have a +4 zip code.
			if ( ! empty( $response_address['Zip4'] ) ) {
				$zip .= '-' . $response_address['Zip4'];
			}
			$updated_address['postcode'] = $zip;

			$result['updated_address'] = $updated_address;

			$result['success'] = true;

			$message = 'Shipping address updated by USPS Address Verification API. Old address was : ' . implode( ' ', $address_to_validate->getAddressInfo() );

			$result['message'] = $message;

			$this->logger->debug( $message );

		} else {

			// TODO: Check the reason for failure... e.g. timeout rather than bad address.

			// "Multiple addresses were found"
			// "Peer’s Certificate has expired."

			$result['success'] = false;

			$error_message = 'USPS Address Information API failed validation: ' . $this->address_verify->getErrorMessage() . "\n" . implode( "\n", array_values( $address ) );

			$result['error_message'] = $error_message;

			$this->logger->debug(
				$error_message,
				array(
					'error_message'  => $this->address_verify->getErrorMessage(),
					'xml_response'   => $xml_response,
					'array_response' => $array_response,
					'address'        => $address,
				)
			);
		}

		return $result;

	}

}
