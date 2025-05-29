<?php

namespace Tests\Feature;

use Tests\TestCase;
use LangChain\Facades\LangChain;
use LangChain\AI\LangChainManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;

class LangChainFacadeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('langchain.openai.api_key', 'test-api-key');
        Config::set('langchain.cache.enabled', true);
        Config::set('langchain.cache.ttl', 3600);
    }

    public function test_facade_resolves_to_manager_instance()
    {
        $manager = LangChain::getFacadeRoot();
        $this->assertInstanceOf(LangChainManager::class, $manager);
    }

    public function test_facade_can_call_generate_text_method()
    {
        // Mock the manager to avoid actual API calls
        $mockManager = Mockery::mock(LangChainManager::class);
        $mockManager->shouldReceive('generateText')
            ->with('Test prompt', [])
            ->andReturn([
                'success' => true,
                'text' => 'Mocked response',
                'usage' => ['total_tokens' => 10]
            ]);
        
        $this->app->instance('langchain', $mockManager);
        
        $result = LangChain::generateText('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Mocked response', $result['text']);
    }

    public function test_facade_passes_parameters_correctly()
    {
        $params = [
            'temperature' => 0.5,
            'max_tokens' => 100
        ];
        
        $mockManager = Mockery::mock(LangChainManager::class);
        $mockManager->shouldReceive('generateText')
            ->with('Test prompt', $params)
            ->andReturn([
                'success' => true,
                'text' => 'Mocked response with params',
                'usage' => ['total_tokens' => 15]
            ]);
        
        $this->app->instance('langchain', $mockManager);
        
        $result = LangChain::generateText('Test prompt', $params);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Mocked response with params', $result['text']);
    }

    public function test_service_provider_registers_singleton()
    {
        $manager1 = app('langchain');
        $manager2 = app('langchain');
        
        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(LangChainManager::class, $manager1);
    }

    public function test_config_is_passed_to_manager()
    {
        $manager = app('langchain');
        
        // Use reflection to check the config was passed correctly
        $reflection = new \ReflectionClass($manager);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        $config = $configProperty->getValue($manager);
        
        $this->assertEquals('test-api-key', $config['openai']['api_key']);
        $this->assertTrue($config['cache']['enabled']);
        $this->assertEquals(3600, $config['cache']['ttl']);
    }

    public function test_facade_handles_missing_config_gracefully()
    {
        Config::set('langchain.openai.api_key', null);
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is required');
        
        // This should trigger the validation when trying to get OpenAI client
        $manager = app('langchain');
        $manager->openAi();
    }

    public function test_multiple_facade_calls_use_same_instance()
    {
        $mockManager = Mockery::mock(LangChainManager::class);
        $mockManager->shouldReceive('generateText')
            ->twice()
            ->andReturn([
                'success' => true,
                'text' => 'Response',
                'usage' => ['total_tokens' => 10]
            ]);
        
        $this->app->instance('langchain', $mockManager);
        
        LangChain::generateText('First call');
        LangChain::generateText('Second call');
        
        // Mockery will verify that the same instance was used
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}