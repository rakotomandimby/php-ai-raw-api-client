# php-ai-raw-api-client

Small standalone PHP examples for calling raw HTTP APIs from major LLM providers without a framework or SDK.

## Purpose

This repository shows how to:

- send a prompt and an optional system instruction to several AI providers
- authenticate requests with provider-specific API keys
- build JSON payloads manually
- execute HTTPS POST requests with cURL
- decode JSON responses and surface API or transport failures as `Exception`s
- extract generated text from each provider's response schema

It is useful if you want a very small PHP starting point for provider integration, debugging raw requests, or comparing response formats across vendors.

## Repository structure

- `/openai/openai-client.php` — calls the OpenAI `/v1/responses` endpoint hardcoded in this repo
- `/openai/example.php` — runnable OpenAI example
- `/anthropic/anthropic-client.php` — calls the Anthropic Messages API
- `/anthropic/example.php` — runnable Anthropic example
- `/google-ai/google-ai-client.php` — calls the Google AI `/v1beta/interactions` endpoint hardcoded in this repo
- `/google-ai/example.php` — runnable Google AI example
- `/deepseek/deepseek-client.php` — calls the DeepSeek Chat Completions API
- `/deepseek/example.php` — runnable DeepSeek example

## What the code does

Each client file exposes one function:

- `call_openai_response(string $instruction, string $prompt, string $model, ?string $apiKey = null): array`
- `call_anthropic_message(string $instruction, string $prompt, string $model, ?string $apiKey = null): array`
- `call_gemini_interaction(string $instruction, string $prompt, string $model, ?string $apiKey = null): array`
- `call_deepseek_chat(string $instruction, string $prompt, string $model, ?string $apiKey = null): array`

All four functions follow the same pattern:

1. resolve the API key from an argument or environment variable
2. build a provider-specific request payload
3. send a POST request with cURL
4. decode the JSON body into a PHP array
5. throw an exception on missing credentials, cURL failure, invalid JSON, or non-2xx API responses
6. return the decoded response for the caller to interpret

## Requirements

- PHP 8+
- PHP cURL extension enabled
- outbound network access to the provider API
- a valid API key for the provider you want to call

## Provider configuration

| Provider | Client function | API endpoint | Environment variable used by the code |
| --- | --- | --- | --- |
| OpenAI | `call_openai_response()` | `https://api.openai.com/v1/responses` | `OPENAI_API_KEY` |
| Anthropic | `call_anthropic_message()` | `https://api.anthropic.com/v1/messages` | `ANTHROPIC_API_KEY` |
| Google AI | `call_gemini_interaction()` | `https://generativelanguage.googleapis.com/v1beta/interactions` | `GOOGLEAI_API_KEY` |
| DeepSeek | `call_deepseek_chat()` | `https://api.deepseek.com/chat/completions` | `DEEPSEEK_API_KEY` |

## Default request behavior

The scripts keep the interface intentionally small and hardcode some generation settings:

- OpenAI: `temperature=0.7`, `top_p=0.95`
- Anthropic: `temperature=0.7`, model-specific `max_tokens` with a `64000` fallback
- Google AI: `temperature=0.7`, `top_p=0.95`, `max_output_tokens=64000`
- DeepSeek: `temperature=0.7`, `top_p=0.95`, `stream=false`

If you need other parameters, edit the payload in the corresponding client file.

## How to use

### 1. Pick a provider

Use the directory that matches the service you want to call.

### 2. Set the API key

Examples:

```bash
export OPENAI_API_KEY="your-openai-key"
export ANTHROPIC_API_KEY="your-anthropic-key"
export GOOGLEAI_API_KEY="your-google-ai-key"
export DEEPSEEK_API_KEY="your-deepseek-key"
```

### 3. Run one of the included examples

```bash
php openai/example.php
php anthropic/example.php
php google-ai/example.php
php deepseek/example.php
```

Each example:

- loads the local client file with `require_once`
- defines an instruction, a prompt, and a model name
- calls the provider function
- walks the provider response structure
- prints the final generated text to stdout

## Using the client functions in your own code

### OpenAI

```php
<?php
require_once __DIR__ . '/openai/openai-client.php';

$response = call_openai_response(
    'You are a concise assistant.',
    'Summarize the purpose of this repository.',
    'gpt-5.4-mini'
);
```

### Anthropic

```php
<?php
require_once __DIR__ . '/anthropic/anthropic-client.php';

$response = call_anthropic_message(
    'You are a concise assistant.',
    'Summarize the purpose of this repository.',
    'claude-haiku-4-5'
);
```

### Google AI

```php
<?php
require_once __DIR__ . '/google-ai/google-ai-client.php';

$response = call_gemini_interaction(
    'You are a concise assistant.',
    'Summarize the purpose of this repository.',
    'gemini-3.5-flash'
);
```

### DeepSeek

```php
<?php
require_once __DIR__ . '/deepseek/deepseek-client.php';

$response = call_deepseek_chat(
    'You are a concise assistant.',
    'Summarize the purpose of this repository.',
    'deepseek-chat'
);
```

Because each function returns the full decoded API payload, your application can either:

- extract only the generated text
- inspect IDs, metadata, and intermediate output objects
- log raw responses while integrating or debugging

## Response parsing notes

The bundled example files extract text from the response shapes expected by the current scripts:

- OpenAI: `output[*].content[*].text` when `type === "output_text"`
- Anthropic: `content[*].text` when `type === "text"`
- Google AI: `steps[*].content[*].text` when the step type is `model_output`
- DeepSeek: `choices[*].message.content`

These paths describe the structures handled by the code in this repository. If the provider changes its schema or you request other output types, update the extraction logic in the example or in your application code.

## Error handling

The client functions throw `Exception` when:

- the API key is missing
- cURL cannot initialize or execute the request
- the response body is not valid JSON
- the provider returns a non-success HTTP status

Wrap calls in `try/catch` if you want to show user-friendly messages or retry failed requests.

## Limitations

This repository is intentionally minimal:

- no Composer package or autoloading
- no retries or backoff
- no streaming support
- no typed response objects
- no automated tests
- no abstraction layer across providers beyond similar function signatures

## When to extend it

This codebase is a good base if you want to add:

- configurable generation parameters
- shared helper functions for cURL and error handling
- response normalization across providers
- test coverage with mocked HTTP responses
- Composer packaging for reuse in other projects
