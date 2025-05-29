<?php

namespace LangChain\AI\Providers;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class LlamaProvider extends AbstractProvider
{
    /**
     * Generate text using Llama
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

            // Format prompt for chat models
            $formattedPrompt = $this->formatPromptForChat($prompt, $model);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->getConfig('api_key'),
            ])
            ->timeout($this->getRequestTimeout())
            ->post($this->getConfig('base_url') . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $formattedPrompt
                    ]
                ],
                'temperature' => $mergedParams['temperature'],
                'max_tokens' => $mergedParams['max_tokens'],
                'stream' => false,
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => 'Llama API error: ' . $response->body(),
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'text' => $data['choices'][0]['message']['content'] ?? '',
                'usage' => [
                    'prompt_tokens' => $data['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $data['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $data['usage']['total_tokens'] ?? 0,
                ],
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Llama API request failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Llama API error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get default parameters for Llama
     *
     * @return array<string, mixed>
     */
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model', 'meta-llama/Llama-2-70b-chat-hf'),
            'temperature' => $this->getConfig('default_temperature', 0.7),
            'max_tokens' => $this->getConfig('default_max_tokens', 1000),
        ];
    }

    /**
     * Validate Llama configuration
     *
     * @throws RuntimeException
     */
    protected function validateConfig(): void
    {
        if (empty($this->getConfig('api_key'))) {
            throw new RuntimeException('Llama API key is required');
        }

        if (empty($this->getConfig('base_url'))) {
            throw new RuntimeException('Llama base URL is required');
        }
    }

    /**
     * Get supported capabilities for Llama provider
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
     * Format prompt for chat models
     *
     * @param string $prompt
     * @param string $model
     * @return string
     */
    private function formatPromptForChat(string $prompt, string $model): string
    {
        // For Llama chat models, we might need special formatting
        if (str_contains($model, 'chat')) {
            return $prompt;
        }

        // For instruction models, add instruction formatting
        if (str_contains($model, 'instruct')) {
            return "[INST] {$prompt} [/INST]";
        }

        return $prompt;
    }


}