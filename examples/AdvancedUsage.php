<?php

/**
 * Advanced Usage Examples for LangChain Laravel
 * 
 * This file demonstrates advanced patterns and integrations.
 */

use LangChain\Facades\LangChain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvancedUsageExamples
{
    /**
     * Content summarization with caching
     */
    public function summarizeContent(string $content, int $maxLength = 200)
    {
        $cacheKey = 'summary_' . md5($content . $maxLength);
        
        return Cache::remember($cacheKey, 3600, function () use ($content, $maxLength) {
            $prompt = "Summarize the following content in approximately {$maxLength} characters:\n\n{$content}";
            
            $response = LangChain::generateText($prompt, [
                'temperature' => 0.3,
                'max_tokens' => intval($maxLength / 3) // Rough token estimation
            ]);
            
            return $response['success'] ? $response['text'] : null;
        });
    }
    
    /**
     * Multi-language content generation
     */
    public function generateMultiLanguageContent(string $topic, array $languages = ['en', 'es', 'fr'])
    {
        $results = [];
        
        foreach ($languages as $lang) {
            $languageNames = [
                'en' => 'English',
                'es' => 'Spanish', 
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian'
            ];
            
            $languageName = $languageNames[$lang] ?? $lang;
            $prompt = "Write about {$topic} in {$languageName}. Keep it informative and engaging.";
            
            $response = LangChain::generateText($prompt, [
                'temperature' => 0.7,
                'max_tokens' => 300
            ]);
            
            $results[$lang] = [
                'language' => $languageName,
                'content' => $response['success'] ? $response['text'] : null,
                'error' => $response['success'] ? null : $response['error']
            ];
        }
        
        return $results;
    }
    
    /**
     * Dynamic prompt templating
     */
    public function generateWithTemplate(string $template, array $variables)
    {
        // Replace template variables
        $prompt = $template;
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{' . $key . '}', $value, $prompt);
        }
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 400
        ]);
        
        return [
            'template' => $template,
            'variables' => $variables,
            'final_prompt' => $prompt,
            'result' => $response
        ];
    }
    
    /**
     * Content moderation and filtering
     */
    public function moderateContent(string $content)
    {
        $moderationPrompt = "Analyze the following content for inappropriate material, hate speech, or harmful content. Respond with 'SAFE' if the content is appropriate, or 'UNSAFE' with a brief explanation if not:\n\n{$content}";
        
        $response = LangChain::generateText($moderationPrompt, [
            'temperature' => 0.1,
            'max_tokens' => 100
        ]);
        
        if (!$response['success']) {
            return ['safe' => false, 'reason' => 'Moderation check failed'];
        }
        
        $result = trim($response['text']);
        $isSafe = stripos($result, 'SAFE') === 0;
        
        return [
            'safe' => $isSafe,
            'analysis' => $result,
            'reason' => $isSafe ? null : $result
        ];
    }
    
    /**
     * Conversational AI with context
     */
    public function conversationalResponse(string $userMessage, array $conversationHistory = [])
    {
        // Build context from conversation history
        $context = "";
        foreach ($conversationHistory as $exchange) {
            $context .= "User: {$exchange['user']}\n";
            $context .= "Assistant: {$exchange['assistant']}\n";
        }
        
        $prompt = $context . "User: {$userMessage}\nAssistant:";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 250,
            'stop' => ['\nUser:', '\nAssistant:']
        ]);
        
        if ($response['success']) {
            // Store the conversation for future context
            $conversationHistory[] = [
                'user' => $userMessage,
                'assistant' => trim($response['text'])
            ];
        }
        
        return [
            'response' => $response['success'] ? trim($response['text']) : 'Sorry, I could not process your message.',
            'conversation_history' => $conversationHistory,
            'tokens_used' => $response['success'] ? $response['usage']['total_tokens'] : 0
        ];
    }
    
    /**
     * SEO content optimization
     */
    public function optimizeForSEO(string $content, array $keywords, string $targetAudience = 'general')
    {
        $keywordList = implode(', ', $keywords);
        
        $prompt = "Optimize the following content for SEO targeting these keywords: {$keywordList}. Target audience: {$targetAudience}. Maintain readability and natural flow:\n\n{$content}";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 600
        ]);
        
        return [
            'original_content' => $content,
            'optimized_content' => $response['success'] ? $response['text'] : null,
            'target_keywords' => $keywords,
            'target_audience' => $targetAudience,
            'success' => $response['success'],
            'error' => $response['success'] ? null : $response['error']
        ];
    }
    
    /**
     * Code review and suggestions
     */
    public function reviewCode(string $code, string $language = 'php')
    {
        $prompt = "Review the following {$language} code and provide suggestions for improvement, potential bugs, and best practices:\n\n```{$language}\n{$code}\n```";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.2,
            'max_tokens' => 500
        ]);
        
        return [
            'code' => $code,
            'language' => $language,
            'review' => $response['success'] ? $response['text'] : null,
            'success' => $response['success']
        ];
    }
    
    /**
     * A/B testing for different prompts
     */
    public function abTestPrompts(array $prompts, string $baseContent)
    {
        $results = [];
        
        foreach ($prompts as $version => $prompt) {
            $fullPrompt = str_replace('{content}', $baseContent, $prompt);
            
            $response = LangChain::generateText($fullPrompt, [
                'temperature' => 0.7,
                'max_tokens' => 300
            ]);
            
            $results[$version] = [
                'prompt' => $fullPrompt,
                'result' => $response['success'] ? $response['text'] : null,
                'tokens_used' => $response['success'] ? $response['usage']['total_tokens'] : 0,
                'success' => $response['success'],
                'timestamp' => now()
            ];
        }
        
        return $results;
    }
    
    /**
     * Content personalization based on user data
     */
    public function personalizeContent(string $baseContent, array $userProfile)
    {
        $profileString = '';
        foreach ($userProfile as $key => $value) {
            $profileString .= "{$key}: {$value}, ";
        }
        $profileString = rtrim($profileString, ', ');
        
        $prompt = "Personalize the following content for a user with this profile ({$profileString}). Keep the core message but adjust tone, examples, and references:\n\n{$baseContent}";
        
        $response = LangChain::generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 400
        ]);
        
        return [
            'original_content' => $baseContent,
            'user_profile' => $userProfile,
            'personalized_content' => $response['success'] ? $response['text'] : null,
            'success' => $response['success']
        ];
    }
}