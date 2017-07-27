<?php
/*
Plugin Name: Payment Gateway Based Fees and Discounts for WooCommerce
Plugin URI: https://wpcodefactory.com/item/payment-gateway-based-fees-and-discounts-for-woocommerce-plugin/
Description: WooCommerce Payment Gateways Fees and Discounts.
Version: 2.2.2
Author: Algoritmika Ltd
Author URI: http://www.algoritmika.com
Text Domain: alg-woocommerce-fees
Domain Path: /langs
Copyright: © 2017 Algoritmika Ltd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Check if WooCommerce is active
$plugin = 'woocommerce/woocommerce.php';
if (
	! in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) &&
	! ( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
) {
	return;
}

if ( 'woocommerce-checkout-fees.php' === basename( __FILE__ ) ) {
	// Check if Pro is active, if so then return
	$plugin = 'checkout-fees-for-woocommerce-pro/woocommerce-checkout-fees-pro.php';
	if (
		in_array( $plugin, apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ) ) ||
		( is_multisite() && array_key_exists( $plugin, get_site_option( 'active_sitewide_plugins', array() ) ) )
	) {
		return;
	}
}

if ( ! class_exists( 'Alg_Woocommerce_Checkout_Fees' ) ) :

/**
 * Main Alg_Woocommerce_Checkout_Fees Class
 *
 * @version 2.2.2
 * @class   Alg_Woocommerce_Checkout_Fees
 */
final class Alg_Woocommerce_Checkout_Fees {

	/**
	 * Plugin version.
	 *
	 * @var   string
	 * @since 2.1.0
	 */
	public $version = '2.2.2';

	/**
	 * @var Alg_Woocommerce_Checkout_Fees The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main Alg_Woocommerce_Checkout_Fees Instance
	 *
	 * Ensures only one instance of Alg_Woocommerce_Checkout_Fees is loaded or can be loaded.
	 *
	 * @static
	 * @return Alg_Woocommerce_Checkout_Fees - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Alg_Woocommerce_Checkout_Fees Constructor.
	 *
	 * @version 2.2.2
	 * @access  public
	 */
	function __construct() {

		// Set up localisation
		load_plugin_textdomain( 'alg-woocommerce-fees', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

		// Include required files
		$this->includes();

		if ( is_admin() ) {
			add_filter( 'woocommerce_get_settings_pages',                     array( $this, 'add_woocommerce_settings_tab' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );

			require_once( 'includes/admin/admin-functions.php' );
		}

		// Maybe delete all plugin data
		add_action( 'admin_init', array( $this, 'maybe_delete_all_plugin_data' ), PHP_INT_MAX );
	}

	/**
	 * maybe_delete_all_plugin_data.
	 *
	 * @version 2.2.2
	 * @since   2.2.2
	 */
	function maybe_delete_all_plugin_data() {
		if ( isset( $_GET['alg_woocommerce_checkout_fees_delete_all_data'] ) ) {
			// General
			if ( isset( $this->general_settings ) ) {
				foreach ( $this->general_settings as $section ) {
					foreach ( $section->get_settings() as $value ) {
						if ( isset( $value['id'] ) ) {
							delete_option( $value['id'] );
						}
					}
				}
			}
			// Gateways
			global $woocommerce;
			if ( isset( $woocommerce ) ) {
				$available_gateways = $woocommerce->payment_gateways->payment_gateways();
				foreach ( $available_gateways as $key => $gateway ) {
					$gateway_settings = apply_filters( 'woocommerce_get_settings_alg_checkout_fees_' . sanitize_title( $key ), array() );
					$_GET['section'] = $key;
					foreach ( $gateway_settings as $value ) {
						if ( isset( $value['id'] ) ) {
							delete_option( $value['id'] );
						}
					}
				}
			}
			// Products meta
			$block_size  = 512;
			$offset      = 0;
			while( true ) {
				$args = array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'posts_per_page' => $block_size,
					'offset'         => $offset,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
				);
				$loop = new WP_Query( $args );
				if ( ! $loop->have_posts() ) {
					break;
				}
				foreach ( $loop->posts as $post_id ) {
					if ( isset( $woocommerce ) ) {
						$available_gateways = $woocommerce->payment_gateways->payment_gateways();
						foreach ( $available_gateways as $gateway_key => $gateway ) {
							foreach ( $this->meta_box_settings->get_meta_box_options() as $option ) {
								delete_post_meta( $post_id, '_' . $option['name'] . '_' . $gateway_key );
							}
						}
					}
				}
				$offset += $block_size;
			}
			// The end
			wp_safe_redirect( remove_query_arg( 'alg_woocommerce_checkout_fees_delete_all_data' ) );
			exit;
		}
	}

	/**
	 * Show action links on the plugin screen
	 *
	 * @version 2.2.2
	 * @param   mixed $links
	 * @return  array
	 */
	function action_links( $links ) {
		$custom_links = array();
		$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_checkout_fees' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
		if ( 'woocommerce-checkout-fees.php' === basename( __FILE__ ) ) {
			$custom_links[] = '<a href="https://wpcodefactory.com/item/payment-gateway-based-fees-and-discounts-for-woocommerce-plugin/">' .
				__( 'Unlock All', 'alg-woocommerce-fees' ) . '</a>';
		}
		return array_merge( $custom_links, $links );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 *
	 * @version 2.2.2
	 */
	function includes() {
		// Settings
		$this->general_settings = array();
		$this->general_settings[] = require_once( 'includes/admin/class-wc-checkout-fees-settings-general.php' );
		$this->general_settings[] = require_once( 'includes/admin/class-wc-checkout-fees-settings-gateways.php' );
		if ( is_admin() && get_option( 'alg_woocommerce_checkout_fees_version', '' ) !== $this->version ) {
			foreach ( $this->general_settings as $section ) {
				foreach ( $section->get_settings() as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? ( bool ) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
			update_option( 'alg_woocommerce_checkout_fees_version', $this->version );
		}
		// Settings - Meta box
		$this->meta_box_settings = require_once( 'includes/admin/class-wc-checkout-fees-meta-boxes-per-product.php' );
		// Core
		require_once( 'includes/class-wc-checkout-fees.php' );
	}

	/**
	 * Add Woocommerce settings tab to WooCommerce settings.
	 */
	function add_woocommerce_settings_tab( $settings ) {
		$settings[] = include( 'includes/admin/class-wc-settings-checkout-fees.php' );
		return $settings;
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	function plugin_url() {
		return untrailingslashit( plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

endif;

if ( ! function_exists( 'create_alg_woocommerce_checkout_fees' ) ) {
	/**
	 * Returns the main instance of Alg_Woocommerce_Checkout_Fees to prevent the need to use globals.
	 *
	 * @version 2.0.0
	 * @return Alg_Woocommerce_Checkout_Fees
	 */
	function create_alg_woocommerce_checkout_fees() {
		return Alg_Woocommerce_Checkout_Fees::instance();
	}
}

create_alg_woocommerce_checkout_fees();
