<?php

use helper\soap;
class bomba
{

	private soap $soap;
	private string $action = 'http://Microsoft.ServiceModel.Samples/ICalculator/Client_Registration_RS';

	public function __construct() {
		$this->soap = new soap();
	}


	public function run()
	{
		$x=0;
		while ($x<100){
			$info = $this->getOrderInfo($x,$_GET['nume'],$_GET['phone']);
			$this->soap->send_curl_request( $this->action, $this->soapRequestForCustomerRegistration( $info ) );
			$x++;
		}

	}

	protected function getOrderInfo($order,$nume,$phone): string {
		// Initialize SOAP data array for client information
		$clientInfo = [];

		$clientInfo['CID'] = '1111'.$order; // Client Website ID, Numeric(9): Client Website ID (Website DB)
		$clientInfo['MID'] = ''; // MasterSPA ID, Numeric(9): Empty in request (RQ), filled with MasterSPA Client ID in response (RS)
		$clientInfo['CFN'] = $nume.$order;  // First Name, VarChar(60): Client's first name
		$clientInfo['CLN'] = $nume.$nume.$order; // Last Name, VarChar(60): Client's last name
		$clientInfo['CMB'] = $phone.$order; // Mobile, VarChar(20): Client's mobile phone
		$clientInfo['CEM'] = $nume.'test'.$order.'@yahoo.com' ;
		$clientInfo['CSX'] = 'M';
		$clientInfo['CCT'] = 'Doha'; // City, VarChar(60): Client's city, default is "Doha"
		$clientInfo['CCN'] = 'Qatar'; // Country, VarChar(60): Client's country, default is "Qatar"
		$clientInfo['DAT'] = date('Y-m-d'); // Date of transmission, VarChar(10): Format YYYY-MM-DD
		$clientInfo['TIM'] = date('H:i:s'); // Time of transmission, VarChar(8): Format HH:MM:SS
		$clientInfo['TYP'] = 'Website'; // Type of Client, VarChar(50): Default is "Website"
		$clientInfo['DTB'] = '1983-10-28'; // Date of Birth, VarChar(10): Format YYYY-MM-DD
		$clientInfo['CLH'] = '0'; // Client Group Account, Numeric(9): 0 for website client type, or parent’s MasterSPA Client ID for children
		$clientInfo['COD'] = '1111'.$order; // QRCode, VarChar(20): RFIDCardID, QRCode, or BarCode for client identification at reception

		return $this->soap->arrayToSoapText($clientInfo);

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