<?php

class SettingsPageCest {

	public function _before( AcceptanceTester $I ) {
	}

	/**
	 *
	 * @param AcceptanceTester $I
	 */
	public function testEmailSettingsPageShowsLinkToThisSettingsPage( AcceptanceTester $I ) {

		$I->loginAsAdmin();

		$I->amOnPage( '/wp-admin/admin.php?page=wc-settings&tab=shipping' );

		$I->canSee( 'Address Validation' );
	}


	// Settings page should have USPS username input
	// Settings page should have 'enable logs' dropdown
	// Settings page should have 'enable email' checkbox
}
