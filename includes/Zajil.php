<?php
/**
 * Zajel Merchant API Integration Class
 * Document v1.8
 */
class Zajil {
    private $apiKey;
    private $customerCode;
    private $baseUrl;

    public function __construct($apiKey, $customerCode, $isTestMode = false) {
        $this->apiKey = $apiKey;
        $this->customerCode = $customerCode;
        
        if ($isTestMode) {
            $this->baseUrl = 'https://api-stg.zajel.com/services/integration/api/Merchant/';
        } else {
            $this->baseUrl = 'https://api.zajel.com:8443/services/integration/api/Merchant/';
        }
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . ltrim($endpoint, '/');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $headers = [
            'accept: */*',
            'X-AUTH-API-KEY: ' . $this->apiKey
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                $headers[] = 'Content-Type: application/json';
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Uncomment if SSL issues occur locally
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }

        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return $decodedResponse;
        } else {
            return [
                'success' => false,
                'message' => 'API Error: ' . ($decodedResponse['message'] ?? $response),
                'code' => $httpCode,
                'response' => $decodedResponse
            ];
        }
    }

    /**
     * Create a domestic shipment (UAE)
     */
    public function createDomesticShipment($data) {
        $payload = [
            "customer_reference_number" => $data['order_number'] ?? '',
            "weight_in_kg" => (float)($data['weight'] ?? 1.0),
            "customer_code" => $this->customerCode,
            "service_type_id" => "DDN", // Usually standard domestic
            "product_type" => "NON-DOCUMENT",
            "description" => $data['description'] ?? "E-commerce Order",
            "length_in_cm" => (float)($data['length'] ?? 10),
            "width_in_cm" => (float)($data['width'] ?? 10),
            "height_in_cm" => (float)($data['height'] ?? 10),
            "num_of_pieces" => (int)($data['pieces'] ?? 1),
            "cod_amount" => (float)($data['cod_amount'] ?? 0),
            "origin" => [
                "name" => $data['origin']['name'],
                "phone" => $data['origin']['phone'],
                "company_name" => $data['origin']['company_name'] ?? '',
                "address_line_1" => $data['origin']['address_line_1'],
                "address_line_2" => $data['origin']['address_line_2'] ?? '',
                "area" => $data['origin']['area'] ?? '', // Must be picked from GetAreas
                "city" => $data['origin']['city'],       // Must be picked from GetCities
                "country" => "UAE",
                "email" => $data['origin']['email'] ?? '',
                "latitude" => "",
                "longitude" => ""
            ],
            "destination" => [
                "name" => $data['destination']['name'],
                "phone" => $data['destination']['phone'],
                "companyName" => $data['destination']['company_name'] ?? '',
                "address_line_1" => $data['destination']['address_line_1'],
                "address_line_2" => $data['destination']['address_line_2'] ?? '',
                "area" => $data['destination']['area'] ?? '',
                "city" => $data['destination']['city'],
                "country" => "UAE",
                "email" => $data['destination']['email'] ?? '',
                "latitude" => "",
                "longitude" => ""
            ]
        ];

        return $this->makeRequest('CreateShipment', 'POST', $payload);
    }

    /**
     * Track a shipment by its AWB/Reference Number
     */
    public function trackShipment($referenceNumber) {
        return $this->makeRequest('TrackShipment?reference_number=' . urlencode($referenceNumber), 'GET');
    }

    /**
     * Get the label (PDF URL or base64 usually) for a shipment
     */
    public function getShipmentLabel($referenceNumber) {
        return $this->makeRequest('GetShipmentLabel?reference_number=' . urlencode($referenceNumber), 'GET');
    }

    /**
     * Cancel a shipment
     */
    public function cancelShipment($referenceNumber) {
        $payload = [
            "reference_number" => $referenceNumber
        ];
        return $this->makeRequest('CancelShipment', 'POST', $payload);
    }
    
    /**
     * Get Cities list
     */
    public function getCities($countryCode = 'UAE') {
        return $this->makeRequest('GetCities?country_code=' . urlencode($countryCode), 'GET');
    }
    
    /**
     * Get Areas by City Code
     */
    public function getAreas($cityCode) {
        return $this->makeRequest('GetAreas?city_code=' . urlencode($cityCode), 'GET');
    }
}
