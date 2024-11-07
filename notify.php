<?php

class notify {
	public function notify_curl_on_purchase( $order_id ) {
		$order = wc_get_order( $order_id );

		foreach ( $order->get_items() as $item_id => $item ) {
			$product_id = $item->get_product_id();
			$quantity   = $item->get_quantity();

			$product_data = [
				'product_id' => $product_id,
				'quantity'   => $quantity,
				'order_id'   => $order_id,
				'customer'   => [
					'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
					'email' => $order->get_billing_email(),
				]
			];

			$this->soap->send_curl_request( $this->notification_endpoint_url, $product_data );
		}
	}

	private function client_register() {
		$url                 = $this->import_endpoint_url;
		$data                = [];
		$this->soap_request = $this->soap_request_for_client_registration( $url, $data );
		$this->soap->send_curl_request( $url, 'http://Microsoft.ServiceModel.Samples/ICalculator/Client_Registration_RS', $this - soap_request );
	}
}