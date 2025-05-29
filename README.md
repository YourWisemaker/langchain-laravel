# LangChain Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/langchain-laravel/langchain.svg?style=flat-square)](https://packagist.org/packages/langchain-laravel/langchain)
[![Total Downloads](https://img.shields.io/packagist/dt/langchain-laravel/langchain.svg?style=flat-square)](https://packagist.org/packages/langchain-laravel/langchain)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/YourWisemaker/langchain-laravel/ci.yml?branch=main&label=tests&style=flat-square)](https://github.com/YourWisemaker/langchain-laravel/actions?query=workflow%3Aci+branch%3Amain)
[![License](https://img.shields.io/packagist/l/langchain-laravel/langchain.svg?style=flat-square)](https://packagist.org/packages/langchain-laravel/langchain)

A powerful Laravel package that integrates multiple AI providers (OpenAI, Claude, Llama, DeepSeek) with advanced capabilities including text generation, translation, code analysis, AI agents, and mathematical reasoning.

## ✨ Features

### Core AI Capabilities
- **Multi-Provider Support**: OpenAI, Claude (Anthropic), Llama, and DeepSeek models
- **Enhanced AI Capabilities**: Text generation, translation, code generation/analysis, AI agents, summarization
- **Advanced Reasoning**: Mathematical problem solving and complex reasoning (DeepSeek)
- **Multi-Language Support**: Built-in translation and language-specific responses
- **Code Intelligence**: Generate, analyze, and explain code in any programming language

### Developer Experience
- **Dynamic Provider Switching**: Change AI providers on the fly
- **Unified API**: Consistent interface across all providers and capabilities
- **Custom Provider Support**: Register and use your own AI providers
- **Model Aliases**: Use friendly names for complex model identifiers
- **Fallback Strategy**: Automatic failover between providers
- **AI Agent Framework**: Create specialized AI agents with roles and context

### Production Ready
- **Configurable**: Extensive configuration options via environment variables
- **Usage Tracking**: Monitor API usage and costs
- **Error Handling**: Comprehensive error handling and logging
- **Caching**: Built-in response caching for improved performance
- **Testing**: Comprehensive test suite with 95%+ coverage
- **Documentation**: Extensive documentation and examples

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer

## Installation

You can install the package via Composer:

```bash
composer require langchain-laravel/langchain
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=langchain-config
```

Add your AI provider API keys to your `.env` file:

```env
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here

# Claude Configuration (optional)
CLAUDE_API_KEY=your_claude_api_key_here

# Llama Configuration (optional)
LLAMA_API_KEY=your_llama_api_key_here

# DeepSeek Configuration (optional)
DEEPSEEK_API_KEY=your_deepseek_api_key_here

# Set default provider
LANGCHAIN_DEFAULT_PROVIDER=openai
```

## Quick Start

### Basic Usage (Default Provider)

```php
use LangChainLaravel\Facades\LangChain;

// Simple text generation using default provider
$result = LangChain::generateText('Write a short poem about Laravel');

if ($result['success']) {
    echo $result['text'];
    echo "Tokens used: " . $result['usage']['total_tokens'];
} else {
    echo "Error: " . $result['error'];
}
```

### Multi-Provider Usage

```php
// Use OpenAI specifically
$openaiResult = LangChain::openai('Explain Laravel routing', [
    'model' => 'gpt-4',
    'max_tokens' => 200
]);

// Use Claude specifically
$claudeResult = LangChain::claude('Write a technical blog post intro', [
    'model' => 'claude-3-sonnet-20240229',
    'max_tokens' => 300
]);

// Use Llama specifically
$llamaResult = LangChain::llama('Generate PHP code for user authentication', [
    'model' => 'meta-llama/Llama-2-70b-chat-hf',
    'max_tokens' => 400
]);
```

### Dynamic Provider Selection

```php
// Switch provider at runtime
$provider = env('AI_PROVIDER', 'openai');
$result = LangChain::generateText('Create a README template', [], $provider);

// Set default provider
LangChain::setDefaultProvider('claude');
$result = LangChain::generateText('Now using Claude as default');
```

### Provider Management

```php
// Get available providers
$providers = LangChain::getAvailableProviders();
// Returns: ['openai', 'claude', 'llama', 'deepseek']

// Check if provider is valid
if (LangChain::isValidProvider('claude')) {
    $result = LangChain::claude('Hello from Claude!');
}

// Register a custom provider
LangChain::registerProvider('custom-ai', CustomAIProvider::class);

// Get current default provider
$current = LangChain::getDefaultProvider();
```

## Usage Examples

### Basic Text Generation

```php
use LangChainLaravel\Facades\LangChain;

// Using default provider
$result = LangChain::generateText('Explain quantum computing');

// Using specific providers
$result = LangChain::openai('Write a story about AI');
$result = LangChain::claude('Analyze this business proposal');
$result = LangChain::llama('Generate creative content');
$result = LangChain::deepseek('Solve this mathematical problem');
```

### Enhanced AI Capabilities

```php
// Multi-language translation
$translation = LangChain::translateText(
    'Hello, how are you?', 
    'Spanish', 
    'English'
);
// Result: 'Hola, ¿cómo estás?'

// Code generation
$code = LangChain::generateCode(
    'Create a PHP function to validate email addresses',
    'php'
);

// Code analysis and explanation
$explanation = LangChain::explainCode(
    'function fibonacci(n) { return n <= 1 ? n : fibonacci(n-1) + fibonacci(n-2); }',
    'javascript'
);

// Text summarization
$summary = LangChain::summarizeText($longArticle, 200);
```

### AI Agent Framework

```php
// Create specialized AI agents
$techConsultant = LangChain::actAsAgent(
    'Senior Software Architect',
    'Review this system design and suggest improvements',
    [
        'system' => 'E-commerce platform',
        'current_load' => '10k users/day',
        'target_load' => '100k users/day'
    ]
);

$marketingExpert = LangChain::actAsAgent(
    'Digital Marketing Strategist',
    'Create a social media campaign for our new app',
    [
        'budget' => '$5000',
        'target_audience' => 'millennials',
        'timeline' => '3 months'
    ]
);
```

### Advanced DeepSeek Capabilities

```php
// Mathematical problem solving
$mathSolution = LangChain::getProvider('deepseek')->solveMath(
    'If a train travels 120 km in 2 hours, then 180 km in 3 hours, what is the average speed?'
);

// Complex reasoning
$reasoning = LangChain::getProvider('deepseek')->performReasoning(
    'Should we invest in renewable energy?',
    [
        'budget' => '$1M',
        'timeline' => '5 years',
        'current_energy_costs' => '$50k/month'
    ]
);
```

### Provider Management

```php
// Set default provider
LangChain::setDefaultProvider('deepseek');

// Register custom providers
LangChain::registerProvider('custom-ai', CustomAIProvider::class);
LangChain::registerProvider('another-ai', AnotherCustomProvider::class);

// Get available providers (includes custom ones)
$providers = LangChain::getAvailableProviders();
// Returns: ['openai', 'claude', 'llama', 'deepseek', 'custom-ai', 'another-ai']

// Check provider capabilities
$provider = LangChain::getProvider('deepseek');
$canSolveMath = $provider->supportsCapability('math_solving'); // true
$capabilities = $provider->getSupportedCapabilitiesList();

// Use custom provider
$result = LangChain::generateText('Hello from custom AI!', [], 'custom-ai');

// Fallback strategy with custom providers
$preferredProviders = ['custom-ai', 'deepseek', 'claude', 'openai'];
foreach ($preferredProviders as $providerName) {
    $result = LangChain::generateText('Complex reasoning task', [], $providerName);
    if ($result['success']) {
        break; // Use first successful provider
    }
}
```

### Advanced Usage with Parameters

```php
$response = LangChain::generateText('Explain Laravel middleware', [
    'model' => 'text-davinci-003',
    'temperature' => 0.5,
    'max_tokens' => 500
]);
```

## Configuration

The configuration file `config/langchain.php` allows you to customize:
 
- **Provider Settings**: API keys, base URLs, and default models for each AI provider
- **Cache Configuration**: Response caching settings for improved performance
- **Default Parameters**: Temperature, max tokens, and other model parameters
- **Model Aliases**: Friendly names for complex model identifiers
- **Request Settings**: Timeouts, retry logic, and error handling

For detailed configuration options, see the [Configuration Guide](docs/installation.md).

## Custom Providers

LangChain Laravel supports registering custom AI providers, allowing you to integrate any AI service that has an HTTP API.

### Creating a Custom Provider

1. **Extend the AbstractProvider class**:

```php
use LangChainLaravel\AI\Providers\AbstractProvider;

class CustomAIProvider extends AbstractProvider
{
    public function generateText(string $prompt, array $params = []): array
    {
        // Implement your AI provider's API call
        // Return array with 'success', 'text', 'usage', 'error' keys
    }
    
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model'),
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];
    }
    
    protected function validateConfig(): void
    {
        if (empty($this->getConfig('api_key'))) {
            throw new \RuntimeException('API key is required');
        }
    }
}
```

2. **Register your provider**:

```php
// Register the custom provider
LangChain::registerProvider('my-ai', CustomAIProvider::class);

// Use your custom provider
$result = LangChain::generateText('Hello!', [], 'my-ai');
```

3. **Configure your provider** in `config/langchain.php`:

```php
'providers' => [
    // ... existing providers
    'my-ai' => [
        'api_key' => env('MY_AI_API_KEY'),
        'base_url' => env('MY_AI_BASE_URL'),
        'default_model' => env('MY_AI_MODEL', 'default-model'),
    ],
],
```

For a complete example, see [`examples/CustomProviderExample.php`](examples/CustomProviderExample.php).

## Testing

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer analyse
```

## Documentation

- [Installation Guide](docs/installation.md)
- [Usage Guide](docs/usage-guide.md)
- [API Reference](docs/api.md)
- [Testing Guide](docs/testing-guide.md)

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability within LangChain Laravel, please send an e-mail to the maintainers. All security vulnerabilities will be promptly addressed.

## Credits

- [Wisemaker](https://github.com/YourWisemaker)
- [All Contributors](https://github.com/YourWisemaker/langchain-laravel/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.