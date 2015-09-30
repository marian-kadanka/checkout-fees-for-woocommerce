<?php
/**
 * Checkout Fees for WooCommerce - General Section Settings
 *
 * @version 1.2.0
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
	 *
	 * @version 1.2.0
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

			array(
				'title'     => __( 'Fees/Discounts per Product', 'alg-woocommerce-fees' ),
				'type'      => 'title',
				'id'        => 'alg_woocommerce_checkout_fees_per_product_options',
			),

			array(
				'title'     => __( 'Payment Gateways Fees and Discounts on per Product Basis', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Enable', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'This will add meta boxes with fees settings to each product\'s edit page.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_per_product_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_woocommerce_checkout_fees_per_product_options',
			),

			array(
				'title'     => __( 'Info on Single Product', 'alg-woocommerce-fees' ),
				'type'      => 'title',
				'id'        => 'alg_woocommerce_checkout_fees_info_options',
			),

			array(
				'title'     => __( 'Info on Single Product Page', 'alg-woocommerce-fees' ),
				'desc'      => __( 'Enable', 'alg-woocommerce-fees' ),
				'desc_tip'  => __( 'This will add gateway fee/discount info on single product frontend page.', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_enabled',
				'default'   => 'no',
				'type'      => 'checkbox',
			),

			array(
				'title'     => __( 'Start HTML', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_start_template',
				'default'   => '<table>',
				'type'      => 'textarea',
				'css'       => 'width:50%;height:50px;',
			),

			array(
				'title'     => __( 'Row Template HTML', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_row_template',
				'default'   => '<tr><td><strong>%gateway_title%</strong></td><td>%gateway_fee_title%</td><td>%product_original_price%</td><td>%product_gateway_price%</td><td>%product_price_diff%</td></tr>',
				'type'      => 'textarea',
				'css'       => 'width:100%;height:50px;',
			),

			array(
				'title'     => __( 'End HTML', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_end_template',
				'default'   => '</table>',
				'type'      => 'textarea',
				'css'       => 'width:50%;height:50px;',
			),

			array(
				'title'     => __( 'Position', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_hook',
				'default'   => 'woocommerce_single_product_summary',
				'type'      => 'select',
				'options'   => array(
					'woocommerce_single_product_summary'        => __( 'Inside product summary', 'alg-woocommerce-fees' ),
					'woocommerce_before_single_product_summary' => __( 'Before product summary', 'alg-woocommerce-fees' ),
					'woocommerce_after_single_product_summary'  => __( 'After product summary',  'alg-woocommerce-fees' ),
				),
			),

			array(
				'title'     => __( 'Position Order', 'alg-woocommerce-fees' ),
				'id'        => 'alg_woocommerce_checkout_fees_info_hook_priority',
				'default'   => 20,
				'type'      => 'number',
			),

			array(
				'type'      => 'sectionend',
				'id'        => 'alg_woocommerce_checkout_fees_info_options',
			),

		);

		return $settings;
	}

}

endif;

return new Alg_WC_Checkout_Fees_Settings_General();
