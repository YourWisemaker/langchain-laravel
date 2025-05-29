<?php

namespace LangChainLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use LangChainLaravel\AI\Providers\AbstractProvider;

/**
 * @method static string generate(string $prompt, array $options = [])
 * @method static array generateWithMetadata(string $prompt, array $options = [])
 * @method static array chat(array $messages, array $options = [])
 * @method static array chatWithMetadata(array $messages, array $options = [])
 * @method static \LangChainLaravel\AI\Providers\AbstractProvider getProvider(string $name = null)
 * @method static array getAvailableProviders()
 * @method static array getProviderCapabilities(string $provider = null)
 * @method static \LangChainLaravel\AI\LangChainManager setProvider(string $provider)
 * @method static \LangChainLaravel\AI\LangChainManager registerProvider(string $name, string $providerClass)
 * @method static array getCustomProviders()
 * @method static array getConfig(string $key = null, mixed $default = null)
 * @method static array translateText(string $text, string $targetLanguage, string $sourceLanguage = null, array $params = [])
 * @method static array generateCode(string $description, string $language = 'php', array $params = [])
 * @method static array actAsAgent(string $role, string $task, array $context = [], array $params = [])
 * @method static array explainCode(string $code, string $language = 'auto', array $params = [])
 * @method static array summarizeText(string $text, int $maxLength = 200, array $params = [])
 * @method static array solveMath(string $problem, array $params = [])
 * @method static array performReasoning(string $question, array $context = [], array $params = [])
 * @method static bool supportsCapability(string $capability)
 * @method static array getSupportedCapabilitiesList()
 */
class LangChain extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'langchain';
    }
}