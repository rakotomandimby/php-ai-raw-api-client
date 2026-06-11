<?php

/**
 * Calls the Anthropic Messages API.
 *
 * @param string $instruction The system instruction to steer the model's behavior.
 * @param string $prompt The user prompt/input for the model.
 * @param string $model The model name (e.g., 'claude-opus-4-5').
 * @param string|null $apiKey Optional API key. If not provided, it attempts to read from the ANTHROPIC_API_KEY environment variable.
 * @return array The decoded JSON response from the API.
 * @throws Exception If the API key is missing, if cURL fails, or if the API returns an error.
 */
function call_anthropic_message(string $instruction, string $prompt, string $model, ?string $apiKey = null): array
{
    $max_tokens = [
        'claude-haiku-4-5' => 64000,
        'claude-sonnet-4-6' => 64000,
        'claude-opus-4-8' => 128000
];
    // Resolve the API key
    $apiKey = $apiKey ?? getenv('ANTHROPIC_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('Anthropic API key is required. Please provide it or set the ANTHROPIC_API_KEY environment variable.');
    }

    $url = 'https://api.anthropic.com/v1/messages';

    // Build the request payload
    $payload = [
        'model'      => $model,
        'max_tokens' => $max_tokens[$model] ?? 64000, 
        'messages'   => [
            [
                'role'    => 'user',
                'content' => $prompt,
            ],
        ],
    ];

    if ($instruction !== '') {
        $payload['system'] = $instruction;
    }

    // Hardcode generation configuration parameters as per requirement
    $payload['temperature'] = 0.7;

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
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
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
        $errorCode    = $decoded['error']['type'] ?? $httpCode;
        throw new Exception("Anthropic API error (HTTP $httpCode, Code $errorCode): $errorMessage", $httpCode);
    }

    return $decoded;
}
