<?php

require_once __DIR__ . '/google-ai-client.php';

// To run this example, ensure the GEMINI_API_KEY environment variable is set:
// export GEMINI_API_KEY="your-api-key"
// php google-ai/example.php

try {
    $instruction = "You are a poetic assistant. Answer in rhyme.";
    $prompt = "Explain why the sky is blue in one sentence.";
    $model = "gemini-3.5-flash"; // User's preferred model

    echo "Sending request to Gemini Interactions API...\n";
    echo "Model: $model\n";
    echo "System Instruction: $instruction\n";
    echo "Prompt: $prompt\n\n";

    // Call the interaction
    $response = call_gemini_interaction($instruction, $prompt, $model);

    echo "Interaction ID: " . ($response['id'] ?? 'N/A') . "\n\n";
    echo "Processing response steps:\n";

    // Traverse the steps schema to extract the text output
    $outputText = '';
    if (!empty($response['steps'])) {
        foreach ($response['steps'] as $stepIndex => $step) {
            $type = $step['type'] ?? 'unknown';
            echo "Step [$stepIndex] Type: $type\n";

            if ($type === 'model_output' && !empty($step['content'])) {
                foreach ($step['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'text') {
                        $outputText .= $contentItem['text'] ?? '';
                    }
                }
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
