<?php

/**
 * Basic Usage Examples for LangChain Laravel
 * 
 * This file demonstrates the fundamental ways to use the LangChain Laravel package.
 */

use LangChainLaravel\Facades\LangChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BasicUsageExamples
{
    /**
     * Simple text generation example
     */
    public function simpleTextGeneration()
    {
        $response = LangChain::generateText('Write a short poem about Laravel');
        
        if ($response['success']) {
            return $response['text'];
        }
        
        return 'Error: ' . $response['error'];
    }
    
    /**
     * Text generation with custom parameters
     */
    public function customParametersExample()
    {
        $response = LangChain::generateText(
            'Explain the benefits of using Laravel for web development',
            [
                'model' => 'text-davinci-003',
                'temperature' => 0.3,  // More focused, less creative
                'max_tokens' => 300,
                'top_p' => 0.9
            ]
        );
        
        return $response;
    }
    
    /**
     * Creative writing with higher temperature
     */
    public function creativeWritingExample()
    {
        $response = LangChain::generateText(
            'Write a creative story about a developer who discovers a magical PHP framework',
            [
                'temperature' => 1.2,  // More creative and random
                'max_tokens' => 500
            ]
        );
        
        return $response;
    }
    
    /**
     * Code generation example
     */
    public function codeGenerationExample()
    {
        $prompt = 'Write a Laravel controller method that validates user input and saves data to database';
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.1,  // Very focused for code generation
            'max_tokens' => 400
        ]);
        
        return $response;
    }
    
    /**
     * Question answering example
     */
    public function questionAnsweringExample()
    {
        $question = 'What are the main differences between Laravel and Symfony?';
        
        $response = LangChain::generateText($question, [
            'temperature' => 0.2,
            'max_tokens' => 350
        ]);
        
        return $response;
    }
    
    /**
     * Error handling example
     */
    public function errorHandlingExample()
    {
        try {
            $response = LangChain::generateText('Generate some text', [
                'max_tokens' => 100
            ]);
            
            if (!$response['success']) {
                // Log the error
                Log::error('LangChain generation failed', [
                    'error' => $response['error']
                ]);
                
                // Return user-friendly message
                return [
                    'success' => false,
                    'message' => 'Sorry, we could not generate the requested content at this time.'
                ];
            }
            
            return [
                'success' => true,
                'content' => $response['text'],
                'tokens_used' => $response['usage']['total_tokens']
            ];
            
        } catch (\Exception $e) {
            Log::error('Unexpected error in LangChain', [
                'exception' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ];
        }
    }
    
    /**
     * Usage monitoring example
     */
    public function usageMonitoringExample()
    {
        $response = LangChain::generateText('Explain Laravel Eloquent ORM');
        
        if ($response['success']) {
            $usage = $response['usage'];
            
            // Log usage for monitoring
            Log::info('LangChain usage', [
                'prompt_tokens' => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
                'total_tokens' => $usage['total_tokens']
            ]);
            
            // You could also store this in database for billing/analytics
            // Usage::create([
            //     'user_id' => auth()->id(),
            //     'tokens_used' => $usage['total_tokens'],
            //     'cost' => $this->calculateCost($usage['total_tokens'])
            // ]);
            
            return $response['text'];
        }
        
        return null;
    }
    
    /**
     * Batch processing example
     */
    public function batchProcessingExample(array $prompts)
    {
        $results = [];
        
        foreach ($prompts as $index => $prompt) {
            $response = LangChain::generateText($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 200
            ]);
            
            $results[$index] = [
                'prompt' => $prompt,
                'success' => $response['success'],
                'result' => $response['success'] ? $response['text'] : $response['error']
            ];
            
            // Add delay to respect rate limits
            usleep(100000); // 100ms delay
        }
        
        return $results;
    }
}