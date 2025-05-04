<?php

namespace RagKit\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RagKit\RagKitServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Load test migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        
        // Run migrations
        $this->artisan('migrate')->run();
        
        // Register routes for testing
        $this->defineWebRoutes($this->app);
    }
    
    protected function getPackageProviders($app)
    {
        return [
            RagKitServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup RagKit config
        $app['config']->set('ragkit.default', 'test');
        $app['config']->set('ragkit.drivers.test', true);

        // Setup storage for testing
        $app['config']->set('ragkit.storage.disk', 'local');
        $app['config']->set('ragkit.storage.path', 'rag_test');
    }

    protected function defineWebRoutes($router)
    {
        require __DIR__ . '/../routes/web.php';
    }
} 