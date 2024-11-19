<?php

add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_fields_in_admin_order', 10, 1 );

function display_custom_fields_in_admin_order( $order ) {
	$custom_field_user_info          = get_post_meta( $order->get_id(), '_custom_field_user_info', true );
	$custom_field_order_info         = get_post_meta( $order->get_id(), '_custom_field_order_info', true );
	$custom_field_user_info_success  = get_post_meta( $order->get_id(), '_custom_field_user_info_success', true );
	$custom_field_user_order_success = get_post_meta( $order->get_id(), '_custom_field_user_order_success', true );

	if ( $custom_field_user_info_success && $custom_field_user_info_success ) {
		echo '<p style="color:green;font-size:15px"><strong>Spa Soft Info:</strong> Successfully sent to Spa Soft</p>';
	} else {
		echo '<p  style="color:red;font-size:15px"><strong>Spa Soft Info:</strong> Failed to send to Spa Soft</p>';
		echo '<a href="http://localhost:8090/?resend_order='.$order->get_id().'" >Retrimite orderul catre server</a>';
	}

	echo '<div id="custom-fields-section" style="margin-top: 20px;">';
	echo '<h3 style="cursor: pointer;" onclick="toggleCustomFields()">Spa Soft Info (click to minimize)</h3>';
	echo '<div id="custom-fields-content" style="display: none;">';

	customFieldToTable( $custom_field_user_info, 'User Info' );
	customFieldToTable( $custom_field_order_info, 'Order Info' );

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