<?php

namespace BrianHenryIE\WC_Address_Validation;

use WC_Order;

interface API_Interface {

	public function check_address_for_order( WC_Order $order, bool $is_manual ): void;

	public function recheck_bad_address_orders(): void;
}
