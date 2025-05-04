<?php

namespace RagKit\Services;

use RagKit\Contracts\ChatServiceInterface;
use RagKit\RAG\RagService;
use Illuminate\Database\Eloquent\Collection;
use RagKit\Models\RagCollection;
use RagKit\Models\RagAccount;
use RagKit\Models\RagDocument;

class ChatService implements ChatServiceInterface
{
    /**
     * The RAG service instance.
     *
     * @var \RagKit\RAG\RagService
     */
    protected $ragService;

    /**
     * Create a new ChatService instance.
     *
     * @param  \RagKit\RAG\RagService  $ragService
     * @return void
     */
    public function __construct(RagService $ragService)
    {
        $this->ragService = $ragService;
    }

    /**
     * Process a chat message.
     *
     * @param  \RagKit\Models\RagCollection  $collection
     * @param  string  $message
     * @param  string|null  $documentId
     * @param  array  $history
     * @return array
     */
    public function processChatMessage(RagCollection $collection, string $message, ?string $documentId, array $history): array
    {
        return $this->ragService->ask($collection, $message, $documentId, $history);
    }

    /**
     * Get active accounts for a user.
     *
     * @param  int  $userId
     * @return \Illuminate\Support\Collection
     */
    public function getUserAccounts(int $userId): Collection
    {
        return RagAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'provider', 'created_at']);
    }

    /**
     * Get active collections for an account.
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @return \Illuminate\Support\Collection
     */
    public function getAccountCollections(int $accountId, int $userId): Collection
    {
        return RagCollection::where('rag_account_id', $accountId)
            ->whereHas('account', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'created_at']);
    }

    /**
     * Get completed documents for a collection.
     *
     * @param  int  $collectionId
     * @param  int  $userId
     * @return \Illuminate\Support\Collection
     */
    public function getCollectionDocuments(int $collectionId, int $userId): Collection
    {
        return RagDocument::where('rag_collection_id', $collectionId)
            ->whereHas('collection.account', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('is_active', true);
            })
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'uuid', 'document_id', 'original_filename', 'created_at']);
    }
} 