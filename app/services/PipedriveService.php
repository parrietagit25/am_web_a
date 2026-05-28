<?php
/**
 * Pipedrive CRM integration service
 */

class PipedriveService {
    private $apiToken;
    private $companyDomain;

    public function __construct() {
        $this->apiToken = defined('PIPEDRIVE_API_TOKEN') ? PIPEDRIVE_API_TOKEN : '';
        $this->companyDomain = defined('PIPEDRIVE_COMPANY_DOMAIN') ? PIPEDRIVE_COMPANY_DOMAIN : '';
    }

    /**
     * Create an Organization in Pipedrive
     * 
     * @param string $companyName
     * @return int|bool
     */
    public function createOrganization($companyName) {
        $payload = [
            'name' => $companyName
        ];
        return $this->postRequest('organizations', $payload);
    }

    /**
     * Create a Person in Pipedrive
     * 
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param int|null $orgId
     * @return int|bool
     */
    public function createPerson($name, $email, $phone, $orgId = null) {
        $payload = [
            'name' => $name,
            'email' => [$email],
            'phone' => [$phone]
        ];
        if ($orgId !== null) {
            $payload['org_id'] = $orgId;
        }
        return $this->postRequest('persons', $payload);
    }

    /**
     * Create a Deal in Pipedrive
     * 
     * @param string $title
     * @param int $personId
     * @param int $orgId
     * @param int|null $pipelineId
     * @param int|null $stageId
     * @return int|bool
     */
    public function createDeal($title, $personId, $orgId, $pipelineId = null, $stageId = null) {
        $payload = [
            'title' => $title,
            'person_id' => $personId,
            'org_id' => $orgId
        ];
        if ($pipelineId !== null) {
            $payload['pipeline_id'] = $pipelineId;
        }
        if ($stageId !== null) {
            $payload['stage_id'] = $stageId;
        }
        return $this->postRequest('deals', $payload);
    }

    /**
     * Create a Note in Pipedrive
     * 
     * @param string $content
     * @param int $dealId
     * @param int|null $personId
     * @param int|null $orgId
     * @return int|bool
     */
    public function createNote($content, $dealId, $personId = null, $orgId = null) {
        $payload = [
            'content' => $content,
            'deal_id' => $dealId
        ];
        if ($personId !== null) {
            $payload['person_id'] = $personId;
        }
        if ($orgId !== null) {
            $payload['org_id'] = $orgId;
        }
        return $this->postRequest('notes', $payload);
    }

    /**
     * Create a simple lead/deal (Legacy support)
     * 
     * @param array $leadData
     * @return array
     */
    public function createLead(array $leadData) {
        am_log("Sending legacy lead request: " . json_encode($leadData), "INFO");
        
        $orgName = $leadData['company'] ?? 'Cliente Corporativo';
        $orgId = $this->createOrganization($orgName);
        
        $personName = $leadData['name'] ?? 'Cliente Nuevo';
        $personEmail = $leadData['email'] ?? '';
        $personPhone = $leadData['phone'] ?? '';
        $personId = $this->createPerson($personName, $personEmail, $personPhone, $orgId);
        
        $dealTitle = 'Oportunidad: ' . ($leadData['interest'] ?? 'Rent A Car') . ' - ' . $personName;
        $dealId = $this->createDeal($dealTitle, $personId, $orgId);
        
        return [
            'status' => 'success',
            'person_id' => $personId,
            'deal_id' => $dealId
        ];
    }

    /**
     * Generic wrapper to submit cURL payloads to Pipedrive
     * 
     * @param string $endpoint
     * @param array $payload
     * @return int|bool
     */
    private function postRequest($endpoint, array $payload) {
        // Handle sandbox simulation
        if ($this->apiToken === 'AQUI_TU_API_TOKEN' || strpos($this->apiToken, 'placeholder') !== false || empty($this->apiToken)) {
            am_log("Pipedrive API sandbox mock execution. Endpoint: $endpoint. Payload: " . json_encode($payload), "DEBUG");
            return rand(1000, 9999);
        }

        $url = "https://{$this->companyDomain}.pipedrive.com/v1/{$endpoint}?api_token={$this->apiToken}";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !in_array($httpCode, [200, 201])) {
            $errDesc = $error ? $error : "HTTP $httpCode - Response: $response";
            $this->logPipedriveError("Failed POST to $endpoint. Payload: " . json_encode($payload) . ". Error: $errDesc");
            return false;
        }

        $decoded = json_decode($response, true);
        if (!$decoded || empty($decoded['success']) || !$decoded['success']) {
            $this->logPipedriveError("Unsuccessful response on $endpoint. Payload: " . json_encode($payload) . ". Response: $response");
            return false;
        }

        return $decoded['data']['id'] ?? true;
    }

    /**
     * Write errors to app/storage/logs/pipedrive.log
     * 
     * @param string $message
     */
    private function logPipedriveError($message) {
        $logDir = __DIR__ . '/../storage/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/pipedrive.log';
        $date = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$date] [ERROR] $message" . PHP_EOL, FILE_APPEND);
    }
}
