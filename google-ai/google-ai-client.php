<?php

/**
 * Calls the Google Vertex AI generateContent API with multi-turn conversation support.
 *
 * @param string $instruction The system instruction to steer the model's behavior.
 * @param array $messages The chat conversation history.
 * @param string $model The model name (e.g., 'gemini-2.5-flash-lite').
 * @param string|null $apiKey Optional API key. If not provided, it attempts to read from the GOOGLEAI_API_KEY environment variable.
 * @return array The decoded JSON response from the API.
 * @throws Exception If the API key is missing, if cURL fails, or if the API returns an error.
 */
function call_gemini_interaction(string $instruction, array $messages, string $model, ?string $apiKey = null): array
{
    // Resolve the API key
    $apiKey = $apiKey ?? getenv('GOOGLEAI_API_KEY');
    if (empty($apiKey)) {
        throw new Exception('Gemini API key is required. Please provide it or set the GOOGLEAI_API_KEY environment variable.');
    }

    $url = 'https://aiplatform.googleapis.com/v1/publishers/google/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

    // Build the request contents
    $contents = [];
    foreach ($messages as $msg) {
        $role = ($msg['role'] === 'assistant' || $msg['role'] === 'model') ? 'model' : 'user';
        $contents[] = [
            'role' => $role,
            'parts' => [
                [
                    'text' => $msg['content'],
                ],
            ],
        ];
    }

    $payload = [
        'contents' => $contents,
    ];

    if ($instruction !== '') {
        $payload['systemInstruction'] = [
            'parts' => [
                [
                    'text' => $instruction,
                ],
            ],
        ];
    }

    // Hardcode generation configuration parameters as per requirement
    $payload['generationConfig'] = [
        'temperature' => 0.7,
        'topP' => 0.95,
        'maxOutputTokens' => 64000,
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
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 180);

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

