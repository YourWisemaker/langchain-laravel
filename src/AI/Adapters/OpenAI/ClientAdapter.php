<?php

namespace LangChainLaravel\AI\Adapters\OpenAI;

use OpenAI\Client as RealOpenAIClient;
use OpenAI\Resources\Completions;
use OpenAI\Resources\Chat;

class ClientAdapter
{
    private RealOpenAIClient $client;

    public function __construct(RealOpenAIClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return Completions
     */
    public function completions(): Completions
    {
        return $this->client->completions();
    }

    /**
     * @return Chat
     */
    public function chat(): Chat
    {
        return $this->client->chat();
    }
}
