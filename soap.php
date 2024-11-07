<?php

class soap {

	//private $import_endpoint_url = 'http://95.77.98.62:8000/GettingStarted/CalculatorService'; // Endpoint pentru import
	private $import_endpoint_url = 'http://google.com'; // Endpoint pentru import

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
		curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output
		curl_setopt($ch, CURLINFO_HEADER_OUT, true); // Track the request headers

		// Execute the request
		$response  = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$body = substr($response, $header_size);


		echo "<pre>";
		print_r("HTTP Code: " . $http_code);
		print_r("Header Size: " . $header_size);
		print_r("Headers: " . $header);
		print_r("Body: " . $body);
		if ($response === false) {
			print_r("cURL Error: " . curl_error($ch));
		}
		echo "</pre>";
		die();


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