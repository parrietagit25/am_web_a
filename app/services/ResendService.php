<?php
/**
 * Resend Email Integration Service
 */
class ResendService {
    private $apiKey;

    public function __construct() {
        $this->apiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
    }

    /**
     * Send email via Resend API
     * 
     * @param array $to Recipient emails
     * @param string $subject Subject
     * @param string $htmlBody HTML content
     * @return array Response status
     */
    public function sendEmail(array $to, $subject, $htmlBody) {
        if (empty($this->apiKey) || $this->apiKey === 're_YOUR_RESEND_API_KEY') {
            return [
                'status' => 'error',
                'message' => 'Resend API Key no configurada o inválida. Por favor actualice config.php.'
            ];
        }

        $url = 'https://api.resend.com/emails';
        
        // Sender: Verified domain sender address
        $from = 'Automarket Contacto <info@automarket.com.pa>';
        
        $payload = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return [
                'status' => 'error',
                'message' => 'Error de cURL: ' . $err
            ];
        }

        $resData = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'status' => 'success',
                'data' => $resData
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $resData['message'] ?? 'Error desconocido de Resend API.',
                'code' => $httpCode
            ];
        }
    }
}
