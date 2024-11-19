<?php
/**
 * Plugin Name: WooCommerce Extra Checkout Fields
 * Description: Adds "Bank Account" and "CUI" fields to the WooCommerce checkout page.
 * Version: 1.0
 * Author: Your Name
 */

// Add the custom fields
add_action( 'woocommerce_after_checkout_billing_form', 'add_custom_checkout_fields' );
function add_custom_checkout_fields( $checkout ) {
	echo '<div id="custom_checkout_fields"><h3>' . __( 'Additional Information' ) . '</h3>';

	woocommerce_form_field( 'bank_account', array(
		'type'        => 'text',
		'class'       => array( 'form-row-wide' ),
		'label'       => __( 'Bank Account' ),
		'placeholder' => __( 'Enter your bank account' ),
		'required'    => false,
	), $checkout->get_value( 'bank_account' ) );

	woocommerce_form_field( 'cui', array(
		'type'        => 'text',
		'class'       => array( 'form-row-wide' ),
		'label'       => __( 'CUI' ),
		'placeholder' => __( 'Enter your CUI' ),
		'required'    => false,
	), $checkout->get_value( 'cui' ) );

	echo '</div>';
}

// Validate the custom fields
add_action( 'woocommerce_checkout_process', 'validate_custom_checkout_fields' );
function validate_custom_checkout_fields() {
	if ( ! $_POST['bank_account'] ) {
		wc_add_notice( __( 'Please enter your Bank Account.' ), 'error' );
	}
	if ( ! $_POST['cui'] ) {
		wc_add_notice( __( 'Please enter your CUI.' ), 'error' );
	}
}

// Save the custom fields
add_action( 'woocommerce_checkout_update_order_meta', 'save_custom_checkout_fields' );
function save_custom_checkout_fields( $order_id ) {
	if ( ! empty( $_POST['bank_account'] ) ) {
		update_post_meta( $order_id, 'Bank Account', sanitize_text_field( $_POST['bank_account'] ) );
	}
	if ( ! empty( $_POST['cui'] ) ) {
		update_post_meta( $order_id, 'CUI', sanitize_text_field( $_POST['cui'] ) );
	}
}

// Display custom fields in the admin order details
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_user_fields_in_admin_order', 10, 1 );
function display_custom_user_fields_in_admin_order( $order ) {
	$bank_account = get_post_meta( $order->get_id(), 'Bank Account', true );
	$cui          = get_post_meta( $order->get_id(), 'CUI', true );

	if ( $bank_account ) {
		echo '<p><strong>' . __( 'Bank Account' ) . ':</strong> ' . $bank_account . '</p>';
	}
	if ( $cui ) {
		echo '<p><strong>' . __( 'CUI' ) . ':</strong> ' . $cui . '</p>';
	}
}


function redirect_to_custom_checkout() {
	if ( is_checkout() && ! is_wc_endpoint_url() ) {
		wp_redirect( home_url( '/spa-checkout' ) ); // Replace with your custom page URL
		exit;
	}
}

add_action( 'template_redirect', 'redirect_to_custom_checkout' );


class WooCustomTemplateOverrides {

	public function __construct() {
		add_filter( 'woocommerce_locate_template', array( $this, 'override_woocommerce_template' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_woocommerce_styles_and_scripts' ) );
	}

	/**
	 * Override WooCommerce template files
	 */
	public function override_woocommerce_template( $template, $template_name, $template_path ) {
		// Define the custom path for your template overrides
		$custom_path = plugin_dir_path( __FILE__ ) . 'woocommerce/templates/' . $template_name;

		// If the custom template exists, use it
		return file_exists( $custom_path ) ? $custom_path : $template;
	}

	/**
	 * Enqueue WooCommerce styles and scripts
	 */
	public function enqueue_woocommerce_styles_and_scripts() {
		if ( class_exists( 'WooCommerce' ) ) {

				// Enqueue checkout page-specific styles
				wp_enqueue_style( 'woocommerce-checkout' );
				wp_enqueue_style( 'woocommerce-smallscreen-checkout' );

				// Enqueue checkout-related scripts
				wp_enqueue_script( 'wc-checkout' );
				wp_enqueue_script( 'wc-credit-card-form' );
				wp_enqueue_script( 'wc-checkout-frontend' );


		}
	}
}

new WooCustomTemplateOverrides();