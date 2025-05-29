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
        // Mock the Factory to avoid actual API calls
        $mockClient = Mockery::mock(OpenAIClient::class);
        
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
        
        $mockClient = Mockery::mock(OpenAIClient::class);
        $mockClient->shouldReceive('completions')->andReturn($mockCompletions);
        
        // This would require dependency injection or a factory pattern
        // For now, we'll test the expected structure
        $this->markTestSkipped('Requires client mocking setup');
        
        $result = $this->manager->generateText('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Generated text response', $result['text']);
        $this->assertArrayHasKey('usage', $result);
    }

    public function test_generate_text_handles_api_error()
    {
        $this->markTestSkipped('Requires client mocking setup');
        
        // Mock an API error
        $mockCompletions = Mockery::mock();
        $mockCompletions->shouldReceive('create')
            ->andThrow(new ErrorException(['message' => 'API Error']));
        
        $mockClient = Mockery::mock(OpenAIClient::class);
        $mockClient->shouldReceive('completions')->andReturn($mockCompletions);
        
        /** @var array{success: bool, text?: string, usage?: array, error?: string} $result */
        $result = $this->manager->generateText('Test prompt');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('API Error', $result['error']);
        $this->assertArrayNotHasKey('text', $result);
    }

    public function test_generate_text_with_custom_parameters()
    {
        $this->markTestSkipped('Requires client mocking setup');
        
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
                'usage' => (object) ['total_tokens' => 50]
            ]);
        
        $result = $this->manager->generateText('Test prompt', $params);
        
        $this->assertTrue($result['success']);
    }

    public function test_generate_text_uses_default_parameters()
    {
        $this->markTestSkipped('Requires client mocking setup');
        
        $mockCompletions = Mockery::mock();
        $mockCompletions->shouldReceive('create')
            ->with([
                'model' => 'text-davinci-003',
                'prompt' => 'Test prompt',
                'temperature' => 0.7,
                'max_tokens' => 256,
            ])
            ->once();
        
        $this->manager->generateText('Test prompt');
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
        $clientProperty = $reflection->getProperty('openAi');
        $clientProperty->setAccessible(true);
        
        $this->assertNull($clientProperty->getValue($this->manager));
    }

    /**
     * Test data providers
     */
    public function invalidApiKeyProvider(): array
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

    public function parameterProvider(): array
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
        // This would test that parameters are properly merged with defaults
        $this->markTestSkipped('Requires client mocking setup');
    }
}