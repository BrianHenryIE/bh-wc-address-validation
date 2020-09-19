<?php

namespace BH_WC_Address_Validation\woocommerce\email;

use WC_Email;
use BH_WC_Address_Validation\BrianHenryIE\WPPB\WPPB_Object;

class Emails extends WPPB_Object {


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
