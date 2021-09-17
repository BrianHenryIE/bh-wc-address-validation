<?php
/**
 * If there is no validator available for the address, return this exception.
 */

namespace BrianHenryIE\WC_Address_Validation\API\Validators;

class No_Validator_Exception extends \Exception {

	public function __construct( array $address, $message = '', $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->address = $address;
	}

	protected array $address;

	/**
	 * @return array
	 */
	public function get_address(): array {
		return $this->address;
	}
}
