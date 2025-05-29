<?php

namespace Tests;

use LangChainLaravel\LangChainServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup can be added here
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LangChainServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'LangChain' => \LangChainLaravel\Facades\LangChain::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        // Setup the application environment for testing
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.env', 'testing');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        
        // Set test API keys
        $app['config']->set('langchain.providers.openai.api_key', 'test-openai-key');
        $app['config']->set('langchain.providers.claude.api_key', 'test-claude-key');
        $app['config']->set('langchain.providers.llama.api_key', 'test-llama-key');
        $app['config']->set('langchain.providers.deepseek.api_key', 'test-deepseek-key');
        
        // Set default provider for testing
        $app['config']->set('langchain.default_provider', 'openai');
        
        // Enable caching for tests
        $app['config']->set('langchain.cache.enabled', true);
        $app['config']->set('langchain.cache.ttl', 3600);
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        // Add any database migrations needed for testing
        // This method can be overridden in specific test classes if needed
    }
}