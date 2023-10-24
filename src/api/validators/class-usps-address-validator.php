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
	 * Given an address array from WooCommerce (i.e. keyed as WooCommerce does), this returns an object as expected by
	 * the USPS validator. In particular, the USPS Address object is sensitive to the order in which the address parts are added.
	 *
	 * @param array array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 *
	 * @return Address
	 */
	protected function wc_address_array_to_usps_address_object( array $address ): Address {
		$address_to_validate = new Address();

		// This needs to be set in order.
		$address_to_validate->setApt( $address['address_2'] );
		$address_to_validate->setAddress( $address['address_1'] );
		$address_to_validate->setCity( $address['city'] );
		$address_to_validate->setState( $address['state'] );
		$address_to_validate->setZip5( substr( $address['postcode'], 0, 5 ) );
		$address_to_validate->setZip4( '' );

		return $address_to_validate;
	}

	/**
	 * TODO: AE (military) addresses cannot be validated.
	 *
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address: ?array, message: ?string, error_message: ?string}
	 */
	public function validate( array $address ): array {

		$result                     = array();
		$original_address           = $address;
		$result['original_address'] = $address;

		// Since USPS always returns the address in all-uppercase letters, let's convert the input to
		// all uppercase so we are later comparing like to like.
		$address = array_map( 'strtoupper', $address );

		// Remove plural 's.
		$address = str_replace( '\'S', 'S', $address );

		// Let's clean up punctuation which USPS does not like.
		// TODO: is "#" ok?
		// forward slash is ok, e.g. 123 1/2 22nd st.
		$address = preg_replace( '/[^a-zA-Z\d\s\/]+/', ' ', $address );
		// Trim and remove double spaces.
		$address = array_map( 'normalize_whitespace', $address );

		$address_to_validate = $this->wc_address_array_to_usps_address_object( $address );

		if ( in_array( $address['state'], array( 'AE', 'AP', 'AA' ), true ) ) {

			$result['success'] = true;

			$message = 'Military address – cannot be validated, assumed to be correct. Sanitized from: `' . implode( ', ', array_filter( $original_address ) ) . '` to: `' . implode( ', ', array_filter( $address ) ) . '`';

			$this->logger->debug( $message, $result );

			$result['message'] = $message;

			return $result;
		}

		// Add the address object to the address verify class
		$this->address_verify->addAddress( $address_to_validate );

		$this->add_address_common_mistakes( $address );

		// Perform the request and return result
		$xml_response   = $this->address_verify->verify();
		$array_response = $this->address_verify->convertResponseToArray();

		// See if it was successful
		// AddressVerify::isSuccess() will return false if any are errors.
		// success = true could still be everything error!
		$success = isset( $array_response['AddressValidateResponse'] ) && isset( $array_response['AddressValidateResponse']['Address'] );

		if ( ! $success ) {

			// TODO: Check the reason for failure... e.g. timeout rather than bad address.

			// "Multiple addresses were found" (not really here).
			// "Peer’s Certificate has expired."

			$result['success'] = false;

			$error_message = 'USPS Address Information API failed validation: ' . $this->address_verify->getErrorMessage() . "\n" . implode( ', ', array_filter( $original_address ) );

			$result['error_message'] = $error_message;

			$this->logger->error(
				$error_message,
				array(
					'request'        => $this->address_verify->getPostData(),
					'error_message'  => $this->address_verify->getErrorMessage(),
					'xml_response'   => $xml_response,
					'array_response' => $array_response,
					'address'        => $address,
				)
			);

			return $result;

		} else {

			$response_address = $array_response['AddressValidateResponse']['Address'];

			// If one address was returned.
			if ( isset( $response_address['@attributes'] ) ) {
				$returned_addresses = array( $response_address );
			} else {
				// If many were found, especially when common errors were found in the input.
				$returned_addresses = $response_address;
			}

			foreach ( $returned_addresses as $index => $usps_address ) {

				// If it's an error, continue.
				// Later, use $returned_addresses[0] for detailed error message if none found at all.
				if ( isset( $usps_address['Error'] ) ) {

					continue;

				} else {

					$updated_address = array();

					// WooCommerce address_1 is the building and street, whereas USPS Address1 is the apartment number.
					$updated_address['address_1'] = $usps_address['Address2'];
					$updated_address['address_2'] = isset( $usps_address['Address1'] ) ? $usps_address['Address1'] : '';
					$updated_address['city']      = $usps_address['City'];
					$updated_address['state']     = $usps_address['State'];
					$updated_address['country']   = 'US';

					$zip = $usps_address['Zip5'];

					// Not all addresses have a +4 zip code.
					if ( ! empty( $usps_address['Zip4'] ) ) {
						$zip .= '-' . $usps_address['Zip4'];
					}
					$updated_address['postcode'] = $zip;

					$result['updated_address'] = $updated_address;

					$result['success'] = true;

					$message = "Shipping address validated by USPS Address Verification API. \n\nOld address was: \n`" . implode( ", \n", array_filter( $original_address ) ) . "`. \n\nNew address is: \n`" . implode( ", \n", array_filter( $updated_address ) ) . '`.';

					$result['message'] = $message;

					$this->logger->debug( preg_replace( '/\n+/', '', $message ) );

					return $result;
				}

				// If it's a match, break.

				// TODO: This is a weird scenario, but it has happened.
				// TODO: This shouldn't be in here...
				if ( $address['state'] !== $usps_address['State'] ) {
					$error_message = 'State returned from USPS ' . $usps_address['State'] . ' did not match customer supplied state ' . strtoupper( $address['state'] );
					$this->logger->notice( $error_message, array( 'array_response' => $array_response ) );

					$result['error_message'] = $error_message;
					$result['success']       = false;

					return $result;
				}
			}

			// No valid address found.
			$usps_address = $returned_addresses[0];

			$message = $usps_address['Error']['Description'] . ' for address: `' . implode( ', ', array_filter( $original_address ) ) . '`.';

			$result['success'] = false;

			$this->logger->debug( $message, $result );

			$result['error_message'] = $message;

			return $result;

		}
	}


	/**
	 * Probably unnecessary to apply every combination of these?
	 *
	 * TODO: document if the supplied address is assumed to be uppercase
	 *
	 * @param array $address
	 *
	 * @return void
	 */
	protected function add_address_common_mistakes( array $address ) {

		// Switch line one and line two
		if ( ! empty( $address['address_1'] && ! empty( $address['address_2'] ) ) ) {
			$new_address              = $address;
			$new_address['address_1'] = $address['address_2'];
			$new_address['address_2'] = $address['address_1'];
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		$us_to_highway = preg_replace( '/(\d+\s)(US)(\s\d+.*)/', ' $1Highway$3', $address['address_1'] );
		if ( $us_to_highway !== $address['address_1'] ) {
			$new_address              = $address;
			$new_address['address_1'] = $us_to_highway;
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// Remove space in "W123 N123 ...".
		$remove_space = preg_replace( '/(\w\d+)\s(\w\d+.*)/', '$1$2', $address['address_1'] );
		if ( $remove_space !== $address['address_1'] ) {
			$new_address              = $address;
			$new_address['address_1'] = $remove_space;
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// Remove an accidental space in the street number, e.g. 13 12 63st -> 1312 63rd st.
		$remove_accidental_space = preg_replace( '/(\d+)\s(\d+\s.*)/', '$1$2', $address['address_1'] );
		if ( $remove_accidental_space !== $address['address_1'] ) {
			$new_address              = $address;
			$new_address['address_1'] = $remove_accidental_space;
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// Replace the word Interstate with just the initial I.
		if ( false !== strpos( $address['address_1'], 'INTERSTATE' ) ) {
			$new_address              = $address;
			$new_address['address_1'] = str_replace( 'INTERSTATE', 'I', $address['address_1'] );
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// Replace the word DRIVE with just DR.
		if ( false !== strpos( $address['address_1'], 'DRIVE' ) ) {
			$new_address              = $address;
			$new_address['address_1'] = str_replace( 'DRIVE', 'DR', $address['address_1'] );
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// If "1 APT" is written, swap it to be "APT 1".
		$num_apt_swap = preg_replace( '/(\d+)\s(.+)/', '$2 $1', $address['address_2'] );
		if ( $num_apt_swap !== $address['address_2'] ) {
			$new_address              = $address;
			$new_address['address_2'] = $num_apt_swap;
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}

		// Try merging address_1 and address_2, sometimes people write the house number on one line and the street on the second.
		$new_address              = $address;
		$new_address['address_1'] = $new_address['address_1'] . ' ' . $new_address['address_2'];
		$new_address['address_2'] = '';
		$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
		unset( $new_address );

		// Sometimes autofill fills the complete address on line one, then the rest of the address as normal.
		$address_parts = array( 'postcode', 'state', 'city', 'address_2', 'address_1' );
		$new_address   = $address;
		foreach ( $address_parts as $part_to_search ) {
			if ( empty( $part_to_search ) ) {
				continue;
			}
			foreach ( $address_parts as $part_to_edit ) {
				if ( $part_to_edit === $part_to_search ) {
					continue;
				}
				$new_address[ $part_to_edit ] = normalize_whitespace( preg_replace( '/\b' . preg_quote( $new_address[ $part_to_search ], '/' ) . '\b/', '', $new_address[ $part_to_edit ] ) );
			}
		}
		// If changes have been made, use the new address.
		if ( implode( '', $address ) !== implode( '', $new_address ) ) {
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
		}
		unset( $new_address );

		// If "RD" has been misspelled as "RE".
		$re_to_rd = preg_replace( '/.*\bRE\b/', '$1RD', $address['address_1'] );
		if ( $re_to_rd !== $address['address_1'] ) {
			$new_address              = $address;
			$new_address['address_1'] = $re_to_rd;
			$this->address_verify->addAddress( $this->wc_address_array_to_usps_address_object( $new_address ) );
			unset( $new_address );
		}
	}
}
