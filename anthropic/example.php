<?php

require_once __DIR__ . '/anthropic-client.php';

// To run this example, ensure the ANTHROPIC_API_KEY environment variable is set:
// export ANTHROPIC_API_KEY="your-api-key"
// php anthropic/example.php

try {
    $instruction = "You are a helpful assistant.";
    $model = "claude-haiku-4-5"; // User's preferred model
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

    $response = call_anthropic_message($instruction, $messages, $model);

    // Extract text response from Anthropic's content blocks schema
    $outputText = '';
    if (!empty($response['content'])) {
        foreach ($response['content'] as $contentItem) {
            if (($contentItem['type'] ?? '') === 'text') {
                $outputText .= $contentItem['text'] ?? '';
            }
        }
    }
    echo "Final Model Response: $outputText\n";

} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
}
