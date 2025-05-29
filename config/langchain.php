<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used by the
    | LangChain package. You may specify which of the providers below you
    | wish to use as your default provider for all AI operations.
    |
    | Supported: "openai", "claude", "llama"
    |
    */
    'default' => env('LANGCHAIN_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure different AI providers and their settings.
    | Each provider can have its own API keys, base URLs, and default models.
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-3.5-turbo'),
            'default_max_tokens' => env('OPENAI_DEFAULT_MAX_TOKENS', 1000),
            'default_temperature' => env('OPENAI_DEFAULT_TEMPERATURE', 0.7),
        ],
        
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'base_url' => env('CLAUDE_BASE_URL', 'https://api.anthropic.com'),
            'api_version' => env('CLAUDE_API_VERSION', '2023-06-01'),
            'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-3-sonnet-20240229'),
            'default_max_tokens' => env('CLAUDE_DEFAULT_MAX_TOKENS', 1000),
            'default_temperature' => env('CLAUDE_DEFAULT_TEMPERATURE', 0.7),
        ],
        
        'llama' => [
            'api_key' => env('LLAMA_API_KEY'),
            'base_url' => env('LLAMA_BASE_URL', 'http://localhost:11434'),
            'default_model' => env('LLAMA_DEFAULT_MODEL', 'llama2'),
            'default_max_tokens' => env('LLAMA_DEFAULT_MAX_TOKENS', 1000),
            'default_temperature' => env('LLAMA_DEFAULT_TEMPERATURE', 0.7),
        ],
        
        'deepseek' => [
            'api_key' => env('DEEPSEEK_API_KEY'),
            'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
            'default_model' => env('DEEPSEEK_DEFAULT_MODEL', 'deepseek-chat'),
            'default_max_tokens' => env('DEEPSEEK_DEFAULT_MAX_TOKENS', 1000),
            'default_temperature' => env('DEEPSEEK_DEFAULT_TEMPERATURE', 0.7),
        ],
        
        // Example custom provider configuration
        // Uncomment and configure for your custom AI provider
        /*
        'custom-ai' => [
            'api_key' => env('CUSTOM_AI_API_KEY'),
            'base_url' => env('CUSTOM_AI_BASE_URL', 'https://api.your-custom-ai.com'),
            'default_model' => env('CUSTOM_AI_DEFAULT_MODEL', 'custom-model-v1'),
            'default_max_tokens' => env('CUSTOM_AI_DEFAULT_MAX_TOKENS', 1000),
            'default_temperature' => env('CUSTOM_AI_DEFAULT_TEMPERATURE', 0.7),
            // Add any custom configuration parameters your provider needs
            'custom_param' => env('CUSTOM_AI_PARAM', 'default_value'),
        ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Aliases
    |--------------------------------------------------------------------------
    |
    | Define aliases for commonly used models to make them easier to reference.
    | You can use these aliases instead of full model names in your code.
    |
    */
    'model_aliases' => [
        // OpenAI aliases
        'gpt3' => 'gpt-3.5-turbo',
        'gpt4' => 'gpt-4',
        'gpt4-turbo' => 'gpt-4-turbo-preview',
        
        // Claude aliases
        'claude' => 'claude-3-sonnet-20240229',
        'claude-haiku' => 'claude-3-haiku-20240307',
        'claude-opus' => 'claude-3-opus-20240229',
        
        // Llama aliases
        'llama' => 'llama2',
        'llama-70b' => 'meta-llama/Llama-2-70b-chat-hf',
        'llama-13b' => 'meta-llama/Llama-2-13b-chat-hf',
        
        // DeepSeek aliases
        'deepseek' => 'deepseek-chat',
        'deepseek-coder' => 'deepseek-coder',
        'deepseek-math' => 'deepseek-math-7b-instruct',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for AI responses to improve performance and reduce
    | API costs. You can enable/disable caching and set TTL values.
    |
    */
    'cache' => [
        'enabled' => env('LANGCHAIN_CACHE_ENABLED', true),
        'ttl' => env('LANGCHAIN_CACHE_TTL', 3600),
        'store' => env('LANGCHAIN_CACHE_STORE'),
        'prefix' => env('LANGCHAIN_CACHE_PREFIX', 'langchain'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default request settings that apply to all AI providers.
    |
    */
    'request' => [
        'timeout' => env('LANGCHAIN_REQUEST_TIMEOUT', 30),
        'retry_attempts' => env('LANGCHAIN_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('LANGCHAIN_RETRY_DELAY', 1000), // milliseconds
    ],
];