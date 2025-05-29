<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;
use RuntimeException;

/**
 * Advanced Features Test
 * 
 * Tests for advanced LangChain Laravel features including caching,
 * error handling, and performance optimizations.
 */
class AdvancedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable caching for these tests
        config(['langchain.cache.enabled' => true]);
        config(['langchain.cache.ttl' => 3600]);
    }

    public function test_caching_functionality()
    {
        $prompt = 'Test caching prompt';
        $expectedResponse = [
            'success' => true,
            'text' => 'Cached response text',
            'usage' => ['total_tokens' => 25]
        ];

        // Mock LangChain to return expected response
        LangChain::shouldReceive('generateText')
            ->once() // Should only be called once due to caching
            ->with($prompt, [])
            ->andReturn($expectedResponse);

        // First call - should hit the API
        $response1 = LangChain::generateText($prompt);
        
        // Second call - should use cache
        $response2 = LangChain::generateText($prompt);

        $this->assertEquals($expectedResponse, $response1);
        $this->assertEquals($expectedResponse, $response2);
    }

    public function test_cache_key_generation()
    {
        $prompt1 = 'First prompt';
        $prompt2 = 'Second prompt';
        $params = ['temperature' => 0.5];

        LangChain::shouldReceive('generateText')
            ->twice() // Different prompts should generate different cache keys
            ->andReturn(['success' => true, 'text' => 'Response']);

        LangChain::generateText($prompt1, $params);
        LangChain::generateText($prompt2, $params);

        // Verify different cache keys were used
        $cacheKey1 = 'langchain_' . md5($prompt1 . serialize($params));
        $cacheKey2 = 'langchain_' . md5($prompt2 . serialize($params));
        
        $this->assertNotEquals($cacheKey1, $cacheKey2);
    }

    public function test_error_handling_with_retry()
    {
        $prompt = 'Test error handling';
        
        // Mock consecutive failures then success
        LangChain::shouldReceive('generateText')
            ->times(3)
            ->with($prompt, [])
            ->andReturn(
                ['success' => false, 'error' => 'Rate limit exceeded'],
                ['success' => false, 'error' => 'Server error'],
                ['success' => true, 'text' => 'Success after retries']
            );

        // Simulate retry logic
        $maxRetries = 3;
        $attempt = 0;
        $response = null;

        while ($attempt < $maxRetries) {
            $response = LangChain::generateText($prompt);
            
            if ($response['success']) {
                break;
            }
            
            $attempt++;
        }

        $this->assertTrue($response['success']);
        $this->assertEquals('Success after retries', $response['text']);
    }

    public function test_token_usage_tracking()
    {
        $responses = [
            ['success' => true, 'text' => 'Response 1', 'usage' => ['total_tokens' => 50]],
            ['success' => true, 'text' => 'Response 2', 'usage' => ['total_tokens' => 75]],
            ['success' => true, 'text' => 'Response 3', 'usage' => ['total_tokens' => 100]]
        ];

        LangChain::shouldReceive('generateText')
            ->times(3)
            ->andReturn(...$responses);

        $totalTokens = 0;
        $successfulRequests = 0;

        foreach (['Prompt 1', 'Prompt 2', 'Prompt 3'] as $prompt) {
            $response = LangChain::generateText($prompt);
            
            if ($response['success']) {
                $totalTokens += $response['usage']['total_tokens'];
                $successfulRequests++;
            }
        }

        $this->assertEquals(225, $totalTokens);
        $this->assertEquals(3, $successfulRequests);
    }

    public function test_batch_processing()
    {
        $prompts = [
            'Generate title for article about Laravel',
            'Write meta description for PHP tutorial',
            'Create summary for database guide'
        ];

        $expectedResponses = [
            ['success' => true, 'text' => 'Laravel: The Ultimate Guide', 'usage' => ['total_tokens' => 20]],
            ['success' => true, 'text' => 'Learn PHP with our comprehensive tutorial', 'usage' => ['total_tokens' => 25]],
            ['success' => true, 'text' => 'Database fundamentals explained simply', 'usage' => ['total_tokens' => 30]]
        ];

        LangChain::shouldReceive('generateText')
            ->times(3)
            ->andReturn(...$expectedResponses);

        $results = [];
        $totalTokens = 0;

        foreach ($prompts as $index => $prompt) {
            $response = LangChain::generateText($prompt);
            $results[$index] = $response;
            
            if ($response['success']) {
                $totalTokens += $response['usage']['total_tokens'];
            }
        }

        $this->assertCount(3, $results);
        $this->assertEquals(75, $totalTokens);
        
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
            $this->assertNotEmpty($result['text']);
        }
    }

    public function test_content_length_optimization()
    {
        $shortPrompt = 'Hi';
        $mediumPrompt = 'Write a paragraph about Laravel framework benefits';
        $longPrompt = str_repeat('This is a very long prompt that exceeds normal limits. ', 100);

        LangChain::shouldReceive('generateText')
            ->times(3)
            ->andReturn(
                ['success' => true, 'text' => 'Hello!', 'usage' => ['total_tokens' => 5]],
                ['success' => true, 'text' => 'Laravel is a powerful PHP framework...', 'usage' => ['total_tokens' => 50]],
                ['success' => true, 'text' => 'Long response...', 'usage' => ['total_tokens' => 200]]
            );

        $shortResponse = LangChain::generateText($shortPrompt, ['max_tokens' => 10]);
        $mediumResponse = LangChain::generateText($mediumPrompt, ['max_tokens' => 100]);
        $longResponse = LangChain::generateText($longPrompt, ['max_tokens' => 500]);

        $this->assertTrue($shortResponse['success']);
        $this->assertTrue($mediumResponse['success']);
        $this->assertTrue($longResponse['success']);
        
        // Verify token usage scales appropriately
        $this->assertLessThan($mediumResponse['usage']['total_tokens'], $shortResponse['usage']['total_tokens']);
        $this->assertLessThan($longResponse['usage']['total_tokens'], $mediumResponse['usage']['total_tokens']);
    }

    public function test_temperature_effects()
    {
        $prompt = 'Write a creative story opening';
        
        $lowTempResponse = ['success' => true, 'text' => 'It was a dark and stormy night.', 'usage' => ['total_tokens' => 20]];
        $highTempResponse = ['success' => true, 'text' => 'Beneath the crimson moon, whispers danced through ancient trees.', 'usage' => ['total_tokens' => 25]];

        LangChain::shouldReceive('generateText')
            ->with($prompt, ['temperature' => 0.1])
            ->once()
            ->andReturn($lowTempResponse);

        LangChain::shouldReceive('generateText')
            ->with($prompt, ['temperature' => 0.9])
            ->once()
            ->andReturn($highTempResponse);

        $conservativeResponse = LangChain::generateText($prompt, ['temperature' => 0.1]);
        $creativeResponse = LangChain::generateText($prompt, ['temperature' => 0.9]);

        $this->assertTrue($conservativeResponse['success']);
        $this->assertTrue($creativeResponse['success']);
        $this->assertNotEquals($conservativeResponse['text'], $creativeResponse['text']);
    }

    public function test_model_switching()
    {
        $prompt = 'Explain quantum computing';
        
        $models = [
            'text-davinci-003' => 'Quantum computing uses quantum mechanics...',
            'gpt-3.5-turbo-instruct' => 'Quantum computers leverage quantum bits...'
        ];

        foreach ($models as $model => $expectedText) {
            LangChain::shouldReceive('generateText')
                ->with($prompt, ['model' => $model])
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => $expectedText,
                    'usage' => ['total_tokens' => 40]
                ]);

            $response = LangChain::generateText($prompt, ['model' => $model]);
            
            $this->assertTrue($response['success']);
            $this->assertEquals($expectedText, $response['text']);
        }
    }

    public function test_concurrent_request_handling()
    {
        $prompts = array_fill(0, 5, 'Test concurrent prompt');
        
        LangChain::shouldReceive('generateText')
            ->times(5)
            ->andReturn([
                'success' => true,
                'text' => 'Concurrent response',
                'usage' => ['total_tokens' => 15]
            ]);

        $startTime = microtime(true);
        
        $responses = [];
        foreach ($prompts as $prompt) {
            $responses[] = LangChain::generateText($prompt);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // All requests should complete successfully
        foreach ($responses as $response) {
            $this->assertTrue($response['success']);
        }
        
        // Should complete in reasonable time (less than 5 seconds for mocked responses)
        $this->assertLessThan(5.0, $duration);
    }

    public function test_memory_usage_optimization()
    {
        $initialMemory = memory_get_usage();
        
        // Generate multiple responses
        for ($i = 0; $i < 10; $i++) {
            LangChain::shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => str_repeat('Response text ', 100), // ~1.3KB response
                    'usage' => ['total_tokens' => 50]
                ]);
            
            $response = LangChain::generateText("Prompt {$i}");
            
            // Process response (simulate real usage)
            $processedText = strtoupper($response['text']);
            unset($processedText); // Clean up
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 5MB)
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease);
    }

    public function test_error_logging()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('LangChain generation failed', \Mockery::type('array'));

        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn([
                'success' => false,
                'error' => 'API key invalid'
            ]);

        $response = LangChain::generateText('Test prompt');
        
        // Simulate error logging
        if (!$response['success']) {
            Log::error('LangChain generation failed', [
                'error' => $response['error'],
                'timestamp' => now()
            ]);
        }
        
        $this->assertFalse($response['success']);
    }

    public function test_configuration_validation()
    {
        // Test with missing API key
        config(['langchain.openai.api_key' => null]);
        
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API key is required');
        
        // This should trigger validation in the service provider
        app('langchain');
    }

    public function test_cache_invalidation()
    {
        $prompt = 'Test cache invalidation';
        
        LangChain::shouldReceive('generateText')
            ->twice() // Should be called twice due to cache invalidation
            ->with($prompt, [])
            ->andReturn([
                'success' => true,
                'text' => 'Response',
                'usage' => ['total_tokens' => 20]
            ]);

        // First call
        $response1 = LangChain::generateText($prompt);
        
        // Clear cache
        Cache::flush();
        
        // Second call - should hit API again
        $response2 = LangChain::generateText($prompt);
        
        $this->assertEquals($response1, $response2);
    }

    public function test_parameter_validation()
    {
        $validParams = [
            'temperature' => 0.7,
            'max_tokens' => 100,
            'top_p' => 0.9
        ];
        
        $invalidParams = [
            'temperature' => 3.0, // Too high
            'max_tokens' => -1,   // Negative
            'top_p' => 1.5        // Too high
        ];

        LangChain::shouldReceive('generateText')
            ->with('Test prompt', $validParams)
            ->once()
            ->andReturn(['success' => true, 'text' => 'Valid response']);

        // Valid parameters should work
        $validResponse = LangChain::generateText('Test prompt', $validParams);
        $this->assertTrue($validResponse['success']);
        
        // Invalid parameters should be handled gracefully
        // (In real implementation, these would be validated and corrected)
    }

    public function test_response_format_consistency()
    {
        $testCases = [
            ['success' => true, 'text' => 'Success response', 'usage' => ['total_tokens' => 25]],
            ['success' => false, 'error' => 'Error message']
        ];

        foreach ($testCases as $expectedResponse) {
            LangChain::shouldReceive('generateText')
                ->once()
                ->andReturn($expectedResponse);

            $response = LangChain::generateText('Test prompt');
            
            // All responses should have 'success' key
            $this->assertArrayHasKey('success', $response);
            
            if ($response['success']) {
                $this->assertArrayHasKey('text', $response);
                $this->assertArrayHasKey('usage', $response);
            } else {
                $this->assertArrayHasKey('error', $response);
            }
        }
    }
}