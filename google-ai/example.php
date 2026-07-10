<?php

require_once __DIR__ . '/google-ai-client.php';

// To run this example, ensure the GOOGLEAI_API_KEY environment variable is set:
// export GOOGLEAI_API_KEY="your-api-key"
// php google-ai/example.php

try {
    $instruction = "You are a helpful assistant.";
    $model = "gemini-3.5-flash"; // User's preferred model
    $messages = [];

    echo "Sending a 3-turn conversation to model: $model in a single API call\n";
    echo "System Instruction: $instruction\n\n";

    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina'],
        ['role' => 'model', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What color is the sky?'],
        ['role' => 'model', 'content' => 'The sky is typically blue on a clear day.'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    foreach ($messages as $index => $msg) {
        $roleLabel = ($msg['role'] === 'user') ? 'User' : 'Model';
        echo "Turn " . (floor($index / 2) + 1) . " - $roleLabel: {$msg['content']}\n";
    }
    echo "\nCalling API...\n";

    $response = call_gemini_interaction($instruction, $messages, $model);
    
    // Extract text response
    $outputText = '';
    if (!empty($response['steps'])) {
        foreach ($response['steps'] as $step) {
            if (($step['type'] ?? '') === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'text') {
                        $outputText .= $contentItem['text'] ?? '';
                    }
                }
            }
        }
    }
    echo "Final Model Response: $outputText\n";

} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
}
