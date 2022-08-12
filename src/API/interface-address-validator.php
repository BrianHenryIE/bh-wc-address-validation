<?php

namespace BrianHenryIE\WC_Address_Validation\API;

interface Address_Validator_Interface {

	/**
	 *
	 *
	 *
	 * @param array{address_1: string, address_2: string, city: string, state: string, postcode: string, country: string} $address
	 * @return array{success: bool, original_address: array, updated_address: ?array, message: ?string, error_message: ?string}
	 */
	public function validate( array $address ): array;

}
