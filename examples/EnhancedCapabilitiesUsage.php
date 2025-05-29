<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LangChain\Facades\LangChain;

/**
 * Enhanced Capabilities Usage Examples
 * 
 * This example demonstrates the enhanced AI capabilities including:
 * - DeepSeek provider support
 * - Multi-language translation
 * - Code generation and analysis
 * - AI agent functionality
 * - Text summarization
 * - Mathematical problem solving
 * - Complex reasoning tasks
 */

echo "=== Enhanced LangChain Laravel Capabilities Demo ===\n\n";

// 1. DeepSeek Provider Examples
echo "1. DeepSeek Provider Examples\n";
echo "----------------------------\n";

// Basic text generation with DeepSeek
$deepseekResult = LangChain::deepseek('Explain quantum computing in simple terms');
if ($deepseekResult['success']) {
    echo "DeepSeek Response: " . substr($deepseekResult['text'], 0, 200) . "...\n\n";
} else {
    echo "DeepSeek Error: " . $deepseekResult['error'] . "\n\n";
}

// Mathematical problem solving with DeepSeek
$mathProblem = "Solve: If a train travels 120 km in 2 hours, and then 180 km in 3 hours, what is the average speed for the entire journey?";
$mathResult = LangChain::getProvider('deepseek')->solveMath($mathProblem);
if ($mathResult['success']) {
    echo "Math Solution: " . $mathResult['solution'] . "\n\n";
    if (isset($mathResult['steps'])) {
        echo "Steps:\n";
        foreach ($mathResult['steps'] as $step) {
            echo "- " . $step . "\n";
        }
        echo "\n";
    }
}

// Complex reasoning with DeepSeek
$reasoningQuestion = "Should artificial intelligence be regulated by governments?";
$reasoningContext = [
    'current_ai_capabilities' => 'Advanced language models, image generation, code writing',
    'concerns' => 'Job displacement, misinformation, privacy',
    'benefits' => 'Medical research, education, productivity'
];
$reasoningResult = LangChain::getProvider('deepseek')->performReasoning($reasoningQuestion, $reasoningContext);
if ($reasoningResult['success']) {
    echo "Reasoning Analysis: " . substr($reasoningResult['reasoning'], 0, 300) . "...\n";
    if (isset($reasoningResult['conclusion'])) {
        echo "Conclusion: " . $reasoningResult['conclusion'] . "\n\n";
    }
}

// 2. Multi-Language Translation
echo "2. Multi-Language Translation\n";
echo "----------------------------\n";

$textToTranslate = "Hello, how are you today? I hope you're having a wonderful day!";

// Translate to different languages using different providers
$languages = [
    'Spanish' => 'español',
    'French' => 'français',
    'German' => 'deutsch',
    'Japanese' => '日本語'
];

foreach ($languages as $langName => $langCode) {
    $translation = LangChain::translateText($textToTranslate, $langName, 'English');
    if ($translation['success']) {
        echo "$langName: " . $translation['text'] . "\n";
    }
}
echo "\n";

// 3. Code Generation and Analysis
echo "3. Code Generation and Analysis\n";
echo "-------------------------------\n";

// Generate PHP code
$codeDescription = "Create a PHP function that validates an email address and returns true if valid, false otherwise";
$codeResult = LangChain::generateCode($codeDescription, 'php');
if ($codeResult['success']) {
    echo "Generated PHP Code:\n";
    echo $codeResult['code'] . "\n\n";
    
    // Now explain the generated code
    $explanation = LangChain::explainCode($codeResult['code'], 'php');
    if ($explanation['success']) {
        echo "Code Explanation:\n";
        echo $explanation['explanation'] . "\n\n";
    }
}

// Generate JavaScript code
$jsDescription = "Create a JavaScript function that fetches data from an API and handles errors";
$jsResult = LangChain::generateCode($jsDescription, 'javascript');
if ($jsResult['success']) {
    echo "Generated JavaScript Code:\n";
    echo $jsResult['code'] . "\n\n";
}

// 4. AI Agent Functionality
echo "4. AI Agent Functionality\n";
echo "------------------------\n";

// Technical consultant agent
$techConsultantResult = LangChain::actAsAgent(
    'Senior Software Architect',
    'Review this system design and suggest improvements for scalability',
    [
        'system' => 'E-commerce platform',
        'current_architecture' => 'Monolithic PHP application with MySQL',
        'traffic' => '10,000 daily users',
        'growth_target' => '100,000 daily users in 6 months'
    ]
);
if ($techConsultantResult['success']) {
    echo "Tech Consultant Response:\n";
    echo $techConsultantResult['response'] . "\n\n";
}

// Marketing strategist agent
$marketingResult = LangChain::actAsAgent(
    'Digital Marketing Strategist',
    'Create a social media campaign strategy for a new mobile app',
    [
        'app_type' => 'Fitness tracking app',
        'target_audience' => 'Health-conscious millennials',
        'budget' => '$10,000/month',
        'timeline' => '3 months'
    ]
);
if ($marketingResult['success']) {
    echo "Marketing Strategy:\n";
    echo $marketingResult['response'] . "\n\n";
}

// 5. Text Summarization
echo "5. Text Summarization\n";
echo "-------------------\n";

$longText = "
Artificial Intelligence (AI) has emerged as one of the most transformative technologies of the 21st century, revolutionizing industries from healthcare and finance to transportation and entertainment. At its core, AI refers to the simulation of human intelligence in machines that are programmed to think and learn like humans. This technology encompasses various subfields, including machine learning, natural language processing, computer vision, and robotics.

Machine learning, a subset of AI, enables computers to learn and improve from experience without being explicitly programmed for every task. Deep learning, a further subset of machine learning, uses neural networks with multiple layers to analyze and interpret complex data patterns. These technologies have enabled breakthroughs in image recognition, speech processing, and predictive analytics.

The applications of AI are vast and growing rapidly. In healthcare, AI assists in medical diagnosis, drug discovery, and personalized treatment plans. In finance, it powers algorithmic trading, fraud detection, and risk assessment. Autonomous vehicles rely on AI for navigation and safety systems. Virtual assistants like Siri and Alexa use natural language processing to understand and respond to human queries.

However, the rapid advancement of AI also raises important ethical and societal questions. Concerns about job displacement, privacy, bias in AI systems, and the potential for misuse have sparked debates about the need for AI governance and regulation. As AI continues to evolve, it's crucial to balance innovation with responsible development and deployment.
";

$summary = LangChain::summarizeText($longText, 100);
if ($summary['success']) {
    echo "Original text length: " . $summary['original_length'] . " characters\n";
    echo "Summary length: " . $summary['summary_length'] . " characters\n";
    echo "Summary: " . $summary['summary'] . "\n\n";
}

// 6. Provider Capabilities Comparison
echo "6. Provider Capabilities Comparison\n";
echo "----------------------------------\n";

$providers = ['openai', 'claude', 'llama', 'deepseek'];

foreach ($providers as $providerName) {
    try {
        $provider = LangChain::getProvider($providerName);
        $capabilities = $provider->getSupportedCapabilitiesList();
        echo "$providerName capabilities: " . implode(', ', $capabilities) . "\n";
        
        // Check specific capabilities
        $specialCapabilities = ['math_solving', 'reasoning'];
        foreach ($specialCapabilities as $capability) {
            if ($provider->supportsCapability($capability)) {
                echo "  ✓ Supports $capability\n";
            }
        }
        echo "\n";
    } catch (Exception $e) {
        echo "$providerName: Not available (" . $e->getMessage() . ")\n\n";
    }
}

// 7. Multi-Provider Fallback Strategy
echo "7. Multi-Provider Fallback Strategy\n";
echo "----------------------------------\n";

$prompt = "Write a haiku about programming";
$preferredProviders = ['deepseek', 'claude', 'openai', 'llama'];

foreach ($preferredProviders as $provider) {
    try {
        echo "Trying $provider...\n";
        $result = LangChain::generateText($prompt, [], $provider);
        
        if ($result['success']) {
            echo "Success with $provider:\n";
            echo $result['text'] . "\n\n";
            break;
        } else {
            echo "Failed with $provider: " . $result['error'] . "\n";
        }
    } catch (Exception $e) {
        echo "Error with $provider: " . $e->getMessage() . "\n";
    }
}

// 8. Language-Specific Responses
echo "8. Language-Specific Responses\n";
echo "-----------------------------\n";

$multilingualPrompt = "Explain the concept of recursion in programming";
$languages = ['English', 'Spanish', 'French'];

foreach ($languages as $language) {
    $result = LangChain::generateText(
        "Please respond in $language. $multilingualPrompt",
        ['temperature' => 0.7]
    );
    
    if ($result['success']) {
        echo "Response in $language:\n";
        echo substr($result['text'], 0, 200) . "...\n\n";
    }
}

echo "=== Demo Complete ===\n";
echo "\nThis example showcased:\n";
echo "- DeepSeek provider with math solving and reasoning\n";
echo "- Multi-language translation capabilities\n";
echo "- Code generation and analysis\n";
echo "- AI agent functionality with different roles\n";
echo "- Text summarization\n";
echo "- Provider capability comparison\n";
echo "- Fallback strategies\n";
echo "- Language-specific responses\n";