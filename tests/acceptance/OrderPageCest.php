<?php

class OrderPageCest {

	public function _before( AcceptanceTester $I ) {

		$I->loginAsAdmin();

	}

	/**
	 *
	 * @param AcceptanceTester $I
	 */
	public function testSetOrderToProcessing( AcceptanceTester $I ) {

		$order_id = 10;

		// Set the USPS username from the environmental variable.
		$usps_username = $_ENV['USPS_USERNAME'];
		$I->amOnPage( '/wp-admin/admin.php?page=wc-settings&tab=shipping&section=bh-wc-address-validation' );
		$I->fillField( 'bh-wc-address-validation-usps-username', $usps_username );

		$I->click( 'Save changes' );

		sleep( 0.5 );

		// An order.
		$I->amOnPage( "/wp-admin/post.php?post={$order_id}&action=edit" );

		$I->selectOption( '#order_status', 'Processing' );

		//
		// $I->makeHtmlSnapshot();

		$I->click( 'Update' );

		sleep( 2.5 );

		$I->amOnCronPage();

		sleep( 2.5 );

		$I->amOnPage( "/wp-admin/post.php?post={$order_id}&action=edit" );

		$I->canSeeOptionIsSelected( '#order_status', 'Bad Address' );
	}


	/**
	 * Check "Order Actions" contains the new action "Verify shipping address with USPS".
	 *
	 * @param AcceptanceTester $I
	 */
	public function testCheckAddressOptionExists( AcceptanceTester $I ) {

		$order_id = 10;

		$I->amOnPage( "/wp-admin/post.php?post={$order_id}&action=edit" );

		$I->selectOption( 'wc_order_action', 'bh_wc_address_validate' );
	}

}
