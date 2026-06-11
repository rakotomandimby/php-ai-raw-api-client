<?php

require_once __DIR__ . '/anthropic-client.php';

// To run this example, ensure the ANTHROPIC_API_KEY environment variable is set:
// export ANTHROPIC_API_KEY="your-api-key"
// php anthropic/example.php

try {
    $instruction = "You are a poetic assistant. Answer in rhyme.";
    $prompt = "Explain why the sky is blue in one sentence.";
    $model = "claude-haiku-4-5"; // User's preferred model

    echo "Sending request to Anthropic Messages API...\n";
    echo "Model: $model\n";
    echo "System Instruction: $instruction\n";
    echo "Prompt: $prompt\n\n";

    // Call the message creation
    $response = call_anthropic_message($instruction, $prompt, $model);

    echo "Response ID: " . ($response['id'] ?? 'N/A') . "\n\n";
    echo "Processing response content:\n";

    // Traverse the content blocks schema to extract the text output
    $outputText = '';
    if (!empty($response['content'])) {
        foreach ($response['content'] as $stepIndex => $contentItem) {
            $type = $contentItem['type'] ?? 'unknown';
            echo "Step [$stepIndex] Type: $type\n";

            if ($type === 'text') {
                $outputText .= $contentItem['text'] ?? '';
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
