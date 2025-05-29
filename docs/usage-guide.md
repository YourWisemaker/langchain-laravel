# Usage Guide

This comprehensive guide covers all the advanced capabilities available in LangChain Laravel, including multi-provider support, enhanced AI features for complex tasks, and creating custom providers.

## Table of Contents

1. [Basic Usage](#basic-usage)
2. [Multi-Provider Support](#multi-provider-support)
3. [Enhanced AI Capabilities](#enhanced-ai-capabilities)
4. [Advanced Features](#advanced-features)
5. [Custom Providers](#custom-providers)
6. [Best Practices](#best-practices)
7. [Error Handling](#error-handling)
8. [Performance Optimization](#performance-optimization)
9. [Real-world Examples](#real-world-examples)

## Basic Usage

### Simple Text Generation

```php
use LangChainLaravel\Facades\LangChain;

// Basic text generation
$response = LangChain::generateText('Write a Laravel tip');

if ($response['success']) {
    echo $response['text'];
} else {
    echo 'Error: ' . $response['error'];
}
```

### Customizing Parameters

```php
$response = LangChain::generateText(
    'Explain Laravel middleware in simple terms',
    [
        'model' => 'text-davinci-003',
        'temperature' => 0.3,  // More focused output
        'max_tokens' => 200,
        'top_p' => 0.9
    ]
);
```

### Parameter Reference

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `model` | string | Provider-specific | AI model to use |
| `temperature` | float | `0.7` | Controls randomness (0.0-2.0) |
| `provider` | string | `default` | AI provider to use (openai, claude, llama, deepseek) |

## Multi-Provider Support

### Using Different Providers

```php
use LangChainLaravel\Facades\LangChain;

// Using OpenAI (default)
$openaiResponse = LangChain::generateText('Explain Laravel', [], 'openai');

// Using Claude
$claudeResponse = LangChain::generateText('Explain Laravel', [], 'claude');

// Using Llama
$llamaResponse = LangChain::generateText('Explain Laravel', [], 'llama');

// Using DeepSeek
$deepseekResponse = LangChain::generateText('Explain Laravel', [], 'deepseek');
```

### Dynamic Provider Switching

```php
// Set default provider
LangChain::setDefaultProvider('claude');

// Get current default provider
$currentProvider = LangChain::getDefaultProvider();

// Get available providers
$providers = LangChain::getAvailableProviders();
// Returns: ['openai', 'claude', 'llama', 'deepseek']
```

### Provider-Specific Features

```php
// Get provider instance
$provider = LangChain::getProvider('deepseek');

// Check capabilities
$canSolveMath = $provider->supportsCapability('math_solving');
$capabilities = $provider->getSupportedCapabilitiesList();

// Use provider-specific methods
if ($provider->supportsCapability('math_solving')) {
    $mathResult = $provider->solveMath('What is 15% of 240?');
}
```

## Enhanced AI Capabilities

### Text Translation

```php
// Translate text
$translation = LangChain::translateText(
    'Hello, how are you?',
    'Spanish'
);

// Multi-language translation
$translations = LangChain::translateText(
    'Welcome to our application',
    ['Spanish', 'French', 'German']
);
```

### Code Generation and Analysis

```php
// Generate code
$code = LangChain::generateCode(
    'Create a Laravel middleware for API rate limiting',
    'PHP'
);

// Explain existing code
$explanation = LangChain::explainCode(
    'public function handle($request, Closure $next) { return $next($request); }',
    'PHP'
);
```

### AI Agents

```php
// Create specialized AI agent
$response = LangChain::actAsAgent(
    'Senior Laravel Developer',
    'Review this code for security issues',
    ['code' => $codeToReview]
);

// Marketing agent
$campaign = LangChain::actAsAgent(
    'Digital Marketing Strategist',
    'Create a social media strategy',
    ['budget' => '$5000', 'target' => 'developers']
);
```

### Text Summarization

```php
// Summarize long text
$summary = LangChain::summarizeText(
    $longArticle,
    ['max_length' => 200, 'style' => 'bullet_points']
);
```

### Advanced DeepSeek Capabilities

```php
// Mathematical problem solving
$deepseek = LangChain::getProvider('deepseek');
$mathSolution = $deepseek->solveMath(
    'If a train travels 120 km in 2 hours, what is its average speed?'
);

// Complex reasoning
$reasoning = $deepseek->performReasoning(
    'Should we migrate from MySQL to PostgreSQL?',
    [
        'current_db_size' => '500GB',
        'read_write_ratio' => '80:20',
        'team_expertise' => 'MySQL'
    ]
);
```
| `max_tokens` | int | `256` | Maximum tokens to generate |
| `top_p` | float | `1.0` | Nucleus sampling parameter |
| `frequency_penalty` | float | `0.0` | Reduces repetition |
| `presence_penalty` | float | `0.0` | Encourages new topics |

## Advanced Features

### Content Summarization

```php
class ContentSummarizer
{
    public function summarize(string $content, int $maxWords = 100): ?string
    {
        $prompt = "Summarize the following content in approximately {$maxWords} words:\n\n{$content}";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => intval($maxWords * 1.5) // Rough estimation
        ]);
        
        return $response['success'] ? trim($response['text']) : null;
    }
}
```

### Multi-step Processing

```php
class ContentProcessor
{
    public function processArticle(string $rawContent): array
    {
        // Step 1: Clean and format
        $cleanPrompt = "Clean and format this text for better readability:\n\n{$rawContent}";
        $cleanResponse = LangChain::generateText($cleanPrompt, ['temperature' => 0.2]);
        
        if (!$cleanResponse['success']) {
            return ['error' => 'Failed to clean content'];
        }
        
        $cleanContent = $cleanResponse['text'];
        
        // Step 2: Generate summary
        $summaryPrompt = "Create a brief summary of this article:\n\n{$cleanContent}";
        $summaryResponse = LangChain::generateText($summaryPrompt, ['temperature' => 0.3]);
        
        // Step 3: Extract key points
        $keyPointsPrompt = "List the main key points from this article:\n\n{$cleanContent}";
        $keyPointsResponse = LangChain::generateText($keyPointsPrompt, ['temperature' => 0.2]);
        
        return [
            'original' => $rawContent,
            'cleaned' => $cleanContent,
            'summary' => $summaryResponse['success'] ? $summaryResponse['text'] : null,
            'key_points' => $keyPointsResponse['success'] ? $keyPointsResponse['text'] : null,
            'total_tokens' => (
                $cleanResponse['usage']['total_tokens'] +
                ($summaryResponse['usage']['total_tokens'] ?? 0) +
                ($keyPointsResponse['usage']['total_tokens'] ?? 0)
            )
        ];
    }
}
```

### Caching for Performance

```php
use Illuminate\Support\Facades\Cache;

class CachedLangChain
{
    public static function generateWithCache(string $prompt, array $params = [], int $ttl = 3600): array
    {
        $cacheKey = 'langchain_' . md5($prompt . serialize($params));
        
        return Cache::remember($cacheKey, $ttl, function () use ($prompt, $params) {
            return LangChain::generateText($prompt, $params);
        });
    }
}
```

## Best Practices

### 1. Prompt Engineering

```php
// ❌ Vague prompt
$response = LangChain::generateText('Write about Laravel');

// ✅ Specific, well-structured prompt
$response = LangChain::generateText(
    'Write a beginner-friendly explanation of Laravel Eloquent ORM. ' .
    'Include: what it is, why it\'s useful, and a simple example. ' .
    'Keep it under 200 words.'
);
```

### 2. Temperature Guidelines

```php
// For factual content (documentation, explanations)
$params = ['temperature' => 0.1];

// For balanced content (articles, tutorials)
$params = ['temperature' => 0.5];

// For creative content (stories, marketing copy)
$params = ['temperature' => 0.9];
```

### 3. Token Management

```php
class TokenManager
{
    public function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters
        return intval(strlen($text) / 4);
    }
    
    public function optimizeForTokens(string $prompt, int $maxResponseTokens = 500): array
    {
        $promptTokens = $this->estimateTokens($prompt);
        $maxPromptTokens = 4000 - $maxResponseTokens; // Leave room for response
        
        if ($promptTokens > $maxPromptTokens) {
            // Truncate prompt if too long
            $maxChars = $maxPromptTokens * 4;
            $prompt = substr($prompt, 0, $maxChars) . '...';
        }
        
        return [
            'prompt' => $prompt,
            'max_tokens' => $maxResponseTokens
        ];
    }
}
```

## Error Handling

### Comprehensive Error Handling

```php
class RobustLangChain
{
    public function generateWithRetry(string $prompt, array $params = [], int $maxRetries = 3): array
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $response = LangChain::generateText($prompt, $params);
                
                if ($response['success']) {
                    return $response;
                }
                
                // Log the error
                Log::warning('LangChain generation failed', [
                    'attempt' => $attempt + 1,
                    'error' => $response['error'],
                    'prompt_length' => strlen($prompt)
                ]);
                
            } catch (\Exception $e) {
                Log::error('LangChain exception', [
                    'attempt' => $attempt + 1,
                    'exception' => $e->getMessage()
                ]);
            }
            
            $attempt++;
            
            // Exponential backoff
            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt));
            }
        }
        
        return [
            'success' => false,
            'error' => 'Failed after ' . $maxRetries . ' attempts'
        ];
    }
}
```

## Performance Optimization

### Batch Processing

```php
class BatchProcessor
{
    public function processBatch(array $prompts, array $defaultParams = []): array
    {
        $results = [];
        $totalTokens = 0;
        
        foreach ($prompts as $index => $prompt) {
            $response = LangChain::generateText($prompt, $defaultParams);
            
            $results[$index] = $response;
            
            if ($response['success']) {
                $totalTokens += $response['usage']['total_tokens'];
            }
            
            // Rate limiting - respect API limits
            usleep(100000); // 100ms delay between requests
        }
        
        return [
            'results' => $results,
            'total_tokens' => $totalTokens,
            'success_rate' => count(array_filter($results, fn($r) => $r['success'])) / count($results)
        ];
    }
}
```

## Real-world Examples

### Blog Post Generator

```php
class BlogPostGenerator
{
    public function generatePost(string $topic, string $targetAudience = 'general'): array
    {
        // Generate outline
        $outlinePrompt = "Create a blog post outline for '{$topic}' targeting {$targetAudience} audience. Include 5-7 main sections.";
        $outlineResponse = LangChain::generateText($outlinePrompt, ['temperature' => 0.4]);
        
        if (!$outlineResponse['success']) {
            return ['error' => 'Failed to generate outline'];
        }
        
        // Generate introduction
        $introPrompt = "Write an engaging introduction for a blog post about '{$topic}' for {$targetAudience}. Make it hook the reader.";
        $introResponse = LangChain::generateText($introPrompt, ['temperature' => 0.7, 'max_tokens' => 200]);
        
        // Generate conclusion
        $conclusionPrompt = "Write a compelling conclusion for a blog post about '{$topic}'. Include a call-to-action.";
        $conclusionResponse = LangChain::generateText($conclusionPrompt, ['temperature' => 0.6, 'max_tokens' => 150]);
        
        return [
            'topic' => $topic,
            'target_audience' => $targetAudience,
            'outline' => $outlineResponse['text'],
            'introduction' => $introResponse['success'] ? $introResponse['text'] : null,
            'conclusion' => $conclusionResponse['success'] ? $conclusionResponse['text'] : null,
            'total_tokens' => (
                $outlineResponse['usage']['total_tokens'] +
                ($introResponse['usage']['total_tokens'] ?? 0) +
                ($conclusionResponse['usage']['total_tokens'] ?? 0)
            )
        ];
    }
}
```

### Customer Support Assistant

```php
class SupportAssistant
{
    public function generateResponse(string $customerQuery, array $context = []): array
    {
        $contextString = empty($context) ? '' : "Context: " . implode(', ', $context) . "\n\n";
        
        $prompt = $contextString . 
            "Customer Query: {$customerQuery}\n\n" .
            "Provide a helpful, professional response. Be empathetic and solution-focused.";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => 300
        ]);
        
        if ($response['success']) {
            // Log for quality assurance
            Log::info('Support response generated', [
                'query_length' => strlen($customerQuery),
                'response_length' => strlen($response['text']),
                'tokens_used' => $response['usage']['total_tokens']
            ]);
        }
        
        return $response;
    }
}
```

### Code Documentation Generator

```php
class CodeDocumentationGenerator
{
    public function documentFunction(string $functionCode): array
    {
        $prompt = "Generate comprehensive documentation for this PHP function. Include description, parameters, return value, and usage example:\n\n```php\n{$functionCode}\n```";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.2,
            'max_tokens' => 400
        ]);
        
        return [
            'original_code' => $functionCode,
            'documentation' => $response['success'] ? $response['text'] : null,
            'success' => $response['success'],
            'error' => $response['error'] ?? null
        ];
    }
}
```

## Custom Providers

LangChain Laravel supports creating and registering custom AI providers to extend functionality beyond the built-in providers (OpenAI, Claude, Llama, DeepSeek).

### Creating Custom Providers

To create a custom provider, extend the `AbstractProvider` class and implement the required methods:

```php
<?php

namespace App\Providers;

use LangChainLaravel\AI\Providers\AbstractProvider;
use Illuminate\Support\Facades\Http;

class CustomAIProvider extends AbstractProvider
{
    /**
     * Generate text using the custom AI provider
     */
    public function generateText(string $prompt, array $params = []): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
                'Content-Type' => 'application/json',
            ])->post($this->getConfig('base_url') . '/generate', [
                'prompt' => $prompt,
                'max_tokens' => $params['max_tokens'] ?? $this->getConfig('max_tokens', 1000),
                'temperature' => $params['temperature'] ?? $this->getConfig('temperature', 0.7),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'text' => $data['text'],
                    'usage' => [
                        'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                        'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                    ],
                ];
            }

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Request failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get default parameters for this provider
     */
    public function getDefaultParams(): array
    {
        return [
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'top_p' => 1.0,
        ];
    }

    /**
     * Get supported capabilities
     */
    public function getSupportedCapabilities(): array
    {
        return [
            'text_generation',
            'translation',
            'code_generation',
            'summarization',
            'custom_capability', // Your custom capability
        ];
    }

    /**
     * Validate provider configuration
     */
    public function validateConfig(): bool
    {
        return !empty($this->getConfig('api_key')) && 
               !empty($this->getConfig('base_url'));
    }

    /**
     * Custom capability method
     */
    public function customCapability(string $input, array $params = []): array
    {
        $prompt = "Perform custom processing on: {$input}";
        return $this->generateText($prompt, $params);
    }
}
```

### Registering Custom Providers

Register your custom provider using the `LangChain` facade:

```php
use LangChainLaravel\Facades\LangChain;
use App\Providers\CustomAIProvider;

// Register the custom provider
LangChain::registerProvider('custom-ai', CustomAIProvider::class);

// Verify registration
$customProviders = LangChain::getCustomProviders();
// Returns: ['custom-ai' => 'App\Providers\CustomAIProvider']
```

### Using Custom Providers

Once registered, use your custom provider like any built-in provider:

```php
// Set as default provider
LangChain::setDefaultProvider('custom-ai');

// Use directly
$response = LangChain::provider('custom-ai')->generateText('Hello, world!');

// Use custom capabilities
$customResponse = LangChain::provider('custom-ai')->customCapability('Process this data');

// Include in fallback strategy
$providers = ['custom-ai', 'openai', 'claude'];
foreach ($providers as $provider) {
    $response = LangChain::provider($provider)->generateText($prompt);
    if ($response['success']) {
        break;
    }
}
```

### Configuration

Add your custom provider configuration to `config/langchain.php`:

```php
'providers' => [
    // ... existing providers
    
    'custom-ai' => [
        'api_key' => env('CUSTOM_AI_API_KEY'),
        'base_url' => env('CUSTOM_AI_BASE_URL', 'https://api.custom-ai.com/v1'),
        'default_model' => env('CUSTOM_AI_MODEL', 'custom-model-v1'),
        'max_tokens' => 1000,
        'temperature' => 0.7,
        'custom_param' => env('CUSTOM_AI_PARAM', 'default_value'),
    ],
],
```

And add the corresponding environment variables to your `.env` file:

```env
CUSTOM_AI_API_KEY=your_api_key_here
CUSTOM_AI_BASE_URL=https://api.custom-ai.com/v1
CUSTOM_AI_MODEL=custom-model-v1
CUSTOM_AI_PARAM=custom_value
```

### Service Provider Registration

For automatic registration, add your custom provider to a service provider:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use LangChainLaravel\Facades\LangChain;
use App\Providers\CustomAIProvider;

class CustomAIServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);
    }
}
```

Then register the service provider in `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\CustomAIServiceProvider::class,
],
```

## Monitoring and Analytics

### Usage Tracking

```php
class UsageTracker
{
    public function trackUsage(array $response, string $feature = 'general'): void
    {
        if ($response['success']) {
            DB::table('langchain_usage')->insert([
                'feature' => $feature,
                'tokens_used' => $response['usage']['total_tokens'],
                'prompt_tokens' => $response['usage']['prompt_tokens'],
                'completion_tokens' => $response['usage']['completion_tokens'],
                'created_at' => now()
            ]);
        }
    }
    
    public function getDailyUsage(): array
    {
        return DB::table('langchain_usage')
            ->selectRaw('DATE(created_at) as date, SUM(tokens_used) as total_tokens, COUNT(*) as requests')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
```

This usage guide provides a comprehensive foundation for working with LangChain Laravel. Remember to always test your implementations and monitor token usage to optimize costs.