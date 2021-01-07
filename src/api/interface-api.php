<?php

namespace BH_WC_Address_Validation\api;

interface API_Interface {

	public function check_address_for_order( $order, $is_manual );
}
