<?php

class client {

	private function soap_request_for_client_registration( $data ): array|string {
		$url = $this->import_endpoint_url;
		// Define the SOAP XML template with placeholders
		$this - soap_xml_template = <<<XML
    <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope"
                   xmlns:wsa="http://www.w3.org/2005/08/addressing"
                   xmlns:mic="http://Microsoft.ServiceModel.Samples">
        <soap:Header>
            <wsa:To>$url</wsa:To>
            <wsa:Action>http://Microsoft.ServiceModel.Samples/ICalculator/Client_Registration_RS</wsa:Action>
        </soap:Header>
        <soap:Body>
            <mic:Client_Registration_RS>
                <mic:client_rq>[SOH][STX]|OID{order_id}|OTPC{otp_code}|DAT{date}|TIM{time}|CLM{client_name}|CLN{client_last_name}|CLPC{client_first_name}|CLT{client_phone}|CLE{client_email}|CNP{client_cnp}|BSXM|DTC{date_code}|OVL{order_value}|MFR{mother_first_name}|MTO{mother_last_name}|MBD{message_body}|MEN{message_end}|PLV{price_value}|JLV{jurisdiction_location}|MPL{metropolitan_location}|PCH{purchase_channel}|TPPL|PPL{purchase_percentage}|RED{reduction_code}|CST{custom_value}|ALA{address_line_1}|ALB{address_line_2}|ALJ{jurisdiction}|ALO{location}|ALT{territory}|ALC{country_code}|FPJ{fiscal_position}|LBF{logistic_base}|LDT{logistic_date}|PJD{juridical_position}|PJC{juridical_code}|PJR{juridical_register}|PJA{juridical_address}|PJB{juridical_bank}|PJI{juridical_iban}|ACC{account_code}|INF{information_code}[ETX]checksum[EOT]</mic:client_rq>
            </mic:Client_Registration_RS>
        </soap:Body>
    </soap:Envelope>
    XML;

		// Replace placeholders with actual data values
		$this - soap_request = str_replace( [
			'{order_id}',
			'{otp_code}',
			'{date}',
			'{time}',
			'{client_name}',
			'{client_last_name}',
			'{client_first_name}',
			'{client_phone}',
			'{client_email}',
			'{client_cnp}',
			'{date_code}',
			'{order_value}',
			'{mother_first_name}',
			'{mother_last_name}',
			'{message_body}',
			'{message_end}',
			'{price_value}',
			'{jurisdiction_location}',
			'{metropolitan_location}',
			'{purchase_channel}',
			'{purchase_percentage}',
			'{reduction_code}',
			'{custom_value}',
			'{address_line_1}',
			'{address_line_2}',
			'{jurisdiction}',
			'{location}',
			'{territory}',
			'{country_code}',
			'{fiscal_position}',
			'{logistic_base}',
			'{logistic_date}',
			'{juridical_position}',
			'{juridical_code}',
			'{juridical_register}',
			'{juridical_address}',
			'{juridical_bank}',
			'{juridical_iban}',
			'{account_code}',
			'{information_code}'
		], [
			$data['order_id'],
			$data['otp_code'],
			$data['date'],
			$data['time'],
			$data['client_name'],
			$data['client_last_name'],
			$data['client_first_name'],
			$data['client_phone'],
			$data['client_email'],
			$data['client_cnp'],
			$data['date_code'],
			$data['order_value'],
			$data['mother_first_name'],
			$data['mother_last_name'],
			$data['message_body'],
			$data['message_end'],
			$data['price_value'],
			$data['jurisdiction_location'],
			$data['metropolitan_location'],
			$data['purchase_channel'],
			$data['purchase_percentage'],
			$data['reduction_code'],
			$data['custom_value'],
			$data['address_line_1'],
			$data['address_line_2'],
			$data['jurisdiction'],
			$data['location'],
			$data['territory'],
			$data['country_code'],
			$data['fiscal_position'],
			$data['logistic_base'],
			$data['logistic_date'],
			$data['juridical_position'],
			$data['juridical_code'],
			$data['juridical_register'],
			$data['juridical_address'],
			$data['juridical_bank'],
			$data['juridical_iban'],
			$data['account_code'],
			$data['information_code']
		], $this - soap_xml_template );

		return $this - soap_request;
	}

}