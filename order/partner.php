<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class partner {
	private soap $soap;
	private string $action = 'http://Microsoft.ServiceModel.Samples/ICalculator/Partner_Registration_RS';

	public function __construct() {
		$this->soap = new soap();
	}

	protected function getOrderInfo(\Automattic\WooCommerce\Admin\Overrides\Order $order ): string {
		// Initialize SOAP data array for partner information
		$partnerInfo = [];

		$partnerInfo['PWB'] = $order->get_customer_id(); // Partner Website ID, Numeric(9): Partner Website ID from the Website DB
		$partnerInfo['PTN'] = ''; // MasterSPA Partner ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Partner ID in response (RS)
		$partnerInfo['PNM'] = $order->get_billing_first_name() .' '.$order->get_billing_last_name() ; // Partner Name, VarChar(60): Name of the partner
		$partnerInfo['PCI'] = ''; // VAT Identifier, VarChar(30): Partner Fiscal Identifier (CUI)
		$partnerInfo['PRC'] = ''; // Registry of Commerce Identifier, VarChar(30): Registry of Commerce Identifier (NrRegCom)
		$partnerInfo['PBK'] = ''; // Bank Name, VarChar(60): Name of the partner's bank
		$partnerInfo['PBA'] = ''; // Bank Account, VarChar(60): Partner's bank account
		$partnerInfo['PAD'] = ''; // Partner Address, VarChar(60): Partner's address (Street, no.)
		$partnerInfo['PCT'] = ''; // Partner City, VarChar(60): Partner's city
		$partnerInfo['PCN'] = ''; // Partner Country, VarChar(60): Partner's country
		$partnerInfo['MID'] = ''; // MasterSPA ID Client, Numeric(9): MasterSPA Client ID linked to this partner
		$partnerInfo['DAT'] = date( 'Y-m-d' ); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$partnerInfo['TIM'] = date( 'H:i:s' ); // Time of transmission, VarChar(8): Format HH:MM:SS




		return $this->soap->arrayToSoapText( $partnerInfo );

	}

	public function sendPartnerToSoap( $order ): void {
		$info = $this->getOrderInfo( $order );
		print_r($this->soapRequestForPartnerRegistration( $info ));
		print_r('

' );
		die();
		$this->soap->send_curl_request( $this->action, $this->soapRequestForPartnerRegistration( $info ) );
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