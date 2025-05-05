<?php

namespace RagKit\Listeners;

use RagKit\Events\DocumentUploaded;
use RagKit\Contracts\DocumentUploadHandler;
use Illuminate\Contracts\Config\Repository;

class HandleDocumentUpload
{
    /**
     * The configuration repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Create the event listener.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * Handle the event.
     *
     * @param  \RagKit\Events\DocumentUploaded  $event
     * @return void
     */
    public function handle(DocumentUploaded $event): void
    {

        if (!$this->config->get('ragkit.upload.enable_upload_listener', true)) {
            return;
        }


        $handlerClass = $this->config->get(
            'ragkit.upload.upload_handler_class',
            \RagKit\Handlers\DefaultUploadHandler::class
        );

        $handler = app($handlerClass);
        
        if (!$handler instanceof DocumentUploadHandler) {
            throw new \RuntimeException(
                "Upload handler must implement " . DocumentUploadHandler::class
            );
        }

        $handler->handle($event->document);
    }
} 