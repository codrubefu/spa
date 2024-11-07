<?php

class soap {

	private $import_endpoint_url = 'http://95.77.98.62:8000/GettingStarted/CalculatorService'; // Endpoint pentru import

	public function getImportEndPoint(): string {
		return $this->import_endpoint_url;
	}
	public function send_curl_request( $action, $soap_request ) {
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

		// Execute the request
		$response  = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
print_r($response);
		// Check for errors
		if ( $response === false || $http_code !== 200 ) {
			error_log( 'Eroare cURL: ' . curl_error( $ch ) . ' (Cod HTTP: ' . $http_code . ')' );
		} else {
			return $response;
		}

		// Close the cURL session
		curl_close( $ch );

		return null;
	}
}