<?php

/**
 * Middleware Examples for LangChain Laravel
 * 
 * This file contains examples of middleware that can be used to integrate
 * LangChain functionality into the Laravel request lifecycle.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use LangChainLaravel\Facades\LangChain;

/**
 * Content Moderation Middleware
 * 
 * Automatically moderates user-generated content using AI
 */
class ContentModerationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $field = 'content'): Response
    {
        // Only process POST/PUT/PATCH requests with content
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH']) || !$request->has($field)) {
            return $next($request);
        }

        $content = $request->input($field);
        
        // Skip moderation for very short content
        if (strlen($content) < 10) {
            return $next($request);
        }

        // Check cache first to avoid re-moderating identical content
        $cacheKey = 'moderation_' . md5($content);
        $moderationResult = Cache::remember($cacheKey, 3600, function () use ($content) {
            return $this->moderateContent($content);
        });

        // Handle moderation result
        switch ($moderationResult['recommendation']) {
            case 'reject':
                return response()->json([
                    'error' => 'Content violates community guidelines',
                    'details' => 'Your content has been flagged for review. Please ensure it follows our community standards.'
                ], 422);

            case 'review':
                // Flag for manual review but allow through
                $this->flagForReview($request, $content, $moderationResult);
                break;

            case 'approve':
            default:
                // Content is approved, continue normally
                break;
        }

        // Log moderation activity
        Log::info('Content moderated', [
            'user_id' => auth()->id(),
            'recommendation' => $moderationResult['recommendation'],
            'confidence' => $moderationResult['confidence'] ?? 0,
            'content_length' => strlen($content)
        ]);

        return $next($request);
    }

    /**
     * Moderate content using LangChain
     */
    private function moderateContent(string $content): array
    {
        $prompt = "Analyze this user-generated content for potential violations. " .
                 "Check for: hate speech, harassment, spam, inappropriate content, or policy violations. " .
                 "Respond with: APPROVE (safe content), REVIEW (questionable content), or REJECT (clear violation). " .
                 "Then provide a brief reason.\n\nContent: {$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.1,
            'max_tokens' => 100
        ]);

        if (!$response['success']) {
            // Default to review if AI fails
            return [
                'recommendation' => 'review',
                'reason' => 'AI moderation unavailable',
                'confidence' => 0.5
            ];
        }

        $analysis = strtolower($response['text']);
        
        if (strpos($analysis, 'reject') !== false) {
            $recommendation = 'reject';
            $confidence = 0.9;
        } elseif (strpos($analysis, 'approve') !== false) {
            $recommendation = 'approve';
            $confidence = 0.8;
        } else {
            $recommendation = 'review';
            $confidence = 0.6;
        }

        return [
            'recommendation' => $recommendation,
            'reason' => $response['text'],
            'confidence' => $confidence,
            'tokens_used' => $response['usage']['total_tokens'] ?? 0
        ];
    }

    /**
     * Flag content for manual review
     */
    private function flagForReview(Request $request, string $content, array $moderationResult): void
    {
        // Store flagged content for admin review
        \DB::table('flagged_content')->insert([
            'user_id' => auth()->id(),
            'content' => $content,
            'route' => $request->route()->getName(),
            'moderation_reason' => $moderationResult['reason'] ?? 'Flagged for review',
            'confidence' => $moderationResult['confidence'] ?? 0,
            'created_at' => now()
        ]);
    }
}

/**
 * Smart Rate Limiting Middleware
 * 
 * Uses AI to analyze request patterns and apply dynamic rate limiting
 */
class SmartRateLimitingMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);
        
        // Get current request count
        $attempts = RateLimiter::attempts($key);
        
        // If approaching limit, analyze request pattern
        if ($attempts > ($maxAttempts * 0.8)) {
            $suspiciousActivity = $this->analyzeRequestPattern($request, $attempts);
            
            if ($suspiciousActivity['is_suspicious']) {
                // Apply stricter rate limiting for suspicious activity
                $adjustedLimit = intval($maxAttempts * 0.5);
                
                if ($attempts >= $adjustedLimit) {
                    Log::warning('Suspicious activity detected', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'attempts' => $attempts,
                        'analysis' => $suspiciousActivity['analysis']
                    ]);
                    
                    return response()->json([
                        'error' => 'Rate limit exceeded due to suspicious activity',
                        'retry_after' => RateLimiter::availableIn($key)
                    ], 429);
                }
            }
        }
        
        // Apply normal rate limiting
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $maxAttempts - RateLimiter::attempts($key)),
            'X-RateLimit-Reset' => now()->addMinutes($decayMinutes)->timestamp
        ]);
        
        return $response;
    }

    /**
     * Analyze request pattern for suspicious activity
     */
    private function analyzeRequestPattern(Request $request, int $attempts): array
    {
        $userAgent = $request->userAgent();
        $requestData = $request->all();
        $requestPath = $request->path();
        
        // Build analysis prompt
        $prompt = "Analyze this web request pattern for suspicious activity:\n" .
                 "- Attempts in last minute: {$attempts}\n" .
                 "- User Agent: {$userAgent}\n" .
                 "- Request Path: {$requestPath}\n" .
                 "- Has Request Data: " . (empty($requestData) ? 'No' : 'Yes') . "\n\n" .
                 "Determine if this looks like: bot traffic, scraping, abuse, or normal usage. " .
                 "Respond with SUSPICIOUS or NORMAL, followed by a brief reason.";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.2,
            'max_tokens' => 80
        ]);

        if (!$response['success']) {
            return [
                'is_suspicious' => false,
                'analysis' => 'Analysis unavailable',
                'confidence' => 0
            ];
        }

        $analysis = $response['text'];
        $isSuspicious = stripos($analysis, 'suspicious') !== false;
        
        return [
            'is_suspicious' => $isSuspicious,
            'analysis' => $analysis,
            'confidence' => $isSuspicious ? 0.8 : 0.7
        ];
    }

    /**
     * Resolve request signature for rate limiting
     */
    private function resolveRequestSignature(Request $request): string
    {
        return sha1(
            $request->method() .
            '|' . $request->server('SERVER_NAME') .
            '|' . $request->path() .
            '|' . $request->ip()
        );
    }
}

/**
 * Auto-Translation Middleware
 * 
 * Automatically translates response content based on user preferences
 */
class AutoTranslationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only translate JSON responses
        if (!$this->shouldTranslate($request, $response)) {
            return $response;
        }
        
        $targetLanguage = $this->getTargetLanguage($request);
        
        if (!$targetLanguage || $targetLanguage === 'en') {
            return $response;
        }
        
        $originalData = json_decode($response->getContent(), true);
        $translatedData = $this->translateResponseData($originalData, $targetLanguage);
        
        if ($translatedData) {
            $response->setContent(json_encode($translatedData));
            $response->headers->set('Content-Language', $targetLanguage);
            $response->headers->set('X-Translated', 'true');
        }
        
        return $response;
    }

    /**
     * Determine if response should be translated
     */
    private function shouldTranslate(Request $request, Response $response): bool
    {
        // Only translate successful JSON responses
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        
        $contentType = $response->headers->get('Content-Type', '');
        if (strpos($contentType, 'application/json') === false) {
            return false;
        }
        
        // Skip if already translated
        if ($response->headers->has('X-Translated')) {
            return false;
        }
        
        return true;
    }

    /**
     * Get target language from request
     */
    private function getTargetLanguage(Request $request): ?string
    {
        // Check user preference first
        if (auth()->check() && auth()->user()->preferred_language) {
            return auth()->user()->preferred_language;
        }
        
        // Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $languages = explode(',', $acceptLanguage);
            $primaryLanguage = trim(explode(';', $languages[0])[0]);
            
            // Map common language codes
            $languageMap = [
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'zh' => 'Chinese',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'ru' => 'Russian',
                'ar' => 'Arabic'
            ];
            
            return $languageMap[$primaryLanguage] ?? null;
        }
        
        return null;
    }

    /**
     * Translate response data
     */
    private function translateResponseData(array $data, string $targetLanguage): ?array
    {
        $translatableFields = ['message', 'description', 'content', 'text', 'title'];
        $translated = false;
        
        foreach ($data as $key => $value) {
            if (is_string($value) && in_array($key, $translatableFields) && strlen($value) > 5) {
                $cacheKey = 'translation_' . md5($value . $targetLanguage);
                
                $translatedValue = Cache::remember($cacheKey, 3600, function () use ($value, $targetLanguage) {
                    return $this->translateText($value, $targetLanguage);
                });
                
                if ($translatedValue) {
                    $data[$key] = $translatedValue;
                    $translated = true;
                }
            } elseif (is_array($value)) {
                $translatedSubData = $this->translateResponseData($value, $targetLanguage);
                if ($translatedSubData) {
                    $data[$key] = $translatedSubData;
                    $translated = true;
                }
            }
        }
        
        return $translated ? $data : null;
    }

    /**
     * Translate text using LangChain
     */
    private function translateText(string $text, string $targetLanguage): ?string
    {
        $prompt = "Translate the following text to {$targetLanguage}. " .
                 "Maintain the original tone and meaning. Provide only the translation:\n\n{$text}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.2,
            'max_tokens' => intval(strlen($text) * 1.5)
        ]);

        return $response['success'] ? trim($response['text']) : null;
    }
}

/**
 * Content Enhancement Middleware
 * 
 * Automatically enhances content with AI-generated improvements
 */
class ContentEnhancementMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $field = 'content'): Response
    {
        // Only enhance content on specific routes
        if (!$this->shouldEnhanceContent($request)) {
            return $next($request);
        }
        
        if ($request->has($field) && $request->input('enhance_content') === 'true') {
            $originalContent = $request->input($field);
            $enhancedContent = $this->enhanceContent($originalContent);
            
            if ($enhancedContent) {
                $request->merge([$field => $enhancedContent]);
                
                // Store original content for comparison
                $request->merge(['original_content' => $originalContent]);
            }
        }
        
        return $next($request);
    }

    /**
     * Determine if content should be enhanced
     */
    private function shouldEnhanceContent(Request $request): bool
    {
        $enhanceableRoutes = [
            'posts.store',
            'posts.update',
            'articles.store',
            'articles.update',
            'comments.store'
        ];
        
        return in_array($request->route()->getName(), $enhanceableRoutes);
    }

    /**
     * Enhance content using AI
     */
    private function enhanceContent(string $content): ?string
    {
        // Skip enhancement for very short content
        if (strlen($content) < 50) {
            return null;
        }
        
        $prompt = "Improve the following content by:\n" .
                 "- Fixing grammar and spelling errors\n" .
                 "- Improving clarity and readability\n" .
                 "- Enhancing flow and structure\n" .
                 "- Maintaining the original meaning and tone\n\n" .
                 "Original content: {$content}\n\n" .
                 "Provide only the improved version:";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.3,
            'max_tokens' => intval(strlen($content) * 1.5)
        ]);

        if ($response['success']) {
            $enhancedContent = trim($response['text']);
            
            // Log enhancement activity
            Log::info('Content enhanced', [
                'original_length' => strlen($content),
                'enhanced_length' => strlen($enhancedContent),
                'tokens_used' => $response['usage']['total_tokens'] ?? 0
            ]);
            
            return $enhancedContent;
        }
        
        return null;
    }
}

/**
 * SEO Optimization Middleware
 * 
 * Automatically optimizes content for SEO
 */
class SEOOptimizationMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only optimize HTML responses
        if (!$this->shouldOptimize($response)) {
            return $response;
        }
        
        $content = $response->getContent();
        $optimizedContent = $this->optimizeContent($content);
        
        if ($optimizedContent) {
            $response->setContent($optimizedContent);
            $response->headers->set('X-SEO-Optimized', 'true');
        }
        
        return $response;
    }

    /**
     * Determine if content should be optimized
     */
    private function shouldOptimize(Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        
        $contentType = $response->headers->get('Content-Type', '');
        return strpos($contentType, 'text/html') !== false;
    }

    /**
     * Optimize content for SEO
     */
    private function optimizeContent(string $content): ?string
    {
        // Extract title and meta description
        preg_match('/<title>(.*?)<\/title>/i', $content, $titleMatches);
        preg_match('/<meta name="description" content="(.*?)"/i', $content, $descMatches);
        
        $currentTitle = $titleMatches[1] ?? '';
        $currentDescription = $descMatches[1] ?? '';
        
        if (empty($currentTitle) && empty($currentDescription)) {
            return null;
        }
        
        // Get page content for context
        $bodyContent = $this->extractBodyContent($content);
        
        if (empty($currentTitle)) {
            $optimizedTitle = $this->generateTitle($bodyContent);
            if ($optimizedTitle) {
                $content = preg_replace('/<title>.*?<\/title>/i', "<title>{$optimizedTitle}</title>", $content);
            }
        }
        
        if (empty($currentDescription)) {
            $optimizedDescription = $this->generateMetaDescription($bodyContent);
            if ($optimizedDescription) {
                $metaTag = "<meta name=\"description\" content=\"{$optimizedDescription}\">";
                $content = preg_replace('/<head>/i', "<head>\n{$metaTag}", $content);
            }
        }
        
        return $content;
    }

    /**
     * Extract body content from HTML
     */
    private function extractBodyContent(string $html): string
    {
        preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches);
        $bodyHtml = $matches[1] ?? '';
        
        // Strip HTML tags and get plain text
        $plainText = strip_tags($bodyHtml);
        $plainText = preg_replace('/\s+/', ' ', $plainText);
        
        return trim(substr($plainText, 0, 1000)); // Limit for prompt
    }

    /**
     * Generate optimized title
     */
    private function generateTitle(string $content): ?string
    {
        $prompt = "Create an SEO-optimized title tag for a webpage with this content. " .
                 "The title should be 50-60 characters, compelling, and descriptive:\n\n{$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 80
        ]);

        return $response['success'] ? trim($response['text']) : null;
    }

    /**
     * Generate meta description
     */
    private function generateMetaDescription(string $content): ?string
    {
        $prompt = "Create an SEO-optimized meta description for a webpage with this content. " .
                 "The description should be 150-160 characters and encourage clicks:\n\n{$content}";

        $response = LangChain::generateText($prompt, [
            'temperature' => 0.4,
            'max_tokens' => 100
        ]);

        return $response['success'] ? trim($response['text']) : null;
    }
}