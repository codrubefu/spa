<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Enqueue JavaScript for AJAX.
add_action( 'wp_enqueue_scripts', 'custom_enqueue_checkout_scripts' );
function custom_enqueue_checkout_scripts() {

	wp_enqueue_script( 'custom-checkout-ajax', plugin_dir_url( __FILE__ ) . '../src/custom-checkout.js', [ 'jquery' ], '1.0', true );
	wp_localize_script( 'custom-checkout-ajax', 'custom_ajax_object', [
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'custom_checkout_nonce' ),
	] );

}


add_action( 'wp_ajax_update_checkout', 'update_checkout_handler' );
add_action( 'wp_ajax_nopriv_update_checkout', 'update_checkout_handler' );

add_action( 'wp_ajax_update_info', 'update_info_handler' );
add_action( 'wp_ajax_nopriv_update_info', 'update_info_handler' );


function update_info_handler(): void {
	check_ajax_referer( 'custom_checkout_nonce', 'security' );
	parse_str( $_POST['form_data'], $checkout_data );

	WC()->cart->cart_contents[ $checkout_data['cart_item_key'] ]['first_name'][ $checkout_data['order'] ] = $checkout_data['first_name'];
	WC()->cart->cart_contents[ $checkout_data['cart_item_key'] ]['last_name'] [ $checkout_data['order'] ] = $checkout_data['last_name'];
	WC()->cart->cart_contents[ $checkout_data['cart_item_key'] ]['phone'][ $checkout_data['order'] ]      = $checkout_data['phone'];
	WC()->cart->cart_contents[ $checkout_data['cart_item_key'] ]['email'][ $checkout_data['order'] ]      = $checkout_data['email'];
	// Manually update the session with the new cart contents
	WC()->cart->set_session();

	wp_send_json_success( [
		'message' => 'Checkout updated successfully.',
	] );
}

function update_checkout_handler(): void {

	check_ajax_referer( 'custom_checkout_nonce', 'security' );

	parse_str( $_POST['form_data'], $checkout_data );

	$errors        = [];
	$response_data = [];

	// Validate required fields (example: billing email).
	if ( empty( $checkout_data['billing_email'] ) || ! is_email( $checkout_data['billing_email'] ) ) {
		$errors['billing_email'] = 'Please enter a valid email address.';
	}

	if ( empty( $checkout_data['billing_first_name'] ) ) {
		$errors['billing_first_name'] = 'First name is required.';
	}

	if ( empty( $checkout_data['billing_last_name'] ) ) {
		$errors['billing_last_name'] = 'Last name is required.';
	}

	// Add more validations for other fields as needed.
	// Update WooCommerce customer data if no errors.
	$customer = WC()->customer;
	if ( empty( $errors ) ) {
		$customer->set_props( [
			'billing_first_name' => sanitize_text_field( $checkout_data['billing_first_name'] ),
			'billing_last_name'  => sanitize_text_field( $checkout_data['billing_last_name'] ),
			'billing_email'      => sanitize_email( $checkout_data['billing_email'] ),
			'billing_address_1'  => sanitize_text_field( $checkout_data['billing_address_1'] ),
			'billing_city'       => sanitize_text_field( $checkout_data['billing_city'] ),
			'billing_postcode'   => sanitize_text_field( $checkout_data['billing_postcode'] ),
			'billing_phone'      => sanitize_text_field( $checkout_data['billing_phone'] ),
			'billing_company'      => sanitize_text_field( $checkout_data['billing_company'] ),
		] );
		WC()->customer->save();

		$cart = WC()->cart->get_cart();
		$custom_checkout_fields = [
			'type' => [ 'label' => __( 'type' ), 'placeholder' => __( 'type' ) ],
			'billing_cui' => [ 'label' => __( 'Cui' ), 'placeholder' => __( 'CUI' ) ],
			'reg_com' => [ 'label' => __( 'Reg.Com.' ), 'placeholder' => __( 'Registrul Comertului' ) ],
			'bank' => [ 'label' => __( 'Banca' ), 'placeholder' => __( 'Banca' ) ],
			'iban' => [ 'label' => __( 'Cont IBAN' ), 'placeholder' => __( 'Cont IBAN' ) ],
		];
		foreach ( $custom_checkout_fields as $key => $field ) {
			parse_str($_POST['form_data'], $output_array);

			if ( ! empty( $output_array[ $key ] ) ) {
				$_SESSION[ $key ] = sanitize_text_field( $output_array[ $key ] );
			}
		}

		foreach ( $cart as $cart_item_key => $cart_item ) {
			$x    = 0;
			while ( $x < WC()->cart->cart_contents[ $cart_item_key ]['quantity'] ) {
				WC()->cart->cart_contents[ $cart_item_key ]['first_name'][ $x ] = $customer->get_billing_first_name();
				WC()->cart->cart_contents[ $cart_item_key ]['last_name'][ $x ]  = $customer->get_billing_last_name();
				WC()->cart->cart_contents[ $cart_item_key ]['phone'] [ $x ]     = $customer->get_billing_phone();
				WC()->cart->cart_contents[ $cart_item_key ]['email'] [ $x ]     = $customer->get_billing_email();
				$x++;
			}


		}
		// Manually update the session with the new cart contents
		WC()->cart->set_session();
		wp_send_json_success( [
			'message' => 'Checkout updated successfully.',
		] );

	} else {
		wp_send_json_error( [
			'errors' => $errors,
		] );
	}
}


add_action( 'woocommerce_checkout_create_order_line_item', 'add_custom_meta_to_order_items', 10, 4 );

function add_custom_meta_to_order_items( $item, $cart_item_key, $values, $order ) {

	// Check if the first_name is set in the cart item
	if ( isset( $values['first_name'] ) ) {
		// Add it as order item meta
		$item->add_meta_data( 'Prenume', json_encode( $values['first_name'] ), true );
	}

	if ( isset( $values['last_name'] ) ) {
		// Add last name as well, if needed
		$item->add_meta_data( 'Nume', json_encode( $values['last_name'] ), true );
	}
	if ( isset( $values['phone'] ) ) {
		// Add last name as well, if needed
		$item->add_meta_data( 'Telefon', json_encode( $values['phone'] ), true );
	}

	if ( isset( $values['email'] ) ) {
		// Add last name as well, if needed
		$item->add_meta_data( 'E-mail', json_encode( $values['email'] ), true );
	}
}

add_filter( 'woocommerce_hidden_order_itemmeta', 'add_custom_hidden_order_itemmeta' );

function add_custom_hidden_order_itemmeta( $hidden_meta_keys ) {
	// Add your custom meta keys to the array
	$hidden_meta_keys[] = 'Prenume';
	$hidden_meta_keys[] = 'Nume';
	$hidden_meta_keys[] = 'Telefon';
	$hidden_meta_keys[] = 'E-mail';

	return $hidden_meta_keys;
}

add_action( 'woocommerce_after_order_itemmeta', 'display_custom_meta_in_admin', 10, 1 );


function display_custom_meta_in_admin( $order_item_id ) {

	$first_name =  wc_get_order_item_meta( $order_item_id , 'Prenume' );
	$first_name = parseMetaToArray( $first_name );

	$last_name =  wc_get_order_item_meta( $order_item_id , 'Nume' );
	$last_name = parseMetaToArray( $last_name );

	$phone =  wc_get_order_item_meta( $order_item_id , 'Telefon' );
	$phone = parseMetaToArray( $phone );

	$email = wc_get_order_item_meta( $order_item_id ,'E-mail' );
	$email = parseMetaToArray( $email );
	$x     = 0;

	while ( $x < count( $first_name ) ) {
		echo "<div><hr>";
		echo "<h4>Client " . ( $x + 1 ) . "</h4>";
		if ( ! empty( $first_name ) ) {
			echo '<p><strong>' . __( 'Nume:', 'woocommerce' ) . '</strong> ' . esc_html( $first_name[ $x ] ) . '</p>';
		}

		if ( ! empty( $last_name ) ) {
			echo '<p><strong>' . __( 'Prenume:', 'woocommerce' ) . '</strong> ' . esc_html( $last_name[ $x ] ) . '</p>';
		}

		if ( ! empty( $phone ) ) {
			echo '<p><strong>' . __( 'Telefon:', 'woocommerce' ) . '</strong> ' . esc_html( $phone[ $x ] ) . '</p>';

			if ( ! empty( $email ) ) {
				echo '<p><strong>' . __( 'Email:', 'woocommerce' ) . '</strong> ' . esc_html( $email[ $x ] ) . '</p>';
			}
			$x ++;
		}
		echo "</div>";
	}
}

function parseMetaToArray( $meta ) {
	return json_decode( $meta, true );
}