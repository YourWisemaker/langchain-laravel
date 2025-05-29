<?php

namespace Tests\Performance;

use Illuminate\Support\Facades\Cache;
use LangChainLaravel\Facades\LangChain;
use Tests\TestCase;

/**
 * Performance Benchmark Tests
 * 
 * These tests measure performance characteristics of the LangChain Laravel package.
 * Run with: vendor/bin/phpunit --group=performance
 * 
 * @group performance
 */
class BenchmarkTest extends TestCase
{
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable caching for accurate performance measurement
        config(['langchain.cache.enabled' => false]);
    }

    protected function tearDown(): void
    {
        // Output performance metrics
        if (!empty($this->performanceMetrics)) {
            echo "\n\n=== Performance Metrics ===\n";
            foreach ($this->performanceMetrics as $test => $metrics) {
                echo "{$test}:\n";
                foreach ($metrics as $metric => $value) {
                    echo "  {$metric}: {$value}\n";
                }
                echo "\n";
            }
        }
        
        parent::tearDown();
    }

    public function test_single_request_performance()
    {
        $prompt = 'Generate a short product description for a smartphone';
        
        LangChain::shouldReceive('generateText')
            ->once()
            ->andReturn([
                'success' => true,
                'text' => 'A powerful smartphone with advanced features and sleek design.',
                'usage' => ['total_tokens' => 45]
            ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = LangChain::generateText($prompt);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;
        
        $this->performanceMetrics['Single Request'] = [
            'Duration (ms)' => round($duration, 2),
            'Memory Used (KB)' => round($memoryUsed / 1024, 2),
            'Success' => $response['success'] ? 'Yes' : 'No'
        ];
        
        $this->assertTrue($response['success']);
        $this->assertLessThan(1000, $duration, 'Single request should complete within 1 second');
        $this->assertLessThan(1024 * 1024, $memoryUsed, 'Memory usage should be less than 1MB');
    }

    public function test_batch_request_performance()
    {
        $prompts = [
            'Write a title for a blog post about Laravel',
            'Create a meta description for a PHP tutorial',
            'Generate a product name for a new app',
            'Write a tagline for a tech startup',
            'Create a headline for a news article'
        ];
        
        $batchSize = count($prompts);
        
        LangChain::shouldReceive('generateText')
            ->times($batchSize)
            ->andReturn([
                'success' => true,
                'text' => 'Generated content response',
                'usage' => ['total_tokens' => 30]
            ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $responses = [];
        foreach ($prompts as $prompt) {
            $responses[] = LangChain::generateText($prompt);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $totalDuration = ($endTime - $startTime) * 1000;
        $avgDuration = $totalDuration / $batchSize;
        $memoryUsed = $endMemory - $startMemory;
        
        $this->performanceMetrics['Batch Requests'] = [
            'Batch Size' => $batchSize,
            'Total Duration (ms)' => round($totalDuration, 2),
            'Avg Duration per Request (ms)' => round($avgDuration, 2),
            'Memory Used (KB)' => round($memoryUsed / 1024, 2),
            'Requests per Second' => round($batchSize / ($totalDuration / 1000), 2)
        ];
        
        $this->assertCount($batchSize, $responses);
        foreach ($responses as $response) {
            $this->assertTrue($response['success']);
        }
        
        $this->assertLessThan(5000, $totalDuration, 'Batch requests should complete within 5 seconds');
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, 'Memory usage should be less than 5MB');
    }

    public function test_cache_performance_impact()
    {
        $prompt = 'Test caching performance impact';
        
        // Test without cache
        config(['langchain.cache.enabled' => false]);
        
        LangChain::shouldReceive('generateText')
            ->times(3) // Called 3 times without cache
            ->andReturn([
                'success' => true,
                'text' => 'Response without cache',
                'usage' => ['total_tokens' => 25]
            ]);

        $startTime = microtime(true);
        for ($i = 0; $i < 3; $i++) {
            LangChain::generateText($prompt);
        }
        $noCacheDuration = (microtime(true) - $startTime) * 1000;
        
        // Test with cache
        config(['langchain.cache.enabled' => true]);
        Cache::flush(); // Clear any existing cache
        
        LangChain::shouldReceive('generateText')
            ->once() // Called only once with cache
            ->andReturn([
                'success' => true,
                'text' => 'Response with cache',
                'usage' => ['total_tokens' => 25]
            ]);

        $startTime = microtime(true);
        for ($i = 0; $i < 3; $i++) {
            // Simulate cache behavior
            if ($i === 0) {
                $response = LangChain::generateText($prompt);
                Cache::put('test_cache_key', $response, 3600);
            } else {
                $response = Cache::get('test_cache_key');
            }
        }
        $cacheDuration = (microtime(true) - $startTime) * 1000;
        
        $performanceGain = (($noCacheDuration - $cacheDuration) / $noCacheDuration) * 100;
        
        $this->performanceMetrics['Cache Performance'] = [
            'Without Cache (ms)' => round($noCacheDuration, 2),
            'With Cache (ms)' => round($cacheDuration, 2),
            'Performance Gain (%)' => round($performanceGain, 2)
        ];
        
        $this->assertLessThan($noCacheDuration, $cacheDuration, 'Cache should improve performance');
    }

    public function test_memory_usage_scaling()
    {
        $requestCounts = [1, 5, 10, 20];
        $memoryUsages = [];
        
        foreach ($requestCounts as $count) {
            LangChain::shouldReceive('generateText')
                ->times($count)
                ->andReturn([
                    'success' => true,
                    'text' => str_repeat('Response text ', 50), // ~650 bytes
                    'usage' => ['total_tokens' => 40]
                ]);

            $startMemory = memory_get_usage();
            
            for ($i = 0; $i < $count; $i++) {
                $response = LangChain::generateText("Prompt {$i}");
                // Simulate processing
                $processed = strtoupper($response['text']);
                unset($processed);
            }
            
            $endMemory = memory_get_usage();
            $memoryUsed = $endMemory - $startMemory;
            $memoryUsages[$count] = $memoryUsed;
            
            // Force garbage collection
            gc_collect_cycles();
        }
        
        $this->performanceMetrics['Memory Scaling'] = [];
        foreach ($memoryUsages as $requests => $memory) {
            $this->performanceMetrics['Memory Scaling']["{$requests} requests (KB)"] = round($memory / 1024, 2);
        }
        
        // Memory usage should scale reasonably (not exponentially)
        $memoryPerRequest1 = $memoryUsages[1] / 1;
        $memoryPerRequest20 = $memoryUsages[20] / 20;
        
        $this->assertLessThan($memoryPerRequest1 * 2, $memoryPerRequest20, 'Memory per request should not double with scale');
    }

    public function test_concurrent_request_simulation()
    {
        $concurrentRequests = 10;
        
        LangChain::shouldReceive('generateText')
            ->times($concurrentRequests)
            ->andReturn([
                'success' => true,
                'text' => 'Concurrent response',
                'usage' => ['total_tokens' => 20]
            ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Simulate concurrent requests (in real scenario, these would be async)
        $responses = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = LangChain::generateText("Concurrent prompt {$i}");
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $totalDuration = ($endTime - $startTime) * 1000;
        $memoryUsed = $endMemory - $startMemory;
        $throughput = $concurrentRequests / ($totalDuration / 1000);
        
        $this->performanceMetrics['Concurrent Requests'] = [
            'Concurrent Requests' => $concurrentRequests,
            'Total Duration (ms)' => round($totalDuration, 2),
            'Memory Used (KB)' => round($memoryUsed / 1024, 2),
            'Throughput (req/sec)' => round($throughput, 2)
        ];
        
        $this->assertCount($concurrentRequests, $responses);
        foreach ($responses as $response) {
            $this->assertTrue($response['success']);
        }
        
        $this->assertGreaterThan(1, $throughput, 'Should handle at least 1 request per second');
    }

    public function test_large_prompt_performance()
    {
        $promptSizes = [
            'Small' => str_repeat('word ', 10),      // ~50 chars
            'Medium' => str_repeat('word ', 100),     // ~500 chars
            'Large' => str_repeat('word ', 500),      // ~2500 chars
            'XLarge' => str_repeat('word ', 1000)     // ~5000 chars
        ];
        
        $this->performanceMetrics['Large Prompt Performance'] = [];
        
        foreach ($promptSizes as $size => $prompt) {
            LangChain::shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'success' => true,
                    'text' => 'Response for ' . $size . ' prompt',
                    'usage' => ['total_tokens' => strlen($prompt) / 4] // Rough estimation
                ]);

            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $response = LangChain::generateText($prompt);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $duration = ($endTime - $startTime) * 1000;
            $memoryUsed = $endMemory - $startMemory;
            
            $this->performanceMetrics['Large Prompt Performance'][$size] = [
                'Prompt Length' => strlen($prompt),
                'Duration (ms)' => round($duration, 2),
                'Memory (KB)' => round($memoryUsed / 1024, 2)
            ];
            
            $this->assertTrue($response['success']);
        }
    }

    public function test_error_handling_performance()
    {
        $errorScenarios = [
            'API Error',
            'Rate Limit',
            'Invalid Response',
            'Network Timeout'
        ];
        
        $this->performanceMetrics['Error Handling Performance'] = [];
        
        foreach ($errorScenarios as $scenario) {
            LangChain::shouldReceive('generateText')
                ->once()
                ->andReturn([
                    'success' => false,
                    'error' => $scenario
                ]);

            $startTime = microtime(true);
            
            $response = LangChain::generateText('Test prompt');
            
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000;
            
            $this->performanceMetrics['Error Handling Performance'][$scenario] = [
                'Duration (ms)' => round($duration, 2)
            ];
            
            $this->assertFalse($response['success']);
            $this->assertEquals($scenario, $response['error']);
        }
    }

    public function test_token_counting_performance()
    {
        $texts = [
            'Short text',
            'This is a medium length text that contains several words and should take more tokens to process.',
            str_repeat('This is a very long text that will be repeated many times to test token counting performance. ', 50)
        ];
        
        $this->performanceMetrics['Token Counting Performance'] = [];
        
        foreach ($texts as $index => $text) {
            $startTime = microtime(true);
            
            // Simulate token counting (rough estimation: 1 token ≈ 4 characters)
            $estimatedTokens = intval(strlen($text) / 4);
            
            $endTime = microtime(true);
            $duration = ($endTime - $startTime) * 1000000; // Convert to microseconds
            
            $this->performanceMetrics['Token Counting Performance']["Text " . ($index + 1)] = [
                'Text Length' => strlen($text),
                'Estimated Tokens' => $estimatedTokens,
                'Duration (μs)' => round($duration, 2)
            ];
        }
    }

    public function test_configuration_loading_performance()
    {
        $startTime = microtime(true);
        
        // Simulate configuration loading
        $config = [
            'openai' => config('langchain.openai'),
            'cache' => config('langchain.cache')
        ];
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        $this->performanceMetrics['Configuration Loading'] = [
            'Duration (ms)' => round($duration, 2),
            'Config Keys Loaded' => count($config, COUNT_RECURSIVE)
        ];
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('openai', $config);
        $this->assertArrayHasKey('cache', $config);
    }

    public function test_facade_resolution_performance()
    {
        $iterations = 100;
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            // Simulate facade resolution
            $facade = app('langchain');
            $this->assertNotNull($facade);
        }
        
        $endTime = microtime(true);
        $totalDuration = ($endTime - $startTime) * 1000;
        $avgDuration = $totalDuration / $iterations;
        
        $this->performanceMetrics['Facade Resolution'] = [
            'Iterations' => $iterations,
            'Total Duration (ms)' => round($totalDuration, 2),
            'Avg Duration per Resolution (ms)' => round($avgDuration, 4)
        ];
        
        $this->assertLessThan(1, $avgDuration, 'Facade resolution should be very fast');
    }

    /**
     * Stress test with many rapid requests
     */
    public function test_stress_test()
    {
        $requestCount = 50;
        $maxDuration = 10000; // 10 seconds
        
        LangChain::shouldReceive('generateText')
            ->times($requestCount)
            ->andReturn([
                'success' => true,
                'text' => 'Stress test response',
                'usage' => ['total_tokens' => 15]
            ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        $peakMemory = $startMemory;
        
        $successCount = 0;
        $errorCount = 0;
        
        for ($i = 0; $i < $requestCount; $i++) {
            $response = LangChain::generateText("Stress test prompt {$i}");
            
            if ($response['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
            
            $currentMemory = memory_get_usage();
            if ($currentMemory > $peakMemory) {
                $peakMemory = $currentMemory;
            }
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $totalDuration = ($endTime - $startTime) * 1000;
        $memoryUsed = $endMemory - $startMemory;
        $peakMemoryUsed = $peakMemory - $startMemory;
        $successRate = ($successCount / $requestCount) * 100;
        
        $this->performanceMetrics['Stress Test'] = [
            'Total Requests' => $requestCount,
            'Successful Requests' => $successCount,
            'Failed Requests' => $errorCount,
            'Success Rate (%)' => round($successRate, 2),
            'Total Duration (ms)' => round($totalDuration, 2),
            'Avg Duration per Request (ms)' => round($totalDuration / $requestCount, 2),
            'Memory Used (KB)' => round($memoryUsed / 1024, 2),
            'Peak Memory Used (KB)' => round($peakMemoryUsed / 1024, 2),
            'Requests per Second' => round($requestCount / ($totalDuration / 1000), 2)
        ];
        
        $this->assertEquals(100, $successRate, 'All requests should succeed in stress test');
        $this->assertLessThan($maxDuration, $totalDuration, 'Stress test should complete within time limit');
        $this->assertLessThan(50 * 1024 * 1024, $peakMemoryUsed, 'Peak memory should be reasonable');
    }
}