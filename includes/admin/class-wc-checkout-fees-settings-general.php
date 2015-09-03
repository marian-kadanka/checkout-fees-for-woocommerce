<?php
/**
 * Checkout Fees for WooCommerce - General Section Settings
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees_Settings_General' ) ) :

class Alg_WC_Checkout_Fees_Settings_General {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id   = 'general';
		$this->desc = __( 'General', 'alg-woocommerce-fees' );

		add_filter( 'woocommerce_get_sections_alg_checkout_fees',              array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_alg_checkout_fees_' . $this->id, array( $this, 'get_settings' ), PHP_INT_MAX );
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		$sections[ $this->id ] = $this->desc;
		return $sections;
	}

	/**
	 * get_settings.
	 */
	function get_settings() {

		$settings = array(

			array(
				'title'     => __( 'Checkout Fees and Discounts', 'alg-woocommerce-fees' ),
				'type'      => 'title',
				'id'        => 'alg_woocommerce_checkout_fees_options',
			),

			array(
				'title'     => __( 'Payment Gateways Fees and Discounts', 'alg-woocommerce-fees' ),
				'desc'      => '<strong>' . __( 'Enable', 'alg-woocommerce-fees' ) . '</strong>',
				'desc_tip'  => __( 'Enable extra fees or discounts for WooCommerce payment gateways.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_enabled',
				'default'   => 'yes',
				'type'      => 'checkbox',
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_woocommerce_checkout_fees_options',
			),

		);

		return $settings;
	}

}

endif;

return new Alg_WC_Checkout_Fees_Settings_General();
