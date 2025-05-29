<?php

namespace LangChain;

use Illuminate\Support\ServiceProvider;
use LangChain\AI\LangChainManager;

class LangChainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('langchain', function($app) {
            return new LangChainManager($app['config']->get('langchain'));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/langchain.php' => config_path('langchain.php'),
        ], 'langchain-config');
    }
}