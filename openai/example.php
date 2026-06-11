<?php

require_once __DIR__ . '/openai-client.php';

// To run this example, ensure the OPENAI_API_KEY environment variable is set:
// export OPENAI_API_KEY="your-api-key"
// php openai/example.php

try {
    $instruction = "You are a poetic assistant. Answer in rhyme.";
    $prompt = "Explain why the sky is blue in one sentence.";
    $model = "gpt-5.4-mini"; // User's preferred model

    echo "Sending request to OpenAI Responses API...\n";
    echo "Model: $model\n";
    echo "System Instruction: $instruction\n";
    echo "Prompt: $prompt\n\n";

    // Call the response creation
    $response = call_openai_response($instruction, $prompt, $model);

    echo "Response ID: " . ($response['id'] ?? 'N/A') . "\n\n";
    echo "Processing response output:\n";

    // Traverse the output items schema to extract the text output
    $outputText = '';
    if (!empty($response['output'])) {
        foreach ($response['output'] as $stepIndex => $item) {
            $type = $item['type'] ?? 'unknown';
            echo "Step [$stepIndex] Type: $type\n";

            if ($type === 'message' && !empty($item['content'])) {
                foreach ($item['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'output_text') {
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
