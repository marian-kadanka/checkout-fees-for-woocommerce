<?php
/**
 * Checkout Fees for WooCommerce
 *
 * @version 2.1.0
 * @since   1.0.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees' ) ) :

class Alg_WC_Checkout_Fees {

	/**
	 * Constructor.
	 *
	 * @version 2.0.0
	 */
	public function __construct() {
		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_enabled' ) ) {
			add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_gateways_fees' ) );
			add_action( 'wp_enqueue_scripts' ,             array( $this, 'enqueue_checkout_script' ) );
			add_action( 'init',                            array( $this, 'register_script' ) );
			if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_info_enabled' ) ) {
				add_action(
					get_option( 'alg_woocommerce_checkout_fees_info_hook' ),
					array( $this, 'show_checkout_fees_full_info' ),
					get_option( 'alg_woocommerce_checkout_fees_info_hook_priority' )
				);
			}
			if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_lowest_price_info_enabled' ) ) {
				add_action(
					get_option( 'alg_woocommerce_checkout_fees_lowest_price_info_hook' ),
					array( $this, 'show_checkout_fees_full_lowest_price_info' ),
					get_option( 'alg_woocommerce_checkout_fees_lowest_price_info_hook_priority' )
				);
			}
			add_shortcode( 'alg_show_checkout_fees_full_info',         array( $this, 'get_checkout_fees_full_info' ) );
			add_shortcode( 'alg_show_checkout_fees_lowest_price_info', array( $this, 'get_checkout_fees_lowest_price_info' ) );
		}
	}

	/**
	 * get_product_cats.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_product_cats( $product_id ) {
		$product_cats = array();
		$product_terms = get_the_terms( $product_id, 'product_cat' );
		if ( is_array( $product_terms ) ) {
			foreach ( $product_terms as $term ) {
				$product_cats[] = $term->term_id;
			}
		}
		return $product_cats;
	}

	/**
	 * check_countries.
	 *
	 * @global fees only
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function check_countries( $current_gateway ) {
		$customer_country = WC()->customer->get_country();
		$include_countries = get_option( 'alg_gateways_fees_countries_include_' . $current_gateway, '' );
		if ( ! empty( $include_countries ) && ! in_array( $customer_country, $include_countries ) ) {
			return false;
		}
		$exclude_countries = get_option( 'alg_gateways_fees_countries_exclude_' . $current_gateway, '' );
		if ( ! empty( $exclude_countries ) && in_array( $customer_country, $exclude_countries ) ) {
			return false;
		}
		return true;
	}

	/**
	 * show_checkout_fees_full_lowest_price_info.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function show_checkout_fees_full_lowest_price_info() {
		echo $this->get_checkout_fees_info( true );
	}

	/**
	 * show_checkout_fees_full_info.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function show_checkout_fees_full_info() {
		echo $this->get_checkout_fees_info( false );
	}

	/**
	 * get_checkout_fees_lowest_price_info.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_checkout_fees_lowest_price_info() {
		return $this->get_checkout_fees_info( true );
	}

	/**
	 * get_checkout_fees_full_info.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_checkout_fees_full_info() {
		return $this->get_checkout_fees_info( false );
	}

	/**
	 * get_checkout_fees_info.
	 *
	 * @version 2.1.0
	 * @since   1.2.0
	 */
	function get_checkout_fees_info( $lowest_price_only ) {

		$product_id  = get_the_ID();
		$the_product = wc_get_product( $product_id );

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		$products_array = array();
		if ( $the_product->is_type( 'variable' ) ) {
			foreach( $the_product->get_available_variations() as $product_variation ) {
				$variation_product = wc_get_product( $product_variation['variation_id'] );
				$products_array[] = array(
					'variation_atts' => $variation_product->get_formatted_variation_attributes( true ),
					'price_excl_tax' => $variation_product->get_price_excluding_tax(),
					'price_incl_tax' => $variation_product->get_price_including_tax(),
					'display_price'  => $variation_product->get_display_price(),
				);
			}
		} else {
			$products_array = array(
				array(
					'variation_atts' => '',
					'price_excl_tax' => $the_product->get_price_excluding_tax(),
					'price_incl_tax' => $the_product->get_price_including_tax(),
					'display_price'  => $the_product->get_display_price(),
				),
			);
		}

		$gateways_data      = array();
		$lowest_price_array = array();

		foreach ( $products_array as $product_data ) {

			$the_variation_atts = $product_data['variation_atts'];
			$the_price_excl_tax = $product_data['price_excl_tax'];
			$the_price_incl_tax = $product_data['price_incl_tax'];
			$the_display_price  = $product_data['display_price'];

			$single_product_gateways_data = array();

			$lowest_price = PHP_INT_MAX;
			$lowest_price_gateway = '';

			$available_gateways = WC()->payment_gateways->get_available_payment_gateways();
			foreach ( $available_gateways as $available_gateway_key => $available_gateway ) {

				$current_gateway = $available_gateway_key;

				// Checking country
				if ( false === $this->check_countries( $current_gateway ) ) {
					continue;
				}

				// Fee - globally
				$args = $this->get_the_args_global( $current_gateway );
				$global_fee = $this->get_the_fee( $args, 'fee_both', $the_price_excl_tax, true, $product_id );

				// Fee - per product
				$local_fee = 0;
				if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_enabled' ) && ( 'bacs' === $current_gateway || '' === apply_filters( 'alg_wc_checkout_fees_option', 'bacs' ) ) ) {
					$args = $this->get_the_args_local( $current_gateway, $product_id, 0, 1 );
					$local_fee = $this->get_the_fee( $args, 'fee_both', $the_price_excl_tax, true, $product_id );
				}

				if ( $tax_display_mode == 'incl' ) {
					$the_price = $the_price_incl_tax;
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
						$the_price += $global_fee;
					}
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
						$the_price += $local_fee;
					}
					$price_diff = ( $the_price - $the_price_incl_tax );
				} else {
					$the_price = $the_price_excl_tax;
					$the_price += $global_fee;
					$the_price += $local_fee;
					$price_diff = ( $the_price - $the_price_excl_tax );
				}


				if ( false === $lowest_price_only ) {
					// Saving for output
					$single_product_gateways_data[ $available_gateway_key ] = array(
						'gateway_title'          => $available_gateway->title,
						'gateway_description'    => $available_gateway->get_description(),
						'gateway_icon'           => $available_gateway->get_icon(),
						'product_gateway_price'  => /* wc_price */( $the_price ),
						'product_original_price' => /* wc_price */( $the_display_price ),
						'product_price_diff'     => /* wc_price */( $price_diff ),
						'product_title'          => $the_product->get_title(),
						'product_variation_atts' => $the_variation_atts,
					);
				} else { // if ( true === $lowest_price_only ) {
					// Saving lowest price data
					if ( $the_price < $lowest_price ) {
						$lowest_price                      = $the_price;
						$lowest_price_gateway              = $available_gateway->title;
						$lowest_price_gateway_description  = $available_gateway->get_description();
						$lowest_price_gateway_icon         = $available_gateway->get_icon();
						$lowest_price_diff                 = $price_diff;
					}
				}
			}

			$gateways_data[] = $single_product_gateways_data;

			// Saving lowest price info
			if ( true === $lowest_price_only && '' != $lowest_price_gateway ) {
				$lowest_price_array[] = array(
					'gateway_title'          => $lowest_price_gateway,
					'gateway_description'    => $lowest_price_gateway_description,
					'gateway_icon'           => $lowest_price_gateway_icon,
					'product_gateway_price'  => $lowest_price,
					'product_original_price' => $the_display_price,
					'product_price_diff'     => $lowest_price_diff,
					'product_title'          => $the_product->get_title(),
					'product_variation_atts' => $the_variation_atts,
				);
			}
		}

		// Outputing results
		$price_keys = array( 'product_gateway_price', 'product_original_price', 'product_price_diff' );
		$final_html = '';
		if ( 'for_each_variation' === get_option( 'alg_woocommerce_checkout_fees_variable_info', 'for_each_variation' ) ) {
			if ( false === $lowest_price_only && ! empty( $gateways_data ) ) {
				// All gateways
				foreach ( $gateways_data as $single_product_gateways_data ) {
					$single_product_gateways_data_html = '';
					foreach ( $single_product_gateways_data as $row ) {
						$row_html = get_option( 'alg_woocommerce_checkout_fees_info_row_template' );
						foreach ( $row as $key => $value ) {
							if ( in_array( $key, $price_keys ) ) {
								$value = wc_price( $value );
							}
							$row_html = str_replace( '%' . $key . '%', $value, $row_html );
						}
						$single_product_gateways_data_html .= $row_html;
					}
					$final_html .= get_option( 'alg_woocommerce_checkout_fees_info_start_template' ) . $single_product_gateways_data_html . get_option( 'alg_woocommerce_checkout_fees_info_end_template' );
				}
			} elseif ( true === $lowest_price_only && ! empty( $lowest_price_array ) ) {
				// Lowest price only
				foreach ( $lowest_price_array as $lowest_price ) {
					$row_html = get_option( 'alg_woocommerce_checkout_fees_lowest_price_info_template' );
					foreach ( $lowest_price as $key => $value ) {
						if ( in_array( $key, $price_keys ) ) {
							$value = wc_price( $value );
						}
						$row_html = str_replace( '%' . $key . '%', $value, $row_html );
					}
					$final_html .= $row_html;
				}
			}
		} elseif ( 'ranges' === get_option( 'alg_woocommerce_checkout_fees_variable_info', 'for_each_variation' ) ) {
			if ( false === $lowest_price_only && ! empty( $gateways_data ) ) {
				// All gateways
				$modified_array = array();
				foreach ( $gateways_data as $i => $single_product_gateways_data ) {
					foreach ( $single_product_gateways_data as $gateway_key => $row ) {
						foreach ( $row as $key => $value ) {
							$modified_array[ $gateway_key ][ $key ][ $i ] = $value;
						}
					}
				}
				foreach ( $modified_array as $gateway_key => $values ) {
					$row_html = get_option( 'alg_woocommerce_checkout_fees_info_row_template' );
					foreach ( $values as $key => $values_array ) {
						$values_array = array_unique( $values_array );
						if ( in_array( $key, $price_keys ) ) {
							if ( count( $values_array ) > 1 ) {
								$value = wc_price( min( $values_array ) ) . '&ndash;'. wc_price( max( $values_array ) );
							} else {
								$value = wc_price( min( $values_array ) );
							}
						} else {
							$value = implode( '<br>', $values_array );
						}
						$row_html = str_replace( '%' . $key . '%', $value, $row_html );
					}
					$final_html .= $row_html;
				}
				$final_html = get_option( 'alg_woocommerce_checkout_fees_info_start_template' ) . $final_html . get_option( 'alg_woocommerce_checkout_fees_info_end_template' );
			} elseif ( true === $lowest_price_only && ! empty( $lowest_price_array ) ) {
				// Lowest price only
				$modified_array = array();
				foreach ( $lowest_price_array as $i => $row ) {
					foreach ( $row as $key => $value ) {
						$modified_array[ $key ][ $i ] = $value;
					}
				}
				$row_html = get_option( 'alg_woocommerce_checkout_fees_lowest_price_info_template' );
				foreach ( $modified_array as $key => $values_array ) {
					$values_array = array_unique( $values_array );
					if ( in_array( $key, $price_keys ) ) {
						if ( count( $values_array ) > 1 ) {
							$value = wc_price( min( $values_array ) ) . '&ndash;'. wc_price( max( $values_array ) );
						} else {
							$value = wc_price( min( $values_array ) );
						}
					} else {
						$value = implode( '<br>', $values_array );
					}
					$row_html = str_replace( '%' . $key . '%', $value, $row_html );
				}
				$final_html = $row_html;
			}
		}

		return $final_html;
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
	 * @version 2.1.0
	 */
	function add_gateways_fees( $the_cart ) {

		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_hide_on_cart', 'no' ) && is_cart() ) return;

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
		// Checking country
		$do_add_fees_global = $this->check_countries( $current_gateway );
		if ( $do_add_fees_global ) {
			$args = $this->get_the_args_global( $current_gateway );
			$this->maybe_add_cart_fee( $args );
		}

		// Add fee - per product
		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_enabled' ) && ( 'bacs' === $current_gateway || '' === apply_filters( 'alg_wc_checkout_fees_option', 'bacs' ) ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$args = $this->get_the_args_local( $current_gateway, $values['product_id'], $values['variation_id'], $values['quantity'] );
				$this->maybe_add_cart_fee( $args );
			}
		}
	}

	/**
	 * get_the_args_global.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_the_args_global( $current_gateway ) {
		$args = array();
		$args['current_gateway']         = $current_gateway;
		$args['is_enabled']              = get_option( 'alg_gateways_fees_enabled_'          . $current_gateway );
		$args['min_cart_amount']         = get_option( 'alg_gateways_fees_min_cart_amount_'  . $current_gateway );
		$args['max_cart_amount']         = get_option( 'alg_gateways_fees_max_cart_amount_'  . $current_gateway );
		$args['fee_text']                = get_option( 'alg_gateways_fees_text_'             . $current_gateway );
		$args['fee_value']               = get_option( 'alg_gateways_fees_value_'            . $current_gateway );
		$args['fee_type']                = get_option( 'alg_gateways_fees_type_'             . $current_gateway );
		$args['fee_text_2']              = get_option( 'alg_gateways_fees_text_2_'           . $current_gateway );
		$args['fee_value_2']             = get_option( 'alg_gateways_fees_value_2_'          . $current_gateway );
		$args['fee_type_2']              = get_option( 'alg_gateways_fees_type_2_'           . $current_gateway );
		$args['do_round']                = get_option( 'alg_gateways_fees_round_'            . $current_gateway );
		$args['precision']               = get_option( 'alg_gateways_fees_round_precision_'  . $current_gateway );
		$args['is_taxable']              = get_option( 'alg_gateways_fees_is_taxable_'       . $current_gateway );
		$args['tax_class_id']            = get_option( 'alg_gateways_fees_tax_class_id_'     . $current_gateway, 0 );
		$args['exclude_shipping']        = get_option( 'alg_gateways_fees_exclude_shipping_' . $current_gateway, 'no' );
		$args['product_id']              = 0;
		$args['product_qty']             = 0;
		$args['fixed_usage']             = 'once';
		return $args;
	}

	/**
	 * get_the_args_local.
	 *
	 * @version 2.0.0
	 * @since   2.0.0
	 */
	function get_the_args_local( $current_gateway, $product_id, $variation_id, $product_qty ) {
		$do_add_product_name = ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_add_product_name', 'no' ) ) ? true : false;
		if ( $do_add_product_name ) {
			if ( isset( $variation_id ) && 0 != $variation_id ) {
				$_product = wc_get_product( $variation_id );
				$product_formatted_name = ' &ndash; ' . $_product->get_title() . ' &ndash; ' . $_product->get_formatted_variation_attributes( true );
			} else {
				$_product = wc_get_product( $product_id );
				$product_formatted_name = ' &ndash; ' . $_product->get_title();
			}
		}
		$args = array();
		$args['current_gateway']         = $current_gateway;
		$args['is_enabled']              = get_post_meta( $product_id, '_' . 'alg_checkout_fees_enabled_'            . $current_gateway, true );
		$args['min_cart_amount']         = get_post_meta( $product_id, '_' . 'alg_checkout_fees_min_cart_amount_'    . $current_gateway, true );
		$args['max_cart_amount']         = get_post_meta( $product_id, '_' . 'alg_checkout_fees_max_cart_amount_'    . $current_gateway, true );
		$args['fee_text']                = ( $do_add_product_name ) ?
			get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_' . $current_gateway, true ) . $product_formatted_name :
			get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_' . $current_gateway, true );
		$args['fee_value']               = get_post_meta( $product_id, '_' . 'alg_checkout_fees_value_'              . $current_gateway, true );
		$args['fee_type']                = get_post_meta( $product_id, '_' . 'alg_checkout_fees_type_'               . $current_gateway, true );
		$args['fee_text_2']              = ( $do_add_product_name ) ?
			get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_2_' . $current_gateway, true ) . $product_formatted_name :
			get_post_meta( $product_id, '_' . 'alg_checkout_fees_title_2_' . $current_gateway, true );
		$args['fee_value_2']             = get_post_meta( $product_id, '_' . 'alg_checkout_fees_value_2_'            . $current_gateway, true );
		$args['fee_type_2']              = get_post_meta( $product_id, '_' . 'alg_checkout_fees_type_2_'             . $current_gateway, true );
		$args['do_round']                = get_post_meta( $product_id, '_' . 'alg_checkout_fees_rounding_enabled_'   . $current_gateway, true );
		$args['precision']               = get_post_meta( $product_id, '_' . 'alg_checkout_fees_rounding_precision_' . $current_gateway, true );
		$args['is_taxable']              = get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_enabled_'        . $current_gateway, true );
		$args['tax_class_id']            = get_post_meta( $product_id, '_' . 'alg_checkout_fees_tax_class_'          . $current_gateway, true );
		$args['exclude_shipping']        = get_post_meta( $product_id, '_' . 'alg_checkout_fees_exclude_shipping_'   . $current_gateway, true );
		$args['product_id']              = ( 'by_product' === get_post_meta( $product_id, '_' . 'alg_checkout_fees_percent_usage_' . $current_gateway, true ) ) ?
			( isset( $variation_id ) && 0 != $variation_id ? $variation_id : $product_id ) :
			0;
		$args['product_qty']             = $product_qty;
		$args['fixed_usage']             = get_post_meta( $product_id, '_' . 'alg_checkout_fees_fixed_usage_'        . $current_gateway, true );
		return $args;
	}

	/**
	 * calculate_the_fee.
	 *
	 * @version 2.1.0
	 * @since   2.0.0
	 */
	function calculate_the_fee( $args, $final_fee_to_add, $total_in_cart, $fee_num ) {
		extract( $args );
		if ( 'fee_2' == $fee_num ) {
			$fee_type  = $fee_type_2;
			$fee_value = $fee_value_2;
		}
		switch ( $fee_type ) {
			case 'fixed':
				$fixed_fee = ( 'by_quantity' === $fixed_usage ) ? $fee_value * $product_qty : $fee_value;
				$fixed_fee = apply_filters( 'wc_aelia_cs_convert', $fixed_fee, get_option( 'woocommerce_currency' ), get_woocommerce_currency() );
				$final_fee_to_add += $fixed_fee;
				break;
			case 'percent':
				if ( 0 != $product_id ) {
					$_product    = wc_get_product( $product_id );
					$sum_for_fee = $_product->get_price() * $product_qty;
				} else {
					$sum_for_fee = $total_in_cart;
				}
				$final_fee_to_add += ( $fee_value / 100 ) * $sum_for_fee;
				if ( 'yes' === $do_round ) {
					$final_fee_to_add = round( $final_fee_to_add, $precision );
				}
				break;
		}
		return $final_fee_to_add;
	}

	 /**
	 * get_sum_for_fee_by_included_and_excluded_cats.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function get_sum_for_fee_by_included_and_excluded_cats( $total_in_cart, $fee_num, $current_gateway ) {
		if ( 'fee_2' == $fee_num ) {
			$include_cats = ( false === get_option( 'alg_gateways_fees_cats_include_fee_2_' . $current_gateway, false ) ) ?
				get_option( 'alg_gateways_fees_cats_include_' . $current_gateway, '' ) :
				get_option( 'alg_gateways_fees_cats_include_fee_2_' . $current_gateway, '' );
			$exclude_cats = ( false === get_option( 'alg_gateways_fees_cats_exclude_fee_2_' . $current_gateway, false ) ) ?
				get_option( 'alg_gateways_fees_cats_exclude_' . $current_gateway, '' ) :
				get_option( 'alg_gateways_fees_cats_exclude_fee_2_' . $current_gateway, '' );
		} else {
			$include_cats = get_option( 'alg_gateways_fees_cats_include_' . $current_gateway, '' );
			$exclude_cats = get_option( 'alg_gateways_fees_cats_exclude_' . $current_gateway, '' );
		}
		if ( ! empty( $include_cats ) && 'only_for_selected_products' === get_option( 'alg_gateways_fees_cats_include_calc_type_' . $current_gateway, 'for_all_cart' ) ) {
			$sum_for_fee = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$product_cats = $this->get_product_cats( $values['product_id'] );
				$the_intersect = array_intersect( $product_cats, $include_cats );
				if ( ! empty( $the_intersect ) ) {
					/* $_product = wc_get_product( $values['product_id'] );
					$sum_for_fee += $_product->get_price_excluding_tax() * $values['quantity']; */
					$sum_for_fee += $values['line_total'];
				}
			}
		} elseif ( ! empty( $exclude_cats ) && 'only_for_selected_products' === get_option( 'alg_gateways_fees_cats_exclude_calc_type_' . $current_gateway, 'for_all_cart' ) ) {
			$sum_for_fee = 0;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$product_cats = $this->get_product_cats( $values['product_id'] );
				$the_intersect = array_intersect( $product_cats, $exclude_cats );
				if ( empty( $the_intersect ) ) {
					/* $_product = wc_get_product( $values['product_id'] );
					$sum_for_fee += $_product->get_price_excluding_tax() * $values['quantity']; */
					$sum_for_fee += $values['line_total'];
				}
			}
		} else {
			$sum_for_fee = $total_in_cart;
		}
		return $sum_for_fee;
	}

	 /**
	 * do_apply_fees_by_categories.
	 *
	 * @version 2.1.0
	 * @since   2.1.0
	 */
	function do_apply_fees_by_categories( $fee_num, $current_gateway, $info_product_id ) {
		if ( 'fee_2' == $fee_num ) {
			$include_cats = ( false === get_option( 'alg_gateways_fees_cats_include_fee_2_' . $current_gateway, false ) ) ?
				get_option( 'alg_gateways_fees_cats_include_' . $current_gateway, '' ) :
				get_option( 'alg_gateways_fees_cats_include_fee_2_' . $current_gateway, '' );
			$exclude_cats = ( false === get_option( 'alg_gateways_fees_cats_exclude_fee_2_' . $current_gateway, false ) ) ?
				get_option( 'alg_gateways_fees_cats_exclude_' . $current_gateway, '' ) :
				get_option( 'alg_gateways_fees_cats_exclude_fee_2_' . $current_gateway, '' );
		} else {
			$include_cats = get_option( 'alg_gateways_fees_cats_include_' . $current_gateway, '' );
			$exclude_cats = get_option( 'alg_gateways_fees_cats_exclude_' . $current_gateway, '' );
		}
		if ( '' != $include_cats || '' != $exclude_cats ) {
			if ( 0 != $info_product_id ) {
				$product_cats = $this->get_product_cats( $info_product_id );
				if ( ! empty( $include_cats ) ) {
					$the_intersect = array_intersect( $product_cats, $include_cats );
					if ( empty( $the_intersect ) ) {
						return false;
					}
				}
				if ( ! empty( $exclude_cats ) ) {
					$the_intersect = array_intersect( $product_cats, $exclude_cats );
					if ( ! empty( $the_intersect ) ) {
						return false;
					}
				}
			} else {
				$do_add_fees_global_by_include = true;
				$do_add_fees_global_by_exclude = false;
				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
					$product_cats = $this->get_product_cats( $values['product_id'] );
					if ( ! empty( $include_cats ) ) {
						$the_intersect = array_intersect( $product_cats, $include_cats );
						if ( empty( $the_intersect ) ) {
							$do_add_fees_global_by_include = false;
						} else {
							// At least one product in the cart is ok, no need to check further
							return true;
						}
					}
					if ( ! empty( $exclude_cats ) ) {
						$the_intersect = array_intersect( $product_cats, $exclude_cats );
						if ( ! empty( $the_intersect ) ) {
							if ( 'for_all_cart' === get_option( 'alg_gateways_fees_cats_exclude_calc_type_' . $current_gateway, 'for_all_cart' ) ) {
								// At least one product in the cart is NOT ok, no need to check further
								return false;
							}
						} else {
							$do_add_fees_global_by_exclude = true;
						}
					}
				}
				if ( ! $do_add_fees_global_by_include && ! $do_add_fees_global_by_exclude ) {
					return false;
				}
			}
		}
		return true;
	}

	 /**
	 * get_the_fee.
	 *
	 * @version 2.1.0
	 * @since   1.2.0
	 */
	function get_the_fee( $args, $fee_num, $total_in_cart = 0, $is_info_only = false, $info_product_id = 0 ) {
		extract( $args );
		$final_fee_to_add = 0;
		if ( '' != $current_gateway && 'yes' === $is_enabled ) {
			global $woocommerce;
			if ( 0 == $total_in_cart ) {
				$total_in_cart = ( 'yes' === $exclude_shipping ) ?
					$woocommerce->cart->cart_contents_total :
					$woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total;
			}
			if ( $total_in_cart >= $min_cart_amount && ( 0 == $max_cart_amount || $total_in_cart <= $max_cart_amount ) ) {
				if ( 0 != $fee_value && 'fee_2' != $fee_num ) {
					if ( 0 != $product_id || $this->do_apply_fees_by_categories( 'fee_1', $current_gateway, $info_product_id ) ) {
						if ( ! $is_info_only && 0 == $product_id ) {
							$total_in_cart = $this->get_sum_for_fee_by_included_and_excluded_cats( $total_in_cart, 'fee_1', $current_gateway );
						}
						$final_fee_to_add = $this->calculate_the_fee( $args, $final_fee_to_add, $total_in_cart, 'fee_1' );
					}
				}
				if ( 0 != $fee_value_2 && 'fee_1' != $fee_num ) {
					if ( 0 != $product_id || $this->do_apply_fees_by_categories( 'fee_2', $current_gateway, $info_product_id ) ) {
						if ( ! $is_info_only && 0 == $product_id ) {
							$total_in_cart = $this->get_sum_for_fee_by_included_and_excluded_cats( $total_in_cart, 'fee_2', $current_gateway );
						}
						$final_fee_to_add = $this->calculate_the_fee( $args, $final_fee_to_add, $total_in_cart, 'fee_2' );
					}
				}
			}
		}
		return $final_fee_to_add;
	}

	/**
	 * maybe_add_cart_fee.
	 *
	 * @version 2.0.0
	 * @since   1.1.0
	 */
	function maybe_add_cart_fee( $args ) {
		extract( $args );
		if ( $fee_text == $fee_text_2 || '' == $fee_text_2 ) {
			$final_fee_to_add   = $this->get_the_fee( $args, 'fee_both' );
			$final_fee_to_add_2 = 0;
		} else {
			$final_fee_to_add   = $this->get_the_fee( $args, 'fee_1' );
			$final_fee_to_add_2 = $this->get_the_fee( $args, 'fee_2' );
		}
		if ( 0 != $final_fee_to_add || 0 != $final_fee_to_add_2 ) {
			global $woocommerce;
			$taxable = ( 'yes' === $is_taxable ) ? true : false;
			$tax_class_name = '';
			if ( $taxable ) {
				$tax_class_names = array_merge( array( '', ), WC_Tax::get_tax_classes() );
				$tax_class_name = $tax_class_names[ $tax_class_id ];
			}
			if ( 0 != $final_fee_to_add ) {
				$woocommerce->cart->add_fee( $fee_text, $final_fee_to_add, $taxable, $tax_class_name );
			}
			if ( 0 != $final_fee_to_add_2 ) {
				$woocommerce->cart->add_fee( $fee_text_2, $final_fee_to_add_2, $taxable, $tax_class_name );
			}
		}
	}
}

endif;

return new Alg_WC_Checkout_Fees();
