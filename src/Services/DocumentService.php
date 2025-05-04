<?php

namespace RagKit\Services;

use Illuminate\Support\Facades\Storage;
use RagKit\Contracts\RagServiceInterface;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;

class DocumentService
{
    /**
     * The RAG service instance.
     *
     * @var \RagKit\Contracts\RagServiceInterface
     */
    protected $ragService;

    /**
     * Create a new DocumentService instance.
     *
     * @param  \RagKit\Contracts\RagServiceInterface  $ragService
     * @return void
     */
    public function __construct(RagServiceInterface $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Get all documents for a user and provider.
     *
     * @param  int  $userId
     * @param  string  $provider
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentsForUser(int $userId, string $provider)
    {
        return RagDocument::whereHas('collection.account', function ($query) use ($userId, $provider) {
            $query->where('user_id', $userId)
                ->where('provider', $provider)
                ->where('is_active', true);
        })->latest()->get();
    }

    /**
     * Get a document by UUID for a user.
     *
     * @param  string  $uuid
     * @param  int  $userId
     * @return \RagKit\Models\RagDocument
     */
    public function getDocumentByUuid(string $uuid, int $userId)
    {
        return RagDocument::where('uuid', $uuid)
            ->whereHas('collection.account', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('is_active', true);
            })->firstOrFail();
    }

    /**
     * Upload a new document.
     *
     * @param  \RagKit\Models\RagCollection  $collection
     * @param  string  $filePath
     * @param  string  $fileName
     * @param  array  $metadata
     * @return \RagKit\Models\RagDocument|null
     */
    public function uploadDocument(RagCollection $collection, string $filePath, string $fileName, array $metadata)
    {
        return $this->ragService->uploadDocument(
            $collection,
            $filePath,
            $fileName,
            $metadata
        );
    }

    /**
     * Delete a document.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return bool
     */
    public function deleteDocument(RagDocument $document)
    {
        $collection = $document->collection;
        $account = $collection->account;
        $adapter = $this->ragService->getAdapter($account->provider);

        if ($adapter) {
            $adapter->deleteDocument(
                $collection->collection_id,
                $document->document_id
            );
        }

        // Delete local file
        if ($document->file_path && Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        return $document->delete();
    }

    /**
     * Get document outline and FAQs.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return array
     */
    public function getDocumentOutlineFaq(RagDocument $document)
    {
        return $this->ragService->getDocumentOutlineFaq($document);
    }
} 