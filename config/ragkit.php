<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default RAG Service Provider
    |--------------------------------------------------------------------------
    |
    | This value determines which RAG service provider will be used by default.
    | Supported: "chatbees", or any other custom driver you add.
    |
    */
    'default' => env('RAGKIT_DEFAULT_PROVIDER', 'chatbees'),

    /*
    |--------------------------------------------------------------------------
    | Enabled Drivers
    |--------------------------------------------------------------------------
    |
    | These values determine which RAG service adapters are enabled.
    | Set to true to enable a driver, false to disable.
    |
    */
    'drivers' => [
        'chatbees' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | RAG Service Provider Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each RAG service
    | provider that will be used by your application.
    |
    */
    'connections' => [
        'chatbees' => [
            'api_key' => env('RAGKIT_CHATBEES_API_KEY', ''),
            'account_id' => env('RAGKIT_CHATBEES_ACCOUNT_ID', ''),
            'base_url' => env('RAGKIT_CHATBEES_BASE_URL', null),
        ],
        
        // Add other driver configurations here
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure storage settings for document uploads.
    |
    */
    'storage' => [
        'disk' => env('RAGKIT_STORAGE_DISK', 'local'),
        'path' => env('RAGKIT_STORAGE_PATH', 'rag'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure route settings for the package.
    |
    */
    'routes' => [
        'prefix' => 'rag',
        'middleware' => ['web', 'auth'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configure file upload settings and limitations.
    |
    */
    'uploads' => [
        'max_file_size' => env('RAGKIT_MAX_FILE_SIZE', 10 * 1024), // 10MB
        'allowed_extensions' => ['pdf', 'docx', 'doc', 'txt'],
    ],

    // User model to use for relationships
    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Document Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configure the document upload event listener behavior and handlers.
    |
    */

    'upload' => [
        // Enable or disable the upload event listener
        'enable_upload_listener' => env('RAGKIT_ENABLE_UPLOAD_LISTENER', true),

        // The handler class that will process document uploads
        'upload_handler_class' => \RagKit\Handlers\DefaultUploadHandler::class,
        
        // Maximum number of retry attempts for document upload jobs
        'max_retry_attempts' => env('RAGKIT_UPLOAD_MAX_RETRIES', 3),
        
        // Backoff times in seconds between retry attempts
        'retry_backoff' => [10, 60, 180],
        
        // Queue to use for document upload jobs
        'queue' => env('RAGKIT_UPLOAD_QUEUE', 'default'),
    ],
]; 