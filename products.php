<?php

require_once 'services.php';
require_once 'soap.php';

class products {

	private services $services;
	private soap $soap;

	public function __construct() {
		$this->services = new services();
		$this->soap     = new soap();
	}

	public function import_products() {
		$product_data = $this->services->loadServices();

		if ( $product_data && is_array( $product_data ) ) {
			foreach ( $product_data as $product ) {
				$this->import_product( $product );
			}
		}
	}

	/**
	 * @throws WC_Data_Exception
	 * @throws Exception
	 */
	private function import_product( $product_data ) {

		$sku = $product_data['AID'];
		$existing_product_id = wc_get_product_id_by_sku( $sku );

		if ( $existing_product_id ) {
			$product = new WC_Product( $existing_product_id );
			$info    = 'modificat';
		} else {
			$product = new WC_Product();
			$product->set_sku( $sku );
			$info = 'adaguat';
		}


		$product->set_name( $product_data['ART'] );
		$product->set_price( $product_data['PRC'] );
		$product->set_description( $product_data['TBS'] );
		$product->set_regular_price( $product_data['PRC'] );
		$product->set_stock_quantity( 100000 );
		$product->set_manage_stock( false );
		$product->save();
		$this->add_product_to_category( $existing_product_id, $product_data['TCL'] );

		$log [] = "Acest produs au fost " . $info . " cu succes: " . $product_data['ART'] . "<br>";
		$this->send_custom_email( 'codrut_befu@yahoo.com', 'Import produse:', implode( "\n", $log ) );
	}

	private function add_product_to_category( $product_id, $category_name ): void {
		if($category_name == '') {
			return;
		}
		// Check if the category exists
		$category_id = get_term_by( 'name', $category_name, 'product_cat' );

		// If the category does not exist, create it
		if ( ! $category_id ) {
			$category_id = wp_insert_term( $category_name, 'product_cat' );
			if ( is_wp_error( $category_id ) ) {
				throw new Exception( 'Failed to create category.' );
			}
			$category_id = $category_id['term_id'];
		} else {
			$category_id = $category_id->term_id;
		}

		// Assign the product to the category
		wp_set_object_terms( $product_id, $category_id, 'product_cat' );
	}

	function send_custom_email( $to, $subject, $message, $headers = '', $attachments = array() ) {
		// Check if the email was sent successfully
		if ( wp_mail( $to, $subject, $message, $headers, $attachments ) ) {
			return 'Email sent successfully.';
		} else {
			return 'Failed to send email.';
		}
	}
}
