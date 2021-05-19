<?php

namespace BrianHenryIE\WC_Address_Validation\API;

interface API_Interface {

	public function check_address_for_order( $order, $is_manual );

	public function recheck_bad_address_orders(): void;
}
