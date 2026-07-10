# php-ai-raw-api-client

Small standalone PHP examples for calling raw HTTP APIs from major LLM providers without a framework or SDK, supporting multi-turn conversations.

## Purpose

This repository shows how to:

- Send a system instruction and a multi-turn conversation history (array of messages) to several AI providers.
- Map a uniform, provider-agnostic message history to provider-specific payloads.
- Authenticate requests with provider-specific API keys.
- Build JSON payloads manually.
- Execute HTTPS POST requests with cURL.
- Decode JSON responses and handle API or transport failures.
- Extract generated text from each provider's unique response schema.
- Provide a uniform object-oriented interface (`AiApiClient`) that standardizes the response output.

It is useful if you want a very small PHP starting point for provider integration, debugging raw requests, or comparing response formats across vendors.

## Repository Structure

- [AiApiClient.php](file:///home/mihamina/Projects/php-ai-raw-api-client/AiApiClient.php) — The main Object-Oriented wrapper providing a unified interface across all providers.
- [/openai/openai-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/openai/openai-client.php) — Standalone function calling the OpenAI `/v1/responses` API.
- [/openai/example.php](file:///home/mihamina/Projects/php-ai-raw-api-client/openai/example.php) — Runnable multi-turn example for OpenAI.
- [/anthropic/anthropic-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/anthropic/anthropic-client.php) — Standalone function calling the Anthropic Messages API.
- [/anthropic/example.php](file:///home/mihamina/Projects/php-ai-raw-api-client/anthropic/example.php) — Runnable multi-turn example for Anthropic.
- [/google-ai/google-ai-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/google-ai/google-ai-client.php) — Standalone function calling the Google AI `/v1beta/interactions` API.
- [/google-ai/example.php](file:///home/mihamina/Projects/php-ai-raw-api-client/google-ai/example.php) — Runnable multi-turn example for Google AI.
- [/deepseek/deepseek-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/deepseek/deepseek-client.php) — Standalone function calling the DeepSeek Chat Completions API.
- [/deepseek/example.php](file:///home/mihamina/Projects/php-ai-raw-api-client/deepseek/example.php) — Runnable multi-turn example for DeepSeek.

## Requirements

- PHP 8.0+
- PHP cURL extension enabled
- Outbound network access to the provider APIs
- Valid API keys for the providers you want to call

## Provider Configuration

| Provider | Client Function | Endpoint URL | Environment Variable | Header Credentials |
| --- | --- | --- | --- | --- |
| **OpenAI** | `call_openai_response()` | `https://api.openai.com/v1/responses` | `OPENAI_API_KEY` | `Authorization: Bearer <KEY>` |
| **Anthropic** | `call_anthropic_message()` | `https://api.anthropic.com/v1/messages` | `ANTHROPIC_API_KEY` | `x-api-key: <KEY>`, `anthropic-version: 2023-06-01` |
| **Google AI** | `call_gemini_interaction()` | `https://generativelanguage.googleapis.com/v1beta/interactions` | `GOOGLEAI_API_KEY` | `x-goog-api-key: <KEY>`, `Api-Revision: 2026-05-20` |
| **DeepSeek** | `call_deepseek_chat()` | `https://api.deepseek.com/chat/completions` | `DEEPSEEK_API_KEY` | `Authorization: Bearer <KEY>` |

### Default Request Settings (Hardcoded)

To keep the interface minimal, generation settings are hardcoded inside each client file:
- **OpenAI**: `temperature=0.7`, `top_p=0.95`, `store=false`
- **Anthropic**: `temperature=0.7`, model-specific `max_tokens` (64,000 for Haiku/Sonnet, 128,000 for Opus)
- **Google AI**: `temperature=0.7`, `top_p=0.95`, `max_output_tokens=64000`, `store=false`
- **DeepSeek**: `temperature=0.7`, `top_p=0.95`, `stream=false`

---

## Detailed Input Format

All client functions and class methods accept a uniform input format for constructing conversations.

### 1. The `$instruction` Parameter (System Instruction)
A `string` containing system instructions or guidelines to steer the model's behavior. If empty (`""`), it is ignored or omitted from the request.
- **OpenAI**: Mapped to the `"instructions"` field at the root of the payload.
- **Anthropic**: Mapped to the `"system"` field at the root of the payload.
- **Google AI**: Mapped to the `"system_instruction"` field at the root of the payload.
- **DeepSeek**: Prepended to the messages array as a message object with the role `"system"`.

### 2. The `$messages` Parameter (Conversation History)
An `array` of associative arrays representing the conversation history. Each message object contains:
- `role` (string): The sender of the message.
- `content` (string): The text content of the message.

#### Role Mappings per Provider
Since different APIs expect different values for roles, the clients handle role propagation or mapping under the hood:

| Provider | Supported User Role | Supported Assistant Role | Notes / Constraints |
| --- | --- | --- | --- |
| **OpenAI** | `"user"` | `"assistant"` | Messages are passed directly in the `"input"` payload field. |
| **Anthropic** | `"user"` | `"assistant"` | Anthropic requires the list to start with a `"user"` message and alternate strictly. |
| **Google AI** | `"user"` | `"model"` | The client maps the messages directly to the Gemini API `"contents"` schema. |
| **DeepSeek** | `"user"` | `"assistant"` | The client maps the messages directly, prepending the system prompt if set. |

#### Message Input Structure Example
```php
$messages = [
    ['role' => 'user', 'content' => 'My name is Mihamina.'],
    ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'], // Use 'model' role when calling Google AI directly
    ['role' => 'user', 'content' => 'What is my name?']
];
```

---

## Detailed Output Format

The client supports two distinct output formats:
1. **Raw Decoded JSON Array** (returned by standalone client functions).
2. **Uniform Output Array** (returned by the OOP class methods).

### 1. Standalone Client Functions (Raw Output)
Standalone client functions return the complete, decoded response directly from the provider's REST API.

#### A. OpenAI Response (`call_openai_response`)
Returns a JSON-decoded array matching OpenAI's Responses API:
```php
[
    "id" => "resp_01j2a3b4c5d6e7f8g9h0i1j2k3",
    "object" => "response",
    "created" => 1718912345,
    "model" => "gpt-5.4-mini",
    "output" => [
        [
            "type" => "message",
            "content" => [
                [
                    "type" => "output_text",
                    "text" => "Your name is Mihamina."
                ]
            ]
        ]
    ]
]
```
- **Text Extraction Path**: Loop through `$response['output']`, find item where `type === 'message'`, loop through its `content` items, and concatenate where `type === 'output_text'`.

#### B. Anthropic Response (`call_anthropic_message`)
Returns a JSON-decoded array matching Anthropic's Messages API:
```php
[
    "id" => "msg_013Zva5t95ca8ZgCr5ir561A",
    "type" => "message",
    "role" => "assistant",
    "content" => [
        [
            "type" => "text",
            "text" => "Your name is Mihamina."
        ]
    ],
    "model" => "claude-haiku-4-5",
    "stop_reason" => "end_turn",
    "stop_sequence" => null,
    "usage" => [
        "input_tokens" => 25,
        "output_tokens" => 8
    ]
]
```
- **Text Extraction Path**: Loop through `$response['content']` and concatenate where `type === 'text'`.

#### C. Google AI Response (`call_gemini_interaction`)
Returns a JSON-decoded array matching the Google AI Interactions API using the `steps` schema:
```php
[
    "id" => "interaction_01j2a3b4c5d6e7f8g9h0i1j2k3",
    "steps" => [
        [
            "type" => "model_output",
            "content" => [
                [
                    "type" => "text",
                    "text" => "Your name is Mihamina."
                ]
            ]
        ]
    ]
]
```
- **Text Extraction Path**: Loop through `$response['steps']`, find step where `type === 'model_output'`, loop through its `content` items, and concatenate where `type === 'text'`.

#### D. DeepSeek Response (`call_deepseek_chat`)
Returns a JSON-decoded array matching the standard OpenAI Chat Completions API schema:
```php
[
    "id" => "chatcmpl-765f34bdde23405786ba11cc986d34e1",
    "object" => "chat.completion",
    "created" => 1718912345,
    "model" => "deepseek-chat",
    "choices" => [
        [
            "index" => 0,
            "message" => [
                "role" => "assistant",
                "content" => "Your name is Mihamina."
            ],
            "finish_reason" => "stop"
        ]
    ],
    "usage" => [
        "prompt_tokens" => 25,
        "completion_tokens" => 8,
        "total_tokens" => 33
    ]
]
```
- **Text Extraction Path**: Loop through `$response['choices']` and concatenate the value under `message.content`.

---

### 2. OOP Wrapper Class (Uniform Output)
When using the [AiApiClient](file:///home/mihamina/Projects/php-ai-raw-api-client/AiApiClient.php) class wrapper, all responses are normalized into a unified, provider-agnostic structure:

```php
[
    'text'           => 'Your name is Mihamina.',  // The complete extracted text response
    'interaction_id' => 'resp_01j2a3...',          // Unique response ID (null if missing)
]
```

---

## Standalone Functions Usage

To use the standalone functions directly, import the specific client file and pass the system instructions, message history, model name, and optional API key:

### OpenAI

```php
require_once __DIR__ . '/openai/openai-client.php';

try {
    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina.'],
        ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    $response = call_openai_response(
        instruction: 'You are a helpful assistant.',
        messages: $messages,
        model: 'gpt-5.4-mini'
    );

    // Extract text manually from raw response
    $text = '';
    foreach ($response['output'] as $item) {
        if (($item['type'] ?? '') === 'message') {
            foreach ($item['content'] as $c) {
                if (($c['type'] ?? '') === 'output_text') {
                    $text .= $c['text'] ?? '';
                }
            }
        }
    }
    echo "Model Output: " . $text . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Anthropic

```php
require_once __DIR__ . '/anthropic/anthropic-client.php';

try {
    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina.'],
        ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    $response = call_anthropic_message(
        instruction: 'You are a helpful assistant.',
        messages: $messages,
        model: 'claude-haiku-4-5'
    );

    // Extract text manually from raw response
    $text = '';
    foreach ($response['content'] as $c) {
        if (($c['type'] ?? '') === 'text') {
            $text .= $c['text'] ?? '';
        }
    }
    echo "Model Output: " . $text . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Google AI (Gemini)

```php
require_once __DIR__ . '/google-ai/google-ai-client.php';

try {
    // Note: Gemini expects the assistant role to be 'model'
    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina.'],
        ['role' => 'model', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    $response = call_gemini_interaction(
        instruction: 'You are a helpful assistant.',
        messages: $messages,
        model: 'gemini-3.5-flash'
    );

    // Extract text manually from raw response
    $text = '';
    foreach ($response['steps'] as $step) {
        if (($step['type'] ?? '') === 'model_output') {
            foreach ($step['content'] as $c) {
                if (($c['type'] ?? '') === 'text') {
                    $text .= $c['text'] ?? '';
                }
            }
        }
    }
    echo "Model Output: " . $text . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### DeepSeek

```php
require_once __DIR__ . '/deepseek/deepseek-client.php';

try {
    $messages = [
        ['role' => 'user', 'content' => 'My name is Mihamina.'],
        ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
        ['role' => 'user', 'content' => 'What is my name?']
    ];

    $response = call_deepseek_chat(
        instruction: 'You are a helpful assistant.',
        messages: $messages,
        model: 'deepseek-chat'
    );

    // Extract text manually from raw response
    $text = '';
    foreach ($response['choices'] as $choice) {
        if (isset($choice['message']['content'])) {
            $text .= $choice['message']['content'];
        }
    }
    echo "Model Output: " . $text . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## OOP Wrapper Usage (`AiApiClient`)

The `AiApiClient` class provides a unified layer that abstracts raw payloads and standardizes the output schema into a clean `['text' => string, 'interaction_id' => string|null]` response.

### 1. Autoloading via Composer
If importing this library in a Composer-backed project, autoload classes using:
```php
require_once __DIR__ . '/vendor/autoload.php';

use Rakotomandimby\PhpAiRawApiClient\AiApiClient;
```

### 2. Multi-turn Conversation OOP Example

```php
require_once __DIR__ . '/AiApiClient.php';

use Rakotomandimby\PhpAiRawApiClient\AiApiClient;

// Initialize client with keys (null values will attempt to fall back to environment variables)
$client = new AiApiClient(
    openaiApiKey: getenv('OPENAI_API_KEY') ?: null,
    anthropicApiKey: getenv('ANTHROPIC_API_KEY') ?: null,
    googleAiApiKey: getenv('GOOGLEAI_API_KEY') ?: null,
    deepseekApiKey: getenv('DEEPSEEK_API_KEY') ?: null
);

// Define conversation history
$messages = [
    ['role' => 'user', 'content' => 'My name is Mihamina.'],
    ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
    ['role' => 'user', 'content' => 'What is my name?']
];

try {
    // 1. Google AI (Gemini expects 'model' role for assistant, so we adjust it for Google AI)
    $geminiMessages = $messages;
    $geminiMessages[1]['role'] = 'model';
    $resultGoogle = $client->askGoogleAi(
        systemInstruction: 'You are a concise assistant.',
        messages: $geminiMessages,
        model: 'gemini-3.5-flash'
    );
    echo "Gemini (ID: {$resultGoogle['interaction_id']}): {$resultGoogle['text']}\n";

    // 2. OpenAI
    $resultOpenai = $client->askOpenai(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'gpt-5.4-mini'
    );
    echo "OpenAI (ID: {$resultOpenai['interaction_id']}): {$resultOpenai['text']}\n";

    // 3. Anthropic
    $resultAnthropic = $client->askAnthropic(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'claude-haiku-4-5'
    );
    echo "Anthropic (ID: {$resultAnthropic['interaction_id']}): {$resultAnthropic['text']}\n";

    // 4. DeepSeek
    $resultDeepseek = $client->askDeepSeek(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'deepseek-chat'
    );
    echo "DeepSeek (ID: {$resultDeepseek['interaction_id']}): {$resultDeepseek['text']}\n";

} catch (Exception $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

---

## Error Handling

All client functions and OOP methods throw a standard `Exception` under any of the following failure scenarios:
1. **Missing Authentication**: The API key could not be resolved from the arguments or the environment variables.
2. **cURL Initialization/Execution Failure**: Issues with the socket, DNS resolution, or payload generation during connection.
3. **Invalid API Payload Response**: The provider returned a non-JSON body or the body could not be decoded.
4. **Non-2xx HTTP Status Codes**: The provider returned an API error (e.g. 400 Bad Request, 401 Unauthorized, 429 Too Many Requests, 500 Server Error). The error message returned by the provider's API is extracted and surfaced inside the PHP exception message.

Wrap all calls in standard `try/catch` blocks to capture and handle these exceptions gracefully.
