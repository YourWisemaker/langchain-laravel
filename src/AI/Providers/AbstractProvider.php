<?php

namespace LangChainLaravel\AI\Providers;

abstract class AbstractProvider
{
    protected array $config;
    protected array $defaultParams;
    protected array $supportedCapabilities = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultParams = $this->getDefaultParams();
        $this->supportedCapabilities = $this->getSupportedCapabilities();
    }

    /**
     * Generate text using the AI provider
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    abstract public function generateText(string $prompt, array $params = []): array;

    /**
     * Translate text from one language to another
     *
     * @param string $text
     * @param string $targetLanguage
     * @param string|null $sourceLanguage
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, source_language?: string, target_language?: string, usage?: array, error?: string}
     */
    public function translateText(string $text, string $targetLanguage, ?string $sourceLanguage = null, array $params = []): array
    {
        if (!$this->supportsCapability('translation')) {
            return [
                'success' => false,
                'error' => 'Translation capability not supported by this provider'
            ];
        }

        $sourcePrompt = $sourceLanguage ? "from {$sourceLanguage}" : "(auto-detect source language)";
        $prompt = "Translate the following text {$sourcePrompt} to {$targetLanguage}:\n\n{$text}\n\nTranslation:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.3]));
        
        if ($result['success']) {
            $result['source_language'] = $sourceLanguage;
            $result['target_language'] = $targetLanguage;
        }
        
        return $result;
    }

    /**
     * Generate code in specified programming language
     *
     * @param string $description
     * @param string $language
     * @param array<string, mixed> $params
     * @return array{success: bool, code?: string, language?: string, usage?: array, error?: string}
     */
    public function generateCode(string $description, string $language = 'php', array $params = []): array
    {
        if (!$this->supportsCapability('code_generation')) {
            return [
                'success' => false,
                'error' => 'Code generation capability not supported by this provider'
            ];
        }

        $prompt = "Generate {$language} code for the following requirement:\n\n{$description}\n\nProvide clean, well-commented code:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.2]));
        
        if ($result['success']) {
            $result['code'] = $result['text'];
            $result['language'] = $language;
        }
        
        return $result;
    }

    /**
     * Act as an AI agent with specific role and context
     *
     * @param string $role
     * @param string $task
     * @param array<string, mixed> $context
     * @param array<string, mixed> $params
     * @return array{success: bool, response?: string, role?: string, usage?: array, error?: string}
     */
    public function actAsAgent(string $role, string $task, array $context = [], array $params = []): array
    {
        if (!$this->supportsCapability('agent')) {
            return [
                'success' => false,
                'error' => 'Agent capability not supported by this provider'
            ];
        }

        $contextStr = empty($context) ? '' : "\n\nContext:\n" . json_encode($context, JSON_PRETTY_PRINT);
        $prompt = "You are a {$role}. {$contextStr}\n\nTask: {$task}\n\nResponse:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.7]));
        
        if ($result['success']) {
            $result['response'] = $result['text'];
            $result['role'] = $role;
        }
        
        return $result;
    }

    /**
     * Analyze and explain code
     *
     * @param string $code
     * @param string $language
     * @param array<string, mixed> $params
     * @return array{success: bool, explanation?: string, language?: string, usage?: array, error?: string}
     */
    public function explainCode(string $code, string $language = 'auto', array $params = []): array
    {
        if (!$this->supportsCapability('code_analysis')) {
            return [
                'success' => false,
                'error' => 'Code analysis capability not supported by this provider'
            ];
        }

        $langPrompt = $language === 'auto' ? '' : " (written in {$language})";
        $prompt = "Explain the following code{$langPrompt} in detail:\n\n```\n{$code}\n```\n\nExplanation:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.4]));
        
        if ($result['success']) {
            $result['explanation'] = $result['text'];
            $result['language'] = $language;
        }
        
        return $result;
    }

    /**
     * Summarize text content
     *
     * @param string $text
     * @param int $maxLength
     * @param array<string, mixed> $params
     * @return array{success: bool, summary?: string, original_length?: int, summary_length?: int, usage?: array, error?: string}
     */
    public function summarizeText(string $text, int $maxLength = 200, array $params = []): array
    {
        if (!$this->supportsCapability('summarization')) {
            return [
                'success' => false,
                'error' => 'Summarization capability not supported by this provider'
            ];
        }

        $prompt = "Summarize the following text in approximately {$maxLength} words or less:\n\n{$text}\n\nSummary:";
        
        $result = $this->generateText($prompt, array_merge($params, ['temperature' => 0.3]));
        
        if ($result['success']) {
            $result['summary'] = $result['text'];
            $result['original_length'] = strlen($text);
            $result['summary_length'] = strlen($result['text']);
        }
        
        return $result;
    }

    /**
     * Get the default parameters for this provider
     *
     * @return array<string, mixed>
     */
    abstract protected function getDefaultParams(): array;

    /**
     * Solve mathematical problems using AI reasoning capabilities
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
        }
        
        return $result;
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
            'code_generation',
            'code_analysis',
            'agent',
            'summarization'
        ];
    }

    /**
     * Check if provider supports a specific capability
     *
     * @param string $capability
     * @return bool
     */
    public function supportsCapability(string $capability): bool
    {
        return in_array($capability, $this->supportedCapabilities);
    }

    /**
     * Get all supported capabilities
     *
     * @return array<string>
     */
    public function getSupportedCapabilitiesList(): array
    {
        return $this->supportedCapabilities;
    }

    /**
     * Validate the provider configuration
     *
     * @throws \RuntimeException
     */
    abstract protected function validateConfig(): void;

    /**
     * Merge user parameters with defaults
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function mergeParams(array $params): array
    {
        return array_merge($this->defaultParams, $params);
    }

    /**
     * Resolve model alias to actual model name
     *
     * @param string $model
     * @return string
     */
    protected function resolveModel(string $model): string
    {
        $aliases = config('langchain.model_aliases', []);
        if (is_array($aliases) && isset($aliases[$model])) {
            return (string) $aliases[$model];
        }
        return $model;
    }

    /**
     * Get configuration value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Format prompt for specific language or locale
     *
     * @param string $prompt
     * @param string $language
     * @return string
     */
    protected function formatPromptForLanguage(string $prompt, string $language): string
    {
        if ($language === 'auto' || empty($language)) {
            return $prompt;
        }

        return "Please respond in {$language}.\n\n{$prompt}";
    }

    /**
     * Get request timeout from configuration
     *
     * @return int
     */
    protected function getRequestTimeout(): int
    {
        $timeout = $this->getConfig('timeout', 30);
        return (int) $timeout;
    }
}