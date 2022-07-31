<?php

namespace BTCPayServer\WC\Gateway;

/**
 * Default Gateway that provides all available payment methods of ZEUSPay store configuration.
 */
class DefaultGateway extends AbstractGateway {

	public function __construct() {
		// Set the id first.
		$this->id                 = 'btcpaygf_default';

		// Call parent constructor.
		parent::__construct();

		// todo: maybe make the button text configurable via settings.
		// General gateway setup.
		$this->order_button_text  = __('Proceed to ZEUSPay', 'zeuspay-for-woocommerce');
		// Admin facing title and description.
		$this->method_title       = 'ZEUSPay (default)';
		$this->method_description = __('ZEUSPay default gateway supporting all available tokens on your ZEUSPay store.', 'zeuspay-for-woocommerce');

		// Actions.
		add_action('woocommerce_api_btcpaygf_default', [$this, 'processWebhook']);
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->get_option('title', 'ZEUSPay (Bitcoin, Lightning Network, ...)');
	}

	/**
	 * @inheritDoc
	 */
	public function getDescription(): string {
		return $this->get_option('description', 'You will be redirected to ZEUSPay to complete your purchase.');
	}

	/**
	 * @inheritDoc
	 */
	public function init_form_fields(): void {
		parent::init_form_fields();
		$this->form_fields += [
			'enforce_payment_tokens' => [
				'title'       => __( 'Enforce payment tokens', 'zeuspay-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enforce payment methods "payment". This way tokens of type promotion will be excluded for this gateway.', 'zeuspay-for-woocommerce' ),
				'default'     => 'yes',
				'value'       => 'yes',
				'description' => __( 'This will override the default ZEUSPay payment method (defaults to all supported by ZEUSPay) and enforce to tokens of type "payment". This is useful if you have enabled separate payment gateways and want full control on what is available on ZEUSPay payment page.', 'zeuspay-for-woocommerce' ),
				'desc_tip'    => true,
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getPaymentMethods(): array {

		$btcPayPaymentGW = [];

		if ($this->get_option('enforce_payment_tokens') === 'yes') {
			$gateways = WC()->payment_gateways->payment_gateways();
			/** @var  $gateway AbstractGateway */
			foreach ($gateways as $id => $gateway) {
				if (
					strpos($id, 'btcpaygf') !== FALSE
					&& (isset($gateway->tokenType) && $gateway->tokenType === 'payment')
				) {
					$btcPayPaymentGW[] = $gateway->primaryPaymentMethod;
				}
			}
			return $btcPayPaymentGW;
		}

		// If payment tokens are not enforced set all.
		$separateGateways = \BTCPayServer\WC\Helper\GreenfieldApiHelper::supportedPaymentMethods();
		foreach ($separateGateways as $sgw) {
			$btcPayPaymentGW[] = $sgw['symbol'];
		}

		return $btcPayPaymentGW;
	}

}
