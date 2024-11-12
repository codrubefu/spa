<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class prices {
	private string $import_endpoint_url = 'http://Microsoft.ServiceModel.Samples/ICalculator/Load_Price_RS';
	private soap $soap;

	public function __construct() {
		$this->soap = new soap();
	}

	public function loadPrices( $name ) {

		$soap_request = $this->soap_request_for_prices( $name );

		$prices = $this->soap->send_curl_request( $this->import_endpoint_url, $soap_request );
		return $this->parse_prices( $prices );
	}

	private function parse_prices( $prices ): array {
		if ( $prices === null ) {
			return [];
		}
		preg_match( '/\&\#x2;(.*)\&\#x3;/s', $prices, $matches );
		$services       = $matches[1];
		$services2array = explode( '|', $services );
		foreach ( $services2array as $row ) {
			$key          = substr( $row, 0, 3 );
			$info[ $key ] = explode( '#', str_replace( $key, '', $row ) );
		}

		$priceInfo = [];

		foreach ( $info as $key => $price ) {
			if ( count( $price ) > 1 ) {
				foreach ( $price as $id => $price ) {
					if ( $price != '' ) {
						$priceInfo[ $id ][ $key ] = $price;

					}
				}
			}
		}

		return $priceInfo;
	}

	private function soap_request_for_prices( $name ) {
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
            <mic:Load_Price_RS>
                <mic:price_rq>[SOH][STX]|PRE1|DAT$data|TIM$time|ART$name|PRC|INF|[ETX]121A[EOT]</mic:price_rq>
            </mic:Load_Price_RS>
        </soap:Body>
    </soap:Envelope>
    XML;
	}
}
