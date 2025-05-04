<?php

namespace RagKit\Console\Commands;

use Illuminate\Console\Command;
use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;

class RagListCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ragkit:collections
                            {--account_id= : Optional account ID to filter collections by}
                            {--provider= : Optional provider to filter collections by}
                            {--user_id= : Optional user ID to filter collections by}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all RAG collections with their details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $accountId = $this->option('account_id');
        $provider = $this->option('provider');
        $userId = $this->option('user_id');

        $query = RagCollection::query()->with('account');

        if ($accountId) {
            $query->where('rag_account_id', $accountId);
        }

        if ($provider || $userId) {
            $query->whereHas('account', function ($q) use ($provider, $userId) {
                if ($provider) {
                    $q->where('provider', $provider);
                }
                if ($userId) {
                    $q->where('user_id', $userId);
                }
            });
        }

        $collections = $query->get();

        if ($collections->isEmpty()) {
            $this->info('No collections found with the specified filters.');
            return 0;
        }

        $rows = [];
        foreach ($collections as $collection) {
            $account = $collection->account;
            $documentCount = $collection->documents()->count();

            $rows[] = [
                'id' => $collection->id,
                'name' => $collection->name,
                'collection_id' => $collection->collection_id,
                'account' => $account->name,
                'provider' => $account->provider,
                'user_id' => $account->user_id,
                'documents' => $documentCount,
                'created_at' => $collection->created_at->format('Y-m-d H:i:s'),
                'active' => $collection->is_active ? 'Yes' : 'No',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Collection ID', 'Account', 'Provider', 'User ID', 'Documents', 'Created At', 'Active'],
            $rows
        );

        return 0;
    }
} 