<?php

class CustomCheckoutFields {
	private array $custom_checkout_fields = [];

	public function __construct() {

		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'save_custom_checkout_fields' ] );
		add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_custom_user_fields_in_admin_order' ],5 );
		add_action( 'template_redirect', [ $this, 'redirect_to_custom_checkout' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_inline_css' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'add_inline_js' ] );
		add_filter( 'woocommerce_checkout_fields', [ $this, 'reorder_billing_fields_with_priority' ] );
		add_filter( 'woocommerce_locate_template', array( $this, 'override_woocommerce_template' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_woocommerce_styles_and_scripts' ) );
		add_shortcode( 'custom_checkout_fields', [ $this, 'shortcode_custom_checkout_fields' ] );

		$this->custom_checkout_fields = [
			'type' => [ 'label' => __( 'Type' ), 'placeholder' => __( 'Type' ) ],
			'billing_cui' => [ 'label' => __( 'Cui' ), 'placeholder' => __( 'CUI' ) ],
			'reg_com' => [ 'label' => __( 'Reg.Com.' ), 'placeholder' => __( 'Registrul Comertului' ) ],
			'bank' => [ 'label' => __( 'Banca' ), 'placeholder' => __( 'Banca' ) ],
			'iban' => [ 'label' => __( 'Cont IBAN' ), 'placeholder' => __( 'Cont IBAN' ) ],
		];
	}

	public function shortcode_custom_checkout_fields() {
		ob_start();
		$this->add_custom_checkout_fields( WC()->checkout() );
		return ob_get_clean();
	}

	// Add the custom fields
	public function add_custom_checkout_fields( $checkout ) {
		foreach ( $this->custom_checkout_fields as $key => $field ) {
			if($key == 'type') {
				continue;
			}
			woocommerce_form_field( $key, array(
				'type'        => 'text',
				'class'       => array( 'form-row-wide' ),
				'label'       => __( $field['label'] ),
				'placeholder' => __( $field['placeholder'] ),
				'required'    => false,
			), $_SESSION[ $key ] );
		}
	}

	// Save the custom fields
	public function save_custom_checkout_fields( $order_id ) {
		foreach ( $this->custom_checkout_fields as $key => $field ) {
			if ( ! empty( $_POST[ $key ] ) ) {
				update_post_meta( $order_id, $key , sanitize_text_field( $_POST[ $key ] ) );
			}
		}
	}

// Display custom fields in the admin order details
	public function display_custom_user_fields_in_admin_order( $order ) {

		foreach ($this->custom_checkout_fields as $key => $field) {
			$value = get_post_meta( $order->get_id(),$key, true );

			if ( $value ) {
				$info[] =  '<p><strong>' . __( $field['label'] ) . ':</strong> ' . $value . '</p>';
			}
		}

		if(!empty($info)){
			echo "<h2>Informatii companie</h2>";
			echo implode('',$info);
		}
	}

	// Redirect to custom checkout page
	public function redirect_to_custom_checkout() {
		if ( is_checkout() && ! is_wc_endpoint_url() && $_SERVER['REQUEST_URI'] == '/checkout/' ) {
			wp_redirect( home_url( '/spa-checkout' ) );
			exit;
		}
	}

	// Reorder billing fields with priority
	public function reorder_billing_fields_with_priority( $fields ) {
		$priorities = [
			'billing_first_name' => 10,
			'billing_last_name' => 20,
			'billing_phone' => 30,
			'billing_email' => 40,
			'billing_company' => 50,
			'billing_city' => 60,
			'billing_postcode' => 60,
			'billing_state' => 70,
			'billing_country' => 80,
			'billing_address_1' => 90,
			'billing_address_2' => 100,
		];

		foreach ( $priorities as $field => $priority ) {
			if ( isset( $fields['billing'][ $field ] ) ) {
				$fields['billing'][ $field ]['priority'] = $priority;
			}
		}

		return $fields;
	}

	// Add inline CSS
	public function add_inline_css() {
		$custom_css = "
            #custom_checkout_fields {
                display: none;
            }
        ";
		wp_add_inline_style( 'woocommerce-inline', $custom_css );
	}

	// Add inline JavaScript
	public function add_inline_js() {
		$custom_js = "
            jQuery(document).ready(function($) {
                $('#type').on('change', function() {
                    if ($(this).val() == '2') {
                        $('#custom_checkout_fields').hide();
                    } else if ($(this).val() == '1') {
                        $('#custom_checkout_fields').show();
                    }
                });
            });
            
            jQuery(function($) {
			    // Listen for the button click
			    $('#confirm-billing').on('click', function(e) {
			        e.preventDefault(); // Prevent default button behavior if it's a form button
			        $('body').trigger('update_checkout');
			    });
			});
        ";
		wp_add_inline_script( 'jquery', $custom_js );
	}

	/**
	 * Override WooCommerce template files
	 */
	public function override_woocommerce_template( $template, $template_name, $template_path ) {
		// Define the custom path for your template overrides

		$custom_path = plugin_dir_path( __FILE__ ) . '../woocommerce/templates/' . $template_name;

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

new CustomCheckoutFields();
