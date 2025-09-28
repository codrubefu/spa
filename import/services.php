<?php

namespace import;
use helper\Soap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class services {
	private Soap $soap;
	private string $import_endpoint_url = 'http://Microsoft.ServiceModel.Samples/ICalculator/Load_Services_RS';

	public function __construct() {
		$this->soap = new soap();
	}

	public function loadServices(): array {
		$soap_request = $this->soap_request_for_services();
		$services     = $this->soap->send_curl_request( $this->import_endpoint_url, $soap_request,false );

		return $this->parse_services( $services );
	}

	private function parse_services( $services ): array {
		if ( $services === null ) {
			return [];
		}
		$services = str_replace( '&#xD;', '&space&', $services );
		preg_match( '/\&\#x2;(.*)\&\#x3;/s', $services, $matches );
		$services       = $matches[1];
		$services2array = explode( '|', $services );
		foreach ( $services2array as $row ) {
			$key          = substr( $row, 0, 3 );
			$info[ $key ] = explode( '#', str_replace( $key, '', $row ) );

		}
		$productsInfo = [];
		foreach ( $info as $key => $products ) {
			if ( count( $products ) > 1 ) {
				foreach ( $products as $id => $product ) {
					$productsInfo[ $id ][ $key ] = html_entity_decode( str_replace( '&space&', '&#xD;', $product ) );
				}
			}
		}

		return $productsInfo;
	}

	private function soap_request_for_services() {
		$url  = $this->soap->getImportEndPoint();
		$data = date( 'Y-m-d' );
		$time = date( 'H:i:s' );

		return <<<XML
    <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                   xmlns:wsa="http://www.w3.org/2005/08/addressing"
                   xmlns:mic="http://Microsoft.ServiceModel.Samples">
        <soap:Header>
            <wsa:To>$url</wsa:To>
            <wsa:Action>$this->import_endpoint_url</wsa:Action>
        </soap:Header>
        <soap:Body>
            <mic:Load_Services_RS>
                <mic:services_rq>[SOH][STX]|TID100|DAT{$data}|TIM{$time}|NOA1|ART1|PRC|TCL|TGR|TTM|TBS|TSX| AID|TS2|TI1|TI2|TI3|TI4|TL1|TL2|TL3|TL4|TDS|TD2|[ETX][EOH] </mic:services_rq>
            </mic:Load_Services_RS>
        </soap:Body>
    </soap:Envelope>
    XML;
	}
}