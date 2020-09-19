<?php

namespace BH_WC_Address_Validation\api;

interface Settings_Interface {

	public function get_usps_username(): ?string;

	public function is_logging_enabled(): bool;

	public function is_admin_email_enabled(): bool;
}
