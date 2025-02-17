<?php

add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_fields_in_admin_order', 10, 1 );

function display_custom_fields_in_admin_order( $order ) {
	$custom_field_user_info          = get_post_meta( $order->get_id(), '_custom_field_user_info', true );
	$custom_partner_order_info         = get_post_meta( $order->get_id(), '_custom_field_order_info', true );
	$custom_field_order_info         = get_post_meta( $order->get_id(), '_custom_field_order_info', true );
	$custom_field_user_info_success  = get_post_meta( $order->get_id(), '_custom_field_user_info_success', true );
	$custom_field_order_info_success  = get_post_meta( $order->get_id(), '_custom_field_user_order_success', true );

	if ( $custom_field_user_info_success == 1 && $custom_field_order_info_success == 1 ) {
		echo '<p style="color:green;font-size:15px"><strong>Spa Soft Info:</strong> Successfully sent to Spa Soft</p>';
	} else {
		echo '<p  style="color:red;font-size:15px"><strong>Spa Soft Info:</strong> Failed to send to Spa Soft</p>';
		echo '<a href="' . home_url( '/?resend_order=' . $order->get_id() ) . '" >Retrimite orderul catre server</a>';	}

	echo '<div id="custom-fields-section" style="margin-top: 20px;">';
	echo '<h3 style="cursor: pointer;" onclick="toggleCustomFields()">Spa Soft Info (apa aici pentru detalii)</h3>';
	echo '<div id="custom-fields-content" style="display: none;">';

	customFieldToTable( $custom_field_user_info, 'User Info' );
	customFieldToTable( $custom_field_order_info, 'Order Info' );
	customFieldToTable( $custom_partner_order_info, 'Partner Info' );

	echo '</div>';
	echo '</div>';
	echo '<script>
    function toggleCustomFields() {
        var content = document.getElementById("custom-fields-content");
        if (content.style.display === "none") {
            content.style.display = "block";
        } else {
            content.style.display = "none";
        }
    }
</script>';
}

function customFieldToTable( $field, $title ) {
	if ( ! empty( $field ) ) {
		$result = json_decode( $field, true );
		if ( is_array( $result ) ) {
			echo '<h3>' . $title . '</h3>';
			echo '<table class="wp-list-table widefat fixed striped">';
			echo '<thead><tr><th>Key</th><th>Value</th></tr></thead>';
			echo '<tbody>';
			foreach ( $result as $key => $value ) {
				echo '<tr>';
				echo '<td>' . esc_html( $key ) . '</td>';
				echo '<td>' . esc_html( $value ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} else {
			echo '<p><strong>' . $title . ' response:</strong> ' . esc_html( $field ) . '</p>';
		}
	}
}

add_action('init', 'register_custom_order_status');
function register_custom_order_status() {
	register_post_status('wc-spa-error-status', array(
		'label'                     => 'Comunicarea cu servery-ul Spa Soft a esuat',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop('Eroare (%s)', 'Erori (%s)'),
	));
}

add_filter('wc_order_statuses', 'add_custom_order_status');
function add_custom_order_status($order_statuses) {
	$new_statuses = array();

	foreach ($order_statuses as $key => $status) {
		$new_statuses[$key] = $status;

		// Insert the custom status after a specific default status, if needed
		if ('wc-pending' === $key) {
			$new_statuses['wc-spa-error-status'] = 'Comunicarea cu Master Spa a esuat';
		}
	}

	return $new_statuses;
}

add_action( 'admin_enqueue_scripts', 'enqueue_custom_admin_styles' );
function enqueue_custom_admin_styles() {
	echo '<style>
        .order-status.status-spa-error-status {
	         background: red !important;
	         color: white !important;
            border:1px solid red !important;
        }
    </style>';
}