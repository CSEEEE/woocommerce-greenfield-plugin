<?php
/**
 * Plugin Name:     ZEUSPay For Woocommerce V2
 * Plugin URI:      https://wordpress.org/plugins/zeuspay-for-woocommerce/
 * Description:     ZEUSPay is a bitcoin payment processor which allows you to receive payments in Bitcoin and altcoins directly, with no fees, transaction cost or a middleman.
 * Author:          ZEUSPay
 * Author URI:      https://zeuspay.com
 * Text Domain:     zeuspay-for-woocommerce
 * Domain Path:     /languages
 * Version:         1.0.2
 * Requires PHP:    7.4
 * Tested up to:    5.9
 * Requires at least: 5.2
 */

use BTCPayServer\WC\Gateway\DefaultGateway;
use BTCPayServer\WC\Gateway\SeparateGateways;
use BTCPayServer\WC\Helper\GreenfieldApiHelper;

defined( 'ABSPATH' ) || exit();

define( 'BTCPAYSERVER_VERSION', '1.0.2' );
define( 'BTCPAYSERVER_PLUGIN_FILE_PATH', plugin_dir_path( __FILE__ ) );
define( 'BTCPAYSERVER_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
define( 'BTCPAYSERVER_PLUGIN_ID', 'zeuspay-for-woocommerce' );

class BTCPayServerWCPlugin {

	private static $instance;

	public function __construct() {
		$this->includes();

		add_action('woocommerce_thankyou_btcpaygf_default', [$this, 'orderStatusThankYouPage'], 10, 1);

		if (is_admin()) {
			// Register our custom global settings page.
			add_filter(
				'woocommerce_get_settings_pages',
				function ($settings) {
					$settings[] = new \BTCPayServer\WC\Admin\GlobalSettings();

					return $settings;
				}
			);
			add_action( 'wp_ajax_handle_ajax_api_url', [$this, 'processAjaxApiUrl'] );

			$this->dependenciesNotification();
			$this->legacyPluginNotification();
			$this->notConfiguredNotification();
		}
	}

	public function includes(): void {
		$autoloader = BTCPAYSERVER_PLUGIN_FILE_PATH . 'vendor/autoload.php';
		if (file_exists($autoloader)) {
			/** @noinspection PhpIncludeInspection */
			require_once $autoloader;
		}

		if (get_option('btcpay_gf_separate_gateways') === 'yes' && is_dir(SeparateGateways::GENERATED_PATH)) {
			$generatedFiles = glob(SeparateGateways::GENERATED_PATH . DIRECTORY_SEPARATOR . GreenfieldApiHelper::PM_CLASS_NAME_PREFIX . '*.php');
			foreach($generatedFiles as $file) {
				require_once $file;
			}
		}

		// Make sure WP internal functions are available.
		if ( ! function_exists('is_plugin_active') ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	public static function initPaymentGateways($gateways): array {
		// We always load the default gateway that covers all payment methods available on ZEUSPay.
		$gateways[] = DefaultGateway::class;

		// Load payment methods from ZEUSPay as separate gateways.
		if (get_option('btcpay_gf_separate_gateways') === 'yes') {
			// Call init separate payment gateways here.
			if ($separateGateways = \BTCPayServer\WC\Helper\GreenfieldApiHelper::supportedPaymentMethods()) {

				\BTCPayServer\WC\Gateway\SeparateGateways::generateClasses();

				foreach ($separateGateways as $gw) {
					$gateways[] = $gw['className'];
					// Thank you page overrides.
					add_action('woocommerce_thankyou_btcpaygf_' . strtolower($gw['symbol']), ['BTCPayServerWCPlugin', 'orderStatusThankYouPage'], 10, 1);
				}
			}
		}

		return $gateways;
	}

	/**
	 * Displays notice (and link to config page) on admin dashboard if the plugin is not configured yet.
	 */
	public function notConfiguredNotification(): void {
		if (!\BTCPayServer\WC\Helper\GreenfieldApiHelper::getConfig()) {
			$message = sprintf(
				esc_html__(
					'Plugin not configured yet, please %1$sconfigure the plugin here%2$s',
					'zeuspay-for-woocommerce'
				),
				'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=btcpay_settings')) . '">',
				'</a>'
			);

			\BTCPayServer\WC\Admin\Notice::addNotice('error', $message);
		}
	}

	/**
	 * Checks and displays notice on admin dashboard if PHP version is too low or WooCommerce not installed.
	 */
	public function dependenciesNotification() {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			$versionMessage = sprintf( __( 'Your PHP version is %s but ZEUSPay Payment plugin requires version 7.4+.', 'zeuspay-for-woocommerce' ), PHP_VERSION );
			\BTCPayServer\WC\Admin\Notice::addNotice('error', $versionMessage);
		}

		// Check if WooCommerce is installed.
		if ( ! is_plugin_active('woocommerce/woocommerce.php') ) {
			$wcMessage = __('WooCommerce seems to be not installed. Make sure you do before you activate ZEUSPay Payment Gateway.', 'zeuspay-for-woocommerce');
			\BTCPayServer\WC\Admin\Notice::addNotice('error', $wcMessage);
		}

	}

	/**
	 * Checks and displays notice on admin dashboard if the legacy ZEUSPay plugin is installed.
	 */
	public function legacyPluginNotification() {
		if ( is_plugin_active('btcpay-for-woocommerce/class-wc-gateway-btcpay.php') ) {
			$legacyMessage = __('Seems you have the old ZEUSPay for WooCommerce plugin installed. While it should work it is strongly recommended to not run both versions but rely on the maintained version (ZEUSPay for WooCommerce).', 'zeuspay-for-woocommerce');
			\BTCPayServer\WC\Admin\Notice::addNotice('warning', $legacyMessage, true);
		}
	}

	/**
	 * Handles the AJAX callback from the GlobalSettings form. Unfortunately with namespaces it seems to not work
	 * to have this method on the GlobalSettings class. So keeping it here for the time being.
	 */
	public function processAjaxApiUrl() {
		$nonce = $_POST['apiNonce'];
		if ( ! wp_verify_nonce( $nonce, 'btcpaygf-api-url-nonce' ) ) {
			wp_die('Unauthorized!', '', ['response' => 401]);
		}

		if ( current_user_can( 'manage_options' ) ) {
			$host = filter_var($_POST['host'], FILTER_VALIDATE_URL);

			if ($host === false || (substr( $host, 0, 7 ) !== "http://" && substr( $host, 0, 8 ) !== "https://")) {
				wp_send_json_error("Error validating ZEUSPay URL.");
			}

			try {
				// Create the redirect url to BTCPay instance.
				$url = \BTCPayServer\Client\ApiKey::getAuthorizeUrl(
					$host,
					\BTCPayServer\WC\Helper\GreenfieldApiAuthorization::REQUIRED_PERMISSIONS,
					'WooCommerce',
					true,
					true,
					home_url('?btcpay-settings-callback'),
					null
				);

				// Store the host to options before we leave the site.
				update_option('btcpay_gf_url', $host);

				// Return the redirect url.
				wp_send_json_success(['url' => $url]);
			} catch (\Throwable $e) {
				\BTCPayServer\WC\Helper\Logger::debug('Error fetching redirect url from ZEUSPay.');
			}
		}

		wp_send_json_error("Error processing Ajax request.");
	}

	public function orderStatusThankYouPage($order_id)
	{
		if (!$order = wc_get_order($order_id)) {
			return;
		}

		$title = _x('Payment Status', 'zeuspay-for-woocommerce');

		$orderData = $order->get_data();
		$status = $orderData['status'];

		switch ($status)
		{
			case 'on-hold':
				$statusDesc = _x('Waiting for payment settlement', 'zeuspay-for-woocommerce');
				break;
			case 'processing':
				$statusDesc = _x('Payment processing', 'zeuspay-for-woocommerce');
				break;
			case 'completed':
				$statusDesc = _x('Payment settled', 'zeuspay-for-woocommerce');
				break;
			case 'failed':
				$statusDesc = _x('Payment failed', 'zeuspay-for-woocommerce');
				break;
			default:
				$statusDesc = _x(ucfirst($status), 'zeuspay-for-woocommerce');
				break;
		}

		echo "
		<section class='woocommerce-order-payment-status'>
		    <h2 class='woocommerce-order-payment-status-title'>{$title}</h2>
		    <p><strong>{$statusDesc}</strong></p>
		</section>
		";
	}

	/**
	 * Gets the main plugin loader instance.
	 *
	 * Ensures only one instance can be loaded.
	 */
	public static function instance(): \BTCPayServerWCPlugin {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

// Start everything up.
function init_btcpay_greenfield() {
	\BTCPayServerWCPlugin::instance();
}

/**
 * Bootstrap stuff on init.
 */
add_action('init', function() {
	// Adding textdomain and translation support.
	load_plugin_textdomain('zeuspay-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
	// Setting up and handling custom endpoint for api key redirect from BTCPay Server.
	add_rewrite_endpoint('btcpay-settings-callback', EP_ROOT);
	// Flush rewrite rules only once after activation.
	if( ! get_option('btcpaygf_permalinks_flushed') ) {
		flush_rewrite_rules(false);
		update_option('btcpaygf_permalinks_flushed', 1);
	}
});

// To be able to use the endpoint without appended url segments we need to do this.
add_filter('request', function($vars) {
	if (isset($vars['btcpay-settings-callback'])) {
		$vars['btcpay-settings-callback'] = true;
	}
	return $vars;
});

// Adding template redirect handling for btcpay-settings-callback.
add_action( 'template_redirect', function() {
	global $wp_query;

	// Only continue on a btcpay-settings-callback request.
	if (! isset( $wp_query->query_vars['btcpay-settings-callback'] ) ) {
		return;
	}

	$btcPaySettingsUrl = admin_url('admin.php?page=wc-settings&tab=btcpay_settings');

	$rawData = file_get_contents('php://input');
	$data = json_decode( $rawData, true );

	// Seems data does get submitted with url-encoded payload, so parse $_POST here.
	if (!empty($_POST)) {
		$data['apiKey'] = sanitize_html_class($_POST['apiKey'] ?? null);
		if (is_array($_POST['permissions'])) {
			foreach ($_POST['permissions'] as $key => $value) {
				$data['permissions'][$key] = sanitize_text_field($_POST['permissions'][$key] ?? null);
			}
		}
	}

	if (isset($data['apiKey']) && isset($data['permissions'])) {
		$apiData = new \BTCPayServer\WC\Helper\GreenfieldApiAuthorization($data);
		if ($apiData->hasSingleStore() && $apiData->hasRequiredPermissions()) {
			update_option('btcpay_gf_api_key', $apiData->getApiKey());
			update_option('btcpay_gf_store_id', $apiData->getStoreID());
			\BTCPayServer\WC\Admin\Notice::addNotice('success', __('Successfully received api key and store id from ZEUSPay API.', 'zeuspay-for-woocommerce'));
			wp_redirect($btcPaySettingsUrl);
		} else {
			\BTCPayServer\WC\Admin\Notice::addNotice('error', __('Please make sure you only select one store on the ZEUSPay API authorization page.', 'zeuspay-for-woocommerce'));
			wp_redirect($btcPaySettingsUrl);
		}
	}

	\BTCPayServer\WC\Admin\Notice::addNotice('error', __('Error processing the data from ZEUSPay. Please try again.', 'zeuspay-for-woocommerce'));
	wp_redirect($btcPaySettingsUrl);
});

// Installation routine.
register_activation_hook( __FILE__, function() {
	update_option('btcpaygf_permalinks_flushed', 0);
});

// Initialize payment gateways and plugin.
add_filter( 'woocommerce_payment_gateways', [ 'BTCPayServerWCPlugin', 'initPaymentGateways' ] );
add_action( 'plugins_loaded', 'init_btcpay_greenfield', 0 );
