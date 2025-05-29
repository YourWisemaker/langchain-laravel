# Installation Guide

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer
- At least one AI provider API key (OpenAI, Claude, Llama, or DeepSeek)

## Step-by-Step Installation

### 1. Install via Composer

```bash
composer require langchain-laravel/langchain
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=langchain-config
```

This will create `config/langchain.php` in your Laravel application.

### 3. Environment Configuration

#### Default Provider Selection

Choose your default AI provider by setting the `LANGCHAIN_DEFAULT_PROVIDER` environment variable:

```env
# Choose: openai, claude, llama, deepseek
LANGCHAIN_DEFAULT_PROVIDER=openai
```

#### OpenAI Configuration

For OpenAI integration, add your API key to your `.env` file:

```env
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_DEFAULT_MODEL=gpt-3.5-turbo
OPENAI_DEFAULT_MAX_TOKENS=1000
OPENAI_DEFAULT_TEMPERATURE=0.7
```

#### Claude (Anthropic) Configuration

For Claude integration, add your Anthropic API key:

```env
CLAUDE_API_KEY=your_claude_api_key_here
CLAUDE_DEFAULT_MODEL=claude-3-sonnet-20240229
CLAUDE_DEFAULT_MAX_TOKENS=1000
CLAUDE_DEFAULT_TEMPERATURE=0.7
```

#### Llama Configuration

For Llama models (via Together AI or similar providers):

```env
LLAMA_API_KEY=your_llama_api_key_here
LLAMA_BASE_URL=https://api.together.xyz/v1
LLAMA_DEFAULT_MODEL=meta-llama/Llama-2-70b-chat-hf
LLAMA_DEFAULT_MAX_TOKENS=1000
LLAMA_DEFAULT_TEMPERATURE=0.7
```

#### DeepSeek Configuration (Optional)

DeepSeek offers advanced reasoning and mathematical problem-solving capabilities:

```env
# DeepSeek API Configuration
DEEPSEEK_API_KEY=your_deepseek_api_key_here
DEEPSEEK_BASE_URL=https://api.deepseek.com
DEEPSEEK_DEFAULT_MODEL=deepseek-chat
DEEPSEEK_DEFAULT_MAX_TOKENS=1000
DEEPSEEK_DEFAULT_TEMPERATURE=0.7
```

**DeepSeek Models:**
- `deepseek-chat`: General conversation and reasoning
- `deepseek-coder`: Specialized for code generation and analysis
- `deepseek-math-7b-instruct`: Optimized for mathematical problem solving

### 4. Cache Configuration (Optional)

Optional cache configuration (applies to all providers):

```env
LANGCHAIN_CACHE_ENABLED=true
LANGCHAIN_CACHE_TTL=3600
LANGCHAIN_CACHE_STORE=redis
LANGCHAIN_CACHE_PREFIX=langchain
```

#### Request Configuration

Configure request timeouts and retry behavior:

```env
LANGCHAIN_REQUEST_TIMEOUT=30
LANGCHAIN_RETRY_ATTEMPTS=3
LANGCHAIN_RETRY_DELAY=1000
```

### 5. Verify Installation

## Verification

To verify the installation, create test routes in your `routes/web.php`:

### Basic Test (Default Provider)

```php
use LangChainLaravel\Facades\LangChain;

Route::get('/test-langchain', function () {
    $result = LangChain::generateText('Hello, how are you?', [
        'max_tokens' => 50,
        'temperature' => 0.7
    ]);
    
    if ($result['success']) {
        return response()->json([
            'message' => 'LangChain is working!',
            'provider' => LangChain::getDefaultProvider(),
            'response' => $result['text'],
            'usage' => $result['usage']
        ]);
    }
    
    return response()->json([
        'error' => $result['error']
    ], 500);
});
```

### Multi-Provider Test

```php
Route::get('/test-providers', function () {
    $prompt = 'Explain Laravel in one sentence.';
    $results = [];
    
    $providers = LangChain::getAvailableProviders();
    
    foreach ($providers as $provider) {
        if (LangChain::isValidProvider($provider)) {
            $result = LangChain::generateText($prompt, [
                'max_tokens' => 50,
                'temperature' => 0.7
            ], $provider);
            
            $results[$provider] = [
                'success' => $result['success'],
                'response' => $result['success'] ? $result['text'] : $result['error']
            ];
        }
    }
    
    return response()->json([
        'message' => 'Multi-provider test completed',
        'default_provider' => LangChain::getDefaultProvider(),
        'results' => $results
    ]);
});
```

### Provider-Specific Tests

```php
// Test OpenAI specifically
Route::get('/test-openai', function () {
    $result = LangChain::openai('Generate a creative story opening.', [
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => 100
    ]);
    
    return response()->json($result);
});

// Test Claude specifically
Route::get('/test-claude', function () {
    $result = LangChain::claude('Explain quantum computing simply.', [
        'model' => 'claude-3-sonnet-20240229',
        'max_tokens' => 100
    ]);
    
    return response()->json($result);
});

// Test Llama specifically
Route::get('/test-llama', function () {
    $result = LangChain::llama('Write a PHP function comment.', [
        'model' => 'meta-llama/Llama-2-70b-chat-hf',
        'max_tokens' => 100
    ]);
    
    return response()->json($result);
});

// Test DeepSeek provider
Route::get('/test-deepseek', function () {
    try {
        $result = LangChain::deepseek('Solve this math problem: What is 15% of 240?');
        return response()->json([
            'provider' => 'deepseek',
            'success' => $result['success'],
            'response' => $result['text'] ?? null,
            'error' => $result['error'] ?? null
        ]);
    } catch (Exception $e) {
        return response()->json([
            'provider' => 'deepseek',
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

// Test enhanced capabilities
Route::get('/test-capabilities', function () {
    try {
        $results = [];
        
        // Test translation
        $translation = LangChain::translateText('Hello world', 'Spanish');
        $results['translation'] = $translation;
        
        // Test code generation
        $code = LangChain::generateCode('Create a function to reverse a string', 'php');
        $results['code_generation'] = $code;
        
        // Test AI agent
        $agent = LangChain::actAsAgent(
            'Technical Writer',
            'Write a brief explanation of APIs',
            ['audience' => 'beginners']
        );
        $results['ai_agent'] = $agent;
        
        // Test provider capabilities
        $provider = LangChain::getProvider();
        $results['capabilities'] = $provider->getSupportedCapabilitiesList();
        
        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});
```

Visit these routes in your browser to test the integration:
- `/test-langchain` - Basic functionality test
- `/test-providers` - Multi-provider availability test
- `/test-openai` - OpenAI specific test
- `/test-claude` - Claude specific test
- `/test-llama` - Llama specific test

## Troubleshooting

### Common Issues

1. **Missing API Key**: Ensure your OpenAI API key is correctly set in the `.env` file
2. **Cache Issues**: Clear Laravel cache with `php artisan cache:clear`
3. **Autoload Issues**: Run `composer dump-autoload`

### Getting Help

If you encounter issues:

1. Check the [examples](examples/) directory
2. Review the [API documentation](api.md)
3. Open an issue on GitHub