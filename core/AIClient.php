<?php

require_once __DIR__ . '/Config.php';

/**
 * AIClient Class
 * Executes low-level HTTP requests and multipart/form-data file uploads to the Python AI service
 */
class AIClient {
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $baseUrl = 'http://127.0.0.1:8000', int $timeout = 60) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Post a file to a specific AI endpoint
     * 
     * @param string $endpoint API path e.g. '/upscale'
     * @param string $filePath Local absolute path of target image
     * @param array $extraFields Custom POST variables
     * @param bool $returnBinary If true, returns raw binary response. If false, parses JSON response.
     * @return array|string|bool Array of details on JSON, raw string on binary, or false on error
     */
    public function postFile(string $endpoint, string $filePath, array $extraFields = [], bool $returnBinary = false): array|string|bool {
        if (!file_exists($filePath)) {
            return false;
        }

        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        // Create CURLFile object
        $mime = mime_content_type($filePath) ?: 'image/jpeg';
        $cfile = new CURLFile($filePath, $mime, basename($filePath));

        // Build POST fields
        $postData = array_merge(['image' => $cfile], $extraFields);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            $logMsg = date('[Y-m-d H:i:s] ') . "AI API Request Failed. URL: {$url}. Code: {$httpCode}. Error: {$error}. Response: {$response}\n";
            @file_put_contents(Config::LOG_PATH . 'ai_error.log', $logMsg, FILE_APPEND);
            return false;
        }

        if ($returnBinary) {
            return $response;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : ['raw' => $response];
    }

    /**
     * Check if the Python microservice is active and healthy
     */
    public function isHealthy(): bool {
        $ch = curl_init($this->baseUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            return isset($data['status']) && $data['status'] === 'online';
        }
        return false;
    }
}
