<?php

namespace RagKit\Tests\Mocks;

use RagKit\Contracts\RagServiceAdapterInterface;

class MockRagAdapter implements RagServiceAdapterInterface
{
    /**
     * Create a collection for a user on the RAG platform.
     *
     * @param  array  $userData  User data to create the collection with
     * @return array|null Response data or null on failure
     */
    public function createCollection(array $userData): ?array
    {
        return [
            'collection_id' => 'mock_collection_' . rand(1000, 9999),
            'collection_name' => $userData['collection_name'] ?? 'Mock Collection',
            'namespace_name' => $userData['namespace_name'] ?? 'public',
            'status' => 'created',
        ];
    }

    /**
     * Check if a collection exists.
     *
     * @param  string  $collectionId  The collection ID to check
     * @return bool True if exists, false otherwise
     */
    public function collectionExists(string $collectionId): bool
    {
        return true;
    }

    /**
     * Upload a document to a collection.
     *
     * @param  string  $collectionId  Collection to upload to
     * @param  string  $filePath  Path to the document to upload
     * @param  string  $fileName  Original filename
     * @return array|null Response data with document ID or null on failure
     */
    public function uploadDocument(string $collectionId, string $filePath, string $fileName): ?array
    {
        return [
            'doc_name' => 'mock_doc_' . rand(1000, 9999),
            'collection_name' => $collectionId,
            'status' => 'uploaded',
        ];
    }

    /**
     * Get document outlines and FAQs.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $documentId  Document ID
     * @return array Response data with outlines and FAQs
     */
    public function getDocumentOutlineFaq(string $collectionId, string $documentId): array
    {
        return [
            'outlines' => [
                ['title' => 'Section 1', 'page' => 1],
                ['title' => 'Section 2', 'page' => 2],
            ],
            'faqs' => [
                ['question' => 'What is RAG?', 'answer' => 'Retrieval-Augmented Generation...'],
                ['question' => 'How to use it?', 'answer' => 'You can use it by...'],
            ],
        ];
    }

    /**
     * Ask a question to the RAG service.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $question  The question to ask
     * @param  string|null|array  $documentId  Optional document ID to limit search to
     * @param  array  $historyMessages  Optional chat history
     * @return array Response with answer and references
     */
    public function ask(string $collectionId, string $question, null|array|string $documentId = null, array $historyMessages = []): array
    {
        return [
            'answer' => "This is a mock answer to the question: {$question}",
            'references' => [
                [
                    'document_id' => $documentId ?? 'mock_doc_1234',
                    'page' => 1,
                    'text' => 'Reference text from the document...',
                ],
            ],
        ];
    }

    /**
     * Continue a chat conversation in the RAG service.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $question  The follow-up question
     * @param  array  $historyMessages  Previous chat history
     * @param  string|null  $documentId  Optional document ID to limit search to
     * @return array Response with answer and references
     */
    public function chat(string $collectionId, string $question, array $historyMessages, ?string $documentId = null): array
    {
        return [
            'answer' => "This is a mock chat response to: {$question}",
            'references' => [
                [
                    'document_id' => $documentId ?? 'mock_doc_1234',
                    'page' => 1,
                    'text' => 'Reference text from the document...',
                ],
            ],
        ];
    }

    /**
     * Delete a document from a collection.
     *
     * @param  string  $collectionId  Collection ID
     * @param  string  $documentId  Document ID to delete
     * @return bool True if successfully deleted, false otherwise
     */
    public function deleteDocument(string $collectionId, string $documentId): bool
    {
        return true;
    }

    /**
     * List all documents in a collection.
     *
     * @param  string  $collectionId  Collection ID
     * @return array List of documents
     */
    public function listDocuments(string $collectionId): array
    {
        return [
            [
                'doc_name' => 'mock_doc_1234',
                'original_filename' => 'test.pdf',
                'status' => 'indexed',
            ],
            [
                'doc_name' => 'mock_doc_5678',
                'original_filename' => 'example.docx',
                'status' => 'indexed',
            ],
        ];
    }
} 