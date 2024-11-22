<?php

use helper\soap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class partner {
	private soap $soap;
	private string $action = 'http://Microsoft.ServiceModel.Samples/ICalculator/Partner_Registration_RS';
	/**
	 * @var array|string[]
	 */
	private array $custom_checkout_fields;

	public function __construct() {
		$this->soap = new soap();

		$this->custom_checkout_fields = [
			'billing_cui' ,
			'reg_com' ,
			'bank',
			'iban'
		];
	}

	protected function getOrderInfo(\Automattic\WooCommerce\Admin\Overrides\Order $order,$customerId ): string {
		foreach ($this->custom_checkout_fields as $key ) {
			$clientInfo[$key] = get_post_meta($order->get_id(), $key, true);
		}
		// Initialize SOAP data array for partner information
		$partnerInfo = [];

		$partnerInfo['PWB'] = $order->get_customer_id(); // Partner Website ID, Numeric(9): Partner Website ID from the Website DB
		$partnerInfo['PTN'] = ''; // MasterSPA Partner ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Partner ID in response (RS)
		$partnerInfo['PNM'] = $order->get_billing_first_name() .' '.$order->get_billing_last_name() ; // Partner Name, VarChar(60): Name of the partner
		$partnerInfo['PCI'] = $clientInfo['cui']; // VAT Identifier, VarChar(30): Partner Fiscal Identifier (CUI)
		$partnerInfo['PRC'] = $clientInfo['reg_com'];// Registry of Commerce Identifier, VarChar(30): Registry of Commerce Identifier (NrRegCom)
		$partnerInfo['PBK'] =  $clientInfo['bank']; // Bank Name, VarChar(60): Name of the partner's bank
		$partnerInfo['PBA'] = $clientInfo['iban'];; // Bank Account, VarChar(60): Partner's bank account
		$partnerInfo['PAD'] = $order->get_billing_address_1(); // Partner Address, VarChar(60): Partner's address (Street, no.)
		$partnerInfo['PCT'] =  $order->get_billing_city();; // Partner City, VarChar(60): Partner's city
		$partnerInfo['PCN'] = $order->get_billing_country();; // Partner Country, VarChar(60): Partner's country
		$partnerInfo['MID'] = $customerId; // MasterSPA ID Client, Numeric(9): MasterSPA Client ID linked to this partner
		$partnerInfo['DAT'] = date( 'Y-m-d' ); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$partnerInfo['TIM'] = date( 'H:i:s' ); // Time of transmission, VarChar(8): Format HH:MM:SS


		return $this->soap->arrayToSoapText( $partnerInfo );

	}

	public function sendPartnerToSoap( $order,$customerId ): string {
		$info = $this->getOrderInfo( $order,$customerId );

		$result = $this->soap->send_curl_request( $this->action, $this->soapRequestForPartnerRegistration( $info ) );

		return $this->updateTheCompany( $order, $result );
	}


	protected function updateTheCompany($order,$result) :string {

		if($result['ERR']=='KO') {
			$order->update_status('spa-error-status','A aparut o eroare la trimiterea datelor catre MasterSPA');
			update_post_meta($order->get_id(), '_custom_field_partner_info_success', false);
			update_post_meta($order->get_id(), '_custom_field_partner_info', json_encode($result));
			return false;
		}
		update_post_meta($order->get_id(), '_custom_field_user_info', json_encode($result));
		update_post_meta($order->get_id(), '_custom_field_user_info_success', true);

		return $result['MID'];
	}


	private function soapRequestForPartnerRegistration( $info ): string {
		$url = $this->soap->getImportEndPoint();
		$inoText= $this->soap->startOfLine().$info.'|'.$this->soap->endOfLine();


	return <<<XML
    <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                   xmlns:wsa="http://www.w3.org/2005/08/addressing"
                   xmlns:mic="http://Microsoft.ServiceModel.Samples">
        <soap:Header>
            <wsa:To>$url</wsa:To>
            <wsa:Action>$this->action</wsa:Action>
        </soap:Header>
        <soap:Body>
            <mic:Partner_Registration_RS>
                <mic:partner_rq>$inoText</mic:partner_rq>
            </mic:Partner_Registration_RS>
        </soap:Body>
    </soap:Envelope>
    XML;
	}
}