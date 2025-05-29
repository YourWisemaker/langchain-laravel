<?php

/**
 * Custom Provider Example for LangChain Laravel
 * 
 * This file demonstrates how to create and register a custom AI provider
 * with the LangChain Laravel package.
 */

use LangChainLaravel\AI\Providers\AbstractProvider;
use LangChainLaravel\Facades\LangChain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Example Custom Provider Implementation
 * 
 * This example shows how to create a custom provider for a hypothetical AI service.
 * You can adapt this pattern for any AI provider that has an HTTP API.
 */
class CustomAIProvider extends AbstractProvider
{
    /**
     * Generate text using the custom AI provider
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
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->getRequestTimeout())
            ->post($this->getConfig('base_url') . '/v1/chat/completions', [
                'model' => $mergedParams['model'] ?? $this->getConfig('default_model'),
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $mergedParams['max_tokens'] ?? 1000,
                'temperature' => $mergedParams['temperature'] ?? 0.7,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'text' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? [],
                ];
            }
            
            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->body(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Custom AI Provider Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get the default parameters for this provider
     *
     * @return array<string, mixed>
     */
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model', 'custom-model-v1'),
            'max_tokens' => $this->getConfig('default_max_tokens', 1000),
            'temperature' => $this->getConfig('default_temperature', 0.7),
        ];
    }
    
    /**
     * Get supported capabilities for this provider
     *
     * @return array<string>
     */
    protected function getSupportedCapabilities(): array
    {
        return [
            'text_generation',
            'translation',
            'summarization',
            // Add custom capabilities specific to your provider
            'custom_feature_1',
            'custom_feature_2',
        ];
    }
    
    /**
     * Validate the provider configuration
     *
     * @throws \RuntimeException
     */
    protected function validateConfig(): void
    {
        if (empty($this->getConfig('api_key'))) {
            throw new \RuntimeException('Custom AI Provider API key is required');
        }
        
        if (empty($this->getConfig('base_url'))) {
            throw new \RuntimeException('Custom AI Provider base URL is required');
        }
    }
    
    /**
     * Custom method specific to this provider
     * 
     * @param string $text
     * @param array $params
     * @return array
     */
    public function customFeature(string $text, array $params = []): array
    {
        if (!$this->supportsCapability('custom_feature_1')) {
            return [
                'success' => false,
                'error' => 'Custom feature not supported'
            ];
        }
        
        // Implement your custom feature logic here
        $prompt = "Perform custom analysis on: {$text}";
        
        return $this->generateText($prompt, $params);
    }
}

/**
 * Usage Examples
 */
class CustomProviderUsage
{
    /**
     * Example 1: Register and use a custom provider
     */
    public function registerCustomProvider()
    {
        // Register the custom provider
        LangChain::registerProvider('custom-ai', CustomAIProvider::class);
        
        // Verify registration
        $customProviders = LangChain::getCustomProviders();
        echo "Registered custom providers: " . implode(', ', array_keys($customProviders)) . "\n";
        
        // Use the custom provider
        $result = LangChain::generateText(
            'Hello from my custom AI provider!',
            ['temperature' => 0.5],
            'custom-ai'
        );
        
        if ($result['success']) {
            echo "Custom AI Response: " . $result['text'] . "\n";
        } else {
            echo "Error: " . $result['error'] . "\n";
        }
    }
    
    /**
     * Example 2: Use custom provider capabilities
     */
    public function useCustomCapabilities()
    {
        // Get the custom provider instance
        $provider = LangChain::getProvider('custom-ai');
        
        // Check capabilities
        $capabilities = $provider->getSupportedCapabilitiesList();
        echo "Custom provider capabilities: " . implode(', ', $capabilities) . "\n";
        
        // Use custom feature
        if ($provider->supportsCapability('custom_feature_1')) {
            $result = $provider->customFeature('Analyze this text with custom logic');
            
            if ($result['success']) {
                echo "Custom feature result: " . $result['text'] . "\n";
            }
        }
    }
    
    /**
     * Example 3: Provider fallback with custom providers
     */
    public function providerFallbackExample()
    {
        $providers = ['custom-ai', 'openai', 'claude'];
        $prompt = 'Generate a creative story about AI';
        
        foreach ($providers as $providerName) {
            try {
                $result = LangChain::generateText($prompt, [], $providerName);
                
                if ($result['success']) {
                    echo "Success with provider: {$providerName}\n";
                    echo "Response: " . $result['text'] . "\n";
                    break;
                }
            } catch (\Exception $e) {
                echo "Provider {$providerName} failed: " . $e->getMessage() . "\n";
                continue;
            }
        }
    }
    
    /**
     * Example 4: Dynamic provider registration from configuration
     */
    public function dynamicProviderRegistration()
    {
        // Example configuration for multiple custom providers
        $customProviders = [
            'custom-ai-1' => CustomAIProvider::class,
            'custom-ai-2' => AnotherCustomProvider::class,
            // Add more custom providers as needed
        ];
        
        // Register all custom providers
        foreach ($customProviders as $name => $class) {
            if (class_exists($class)) {
                LangChain::registerProvider($name, $class);
                echo "Registered custom provider: {$name}\n";
            }
        }
        
        // List all available providers (built-in + custom)
        $allProviders = LangChain::getAvailableProviders();
        echo "All available providers: " . implode(', ', $allProviders) . "\n";
    }
}

/**
 * Another example custom provider for demonstration
 */
class AnotherCustomProvider extends AbstractProvider
{
    public function generateText(string $prompt, array $params = []): array
    {
        // Implement your provider logic here
        return [
            'success' => true,
            'text' => 'Response from another custom provider: ' . $prompt,
        ];
    }
    
    protected function getDefaultParams(): array
    {
        return ['temperature' => 0.8];
    }
    
    protected function validateConfig(): void
    {
        // Implement validation logic
    }
}