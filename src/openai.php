<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

final class OpenAIClient
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, string $model = 'gpt-4o-mini', string $baseUrl = 'https://api.openai.com/v1')
    {
        $this->apiKey = trim($apiKey ?? (env::get('OPENAI_API_KEY') ?? ''));
        if ($this->apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is missing. Add it to your .env file.');
        }

        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @param array<int, array{role:string, content:string}> $messages
     */
    public function chat(array $messages, float $temperature = 0.3): string
    {
        if (empty($messages)) {
            throw new InvalidArgumentException('Messages array cannot be empty');
        }

        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        $response = $this->request('/chat/completions', $payload);

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new RuntimeException('Unexpected response from the OpenAI API.');
        }

        return trim((string)$response['choices'][0]['message']['content']);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $path, array $payload): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL.');
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new RuntimeException('Failed to encode request payload as JSON.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => $body,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            throw new RuntimeException('OpenAI request failed: ' . ($error ?: 'unknown error'));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Unable to decode response from OpenAI: ' . $raw);
        }

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? 'unknown error';
            throw new RuntimeException("OpenAI API error ({$status}): {$message}");
        }

        return $decoded;
    }
}
