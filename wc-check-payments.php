<?php
/**
 * Plugin Name: WC Check Payments
 * Plugin URI: https://rayflores.com
 * Description: A simple plugin to add check payments to WooCommerce.
 * Version: 1.0.2
 * Author: Ray Flores
 * Author URI: https://rayflores.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-check-payments
 *
 * @package WC_Check_Payments
 */

/**
 * Add a check payment metabox to WooCommerce Edit Order Screen.
 */
function wc_activation_hook() {
	if ( ! class_exists( 'WC_Check_Payments' ) ) {
		include_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-check-payments.php';
	}
	$wc_check_payments = new WC_Check_Payments();
	$wc_check_payments::get_instance();
}
register_activation_hook( __FILE__, 'wc_activation_hook' );

/**
 * Version check.
 */
if ( is_admin() ) {
	define( 'GH_REQUEST_URI', 'https://api.github.com/repos/%s/%s/releases' );
	define( 'GHPU_USERNAME', 'rayflores' );
	define( 'GHPU_REPOSITORY', 'wc-check-payments' );
	define( 'GHPU_AUTH_TOKEN', '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' );

	include_once plugin_dir_path( __FILE__ ) . '/ghpluginupdater.php';

	$updater = new GhPluginUpdater( __FILE__ );
	$updater->init();
}
