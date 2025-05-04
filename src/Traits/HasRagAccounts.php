<?php

namespace RagKit\Traits;

use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;

trait HasRagAccounts
{
    /**
     * Get all RAG accounts for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ragAccounts()
    {
        return $this->hasMany(RagAccount::class);
    }

    /**
     * Get all RAG collections across all accounts for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function ragCollections()
    {
        return $this->hasManyThrough(
            RagCollection::class,
            RagAccount::class
        );
    }

    /**
     * Get all RAG documents across all collections for this user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function allRagDocuments()
    {
        return $this->ragAccounts()
            ->join('rag_collections', 'rag_accounts.id', '=', 'rag_collections.rag_account_id')
            ->join('rag_documents', 'rag_collections.id', '=', 'rag_documents.rag_collection_id')
            ->select('rag_documents.*');
    }

    /**
     * Get RAG accounts for a specific provider.
     *
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccountsByProvider(string $provider)
    {
        return $this->ragAccounts()
            ->where('provider', $provider)
            ->where('is_active', true)
            ->get();
    }
} 