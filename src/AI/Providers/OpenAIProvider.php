<?php

namespace LangChain\AI\Providers;

use OpenAI\Client as OpenAIClient;
use OpenAI\Factory;
use RuntimeException;
use OpenAI\Exceptions\ErrorException;

class OpenAIProvider extends AbstractProvider
{
    protected ?OpenAIClient $client = null;

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
            $model = $this->resolveModel($mergedParams['model']);

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

                return [
                    'success' => true,
                    'text' => $response->choices[0]->message->content,
                    'usage' => $response->usage->toArray(),
                ];
            } else {
                $response = $this->getClient()->completions()->create([
                    'model' => $model,
                    'prompt' => $prompt,
                    'temperature' => $mergedParams['temperature'],
                    'max_tokens' => $mergedParams['max_tokens'],
                ]);

                return [
                    'success' => true,
                    'text' => $response->choices[0]->text,
                    'usage' => $response->usage->toArray(),
                ];
            }
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
    public function getClient(): OpenAIClient
    {
        if (!$this->client) {
            $this->validateConfig();
            
            $factory = Factory::new()->withApiKey($this->getConfig('api_key'));
            
            if ($organization = $this->getConfig('organization')) {
                $factory = $factory->withOrganization($organization);
            }
            
            if ($baseUrl = $this->getConfig('base_url')) {
                $factory = $factory->withBaseUri($baseUrl);
            }
            
            $this->client = $factory->make();
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
        if (empty($apiKey) || trim($apiKey) === '') {
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
        $chatModels = [
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4-turbo-preview',
            'gpt-3.5-turbo',
            'gpt-3.5-turbo-16k',
        ];

        foreach ($chatModels as $chatModel) {
            if (str_starts_with($model, $chatModel)) {
                return true;
            }
        }

        return false;
    }
}