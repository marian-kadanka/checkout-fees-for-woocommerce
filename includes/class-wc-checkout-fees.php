<?php
/**
 * Checkout Fees for WooCommerce
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees' ) ) :

class Alg_WC_Checkout_Fees {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_enabled' ) ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_gateways_fees' ) );
			add_action( 'wp_enqueue_scripts' ,             array( $this, 'enqueue_checkout_script' ) );
			add_action( 'init',                            array( $this, 'register_script' ) );
		}
	}

	/**
	 * register_script.
	 */
	public function register_script() {
		wp_register_script(
			'alg-payment-gateways-checkout',
			trailingslashit( plugin_dir_url( __FILE__ ) ) . 'js/checkout-fees.js',
			array( 'jquery' ),
			false,
			true
		);
	}

	/**
	 * enqueue_checkout_script.
	 */
	public function enqueue_checkout_script() {
		if ( ! is_checkout() ) return;
		wp_enqueue_script( 'alg-payment-gateways-checkout' );
	}

	/**
	 * add_gateways_fees.
	 */
	function add_gateways_fees() {
		global $woocommerce;

		// Get current gateway
		$current_gateway = $woocommerce->session->chosen_payment_method;
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		if ( ! array_key_exists( $current_gateway, $available_gateways ) ) {
			$current_gateway = get_option( 'woocommerce_default_gateway', '' );
			if ( '' == $current_gateway ) {
				$current_gateway = current( $available_gateways );
				$current_gateway = isset( $current_gateway->id ) ? $current_gateway->id : '';
			}
		}

		// Add fee
		if ( '' != $current_gateway && 'yes' === get_option( 'alg_gateways_fees_enabled_' . $current_gateway ) ) {
			$min_cart_amount = get_option( 'alg_gateways_fees_min_cart_amount_' . $current_gateway );
			$max_cart_amount = get_option( 'alg_gateways_fees_max_cart_amount_' . $current_gateway );
			$total_in_cart = $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
			if ( $total_in_cart >= $min_cart_amount  && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) ) {
				if ( 0 == ( $fee_value = get_option( 'alg_gateways_fees_value_' . $current_gateway ) ) ) return;
				$fee_text = get_option( 'alg_gateways_fees_text_' . $current_gateway );
				$taxable = ( 'yes' === get_option( 'alg_gateways_fees_is_taxable_' . $current_gateway ) ) ? true : false;
				$tax_class_name = '';
				if ( $taxable ) {
					$tax_class_id = get_option( 'alg_gateways_fees_tax_class_id_' . $current_gateway, 0 );
					$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
					$tax_class_name = $tax_class_names[ $tax_class_id ];
				}
				$woocommerce->cart->add_fee( $fee_text, $fee_value, $taxable, $tax_class_name );
			}
		}
	}
}

endif;

return new Alg_WC_Checkout_Fees();
