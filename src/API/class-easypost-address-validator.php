<?php
/**
 * @see https://www.easypost.com/address-verification-api
 * @see https://www.easypost.com/address-verification-guide
 * @see https://www.easypost.com/docs/api#addresses
 * @see https://www.easypost.com/docs/address-verification-by-country
 * @see https://github.com/easypost/easypost-php
 */

namespace BrianHenryIE\WC_Address_Validation\API;

use BrianHenryIE\WC_Address_Validation\EasyPost\Address;
use BrianHenryIE\WC_Address_Validation\EasyPost\EasyPost;
use Psr\Log\LoggerAwareTrait;

/**
 * Class EasyPost_Address_Validator
 * @package BrianHenryIE\WC_Address_Validation\API
 */
class EasyPost_Address_Validator implements Address_Validator_Interface {

	use LoggerAwareTrait;

	public function __construct( $api_key, $logger ) {
		$this->setLogger( $logger );

		EasyPost::setApiKey( $api_key );

	}

	/**
	 *
	 *
	 *
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address?: array, message?: string, error_message?: string}
	 */
	public function validate( array $address ): array {

		$easypost_address_array = array(
			'street1' => $address['address_1'],
			'street2' => $address['address_2'],
			'city'    => $address['city'],
			'state'   => $address['state'],
			'zip'     => $address['postcode'],
			'country' => $address['country'],
		);

		try {
			/** @var Address $verified */
			$verified = Address::create_and_verify( $easypost_address_array );

		} catch ( \Exception $e ) {
			$result['success']       = false;
			$result['error_message'] = 'fail!';

			$this->logger->info( 'error' );

			return $result;
		}

		$result['success'] = true;

		$updated_address = array(
			'address_1' => $verified['street1'],
			'address_2' => $verified['street2'],
			'city'      => $verified['city'],
			'state'     => $verified['state'],
			'postcode'  => $verified['zip'],
			'country'   => $verified['country'],

		);

		$result['updated_address'] = $updated_address;

		$result['message'] = '';

		return $result;

	}
}
