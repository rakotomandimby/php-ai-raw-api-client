<?php

/**
 * Calls the DeepSeek Chat Completions API with multi-turn conversation support.
 *
 * @param string $instruction The system instruction to steer the model's behavior.
 * @param array  $messages    The conversation history. Each entry must be a provider-agnostic
 *                            array with 'role' (e.g. 'user' or 'assistant') and 'content' keys.
 * @param string $model       The model name (e.g., 'deepseek-chat').
 * @param string|null $apiKey Optional API key. If not provided, it attempts to read from the DEEPSEEK_API_KEY environment variable.
 * @return array The decoded JSON response from the API.
 * @throws Exception If the API key is missing, if cURL fails, or if the API returns an error.
 */
function call_deepseek_chat(string $instruction, array $messages, string $model, ?string $apiKey = null): array
{
    // Resolve the API key
    $apiKey = $apiKey ?? getenv('DEEPSEEK_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('DeepSeek API key is required. Please provide it or set the DEEPSEEK_API_KEY environment variable.');
    }

    $url = 'https://api.deepseek.com/chat/completions';

    // Build messages payload: prepend system instruction if provided,
    // then map the provider-agnostic messages to DeepSeek's format.
    $deepseekMessages = [];
    if ($instruction !== '') {
        $deepseekMessages[] = [
            'role'    => 'system',
            'content' => $instruction,
        ];
    }
    foreach ($messages as $msg) {
        $deepseekMessages[] = [
            'role'    => $msg['role'],
            'content' => $msg['content'],
        ];
    }

    // Build the request payload
    $payload = [
        'model'    => $model,
        'messages' => $deepseekMessages,
        'stream'   => false,
    ];

    // Hardcode generation configuration parameters as per requirement
    $payload['temperature'] = 0.7;
    $payload['top_p'] = 0.95;

    // Initialize cURL
    $ch = curl_init($url);
    if ($ch === false) {
        throw new Exception('Failed to initialize cURL session.');
    }

    $jsonData = json_encode($payload);
    if ($jsonData === false) {
        throw new Exception('Failed to encode payload to JSON: ' . json_last_error_msg());
    }

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Execute the request
    $response = curl_exec($ch);
    $errorMsg = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('cURL Request failed: ' . $errorMsg);
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Failed to decode JSON response: ' . json_last_error_msg() . '. Response was: ' . $response);
    }

    // Check for HTTP errors or API errors
    if ($httpCode < 200 || $httpCode >= 300) {
        $errorMessage = $decoded['error']['message'] ?? 'Unknown API error';
        $errorCode    = $decoded['error']['code'] ?? $httpCode;
        throw new Exception("DeepSeek API error (HTTP $httpCode, Code $errorCode): $errorMessage", $httpCode);
    }

    return $decoded;
}
