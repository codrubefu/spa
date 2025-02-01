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
		$custom_field_partner_order_success = get_post_meta( $order->get_id(), '_custom_field_partner_order_success', true );

		if($custom_field_user_order_success && $custom_field_user_info_success && $custom_field_partner_order_success){
			return;
		}
		$x = 0;
		foreach ($order->get_items() as $item_id => $item) {
			$first_name =  wc_get_order_item_meta( $item_id , 'Prenume' );
			$first_name =  parseMetaToArray( $first_name );
			$last_name =  wc_get_order_item_meta( $item_id , 'Nume' );
			$last_name = parseMetaToArray( $last_name );
			$phone =  wc_get_order_item_meta( $item_id , 'Telefon' );
			$phone =  parseMetaToArray( $phone );
			$email = wc_get_order_item_meta( $item_id ,'E-mail' );
			$email = parseMetaToArray( $email );

			foreach ($first_name as $key => $value) {
				$beneficiary[$x]['first_name'] = $first_name[$key];
				$beneficiary[$x]['last_name'] = $last_name[$key];
				$beneficiary[$x]['phone'] = $phone[$key];
				$beneficiary[$x]['email'] = $email[$key]; ;
				$x++;
			}
		}

		$customerId = $this->customer->sendCustomerToSoap( $order );
		$beneficiariesInfo=[];
		$clintIds=[];

		foreach ($beneficiary as $key => $value) {
			if($value['phone'] === $order->get_billing_phone() && $value['email'] === $order->get_billing_email()){
				$beneficiariesInfo[] = $customerId;
				continue;
			}

			$beneficiariesInfo[] = $this->customer->sendBeneficiaryToSoap( $value ,$order,$key);
		}

		foreach ($beneficiariesInfo as $item) {
			$beneficiariesIds[] = $item[0];
			$clintIds[] = $item[1];
		}

		$partnerId = 0;
		if(trim($order->get_billing_company()) !== ''){
			$partnerId = $this->partner->sendPartnerToSoap($order,$customerId[0]);
		}

		if($customerId[0]){
			$this->order->sendOrderToSoap( $order, $customerId[0],$partnerId,$beneficiariesIds,$clintIds );
		}

		if ( $order ) {
			// Your custom code here
			error_log( 'Order ' . $order_id . ' has been completed and paid.' );
		}
	}


}

new finalize_order();
