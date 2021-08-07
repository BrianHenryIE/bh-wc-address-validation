<?php

namespace BrianHenryIE\WC_Address_Validation;

use BrianHenryIE\WC_Address_Validation\API\EasyPost_Address_Validator;
use BrianHenryIE\WC_Address_Validation\API\Settings_Interface;
use BrianHenryIE\WC_Address_Validation\API\USPS_Address_Validator;
use BrianHenryIE\WC_Address_Validation\USPS\AddressVerify;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Container implements ContainerInterface {

	use LoggerAwareTrait;

	protected Settings_Interface $settings;

	const USPS_ADDRESS_VALIDATOR     = 'usps_address_validator';
	const EASYPOST_ADDRESS_VALIDATOR = 'easypost_address_validator';

	const USPS_API_ADDRESS_VERIFY = 'usps_api_address_verify';

	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
	}

	/**
	 * Finds an entry of the container by its identifier and returns it.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 * @throws ContainerExceptionInterface Error while retrieving the entry.
	 *
	 * @throws NotFoundExceptionInterface  No entry was found for **this** identifier.
	 */
	public function get( $id ) {

		switch ( $id ) {
			case self::USPS_ADDRESS_VALIDATOR:
				return new USPS_Address_Validator( $this, $this->logger );

			case self::EASYPOST_ADDRESS_VALIDATOR:
				return new EasyPost_Address_Validator( $this->settings->get_easypost_api_key(), $this->logger );

			case self::USPS_API_ADDRESS_VERIFY:
				return new AddressVerify( $this->settings->get_usps_username() );
		}

	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
	 * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
	 *
	 * @param string $id Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( $id ) {
		// TODO: Implement has() method.
	}
}
