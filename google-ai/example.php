<?php

require_once __DIR__ . '/google-ai-client.php';

// To run this example, ensure the GOOGLEAI_API_KEY environment variable is set:
// export GOOGLEAI_API_KEY="your-api-key"
// php google-ai/example.php

try {
    $instruction = "You are a helpful assistant.";
    $model = "gemini-3.5-flash"; // User's preferred model
    $messages = [];

    echo "Starting a 3-turn conversation with model: $model\n";
    echo "System Instruction: $instruction\n\n";

    // --- Turn 1 ---
    $prompt1 = "My name is Mihamina";
    echo "Turn 1 - User: $prompt1\n";
    $messages[] = ['role' => 'user', 'content' => $prompt1];

    $response1 = call_gemini_interaction($instruction, $messages, $model);
    
    // Extract text response
    $outputText1 = '';
    if (!empty($response1['steps'])) {
        foreach ($response1['steps'] as $step) {
            if (($step['type'] ?? '') === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'text') {
                        $outputText1 .= $contentItem['text'] ?? '';
                    }
                }
            }
        }
    }
    echo "Turn 1 - Model: $outputText1\n\n";
    $messages[] = ['role' => 'model', 'content' => $outputText1];

    // --- Turn 2 ---
    $prompt2 = "What color is the sky?";
    echo "Turn 2 - User: $prompt2\n";
    $messages[] = ['role' => 'user', 'content' => $prompt2];

    $response2 = call_gemini_interaction($instruction, $messages, $model);

    $outputText2 = '';
    if (!empty($response2['steps'])) {
        foreach ($response2['steps'] as $step) {
            if (($step['type'] ?? '') === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'text') {
                        $outputText2 .= $contentItem['text'] ?? '';
                    }
                }
            }
        }
    }
    echo "Turn 2 - Model: $outputText2\n\n";
    $messages[] = ['role' => 'model', 'content' => $outputText2];

    // --- Turn 3 ---
    $prompt3 = "What is my name?";
    echo "Turn 3 - User: $prompt3\n";
    $messages[] = ['role' => 'user', 'content' => $prompt3];

    $response3 = call_gemini_interaction($instruction, $messages, $model);

    $outputText3 = '';
    if (!empty($response3['steps'])) {
        foreach ($response3['steps'] as $step) {
            if (($step['type'] ?? '') === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'text') {
                        $outputText3 .= $contentItem['text'] ?? '';
                    }
                }
            }
        }
    }
    echo "Turn 3 - Model: $outputText3\n\n";

} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
}
