<?php
/**
 * Supabase PHP Client
 * Simple REST API client for Supabase database operations
 */

class SupabaseClient {
    private string $url;
    private string $apiKey;
    private string $baseUrl;

    public function __construct(string $url, string $apiKey) {
        $this->url = rtrim($url, '/');
        $this->apiKey = $apiKey;
        $this->baseUrl = $this->url . '/rest/v1';
    }

    /**
     * Execute a SELECT query
     */
    public function select(string $table, array $options = []): array {
        $url = $this->baseUrl . '/' . $table;
        $params = [];

        if (!empty($options['select'])) {
            $params['select'] = $options['select'];
        }

        if (!empty($options['where'])) {
            foreach ($options['where'] as $key => $value) {
                if (is_array($value)) {
                    $params[$key] = $value['operator'] . '.' . $value['value'];
                } else {
                    $params[$key] = 'eq.' . $value;
                }
            }
        }

        if (!empty($options['order'])) {
            $params['order'] = $options['order'];
        }

        if (!empty($options['limit'])) {
            $params['limit'] = $options['limit'];
        }

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $result = $this->request('GET', $url);
        return is_array($result) ? $result : [];
    }

    /**
     * Insert a record
     */
    public function insert(string $table, array $data): ?array {
        $url = $this->baseUrl . '/' . $table;
        $result = $this->request('POST', $url, $data, ['Prefer' => 'return=representation']);
        return is_array($result) && !empty($result) ? $result[0] : null;
    }

    /**
     * Update records
     */
    public function update(string $table, array $data, array $where = []): bool {
        $url = $this->baseUrl . '/' . $table;

        if (!empty($where)) {
            $params = [];
            foreach ($where as $key => $value) {
                $params[$key] = 'eq.' . $value;
            }
            $url .= '?' . http_build_query($params);
        }

        $this->request('PATCH', $url, $data);
        return true;
    }

    /**
     * Delete records
     */
    public function delete(string $table, array $where): bool {
        $url = $this->baseUrl . '/' . $table;

        if (!empty($where)) {
            $params = [];
            foreach ($where as $key => $value) {
                $params[$key] = 'eq.' . $value;
            }
            $url .= '?' . http_build_query($params);
        }

        $this->request('DELETE', $url);
        return true;
    }

    /**
     * Execute raw SQL via RPC
     */
    public function rpc(string $functionName, array $params = []): mixed {
        $url = $this->url . '/rest/v1/rpc/' . $functionName;
        return $this->request('POST', $url, $params);
    }

    /**
     * Make HTTP request to Supabase
     */
    private function request(string $method, string $url, ?array $data = null, array $extraHeaders = []): mixed {
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

        foreach ($extraHeaders as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $options = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
            ]
        ];

        if ($data !== null && in_array($method, ['POST', 'PATCH', 'PUT'])) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            error_log("Supabase API Error: Failed to connect to $url");
            return null;
        }

        if (!empty($http_response_header)) {
            $statusLine = $http_response_header[0];
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;

            if ($httpCode >= 400) {
                error_log("Supabase API Error ($httpCode): " . $response);
                return null;
            }
        }

        return json_decode($response, true);
    }
}

/**
 * Get Supabase client instance
 */
function getSupabaseClient(): SupabaseClient {
    static $client = null;

    if ($client === null) {
        $url = getenv('VITE_SUPABASE_URL');
        $apiKey = getenv('VITE_SUPABASE_SUPABASE_ANON_KEY');

        if (!$url || !$apiKey) {
            throw new Exception('Supabase configuration missing');
        }

        $client = new SupabaseClient($url, $apiKey);
    }

    return $client;
}
