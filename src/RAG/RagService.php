<?php

namespace RagKit\RAG;

use RagKit\Contracts\RagServiceInterface;
use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RagService implements RagServiceInterface
{
    /**
     * The registered adapters.
     *
     * @var array
     */
    protected $adapters = [];

    /**
     * The default provider.
     *
     * @var string
     */
    protected $defaultProvider;

    /**
     * Create a new RAG service instance.
     */
    public function __construct()
    {
        $this->defaultProvider = Config::get('ragkit.default', 'chatbees');
    }

    /**
     * Register a RAG service adapter
     *
     * @param  string  $provider  Provider name
     * @param  object  $adapter  Provider adapter instance
     * @return self
     */
    public function registerAdapter(string $provider, $adapter): self
    {
        $this->adapters[$provider] = $adapter;

        return $this;
    }

    /**
     * Get the provider adapter
     *
     * @param  string|null  $provider  Provider name
     * @return object|null The adapter or null if not found
     */
    public function getAdapter(?string $provider = null): ?object
    {
        $provider = $provider ?: $this->defaultProvider;

        return $this->adapters[$provider] ?? null;
    }

    /**
     * Create a RAG account for a user.
     */
    public function createUserAccount(
        $user,
        string $name,
        string $provider,
        array $settings = []
    ): RagAccount {
        $adapter = $this->getAdapter($provider);

        if (! $adapter) {
            throw new \Exception("No adapter registered for provider: {$provider}");
        }

        $result = $adapter->createAccount([
            'name' => $name,
            'settings' => $settings
        ]);

        return RagAccount::create([
            'user_id' => $user->id,
            'name' => $name,
            'provider' => $provider,
            'settings' => $settings,
            'is_active' => true,
            'account_id' => $result['id'] ?? null,
        ]);
    }

    /**
     * Create a collection for an account.
     */
    public function createCollection(
        RagAccount $account,
        string $name,
        array $metadata = []
    ): RagCollection {
        $adapter = $this->getAdapter($account->provider);

        if (! $adapter) {
            throw new \Exception("No adapter registered for provider: {$account->provider}");
        }

        $result = $adapter->createCollection([
            'collection_name' => $name,
            'namespace_name' => $metadata['namespace_name'] ?? 'public',
            'api_key' => $account->getApiKey(),
            'account_id' => $account->getExternalAccountId(),
        ]);

        return RagCollection::create([
            'rag_account_id' => $account->id,
            'name' => $name,
            'collection_id' => $result['collection_id'] ?? $name,
            'collection_metadata' => $result ?? [],
            'is_active' => true,
        ]);
    }

    /**
     * Upload a document to a collection.
     */
    public function uploadDocument(
        RagCollection $collection,
        string $filePath,
        string $fileName,
        array $metadata = []
    ): ?RagDocument {
        $account = $collection->account;
        $adapter = $this->getAdapter($account->provider);

        if (! $adapter) {
            Log::error('No adapter registered for provider', [
                'provider' => $account->provider,
            ]);
            return null;
        }

        $storagePath = "rag/{$collection->id}/".Str::uuid().'_'.$fileName;
        $storedPath = Storage::put($storagePath, file_get_contents($filePath));
        $fileSize = Storage::size($storagePath);
        $mimeType = Storage::mimeType($storagePath);

        $document = RagDocument::create([
            'rag_collection_id' => $collection->id,
            'original_filename' => $fileName,
            'file_path' => $storagePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'status' => 'processing',
            'metadata' => $metadata,
        ]);

        $result = $adapter->uploadDocument(
            $collection->collection_id,
            Storage::path($storagePath),
            $fileName
        );

        if ($result) {
            $document->update([
                'document_id' => $result['doc_name'] ?? $fileName,
                'status' => 'completed',
            ]);

            try {
                $outlineFaq = $adapter->getDocumentOutlineFaq(
                    $collection->collection_id,
                    $document->document_id
                );

                $document->update([
                    'outlines' => $outlineFaq['outlines'] ?? [],
                    'faqs' => $outlineFaq['faqs'] ?? [],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to get document outline/FAQs', [
                    'error' => $e->getMessage(),
                    'document_id' => $document->id,
                ]);
            }

            return $document;
        }

        $document->update(['status' => 'failed']);
        
        return null;
    }

    /**
     * Ask a question using a specific collection.
     */
    public function ask(
        RagCollection $collection,
        string $question,
        ?string $documentId = null,
        array $historyMessages = []
    ): array {
        $account = $collection->account;
        $adapter = $this->getAdapter($account->provider);

        if (! $adapter) {
            return [
                'answer' => 'Error: No adapter registered for provider',
                'references' => [],
                'error' => true,
            ];
        }

        try {

            if (! empty($historyMessages)) {
                return $adapter->chat(
                    $collection->collection_id,
                    $question,
                    $historyMessages,
                    $documentId
                );
            }

            return $adapter->ask(
                $collection->collection_id,
                $question,
                $documentId,
                $historyMessages
            );
        } catch (\Exception $e) {
            Log::error('Failed to get answer from RAG service', [
                'error' => $e->getMessage(),
                'question' => $question,
                'collection_id' => $collection->id,
            ]);

            return [
                'answer' => 'Error communicating with the RAG service. Please try again later.',
                'references' => [],
                'error' => true,
            ];
        }
    }

    /**
     * Get document outline and FAQs.
     */
    public function getDocumentOutlineFaq(
        RagDocument $document
    ): array {
        $collection = $document->collection;
        $account = $collection->account;
        $adapter = $this->getAdapter($account->provider);

        if (! $adapter || ! $document->document_id) {
            return [
                'outlines' => [],
                'faqs' => [],
                'error' => 'Document or adapter not found',
            ];
        }

        try {
            $result = $adapter->getDocumentOutlineFaq(
                $collection->collection_id,
                $document->document_id
            );

            // Update the document with the latest outlines and FAQs
            $document->update([
                'outlines' => $result['outlines'] ?? [],
                'faqs' => $result['faqs'] ?? [],
            ]);

            return [
                'outlines' => $result['outlines'] ?? [],
                'faqs' => $result['faqs'] ?? [],
                'document_id' => $document->document_id,
                'original_filename' => $document->original_filename,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get document outline/FAQs', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return [
                'outlines' => $document->outlines ?? [], 
                'faqs' => $document->faqs ?? [],
                'error' => 'Error communicating with the RAG service.',
            ];
        }
    }
} 