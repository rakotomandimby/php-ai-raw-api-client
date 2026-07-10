<?php

require_once __DIR__ . '/deepseek-client.php';

// To run this example, ensure the DEEPSEEK_API_KEY environment variable is set:
// export DEEPSEEK_API_KEY="your-api-key"
// php deepseek/example.php

try {
    $instruction = "You are a helpful assistant.";
    $model = "deepseek-chat"; // User's preferred model
    $messages = [];

    echo "Starting a 3-turn conversation with model: $model\n";
    echo "System Instruction: $instruction\n\n";

    // --- Turn 1 ---
    $prompt1 = "My name is Mihamina";
    echo "Turn 1 - User: $prompt1\n";
    $messages[] = ['role' => 'user', 'content' => $prompt1];

    $response1 = call_deepseek_chat($instruction, $messages, $model);

    // Extract text response from DeepSeek's choices schema
    $outputText1 = '';
    if (!empty($response1['choices'])) {
        foreach ($response1['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                $outputText1 .= $choice['message']['content'];
            }
        }
    }
    echo "Turn 1 - Model: $outputText1\n\n";
    $messages[] = ['role' => 'assistant', 'content' => $outputText1];

    // --- Turn 2 ---
    $prompt2 = "What color is the sky?";
    echo "Turn 2 - User: $prompt2\n";
    $messages[] = ['role' => 'user', 'content' => $prompt2];

    $response2 = call_deepseek_chat($instruction, $messages, $model);

    $outputText2 = '';
    if (!empty($response2['choices'])) {
        foreach ($response2['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                $outputText2 .= $choice['message']['content'];
            }
        }
    }
    echo "Turn 2 - Model: $outputText2\n\n";
    $messages[] = ['role' => 'assistant', 'content' => $outputText2];

    // --- Turn 3 ---
    $prompt3 = "What is my name?";
    echo "Turn 3 - User: $prompt3\n";
    $messages[] = ['role' => 'user', 'content' => $prompt3];

    $response3 = call_deepseek_chat($instruction, $messages, $model);

    $outputText3 = '';
    if (!empty($response3['choices'])) {
        foreach ($response3['choices'] as $choice) {
            if (isset($choice['message']['content'])) {
                $outputText3 .= $choice['message']['content'];
            }
        }
    }
    echo "Turn 3 - Model: $outputText3\n\n";

} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
}
