<?php

namespace RagKit\Contracts;

use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;

interface RagServiceInterface
{
    /**
     * Register a RAG service adapter
     *
     * @param  string  $provider  Provider name
     * @param  object  $adapter  Provider adapter instance
     * @return self
     */
    public function registerAdapter(string $provider, $adapter): self;

    /**
     * Get the provider adapter
     *
     * @param  string|null  $provider  Provider name
     * @return object|null The adapter or null if not found
     */
    public function getAdapter(?string $provider = null): ?object;

    /**
     * Create a RAG account for a user.
     *
     * @param  mixed  $user  The user to create an account for
     * @param  string  $name  The name of the account
     * @param  string  $provider  The RAG service provider
     * @param  array  $settings  Additional account settings
     * @return RagAccount The created RAG account
     */
    public function createUserAccount(
        $user,
        string $name,
        string $provider,
        array $settings = []
    ): RagAccount;

    /**
     * Create a collection for an account.
     *
     * @param  RagAccount  $account  The RAG account to create a collection for
     * @param  string  $name  The name of the collection
     * @param  array  $metadata  Additional collection metadata
     * @return RagCollection The created RAG collection
     */
    public function createCollection(
        RagAccount $account,
        string $name,
        array $metadata = []
    ): RagCollection;

    /**
     * Upload a document to a collection.
     *
     * @param  RagCollection  $collection  The RAG collection to upload the document to
     * @param  string  $filePath  The path to the document
     * @param  string  $fileName  The original filename
     * @param  array  $metadata  Additional metadata
     * @return RagDocument|null The created document or null on failure
     */
    public function uploadDocument(
        RagCollection $collection,
        string $filePath,
        string $fileName,
        array $metadata = []
    ): ?RagDocument;

    /**
     * Ask a question using a specific collection.
     *
     * @param  RagCollection  $collection  The RAG collection to ask the question in
     * @param  string  $question  The question to ask
     * @param  string|null  $documentId  Optional document ID to limit search to
     * @param  array  $historyMessages  Optional chat history
     * @return array Response with answer and references
     */
    public function ask(
        RagCollection $collection,
        string $question,
        ?string $documentId = null,
        array $historyMessages = []
    ): array;

    /**
     * Get document outline and FAQs.
     *
     * @param  RagDocument  $document  The RAG document to get the outline and FAQs for
     * @return array Response with outlines and FAQs
     */
    public function getDocumentOutlineFaq(
        RagDocument $document
    ): array;
} 