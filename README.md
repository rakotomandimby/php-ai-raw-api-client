# php-ai-raw-api-client

Small standalone PHP library for calling raw HTTP APIs from major LLM providers without a framework or SDK, supporting multi-turn conversations through a unified OOP interface.

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
- [/openai/openai-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/openai/openai-client.php) — Internal client calling the OpenAI `/v1/responses` API.
- [/anthropic/anthropic-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/anthropic/anthropic-client.php) — Internal client calling the Anthropic Messages API.
- [/google-ai/google-ai-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/google-ai/google-ai-client.php) — Internal client calling the Google Vertex AI Gemini `:generateContent` API.
- [/deepseek/deepseek-client.php](file:///home/mihamina/Projects/php-ai-raw-api-client/deepseek/deepseek-client.php) — Internal client calling the DeepSeek Chat Completions API.

## Requirements

- PHP 8.0+
- PHP cURL extension enabled
- Outbound network access to the provider APIs
- Valid API keys for the providers you want to call

## Provider Configuration

| Provider | OOP Method | Endpoint URL | Environment Variable | Header Credentials |
| --- | --- | --- | --- | --- |
| **OpenAI** | `askOpenai()` | `https://api.openai.com/v1/responses` | `OPENAI_API_KEY` | `Authorization: Bearer <KEY>` |
| **Anthropic** | `askAnthropic()` | `https://api.anthropic.com/v1/messages` | `ANTHROPIC_API_KEY` | `x-api-key: <KEY>`, `anthropic-version: 2023-06-01` |
| **Google Vertex AI (Gemini)** | `askGoogleAi()` | `https://aiplatform.googleapis.com/v1/publishers/google/models/{model}:generateContent?key=<KEY>` | `GOOGLEAI_API_KEY` | API key passed as `key` query parameter |
| **DeepSeek** | `askDeepSeek()` | `https://api.deepseek.com/chat/completions` | `DEEPSEEK_API_KEY` | `Authorization: Bearer <KEY>` |

### Default Request Settings (Hardcoded)

To keep the interface minimal, generation settings are hardcoded inside each client file:
- **OpenAI**: `temperature=0.7`, `top_p=0.95`, `store=false`
- **Anthropic**: `temperature=0.7`, model-specific `max_tokens` (64,000 for Haiku/Sonnet, 128,000 for Opus)
- **Google Vertex AI (Gemini)**: `temperature=0.7`, `topP=0.95`, `maxOutputTokens=64000`
- **DeepSeek**: `temperature=0.7`, `top_p=0.95`, `stream=false`

---

## Detailed Input Format

All class methods accept a uniform input format for constructing conversations.

### 1. The `$systemInstruction` Parameter (System Instruction)

A `string` containing system instructions or guidelines to steer the model's behavior. If empty (`""`), it is ignored or omitted from the request.

How it maps internally per provider:
- **OpenAI**: Mapped to the `"instructions"` field at the root of the payload.
- **Anthropic**: Mapped to the `"system"` field at the root of the payload.
- **Google Vertex AI (Gemini)**: Mapped to the `"systemInstruction"` field at the root of the payload.
- **DeepSeek**: Prepended to the messages array as a message object with the role `"system"`.

### 2. The `$messages` Parameter (Conversation History)

An `array` of associative arrays representing the conversation history. Each message object contains:
- `role` (string): The sender of the message.
- `content` (string): The text content of the message.

#### Role Mappings per Provider

Since different APIs expect different values for roles, the wrapper handles role propagation or mapping under the hood:

| Provider | Supported User Role | Supported Assistant Role | Notes / Constraints |
| --- | --- | --- | --- |
| **OpenAI** | `"user"` | `"assistant"` | Messages are passed directly in the `"input"` payload field. |
| **Anthropic** | `"user"` | `"assistant"` | Anthropic requires the list to start with a `"user"` message and alternate strictly. |
| **Google Vertex AI (Gemini)** | `"user"` | `"model"` | The client maps the messages directly to the Gemini API `"contents"` schema. |
| **DeepSeek** | `"user"` | `"assistant"` | The client maps the messages directly, prepending the system prompt if set. |

#### Message Input Structure Example

```php
$messages = [
    ['role' => 'user', 'content' => 'My name is Mihamina.'],
    ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'], // Use 'model' role when calling Google Vertex AI
    ['role' => 'user', 'content' => 'What is my name?']
];
```

### 3. The `$model` Parameter (Model Name)

A `string` specifying the exact model identifier to use. Each provider has its own model naming convention:

| Provider | Example Model Values |
| --- | --- |
| **OpenAI** | `'gpt-5.4-mini'`, `'gpt-5.4'` |
| **Anthropic** | `'claude-haiku-4-5'`, `'claude-sonnet-4-5'`, `'claude-opus-4'` |
| **Google Vertex AI (Gemini)** | `'gemini-3.5-flash'`, `'gemini-3.5-pro'` |
| **DeepSeek** | `'deepseek-chat'`, `'deepseek-reasoner'` |

---

## Detailed Output Format

All four `AiApiClient` methods (`askOpenai()`, `askAnthropic()`, `askGoogleAi()`, `askDeepSeek()`) return a **uniform associative array** with the following structure:

```php
[
    'text'           => string,       // The complete extracted text response from the model
    'interaction_id' => string|null,  // Unique response ID assigned by the provider (null if unavailable)
]
```

### Output Field Details

#### `'text'` (string)

The full text content generated by the model. This value is extracted and concatenated from the provider's native response schema automatically by the wrapper. You never need to manually parse the raw API response.

- The text is returned as a plain string.
- If the model produced multiple text blocks in a single response (some providers support this), they are concatenated into a single string.
- If no text content was found in the response, this field will be an empty string (`''`).

Internally, the extraction logic varies per provider:
- **OpenAI**: Iterates over `response['output']`, finds items where `type === 'message'`, then iterates over their `content` items and concatenates those where `type === 'output_text'`.
- **Anthropic**: Iterates over `response['content']` and concatenates items where `type === 'text'`.
- **Google Vertex AI (Gemini)**: Iterates over `response['candidates']`, then over `candidate['content']['parts']`, and concatenates each `part['text']`.
- **DeepSeek**: Iterates over `response['choices']` and concatenates the `message.content` value from each choice.

#### `'interaction_id'` (string|null)

A unique identifier for the API response, as assigned by the provider. This is useful for:
- Logging and auditing API calls.
- Referencing a specific response in support requests to the provider.
- Correlating responses in multi-step workflows.

The value is extracted from the raw provider response ID field (`responseId` for Vertex AI, `id` for the other providers). If an ID is unavailable, this will be `null`.

Example values per provider:
- **OpenAI**: `"resp_01j2a3b4c5d6e7f8g9h0i1j2k3"`
- **Anthropic**: `"msg_013Zva5t95ca8ZgCr5ir561A"`
- **Google Vertex AI (Gemini)**: `"ABCD1234EFGH5678"` (from `responseId`, falling back to `id` when present)
- **DeepSeek**: `"chatcmpl-765f34bdde23405786ba11cc986d34e1"`

---

## OOP Wrapper Usage (`AiApiClient`)

### 1. Instantiation

Import and instantiate the `AiApiClient` class. API keys can be passed explicitly or resolved from environment variables automatically when set to `null`:

```php
require_once __DIR__ . '/AiApiClient.php';

use Rakotomandimby\PhpAiRawApiClient\AiApiClient;

// Option A: Pass API keys explicitly
$client = new AiApiClient(
    openaiApiKey: 'sk-your-openai-key',
    anthropicApiKey: 'sk-ant-your-anthropic-key',
    googleAiApiKey: 'AIzaSy-your-google-key',
    deepseekApiKey: 'sk-your-deepseek-key'
);

// Option B: Let keys fall back to environment variables
//   OPENAI_API_KEY, ANTHROPIC_API_KEY, GOOGLEAI_API_KEY, DEEPSEEK_API_KEY
$client = new AiApiClient(
    openaiApiKey: null,
    anthropicApiKey: null,
    googleAiApiKey: null,
    deepseekApiKey: null
);
```

You only need to provide keys for the providers you intend to use. Calling a method for a provider whose key is missing (both argument and environment variable) will throw an `Exception`.

### 2. Calling a Provider

Each provider has a dedicated method with the same signature:

```php
public function askOpenai(string $systemInstruction, array $messages, string $model): array
public function askAnthropic(string $systemInstruction, array $messages, string $model): array
public function askGoogleAi(string $systemInstruction, array $messages, string $model): array
public function askDeepSeek(string $systemInstruction, array $messages, string $model): array
```

All four methods return the same uniform output array: `['text' => string, 'interaction_id' => string|null]`.

### 3. Complete Multi-Provider Example

```php
require_once __DIR__ . '/AiApiClient.php';

use Rakotomandimby\PhpAiRawApiClient\AiApiClient;

// Initialize client (keys from environment variables)
$client = new AiApiClient(
    openaiApiKey: getenv('OPENAI_API_KEY') ?: null,
    anthropicApiKey: getenv('ANTHROPIC_API_KEY') ?: null,
    googleAiApiKey: getenv('GOOGLEAI_API_KEY') ?: null,
    deepseekApiKey: getenv('DEEPSEEK_API_KEY') ?: null
);

// Define conversation history (provider-agnostic format)
$messages = [
    ['role' => 'user', 'content' => 'My name is Mihamina.'],
    ['role' => 'assistant', 'content' => 'Hello Mihamina! How can I help you today?'],
    ['role' => 'user', 'content' => 'What is my name?']
];

try {
    // --- OpenAI ---
    $resultOpenai = $client->askOpenai(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'gpt-5.4-mini'
    );
    // $resultOpenai = [
    //     'text'           => 'Your name is Mihamina.',
    //     'interaction_id' => 'resp_01j2a3b4c5d6e7f8g9h0i1j2k3'
    // ]
    echo "OpenAI:\n";
    echo "  Response text : {$resultOpenai['text']}\n";
    echo "  Interaction ID: {$resultOpenai['interaction_id']}\n\n";

    // --- Anthropic ---
    $resultAnthropic = $client->askAnthropic(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'claude-haiku-4-5'
    );
    // $resultAnthropic = [
    //     'text'           => 'Your name is Mihamina.',
    //     'interaction_id' => 'msg_013Zva5t95ca8ZgCr5ir561A'
    // ]
    echo "Anthropic:\n";
    echo "  Response text : {$resultAnthropic['text']}\n";
    echo "  Interaction ID: {$resultAnthropic['interaction_id']}\n\n";

    // --- Google Vertex AI (Gemini) ---
    // Important: Vertex AI expects 'model' as the assistant role instead of 'assistant'
    $geminiMessages = $messages;
    $geminiMessages[1]['role'] = 'model';
    $resultGoogle = $client->askGoogleAi(
        systemInstruction: 'You are a concise assistant.',
        messages: $geminiMessages,
        model: 'gemini-3.5-flash'
    );
    // $resultGoogle = [
    //     'text'           => 'Your name is Mihamina.',
    //     'interaction_id' => 'ABCD1234EFGH5678'
    // ]
    echo "Google Vertex AI:\n";
    echo "  Response text : {$resultGoogle['text']}\n";
    echo "  Interaction ID: {$resultGoogle['interaction_id']}\n\n";

    // --- DeepSeek ---
    $resultDeepseek = $client->askDeepSeek(
        systemInstruction: 'You are a concise assistant.',
        messages: $messages,
        model: 'deepseek-chat'
    );
    // $resultDeepseek = [
    //     'text'           => 'Your name is Mihamina.',
    //     'interaction_id' => 'chatcmpl-765f34bdde23405786ba11cc986d34e1'
    // ]
    echo "DeepSeek:\n";
    echo "  Response text : {$resultDeepseek['text']}\n";
    echo "  Interaction ID: {$resultDeepseek['interaction_id']}\n\n";

} catch (Exception $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
```

### 4. Working with the Output

#### Accessing the text response

```php
$result = $client->askOpenai(
    systemInstruction: 'Translate to French.',
    messages: [['role' => 'user', 'content' => 'Hello, how are you?']],
    model: 'gpt-5.4-mini'
);

// The 'text' key always contains the model's generated text as a plain string
$generatedText = $result['text'];
// e.g. "Bonjour, comment allez-vous ?"

echo $generatedText;
```

#### Using the interaction ID for logging

```php
$result = $client->askAnthropic(
    systemInstruction: 'You are a helpful assistant.',
    messages: [['role' => 'user', 'content' => 'Explain PHP closures.']],
    model: 'claude-haiku-4-5'
);

// Log the interaction for auditing or debugging
$logEntry = sprintf(
    "[%s] Provider: Anthropic | ID: %s | Response length: %d chars\n",
    date('Y-m-d H:i:s'),
    $result['interaction_id'] ?? 'N/A',
    strlen($result['text'])
);
file_put_contents('api_calls.log', $logEntry, FILE_APPEND);
```

#### Building a multi-turn conversation loop

```php
$client = new AiApiClient(openaiApiKey: null);
$history = [];
$systemPrompt = 'You are a helpful coding assistant.';

// Simulate a multi-turn conversation
$userQuestions = [
    'What is a PHP generator?',
    'Can you give me a simple example?',
    'How does it differ from returning an array?'
];

foreach ($userQuestions as $question) {
    // Append user message to history
    $history[] = ['role' => 'user', 'content' => $question];

    // Send conversation to the API
    $result = $client->askOpenai(
        systemInstruction: $systemPrompt,
        messages: $history,
        model: 'gpt-5.4-mini'
    );

    // Append assistant response to history for the next turn
    $history[] = ['role' => 'assistant', 'content' => $result['text']];

    echo "User: {$question}\n";
    echo "Assistant: {$result['text']}\n";
    echo "--- (ID: {$result['interaction_id']})\n\n";
}
```

---

## Error Handling

All OOP methods throw a standard `Exception` under any of the following failure scenarios:
1. **Missing Authentication**: The API key could not be resolved from the arguments or the environment variables.
2. **cURL Initialization/Execution Failure**: Issues with the socket, DNS resolution, or payload generation during connection.
3. **Invalid API Payload Response**: The provider returned a non-JSON body or the body could not be decoded.
4. **Non-2xx HTTP Status Codes**: The provider returned an API error (e.g. 400 Bad Request, 401 Unauthorized, 429 Too Many Requests, 500 Server Error). The error message returned by the provider's API is extracted and surfaced inside the PHP exception message.

Wrap all calls in standard `try/catch` blocks to capture and handle these exceptions gracefully.
