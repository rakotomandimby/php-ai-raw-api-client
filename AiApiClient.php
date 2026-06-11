<?php

require_once __DIR__ . '/openai/openai-client.php';
require_once __DIR__ . '/anthropic/anthropic-client.php';
require_once __DIR__ . '/google-ai/google-ai-client.php';

class AiApiClient
{
  private ?string $openaiApiKey;
  private ?string $anthropicApiKey;
  private ?string $googleAiApiKey;

  public function __construct(
    ?string $openaiApiKey = null,
    ?string $anthropicApiKey = null,
    ?string $googleAiApiKey = null
  ) {
    $this->openaiApiKey = $openaiApiKey;
    $this->anthropicApiKey = $anthropicApiKey;
    $this->googleAiApiKey = $googleAiApiKey;
    }

    public function askGoogleAi(string $systemInstruction, string $userPrompt, string $model): string
    {
      $response = call_gemini_interaction(
        $systemInstruction,
        $userPrompt,
        $model,
        $this->googleAiApiKey
        );

      return $this->extractGoogleAiText($response);
    }

    public function askOpenai(string $systemInstruction, string $userPrompt, string $model): string
    {
      $response = call_openai_response(
        $systemInstruction,
        $userPrompt,
        $model,
        $this->openaiApiKey
        );

      return $this->extractOpenaiText($response);
    }

    public function askAnthropic(string $systemInstruction, string $userPrompt, string $model): string
    {
      $response = call_anthropic_message(
        $systemInstruction,
        $userPrompt,
        $model,
        $this->anthropicApiKey
        );

      return $this->extractAnthropicText($response);
    }

    private function extractGoogleAiText(array $response): string
    {
      $outputText = '';

      if (!empty($response['steps'])) {
        foreach ($response['steps'] as $step) {
          $type = $step['type'] ?? '';

          if ($type !== 'model_output' || empty($step['content'])) {
            continue;
                }

                foreach ($step['content'] as $contentItem) {
                  if (($contentItem['type'] ?? '') === 'text') {
                    $outputText .= $contentItem['text'] ?? '';
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
}

