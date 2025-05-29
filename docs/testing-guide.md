# Testing Guide

This guide covers testing strategies and best practices for applications using LangChain Laravel.

## Table of Contents

1. [Testing Setup](#testing-setup)
2. [Unit Testing](#unit-testing)
3. [Multi-Provider Testing](#multi-provider-testing)
4. [Feature Testing](#feature-testing)
5. [Integration Testing](#integration-testing)
6. [Mocking Strategies](#mocking-strategies)
7. [Performance Testing](#performance-testing)
8. [Best Practices](#best-practices)

## Testing Setup

### Environment Configuration

Create a dedicated test environment configuration in `phpunit.xml`:

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    
    <!-- Multi-Provider Test Keys -->
    <env name="OPENAI_API_KEY" value="test-openai-key"/>
    <env name="CLAUDE_API_KEY" value="test-claude-key"/>
    <env name="LLAMA_API_KEY" value="test-llama-key"/>
    <env name="DEEPSEEK_API_KEY" value="test-deepseek-key"/>
    
    <!-- Default Provider for Testing -->
    <env name="LANGCHAIN_DEFAULT_PROVIDER" value="openai"/>
    <env name="LANGCHAIN_CACHE_ENABLED" value="false"/>
</php>
```

### Test Database Setup

If you're tracking usage statistics, set up a test database:

```php
// tests/TestCase.php
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;
    
    protected function getPackageProviders($app)
    {
        return [
            \LangChainLaravel\LangChainServiceProvider::class,
        ];
    }
    
    protected function getPackageAliases($app)
    {
        return [
            'LangChain' => \LangChainLaravel\Facades\LangChain::class,
        ];
    }
    
    protected function defineEnvironment($app)
    {
        // Multi-provider configuration
        $app['config']->set('langchain.default_provider', 'openai');
        $app['config']->set('langchain.openai.api_key', 'test-openai-key');
        $app['config']->set('langchain.claude.api_key', 'test-claude-key');
        $app['config']->set('langchain.llama.api_key', 'test-llama-key');
        $app['config']->set('langchain.deepseek.api_key', 'test-deepseek-key');
        $app['config']->set('langchain.cache.enabled', false);
    }
}
```

## Unit Testing

### Testing LangChain Manager

```php
// tests/Unit/LangChainManagerTest.php
use LangChainLaravel\LangChainManager;
use OpenAI\Client;
use OpenAI\Responses\Completions\CreateResponse;
use PHPUnit\Framework\TestCase;
use Mockery;

class LangChainManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    public function test_validates_openai_config()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is required');
        
        new LangChainManager(['openai' => ['api_key' => null]]);
    }
    
    public function test_generates_text_successfully()
    {
        $mockClient = Mockery::mock(Client::class);
        $mockResponse = Mockery::mock(CreateResponse::class);
        
        $mockResponse->shouldReceive('toArray')
            ->andReturn([
                'choices' => [
                    ['text' => 'Generated text response']
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15
                ]
            ]);
        
        $mockClient->shouldReceive('completions->create')
            ->with([
                'model' => 'text-davinci-003',
                'prompt' => 'Test prompt',
                'temperature' => 0.7,
                'max_tokens' => 256
            ])
            ->andReturn($mockResponse);
        
        $manager = new LangChainManager(['openai' => ['api_key' => 'test-key']]);
        
        // Use reflection to inject mock client
        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('openAiClient');
        $property->setAccessible(true);
        $property->setValue($manager, $mockClient);
        
        $result = $manager->generateText('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Generated text response', $result['text']);
        $this->assertEquals(15, $result['usage']['total_tokens']);
    }
    
    public function test_handles_api_errors()
    {
        $mockClient = Mockery::mock(Client::class);
        
        $mockClient->shouldReceive('completions->create')
            ->andThrow(new \Exception('API Error'));
        
        $manager = new LangChainManager(['openai' => ['api_key' => 'test-key']]);
        
        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('openAiClient');
        $property->setAccessible(true);
        $property->setValue($manager, $mockClient);
        
        $result = $manager->generateText('Test prompt');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API Error', $result['error']);
    }
    
    public function test_uses_custom_parameters()
    {
        $mockClient = Mockery::mock(Client::class);
        $mockResponse = Mockery::mock(CreateResponse::class);
        
        $mockResponse->shouldReceive('toArray')
            ->andReturn([
                'choices' => [['text' => 'Custom response']],
                'usage' => ['total_tokens' => 20]
            ]);
        
        $mockClient->shouldReceive('completions->create')
            ->with([
                'model' => 'gpt-3.5-turbo-instruct',
                'prompt' => 'Custom prompt',
                'temperature' => 0.5,
                'max_tokens' => 100,
                'top_p' => 0.9
            ])
            ->andReturn($mockResponse);
        
        $manager = new LangChainManager(['openai' => ['api_key' => 'test-key']]);
        
        $reflection = new \ReflectionClass($manager);
        $property = $reflection->getProperty('openAiClient');
        $property->setAccessible(true);
        $property->setValue($manager, $mockClient);
        
        $result = $manager->generateText('Custom prompt', [
            'model' => 'gpt-3.5-turbo-instruct',
            'temperature' => 0.5,
            'max_tokens' => 100,
            'top_p' => 0.9
        ]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Custom response', $result['text']);
    }
}
```

## Multi-Provider Testing

### Testing Provider Switching

```php
// tests/Feature/MultiProviderTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class MultiProviderTest extends TestCase
{
    public function test_can_switch_between_providers()
    {
        // Test OpenAI
        $response = LangChain::generateText('Test prompt', [], 'openai');
        $this->assertIsArray($response);
        
        // Test Claude
        $response = LangChain::generateText('Test prompt', [], 'claude');
        $this->assertIsArray($response);
        
        // Test DeepSeek
        $response = LangChain::generateText('Test prompt', [], 'deepseek');
        $this->assertIsArray($response);
    }
    
    public function test_provider_capabilities()
    {
        $deepseek = LangChain::getProvider('deepseek');
        
        $this->assertTrue($deepseek->supportsCapability('math_solving'));
        $this->assertTrue($deepseek->supportsCapability('reasoning'));
        
        $capabilities = $deepseek->getSupportedCapabilitiesList();
        $this->assertContains('text_generation', $capabilities);
        $this->assertContains('math_solving', $capabilities);
    }
    
    public function test_enhanced_capabilities()
    {
        // Test translation
        $translation = LangChain::translateText('Hello', 'Spanish');
        $this->assertIsArray($translation);
        
        // Test code generation
        $code = LangChain::generateCode('Create a simple function', 'PHP');
        $this->assertIsArray($code);
        
        // Test AI agent
        $agent = LangChain::actAsAgent('Developer', 'Review code', ['code' => 'test']);
        $this->assertIsArray($agent);
    }
}
```

### Testing Provider Fallback

```php
public function test_provider_fallback_strategy()
{
    $providers = ['deepseek', 'claude', 'openai'];
    $result = null;
    
    foreach ($providers as $provider) {
        try {
            $result = LangChain::generateText('Test prompt', [], $provider);
            if ($result['success']) {
                break;
            }
        } catch (\Exception $e) {
            continue; // Try next provider
        }
    }
    
    $this->assertNotNull($result);
    $this->assertTrue($result['success']);
}
```

### Testing Custom Classes

```php
// tests/Unit/ContentSummarizerTest.php
use App\Services\ContentSummarizer;
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class ContentSummarizerTest extends TestCase
{
    public function test_summarizes_content_successfully()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->with(
                Mockery::on(function ($prompt) {
                    return str_contains($prompt, 'Summarize the following content');
                }),
                ['temperature' => 0.3, 'max_tokens' => 150]
            )
            ->andReturn([
                'success' => true,
                'text' => 'This is a summary of the content.'
            ]);
        
        $summarizer = new ContentSummarizer();
        $result = $summarizer->summarize('Long content to summarize...', 100);
        
        $this->assertEquals('This is a summary of the content.', $result);
    }
    
    public function test_returns_null_on_failure()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'API Error'
            ]);
        
        $summarizer = new ContentSummarizer();
        $result = $summarizer->summarize('Content to summarize...');
        
        $this->assertNull($result);
    }
}
```

## Feature Testing

### Testing HTTP Endpoints

```php
// tests/Feature/ContentGenerationTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class ContentGenerationTest extends TestCase
{
    public function test_generates_content_via_api()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->with('Generate a blog post about Laravel', [])
            ->andReturn([
                'success' => true,
                'text' => 'Laravel is a powerful PHP framework...',
                'usage' => ['total_tokens' => 50]
            ]);
        
        $response = $this->postJson('/api/generate-content', [
            'prompt' => 'Generate a blog post about Laravel'
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'content' => 'Laravel is a powerful PHP framework...',
                'tokens_used' => 50
            ]);
    }
    
    public function test_handles_generation_errors()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'Rate limit exceeded'
            ]);
        
        $response = $this->postJson('/api/generate-content', [
            'prompt' => 'Test prompt'
        ]);
        
        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'error' => 'Rate limit exceeded'
            ]);
    }
    
    public function test_validates_request_data()
    {
        $response = $this->postJson('/api/generate-content', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    }
}
```

### Testing Artisan Commands

```php
// tests/Feature/GenerateContentCommandTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class GenerateContentCommandTest extends TestCase
{
    public function test_command_generates_content()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->with('Write about Laravel testing', [])
            ->andReturn([
                'success' => true,
                'text' => 'Laravel testing is essential...'
            ]);
        
        $this->artisan('langchain:generate', [
            'prompt' => 'Write about Laravel testing'
        ])
        ->expectsOutput('Laravel testing is essential...')
        ->assertExitCode(0);
    }
    
    public function test_command_handles_errors()
    {
        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'API Error'
            ]);
        
        $this->artisan('langchain:generate', [
            'prompt' => 'Test prompt'
        ])
        ->expectsOutput('Error: API Error')
        ->assertExitCode(1);
    }
}
```

## Integration Testing

### Real API Testing

```php
// tests/Integration/OpenAIRealApiTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

/**
 * @group integration
 * @group external-api
 */
class OpenAIRealApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (!env('OPENAI_API_KEY') || env('OPENAI_API_KEY') === 'test-key') {
            $this->markTestSkipped('Real OpenAI API key required for integration tests');
        }
    }
    
    public function test_real_text_generation()
    {
        $response = LangChain::generateText('Say "Hello, World!" in a friendly way.');
        
        $this->assertTrue($response['success']);
        $this->assertNotEmpty($response['text']);
        $this->assertArrayHasKey('usage', $response);
        $this->assertGreaterThan(0, $response['usage']['total_tokens']);
    }
    
    public function test_different_models()
    {
        $models = ['text-davinci-003', 'gpt-3.5-turbo-instruct'];
        
        foreach ($models as $model) {
            $response = LangChain::generateText('Count to 3', [
                'model' => $model,
                'max_tokens' => 50
            ]);
            
            $this->assertTrue($response['success'], "Failed for model: {$model}");
            $this->assertNotEmpty($response['text']);
        }
    }
    
    public function test_parameter_effects()
    {
        // Test low temperature (more deterministic)
        $lowTempResponse = LangChain::generateText('The capital of France is', [
            'temperature' => 0.1,
            'max_tokens' => 10
        ]);
        
        // Test high temperature (more creative)
        $highTempResponse = LangChain::generateText('Write a creative opening line', [
            'temperature' => 0.9,
            'max_tokens' => 20
        ]);
        
        $this->assertTrue($lowTempResponse['success']);
        $this->assertTrue($highTempResponse['success']);
        $this->assertNotEmpty($lowTempResponse['text']);
        $this->assertNotEmpty($highTempResponse['text']);
    }
}
```

## Mocking Strategies

### Facade Mocking

```php
// In your test methods
use LangChainLaravel\Facades\LangChain;

public function test_with_facade_mock()
{
    LangChain::shouldReceive('generateText')
        ->once()
        ->with('Test prompt', [])
        ->andReturn([
            'success' => true,
            'text' => 'Mocked response'
        ]);
    
    // Your test code here
}
```

### Partial Mocking

```php
use LangChainLaravel\LangChainManager;
use Mockery;

public function test_with_partial_mock()
{
    $manager = Mockery::mock(LangChainManager::class)->makePartial();
    
    $manager->shouldReceive('generateText')
        ->with('Test prompt')
        ->andReturn(['success' => true, 'text' => 'Mocked']);
    
    $this->app->instance('langchain', $manager);
    
    // Your test code here
}
```

### HTTP Client Mocking

```php
use Illuminate\Support\Facades\Http;

public function test_with_http_mock()
{
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['text' => 'Mocked API response']],
            'usage' => ['total_tokens' => 25]
        ], 200)
    ]);
    
    // Your test code here
}
```

## Performance Testing

### Load Testing

```php
// tests/Performance/LangChainLoadTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

/**
 * @group performance
 */
class LangChainLoadTest extends TestCase
{
    public function test_concurrent_requests_performance()
    {
        LangChain::shouldReceive('generateText')
            ->times(10)
            ->andReturn([
                'success' => true,
                'text' => 'Response',
                'usage' => ['total_tokens' => 20]
            ]);
        
        $startTime = microtime(true);
        
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = LangChain::generateText("Prompt {$i}");
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Assert that 10 requests complete within reasonable time
        $this->assertLessThan(5.0, $duration, 'Requests took too long');
    }
    
    public function test_memory_usage()
    {
        $initialMemory = memory_get_usage();
        
        LangChain::shouldReceive('generateText')
            ->times(100)
            ->andReturn([
                'success' => true,
                'text' => str_repeat('x', 1000), // 1KB response
                'usage' => ['total_tokens' => 250]
            ]);
        
        for ($i = 0; $i < 100; $i++) {
            LangChain::generateText("Test prompt {$i}");
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Assert memory increase is reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
    }
}
```

### Token Usage Testing

```php
// tests/Performance/TokenUsageTest.php
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

class TokenUsageTest extends TestCase
{
    public function test_token_estimation_accuracy()
    {
        $testCases = [
            'Short prompt' => 50,
            'This is a medium length prompt that should use more tokens' => 150,
            str_repeat('Long prompt ', 50) => 400
        ];
        
        foreach ($testCases as $prompt => $expectedMaxTokens) {
            LangChain::shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => 'Response',
                    'usage' => [
                        'prompt_tokens' => intval(strlen($prompt) / 4),
                        'completion_tokens' => 10,
                        'total_tokens' => intval(strlen($prompt) / 4) + 10
                    ]
                ]);
            
            $response = LangChain::generateText($prompt);
            
            $this->assertLessThan(
                $expectedMaxTokens,
                $response['usage']['total_tokens'],
                "Token usage exceeded expected maximum for prompt: {$prompt}"
            );
        }
    }
}
```

## Best Practices

### 1. Test Organization

```php
// Group related tests
/**
 * @group langchain
 * @group unit
 */
class LangChainUnitTest extends TestCase
{
    // Unit tests here
}

/**
 * @group langchain
 * @group integration
 */
class LangChainIntegrationTest extends TestCase
{
    // Integration tests here
}
```

### 2. Data Providers

```php
public function test_handles_various_prompts()
{
    $this->markTestIncomplete('Use data provider instead');
}

/**
 * @dataProvider promptProvider
 */
public function test_handles_various_prompts_with_provider($prompt, $expectedLength)
{
    LangChain::shouldReceive('generateText')
        ->with($prompt, [])
        ->andReturn([
            'success' => true,
            'text' => str_repeat('x', $expectedLength)
        ]);
    
    $response = LangChain::generateText($prompt);
    
    $this->assertEquals($expectedLength, strlen($response['text']));
}

public function promptProvider()
{
    return [
        'short prompt' => ['Hello', 50],
        'medium prompt' => ['Write a paragraph about Laravel', 200],
        'long prompt' => ['Write a detailed essay about PHP frameworks', 500]
    ];
}
```

### 3. Custom Assertions

```php
// tests/TestCase.php
protected function assertValidLangChainResponse(array $response)
{
    $this->assertIsArray($response);
    $this->assertArrayHasKey('success', $response);
    
    if ($response['success']) {
        $this->assertArrayHasKey('text', $response);
        $this->assertArrayHasKey('usage', $response);
        $this->assertIsString($response['text']);
        $this->assertIsArray($response['usage']);
    } else {
        $this->assertArrayHasKey('error', $response);
        $this->assertIsString($response['error']);
    }
}
```

### 4. Environment-Specific Tests

```php
public function test_requires_production_environment()
{
    if (app()->environment() !== 'production') {
        $this->markTestSkipped('This test only runs in production');
    }
    
    // Production-specific test code
}

public function test_skips_without_api_key()
{
    if (!config('langchain.openai.api_key')) {
        $this->markTestSkipped('OpenAI API key required');
    }
    
    // Test requiring real API key
}
```

### 5. Cleanup and Teardown

```php
protected function tearDown(): void
{
    // Clear any cached responses
    Cache::flush();
    
    // Reset facade mocks
    Mockery::close();
    
    parent::tearDown();
}
```

## Running Tests

### Basic Test Execution

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test groups
vendor/bin/phpunit --group=unit
vendor/bin/phpunit --group=integration
vendor/bin/phpunit --group=performance

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/LangChainManagerTest.php
```

### Continuous Integration

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        laravel: [10.*, 11.*]
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run unit tests
        run: vendor/bin/phpunit --group=unit
        
      - name: Run integration tests
        run: vendor/bin/phpunit --group=integration
        env:
          OPENAI_API_KEY: ${{ secrets.OPENAI_API_KEY }}
```

This comprehensive testing guide covers all aspects of testing your LangChain Laravel implementation, including multi-provider scenarios, enhanced capabilities, and custom providers.

## Custom Provider Testing

### Testing Custom Provider Implementation

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Providers\CustomAIProvider;
use LangChain\Facades\LangChain;
use Illuminate\Support\Facades\Http;

class CustomProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register custom provider for testing
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);
    }

    public function test_custom_provider_registration()
    {
        $customProviders = LangChain::getCustomProviders();
        
        $this->assertArrayHasKey('custom-ai', $customProviders);
        $this->assertEquals(CustomAIProvider::class, $customProviders['custom-ai']);
    }

    public function test_custom_provider_text_generation()
    {
        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Custom AI response',
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 5,
                    'total_tokens' => 15
                ]
            ], 200)
        ]);

        $response = LangChain::provider('custom-ai')->generateText('Test prompt');

        $this->assertTrue($response['success']);
        $this->assertEquals('Custom AI response', $response['text']);
        $this->assertEquals(15, $response['usage']['total_tokens']);
    }

    public function test_custom_provider_capabilities()
    {
        $provider = LangChain::provider('custom-ai');
        $capabilities = $provider->getSupportedCapabilities();

        $this->assertContains('text_generation', $capabilities);
        $this->assertContains('custom_capability', $capabilities);
    }

    public function test_custom_provider_validation()
    {
        config([
            'langchain.providers.custom-ai.api_key' => 'test-key',
            'langchain.providers.custom-ai.base_url' => 'https://api.custom-ai.com'
        ]);

        $provider = LangChain::provider('custom-ai');
        $this->assertTrue($provider->validateConfig());
    }

    public function test_custom_provider_error_handling()
    {
        Http::fake([
            'api.custom-ai.com/*' => Http::response([], 500)
        ]);

        $response = LangChain::provider('custom-ai')->generateText('Test prompt');

        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
    }

    public function test_custom_capability_method()
    {
        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Custom processing result',
                'usage' => ['total_tokens' => 20]
            ], 200)
        ]);

        $response = LangChain::provider('custom-ai')->customCapability('Test input');

        $this->assertTrue($response['success']);
        $this->assertEquals('Custom processing result', $response['text']);
    }
}
```

### Testing Custom Provider Integration

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Providers\CustomAIProvider;
use LangChain\Facades\LangChain;
use Illuminate\Support\Facades\Http;

class CustomProviderIntegrationTest extends TestCase
{
    public function test_custom_provider_in_fallback_strategy()
    {
        // Register custom provider
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);

        // Mock custom provider to fail
        Http::fake([
            'api.custom-ai.com/*' => Http::response([], 500),
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'OpenAI fallback response']
                ]],
                'usage' => ['total_tokens' => 25]
            ], 200)
        ]);

        $providers = ['custom-ai', 'openai'];
        $response = null;

        foreach ($providers as $provider) {
            $response = LangChain::provider($provider)->generateText('Test prompt');
            if ($response['success']) {
                break;
            }
        }

        $this->assertTrue($response['success']);
        $this->assertEquals('OpenAI fallback response', $response['text']);
    }

    public function test_custom_provider_with_enhanced_capabilities()
    {
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);

        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Translated text: Hola mundo',
                'usage' => ['total_tokens' => 15]
            ], 200)
        ]);

        $response = LangChain::provider('custom-ai')->translateText(
            'Hello world',
            'en',
            'es'
        );

        $this->assertTrue($response['success']);
        $this->assertStringContains('Hola mundo', $response['text']);
    }

    public function test_multiple_custom_providers()
    {
        // Register multiple custom providers
        LangChain::registerProvider('custom-ai-1', CustomAIProvider::class);
        LangChain::registerProvider('custom-ai-2', CustomAIProvider::class);

        $customProviders = LangChain::getCustomProviders();

        $this->assertCount(2, $customProviders);
        $this->assertArrayHasKey('custom-ai-1', $customProviders);
        $this->assertArrayHasKey('custom-ai-2', $customProviders);
    }

    public function test_custom_provider_configuration_override()
    {
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);

        // Test with different configurations
        config([
            'langchain.providers.custom-ai.max_tokens' => 2000,
            'langchain.providers.custom-ai.temperature' => 0.9
        ]);

        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Response with custom config',
                'usage' => ['total_tokens' => 30]
            ], 200)
        ]);

        $response = LangChain::provider('custom-ai')->generateText('Test prompt', [
            'max_tokens' => 1500,  // Override config
            'temperature' => 0.5   // Override config
        ]);

        $this->assertTrue($response['success']);
        
        // Verify the request was made with overridden parameters
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return $body['max_tokens'] === 1500 && $body['temperature'] === 0.5;
        });
    }
}
```

### Testing Custom Provider Performance

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Providers\CustomAIProvider;
use LangChain\Facades\LangChain;
use Illuminate\Support\Facades\Http;

class CustomProviderPerformanceTest extends TestCase
{
    public function test_custom_provider_response_time()
    {
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);

        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Performance test response',
                'usage' => ['total_tokens' => 20]
            ], 200)
        ]);

        $startTime = microtime(true);
        
        $response = LangChain::provider('custom-ai')->generateText('Performance test prompt');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertTrue($response['success']);
        $this->assertLessThan(5000, $responseTime, 'Response time should be under 5 seconds');
    }

    public function test_custom_provider_concurrent_requests()
    {
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);

        Http::fake([
            'api.custom-ai.com/*' => Http::response([
                'text' => 'Concurrent response',
                'usage' => ['total_tokens' => 15]
            ], 200)
        ]);

        $promises = [];
        $concurrentRequests = 5;

        for ($i = 0; $i < $concurrentRequests; $i++) {
            $promises[] = \Illuminate\Support\Facades\Http::async()
                ->withHeaders([
                    'Authorization' => 'Bearer test-key',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.custom-ai.com/generate', [
                    'prompt' => "Concurrent test {$i}",
                    'max_tokens' => 100
                ]);
        }

        $responses = \Illuminate\Support\Facades\Http::pool(fn () => $promises);

        foreach ($responses as $response) {
            $this->assertTrue($response->successful());
        }
    }
}
```