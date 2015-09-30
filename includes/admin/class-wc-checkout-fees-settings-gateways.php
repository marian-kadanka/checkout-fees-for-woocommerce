<?php
/**
 * Checkout Fees for WooCommerce - Gateways Section(s) Settings
 *
 * @version 1.2.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees_Settings_Gateways' ) ) :

class Alg_WC_Checkout_Fees_Settings_Gateways {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 */
	public function __construct() {

//		$this->id   = 'gateways';
//		$this->desc = __( 'Gateways', 'alg-woocommerce-fees' );

		add_filter( 'wp_loaded',                                  array( $this, 'add_gateway_fees_settings_hook' ) );
		add_filter( 'woocommerce_get_sections_alg_checkout_fees', array( $this, 'settings_section' ) );
	}

	/**
	 * get_settings.
	 */
	function get_settings() {
		return array();
	}

	/**
	 * add_gateway_fees_settings_hook.
	 */
	function add_gateway_fees_settings_hook() {
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			add_filter( 'woocommerce_get_settings_alg_checkout_fees_' . $key, array( $this, 'add_gateway_fees_settings' ), PHP_INT_MAX );
		}
	}

	/**
	 * settings_section.
	 */
	function settings_section( $sections ) {
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		foreach ( $available_gateways as $key => $gateway ) {
			$sections[ $key ] = $gateway->title;
		}
		return $sections;
	}

	/**
	 * add_gateway_fees_settings.
	 *
	 * @version 1.1.0
	 */
	function add_gateway_fees_settings() {

		// Getting current gateway (section)
		if ( ! isset( $_GET['section'] ) ) return array();
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		$key = $_GET['section'];
		if ( ! isset( $available_gateways[ $key ] ) ) return array();
		$gateway = $available_gateways[ $key ];

		// Adding settings
		$settings = array(

			array(
				'title'     => $gateway->title,
				'type'      => 'title',
				'id'        => 'alg_gateways_fees_options'
			),

			array(
				'title'     => $gateway->title . ' ' . __( 'Fees and Discounts', 'alg-woocommerce-fees' ),
				'desc'      => '<strong>' . __( 'Enable', 'alg-woocommerce-fees' ) . '</strong>',
				'desc_tip'  => sprintf( __( 'Add fee/discount to %s gateway', 'alg-woocommerce-fees' ), $gateway->title ),
				'id'        => 'alg_gateways_fees_enabled_' . $key,
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Title', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Fee (or discount) title to show to customer.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_text_' . $key,
				'default'   => '',
				'type'      => 'text',
			),

			array(
				'title'     => __( 'Type', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Fee (or discount) type.', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'Percent or fixed value.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_type_' . $key,
				'default'   => 'fixed',
				'type'      => 'select',
				'options'   => array(
					'fixed'   => __( 'Fixed', 'alg-woocommerce-fees' ),
					'percent' => __( 'Percent', 'alg-woocommerce-fees' ),
				),
			),

			array(
				'title'     => __( 'Value', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Fee (or discount) value.', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'The value. For discount enter a negative number.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_value_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array( 'step' => '0.0001', ),
			),

			array(
				'title'     => __( 'Minimum Amount', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Minimum cart amount for adding the fee (or discount).', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'Set 0 to disable.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_min_cart_amount_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array( 'step' => '0.0001', 'min' => '0', ),
			),

			array(
				'title'     => __( 'Maximum Amount', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Maximum cart amount for adding the fee (or discount).', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'Set 0 to disable.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_max_cart_amount_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array( 'step' => '0.0001', 'min' => '0', ),
			),

			array(
				'title'     => __( 'Rounding', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Round the fee (or discount) value before adding to the cart.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_round_' . $key,
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( '', 'alg-woocommerce-fees' ),
				'desc'      => __( 'If rounding is enabled, set precision here.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_round_precision_' . $key,
				'default'   => 0,
				'type'      => 'number',
				'custom_attributes' => array( 'step' => '1', 'min' => '0', ),
			),

			array(
				'title'     => __( 'Taxes', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Is taxable?', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_is_taxable_' . $key,
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( '', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Tax Class (only if Taxable selected).', 'alg-woocommerce-fees' ),
				'id'        => 'alg_gateways_fees_tax_class_id_' . $key,
				'default'   => '',
				'type'      => 'select',
				'options'   => array_merge( array( __( 'Standard Rate', 'alg-woocommerce-fees' ) ), WC_Tax::get_tax_classes() ),
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_gateways_fees_options'
			),

		);

		return $settings;
	}

}

endif;

return new Alg_WC_Checkout_Fees_Settings_Gateways();
