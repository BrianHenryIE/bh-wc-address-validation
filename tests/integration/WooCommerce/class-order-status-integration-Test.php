<?php

namespace BrianHenryIE\WC_Address_Validation\WooCommerce;

class Order_Status_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function test_order_status_added_to_reports_filter() {

		$result = apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) );

		$this->assertContains( 'bad-address', $result );

	}

}
