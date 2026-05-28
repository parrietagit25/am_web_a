<?php
/**
 * Automarket API integration service
 */

class AutomarketApiService {
    private $apiUrl;
    private $user;
    private $pass;

    public function __construct() {
        $this->apiUrl = defined('AUTOMARKET_API_URL') ? AUTOMARKET_API_URL : '';
        $this->user = defined('AUTOMARKET_PARTNER_USER') ? AUTOMARKET_PARTNER_USER : '';
        $this->pass = defined('AUTOMARKET_PARTNER_PASS') ? AUTOMARKET_PARTNER_PASS : '';
    }

    /**
     * Check vehicle availability
     * 
     * @param array $params
     * @return array
     */
    public function getAvailability(array $params) {
        am_log("Requesting live availability: " . json_encode($params), "INFO");

        // Structured payload validator
        $payload = [
            "locationCode" => $params['locationCode'] ?? 'PTY',
            "returnLocationCode" => empty($params['returnLocationCode']) ? ($params['locationCode'] ?? 'PTY') : $params['returnLocationCode'],
            "pickupDate" => $params['pickupDate'] ?? '',
            "pickupTime" => $params['pickupTime'] ?? '',
            "returnDate" => $params['returnDate'] ?? '',
            "returnTime" => $params['returnTime'] ?? '',
            "age" => $params['age'] ?? '25',
            "promoCode" => $params['promoCode'] ?? ''
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        // Track response headers (e.g. X-Cache)
        $responseHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $headerLine) use (&$responseHeaders) {
            $len = strlen($headerLine);
            $parts = explode(':', $headerLine, 2);
            if (count($parts) === 2) {
                $name = strtolower(trim($parts[0]));
                $value = trim($parts[1]);
                $responseHeaders[$name] = $value;
            }
            return $len;
        });

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->user . ':' . $this->pass)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            am_log("API Error (HTTP $httpCode): " . ($error ? $error : $response), "ERROR");
            return [
                'success' => false,
                'source' => 'ERROR-FALLBACK',
                'xCache' => 'BYPASS',
                'vehicles' => $this->getMockVehicles()
            ];
        }

        $decoded = json_decode($response, true);
        if (!$decoded) {
            am_log("API Error decoding response: " . $response, "ERROR");
            return [
                'success' => false,
                'source' => 'DECODE-FALLBACK',
                'xCache' => 'BYPASS',
                'vehicles' => $this->getMockVehicles()
            ];
        }

        return [
            'success' => true,
            'source' => $decoded['source'] ?? ($responseHeaders['x-source'] ?? 'API'),
            'xCache' => $responseHeaders['x-cache'] ?? 'MISS',
            'vehicles' => $decoded['vehicles'] ?? []
        ];
    }

    /**
     * Provide list of mock vehicles matching layout card structures
     * 
     * @return array
     */
    public function getMockVehicles() {
        return [
            [
                'id' => 1,
                'name' => 'Toyota Hilux 4x4 Double Cab',
                'category' => 'Pick Up',
                'passengers' => 5,
                'ac' => true,
                'transmission' => 'Manual',
                'price' => 55.00,
                'img' => 'hilux.jpg'
            ],
            [
                'id' => 2,
                'name' => 'Hyundai Accent',
                'category' => 'Sedanes',
                'passengers' => 5,
                'ac' => true,
                'transmission' => 'Automática',
                'price' => 29.99,
                'img' => 'accent.jpg'
            ],
            [
                'id' => 3,
                'name' => 'Toyota RAV4 AWD',
                'category' => 'SUV',
                'passengers' => 5,
                'ac' => true,
                'transmission' => 'Automática',
                'price' => 45.50,
                'img' => 'rav4.jpg'
            ],
            [
                'id' => 4,
                'name' => 'Hyundai H1 Panel',
                'category' => 'Comerciales',
                'passengers' => 3,
                'ac' => true,
                'transmission' => 'Manual',
                'price' => 60.00,
                'img' => 'h1.jpg'
            ],
            [
                'id' => 5,
                'name' => 'Kia Picanto',
                'category' => 'Promociones',
                'passengers' => 4,
                'ac' => true,
                'transmission' => 'Manual',
                'price' => 19.99,
                'img' => 'picanto.jpg'
            ]
        ];
    }
}
