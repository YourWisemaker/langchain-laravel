<?php

namespace LangChainLaravel\AI;

use LangChainLaravel\AI\Providers\AbstractProvider;
use LangChainLaravel\AI\Providers\OpenAIProvider;
use LangChainLaravel\AI\Providers\ClaudeProvider;
use LangChainLaravel\AI\Providers\LlamaProvider;
use LangChainLaravel\AI\Providers\DeepSeekProvider;
use RuntimeException;
use InvalidArgumentException;

class LangChainManager
{
    protected array $config;
    protected array $providers = [];
    protected ?string $defaultProvider = null;
    protected array $customProviders = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultProvider = $config['default'] ?? 'openai';
    }

    /**
     * Set a provider instance, primarily for testing.
     *
     * @param string $name
     * @param AbstractProvider $provider
     * @return void
     */
    public function setProvider(string $name, AbstractProvider $provider): void
    {
        $this->providers[$name] = $provider;
    }

    /**
     * Generate text using the default or specified provider
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @param string|null $provider
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function generateText(string $prompt, array $params = [], ?string $provider = null): array
    {
        $providerName = $provider ?? $this->defaultProvider;
        $providerInstance = $this->getProvider($providerName);
        
        return $providerInstance->generateText($prompt, $params);
    }

    /**
     * Generate text using OpenAI
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function openai(string $prompt, array $params = []): array
    {
        return $this->generateText($prompt, $params, 'openai');
    }

    /**
     * Generate text using Claude
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function claude(string $prompt, array $params = []): array
    {
        return $this->generateText($prompt, $params, 'claude');
    }

    /**
     * Generate text using Llama
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function llama(string $prompt, array $params = []): array
    {
        return $this->generateText($prompt, $params, 'llama');
    }

    /**
     * Generate text using DeepSeek
     *
     * @param string $prompt
     * @param array<string, mixed> $params
     * @return array{success: bool, text?: string, usage?: array, error?: string}
     */
    public function deepseek(string $prompt, array $params = []): array
    {
        return $this->generateText($prompt, $params, 'deepseek');
    }

    /**
     * Get a provider instance
     *
     * @param string $name
     * @return AbstractProvider
     * @throws InvalidArgumentException
     */
    public function getProvider(string $name): AbstractProvider
    {
        if (!isset($this->providers[$name])) {
            $this->providers[$name] = $this->createProvider($name);
        }

        return $this->providers[$name];
    }

    /**
     * Set the default provider
     *
     * @param string $provider
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setDefaultProvider(string $provider): self
    {
        if (!$this->isValidProvider($provider)) {
            throw new InvalidArgumentException("Invalid provider: {$provider}");
        }

        $this->defaultProvider = $provider;
        return $this;
    }

    /**
     * Get the current default provider
     *
     * @return string
     */
    public function getDefaultProvider(): string
    {
        return $this->defaultProvider;
    }

    /**
     * Get available providers
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->config['providers'] ?? []);
    }

    /**
     * Check if a provider is valid and configured
     *
     * @param string $provider
     * @return bool
     */
    public function isValidProvider(string $provider): bool
    {
        return isset($this->config['providers'][$provider]);
    }

    /**
     * Get configuration for the manager
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Register a custom provider
     *
     * @param string $name
     * @param string $providerClass
     * @return $this
     * @throws InvalidArgumentException
     */
    public function registerProvider(string $name, string $providerClass): self
    {
        if (!class_exists($providerClass)) {
            throw new InvalidArgumentException("Provider class '{$providerClass}' does not exist");
        }

        if (!is_subclass_of($providerClass, AbstractProvider::class)) {
            throw new InvalidArgumentException("Provider class '{$providerClass}' must extend AbstractProvider");
        }

        $this->customProviders[$name] = $providerClass;
        return $this;
    }

    /**
     * Get registered custom providers
     *
     * @return array<string, string>
     */
    public function getCustomProviders(): array
    {
        return $this->customProviders;
    }

    /**
     * Create a provider instance
     *
     * @param string $name
     * @return AbstractProvider
     * @throws InvalidArgumentException
     */
    protected function createProvider(string $name): AbstractProvider
    {
        if (!$this->isValidProvider($name)) {
            throw new InvalidArgumentException("Provider '{$name}' is not configured");
        }

        $config = $this->config['providers'][$name];

        // Check for custom providers first
        if (isset($this->customProviders[$name])) {
            $providerClass = $this->customProviders[$name];
            return new $providerClass($config);
        }

        // Built-in providers
        return match ($name) {
            'openai' => new OpenAIProvider($config),
            'claude' => new ClaudeProvider($config),
            'llama' => new LlamaProvider($config),
            'deepseek' => new DeepSeekProvider($config),
            default => throw new InvalidArgumentException("Unsupported provider: {$name}")
        };
    }

}