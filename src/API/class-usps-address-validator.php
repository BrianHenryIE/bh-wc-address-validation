<?php
/**
 * @see https://www.usps.com/business/web-tools-apis/address-information-api.htm
 */

namespace BrianHenryIE\WC_Address_Validation\API;

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
	}

	/**
	 *
	 *
	 *
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address?: array}
	 */
	public function validate( array $address ): array {
		$result                     = array();
		$result['original_address'] = $address;

		$address_to_validate = new Address();

		$address_to_validate->setApt( $address['address_1'] );
		$address_to_validate->setAddress( $address['address_2'] );
		$address_to_validate->setCity( $address['city'] );
		$address_to_validate->setState( $address['state'] ); // TODO: I hope this returns 2 letter state.
		$address_to_validate->setZip5( substr( $address['postcode'], 0, 5 ) );
		$address_to_validate->setZip4( '' );

		// Add the address object to the address verify class
		$this->address_verify->addAddress( $address_to_validate );

		// Perform the request and return result
		$xml_response = $this->address_verify->verify();

		// See if it was successful
		if ( $this->address_verify->isSuccess() ) {

			$array_response = $this->address_verify->getArrayResponse();

			if ( isset( $array_response['AddressValidateResponse'] )
				&& isset( $array_response['AddressValidateResponse']['Address'] ) ) {

				// Let's just assume the API consistently returns all fields.
				$response_address = $array_response['AddressValidateResponse']['Address'];

				if ( ! isset( $response_address['State'] ) ) {
					$this->logger->info(
						' State not set : ' . json_encode( $array_response ),
						array(
							'xml_response'   => $xml_response,
							'array_response' => $array_response,
						)
					);

					// This is happening when USPS is returning two results together.
					// The correct address is probably in that array.
					// But since the two requests were made separately, why is USPS returning them as one?!
					// Were the two requests made separately?

					$result['success'] = false;

					return $result;
				}

				// TODO: This is a weird scenario.
				if ( strtoupper( $address['state'] ) !== $response_address['State'] ) {
					$error_message = 'State returned from USPS ' . $response_address['State'] . 'did not match customer supplied state ' . strtoupper( $address['state'] );
					$this->logger->notice( $error_message, array( 'response' => $array_response ) );

					$result['error']   = $error_message;
					$result['success'] = false;

					return $result;
				}

				$updated_address = array();

				$updated_address['address_1'] = isset( $response_address['Address1'] ) ? $response_address['Address1'] : '';
				$updated_address['address_2'] = $response_address['Address2'];
				$updated_address['city']      = $response_address['City'];
				$updated_address['state']     = $response_address['State'];
				$updated_address['country']   = 'US';

				$zip = $response_address['Zip5'];
				if ( ! empty( $response_address['Zip4'] ) ) {
					$zip .= '-' . $response_address['Zip4'];
				}
				$updated_address['postcode'] = $zip;

				$result['updated_address'] = $updated_address;

				$result['success'] = true;

				$message = 'Shipping address updated by USPS Address Verification API. Old address was : ' . implode( ' ', $address_to_validate->getAddressInfo() );

				$result['message'] = $message;

				$this->logger->debug( $message );

			}
		} else {

			// TODO: Check the reason for failure... e.g. timeout rather than bad address.

			//
			// "Peer’s Certificate has expired."

			$message = 'USPS Address Information API failed validation: ' . $this->address_verify->getErrorMessage();
			$this->logger->debug( $message );

			if ( strpos( $this->address_verify->getErrorMessage(), 'Multiple addresses were found' ) === 0 ) {

				$array_response = $this->address_verify->convertResponseToArray();

				$message .= "\n\n" . $array_response;

				$this->logger->debug(
					$message,
					array(
						'response'      => $array_response,
						'error_message' => $this->address_verify->getErrorMessage(),
					)
				);

			}
		}

		return $result;

	}

}
