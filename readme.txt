=== ZEUSPay for WooCommerce V2 ===
Contributors: ndeet, kukks, nicolasdorier
Donate link: https://zeuspay.com/
Tags: zeuspay, ZEUSPay, zeus, bitcoin, btcpay, BTCPay Server, btcpayserver, WooCommerce, payment gateway, accept bitcoin, bitcoin plugin, bitcoin payment processor, bitcoin e-commerce, Lightning Network, Litecoin, cryptocurrency
Requires at least: 5.2
Tested up to: 5.9
Requires PHP: 7.4
Stable tag: 1.0.2
License: MIT
License URI: https://github.com/zeuspayments/woocommerce-greenfield-plugin/blob/master/license.txt

ZEUSPay is a bitcoin payment processor which allows you to receive payments in Bitcoin and altcoins directly, with no fees, transaction cost or a middleman.

== Description ==

ZEUSPay is a cryptocurrency payment processor which allows you to receive payments in Bitcoin and altcoins directly, with no fees, transaction cost or a middleman.

ZEUSPay is a non-custodial invoicing system which eliminates the involvement of a third-party. Payments with ZEUSPay WooCommerce Plugin go directly to your wallet, which increases the privacy and security. Your private keys are never uploaded to the server. There is no address re-use since each invoice generates a new address deriving from your xPub key.

The solution allows you not only to attach an unlimited number of stores and use the Lightning Network but also become the payment processor for others.

* Direct, peer-to-peer Bitcoin and altcoin payments
* No transaction fees (other than mining fees by cryptocurrency network itself)
* No processing fees
* No middleman
* No KYC
* User has complete control over private keys
* Enhanced privacy (no address re-use, no IP leaks to third parties)
* Enhanced security
* SegWit, Taproot support
* Lightning Network support (LND, c-lightning and Eclair)
* Altcoin support
* Attach unlimited stores, process payments for friends
* Easy-embeddable Payment buttons
* Point of Sale app

== Installation ==

This plugin requires WooCommerce. Please make sure you have WooCommerce installed.

To integrate ZEUSPay into an existing WooCommerce store, follow the steps below or check our official [installation instructions](https://docs.zeuspay.com/WooCommerce/).

### 1. Deploy ZEUSPay (optional) ###

This step is optional, if you already have a ZEUSPay instance setup you can skip to section 2. below.

### 2. Install ZEUSPay WooCommerce Plugin ###

ZEUSPay WooCommerce plugin is a bridge between your ZEUSPay (payment processor) and your e-commerce store.

You can find detailed installation instructions on our [WooCommerce documentation](https://docs.zeuspay.com/WooCommerce/).

###  3. Connecting your wallet ###

### 4. Testing the checkout ###

Making a small test-purchase from your own store, will give you a piece of mind. Always make sure that everything is set up correctly before going live. The final video, guides you through the steps of setting a gap limit in your Electrum wallet and testing the checkout process.

Depending on your business model and store settings, you may want to fine tune [your order statuses](https://docs.zeuspay.com/WooCommerce/#41-global-settings).

== Frequently Asked Questions ==

You'll find extensive documentation and answers to many of your questions on [ZEUSPay for WooCommerce V2 docs](https://docs.zeuspay.com/WooCommerce) and on [ZEUSPay for WooCommerce integrations FAQ](https://docs.zeuspay.com/FAQ/Integrations/#woocommerce-faq).

== Screenshots ==

1. The ZEUSPay invoice. Your customers will see this at the checkout. They can pay from their wallet by scanning a QR or copy/pasting it manually into the wallet.
2. Customizable plugin interface allows store owners to adjust store statuses according to their needs.
3. Customer will see the pay with Bitcoin button at the checkout.Text can be customized.
4. Example of successfully paid invoice.
5. Example of an easy-embeddable HTML donation payment button.
6. Example of the PoS app you can launch.

== Changelog ==
= 1.0.2 :: 2022-04-08 =
* Fix plugin meta docblock version update, pump version once more.

= 1.0.1 :: 2022-04-08 =
* Fix bug if the custom uploaded payment gateway icon is deleted from filesystem.
* Added information about Tor proxy for Umbrel and other self-hosted nodes to ZEUSPay settings page.

= 1.0.0 :: 2022-03-27 =
* Reflect stability with release 1.0.0.
* Create a new invoice (and mark the old invalid) if the user uses browser back button and changes the payment method (relevant for separate payment gateway feature).
* Added plugin loader singleton.
* Added missing docs link to separate payment gateways feature.
* Added checkbox to enable/disable gateway from within gateway settings.
* Updated README.md

= 0.2.5 :: 2022-03-13 =
*  Load media library and JS only on payment gateway settings page.

= 0.2.4 :: 2022-03-04 =
* Fix possible problem with CamelCased headers on PHP-FPM and/or Nginx.
* Do not log hitting the cache on debug log to avoid clutter.

= 0.2.3 :: 2022-02-28 =
* Adding irrelevant GitHub workflow files to .distignore.
* Updating installation instructions with new material.

= 0.2.2 :: 2022-02-28 =
* Fix fatal error, make sure is_plugin_active() is available.

= 0.2.1 :: 2022-02-21 =
* Replace SVG by PNG logo to avoid scaling it on themes without proper CSS rules for payment gateway icons.

= 0.2.0 :: 2022-02-18 =
* Fix Cash on delivery, Bank transfer gateways missing after plugin activation.

= 0.1.10 :: 2022-02-15 =
* Make sure custom endpoint works without nice url enabled.
* Better description for setting.
* Update translation strings.

= 0.1.9 :: 2022-02-08 =
* Make sure custom endpoint works by flushing rewrite rules on plugin activation.
* Replacing usage of WC_Admin_Settings::addMessage() with our own.

= 0.1.1 :: 2022-01-13 =
* Admin notice if legacy plugin is installed
* Admin notice on missing WooCommerce / PHP version lower 7.4
* Minor changes metadata / readme.txt

= 0.1.0 :: 2022-01-13 =
* First public release for testing.
