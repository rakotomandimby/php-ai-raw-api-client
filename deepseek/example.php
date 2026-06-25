<?php

require_once __DIR__ . '/deepseek-client.php';

// To run this example, ensure the DEEPSEEK_API_KEY environment variable is set:
// export DEEPSEEK_API_KEY="your-api-key"
// php deepseek/example.php

try {
    $instruction = "You are a poetic assistant. Answer in rhyme.";
    $prompt = "Explain why the sky is blue in one sentence.";
    $model = "deepseek-v4-pro"; // User's preferred model

    echo "Sending request to DeepSeek Chat Completions API...\n";
    echo "Model: $model\n";
    echo "System Instruction: $instruction\n";
    echo "Prompt: $prompt\n\n";

    // Call the chat completion
    $response = call_deepseek_chat($instruction, $prompt, $model);

    echo "Response ID: " . ($response['id'] ?? 'N/A') . "\n\n";
    echo "Processing response output:\n";

    // Extract and print the output text
    $outputText = '';
    if (!empty($response['choices'])) {
        foreach ($response['choices'] as $choiceIndex => $choice) {
            echo "Choice [$choiceIndex] Finish Reason: " . ($choice['finish_reason'] ?? 'unknown') . "\n";
            if (isset($choice['message']['content'])) {
                $outputText .= $choice['message']['content'];
            }
        }
    }

    echo "\nFinal Answer:\n";
    echo "----------------------------------------\n";
    echo $outputText . "\n";
    echo "----------------------------------------\n";

} catch (Exception $e) {
    echo "Error occurred: " . $e->getMessage() . "\n";
}

