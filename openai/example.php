<?php

require_once __DIR__ . '/openai-client.php';

// To run this example, ensure the OPENAI_API_KEY environment variable is set:
// export OPENAI_API_KEY="your-api-key"
// php openai/example.php

try {
    $instruction = "You are a helpful assistant.";
    $model = "gpt-5.4-mini"; // User's preferred model
    $messages = [];

    echo "Sending a 3-turn conversation to model: $model in a single API call\n";
    echo "System Instruction: $instruction\n\n";

    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina'],
        ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What color is the sky?'],
        ['role' => 'assistant', 'content' => 'The sky is typically blue on a clear day.'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    foreach ($messages as $index => $msg) {
        $roleLabel = ($msg['role'] === 'user') ? 'User' : 'Model';
        echo "Turn " . (floor($index / 2) + 1) . " - $roleLabel: {$msg['content']}\n";
    }
    echo "\nCalling API...\n";

    $response = call_openai_response($instruction, $messages, $model);

    // Extract text response from OpenAI's output items schema
    $outputText = '';
    if (!empty($response['output'])) {
        foreach ($response['output'] as $item) {
            if (($item['type'] ?? '') === 'message' && !empty($item['content'])) {
                foreach ($item['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'output_text') {
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
