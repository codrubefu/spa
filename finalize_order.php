<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class finalize_order {
	public function __construct() {
		// Hook into the order status completed action
		add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_completed' ), 10, 1 );
	}

	// Define the function that will run when an order is completed
	public function on_order_completed( $order_id ) {
		// Ensure the order exists and is paid
		$order = wc_get_order( $order_id );

		$itemsInfo = [];
		foreach ( $order->get_items() as $key => $item ) {
			$nameInfo = explode( '<span> - </span>', $item->get_name() );

			if ( count($nameInfo) == 2 ) {
				$itemInfo['name']    = $nameInfo[0];
				$itemInfo['types'] = $nameInfo[1];
			} else {
				$itemInfo['name']    = $item->get_name();
				$itemInfo['types'] = '';
			}


			$items['names'][ $key ]       = $itemInfo['name'];
			$items['type'][ $key ]       = $itemInfo['types'];

			$items['quantity'][ $key ]    = $item->get_quantity();
			$items['unit_prices'][ $key ] = $item->get_total() / $item->get_quantity();
			$items['prices'][ $key ]      = $item->get_total();
		}



// Initialize SOAP data array
		$soapCartInfo = [];

		$soapCartInfo['SID'] = $order->get_id(); // Payment Sales ID, Numeric(9): ID of the Payment Order Confirmation from Website DB
		$soapCartInfo['DAT'] = date('Y-m-d'); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$soapCartInfo['TIM'] = date('H:i:s'); // Time of transmission, VarChar(8): Format HH:MM:SS
		$soapCartInfo['PVL'] = array_sum($items['prices']); // Payment value, Numeric(11,2): Total value of the credit card payment
		$soapCartInfo['ART'] = implode('#', $items['names']); // List of Articles, VarChar: List of articles separated by '#'
		$soapCartInfo['QTY'] = implode('#', $items['quantity']); // List of Quantities, VarChar: Quantities for each article separated by '#'
		$soapCartInfo['PRC'] = implode('#', $items['unit_prices']); // List of Prices, VarChar: Unit prices for each article separated by '#'
		$soapCartInfo['VAL'] = implode('#', $items['prices']); // List of Values, VarChar: Total prices (unit price * quantity) for each article separated by '#'
		$soapCartInfo['NTP'] = 'CARD'; // Transaction Number, VarChar(20): Transaction number of the credit card payment
		$soapCartInfo['TCC'] = 'CARD'; // Type of Credit Card, VarChar(20): For example, Visa, MasterCard, etc.
		$soapCartInfo['CID'] = ''; // Client Website ID (Buyer), Numeric(9): Website DB ID for the buyer
		$soapCartInfo['MID'] = ''; // Buyer MasterSPA ID, Numeric(9): MasterSPA Client ID for Buyer
		$soapCartInfo['ACC'] = ''; // Account Number, VarChar(30): Account number of the credit card transaction
		$soapCartInfo['PRD'] = ''; // Promotion Code, VarChar(20): Promotion code for discount, empty if none
		$soapCartInfo['BCI'] = ''; // Client Website ID (Beneficiary), Numeric(9): Website DB ID for the beneficiary, equal to CID if none
		$soapCartInfo['BMI'] = ''; // Beneficiary MasterSPA ID, Numeric(9): MasterSPA Client ID for Beneficiary, equal to MID if none
		$soapCartInfo['CRD'] = ''; // Voucher/Card Code (Buyer), VarChar(20): Barcode/code from Voucher/Gift Card/Member Card for Buyer
		$soapCartInfo['BCD'] = ''; // Voucher/Card Code (Family 2), VarChar(20): Voucher/Gift Card for Beneficiary, empty if none
		$soapCartInfo['CNP'] = ''; // Buyer Personal ID, VarChar(13): Buyer's personal ID number
		$soapCartInfo['REN'] = ''; // Renewal Flag, Char(1): "0"=not renewal, "1"=is renewal
		$soapCartInfo['TYP'] = implode('#', $items['type']);; // List of Type of Sale, VarChar(30): Types of sales separated by '#'
		$soapCartInfo['PTN'] = ''; // Partner ID, Numeric(9): Company ID for issuing the fiscal invoice

		echo $this->array_to_soap_text($soapCartInfo);
		die();

		// Initialize SOAP data array for client information
		$clientInfo = [];

		$clientInfo['CID'] = ''; // Client Website ID, Numeric(9): Client Website ID (Website DB)
		$clientInfo['MID'] = ''; // MasterSPA ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Client ID in response (RS)
		$clientInfo['CFN'] = $order->get_billing_first_name(); // First Name, VarChar(60): Client's first name
		$clientInfo['CLN'] = $order->get_billing_first_name(); // Last Name, VarChar(60): Client's last name
		$clientInfo['CMB'] = ''; // Mobile, VarChar(20): Client's mobile phone
		$clientInfo['CEM'] = ''; // Email, VarChar(50): Client's email
		$clientInfo['CLM'] = ''; // Member, Char(1): "0"=not member, "1"=is member
		$clientInfo['CSX'] = ''; // Sex, Char(1): "M"=Male, "F"=Female
		$clientInfo['CCT'] = 'Doha'; // City, VarChar(60): Client's city, default is "Doha"
		$clientInfo['CCN'] = 'Qatar'; // Country, VarChar(60): Client's country, default is "Qatar"
		$clientInfo['DAT'] = date('Y-m-d'); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$clientInfo['TIM'] = date('H:i:s'); // Time of transmission, VarChar(8): Format HH:MM:SS
		$clientInfo['TYP'] = 'Website'; // Type of Client, VarChar(50): Default is "Website"
		$clientInfo['DTB'] = ''; // Date of Birth, VarChar(10): Format YYYY-MM-DD
		$clientInfo['CLH'] = '0'; // Client Group Account, Numeric(9): 0 for website client type, or parent’s MasterSPA Client ID for children
		$clientInfo['COD'] = ''; // QRCode, VarChar(20): RFIDCardID, QRCode, or BarCode for client identification at reception


		// Initialize SOAP data array for partner information
		$partnerInfo = [];

		$partnerInfo['PWB'] = ''; // Partner Website ID, Numeric(9): Partner Website ID from the Website DB
		$partnerInfo['PTN'] = ''; // MasterSPA Partner ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Partner ID in response (RS)
		$partnerInfo['PNM'] = ''; // Partner Name, VarChar(60): Name of the partner
		$partnerInfo['PCI'] = ''; // VAT Identifier, VarChar(30): Partner Fiscal Identifier (CUI)
		$partnerInfo['PRC'] = ''; // Registry of Commerce Identifier, VarChar(30): Registry of Commerce Identifier (NrRegCom)
		$partnerInfo['PBK'] = ''; // Bank Name, VarChar(60): Name of the partner's bank
		$partnerInfo['PBA'] = ''; // Bank Account, VarChar(60): Partner's bank account
		$partnerInfo['PAD'] = ''; // Partner Address, VarChar(60): Partner's address (Street, no.)
		$partnerInfo['PCT'] = ''; // Partner City, VarChar(60): Partner's city
		$partnerInfo['PCN'] = ''; // Partner Country, VarChar(60): Partner's country
		$partnerInfo['MID'] = ''; // MasterSPA ID Client, Numeric(9): MasterSPA Client ID linked to this partner
		$partnerInfo['DAT'] = date('Y-m-d'); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$partnerInfo['TIM'] = date('H:i:s'); // Time of transmission, VarChar(8): Format HH:MM:SS


		if ( $order && $order->is_paid() ) {
			// Your custom code here
			error_log( 'Order ' . $order_id . ' has been completed and paid.' );

			// Example: Send a custom email, call an external API, etc.
			// $this->send_custom_notification( $order );
		}
	}

	// Optional: Example of a custom function, like sending a notification
	private function send_custom_notification( $order ) {
		dd( $order );
	}

	private function array_to_soap_text($array){
		$soap_text = '';
		foreach ($array as $key => $value){
			$soap_text .= "|$key$value";
		}

		return $soap_text;
	}
}

new finalize_order();
