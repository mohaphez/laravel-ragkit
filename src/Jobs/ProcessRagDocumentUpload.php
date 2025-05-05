<?php

namespace RagKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RagKit\Contracts\RagServiceInterface;
use RagKit\Models\RagDocument;

class ProcessRagDocumentUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries;
    
    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff;
    
    /**
     * The document instance.
     *
     * @var \RagKit\Models\RagDocument
     */
    protected $document;

    /**
     * Create a new job instance.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return void
     */
    public function __construct(RagDocument $document)
    {
        $this->document = $document;
        $this->tries = config('ragkit.upload.max_retry_attempts', 3);
        $this->backoff = config('ragkit.upload.retry_backoff', [10, 60, 180]);
    }

    /**
     * Execute the job.
     *
     * @param  \RagKit\Contracts\RagServiceInterface  $ragService
     * @return void
     */
    public function handle(RagServiceInterface $ragService)
    {
        $collection = $this->document->collection;
        $account = $collection->account;
        $adapter = $ragService->getAdapter($account->provider);

        if (! $adapter) {
            Log::error('RAG document upload failed: No adapter registered for provider', [
                'provider' => $account->provider,
                'document_id' => $this->document->id,
            ]);
            
            $this->document->update([
                'status' => 'failed',
                'status_message' => 'RAG provider adapter not found',
            ]);
            
            return;
        }

        // Update document status to uploading
        $this->document->update([
            'status' => 'uploading',
            'status_message' => 'Document is being uploaded to RAG provider',
        ]);

        // Make sure the file exists in storage
        if (! Storage::exists($this->document->file_path)) {
            Log::error('RAG document upload failed: File does not exist', [
                'file_path' => $this->document->file_path,
                'document_id' => $this->document->id,
            ]);
            
            $this->document->update([
                'status' => 'failed',
                'status_message' => 'File not found in storage',
            ]);
            
            return;
        }

        try {
        
            $result = $adapter->uploadDocument(
                $collection->collection_id,
                Storage::path($this->document->file_path),
                $this->document->original_filename
            );

            if (!$result) {
                throw new \Exception('RAG provider returned empty result');
            }

            // Update document with the provider's document ID and status
            $this->document->update([
                'document_id' => $result['doc_name'] ?? $this->document->original_filename,
                'status' => 'processing',
                'status_message' => 'Document uploaded, waiting for processing',
                'external_metadata' => array_merge($this->document->external_metadata ?? [], [
                    'upload_response' => $result,
                ]),
            ]);

            // Wait for processing to complete and get outline/FAQ if available
            try {
                $outlineFaq = $adapter->getDocumentOutlineFaq(
                    $collection->collection_id,
                    $this->document->document_id
                );

                $this->document->update([
                    'outlines' => $outlineFaq['outlines'] ?? [],
                    'faqs' => $outlineFaq['faqs'] ?? [],
                    'status' => 'completed',
                    'status_message' => 'Document successfully processed',
                    'processed_at' => now(),
                ]);

                Log::info('RAG document successfully processed', [
                    'document_id' => $this->document->id,
                    'provider' => $account->provider,
                ]);
            } catch (\Exception $e) {
                // Still mark as complete but log the outline/faq error
                $this->document->update([
                    'status' => 'completed', 
                    'status_message' => 'Document uploaded but failed to get outlines/FAQs',
                    'processed_at' => now(),
                ]);
                
                Log::warning('RAG document upload successful but failed to get outline/FAQs', [
                    'error' => $e->getMessage(),
                    'document_id' => $this->document->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('RAG document upload failed', [
                'error' => $e->getMessage(),
                'document_id' => $this->document->id,
                'attempt' => $this->attempts(),
            ]);
            
            // If we've exceeded retry attempts, mark as failed
            if ($this->attempts() >= $this->tries) {
                $this->document->update([
                    'status' => 'failed',
                    'status_message' => 'Failed to upload document after multiple attempts: ' . $e->getMessage(),
                ]);
                return;
            }
            
            // Otherwise, let the job retry
            $this->document->update([
                'status' => 'retry',
                'status_message' => 'Upload failed, will retry. Error: ' . $e->getMessage(),
            ]);
            
            $this->release($this->backoff[$this->attempts() - 1] ?? 60);
        }
    }
} 