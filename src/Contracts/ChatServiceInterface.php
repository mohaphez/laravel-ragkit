<?php

namespace RagKit\Contracts;

use Illuminate\Database\Eloquent\Collection;
use RagKit\Models\RagCollection;

interface ChatServiceInterface
{
    /**
     * Process a chat message.
     *
     * @param  \RagKit\Models\RagCollection  $collection
     * @param  string  $message
     * @param  string|null  $documentId
     * @param  array  $history
     * @return array
     */
    public function processChatMessage(RagCollection $collection, string $message, ?string $documentId, array $history): array;

    /**
     * Get active accounts for a user.
     *
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserAccounts(int $userId): Collection;

    /**
     * Get active collections for an account.
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountCollections(int $accountId, int $userId): Collection;

    /**
     * Get completed documents for a collection.
     *
     * @param  int  $collectionId
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCollectionDocuments(int $collectionId, int $userId): Collection;
} 