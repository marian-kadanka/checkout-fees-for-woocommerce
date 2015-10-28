<?php
/**
 * Checkout Fees for WooCommerce - Per Product Meta Boxes
 *
 * @version 1.3.0
 * @since   1.1.0
 * @author  Algoritmika Ltd.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Alg_WC_Checkout_Fees_Settings_Per_Product' ) ) :

class Alg_WC_Checkout_Fees_Settings_Per_Product {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id   = 'per_product';
		$this->desc = __( 'Checkout Fees and Discounts', 'alg-woocommerce-fees' );

		if ( 'yes' === get_option( 'alg_woocommerce_checkout_fees_per_product_enabled' ) ) {
			add_action( 'add_meta_boxes',    array( $this, 'add_meta_box' ) );
			add_action( 'save_post_product', array( $this, 'save_meta_box' ), PHP_INT_MAX, 2 );
			add_action( 'admin_init',        array( $this, 'register_styles' ) );
			add_action( 'admin_init',        array( $this, 'enqueue_styles' ) );
		}

	}

	/**
	 * register_styles.
	 */
	function register_styles() {
		wp_register_style( 'checkout-fees-admin', plugins_url( 'css/checkout-fees-admin.css', __FILE__ ) );
	}

	/**
	 * enqueue_styles.
	 */
	function enqueue_styles() {
		wp_enqueue_style( 'checkout-fees-admin' );
	}

	/**
	 * get_meta_box_options.
	 *
	 * @version 1.3.0
	 */
	function get_meta_box_options() {
		return array(
			array(
				'name'    => 'alg_checkout_fees_enabled',
				'default' => '',
				'type'    => 'checkbox',
				'title'   => '',
			),
			array(
				'name'    => 'alg_checkout_fees_title',
				'default' => '',
				'type'    => 'text',
				'title'   => __( 'Fee/Discount', 'alg-woocommerce-fees' ) . ' ' . __( 'Title', 'alg-woocommerce-fees' ),
			),
			array(
				'name'    => 'alg_checkout_fees_type',
				'default' => '',
				'type'    => 'select',
				'title'   => __( 'Fee Type', 'alg-woocommerce-fees' ),
				'options'   => array(
					'fixed'   => __( 'Fixed', 'alg-woocommerce-fees' ),
					'percent' => __( 'Percent', 'alg-woocommerce-fees' ),
				),
			),
			array(
				'name'    => 'alg_checkout_fees_value',
				'default' => '',
				'type'    => 'number',
				'title'   => __( 'Fee Value', 'alg-woocommerce-fees' ),
				'custom_atts' => ' step="0.0001"',
			),
			array(
				'name'    => 'alg_checkout_fees_type_2',
				'default' => '',
				'type'    => 'select',
				'title'   => __( 'Additional Fee Type (Optional)', 'alg-woocommerce-fees' ),
				'options'   => array(
					'fixed'   => __( 'Fixed', 'alg-woocommerce-fees' ),
					'percent' => __( 'Percent', 'alg-woocommerce-fees' ),
				),
			),
			array(
				'name'    => 'alg_checkout_fees_value_2',
				'default' => '',
				'type'    => 'number',
				'title'   => __( 'Additional Fee Value (Optional)', 'alg-woocommerce-fees' ),
				'custom_atts' => ' step="0.0001"',
			),
			array(
				'name'    => 'alg_checkout_fees_min_cart_amount',
				'default' => '',
				'type'    => 'number',
				'title'   => __( 'Minimum Amount', 'alg-woocommerce-fees' ),
				'custom_atts' => ' step="0.0001" min="0"',
			),
			array(
				'name'    => 'alg_checkout_fees_max_cart_amount',
				'default' => '',
				'type'    => 'number',
				'title'   => __( 'Maximum Amount', 'alg-woocommerce-fees' ),
				'custom_atts' => ' step="0.0001" min="0"',
			),
			array(
				'name'    => 'alg_checkout_fees_rounding_enabled',
				'default' => '',
				'type'    => 'checkbox',
				'title'   => __( 'Rounding', 'alg-woocommerce-fees' ),
			),
			array(
				'name'    => 'alg_checkout_fees_rounding_precision',
				'default' => '',
				'type'    => 'number',
				'title'   => __( 'Rounding Precision', 'alg-woocommerce-fees' ),
				'custom_atts' => ' step="1" min="0"',
			),
			array(
				'name'    => 'alg_checkout_fees_tax_enabled',
				'default' => '',
				'type'    => 'checkbox',
				'title'   => __( 'Taxes', 'alg-woocommerce-fees' ),
			),
			array(
				'name'    => 'alg_checkout_fees_tax_class',
				'default' => '',
				'type'    => 'select',
				'title'   => __( 'Tax Class', 'alg-woocommerce-fees' ),
				'options' => array_merge( array( __( 'Standard Rate', 'alg-woocommerce-fees' ) ), WC_Tax::get_tax_classes() ),
			),
		);
	}

	/**
	 * save_meta_box.
	 */
	function save_meta_box( $post_id, $post ) {

		// Check if we are saving with current metabox displayed
		if ( ! isset( $_POST[ 'alg_checkout_fees_' . $this->id . '_save_post' ] ) ) {
			return;
		}
		 // Save options
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();
		$gateway_key = 'bacs';
		$gateway = $available_gateways[ $gateway_key ];
		foreach ( $this->get_meta_box_options() as $option ) {
			$option_name = $option['name'] . '_' . $gateway_key;
			$option_value = isset( $_POST[ $option_name ] ) ? $_POST[ $option_name ] : $option['default'];
			if ( 'checkbox' === $option['type'] ) $option_value = ( '' == $option_value ) ? 'no' : 'yes';
			update_post_meta( $post_id, '_' . $option_name, $option_value );
		}
	}

	/**
	 * add_meta_box.
	 */
	function add_meta_box() {
		add_meta_box(
			'alg-' . $this->id,
			$this->desc,
			array( $this, 'create_meta_box' ),
			'product',
			'normal',
			'high'
		);
	}

	/**
	 * create_meta_box.
	 */
	function create_meta_box() {

		$current_post_id = get_the_ID();
		global $woocommerce;
		$available_gateways = $woocommerce->payment_gateways->payment_gateways();

		$html = '';
		$html .= '<ul class="tabs">';

			// Tab Labels
			$html .= '<li class="labels">';
			foreach ( $available_gateways as $gateway_key => $gateway ) {
				$gateway_title = ( '' == $gateway->title ) ? $gateway_key : $gateway->title;
				$html .= '<label for="tab-' . $gateway_key . '" id="label-' . $gateway_key . '">' . $gateway_title . '</label>';

			}
			$html .= '</li>';

			// Tab Content
			$i = 0;
			foreach ( $available_gateways as $gateway_key => $gateway ) {
				$i++;
				$html .= '<li>';
					$gateway_title = ( '' == $gateway->title ) ? $gateway_key : $gateway->title;
					$html .= '<input type="radio" id="tab-' . $gateway_key . '" name="tabs"' . checked( $i, 1, false ) . '>';
					$html .= '<div class="tab-content" id="tab-content-' . $gateway_key . '">';
						$html .= ( 1 != $i ) ? '<div>'
							. __( 'In free version only Direct Bank Transfer (BACS) fees are available on per product basis.', 'alg-woocommerce-fees' ) . ' '
							. __( 'Please visit <a href="http://coder.fm/item/checkout-fees-for-woocommerce-plugin/">Checkout Fees for WooCommerce plugin page</a>.', 'alg-woocommerce-fees' )
							. '</div>' : '';
						$html .= '<table>';
						foreach ( $this->get_meta_box_options() as $option ) {
							if ( ! isset( $option['custom_atts'] ) ) $option['custom_atts'] = '';
							$option['custom_atts'] = ( 'bacs' != $gateway_key ) ? ' readonly="readonly"' : '';
							$option_name = $option['name'] . '_' . $gateway_key;
							$option_value = get_post_meta( $current_post_id, '_' . $option_name, true );
							$option_title = ( '' == $option['title'] ) ? '<span style="font-size:large;font-weight:bold;">' . $gateway_title . '</span>' : $option['title'];
							if ( 'checkbox' === $option['type'] ) {
								$option['custom_atts'] .= checked( $option_value, 'yes', false );
								$option['custom_atts'] .= ( 'bacs' != $gateway_key ) ? ' disabled="disabled"' : '';
							}
							$input_ending = ' id="' . $option_name . '" name="' . $option_name . '" value="' . $option_value . '"' . $option['custom_atts'] . '>';
							$select_options = '';
							if ( isset( $option['options'] ) ) {
								foreach ( $option['options'] as $select_option_key => $select_option_value ) {
									$select_options .= '<option value="' . $select_option_key . '"' . selected( $option_value, $select_option_key, false ) . '>' . $select_option_value . '</option>';
								}
							}
							$field_html = '';
							switch ( $option['type'] ) {
								case 'checkbox':
									$field_html = '<input class="short" type="' . $option['type'] . '"' . $input_ending . ' ' . __( 'Enable', 'alg-woocommerce-fees' );
									break;
								case 'text':
									$field_html = '<input class="short" type="' . $option['type'] . '"' . $input_ending;
									break;
								case 'number':
									$field_html = '<input class="short" type="' . $option['type'] . '"' . $input_ending;
									break;
								case 'select':
									$ro = ( 'bacs' != $gateway_key ) ? ' disabled="disabled"' : '';
									$field_html = '<select name="' . $option_name . '" id="' . $option_name . '" style="" class=""' . $ro . '>' . $select_options . '</select>';
									break;
							}
							$html .= '<tr>';
							$html .= '<th style="text-align:right;padding:10px;">' . $option_title . '</th>';
							$html .= '<td>' . $field_html . '</td>';
							$html .= '</tr>';
						}
						$html .= '</table>';
					$html .= '</div>';
				$html .= '</li>';
			}
		$html .= '</ul>';
		echo $html;
		echo '<input type="hidden" name="alg_checkout_fees_' . $this->id . '_save_post" value="alg_checkout_fees_' . $this->id . '_save_post">';
	}

}

endif;

return new Alg_WC_Checkout_Fees_Settings_Per_Product();
