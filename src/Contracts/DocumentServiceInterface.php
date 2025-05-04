<?php

namespace RagKit\Contracts;

use Illuminate\Database\Eloquent\Collection;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;

interface DocumentServiceInterface
{
    /**
     * Get all documents for a user and provider.
     *
     * @param  int  $userId
     * @param  string  $provider
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentsForUser(int $userId, string $provider): Collection;

    /**
     * Get a document by UUID for a user.
     *
     * @param  string  $uuid
     * @param  int  $userId
     * @return \RagKit\Models\RagDocument
     */
    public function getDocumentByUuid(string $uuid, int $userId): RagDocument;

    /**
     * Upload a new document.
     *
     * @param  \RagKit\Models\RagCollection  $collection
     * @param  string  $filePath
     * @param  string  $fileName
     * @param  array  $metadata
     * @return \RagKit\Models\RagDocument|null
     */
    public function uploadDocument(RagCollection $collection, string $filePath, string $fileName, array $metadata): ?RagDocument;

    /**
     * Delete a document.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return bool
     */
    public function deleteDocument(RagDocument $document): bool;

    /**
     * Get document outline and FAQs.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return array
     */
    public function getDocumentOutlineFaq(RagDocument $document): array;
} 