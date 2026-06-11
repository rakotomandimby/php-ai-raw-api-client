<?php

/**
 * Calls the Google Gemini Interactions API using the "steps" schema.
 *
 * @param string $instruction The system instruction to steer the model's behavior.
 * @param string $prompt The user prompt/input for the model.
 * @param string $model The model name (e.g., 'gemini-3.5-flash').
 * @param string|null $apiKey Optional API key. If not provided, it attempts to read from the GEMINI_API_KEY environment variable.
 * @return array The decoded JSON response from the API.
 * @throws Exception If the API key is missing, if cURL fails, or if the API returns an error.
 */
function call_gemini_interaction(string $instruction, string $prompt, string $model, ?string $apiKey = null): array
{
    // Resolve the API key
    $apiKey = $apiKey ?? getenv('GOOGLE_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('Gemini API key is required. Please provide it or set the GOOGLE_API_KEY environment variable.');
    }

    $url = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    // Build the request payload
    $payload = [
        'model' => $model,
        'input' => $prompt,
    ];

    if ($instruction !== '') {
        $payload['system_instruction'] = [
            'parts' => [
                ['text' => $instruction]
            ]
        ];
    }

    // Hardcode generation configuration parameters as per requirement
    $payload['generation_config'] = [
        'temperature' => 0.7,
        'top_k' => 40,
        'top_p' => 0.95,
        'max_output_tokens' => 2048,
    ];

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
        'Api-Revision: 2026-05-20',
        'x-goog-api-key: ' . $apiKey,
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
        $errorCode = $decoded['error']['code'] ?? $httpCode;
        throw new Exception("Gemini API error (HTTP $httpCode, Code $errorCode): $errorMessage", $httpCode);
    }

    return $decoded;
}
