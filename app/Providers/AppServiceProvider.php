<?php

namespace App\Providers;

use App\Services\Chatbot\BedrockClient;
use App\Services\Chatbot\LLMClientInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LLMClientInterface::class, BedrockClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
