<?php

use helper\soap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class order {

	private string $action = 'http://Microsoft.ServiceModel.Samples/ICalculator/Payment_Sales_Confirmation_RS';

	private soap $soap;

	public function __construct() {
		$this->soap = new soap();
	}

	protected function getOrderInfo( $order ,$customerId,$partnerId,$beneficiariesIds,$clintIds ): string {
		$itemsInfo = [];
		$x=0;
		foreach ( $order->get_items() as $key => $item ) {
			while ($q < $item->get_quantity()) {
				$nameInfo = explode( '<span> - </span>', $item->get_name() );

				if ( count( $nameInfo ) == 2 ) {
					$itemInfo['name'] = $nameInfo[0];
				} else {
					$itemInfo['name'] = $item->get_name();
				}
				$itemInfo['types']    = 'Card Membru';
				$items['names'][ $x ] = $itemInfo['name'];
				$items['type'][ $x ]  = $itemInfo['types'];

				$items['quantity'][ $x ]    = 1;
				$items['unit_prices'][ $x ] = number_format($item->get_total() / $item->get_quantity(), 2);
				$items['prices'][ $x ] = number_format($item->get_total() / $item->get_quantity(), 2);
				$items['bcd'][ $x ]      = '';
				$items['ren'][ $x ]      = '0';
				$items['crd'][ $x ] = 'CM' . str_pad($order->get_id() . $x, 10, '0', STR_PAD_LEFT);
				$x ++;
				$q ++;
			}
		}
		$soapCartInfo = [];

		$soapCartInfo['SID'] = $order->get_id(); // Payment Sales ID, Numeric(9): ID of the Payment Order Confirmation from Website DB
		$soapCartInfo['DAT'] = date( 'Y-m-d' ); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$soapCartInfo['TIM'] = date( 'H:i:s' ); // Time of transmission, VarChar(8): Format HH:MM:SS
		$soapCartInfo['PVL'] = (int) array_sum( $items['prices'] ); // Payment value, Numeric(11,2): Total value of the credit card payment
		$soapCartInfo['ART'] = implode( '#', $items['names'] ); // List of Articles, VarChar: List of articles separated by '#'
		$soapCartInfo['QTY'] = implode( '#', $items['quantity'] ); // List of Quantities, VarChar: Quantities for each article separated by '#'
		$soapCartInfo['PRC'] = implode( '#', $items['unit_prices'] ); // List of Prices, VarChar: Unit prices for each article separated by '#'
		$soapCartInfo['VAL'] = implode( '#', $items['prices'] ); // List of Values, VarChar: Total prices (unit price * quantity) for each article separated by '#'
		$soapCartInfo['NTP'] = '12345'; // Transaction Number, VarChar(20): Transaction number of the credit card payment
		$soapCartInfo['TCC'] = 'VISA'; // Type of Credit Card, VarChar(20): For example, Visa, MasterCard, etc.
		$soapCartInfo['CID'] = $order->get_customer_id();; // Client Website ID (Buyer), Numeric(9): Website DB ID for the buyer
		$soapCartInfo['MID'] = $customerId; // Buyer MasterSPA ID, Numeric(9): MasterSPA Client ID for Buyer
		$soapCartInfo['ACC'] = '1234*********3456'; // Account Number, VarChar(30): Account number of the credit card transaction
		$soapCartInfo['PRD'] = ''; // Promotion Code, VarChar(20): Promotion code for discount, empty if none
		$soapCartInfo['BCI'] = implode('#',$clintIds);; // Client Website ID (Beneficiary), Numeric(9): Website DB ID for the beneficiary, equal to CID if none
		$soapCartInfo['BMI'] = implode('#',$beneficiariesIds); // Beneficiary MasterSPA ID, Numeric(9): MasterSPA Client ID for Beneficiary, equal to MID if none
		$soapCartInfo['CRD'] = implode( '#', $items['crd'] ); // CM12 cifre   Voucher/Card Code (Buyer), VarChar(20): Barcode/code from Voucher/Gift Card/Member Card for Buyer
		$soapCartInfo['BCD'] = implode( '#', $items['bcd'] ); // Voucher/Card Code (Family 2), VarChar(20): Voucher/Gift Card for Beneficiary, empty if none
		$soapCartInfo['CNP'] = '0000000000000'; // Buyer Personal ID, VarChar(13): Buyer's personal ID number
		$soapCartInfo['REN'] = implode( '#', $items['ren'] );; // Renewal Flag, Char(1): "0"=not renewal, "1"=is renewal
		$soapCartInfo['TYP'] = implode( '#', $items['type'] );; // List of Type of Sale, VarChar(30): Types of sales separated by '#'
		$soapCartInfo['PTN'] = $partnerId; // Partner ID, Numeric(9): Company ID for issuing the fiscal invoice
		return $this->soap->arrayToSoapText( $soapCartInfo );
	}

	public function sendOrderToSoap( $order, $customerId,$partnerId,$beneficiariesIds,$clintIds ): void {
		$info = $this->getOrderInfo( $order, $customerId ,$partnerId,$beneficiariesIds,$clintIds);

		$result = $this->soap->send_curl_request( $this->action, $this->soapRequestForOrderRegistration( $info ) );
		$this->updateTheOrder($order,$result);
	}

	protected function updateTheOrder($order,$result) :void {
		if($result['ERR']=='KO') {
			update_post_meta($order->get_id(), '_custom_field_user_order_success', false);
		}
		update_post_meta($order->get_id(), '_custom_field_order_info', json_encode($result));
		update_post_meta($order->get_id(), '_custom_field_user_order_success', true);
	}


	private function soapRequestForOrderRegistration( $info ): string {
		$url     = $this->soap->getImportEndPoint();
		$inoText = $this->soap->startOfLine() . $info . '|' . $this->soap->endOfLine();

		return <<<XML
    <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                   xmlns:wsa="http://www.w3.org/2005/08/addressing"
                   xmlns:mic="http://Microsoft.ServiceModel.Samples">
        <soap:Header>
            <wsa:To>$url</wsa:To>
            <wsa:Action>$this->action</wsa:Action>
        </soap:Header>
        <soap:Body>
            <mic:Payment_Sales_Confirmation_RS>
                <mic:payment_rq>$inoText</mic:payment_rq>
            </mic:Payment_Sales_Confirmation_RS>
        </soap:Body>
    </soap:Envelope>
    XML;
	}
}