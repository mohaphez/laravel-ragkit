<?php

namespace RagKit;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use RagKit\Console\Commands\RagDocumentImport;
use RagKit\Console\Commands\RagListCollections;
use RagKit\Contracts\RagServiceInterface;
use RagKit\Contracts\DocumentServiceInterface;
use RagKit\Contracts\ChatServiceInterface;
use RagKit\Drivers\ChatBees\ChatBeesAdapter;
use RagKit\RAG\RagService;
use RagKit\Services\DocumentService;
use RagKit\Services\ChatService;
use RagKit\Events\DocumentUploaded;
use RagKit\Listeners\HandleDocumentUpload;

class RagKitServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        DocumentUploaded::class => [
            HandleDocumentUpload::class,
        ],
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/ragkit.php', 'ragkit'
        );

        // Register the main RagService class
        $this->app->singleton(RagServiceInterface::class, function ($app) {
            $service = new RagService();
            
            // Register available drivers from config
            $drivers = config('ragkit.drivers', []);
            
            foreach ($drivers as $driver => $enabled) {
                if ($enabled) {
                    $this->registerDriver($service, $driver);
                }
            }
            
            return $service;
        });

        // Register DocumentService
        $this->app->singleton(DocumentServiceInterface::class, function ($app) {
            return new DocumentService($app->make(RagServiceInterface::class));
        });

        // Register ChatService
        $this->app->singleton(ChatServiceInterface::class, function ($app) {
            return new ChatService($app->make(RagServiceInterface::class));
        });
        
        // Register RagKit Facade
        $this->app->bind('ragkit', function ($app) {
            return $app->make(RagServiceInterface::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/ragkit.php' => config_path('ragkit.php'),
        ], 'ragkit-config');
        
        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'ragkit-migrations');
        
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RagDocumentImport::class,
                RagListCollections::class,
            ]);
        }

        // Register event listeners
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }
    }
    
    /**
     * Register a driver for the RagService
     */
    protected function registerDriver(RagService $service, string $driver): void
    {
        switch ($driver) {
            case 'chatbees':
                $adapter = new ChatBeesAdapter(
                    config('ragkit.connections.chatbees.api_key', ''),
                    config('ragkit.connections.chatbees.account_id', ''),
                    config('ragkit.connections.chatbees.base_url', null)
                );
                $service->registerAdapter('chatbees', $adapter);
                break;
        }
    }
} 