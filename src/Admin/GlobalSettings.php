<?php

declare(strict_types=1);

namespace BTCPayServer\WC\Admin;

use BTCPayServer\Client\ApiKey;
use BTCPayServer\Client\StorePaymentMethod;
use BTCPayServer\WC\Gateway\SeparateGateways;
use BTCPayServer\WC\Helper\GreenfieldApiAuthorization;
use BTCPayServer\WC\Helper\GreenfieldApiHelper;
use BTCPayServer\WC\Helper\GreenfieldApiWebhook;
use BTCPayServer\WC\Helper\Logger;
use BTCPayServer\WC\Helper\OrderStates;

/**
 * todo: add validation of host/url
 */
class GlobalSettings extends \WC_Settings_Page {

	public function __construct()
	{
		$this->id = 'btcpay_settings';
		$this->label = __( 'ZEUSPay Settings', 'zeuspay-for-woocommerce' );
		// Register custom field type order_states with OrderStatesField class.
		add_action('woocommerce_admin_field_order_states', [(new OrderStates()), 'renderOrderStatesHtml']);

		if (is_admin()) {
			// Register and include JS.
			wp_register_script('btcpay_gf_global_settings', BTCPAYSERVER_PLUGIN_URL . 'assets/js/apiKeyRedirect.js', ['jquery'], BTCPAYSERVER_VERSION);
			wp_enqueue_script('btcpay_gf_global_settings');
			wp_localize_script( 'btcpay_gf_global_settings',
				'BTCPayGlobalSettings',
				[
					'url' => admin_url( 'admin-ajax.php' ),
					'apiNonce' => wp_create_nonce( 'btcpaygf-api-url-nonce' ),
				]);
		}
		parent::__construct();
	}

	public function output(): void
	{
		$settings = $this->get_settings_for_default_section();
		\WC_Admin_Settings::output_fields($settings);
	}

	public function get_settings_for_default_section(): array
	{
		return $this->getGlobalSettings();
	}

	public function getGlobalSettings(): array
	{
		Logger::debug('Entering Global Settings form.');
		return [
			'title'                 => [
				'title' => esc_html_x(
					'ZEUSPay Payments Settings',
					'global_settings',
					'zeuspay-for-woocommerce'
				),
				'type'        => 'title',
				'desc' => sprintf( _x( 'This plugin version is %s and your PHP version is %s. If you need assistance, please come on our chat <a href="https://chat.zeuspay.com" target="_blank">https://chat.zeuspay.com</a>. Thank you for using ZEUSPay!', 'global_settings', 'btcpay-greenfield-for-woocommerce' ), BTCPAYSERVER_VERSION, PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ),
				'id' => 'btcpay_gf'
			],
			'url'                      => [
				'title'       => esc_html_x(
					'ZEUSPay URL',
					'global_settings',
					'zeuspay-for-woocommerce'
				),
				'type'        => 'text',
				'desc' => esc_html_x( 'URL/host to your ZEUSPay instance. Note: if you use a self hosted node like Umbrel, RaspiBlitz, myNode, etc. you will have to make sure your node is reachable from the internet. One option is through Tor, see <a href="https://docs.zeuspay.com/Deployment/ReverseProxyToTor/" target="_blank">here</a>.', 'global_settings', 'zeuspay-for-woocommerce' ),
				'placeholder' => esc_attr_x( 'e.g. https://zeuspay.example.com', 'global_settings', 'zeuspay-for-woocommerce' ),
				'desc_tip'    => true,
				'id' => 'btcpay_gf_url'
			],
			'api_key'                  => [
				'title'       => esc_html_x( 'ZEUSPay API Key', 'global_settings','zeuspay-for-woocommerce' ),
				'type'        => 'text',
				'desc' => _x( 'Your ZEUSPay API Key. If you do not have any yet <a href="#" class="zeuspay-api-key-link" target="_blank">click here to generate API keys.</a>', 'global_settings', 'zeuspay-for-woocommerce' ),
				'default'     => '',
				'id' => 'btcpay_gf_api_key'
			],
			'store_id'                  => [
				'title'       => esc_html_x( 'Store ID', 'global_settings','zeuspay-for-woocommerce' ),
				'type'        => 'text',
				'desc_tip' => _x( 'Your ZEUSPay Store ID. You can find it on the store settings page on your ZEUSPay.', 'global_settings', 'zeuspay-for-woocommerce' ),
				'default'     => '',
				'id' => 'btcpay_gf_store_id'
			],
			'default_description'                     => [
				'title'       => esc_html_x( 'Default Customer Message', 'zeuspay-for-woocommerce' ),
				'type'        => 'textarea',
				'desc' => esc_html_x( 'Message to explain how the customer will be paying for the purchase. Can be overwritten on a per gateway basis.', 'zeuspay-for-woocommerce' ),
				'default'     => esc_html_x('You will be redirected to ZEUSPay to complete your purchase.', 'global_settings', 'zeuspay-for-woocommerce'),
				'desc_tip'    => true,
				'id' => 'btcpay_gf_default_description'
			],
			'transaction_speed'               => [
				'title'       => esc_html_x( 'Invoice pass to "settled" state after', 'zeuspay-for-woocommerce' ),
				'type'        => 'select',
				'desc' => esc_html_x('An invoice becomes settled after the payment has this many confirmations...', 'global_settings', 'zeuspay-for-woocommerce'),
				'options'     => [
					'default'    => _x('Keep ZEUSPay store level configuration', 'global_settings', 'zeuspay-for-woocommerce'),
					'high'       => _x('0 confirmation on-chain', 'global_settings', 'zeuspay-for-woocommerce'),
					'medium'     => _x('1 confirmation on-chain', 'global_settings', 'zeuspay-for-woocommerce'),
					'low-medium' => _x('2 confirmations on-chain', 'global_settings', 'zeuspay-for-woocommerce'),
					'low'        => _x('6 confirmations on-chain', 'global_settings', 'zeuspay-for-woocommerce'),
				],
				'default'     => 'default',
				'desc_tip'    => true,
				'id' => 'btcpay_gf_transaction_speed'
			],
			'order_states'                    => [
				'type' => 'order_states',
				'id' => 'btcpay_gf_order_states'
			],
			'separate_gateways'                           => [
				'title'       => __( 'Separate Payment Gateways', 'zeuspay-for-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'desc' => _x( 'Make all supported and enabled payment methods available as their own payment gateway. This opens new possibilities like discounts for specific payment methods. See our <a href="https://docs.zeuspay.com/FAQ/Integrations/#how-to-configure-additional-token-support-separate-payment-gateways" target="_blank">full guide here</a>', 'global_settings', 'zeuspay-for-woocommerce' ),
				'id' => 'btcpay_gf_separate_gateways'
			],
			'customer_data'                           => [
				'title'       => __( 'Send customer data to ZEUSPay', 'zeuspay-for-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'desc' =>  _x( 'If you want customer email, address, etc. sent to ZEUSPay enable this option. By default for privacy and GDPR reasons this is disabled.', 'global_settings', 'zeuspay-for-woocommerce' ),
				'id' => 'btcpay_gf_send_customer_data'
			],
			'debug'                           => [
				'title'       => __( 'Debug Log', 'zeuspay-for-woocommerce' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'desc'        => sprintf( _x( 'Enable logging <a href="%s" class="button">View Logs</a>', 'global_settings', 'zeuspay-for-woocommerce' ), Logger::getLogFileUrl()),
				'id' => 'btcpay_gf_debug'
			],
			// todo: not sure if callback and redirect url should be overridable; can be done via woocommerce hooks if
			// needed but no common use case for 99%
			/*
			'notification_url'                => [
				'title'       => esc_html_x( 'Notification URL', 'global_settings', 'zeuspay-for-woocommerce' ),
				'type'        => 'url',
				'desc' => __( 'ZEUSPay will send IPNs for orders to this URL with the ZEUSPay invoice data', 'zeuspay-for-woocommerce' ),
				'default'     => '',
				'placeholder' => WC()->api_request_url( 'BTCPayServer_WC_Gateway_Default' ),
				'desc_tip'    => true,
				'id' => 'btcpay_gf_notification_url'
			],
			'redirect_url'                    => [
				'title'       => __( 'Redirect URL', 'zeuspay-for-woocommerce' ),
				'type'        => 'url',
				'desc' => __( 'After paying the ZEUSPay invoice, users will be redirected back to this URL', 'zeuspay-for-woocommerce' ),
				'default'     => '',
				'placeholder' => '', $this->get_return_url(),
				'desc_tip'    => true,
				'id' => 'btcpay_gf_redirect_url'
			],
			*/
			'sectionend' => [
				'type' => 'sectionend',
				'id' => 'btcpay_gf',
			],
		];
	}

	/**
	 * On saving the settings form make sure to check if the API key works and register a webhook if needed.
	 */
	public function save() {
		// If we have url, storeID and apiKey we want to check if the api key works and register a webhook.
		Logger::debug('Saving GlobalSettings.');
		if ( $this->hasNeededApiCredentials() ) {
			// Check if api key works for this store.
			$apiUrl  = esc_url_raw( $_POST['btcpay_gf_url'] );
			$apiKey  = sanitize_text_field( $_POST['btcpay_gf_api_key'] );
			$storeId = sanitize_text_field( $_POST['btcpay_gf_store_id'] );

			// todo: fix change of url + key + storeid not leading to recreation of webhook.
			if ( GreenfieldApiHelper::apiCredentialsExist($apiUrl, $apiKey, $storeId) ) {
				// Check if the provided API key has the right scope and permissions.
				try {
					$apiClient  = new ApiKey( $apiUrl, $apiKey );
					$apiKeyData = $apiClient->getCurrent();
					$apiAuth    = new GreenfieldApiAuthorization( $apiKeyData->getData() );
					$hasError   = false;

					if ( ! $apiAuth->hasSingleStore() ) {
						$messageSingleStore = __( 'The provided API key scope is valid for multiple stores, please make sure to create one for a single store.', 'zeuspay-for-woocommerce' );
						Notice::addNotice('error', $messageSingleStore );
						Logger::debug($messageSingleStore, true);
						$hasError = true;
					}

					if ( ! $apiAuth->hasRequiredPermissions() ) {
						$messagePermissionsError = sprintf(
							__( 'The provided API key does not match the required permissions. Please make sure the following permissions are are given: %s', 'zeuspay-for-woocommerce' ),
							implode( ', ', GreenfieldApiAuthorization::REQUIRED_PERMISSIONS )
						);
						Notice::addNotice('error', $messagePermissionsError );
						Logger::debug( $messagePermissionsError, true );
					}

					// Check if a webhook for our callback url exists.
					if ( false === $hasError ) {
						// Check if we already have a webhook registered for that store.
						if ( GreenfieldApiWebhook::webhookExists( $apiUrl, $apiKey, $storeId ) ) {
							$messageReuseWebhook = __( 'Reusing existing webhook.', 'zeuspay-for-woocommerce' );
							Notice::addNotice('info', $messageReuseWebhook, true);
							Logger::debug($messageReuseWebhook);
						} else {
							// Register a new webhook.
							if ( GreenfieldApiWebhook::registerWebhook( $apiUrl, $apiKey, $storeId ) ) {
								$messageWebhookSuccess = __( 'Successfully registered a new webhook on ZEUSPay.', 'zeuspay-for-woocommerce' );
								Notice::addNotice('success', $messageWebhookSuccess, true );
								Logger::debug( $messageWebhookSuccess );
							} else {
								$messageWebhookError = __( 'Could not register a new webhook on the store.', 'zeuspay-for-woocommerce' );
								Notice::addNotice('error', $messageWebhookError );
								Logger::debug($messageWebhookError, true);
							}
						}

						// Make sure there is at least one payment method configured.
						try {
							$pmClient = new StorePaymentMethod( $apiUrl, $apiKey );
							if (($pmClient->getPaymentMethods($storeId)) === []) {
								$messagePaymentMethodsError = __( 'No wallet configured on your ZEUSPay store settings. Make sure to add at least one otherwise this plugin will not work.', 'zeuspay-for-woocommerce' );
								Notice::addNotice('error', $messagePaymentMethodsError );
								Logger::debug($messagePaymentMethodsError, true);
							}
						} catch (\Throwable $e) {
							Logger::debug('Error loading wallet information (payment methods) from ZEUSPay.');
						}
					}
				} catch ( \Throwable $e ) {
					$messageException = sprintf(
						__( 'Error fetching data for this API key from server. Please check if the key is valid. Error: %s', 'zeuspay-for-woocommerce' ),
						$e->getMessage()
					);
					Notice::addNotice('error', $messageException );
					Logger::debug($messageException, true);
				}

			}
		} else {
			$messageNotConnecting = 'Did not try to connect to ZEUSPay API because one of the required information was missing: URL, key or storeID';
			Notice::addNotice('warning', $messageNotConnecting);
			Logger::debug($messageNotConnecting);
		}

		parent::save();

		// Purge separate payment methods cache.
		SeparateGateways::cleanUpGeneratedFilesAndCache();
		GreenfieldApiHelper::clearSupportedPaymentMethodsCache();
	}

	private function hasNeededApiCredentials(): bool {
		if (
			!empty($_POST['btcpay_gf_url']) &&
			!empty($_POST['btcpay_gf_api_key']) &&
			!empty($_POST['btcpay_gf_store_id'])
		) {
			return true;
		}
		return false;
	}
}
