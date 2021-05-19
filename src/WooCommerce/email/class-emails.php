<?php

namespace BrianHenryIE\WC_Address_Validation\WooCommerce\Email;

use WC_Email;


class Emails {


	/**
	 * @hooked woocommerce_email_classes
	 * @see WC_Emails::init()
	 *
	 * @param WC_Email[] $emails
	 *
	 * @return WC_Email[]
	 */
	public function register_email( $emails ): array {

		$emails['Bad_Address'] = new Bad_Address_Email();

		return $emails;
	}
}
