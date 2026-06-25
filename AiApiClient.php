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
    $this->openaiApiKey = $openaiApiKey;
    $this->anthropicApiKey = $anthropicApiKey;
    $this->googleAiApiKey = $googleAiApiKey;
    $this->deepseekApiKey = $deepseekApiKey;
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

    public function askDeepSeek(string $systemInstruction, string $userPrompt, string $model): string
    {
      $response = call_deepseek_chat(
        $systemInstruction,
        $userPrompt,
        $model,
        $this->deepseekApiKey
        );

      return $this->extractDeepSeekText($response);
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
