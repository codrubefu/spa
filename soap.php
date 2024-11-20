<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

include_plugin_files();

class soap {

	private $import_endpoint_parameters = '/GettingStarted/CalculatorService'; // Endpoint pentru import
	private $import_endpoint_url = '/GettingStarted/CalculatorService'; // Endpoint pentru import

	private string $SOH; // Start of Header
	private string $STX; // Start of Text
	private string $ETX; // End of Text
	private string $EOT; // End of Transmission

	public function __construct() {
		$this->SOH = chr( 0x01 ); // Start of Header
		$this->STX = chr( 0x02 ); // Start of Text
		$this->ETX = chr( 0x03 ); // End of Text
		$this->EOT = chr( 0x04 ); // End of Transmission
		$this->import_endpoint_url = get_option('master_spa_server_address', '').$this->import_endpoint_parameters;
	}

	public function startOfLine(): string {
		return base64_encode( $this->SOH . $this->STX );
	}

	public function endOfLine(): string {
		return base64_encode( $this->ETX . $this->EOT );
	}


	public function getImportEndPoint(): string {
		return $this->import_endpoint_url;
	}

	public function arrayToSoapText( $array ): string {
		$soap_text = '';
		foreach ( $array as $key => $value ) {
			$soap_text .= "|$key$value";
		}

		return $soap_text;
	}

	public function send_curl_request( $action, $soap_request ): bool|string|null|array {
		$url = $this->import_endpoint_url;
		// Initialize cURL test
		$ch = curl_init( $url );

		// Set cURL options
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Accept-Encoding: gzip, deflate',
			'Content-Type: application/soap+xml;charset=UTF-8;action="' . $action . '"',
			'Content-Length: ' . strlen( $soap_request ),
			'Connection: Keep-Alive',
			'User-Agent: Apache-HttpClient/4.5.5 (Java/16.0.2)',
		] );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $soap_request );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 ); // 10 seconds timeout
		curl_setopt( $ch, CURLOPT_HEADER, true ); // Include headers in the output
		curl_setopt( $ch, CURLINFO_HEADER_OUT, true ); // Track the request headers

		// Execute the request
		$response    = curl_exec( $ch );
		$http_code   = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header      = substr( $response, 0, $header_size );
		$body        = substr( $response, $header_size );

		// Check for errors
		if ( $response === false || $http_code !== 200 ) {

			return [
				'ERR' => 'KO',
				'HTTP_CODE' => $http_code
			];

		} else {
			return $this->parseResponse( $response );
		}

		// Close the cURL session
		curl_close( $ch );

		return null;
	}

	protected function parseResponse( $str ) {

		$pattern = '/\|(.*)\|/';
		preg_match( $pattern, $str, $matches );
		if ( ! isset( $matches[1] ) ) {
			return '';
		}
		$info = explode( '|', $matches[1] );
		foreach ( $info as $item ) {
			$key            = substr( $item, 0, 3 );
			$value          = substr( $item, 3 );
			$result[ $key ] = $value;
		}
		return $result;
	}
}