<?php


namespace BH_WC_Address_Validation\woocommerce\email;

use WC_Email;
use WC_Order;
use BH_WC_Address_Validation\woocommerce\Order_Status;

class Bad_Address_Email extends WC_Email {


	/**
	 * Create an instance of the class.
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {

		$this->id             = 'bad_address_admin';
		$this->title          = __( 'Bad Address', 'bh-wc-address-validation' );
		$this->description    = __( 'An email sent to the admin when an order\'s address is not recognized by USPS.', 'bh-wc-address-validation' );
		$this->template_base  = WP_PLUGIN_DIR . '/bh-wc-address-validation/woocommerce/templates/';
		$this->template_html  = 'emails/bad-address.php';
		$this->template_plain = 'emails/plain/bad-address.php';
		$this->placeholders   = array(
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Action to which we hook onto to send the email.
		add_action( 'woocommerce_order_status_' . Order_Status::BAD_ADDRESS_STATUS, array( $this, 'trigger' ) );

		parent::__construct();
	}


	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: Order #{order_number} has failed USPS address verification', 'bh-wc-address-validation' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Bad Address: #{order_number}', 'bh-wc-address-validation' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int            $order_id The order ID.
	 * @param WC_Order|false $order Order object.
	 */
	public function trigger( $order_id, $order = false ) {
		$this->setup_locale();

		if ( $order_id && ! is_a( $order, WC_Order::class ) ) {
			$order = wc_get_order( $order_id );
		}

		if ( is_a( $order, WC_Order::class ) ) {
			$this->object                         = $order;
			$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{order_number}'] = $this->object->get_order_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => false,
				'email'              => $this,
			)
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			array(
				'order'              => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => true,
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}

	/**
	 * Default content to show below main email content.
	 *
	 * @since 3.7.0
	 * @return string
	 */
	public function get_default_additional_content() {
		return __( 'The shipping address for this order needs to be corrected before the order can be processed. This can be done with <a target="_blank" href="https://tools.usps.com/zip-code-lookup.htm?byaddress">USPS Zip Code Lookup</a> or by contacting the customer.', 'bh-wc-address-validation' );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		/* translators: %s: list of placeholders */
		$placeholder_text  = sprintf( __( 'Available placeholders: %s', 'bh-wc-address-validation' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
		$this->form_fields = array(
			'enabled'            => array(
				'title'   => __( 'Enable/Disable', 'bh-wc-address-validation' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'bh-wc-address-validation' ),
				'default' => 'no',
			),
			'recipient'          => array(
				'title'       => __( 'Recipient(s)', 'bh-wc-address-validation' ),
				'type'        => 'text',
				/* translators: %s: WP admin email */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'bh-wc-address-validation' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'subject'            => array(
				'title'       => __( 'Subject', 'bh-wc-address-validation' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'            => array(
				'title'       => __( 'Email heading', 'bh-wc-address-validation' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => $placeholder_text,
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'additional_content' => array(
				'title'       => __( 'Additional content', 'bh-wc-address-validation' ),
				'description' => __( 'Text to appear below the main email content.', 'bh-wc-address-validation' ) . ' ' . $placeholder_text,
				'css'         => 'width:400px; height: 75px;',
				'placeholder' => __( 'N/A', 'bh-wc-address-validation' ),
				'type'        => 'textarea',
				'default'     => $this->get_default_additional_content(),
				'desc_tip'    => true,
			),
			'email_type'         => array(
				'title'       => __( 'Email type', 'bh-wc-address-validation' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'bh-wc-address-validation' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}

}
