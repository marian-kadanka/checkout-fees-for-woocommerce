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

		add_action( 'woocommerce_settings_save_' . $this->id,  array( $this, 'save' ) );

		parent::__construct();
	}

	/**
	 * Save settings
	 */
	function save() {
		parent::save();
		echo '<div class="updated">' .
				'<p class="main"><strong>' .
					__( 'Install Checkout Fees for WooCommerce Pro to unlock all features', 'alg-woocommerce-fees' ) .
				'</strong></p>' .
				'<span>' .
					sprintf(
						__('Some settings fields are locked and you will need %s to modify all locked fields.', 'alg-woocommerce-fees'),
						'<a href="http://coder.fm/item/checkout-fees-for-woocommerce-plugin/">Checkout Fees for WooCommerce Pro</a>'
					) .
				'</span>' .
				'<p>' .
					'<a href="http://coder.fm/item/checkout-fees-for-woocommerce-plugin/" target="_blank" class="button button-primary">'. __( 'Visit plugin site', 'alg-woocommerce-fees' ) . '</a>' .
				'</p>' .
		'</div>';
	}

	/**
	 * get_settings.
	 */
	public function get_settings() {
		global $current_section;
		$the_current_section = ( '' != $current_section ) ? $current_section : 'general';
		return apply_filters( 'woocommerce_get_settings_' . $this->id . '_' . $the_current_section, array() );
	}
}

endif;

return new Alg_WC_Settings_Checkout_Fees();
