<?php

namespace LangChainLaravel\AI\Providers;

use LangChainLaravel\AI\Adapters\OpenAI\ClientAdapter;
use OpenAI\Client as OpenAIClient;
use OpenAI\Factory;
use RuntimeException;
use OpenAI\Exceptions\ErrorException;

class OpenAIProvider extends AbstractProvider
{
    protected ?ClientAdapter $client = null;

    /**
     * Generate text using OpenAI
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
            // Ensure model is a string for PHPStan type checking
            $modelParam = '';
            if (isset($mergedParams['model'])) {
                // PHPStan type safety for model parameter
                if (is_string($mergedParams['model'])) {
                    $modelParam = $mergedParams['model'];
                } else {
                    $modelValue = $mergedParams['model'];
                    if (is_object($modelValue)) {
                        $modelParam = get_class($modelValue);
                    } elseif (is_scalar($modelValue)) {
                        $modelParam = (string) $modelValue;
                    } else {
                        $modelParam = 'unknown_model_type';
                    }
                }
            }
            $model = $this->resolveModel($modelParam);

            // Use chat completions for newer models, completions for older ones
            if ($this->isChatModel($model)) {
                $response = $this->getClient()->chat()->create([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => $mergedParams['temperature'],
                    'max_tokens' => $mergedParams['max_tokens'],
                ]);

                $content = $response->choices[0]->message->content;
                return [
                    'success' => true,
                    'text' => $content !== null ? (string) $content : '',
                    'usage' => $response->usage->toArray(),
                ];
            } else {
                $response = $this->getClient()->completions()->create([
                    'model' => $model,
                    'prompt' => $prompt,
                    'temperature' => $mergedParams['temperature'],
                    'max_tokens' => $mergedParams['max_tokens'],
                ]);

                $text = $response->choices[0]->text;
                return [
                    'success' => true,
                    'text' => $text !== null ? (string) $text : '',
                    'usage' => $response->usage->toArray(),
                ];
            }
        } catch (RuntimeException $e) {
            // Re-throw configuration validation errors
            throw $e;
        } catch (ErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'OpenAI API error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get OpenAI client instance
     */
    public function getClient(): ClientAdapter
    {
        if (!$this->client) {
            $this->validateConfig();
            
            $factory = new Factory();
            // Handle API key type safety
            $rawApiKey = $this->getConfig('api_key');
            if (is_string($rawApiKey)) {
                $apiKey = $rawApiKey;
            } elseif (is_scalar($rawApiKey)) {
                $apiKey = (string) $rawApiKey;
            } else {
                // Fallback for non-scalar types
                $apiKey = is_object($rawApiKey) ? get_class($rawApiKey) : 'invalid_api_key';
            }
            $factory = $factory->withApiKey($apiKey);
            
            $organization = $this->getConfig('organization');
            if ($organization !== null && $organization !== '') {
                if (is_string($organization)) {
                    $factory = $factory->withOrganization($organization);
                } elseif (is_object($organization)) {
                    $factory = $factory->withOrganization(get_class($organization));
                } else {
                    // Handle non-string, non-object types
                    $orgStr = '';
                    if (is_scalar($organization)) {
                        $orgStr = (string) $organization;
                    } else {
                        $orgStr = 'invalid_organization';
                    }
                    $factory = $factory->withOrganization($orgStr);
                }
            }
            
            $baseUrl = $this->getConfig('base_url');
            if ($baseUrl !== null && $baseUrl !== '') {
                if (is_string($baseUrl)) {
                    $factory = $factory->withBaseUri($baseUrl);
                } elseif (is_object($baseUrl)) {
                    $factory = $factory->withBaseUri(get_class($baseUrl));
                } else {
                    // Handle non-string, non-object types
                    $baseUrlStr = '';
                    if (is_scalar($baseUrl)) {
                        $baseUrlStr = (string) $baseUrl;
                    } else {
                        $baseUrlStr = 'invalid_base_url';
                    }
                    $factory = $factory->withBaseUri($baseUrlStr);
                }
            }
            
            $realClient = $factory->make();
            $this->client = new ClientAdapter($realClient);
        }
        return $this->client;
    }

    /**
     * Get default parameters for OpenAI
     *
     * @return array<string, mixed>
     */
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model', 'gpt-3.5-turbo'),
            'temperature' => $this->getConfig('default_temperature', 0.7),
            'max_tokens' => $this->getConfig('default_max_tokens', 1000),
        ];
    }

    /**
     * Validate OpenAI configuration
     *
     * @throws RuntimeException
     */
    protected function validateConfig(): void
    {
        $apiKey = $this->getConfig('api_key');
        if ($apiKey === null || $apiKey === '' || (is_string($apiKey) && trim($apiKey) === '')) {
            throw new RuntimeException('OpenAI API key is required');
        }
    }

    /**
     * Get supported capabilities for OpenAI provider
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
        ];
    }

    /**
     * Check if the model uses chat completions API
     *
     * @param string $model
     * @return bool
     */
    private function isChatModel(string $model): bool
    {
        // Dynamic pattern matching for chat models
        $chatModelPatterns = [
            '/^gpt-\d+/', // All GPT-X variants (gpt-3, gpt-4, gpt-5, gpt-9, etc.)
            '/^chatgpt-/', // ChatGPT models
            '/^o1-/', // O1 series models
        ];

        foreach ($chatModelPatterns as $pattern) {
            if (preg_match($pattern, $model)) {
                return true;
            }
        }

        // Legacy models that use completions API
        $legacyModelPatterns = [
            '/^text-davinci/',
            '/^text-curie/',
            '/^text-babbage/',
            '/^text-ada/',
            '/^davinci/',
            '/^curie/',
            '/^babbage/',
            '/^ada/',
        ];

        foreach ($legacyModelPatterns as $pattern) {
            if (preg_match($pattern, $model)) {
                return false;
            }
        }

        // Default to chat completions for unknown models (safer assumption for newer models)
        return true;
    }
}