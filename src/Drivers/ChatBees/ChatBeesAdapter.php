<?php

namespace RagKit\Drivers\ChatBees;

use RagKit\Contracts\RagServiceAdapterInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ChatBeesAdapter implements RagServiceAdapterInterface
{
    /**
     * @var string The API key
     */
    protected $apiKey;

    /**
     * @var string The account ID
     */
    protected $accountId;

    /**
     * @var string The base URL for API requests
     */
    protected $baseUrl;

    /**
     * Create a new ChatBees adapter instance.
     *
     * @param  string  $apiKey  The ChatBees API key
     * @param  string  $accountId  The ChatBees account ID
     * @param  string|null  $baseUrl  Optional base URL override
     */
    public function __construct(string $apiKey, string $accountId, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->accountId = $accountId;
        $this->baseUrl = $baseUrl ?: "https://{$accountId}.us-west-2.aws.chatbees.ai";
    }

    /**
     * Create a collection for a user on the RAG platform.
     *
     * @param  array  $userData  User data to create the collection with
     * @return array|null Response data or null on failure
     */
    public function createCollection(array $userData): ?array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/collections/create", [
                'collection_name' => $userData['collection_name'] ?? null,
                'namespace_name' => $userData['namespace_name'] ?? 'public',
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ChatBees failed to create collection', [
                'response' => $response->body(),
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ChatBees API error when creating collection', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if a collection exists.
     *
     * @param  string  $collectionId  The collection ID to check
     * @return bool True if exists, false otherwise
     */
    public function collectionExists(string $collectionId): bool
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/collections/list");

            if ($response->successful()) {
                $collections = $response->json('collections') ?? [];

                return collect($collections)->contains('collection_name', $collectionId);
            }

            return false;
        } catch (\Exception $e) {
            Log::error('ChatBees API error when checking collection existence', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
            ]);

            return false;
        }
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
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
            ])->attach(
                'file', file_get_contents($filePath), $fileName
            )->post("{$this->baseUrl}/docs/add", [
                'request' => json_encode([
                    'collection_name' => $collectionId,
                    'namespace_name' => 'public',
                ]),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ChatBees failed to upload document', [
                'response' => $response->body(),
                'status' => $response->status(),
                'collection_id' => $collectionId,
                'file_name' => $fileName,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ChatBees API error when uploading document', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
                'file_name' => $fileName,
            ]);

            return null;
        }
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
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/docs/get_outline_faq", [
                'namespace_name' => 'public',
                'collection_name' => $collectionId,
                'doc_name' => $documentId,
            ]);

            if ($response->successful()) {
                return [
                    'outlines' => $response->json('outlines') ?? [],
                    'faqs' => $response->json('faqs') ?? [],
                ];
            }

            Log::error('ChatBees failed to get document outline/FAQ', [
                'response' => $response->body(),
                'status' => $response->status(),
                'collection_id' => $collectionId,
                'document_id' => $documentId,
            ]);

            return [
                'outlines' => [],
                'faqs' => [],
            ];
        } catch (\Exception $e) {
            Log::error('ChatBees API error when getting document outline/FAQ', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
                'document_id' => $documentId,
            ]);

            return [
                'outlines' => [],
                'faqs' => [],
            ];
        }
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
        try {
            $payload = [
                'namespace_name' => 'public',
                'collection_name' => $collectionId,
                'query' => $question,
            ];

            // Add document filter if provided
            if ($documentId) {
                $payload['doc_names'] = is_array($documentId) ? $documentId : [$documentId];
            }

            // Add history messages if provided
            if (!empty($historyMessages)) {
                $payload['chat_history'] = $this->formatHistoryMessages($historyMessages);
            }

            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/docs/query", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                return [
                    'answer' => $responseData['answer'] ?? 'No answer was provided',
                    'references' => $responseData['references'] ?? [],
                ];
            }

            Log::error('ChatBees failed to get answer', [
                'response' => $response->body(),
                'status' => $response->status(),
                'collection_id' => $collectionId,
                'question' => $question,
            ]);

            return [
                'answer' => 'Failed to get answer from ChatBees. Please try again later.',
                'references' => [],
                'error' => true,
            ];
        } catch (\Exception $e) {
            Log::error('ChatBees API error when asking question', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
                'question' => $question,
            ]);

            return [
                'answer' => 'Error communicating with ChatBees. Please try again later.',
                'references' => [],
                'error' => true,
            ];
        }
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
        // In ChatBees, the chat continuation is handled by the same endpoint as ask
        return $this->ask($collectionId, $question, $documentId, $historyMessages);
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
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/docs/delete", [
                'namespace_name' => 'public',
                'collection_name' => $collectionId,
                'doc_name' => $documentId,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('ChatBees API error when deleting document', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
                'document_id' => $documentId,
            ]);

            return false;
        }
    }

    /**
     * List all documents in a collection.
     *
     * @param  string  $collectionId  Collection ID
     * @return array List of documents
     */
    public function listDocuments(string $collectionId): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/docs/list", [
                'namespace_name' => 'public',
                'collection_name' => $collectionId,
            ]);

            if ($response->successful()) {
                return $response->json('docs') ?? [];
            }

            Log::error('ChatBees failed to list documents', [
                'response' => $response->body(),
                'status' => $response->status(),
                'collection_id' => $collectionId,
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('ChatBees API error when listing documents', [
                'error' => $e->getMessage(),
                'collection_id' => $collectionId,
            ]);

            return [];
        }
    }

    /**
     * Format history messages for the ChatBees API.
     *
     * @param  array  $historyMessages  History messages to format
     * @return array Formatted history messages
     */
    protected function formatHistoryMessages(array $historyMessages): array
    {
        $formatted = [];

        foreach ($historyMessages as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                $role = strtolower($message['role']);
                
                // ChatBees uses "user" and "assistant" roles
                if ($role === 'human' || $role === 'user') {
                    $formatted[] = [
                        'role' => 'user',
                        'content' => $message['content'],
                    ];
                } elseif ($role === 'ai' || $role === 'assistant' || $role === 'system') {
                    $formatted[] = [
                        'role' => 'assistant',
                        'content' => $message['content'],
                    ];
                }
            }
        }

        return $formatted;
    }
} 