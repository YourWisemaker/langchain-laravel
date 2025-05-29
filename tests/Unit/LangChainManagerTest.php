<?php

namespace Tests\Unit;

use Tests\TestCase;
use LangChainLaravel\AI\LangChainManager;
use OpenAI\Client as OpenAIClient;
use OpenAI\Exceptions\ErrorException;
use RuntimeException;
use Mockery;
use ReflectionClass;

class LangChainManagerTest extends TestCase
{
    protected LangChainManager $manager;
    protected array $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = [
            'providers' => [
                'openai' => [
                    'api_key' => 'test-api-key'
                ]
            ],
            'cache' => [
                'enabled' => true,
                'ttl' => 3600
            ]
        ];
        
        $this->manager = new LangChainManager($this->config);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructor_sets_config()
    {
        $manager = new LangChainManager($this->config);
        $this->assertInstanceOf(LangChainManager::class, $manager);
    }

    public function test_validate_openai_config_throws_exception_when_api_key_missing()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is required');
        
        $configWithoutKey = ['providers' => ['openai' => ['api_key' => '']]];
        $manager = new LangChainManager($configWithoutKey);
        $manager->openai('test prompt');
    }

    public function test_validate_openai_config_throws_exception_when_api_key_null()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is required');
        
        $configWithoutKey = ['providers' => ['openai' => ['api_key' => null]]];
        $manager = new LangChainManager($configWithoutKey);
        $manager->openai('test prompt');
    }

    public function test_openai_client_is_singleton()
    {
        // We'll need to mock the Factory class in a real implementation
        // For now, this test demonstrates the expected behavior
        $this->markTestSkipped('Requires Factory mocking setup');
        
        $client1 = $this->manager->getProvider('openai');
        $client2 = $this->manager->getProvider('openai');
        
        $this->assertSame($client1, $client2);
    }

    public function test_generate_text_returns_success_response()
    {
        // Mock the OpenAI client and response
        $mockResponse = (object) [
            'choices' => [
                (object) ['text' => 'Generated text response']
            ],
            'usage' => (object) [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30
            ]
        ];
        
        $mockUsage = Mockery::mock();
        $mockUsage->shouldReceive('toArray')->andReturn([
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30
        ]);
        $mockResponse->usage = $mockUsage;
        
        $mockCompletions = Mockery::mock();
        $mockCompletions->shouldReceive('create')
            ->with([
                'model' => 'text-davinci-003',
                'prompt' => 'Test prompt',
                'temperature' => 0.7,
                'max_tokens' => 256,
            ])
            ->andReturn($mockResponse);
        
        $mockAdapter = Mockery::mock(\LangChainLaravel\AI\Adapters\OpenAI\ClientAdapter::class);
        // Since OpenAIProvider::generateText is now responsible for the structure,
        // and it internally calls getClient()->chat() or getClient()->completions(),
        // we mock the behavior of the provider's generateText method directly.

        $mockProvider = Mockery::mock(\LangChainLaravel\AI\Providers\OpenAIProvider::class)->makePartial();
        $mockProvider->shouldAllowMockingProtectedMethods(); // Allow mocking protected methods if any were called by generateText

        // The actual call from LangChainManager is to $providerInstance->generateText()
        // So we mock this method on the provider.
        $mockProvider->shouldReceive('generateText')
            ->with('Test prompt', Mockery::any()) // Allow any params for now, can be more specific
            ->andReturn([
                'success' => true,
                'text' => 'Generated text response',
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30
                ]
            ]);

        $this->manager->setProvider('openai', $mockProvider);
        
        // $this->markTestSkipped('Requires client mocking setup'); // Try to unskip
        
        $result = $this->manager->generateText('Test prompt', [], 'openai'); // Specify provider
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Generated text response', $result['text']);
        $this->assertArrayHasKey('usage', $result);
    }

    public function test_generate_text_handles_api_error()
    {
        // $this->markTestSkipped('Requires client mocking setup'); // Try to unskip
        
        // Mock an API error by mocking the provider's generateText method
        $mockProvider = Mockery::mock(\LangChainLaravel\AI\Providers\OpenAIProvider::class)->makePartial();
        $mockProvider->shouldAllowMockingProtectedMethods();

        $mockProvider->shouldReceive('generateText')
            ->with('Test prompt', Mockery::any())
            ->andReturn([
                'success' => false,
                'error' => 'API Error',
            ]);
            
        $this->manager->setProvider('openai', $mockProvider);
        
        /** @var array{success: bool, text?: string, usage?: array, error?: string} $result */
        $result = $this->manager->generateText('Test prompt', [], 'openai'); // Specify provider
        
        $this->assertFalse($result['success']);
        $this->assertEquals('API Error', $result['error']);
        $this->assertArrayNotHasKey('text', $result);
    }

    public function test_generate_text_with_custom_parameters()
    {
        // $this->markTestSkipped('Requires client mocking setup'); // Try to unskip
        
        $params = [
            'model' => 'text-curie-001',
            'temperature' => 0.5,
            'max_tokens' => 100
        ];
        
        $mockCompletions = Mockery::mock();
        $mockCompletions->shouldReceive('create')
            ->with([
                'model' => 'text-curie-001',
                'prompt' => 'Test prompt',
                'temperature' => 0.5,
                'max_tokens' => 100,
            ])
            ->andReturn((object) [
                'choices' => [(object) ['text' => 'Response']],
                'usage' => ['total_tokens' => 50] // Ensure array format
            ]);

        $mockProvider = Mockery::mock(\LangChainLaravel\AI\Providers\OpenAIProvider::class)->makePartial();
        $mockProvider->shouldReceive('generateText')
            ->with('Test prompt', $params) // Expect these specific params
            ->andReturn([
                'success' => true,
                'text' => 'Response',
                'usage' => ['total_tokens' => 50]
            ]);
        
        $this->manager->setProvider('openai', $mockProvider);
        
        $result = $this->manager->generateText('Test prompt', $params, 'openai'); // Specify provider
        
        $this->assertTrue($result['success']);
    }

    public function test_generate_text_uses_default_parameters()
    {
        // $this->markTestSkipped('Requires client mocking setup'); // Try to unskip
        
        // We expect the provider's generateText to be called with the prompt and empty params,
        // as defaults are handled within the provider itself or its defaults.
        $mockProvider = Mockery::mock(\LangChainLaravel\AI\Providers\OpenAIProvider::class)->makePartial();
        $mockProvider->shouldReceive('generateText')
            ->with('Test prompt', []) // Expect empty params, defaults are provider's concern
            ->once()
            ->andReturn([ // Must return the expected structure
                'success' => true, 
                'text' => 'Default response', 
                'usage' => []
            ]);

        $this->manager->setProvider('openai', $mockProvider);
        
        $this->manager->generateText('Test prompt', [], 'openai'); // Specify provider and empty params
    }

    public function test_config_is_accessible()
    {
        $reflection = new ReflectionClass($this->manager);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setAccessible(true);
        
        $this->assertEquals($this->config, $configProperty->getValue($this->manager));
    }

    public function test_openai_client_property_is_initially_null()
    {
        $reflection = new ReflectionClass($this->manager);
        $providersProperty = $reflection->getProperty('providers');
        $providersProperty->setAccessible(true);
        
        // 1. Assert that initially, the $providers array does not contain a key for 'openai'
        $initialProviders = $providersProperty->getValue($this->manager);
        $this->assertArrayNotHasKey('openai', $initialProviders, "Providers array should not have 'openai' key initially.");
        
        // 2. Trigger initialization
        $this->manager->getProvider('openai');
        
        // 3. Assert that the $providers array now contains the key 'openai' and it's the correct type
        $activeProviders = $providersProperty->getValue($this->manager);
        $this->assertArrayHasKey('openai', $activeProviders, "Providers array should have 'openai' key after getProvider() call.");
        $this->assertInstanceOf(
            \LangChainLaravel\AI\Providers\OpenAIProvider::class,
            $activeProviders['openai'],
            "Provider 'openai' should be an instance of OpenAIProvider."
        );
    }

    /**
     * Test data providers
     */
    public static function invalidApiKeyProvider(): array
    {
        return [
            'empty string' => [''],
            'null value' => [null],
            'whitespace only' => ['   '],
        ];
    }

    /**
     * @dataProvider invalidApiKeyProvider
     */
    public function test_validate_openai_config_with_invalid_keys($invalidKey)
    {
        $this->expectException(RuntimeException::class);
        
        $config = ['providers' => ['openai' => ['api_key' => $invalidKey]]];
        $manager = new LangChainManager($config);
        $manager->openai('test prompt');
    }

    public static function parameterProvider(): array
    {
        return [
            'minimal params' => [['temperature' => 0.5]],
            'full params' => [[
                'model' => 'text-curie-001',
                'temperature' => 0.8,
                'max_tokens' => 500
            ]],
            'empty params' => [[]],
        ];
    }

    /**
     * @dataProvider parameterProvider
     */
    public function test_generate_text_parameter_handling($params)
    {
        // $this->markTestSkipped('Requires client mocking setup'); // Try to unskip

        // This test asserts that LangChainManager correctly passes the parameters
        // to the provider. The provider itself is responsible for merging with defaults.
        $mockProvider = Mockery::mock(\LangChainLaravel\AI\Providers\OpenAIProvider::class)->makePartial();
        $mockProvider->shouldReceive('generateText')
            ->with('Test prompt', $params) // Expect the exact params passed
            ->once()
            ->andReturn(['success' => true, 'text' => 'Handled response', 'usage' => []]);

        $this->manager->setProvider('openai', $mockProvider);

        $this->manager->generateText('Test prompt', $params, 'openai');
    }
}