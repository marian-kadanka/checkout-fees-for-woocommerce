<?php
/**
 * Checkout Fees for WooCommerce
 *
 * @version 1.2.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees' ) ) :

class Alg_WC_Checkout_Fees {

	/**
	 * Constructor.
	 *
	 * @version 1.2.0
	 */
	public function __construct() {
		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_enabled' ) ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_gateways_fees' ) );
			add_action( 'wp_enqueue_scripts' ,             array( $this, 'enqueue_checkout_script' ) );
			add_action( 'init',                            array( $this, 'register_script' ) );
			if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_info_enabled' ) ) {
				add_action(
					get_option( 'alg_woocommerce_checkout_fees_info_hook' ),
					array( $this, 'show_checkout_fees_info' ),
					get_option( 'alg_woocommerce_checkout_fees_info_hook_priority' )
				);
			}
		}
	}

	/**
	 * show_checkout_fees_info.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function show_checkout_fees_info() {
		$html = '';
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
		foreach ( $available_gateways as $available_gateway_key => $available_gateway ) {

			$current_gateway = $available_gateway_key;
			$product_id = get_the_ID();

			// Fee - globally
			$global_fee = $this->get_the_fee(
				$current_gateway,
				get_option( 'alg_gateways_fees_enabled_'         . $current_gateway ),
				get_option( 'alg_gateways_fees_text_'            . $current_gateway ),
				get_option( 'alg_gateways_fees_min_cart_amount_' . $current_gateway ),
				get_option( 'alg_gateways_fees_max_cart_amount_' . $current_gateway ),
				get_option( 'alg_gateways_fees_value_'           . $current_gateway ),
				get_option( 'alg_gateways_fees_type_'            . $current_gateway ),
				get_option( 'alg_gateways_fees_round_'           . $current_gateway ),
				get_option( 'alg_gateways_fees_round_precision_' . $current_gateway ),
				get_option( 'alg_gateways_fees_is_taxable_'      . $current_gateway ),
				get_option( 'alg_gateways_fees_tax_class_id_'    . $current_gateway, 0 )
			);

			// Fee - per product
			$local_fee = 0;
			if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_enabled' ) && 'bacs' === $current_gateway ) {
				$local_fee = $this->get_the_fee(
					$current_gateway,
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_enabled_'            . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_'              . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_min_cart_amount_'    . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_max_cart_amount_'    . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_value_'              . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_type_'               . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_rounding_enabled_'   . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_rounding_precision_' . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_enabled_'        . $current_gateway, true ),
					get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_class_'          . $current_gateway, true )
				);
			}

			if ( 0 != $global_fee || 0 != $local_fee ) {
				$the_product = wc_get_product( $product_id );
				$the_price = $the_product->get_price();
				$the_price_original = $the_price;
				$fee_title = $fee_value = '';
				if ( 0 != $global_fee ) {
					if ( 'yes' === get_option( 'alg_gateways_fees_is_taxable_' . $current_gateway ) ) {
						$tax_class_name = '';
						$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
						$tax_class_name = $tax_class_names[ get_option( 'alg_gateways_fees_tax_class_id_' . $current_gateway, 0 ) ];
						$tax_rates = WC_Tax::get_rates( $tax_class_name );
						$fee_taxes = WC_Tax::calc_tax( $global_fee, $tax_rates, false );
						if ( ! empty( $fee_taxes ) ) {
							$tax = array_sum( $fee_taxes );
							$global_fee += $tax;
						}
					}
					$fee_title .= get_option( 'alg_gateways_fees_text_'  . $current_gateway );
					$fee_value .= get_option( 'alg_gateways_fees_value_' . $current_gateway );
					$fee_value = ( 'percent' === get_option( 'alg_gateways_fees_type_' . $current_gateway ) ) ?
						$fee_value . '%' :
						wc_price( $fee_value );

					$the_price += $global_fee;
				}
				$fee_title_per_product = $fee_value_per_product = '';
				if ( 0 != $local_fee ) {
					if ( 'yes' === get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_enabled_' . $current_gateway, true ) ) {
						$tax_class_name = '';
						$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
						$tax_class_name = $tax_class_names[ get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_class_' . $current_gateway, true ) ];
						$tax_rates = WC_Tax::get_rates( $tax_class_name );
						$fee_taxes = WC_Tax::calc_tax( $local_fee, $tax_rates, false );
						if ( ! empty( $fee_taxes ) ) {
							$tax = array_sum( $fee_taxes );
							$local_fee += $tax;
						}
					}
					$fee_title_per_product .= get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_' . $current_gateway, true );
					$fee_value_per_product .= get_post_meta( $product_id, '_' . 'alg_checkout_fees_value_' . $current_gateway, true );
					$fee_value_per_product = ( 'percent' === get_post_meta( $product_id, '_' . 'alg_checkout_fees_type_' . $current_gateway, true ) ) ?
						$fee_value_per_product . ' %' :
						wc_price( $fee_value_per_product );

					$the_price += $local_fee;
				}
				if ( '' != $fee_title_per_product ) {
					$fee_title = ( '' == $fee_title ) ? $fee_title_per_product : $fee_title . ' / ' . $fee_title_per_product;
				}
				if ( '' != $fee_value_per_product ) {
					$fee_value = ( '' == $fee_value ) ? $fee_value_per_product : $fee_value . ' / ' . $fee_value_per_product;
				}
				$price_diff = ( $the_price - $the_price_original );
//				$discount_or_fee_text = ( $price_diff < 0 ) ? 'Save: ' : '';//'Fee: ';
				$row_html = get_option( 'alg_woocommerce_checkout_fees_info_row_template' );
				$row_html = str_replace( '%gateway_title%',          $available_gateway->title,             $row_html );
				$row_html = str_replace( '%gateway_description%',    $available_gateway->get_description(), $row_html );
				$row_html = str_replace( '%gateway_icon%',           $available_gateway->get_icon(),        $row_html );
				$row_html = str_replace( '%product_gateway_price%',  wc_price( $the_price ),                $row_html );
				$row_html = str_replace( '%product_original_price%', wc_price( $the_price_original ),       $row_html );
				$row_html = str_replace( '%product_price_diff%',     wc_price( $price_diff ),               $row_html );
//				$row_html = str_replace( '%discount_or_fee_text%',   $discount_or_fee_text,                 $row_html );
				$row_html = str_replace( '%gateway_fee_title%',      $fee_title,                            $row_html );
				$row_html = str_replace( '%gateway_fee_value%',      $fee_value,                            $row_html );
				$html .= $row_html;
			}
		}
		if ( '' != $html ) {
			echo get_option( 'alg_woocommerce_checkout_fees_info_start_template' )
				 . $html .
				 get_option( 'alg_woocommerce_checkout_fees_info_end_template' );
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
	 *
	 * @version 1.1.0
	 */
	function add_gateways_fees( $the_cart ) {
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

		// Add fee - globally
		$this->maybe_add_cart_fee(
			$current_gateway,
			get_option( 'alg_gateways_fees_enabled_'         . $current_gateway ),
			get_option( 'alg_gateways_fees_text_'            . $current_gateway ),
			get_option( 'alg_gateways_fees_min_cart_amount_' . $current_gateway ),
			get_option( 'alg_gateways_fees_max_cart_amount_' . $current_gateway ),
			get_option( 'alg_gateways_fees_value_'           . $current_gateway ),
			get_option( 'alg_gateways_fees_type_'            . $current_gateway ),
			get_option( 'alg_gateways_fees_round_'           . $current_gateway ),
			get_option( 'alg_gateways_fees_round_precision_' . $current_gateway ),
			get_option( 'alg_gateways_fees_is_taxable_'      . $current_gateway ),
			get_option( 'alg_gateways_fees_tax_class_id_'    . $current_gateway, 0 )
		);

		// Add fee - per product
		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_enabled' ) && 'bacs' === $current_gateway ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$this->maybe_add_cart_fee(
					$current_gateway,
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_enabled_'            . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_title_'              . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_min_cart_amount_'    . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_max_cart_amount_'    . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_value_'              . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_type_'               . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_rounding_enabled_'   . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_rounding_precision_' . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_tax_enabled_'        . $current_gateway, true ),
					get_post_meta( $values['product_id'], '_' . 'alg_checkout_fees_tax_class_'          . $current_gateway, true )
				);
			}
		}
	}

	/**
	 * get_the_fee.
	 *
	 * @version 1.2.0
	 * @since   1.2.0
	 */
	function get_the_fee( $current_gateway, $is_enabled, $fee_text, $min_cart_amount, $max_cart_amount, $fee_value, $fee_type, $do_round, $precision ) {
		$final_fee_to_add = 0;
		if ( '' != $current_gateway && 'yes' === $is_enabled ) {
			global $woocommerce;
			$total_in_cart = $woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
			if ( $total_in_cart >= $min_cart_amount && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) ) {
				if ( 0 == $fee_value ) return 0;
				switch ( $fee_type ) {
					case 'fixed':
						$final_fee_to_add = $fee_value;
						break;
					case 'percent':
						$final_fee_to_add = ( $fee_value / 100 ) * $total_in_cart;
						if ( 'yes' === $do_round ) {
							$final_fee_to_add = round( $final_fee_to_add, $precision );
						}
						break;
				}
			}
		}
		return $final_fee_to_add;
	}

	/**
	 * maybe_add_cart_fee.
	 *
	 * @version 1.2.0
	 * @since   1.1.0
	 */
	function maybe_add_cart_fee( $current_gateway, $is_enabled, $fee_text, $min_cart_amount, $max_cart_amount, $fee_value, $fee_type, $do_round, $precision, $is_taxable, $tax_class_id ) {
		$final_fee_to_add = $this->get_the_fee( $current_gateway, $is_enabled, $fee_text, $min_cart_amount, $max_cart_amount, $fee_value, $fee_type, $do_round, $precision );
		if ( 0 != $final_fee_to_add ) {
			global $woocommerce;
			$taxable = ( 'yes' === $is_taxable ) ? true : false;
			$tax_class_name = '';
			if ( $taxable ) {
				$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
				$tax_class_name = $tax_class_names[ $tax_class_id ];
			}
			$woocommerce->cart->add_fee( $fee_text, $final_fee_to_add, $taxable, $tax_class_name );
		}
	}
}

endif;

return new Alg_WC_Checkout_Fees();
