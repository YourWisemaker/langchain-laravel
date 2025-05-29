<?php

namespace LangChainLaravel\AI\Providers;

use RuntimeException;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class ClaudeProvider extends AbstractProvider
{
    /**
     * Generate text using Claude
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

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $this->getConfig('api_key'),
                'anthropic-version' => $this->getConfig('version', '2023-06-01'),
            ])
            ->timeout($this->getRequestTimeout())
            ->post($this->getConfig('base_url') . '/v1/messages', [
                'model' => $model,
                'max_tokens' => $mergedParams['max_tokens'],
                'temperature' => $mergedParams['temperature'],
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

            if ($response->failed()) {
                return [
                    'success' => false,
                    'error' => 'Claude API error: ' . $response->body(),
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'text' => $data['content'][0]['text'] ?? '',
                'usage' => [
                    'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                    'output_tokens' => $data['usage']['output_tokens'] ?? 0,
                    'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
                ],
            ];
        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'Claude API request failed: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Claude API error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get default parameters for Claude
     *
     * @return array<string, mixed>
     */
    protected function getDefaultParams(): array
    {
        return [
            'model' => $this->getConfig('default_model', 'claude-3-sonnet-20240229'),
            'temperature' => $this->getConfig('default_temperature', 0.7),
            'max_tokens' => $this->getConfig('default_max_tokens', 1000),
        ];
    }

    /**
     * Validate Claude configuration
     *
     * @throws RuntimeException
     */
    protected function validateConfig(): void
    {
        if (empty($this->getConfig('api_key'))) {
            throw new RuntimeException('Claude API key is required');
        }

        if (empty($this->getConfig('base_url'))) {
            throw new RuntimeException('Claude base URL is required');
        }
    }

    /**
     * Get supported capabilities for Claude provider
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


}