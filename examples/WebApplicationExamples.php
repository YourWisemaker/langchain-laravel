<?php

/**
 * Web Application Examples for LangChain Laravel
 * 
 * This file contains practical examples of how to integrate LangChain
 * into web applications, including controllers, middleware, and services.
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use LangChain\Facades\LangChain;

/**
 * Content Generation Controller
 * 
 * Handles various content generation requests through web endpoints
 */
class ContentGenerationController extends Controller
{
    /**
     * Generate blog post content
     */
    public function generateBlogPost(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'topic' => 'required|string|max:200',
            'tone' => 'sometimes|string|in:professional,casual,friendly,formal',
            'length' => 'sometimes|integer|min:100|max:2000',
            'target_audience' => 'sometimes|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $topic = $request->input('topic');
        $tone = $request->input('tone', 'professional');
        $length = $request->input('length', 500);
        $audience = $request->input('target_audience', 'general audience');

        $prompt = $this->buildBlogPostPrompt($topic, $tone, $length, $audience);

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.7,
            'max_tokens' => intval($length * 1.5)
        ]);

        if ($response['success']) {
            // Log successful generation
            Log::info('Blog post generated', [
                'topic' => $topic,
                'tokens_used' => $response['usage']['total_tokens'],
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'content' => $response['text'],
                'metadata' => [
                    'topic' => $topic,
                    'tone' => $tone,
                    'estimated_words' => str_word_count($response['text']),
                    'tokens_used' => $response['usage']['total_tokens']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate content: ' . $response['error']
        ], 500);
    }

    /**
     * Generate product descriptions
     */
    public function generateProductDescription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:100',
            'features' => 'required|array|min:1',
            'features.*' => 'string|max:200',
            'target_market' => 'sometimes|string|max:100',
            'style' => 'sometimes|string|in:persuasive,informative,creative'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $productName = $request->input('product_name');
        $features = $request->input('features');
        $targetMarket = $request->input('target_market', 'general consumers');
        $style = $request->input('style', 'persuasive');

        $featuresText = implode(', ', $features);
        
        $prompt = "Write a {$style} product description for '{$productName}' targeting {$targetMarket}. " .
                 "Key features: {$featuresText}. " .
                 "Make it engaging and highlight the benefits. Keep it between 100-200 words.";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 300
        ]);

        if ($response['success']) {
            return response()->json([
                'success' => true,
                'description' => $response['text'],
                'metadata' => [
                    'product_name' => $productName,
                    'style' => $style,
                    'word_count' => str_word_count($response['text']),
                    'tokens_used' => $response['usage']['total_tokens']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate description: ' . $response['error']
        ], 500);
    }

    /**
     * Generate email templates
     */
    public function generateEmailTemplate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:welcome,promotional,newsletter,follow_up',
            'company_name' => 'required|string|max:100',
            'recipient_type' => 'sometimes|string|max:100',
            'key_points' => 'sometimes|array',
            'call_to_action' => 'sometimes|string|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->input('type');
        $companyName = $request->input('company_name');
        $recipientType = $request->input('recipient_type', 'customers');
        $keyPoints = $request->input('key_points', []);
        $callToAction = $request->input('call_to_action', '');

        $prompt = $this->buildEmailPrompt($type, $companyName, $recipientType, $keyPoints, $callToAction);

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 400
        ]);

        if ($response['success']) {
            return response()->json([
                'success' => true,
                'email_template' => $response['text'],
                'metadata' => [
                    'type' => $type,
                    'company_name' => $companyName,
                    'tokens_used' => $response['usage']['total_tokens']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate email template: ' . $response['error']
        ], 500);
    }

    /**
     * Build blog post prompt
     */
    private function buildBlogPostPrompt(string $topic, string $tone, int $length, string $audience): string
    {
        return "Write a {$tone} blog post about '{$topic}' for {$audience}. " .
               "The post should be approximately {$length} words long. " .
               "Include an engaging introduction, well-structured main content, and a compelling conclusion. " .
               "Make it informative and valuable to the reader.";
    }

    /**
     * Build email prompt
     */
    private function buildEmailPrompt(string $type, string $companyName, string $recipientType, array $keyPoints, string $callToAction): string
    {
        $typeDescriptions = [
            'welcome' => 'welcoming new customers and introducing them to the company',
            'promotional' => 'promoting a special offer or product',
            'newsletter' => 'sharing company updates and valuable content',
            'follow_up' => 'following up with customers after a purchase or interaction'
        ];

        $description = $typeDescriptions[$type] ?? 'communicating with customers';
        $keyPointsText = empty($keyPoints) ? '' : ' Key points to include: ' . implode(', ', $keyPoints) . '.';
        $ctaText = empty($callToAction) ? '' : " Include this call-to-action: '{$callToAction}'.";

        return "Write a {$type} email template for {$companyName} aimed at {$recipientType}. " .
               "The email should be professional and engaging, focused on {$description}." .
               $keyPointsText . $ctaText .
               " Include a subject line and format the email properly.";
    }
}

/**
 * SEO Content Controller
 * 
 * Specialized controller for SEO-focused content generation
 */
class SEOContentController extends Controller
{
    /**
     * Generate meta descriptions
     */
    public function generateMetaDescription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_title' => 'required|string|max:200',
            'page_content' => 'required|string|max:2000',
            'target_keywords' => 'sometimes|array',
            'target_keywords.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pageTitle = $request->input('page_title');
        $pageContent = substr($request->input('page_content'), 0, 500); // Limit content for prompt
        $keywords = $request->input('target_keywords', []);

        $keywordsText = empty($keywords) ? '' : ' Target keywords: ' . implode(', ', $keywords) . '.';
        
        $prompt = "Write a compelling meta description for a webpage titled '{$pageTitle}'. " .
                 "Page content summary: {$pageContent}" . $keywordsText .
                 " The meta description should be 150-160 characters, engaging, and encourage clicks.";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.5,
            'max_tokens' => 100
        ]);

        if ($response['success']) {
            $metaDescription = trim($response['text']);
            $characterCount = strlen($metaDescription);
            
            return response()->json([
                'success' => true,
                'meta_description' => $metaDescription,
                'character_count' => $characterCount,
                'is_optimal_length' => $characterCount >= 150 && $characterCount <= 160,
                'tokens_used' => $response['usage']['total_tokens']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate meta description: ' . $response['error']
        ], 500);
    }

    /**
     * Generate title tags
     */
    public function generateTitleTag(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page_topic' => 'required|string|max:200',
            'primary_keyword' => 'required|string|max:100',
            'brand_name' => 'sometimes|string|max:50',
            'page_type' => 'sometimes|string|in:homepage,category,product,article,service'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $pageTopic = $request->input('page_topic');
        $primaryKeyword = $request->input('primary_keyword');
        $brandName = $request->input('brand_name', '');
        $pageType = $request->input('page_type', 'page');

        $brandText = empty($brandName) ? '' : " Include the brand name '{$brandName}'.";
        
        $prompt = "Create an SEO-optimized title tag for a {$pageType} about '{$pageTopic}'. " .
                 "The primary keyword is '{$primaryKeyword}' and should be included naturally." .
                 $brandText .
                 " The title should be 50-60 characters, compelling, and click-worthy.";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 80
        ]);

        if ($response['success']) {
            $titleTag = trim($response['text']);
            $characterCount = strlen($titleTag);
            
            return response()->json([
                'success' => true,
                'title_tag' => $titleTag,
                'character_count' => $characterCount,
                'is_optimal_length' => $characterCount >= 50 && $characterCount <= 60,
                'contains_keyword' => stripos($titleTag, $primaryKeyword) !== false,
                'tokens_used' => $response['usage']['total_tokens']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate title tag: ' . $response['error']
        ], 500);
    }
}

/**
 * Customer Support Controller
 * 
 * AI-powered customer support responses
 */
class CustomerSupportController extends Controller
{
    /**
     * Generate support response
     */
    public function generateSupportResponse(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_message' => 'required|string|max:1000',
            'category' => 'sometimes|string|in:technical,billing,general,complaint',
            'customer_tier' => 'sometimes|string|in:basic,premium,enterprise',
            'previous_context' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $customerMessage = $request->input('customer_message');
        $category = $request->input('category', 'general');
        $customerTier = $request->input('customer_tier', 'basic');
        $previousContext = $request->input('previous_context', '');

        $prompt = $this->buildSupportPrompt($customerMessage, $category, $customerTier, $previousContext);

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.3, // More consistent for support
            'max_tokens' => 300
        ]);

        if ($response['success']) {
            // Log support interaction
            Log::info('Support response generated', [
                'category' => $category,
                'customer_tier' => $customerTier,
                'tokens_used' => $response['usage']['total_tokens']
            ]);

            return response()->json([
                'success' => true,
                'response' => $response['text'],
                'metadata' => [
                    'category' => $category,
                    'customer_tier' => $customerTier,
                    'confidence_level' => $this->calculateConfidenceLevel($response['text']),
                    'tokens_used' => $response['usage']['total_tokens']
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Failed to generate support response: ' . $response['error']
        ], 500);
    }

    /**
     * Build support prompt
     */
    private function buildSupportPrompt(string $customerMessage, string $category, string $customerTier, string $previousContext): string
    {
        $contextText = empty($previousContext) ? '' : "Previous context: {$previousContext}\n\n";
        
        $tierInstructions = [
            'basic' => 'Provide helpful and professional support.',
            'premium' => 'Provide priority support with detailed explanations.',
            'enterprise' => 'Provide white-glove support with comprehensive solutions.'
        ];

        $instruction = $tierInstructions[$customerTier] ?? $tierInstructions['basic'];

        return $contextText .
               "Customer Message ({$category} category): {$customerMessage}\n\n" .
               "Generate a professional, empathetic, and helpful customer support response. " .
               $instruction .
               " Be solution-focused and include next steps if applicable.";
    }

    /**
     * Calculate confidence level based on response content
     */
    private function calculateConfidenceLevel(string $response): string
    {
        $uncertainPhrases = ['might', 'possibly', 'perhaps', 'not sure', 'unclear'];
        $confidentPhrases = ['will', 'can', 'definitely', 'certainly', 'guaranteed'];
        
        $uncertainCount = 0;
        $confidentCount = 0;
        
        foreach ($uncertainPhrases as $phrase) {
            $uncertainCount += substr_count(strtolower($response), $phrase);
        }
        
        foreach ($confidentPhrases as $phrase) {
            $confidentCount += substr_count(strtolower($response), $phrase);
        }
        
        if ($confidentCount > $uncertainCount) {
            return 'high';
        } elseif ($uncertainCount > $confidentCount) {
            return 'low';
        } else {
            return 'medium';
        }
    }
}

/**
 * Content Moderation Service
 * 
 * AI-powered content moderation and analysis
 */
class ContentModerationService
{
    /**
     * Analyze content for moderation
     */
    public function analyzeContent(string $content): array
    {
        $prompt = "Analyze the following content for potential issues. " .
                 "Check for: inappropriate language, spam, harassment, misinformation, or policy violations. " .
                 "Provide a brief analysis and a recommendation (approve/review/reject):\n\n{$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.1, // Very consistent for moderation
            'max_tokens' => 200
        ]);

        if ($response['success']) {
            $analysis = $response['text'];
            $recommendation = $this->extractRecommendation($analysis);
            
            return [
                'success' => true,
                'analysis' => $analysis,
                'recommendation' => $recommendation,
                'confidence' => $this->calculateModerationConfidence($analysis),
                'tokens_used' => $response['usage']['total_tokens']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'],
            'recommendation' => 'review' // Default to manual review on error
        ];
    }

    /**
     * Extract recommendation from analysis
     */
    private function extractRecommendation(string $analysis): string
    {
        $analysis = strtolower($analysis);
        
        if (strpos($analysis, 'reject') !== false) {
            return 'reject';
        } elseif (strpos($analysis, 'approve') !== false) {
            return 'approve';
        } else {
            return 'review';
        }
    }

    /**
     * Calculate moderation confidence
     */
    private function calculateModerationConfidence(string $analysis): float
    {
        $strongIndicators = ['clearly', 'definitely', 'obviously', 'certainly'];
        $weakIndicators = ['might', 'possibly', 'unclear', 'uncertain'];
        
        $strongCount = 0;
        $weakCount = 0;
        
        foreach ($strongIndicators as $indicator) {
            $strongCount += substr_count(strtolower($analysis), $indicator);
        }
        
        foreach ($weakIndicators as $indicator) {
            $weakCount += substr_count(strtolower($analysis), $indicator);
        }
        
        if ($strongCount > $weakCount) {
            return 0.8;
        } elseif ($weakCount > $strongCount) {
            return 0.4;
        } else {
            return 0.6;
        }
    }
}

/**
 * Translation Service
 * 
 * AI-powered content translation
 */
class TranslationService
{
    /**
     * Translate content to target language
     */
    public function translateContent(string $content, string $targetLanguage, string $sourceLanguage = 'auto'): array
    {
        $sourceText = $sourceLanguage === 'auto' ? '' : " from {$sourceLanguage}";
        
        $prompt = "Translate the following text{$sourceText} to {$targetLanguage}. " .
                 "Maintain the original tone and meaning. Provide only the translation:\n\n{$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.2, // Low temperature for accurate translation
            'max_tokens' => intval(strlen($content) * 1.5) // Estimate based on content length
        ]);

        if ($response['success']) {
            return [
                'success' => true,
                'original_text' => $content,
                'translated_text' => trim($response['text']),
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'tokens_used' => $response['usage']['total_tokens']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error']
        ];
    }

    /**
     * Detect language of content
     */
    public function detectLanguage(string $content): array
    {
        $prompt = "Detect the language of the following text. " .
                 "Respond with only the language name (e.g., 'English', 'Spanish', 'French'):\n\n{$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.1,
            'max_tokens' => 20
        ]);

        if ($response['success']) {
            return [
                'success' => true,
                'detected_language' => trim($response['text']),
                'confidence' => 0.8, // Placeholder confidence
                'tokens_used' => $response['usage']['total_tokens']
            ];
        }

        return [
            'success' => false,
            'error' => $response['error']
        ];
    }
}