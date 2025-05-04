<?php

namespace RagKit\Contracts;

interface RagServiceAdapterInterface
{
    /**
     * Create a collection for a user on the RAG platform.
     *
     * @param  array  $userData  User data to create the collection with
     * @return array|null Response data or null on failure
     */
    public function createCollection(array $userData): ?array;

    /**
     * Check if a collection exists.
     *
     * @param  string  $collectionId  The collection ID to check
     * @return bool True if exists, false otherwise
     */
    public function collectionExists(string $collectionId): bool;

    /**
     * Upload a document to a collection.
     *
     * @param  string  $collectionId  Collection to upload to
     * @param  string  $filePath  Path to the document to upload
     * @param  string  $fileName  Original filename
     * @return array|null Response data with document ID or null on failure
     */
    public function uploadDocument(string $collectionId, string $filePath, string $fileName): ?array;

    /**
     * Get document outlines and FAQs.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $documentId  Document ID
     * @return array Response data with outlines and FAQs
     */
    public function getDocumentOutlineFaq(string $collectionId, string $documentId): array;

    /**
     * Ask a question to the RAG service.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $question  The question to ask
     * @param  string|null|array  $documentId  Optional document ID to limit search to
     * @param  array  $historyMessages  Optional chat history
     * @return array Response with answer and references
     */
    public function ask(string $collectionId, string $question, null|array|string $documentId = null, array $historyMessages = []): array;

    /**
     * Continue a chat conversation in the RAG service.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $question  The follow-up question
     * @param  array  $historyMessages  Previous chat history
     * @param  string|null  $documentId  Optional document ID to limit search to
     * @return array Response with answer and references
     */
    public function chat(string $collectionId, string $question, array $historyMessages, ?string $documentId = null): array;

    /**
     * Delete a document from a collection.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $documentId  Document ID to delete
     * @return bool True if successfully deleted, false otherwise
     */
    public function deleteDocument(string $collectionId, string $documentId): bool;

    /**
     * List all documents in a collection.
     *
     * @param  string  $collectionId  Collection ID
     * @return array List of documents
     */
    public function listDocuments(string $collectionId): array;
} 