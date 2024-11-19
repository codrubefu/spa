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
		add_action( 'woocommerce_order_status_completed', array( $this, 'onOrderCompleted' ), 10, 1 );
	}

	// Define the function that will run when an order is completed
	public function onOrderCompleted( $order_id ): void {
		// Ensure the order exists and is paid
		$order = wc_get_order( $order_id );


		$customerId = $this->customer->sendCustomerToSoap( $order );
		if($customerId){
			$this->order->sendOrderToSoap( $order, $customerId );
		}

		//	$this->partner->sendPartnerToSoap($order);


		if ( $order ) {
			// Your custom code here
			error_log( 'Order ' . $order_id . ' has been completed and paid.' );

			// Example: Send a custom email, call an external API, etc.
			// $this->send_custom_notification( $order );
		}
	}


}

new finalize_order();
