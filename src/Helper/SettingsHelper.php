<?php

namespace BTCPayServer\WC\Helper;

class SettingsHelper {
	public function gatewayFormFields(
		$defaultTitle,
		$defaultDescription
	) {
		$this->form_fields = [
			'title' => [
				'title'       => __('Title', 'zeuspay-for-woocommerce'),
				'type'        => 'text',
				'description' => __('Controls the name of this payment method as displayed to the customer during checkout.', 'zeuspay-for-woocommerce'),
				'default'     => __('ZEUSPay (Bitcoin, Lightning Network, ...)', 'zeuspay-for-woocommerce'),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __('Customer Message', 'zeuspay-for-woocommerce'),
				'type'        => 'textarea',
				'description' => __('Message to explain how the customer will be paying for the purchase.', 'zeuspay-for-woocommerce'),
				'default'     => 'You will be redirected to ZEUSPay to complete your purchase.',
				'desc_tip'    => true,
			],
		];

		return $this->form_fields;
	}
}
