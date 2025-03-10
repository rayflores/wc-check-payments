<?php
/**
 * Plugin Name: WC Check Payments
 * Plugin URI: https://rayflores.com
 * Description: A simple plugin to add check payments to WooCommerce.
 * Version: 1.0
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
	$wc_check_payments = new WC_Check_Payments();
	$wc_check_payments->init();

	if ( is_admin() ) {
		define( 'GH_REQUEST_URI', 'https://api.github.com/repos/%s/%s/releases' );
		define( 'GHPU_USERNAME', 'YOUR_GITHUB_USERNAME' );
		define( 'GHPU_REPOSITORY', 'YOUR_GITHUB_REPOSITORY_NAME' );
		define( 'GHPU_AUTH_TOKEN', 'YOUR_GITHUB_ACCESS_TOKEN' );

		include_once plugin_dir_path( __FILE__ ) . '/ghpluginupdater.php';

		$updater = new GhPluginUpdater( __FILE__ );
		$updater->init();
	}
}
