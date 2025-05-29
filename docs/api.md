# API Reference

## LangChain Facade

The main interface for interacting with multiple AI providers and advanced capabilities.

### Core Methods

#### `generateText(string $prompt, array $params = [], string $provider = null): array`

Generates text using the specified AI provider.

**Parameters:**
- `$prompt` (string): The input prompt for text generation
- `$params` (array): Optional parameters to customize the request
- `$provider` (string): AI provider to use (openai, claude, llama, deepseek)

**Available Parameters:**
- `model` (string): The AI model to use (provider-specific)
- `temperature` (float): Controls randomness (0.0 to 2.0, default: 0.7)
- `max_tokens` (int): Maximum tokens to generate (default: varies by provider)
- `top_p` (float): Nucleus sampling parameter (default: 1.0)
- `frequency_penalty` (float): Frequency penalty (default: 0.0)
- `presence_penalty` (float): Presence penalty (default: 0.0)

#### `translateText(string $text, string|array $targetLanguage, string $provider = null): array`

Translates text to one or more target languages.

**Parameters:**
- `$text` (string): Text to translate
- `$targetLanguage` (string|array): Target language(s)
- `$provider` (string): AI provider to use

#### `generateCode(string $description, string $language, string $provider = null): array`

Generates code based on description.

**Parameters:**
- `$description` (string): Description of the code to generate
- `$language` (string): Programming language
- `$provider` (string): AI provider to use

#### `explainCode(string $code, string $language, string $provider = null): array`

Explains existing code.

**Parameters:**
- `$code` (string): Code to explain
- `$language` (string): Programming language
- `$provider` (string): AI provider to use

#### `actAsAgent(string $role, string $task, array $context = [], string $provider = null): array`

Creates a specialized AI agent with a specific role.

**Parameters:**
- `$role` (string): Agent role/persona
- `$task` (string): Task for the agent
- `$context` (array): Additional context
- `$provider` (string): AI provider to use

#### `summarizeText(string $text, array $options = [], string $provider = null): array`

Summarizes long text.

**Parameters:**
- `$text` (string): Text to summarize
- `$options` (array): Summarization options (max_length, style)
- `$provider` (string): AI provider to use

### Provider Management

#### `setDefaultProvider(string $provider): void`

Sets the default AI provider.

#### `getDefaultProvider(): string`

Gets the current default provider.

#### `getAvailableProviders(): array`

Returns list of available providers.

#### `getProvider(string $provider): AbstractProvider`

Gets a specific provider instance.

#### `registerProvider(string $name, string $providerClass): LangChainManager`

Registers a custom provider class.

**Parameters:**
- `$name` (string): The name to register the provider under
- `$providerClass` (string): The fully qualified class name that extends AbstractProvider

**Returns:** LangChainManager instance for method chaining

**Example:**
```php
LangChain::registerProvider('custom-ai', CustomAIProvider::class);
```

#### `getCustomProviders(): array`

Gets all registered custom providers.

**Returns:** Array of custom provider names and their class names

**Example:**
```php
$customProviders = LangChain::getCustomProviders();
// Returns: ['custom-ai' => 'App\Providers\CustomAIProvider']
```

### Provider-Specific Methods

#### DeepSeek Provider

##### `solveMath(string $problem): array`

Solves mathematical problems.

##### `performReasoning(string $question, array $context = []): array`

Performs complex reasoning tasks.

**Return Value:**
Returns an array with the following structure:

```php
[
    'success' => bool,
    'text' => string,      // Generated text (if successful)
    'usage' => array,      // Token usage statistics (if successful)
    'error' => string      // Error message (if failed)
]
```

**Example:**

```php
use LangChainLaravel\Facades\LangChain;

$response = LangChain::generateText('Explain Laravel middleware', [
    'model' => 'text-davinci-003',
    'temperature' => 0.5,
    'max_tokens' => 500
]);

if ($response['success']) {
    echo $response['text'];
    echo "Tokens used: " . $response['usage']['total_tokens'];
} else {
    echo "Error: " . $response['error'];
}
```

## LangChainManager Class

The core manager class that handles OpenAI client interactions.

### Methods

#### `openAi(): OpenAIClient`

Returns the configured OpenAI client instance.

**Return Value:**
- `OpenAIClient`: The OpenAI client instance

**Throws:**
- `RuntimeException`: If the OpenAI API key is not configured

#### `generateText(string $prompt, array $params = []): array`

Same as the facade method, but called directly on the manager instance.

## Configuration

The package configuration is stored in `config/langchain.php`:

```php
return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],
    'cache' => [
        'enabled' => env('LANGCHAIN_CACHE_ENABLED', true),
        'ttl' => env('LANGCHAIN_CACHE_TTL', 3600)
    ]
];
```

### Configuration Options

- `openai.api_key`: Your OpenAI API key
- `cache.enabled`: Whether to enable response caching
- `cache.ttl`: Cache time-to-live in seconds

## Error Handling

The package handles errors gracefully and returns structured error responses:

```php
$response = LangChain::generateText('Invalid prompt with special chars');

if (!$response['success']) {
    // Handle the error
    Log::error('LangChain error: ' . $response['error']);
    return response()->json(['error' => 'Failed to generate text'], 500);
}
```

## Usage Statistics

The package returns token usage statistics for monitoring and billing:

```php
$response = LangChain::generateText('Hello world');

if ($response['success']) {
    $usage = $response['usage'];
    echo "Prompt tokens: " . $usage['prompt_tokens'];
    echo "Completion tokens: " . $usage['completion_tokens'];
    echo "Total tokens: " . $usage['total_tokens'];
}
```