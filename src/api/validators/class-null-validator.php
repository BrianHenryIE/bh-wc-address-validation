<?php
/**
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

use BrianHenryIE\WC_Address_Validation\API\Address_Validator_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Null_Validator implements Address_Validator_Interface {

	use LoggerAwareTrait;

	public function __construct( LoggerInterface $logger ) {

		$this->setLogger( $logger );
	}

	/**
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address: ?array, message: ?string, error_message: ?string}
	 */
	public function validate( array $address ): array {

		$result                     = array();
		$result['original_address'] = $address;
		$result['success']          = true;
		return $result;
	}
}
