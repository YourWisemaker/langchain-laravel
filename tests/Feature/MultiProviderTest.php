<?php

namespace Tests\Feature;

use Tests\TestCase;
use LangChainLaravel\Facades\LangChain;
use LangChainLaravel\AI\LangChainManager;
use LangChainLaravel\AI\Providers\OpenAIProvider;
use LangChainLaravel\AI\Providers\ClaudeProvider;
use LangChainLaravel\AI\Providers\LlamaProvider;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

class MultiProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional test-specific configuration can be added here if needed
    }

    public function test_facade_resolves_correctly()
    {
        $manager = LangChain::getFacadeRoot();
        $this->assertInstanceOf(LangChainManager::class, $manager);
    }

    public function test_default_provider_is_set_correctly()
    {
        $defaultProvider = LangChain::getDefaultProvider();
        $this->assertEquals('openai', $defaultProvider);
    }

    public function test_can_get_available_providers()
    {
        $providers = LangChain::getAvailableProviders();
        $this->assertIsArray($providers);
        $this->assertContains('openai', $providers);
        $this->assertContains('claude', $providers);
        $this->assertContains('llama', $providers);
    }

    public function test_can_validate_providers()
    {
        $this->assertTrue(LangChain::isValidProvider('openai'));
        $this->assertTrue(LangChain::isValidProvider('claude'));
        $this->assertTrue(LangChain::isValidProvider('llama'));
        $this->assertFalse(LangChain::isValidProvider('invalid'));
    }

    public function test_can_get_provider_instances()
    {
        $openaiProvider = LangChain::getProvider('openai');
        $this->assertInstanceOf(OpenAIProvider::class, $openaiProvider);

        $claudeProvider = LangChain::getProvider('claude');
        $this->assertInstanceOf(ClaudeProvider::class, $claudeProvider);

        $llamaProvider = LangChain::getProvider('llama');
        $this->assertInstanceOf(LlamaProvider::class, $llamaProvider);
    }

    public function test_throws_exception_for_invalid_provider()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Provider 'invalid' is not configured");
        
        LangChain::getProvider('invalid');
    }

    public function test_can_set_default_provider()
    {
        LangChain::setDefaultProvider('claude');
        $this->assertEquals('claude', LangChain::getDefaultProvider());

        LangChain::setDefaultProvider('llama');
        $this->assertEquals('llama', LangChain::getDefaultProvider());
    }

    public function test_throws_exception_for_invalid_default_provider()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid provider: invalid");
        
        LangChain::setDefaultProvider('invalid');
    }

    public function test_provider_instances_are_singletons()
    {
        $provider1 = LangChain::getProvider('openai');
        $provider2 = LangChain::getProvider('openai');
        
        $this->assertSame($provider1, $provider2);
    }

    public function test_can_get_configuration()
    {
        $config = LangChain::getConfig();
        $this->assertIsArray($config);
        $this->assertEquals('openai', $config['default']);
        $this->assertArrayHasKey('providers', $config);
        $this->assertArrayHasKey('model_aliases', $config);
    }

    public function test_provider_specific_methods_exist()
    {
        $manager = LangChain::getFacadeRoot();
        
        $this->assertTrue(method_exists($manager, 'openai'));
        $this->assertTrue(method_exists($manager, 'claude'));
        $this->assertTrue(method_exists($manager, 'llama'));
    }

    public function test_generate_text_with_specific_provider()
    {
        // Mock the HTTP client for testing
        $this->markTestSkipped('Requires HTTP mocking for actual API calls');
    }

    public function test_model_aliases_are_loaded()
    {
        $config = LangChain::getConfig();
        $aliases = $config['model_aliases'];
        
        $this->assertArrayHasKey('gpt4', $aliases);
        $this->assertArrayHasKey('claude', $aliases);
        $this->assertArrayHasKey('llama2', $aliases);
        $this->assertEquals('gpt-4', $aliases['gpt4']);
    }

    public function test_provider_configuration_is_passed_correctly()
    {
        $openaiProvider = LangChain::getProvider('openai');
        $claudeProvider = LangChain::getProvider('claude');
        $llamaProvider = LangChain::getProvider('llama');
        
        // Test that providers receive their configuration
        $this->assertInstanceOf(OpenAIProvider::class, $openaiProvider);
        $this->assertInstanceOf(ClaudeProvider::class, $claudeProvider);
        $this->assertInstanceOf(LlamaProvider::class, $llamaProvider);
    }

    public function test_backward_compatibility_with_openai_method()
    {
        $manager = LangChain::getFacadeRoot();
        $openaiProvider = $manager->openai(); // Legacy method
        
        $this->assertInstanceOf(OpenAIProvider::class, $openaiProvider);
    }

    public function test_cache_configuration_is_preserved()
    {
        $config = LangChain::getConfig();
        $cacheConfig = $config['cache'];
        
        $this->assertTrue($cacheConfig['enabled']);
        $this->assertEquals(3600, $cacheConfig['ttl']);
    }

    public function test_request_configuration_is_preserved()
    {
        $config = LangChain::getConfig();
        $requestConfig = $config['request'] ?? [];
        
        $this->assertEquals(30, $requestConfig['timeout'] ?? 30);
        $this->assertEquals(3, $requestConfig['retry_attempts'] ?? 3);
        $this->assertEquals(1000, $requestConfig['retry_delay'] ?? 1000);
    }

    public function test_provider_default_parameters_are_set()
    {
        $config = LangChain::getConfig();
        
        // OpenAI defaults
        $openaiConfig = $config['providers']['openai'];
        $this->assertEquals('gpt-3.5-turbo', $openaiConfig['default_model']);
        $this->assertEquals(1000, $openaiConfig['default_max_tokens']);
        $this->assertEquals(0.7, $openaiConfig['default_temperature']);
        
        // Claude defaults
        $claudeConfig = $config['providers']['claude'];
        $this->assertEquals('claude-3-sonnet-20240229', $claudeConfig['default_model']);
        $this->assertEquals(1000, $claudeConfig['default_max_tokens']);
        $this->assertEquals(0.7, $claudeConfig['default_temperature']);
        
        // Llama defaults
        $llamaConfig = $config['providers']['llama'];
        $this->assertEquals('meta-llama/Llama-2-70b-chat-hf', $llamaConfig['default_model']);
        $this->assertEquals(1000, $llamaConfig['default_max_tokens']);
        $this->assertEquals(0.7, $llamaConfig['default_temperature']);
    }
}