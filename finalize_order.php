<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class finalize_order {
	private order $order;
	private customer $customer;
	private partner $partner;

	public function __construct() {

		$this->order    = new order();
		$this->customer = new customer();
		$this->partner  = new partner();
		// Hook into the order status completed action
		add_action( 'woocommerce_order_status_processing', array( $this, 'onOrderCompleted' ), 10, 1 );
	}

	// Define the function that will run when an order is completed
	public function onOrderCompleted( $order_id ): void {
		// Ensure the order exists and is paid
		// Get latest 3 orders.
		$args = array(
			'id' => $order_id,
		);
		$orders = wc_get_orders( $args );
		if ( ! $orders ) {
			error_log( 'Order not found: ' . $order_id );
			return;
		}
		$order = $orders[0];
		$custom_field_user_info_success  = get_post_meta( $order->get_id(), '_custom_field_user_info_success', true );
		$custom_field_user_order_success = get_post_meta( $order->get_id(), '_custom_field_user_order_success', true );

		if($custom_field_user_order_success && $custom_field_user_info_success){
			return;
		}

		$customerId = $this->customer->sendCustomerToSoap( $order );
		if($customerId){
			$this->order->sendOrderToSoap( $order, $customerId );
		}

		//	$this->partner->sendPartnerToSoap($order);


		if ( $order ) {
			// Your custom code here
			error_log( 'Order ' . $order_id . ' has been completed and paid.' );
		}
	}


}

new finalize_order();
