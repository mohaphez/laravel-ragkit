<?php

namespace RagKit\Handlers;

use RagKit\Contracts\DocumentUploadHandler;
use RagKit\Models\RagDocument;
use RagKit\Jobs\ProcessRagDocumentUpload;
use Illuminate\Support\Facades\Log;

class DefaultUploadHandler implements DocumentUploadHandler
{
    /**
     * Handle the document upload event.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return void
     */
    public function handle(RagDocument $document): void
    {
        $document->update([
            'status' => 'queued',
            'status_message' => 'Document queued for processing',
        ]);
        
        ProcessRagDocumentUpload::dispatch($document)
            ->onQueue(config('ragkit.upload.queue', 'default'));
            
        Log::info('Document upload job dispatched', [
            'document_id' => $document->id,
            'original_filename' => $document->original_filename,
        ]);
    }
} 