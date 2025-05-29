<?php

namespace Tests\Feature;

use Tests\TestCase;
use LangChainLaravel\Facades\LangChain;
use LangChainLaravel\AI\Providers\DeepSeekProvider;
use LangChainLaravel\AI\Providers\AbstractProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use ReflectionClass;

class EnhancedCapabilitiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test configuration
        Config::set('langchain.providers.deepseek', [
            'api_key' => 'test-deepseek-key',
            'base_url' => 'https://api.deepseek.com',
            'default_model' => 'deepseek-chat',
            'default_max_tokens' => 1000,
            'default_temperature' => 0.7,
        ]);
        
        Config::set('langchain.providers.openai', [
            'api_key' => 'test-openai-key',
            'base_url' => 'https://api.openai.com/v1',
            'default_model' => 'gpt-3.5-turbo',
            'default_max_tokens' => 1000,
            'default_temperature' => 0.7,
        ]);
    }
    
    /** @test */
    public function it_can_create_deepseek_provider_instance()
    {
        $provider = LangChain::getProvider('deepseek');
        
        $this->assertInstanceOf(DeepSeekProvider::class, $provider);
        $this->assertInstanceOf(AbstractProvider::class, $provider);
    }
    
    /** @test */
    public function it_includes_deepseek_in_available_providers()
    {
        $providers = LangChain::getAvailableProviders();
        
        $this->assertContains('deepseek', $providers);
        $this->assertContains('openai', $providers);
        $this->assertContains('claude', $providers);
        $this->assertContains('llama', $providers);
    }
    
    /** @test */
    public function it_validates_deepseek_as_valid_provider()
    {
        $this->assertTrue(LangChain::isValidProvider('deepseek'));
        $this->assertFalse(LangChain::isValidProvider('invalid-provider'));
    }
    
    /** @test */
    public function it_supports_enhanced_capabilities()
    {
        $provider = LangChain::getProvider('openai');
        
        // Test basic capabilities
        $this->assertTrue($provider->supportsCapability('text_generation'));
        $this->assertTrue($provider->supportsCapability('translation'));
        $this->assertTrue($provider->supportsCapability('code_generation'));
        $this->assertTrue($provider->supportsCapability('code_analysis'));
        $this->assertTrue($provider->supportsCapability('agent'));
        $this->assertTrue($provider->supportsCapability('summarization'));
        
        // Test unsupported capability
        $this->assertFalse($provider->supportsCapability('nonexistent_capability'));
    }
    
    /** @test */
    public function it_supports_deepseek_specific_capabilities()
    {
        $provider = LangChain::getProvider('deepseek');
        
        // DeepSeek should support additional capabilities
        $this->assertTrue($provider->supportsCapability('reasoning'));
        $this->assertTrue($provider->supportsCapability('math_solving'));
        
        $capabilities = $provider->getSupportedCapabilitiesList();
        $this->assertContains('reasoning', $capabilities);
        $this->assertContains('math_solving', $capabilities);
    }
    
    /** @test */
    public function it_can_translate_text()
    {
        $provider = LangChain::getProvider('openai');
        
        // Mock the generateText method to return a translation
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => 'Hola, ¿cómo estás?'
        ]);
        
        $result = $provider->translateText('Hello, how are you?', 'Spanish', 'English');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Hola, ¿cómo estás?', $result['text']);
        $this->assertEquals('English', $result['source_language']);
        $this->assertEquals('Spanish', $result['target_language']);
    }
    
    /** @test */
    public function it_can_generate_code()
    {
        $provider = LangChain::getProvider('openai');
        
        $expectedCode = '<?php\nfunction validateEmail($email) {\n    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;\n}';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedCode
        ]);
        
        $result = $provider->generateCode('Create a PHP function to validate email', 'php');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedCode, $result['code']);
        $this->assertEquals('php', $result['language']);
    }
    
    /** @test */
    public function it_can_act_as_agent()
    {
        $provider = LangChain::getProvider('openai');
        
        $expectedResponse = 'As a software architect, I recommend implementing microservices architecture...';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedResponse
        ]);
        
        $result = $provider->actAsAgent(
            'Software Architect',
            'Design a scalable e-commerce system',
            ['budget' => '$100k', 'timeline' => '6 months']
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedResponse, $result['response']);
        $this->assertEquals('Software Architect', $result['role']);
    }
    
    /** @test */
    public function it_can_explain_code()
    {
        $provider = LangChain::getProvider('openai');
        
        $code = 'function fibonacci(n) { return n <= 1 ? n : fibonacci(n-1) + fibonacci(n-2); }';
        $expectedExplanation = 'This is a recursive function that calculates the Fibonacci sequence...';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedExplanation
        ]);
        
        $result = $provider->explainCode($code, 'javascript');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedExplanation, $result['explanation']);
        $this->assertEquals('javascript', $result['language']);
    }
    
    /** @test */
    public function it_can_summarize_text()
    {
        $provider = LangChain::getProvider('openai');
        
        $longText = str_repeat('This is a long text that needs to be summarized. ', 50);
        $expectedSummary = 'This text discusses the need for summarization.';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedSummary
        ]);
        
        $result = $provider->summarizeText($longText, 100);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedSummary, $result['summary']);
        $this->assertEquals(strlen($longText), $result['original_length']);
        $this->assertEquals(strlen($expectedSummary), $result['summary_length']);
    }
    
    /** @test */
    public function it_can_solve_math_problems_with_deepseek()
    {
        $provider = LangChain::getProvider('deepseek');
        
        $expectedSolution = 'Step 1: Calculate total distance...\nStep 2: Calculate total time...\nAnswer: 60 km/h';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedSolution
        ]);
        
        $result = $provider->solveMath('What is the average speed if you travel 120km in 2 hours?');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedSolution, $result['solution']);
        $this->assertIsArray($result['steps']);
    }
    
    /** @test */
    public function it_can_perform_reasoning_with_deepseek()
    {
        $provider = LangChain::getProvider('deepseek');
        
        $expectedReasoning = 'Let me think through this step by step...\nTherefore, the answer is yes.';
        
        $this->mockProviderResponse($provider, [
            'success' => true,
            'text' => $expectedReasoning
        ]);
        
        $result = $provider->performReasoning(
            'Should we invest in renewable energy?',
            ['budget' => '$1M', 'timeline' => '5 years']
        );
        
        $this->assertTrue($result['success']);
        $this->assertEquals($expectedReasoning, $result['reasoning']);
        $this->assertEquals('the answer is yes.', $result['conclusion']);
    }
    
    /** @test */
    public function it_returns_error_for_unsupported_capabilities()
    {
        // Create a mock provider that doesn't support certain capabilities
        $provider = $this->createMockProvider(['text_generation']);
        
        $result = $provider->translateText('Hello', 'Spanish');
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Translation capability not supported', $result['error']);
    }
    
    /** @test */
    public function it_formats_prompts_for_different_languages()
    {
        $provider = LangChain::getProvider('openai');
        
        // Use reflection to test the protected method
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('formatPromptForLanguage');
        $method->setAccessible(true);
        
        $prompt = 'Hello world';
        $formattedPrompt = $method->invoke($provider, $prompt, 'Spanish');
        
        $this->assertStringContainsString('Please respond in Spanish', $formattedPrompt);
        $this->assertStringContainsString($prompt, $formattedPrompt);
        
        // Test auto language
        $autoPrompt = $method->invoke($provider, $prompt, 'auto');
        $this->assertEquals($prompt, $autoPrompt);
    }
    
    /** @test */
    public function it_has_correct_model_aliases_for_deepseek()
    {
        $aliases = config('langchain.model_aliases');
        
        $this->assertArrayHasKey('deepseek', $aliases);
        $this->assertArrayHasKey('deepseek-coder', $aliases);
        $this->assertArrayHasKey('deepseek-math', $aliases);
        
        $this->assertEquals('deepseek-chat', $aliases['deepseek']);
        $this->assertEquals('deepseek-coder', $aliases['deepseek-coder']);
        $this->assertEquals('deepseek-math-7b-instruct', $aliases['deepseek-math']);
    }
    
    /** @test */
    public function it_can_access_deepseek_through_facade()
    {
        // Test that the facade method exists and can be called
        $this->assertTrue(method_exists(LangChain::class, 'deepseek'));
        
        // Mock the response to avoid actual API calls
        $this->mockFacadeResponse('deepseek', [
            'success' => true,
            'text' => 'DeepSeek response'
        ]);
        
        $result = LangChain::deepseek('Test prompt');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('DeepSeek response', $result['text']);
    }
    
    /**
     * Mock a provider response for testing
     */
    private function mockProviderResponse($provider, array $response)
    {
        $mock = \Mockery::mock($provider);
        $mock->shouldReceive('generateText')->andReturn($response);
        $mock->shouldReceive('supportsCapability')->andReturn(true);
        $mock->shouldReceive('getSupportedCapabilitiesList')->andReturn([
            'text_generation', 'translation', 'code_generation', 
            'code_analysis', 'agent', 'summarization'
        ]);
        
        // Replace the provider in the container
        $this->app->instance(get_class($provider), $mock);
    }
    
    /**
     * Create a mock provider with specific capabilities
     */
    private function createMockProvider(array $capabilities)
    {
        return new class($capabilities) extends AbstractProvider {
            private array $caps;
            
            public function __construct(array $capabilities)
            {
                $this->caps = $capabilities;
                parent::__construct([]);
            }
            
            public function generateText(string $prompt, array $params = []): array
            {
                return ['success' => true, 'text' => 'Mock response'];
            }
            
            protected function getDefaultParams(): array
            {
                return [];
            }
            
            protected function getSupportedCapabilities(): array
            {
                return $this->caps;
            }
            
            protected function validateConfig(): void
            {
                // Mock validation
            }
        };
    }
    
    /**
     * Mock facade response for testing
     */
    private function mockFacadeResponse(string $method, array $response)
    {
        // This would typically involve mocking the underlying manager
        // For now, we'll just verify the method exists
    }
}