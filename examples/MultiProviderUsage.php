<?php

/**
 * Multi-Provider Usage Examples for LangChain Laravel
 * 
 * This file demonstrates how to use different AI providers (OpenAI, Claude, Llama)
 * with the LangChain Laravel package.
 */

use LangChainLaravel\Facades\LangChain;
use Illuminate\Support\Facades\Log;

class MultiProviderUsage
{
    /**
     * Example 1: Using the default provider (set in config)
     */
    public function useDefaultProvider()
    {
        $prompt = "Write a short poem about Laravel.";
        
        $result = LangChain::generateText($prompt, [
            'max_tokens' => 100,
            'temperature' => 0.8
        ]);
        
        if ($result['success']) {
            echo "Generated with default provider: " . $result['text'];
        } else {
            echo "Error: " . $result['error'];
        }
    }
    
    /**
     * Example 2: Explicitly using OpenAI
     */
    public function useOpenAI()
    {
        $prompt = "Explain the benefits of using Laravel for web development.";
        
        $result = LangChain::openai($prompt, [
            'model' => 'gpt-4',
            'max_tokens' => 200,
            'temperature' => 0.7
        ]);
        
        if ($result['success']) {
            echo "OpenAI Response: " . $result['text'];
            echo "Tokens used: " . $result['usage']['total_tokens'];
        }
    }
    
    /**
     * Example 3: Using Claude for creative writing
     */
    public function useClaude()
    {
        $prompt = "Write a creative story about a developer who discovers a magical programming language.";
        
        $result = LangChain::claude($prompt, [
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 500,
            'temperature' => 0.9
        ]);
        
        if ($result['success']) {
            echo "Claude Response: " . $result['text'];
            echo "Input tokens: " . $result['usage']['input_tokens'];
            echo "Output tokens: " . $result['usage']['output_tokens'];
        }
    }
    
    /**
     * Example 4: Using Llama for code generation
     */
    public function useLlama()
    {
        $prompt = "Generate a PHP function that validates an email address and returns detailed validation results.";
        
        $result = LangChain::llama($prompt, [
            'model' => 'meta-llama/Llama-2-70b-chat-hf',
            'max_tokens' => 300,
            'temperature' => 0.3
        ]);
        
        if ($result['success']) {
            echo "Llama Response: " . $result['text'];
        }
    }
    
    /**
     * Example 5: Switching providers dynamically
     */
    public function switchProviders()
    {
        $prompt = "What are the advantages of microservices architecture?";
        
        // Get current default provider
        $currentProvider = LangChain::getDefaultProvider();
        echo "Current default provider: " . $currentProvider;
        
        // Try different providers for the same prompt
        $providers = ['openai', 'claude', 'llama'];
        
        foreach ($providers as $provider) {
            if (LangChain::isValidProvider($provider)) {
                $result = LangChain::generateText($prompt, [
                    'max_tokens' => 150,
                    'temperature' => 0.7
                ], $provider);
                
                if ($result['success']) {
                    echo "\n{$provider} response: " . substr($result['text'], 0, 100) . "...";
                } else {
                    echo "\n{$provider} error: " . $result['error'];
                }
            }
        }
    }
    
    /**
     * Example 6: Using model aliases
     */
    public function useModelAliases()
    {
        // Using aliases defined in config
        $prompts = [
            'gpt4' => "Analyze the performance implications of using Redis vs Memcached.",
            'claude' => "Write a technical blog post introduction about API design.",
            'llama2' => "Create a bash script that monitors server resources."
        ];
        
        foreach ($prompts as $modelAlias => $prompt) {
            $result = LangChain::generateText($prompt, [
                'model' => $modelAlias,
                'max_tokens' => 200
            ]);
            
            if ($result['success']) {
                echo "\nUsing {$modelAlias}: " . substr($result['text'], 0, 100) . "...";
            }
        }
    }
    
    /**
     * Example 7: Provider-specific error handling
     */
    public function handleProviderErrors()
    {
        $prompt = "Generate a complex algorithm explanation.";
        
        $providers = LangChain::getAvailableProviders();
        
        foreach ($providers as $provider) {
            try {
                $result = LangChain::generateText($prompt, [
                    'max_tokens' => 100
                ], $provider);
                
                if ($result['success']) {
                    echo "\n✅ {$provider}: Success";
                    break; // Use first successful provider
                } else {
                    echo "\n❌ {$provider}: " . $result['error'];
                }
            } catch (\Exception $e) {
                echo "\n💥 {$provider}: Exception - " . $e->getMessage();
            }
        }
    }
    
    /**
     * Example 8: Comparing responses from different providers
     */
    public function compareProviders()
    {
        $prompt = "Explain the concept of dependency injection in simple terms.";
        $responses = [];
        
        $providers = ['openai', 'claude', 'llama'];
        
        foreach ($providers as $provider) {
            if (LangChain::isValidProvider($provider)) {
                $result = LangChain::generateText($prompt, [
                    'max_tokens' => 150,
                    'temperature' => 0.5
                ], $provider);
                
                if ($result['success']) {
                    $responses[$provider] = [
                        'text' => $result['text'],
                        'tokens' => $result['usage']['total_tokens'] ?? 0,
                        'length' => strlen($result['text'])
                    ];
                }
            }
        }
        
        // Display comparison
        foreach ($responses as $provider => $data) {
            echo "\n=== {$provider} ===";
            echo "\nLength: {$data['length']} chars";
            echo "\nTokens: {$data['tokens']}";
            echo "\nResponse: " . substr($data['text'], 0, 100) . "...\n";
        }
    }
    
    /**
     * Example 9: Fallback provider strategy
     */
    public function useWithFallback()
    {
        $prompt = "Create a JSON schema for a user profile.";
        $preferredProviders = ['claude', 'openai', 'llama'];
        
        foreach ($preferredProviders as $provider) {
            if (!LangChain::isValidProvider($provider)) {
                continue;
            }
            
            $result = LangChain::generateText($prompt, [
                'max_tokens' => 200,
                'temperature' => 0.3
            ], $provider);
            
            if ($result['success']) {
                echo "Successfully used {$provider}: " . $result['text'];
                Log::info("LangChain: Used {$provider} as fallback");
                return $result;
            } else {
                Log::warning("LangChain: {$provider} failed: " . $result['error']);
            }
        }
        
        throw new \Exception('All providers failed');
    }
    
    /**
     * Example 10: Provider-specific optimizations
     */
    public function optimizeForProvider()
    {
        $basePrompt = "Write a function to calculate fibonacci numbers";
        
        // OpenAI - good for general programming
        $openaiResult = LangChain::openai($basePrompt . " in PHP with proper documentation.", [
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.2
        ]);
        
        // Claude - good for detailed explanations
        $claudeResult = LangChain::claude($basePrompt . " with step-by-step explanation.", [
            'model' => 'claude-3-sonnet-20240229',
            'temperature' => 0.4
        ]);
        
        // Llama - good for performance-focused code
        $llamaResult = LangChain::llama($basePrompt . " optimized for performance.", [
            'model' => 'meta-llama/Llama-2-70b-chat-hf',
            'temperature' => 0.1
        ]);
        
        return [
            'openai' => $openaiResult,
            'claude' => $claudeResult,
            'llama' => $llamaResult
        ];
    }
}