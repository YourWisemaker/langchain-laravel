<?php

namespace LangChain\AI\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DeepSeekProvider extends AbstractProvider
{
    /**
     * Generate text using DeepSeek API
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function generateText(string $prompt, array $params = []): array
    {
        try {
            $this->validateConfig();
            
            $mergedParams = $this->mergeParams($params);
            $model = $this->resolveModel($mergedParams['model'] ?? $this->getConfig('default_model'));
            
            $requestData = [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => $mergedParams['max_tokens'] ?? 1000,
                'temperature' => $mergedParams['temperature'] ?? 0.7,
                'stream' => false
            ];
            
            // Add optional parameters if provided
            if (isset($mergedParams['top_p'])) {
                $requestData['top_p'] = $mergedParams['top_p'];
            }
            
            if (isset($mergedParams['frequency_penalty'])) {
                $requestData['frequency_penalty'] = $mergedParams['frequency_penalty'];
            }
            
            if (isset($mergedParams['presence_penalty'])) {
                $requestData['presence_penalty'] = $mergedParams['presence_penalty'];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->getRequestTimeout())
            ->post($this->getConfig('base_url', 'https://api.deepseek.com') . '/chat/completions', $requestData);
            
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'DeepSeek API request failed: ' . $response->body()
                ];
            }
            
            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                return [
                    'success' => false,
                    'error' => 'Invalid response format from DeepSeek API'
                ];
            }
            
            return [
                'success' => true,
                'text' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? []
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'DeepSeek request failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get the default parameters for DeepSeek
     *
     * @return array<string, mixed>
     */
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model', 'deepseek-chat'),
            'max_tokens' => $this->getConfig('default_max_tokens', 1000),
            'temperature' => $this->getConfig('default_temperature', 0.7),
            'top_p' => 1.0,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ];
    }
    
    /**
     * Validate the DeepSeek configuration
     *
     * @throws RuntimeException
     */
    protected function validateConfig(): void
    {
        if (empty($this->getConfig('api_key'))) {
            throw new RuntimeException('DeepSeek API key is required');
        }
        
        $baseUrl = $this->getConfig('base_url', 'https://api.deepseek.com');
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('DeepSeek base URL is required and must be valid');
        }
    }
    
    /**
     * Get supported capabilities for DeepSeek provider
     *
     * @return array<string>
     */
    protected function getSupportedCapabilities(): array
    {
        return [
            'text_generation',
            'translation',
            'code_generation',
            'code_analysis',
            'agent',
            'summarization',
            'reasoning',
            'math_solving'
        ];
    }


    
    /**
     * Solve mathematical problems using DeepSeek's reasoning capabilities
     *
     * @param string $problem
     * @param array<string, mixed> $params
     * @return array{success: bool, solution?: string, steps?: array, usage?: array, error?: string}
     */
    public function solveMath(string $problem, array $params = []): array
    {
        if (!$this->supportsCapability('math_solving')) {
            return [
                'success' => false,
                'error' => 'Math solving capability not supported by this provider'
            ];
        }

        $prompt = "Solve the following mathematical problem step by step:\n\n{$problem}\n\nProvide a detailed solution with clear steps:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.1]));
        
        if ($result['success']) {
            $result['solution'] = $result['text'];
            // Try to extract steps from the response
            $steps = $this->extractStepsFromSolution($result['text']);
            if (!empty($steps)) {
                $result['steps'] = $steps;
            }
        }
        
        return $result;
    }
    
    /**
     * Perform complex reasoning tasks
     *
     * @param string $question
     * @param array<string, mixed> $context
     * @param array<string, mixed> $params
     * @return array{success: bool, reasoning?: string, conclusion?: string, usage?: array, error?: string}
     */
    public function performReasoning(string $question, array $context = [], array $params = []): array
    {
        if (!$this->supportsCapability('reasoning')) {
            return [
                'success' => false,
                'error' => 'Reasoning capability not supported by this provider'
            ];
        }

        $contextStr = empty($context) ? '' : "\n\nContext:\n" . json_encode($context, JSON_PRETTY_PRINT);
        $prompt = "Think through this question step by step and provide detailed reasoning:{$contextStr}\n\nQuestion: {$question}\n\nReasoning:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.3]));
        
        if ($result['success']) {
            $result['reasoning'] = $result['text'];
            // Try to extract conclusion
            $conclusion = $this->extractConclusionFromReasoning($result['text']);
            if ($conclusion) {
                $result['conclusion'] = $conclusion;
            }
        }
        
        return $result;
    }
    
    /**
     * Extract steps from mathematical solution
     *
     * @param string $solution
     * @return array<string>
     */
    private function extractStepsFromSolution(string $solution): array
    {
        $steps = [];
        $lines = explode("\n", $solution);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^(Step \d+|\d+\.|\d+\))/i', $line)) {
                $steps[] = $line;
            }
        }
        
        return $steps;
    }
    
    /**
     * Extract conclusion from reasoning text
     *
     * @param string $reasoning
     * @return string|null
     */
    private function extractConclusionFromReasoning(string $reasoning): ?string
    {
        $patterns = [
            '/Conclusion:(.+?)(?=\n\n|$)/is',
            '/Therefore,(.+?)(?=\n\n|$)/is',
            '/In conclusion,(.+?)(?=\n\n|$)/is'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $reasoning, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }
}