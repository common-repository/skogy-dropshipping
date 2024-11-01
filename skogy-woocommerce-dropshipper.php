<?PHP
/**
 * Plugin Name: Skogy Dropshipping
 * Plugin URI: 
 * Description: Dropship products
 * Author: Skogy
 * Author URI: https://www.skogy.com/
 * Version: 1.0.0
 * WC requires at least: 2.6.0
 * WC tested up to: 3.3.1
 *
 * Copyright: (c) 2018 Skogy. (info@skogy.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     skogy-woocomomerce
 * @author      Skogy
 * @Category    Plugin
 * @copyright   Copyright (c) 2018 Skogy
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
	if ( !function_exists( 'add_action' ) ) {
		echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
		exit;
	}

	define( 'SKOGY_WOOCOMMERCE_DROPSHIPPER_VERSION', '1.0.0' );
	define( 'SKOGY_WOOCOMMERCE_DROPSHIPPER__MINIMUM_WP_VERSION', '4.0' );
	define( 'SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
	define( 'SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_URL', plugin_dir_url( __FILE__ ) );

	if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	    // Put your plugin code here

		require_once( SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_DIR . 'Skogy_Woocommerce_Dropshipper_Admin.php' );
		add_action( 'init', array( 'Skogy_Woocommerce_Dropshipper_Admin', 'init' ) );
		register_activation_hook( __FILE__, array('Skogy_Woocommerce_Dropshipper_Admin','install') );
		register_deactivation_hook( __FILE__, array('Skogy_Woocommerce_Dropshipper_Admin','uninstall') );
	}
?>