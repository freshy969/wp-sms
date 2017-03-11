<?php

class cheapsms extends WP_SMS
{
	private $wsdl_link = "http://198.24.149.4/API";
	public $tariff = "http://www.cheapsms.com/";
	public $unitrial = true;
	public $unit;
	public $flash = "disable";
	public $isflash = false;

	public function __construct()
	{
		parent::__construct();
		$this->validateNumber = "e.g. 9029963999";
		$this->help = 'Please enter Route ID in API Key field';
		$this->has_key = true;
	}

	public function SendSMS()
	{
		// Check gateway credit
		if (is_wp_error($this->GetCredit())) {
			return new WP_Error('account-credit', __('Your account does not credit for sending sms.', 'wp-sms-pro'));
		}

		/**
		 * Modify sender number
		 *
		 * @since 3.4
		 * @param string $this ->from sender number.
		 */
		$this->from = apply_filters('wp_sms_from', $this->from);

		/**
		 * Modify Receiver number
		 *
		 * @since 3.4
		 * @param array $this ->to receiver number
		 */
		$this->to = apply_filters('wp_sms_to', $this->to);

		/**
		 * Modify text message
		 *
		 * @since 3.4
		 * @param string $this ->msg text message.
		 */
		$this->msg = apply_filters('wp_sms_msg', $this->msg);

		$response = wp_remote_get($this->wsdl_link . "/pushsms.aspx?loginID=" . $this->username . "&password=" . $this->password . "&mobile=" . implode(',', $this->to) . "&text=" . urlencode($this->msg) . "&senderid=" . $this->from . "&route_id=" . $this->has_key . "&Unicode=1");

		// Check gateway credit
		if (is_wp_error($response)) {
			return new WP_Error('send-sms', $response->get_error_message());
		}

		// Ger response code
		$response_code = wp_remote_retrieve_response_code($response);

		// Check response code
		if ($response_code == '200') {
			$json = json_decode($response['body']);

			if ($json->MsgStatus == 'Sent') {
				$this->InsertToDB($this->from, $this->msg, $this->to);

				/**
				 * Run hook after send sms.
				 *
				 * @since 2.4
				 * @param string $response result output.
				 */
				do_action('wp_sms_send', $response);

				return $response;
			} else {
				return new WP_Error('send-sms', $json->MsgStatus);
			}

		} else {
			return new WP_Error('send-sms', $response['body']);
		}
	}

	public function GetCredit()
	{
		// Check username and password
		if (!$this->username or !$this->password) {
			return new WP_Error('account-credit', __('Username/Password does not set for this gateway', 'wp-sms-pro'));
		}

		return true;
	}
}