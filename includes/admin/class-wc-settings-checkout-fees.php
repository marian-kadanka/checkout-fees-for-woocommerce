<?php
/**
 * Checkout Fees for WooCommerce - Settings
 *
 * @version 1.0.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Settings_Checkout_Fees' ) ) :

class Alg_WC_Settings_Checkout_Fees extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id    = 'alg_checkout_fees';
		$this->label = __( 'Checkout Fees', 'alg-woocommerce-fees' );

		parent::__construct();
	}

	public function get_settings() {
		global $current_section;
		$the_current_section = ( '' != $current_section ) ? $current_section : 'general';
		return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $the_current_section, array() );
	}
}

endif;

return new Alg_WC_Settings_Checkout_Fees();
