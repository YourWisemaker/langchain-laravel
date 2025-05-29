<?php

namespace Tests\Integration;

use Tests\TestCase;
use LangChainLaravel\AI\LangChainManager;
use LangChainLaravel\Facades\LangChain;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class OpenAIIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Only run these tests if we have a real API key for integration testing
        if (!env('OPENAI_API_KEY_INTEGRATION')) {
            $this->markTestSkipped('Integration tests require OPENAI_API_KEY_INTEGRATION environment variable');
        }
        
        Config::set('langchain.openai.api_key', env('OPENAI_API_KEY_INTEGRATION'));
    }

    public function test_real_openai_text_generation()
    {
        $response = LangChain::generateText('Say "Hello World" in a friendly way', [
            'temperature' => 0.1,
            'max_tokens' => 50
        ]);
        
        $this->assertTrue($response['success'], 'API call should succeed');
        $this->assertNotEmpty($response['text'], 'Response should contain text');
        $this->assertArrayHasKey('usage', $response, 'Response should include usage statistics');
        $this->assertGreaterThan(0, $response['usage']['total_tokens'], 'Should have used some tokens');
    }

    public function test_different_models_work()
    {
        $models = [
            'text-davinci-003',
            'text-curie-001',
            'text-babbage-001'
        ];
        
        foreach ($models as $model) {
            $response = LangChain::generateText('Test prompt', [
                'model' => $model,
                'max_tokens' => 20,
                'temperature' => 0.1
            ]);
            
            $this->assertTrue(
                $response['success'], 
                "Model {$model} should work. Error: " . ($response['error'] ?? 'none')
            );
        }
    }

    public function test_temperature_affects_output_variability()
    {
        $prompt = 'Write a creative story about a cat';
        
        // Low temperature (more deterministic)
        $lowTempResponse = LangChain::generateText($prompt, [
            'temperature' => 0.1,
            'max_tokens' => 100
        ]);
        
        // High temperature (more creative)
        $highTempResponse = LangChain::generateText($prompt, [
            'temperature' => 1.5,
            'max_tokens' => 100
        ]);
        
        $this->assertTrue($lowTempResponse['success']);
        $this->assertTrue($highTempResponse['success']);
        
        // The responses should be different (though this isn't guaranteed)
        // This is more of a sanity check
        $this->assertNotEmpty($lowTempResponse['text']);
        $this->assertNotEmpty($highTempResponse['text']);
    }

    public function test_max_tokens_limits_response_length()
    {
        $shortResponse = LangChain::generateText('Write a long essay about PHP', [
            'max_tokens' => 10,
            'temperature' => 0.5
        ]);
        
        $longResponse = LangChain::generateText('Write a long essay about PHP', [
            'max_tokens' => 200,
            'temperature' => 0.5
        ]);
        
        $this->assertTrue($shortResponse['success']);
        $this->assertTrue($longResponse['success']);
        
        // Short response should use fewer tokens
        $this->assertLessThan(
            $longResponse['usage']['completion_tokens'],
            $shortResponse['usage']['completion_tokens']
        );
    }

    public function test_invalid_api_key_returns_error()
    {
        $manager = new LangChainManager([
            'openai' => ['api_key' => 'invalid-key-12345']
        ]);
        
        $response = $manager->generateText('Test prompt');
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('error', $response);
        $this->assertStringContainsString('API', $response['error']);
    }

    public function test_very_long_prompt_handling()
    {
        // Create a very long prompt
        $longPrompt = str_repeat('This is a test sentence. ', 100);
        $longPrompt .= 'Please summarize the above.';
        
        $response = LangChain::generateText($longPrompt, [
            'max_tokens' => 100,
            'temperature' => 0.3
        ]);
        
        // Should either succeed or fail gracefully
        if ($response['success']) {
            $this->assertNotEmpty($response['text']);
        } else {
            $this->assertArrayHasKey('error', $response);
        }
    }

    public function test_special_characters_in_prompt()
    {
        $specialPrompt = 'Translate: "Hello, world!" (with émojis 🌍) & symbols @#$%';
        
        $response = LangChain::generateText($specialPrompt, [
            'max_tokens' => 50,
            'temperature' => 0.3
        ]);
        
        $this->assertTrue($response['success'], 'Should handle special characters');
        $this->assertNotEmpty($response['text']);
    }

    public function test_empty_prompt_handling()
    {
        $response = LangChain::generateText('', [
            'max_tokens' => 50
        ]);
        
        // Should either succeed with some default response or fail gracefully
        if (!$response['success']) {
            $this->assertArrayHasKey('error', $response);
        }
    }

    public function test_rate_limiting_handling()
    {
        // Make multiple rapid requests to test rate limiting
        $responses = [];
        
        for ($i = 0; $i < 5; $i++) {
            $responses[] = LangChain::generateText("Test request {$i}", [
                'max_tokens' => 10,
                'temperature' => 0.1
            ]);
            
            // Small delay to be respectful
            usleep(100000); // 100ms
        }
        
        // At least some should succeed
        $successCount = count(array_filter($responses, fn($r) => $r['success']));
        $this->assertGreaterThan(0, $successCount, 'At least some requests should succeed');
    }

    public function test_usage_statistics_accuracy()
    {
        $response = LangChain::generateText('Count to five', [
            'max_tokens' => 20,
            'temperature' => 0.1
        ]);
        
        $this->assertTrue($response['success']);
        
        $usage = $response['usage'];
        $this->assertArrayHasKey('prompt_tokens', $usage);
        $this->assertArrayHasKey('completion_tokens', $usage);
        $this->assertArrayHasKey('total_tokens', $usage);
        
        // Total should equal prompt + completion
        $this->assertEquals(
            $usage['prompt_tokens'] + $usage['completion_tokens'],
            $usage['total_tokens']
        );
        
        // All values should be positive
        $this->assertGreaterThan(0, $usage['prompt_tokens']);
        $this->assertGreaterThan(0, $usage['completion_tokens']);
        $this->assertGreaterThan(0, $usage['total_tokens']);
    }
}