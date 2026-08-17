<?php

namespace Rakotomandimby\PhpAiRawApiClient;

require_once __DIR__ . '/openai/openai-client.php';
require_once __DIR__ . '/anthropic/anthropic-client.php';
require_once __DIR__ . '/google-ai/google-ai-client.php';
require_once __DIR__ . '/deepseek/deepseek-client.php';

class AiApiClient
{
    private ?string $openaiApiKey;
    private ?string $anthropicApiKey;
    private ?string $googleAiApiKey;
    private ?string $deepseekApiKey;

    public function __construct(
        ?string $openaiApiKey = null,
        ?string $anthropicApiKey = null,
        ?string $googleAiApiKey = null,
        ?string $deepseekApiKey = null
    ) {
        $this->openaiApiKey    = $openaiApiKey;
        $this->anthropicApiKey = $anthropicApiKey;
        $this->googleAiApiKey  = $googleAiApiKey;
        $this->deepseekApiKey  = $deepseekApiKey;
    }

    /**
     * Sends a multi-turn conversation to Google AI (Gemini).
     *
     * @param string $systemInstruction The system instruction.
     * @param array  $messages          Provider-agnostic messages: [['role' => '...', 'content' => '...'], ...]
     * @param string $model             Model name.
     * @return array Uniform response: ['text' => string, 'interaction_id' => string|null]
     */
    public function askGoogleAi(string $systemInstruction, array $messages, string $model): array
    {
        $response = call_gemini_interaction(
            $systemInstruction,
            $messages,
            $model,
            $this->googleAiApiKey
        );

        return [
            'text'           => $this->extractGoogleAiText($response),
            'interaction_id' => $response['responseId'] ?? ($response['id'] ?? null),
        ];
    }

    /**
     * Sends a multi-turn conversation to OpenAI.
     *
     * @param string $systemInstruction The system instruction.
     * @param array  $messages          Provider-agnostic messages: [['role' => '...', 'content' => '...'], ...]
     * @param string $model             Model name.
     * @return array Uniform response: ['text' => string, 'interaction_id' => string|null]
     */
    public function askOpenai(string $systemInstruction, array $messages, string $model): array
    {
        $response = call_openai_response(
            $systemInstruction,
            $messages,
            $model,
            $this->openaiApiKey
        );

        return [
            'text'           => $this->extractOpenaiText($response),
            'interaction_id' => $response['id'] ?? null,
        ];
    }

    /**
     * Sends a multi-turn conversation to Anthropic (Claude).
     *
     * @param string $systemInstruction The system instruction.
     * @param array  $messages          Provider-agnostic messages: [['role' => '...', 'content' => '...'], ...]
     * @param string $model             Model name.
     * @return array Uniform response: ['text' => string, 'interaction_id' => string|null]
     */
    public function askAnthropic(string $systemInstruction, array $messages, string $model): array
    {
        $response = call_anthropic_message(
            $systemInstruction,
            $messages,
            $model,
            $this->anthropicApiKey
        );

        return [
            'text'           => $this->extractAnthropicText($response),
            'interaction_id' => $response['id'] ?? null,
        ];
    }

    /**
     * Sends a multi-turn conversation to DeepSeek.
     *
     * @param string $systemInstruction The system instruction.
     * @param array  $messages          Provider-agnostic messages: [['role' => '...', 'content' => '...'], ...]
     * @param string $model             Model name.
     * @return array Uniform response: ['text' => string, 'interaction_id' => string|null]
     */
    public function askDeepSeek(string $systemInstruction, array $messages, string $model): array
    {
        $response = call_deepseek_chat(
            $systemInstruction,
            $messages,
            $model,
            $this->deepseekApiKey
        );

        return [
            'text'           => $this->extractDeepSeekText($response),
            'interaction_id' => $response['id'] ?? null,
        ];
    }

    private function extractGoogleAiText(array $response): string
    {
        $outputText = '';

        if (!empty($response['candidates'])) {
            foreach ($response['candidates'] as $candidate) {
                if (!empty($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            $outputText .= $part['text'];
                        }
                    }
                }
            }
        }

        return $outputText;
    }

    private function extractOpenaiText(array $response): string
    {
        $outputText = '';

        if (!empty($response['output'])) {
            foreach ($response['output'] as $item) {
                $type = $item['type'] ?? '';

                if ($type !== 'message' || empty($item['content'])) {
                    continue;
                }

                foreach ($item['content'] as $contentItem) {
                    if (($contentItem['type'] ?? '') === 'output_text') {
                        $outputText .= $contentItem['text'] ?? '';
                    }
                }
            }
        }

        return $outputText;
    }

    private function extractAnthropicText(array $response): string
    {
        $outputText = '';

        if (!empty($response['content'])) {
            foreach ($response['content'] as $contentItem) {
                if (($contentItem['type'] ?? '') === 'text') {
                    $outputText .= $contentItem['text'] ?? '';
                }
            }
        }

        return $outputText;
    }

    private function extractDeepSeekText(array $response): string
    {
        $outputText = '';

        if (!empty($response['choices'])) {
            foreach ($response['choices'] as $choice) {
                if (isset($choice['message']['content'])) {
                    $outputText .= $choice['message']['content'];
                }
            }
        }

        return $outputText;
    }
}
