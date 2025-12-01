<?php
declare(strict_types=1);


final class OpenAIClient
{
    // Store the API key
    private string $apiKey;
    // Which AI model
    private string $model;
    // The OpenAI web address
    private string $baseUrl;


    /**
     * OpenAI Client setup
     * 
     * @param string|null $apiKey - ApenAI key
     * @param string $model - AI model
     * @param string $baseUrl - OpenAI's web address
     */
    public function __construct(
        ?string $apiKey = null, 
        string $model = 'gpt-4o-mini', 
        string $baseUrl = 'https://api.openai.com/v1')
    {
        // Get the API key
        if ($apiKey !== null) {
            $this->apiKey = trim($apiKey);
        } else {
            $keyFromEnv = $this->loadApiKey();
            $this->apiKey = $keyFromEnv !== null ? trim($keyFromEnv) : '';
        }

        // Make sure API key exists
        if ($this->apiKey === '') {
        error_log('OpenAI API key missing from .env file');
        throw new RuntimeException('AI service is not configured');
}

$this->model = $model;
$this->baseUrl = rtrim($baseUrl, '/');

        $this->model = $model;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Load API key from .env
     * 
     * @return string - API key
     */
    private function loadApiKey(): string
    {
        // Path to .env
        $envPath = dirname(__DIR__) . '/.env';

        // Check if .env exists
        if (!file_exists($envPath)) {
            return '';
        }

        // Read the entire file content
        $content = file_get_contents($envPath);

        if ($content === false) {
            return '';
        }

        if (preg_match('/^OPENAI_API_KEY\s*=\s*(.+)$/m', $content, $matches)) {
            // Remove quotes and whitespace
            return trim($matches[1], " \t\n\r\0\x0B\"'");
        }

        return '';
    }

    /**
     * @param array $messages - Array of message objects
     * @param float $temperature - How creative AI should be (0 = focused | 10 = creative)
     * @return string - The AI's response to questions
     */
    public function chat(array $messages, float $temperature = 0.3): string
    {
        // Make sure theres at least one message
        if (empty($messages)) {
            throw new InvalidArgumentException('Messages array cannot be empty');
        }

        // Data-payload sent to OpenAI 
        $payload = [
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
        ];

        // Send request to OpenAI
        $response = $this->request('/chat/completions', $payload);

        // Extract reply from AI's respnse
        $aiReply = $this->extractReply($response);

        return trim($aiReply);
    }

    /**
     * Extract the AI's respnse from OpenAI's respnse
     * 
     * @param array $response - The full response from OpenAI
     * @return string - Just the text reply
     */
    private function request(string $path, array $payload): array
    {
        // Create the full URL
        $url = $this->baseUrl . $path;

        // Convert from PHP-array to JSON
        $jsonData = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($jsonData === false) {
            throw new RuntimeException('Failed to encode request payload as JSON');
        }

        // Initialize CURL
        $curl = curl_init($url);

        if ($curl === false) {
            throw new RuntimeException('Failed to initialize CURL');
        }

        // Set up the request options
        curl_setopt($curl, CURLOPT_POST, true);             // Use POST method
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);   // Return as string
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);            // Wait 30 seconds before timeout
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);  // The json data to be sent

        // Set HTTP headers
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',           // Sending JSON
            'Authorization: Bearer ' . $this->apiKey,   // API key for authentication
        ]);

        // Execute request
        $responseText = curl_exec($curl);

        // Check if there was an error
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);

        // Get the HTTP status code
        $httpStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        // Close the CURL connection
        curl_close($curl);

        // Handle CURL errors
        if ($responseText == false || $errorNumber !== 0) {
            $error = $errorMessage !== '' ? $errorMessage : 'unknown error';
            error_log('OpenAI CURL error: ' . $error);
            throw new RuntimeException('Unable to connect to AI service. Please try again later.');
        }

        // Convert the JSON response text back to PHP array
        $responseArray = json_decode($responseText, true);

        if (!is_array($responseArray)) {
            error_log('Unable to decode OpenAI response: ' . $responseText);
            throw new RuntimeException('Received invalid response from AI service.');
        }

        // Check if OpenAI returned an error
        if ($httpStatus >= 400) {
            // Try to get the error message from the response
            $errorMsg = 'unkown error';

            if (isset($responseArray['error']) && is_array($responseArray['error'])) {
                if (isset($responseArray['error']['message'])) {
                    $errorMsg = $responseArray['error']['message'];
                }
            }

            error_log("OpenAI API error ({$httpStatus}): {$errorMsg}");
            throw new RuntimeException('AI service returned an error. Please try again later.');
        }

        return $responseArray;
    }

    /**
     * Extract the AI's message from OpenAI's response
     *
     * @param array $response - The full response from OpenAI
     * @return string - Just the text reply
     */
    private function extractReply(array $response): string
    {
        // Check if the response has the expected structure
        if (!isset($response['choices']) || !is_array($response['choices'])) {
            error_log('OpenAI response missing choices');
            throw new RuntimeException('Received invalid response from AI service.');
        }

        if (empty($response['choices'])) {
            error_log('OpenAI response has empty choices array');
            throw new RuntimeException('Received invalid response from AI service.');
        }

        $firstChoice = $response['choices'][0];

        if (!isset($firstChoice['message']['content'])) {
            error_log('OpenAI response missing message content');
            throw new RuntimeException('Received invalid response from AI service.');
        }

        return (string)$firstChoice['message']['content'];
    }
}
