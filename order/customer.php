<?php

use helper\soap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class customer {

	private soap $soap;
	private string $action = 'http://Microsoft.ServiceModel.Samples/ICalculator/Client_Registration_RS';

	public function __construct() {
		$this->soap = new soap();
	}


	protected function getOrderInfo($order): string {
		// Initialize SOAP data array for client information
		$clientInfo = [];

		$clientInfo['CID'] = $order->get_customer_id(); // Client Website ID, Numeric(9): Client Website ID (Website DB)
		$clientInfo['MID'] = ''; // MasterSPA ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Client ID in response (RS)
		$clientInfo['CFN'] = $order->get_billing_first_name(); // First Name, VarChar(60): Client's first name
		$clientInfo['CLN'] = $order->get_billing_first_name(); // Last Name, VarChar(60): Client's last name
		$clientInfo['CMB'] = $order->get_billing_phone() ; // Mobile, VarChar(20): Client's mobile phone
		$clientInfo['CEM'] = $order->get_billing_email(); // Email, VarChar(50): Client's email
		$clientInfo['CLM'] = '0'; // Member, Char(1): "0"=not member, "1"=is member
		$clientInfo['CSX'] = 'M'; // Sex, Char(1): "M"=Male, "F"=Female
		$clientInfo['CCT'] = $order->get_billing_city(); // City, VarChar(60): Client's city, default is "Doha"
		$clientInfo['CCN'] = $order->get_billing_country(); // Country, VarChar(60): Client's country, default is "Qatar"
		$clientInfo['DAT'] = date('Y-m-d'); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$clientInfo['TIM'] = date('H:i:s'); // Time of transmission, VarChar(8): Format HH:MM:SS
		$clientInfo['TYP'] = 'Website'; // Type of Client, VarChar(50): Default is "Website"
		$clientInfo['DTB'] = '1983-10-28'; // Date of Birth, VarChar(10): Format YYYY-MM-DD
		$clientInfo['CLH'] = '0'; // Client Group Account, Numeric(9): 0 for website client type, or parent’s MasterSPA Client ID for children
		$clientInfo['COD'] =  $order->get_customer_id(); // QRCode, VarChar(20): RFIDCardID, QRCode, or BarCode for client identification at reception

		return $this->soap->arrayToSoapText($clientInfo);

	}


	public function sendCustomerToSoap($order):string  {
		$info = $this->getOrderInfo($order);
		$result = $this->soap->send_curl_request( $this->action, $this->soapRequestForCustomerRegistration( $info ) );

		return $this->updateTheCustomer($order,$result);
	}

	protected function updateTheCustomer($order,$result) :string {

		if($result['ERR']=='KO') {
			$order->update_status('spa-error-status','A aparut o eroare la trimiterea datelor catre MasterSPA');
			update_post_meta($order->get_id(), '_custom_field_user_info_success', false);
			update_post_meta($order->get_id(), '_custom_field_user_info', json_encode($result));
			return false;
		}
		update_post_meta($order->get_id(), '_custom_field_user_info', json_encode($result));
		update_post_meta($order->get_id(), '_custom_field_user_info_success', true);

		return $result['MID'];
	}

	private function soapRequestForCustomerRegistration( $info ): string {
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
            <mic:Client_Registration_RS>
                <mic:client_rq>$inoText</mic:client_rq>
            </mic:Client_Registration_RS>
        </soap:Body>
    </soap:Envelope>
    XML;
	}
}

