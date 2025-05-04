<?php

namespace RagKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RagKit\Contracts\ChatServiceInterface;
use RagKit\Models\RagCollection;

class RagChatController extends Controller
{
    /**
     * The chat service instance.
     *
     * @var \RagKit\Contracts\ChatServiceInterface
     */
    protected $chatService;

    /**
     * Create a new RagChatController instance.
     *
     * @param  \RagKit\Contracts\ChatServiceInterface  $chatService
     * @return void
     */
    public function __construct(ChatServiceInterface $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle a chat request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
            'document_id' => 'nullable|string',
            'collection_id' => 'required|integer',
        ]);

        $user = $request->user();
        $message = $request->input('message');
        $history = $request->input('history', []);
        $documentId = $request->input('document_id');
        $collectionId = $request->input('collection_id');

        $collection = RagCollection::where('id', $collectionId)
            ->whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->first();

        if (!$collection) {
            return response()->json([
                'answer' => 'Error: Collection not found or access denied',
                'references' => [],
                'error' => true,
            ]);
        }

        $result = $this->chatService->processChatMessage(
            $collection,
            $message,
            $documentId,
            $history
        );

        return response()->json($result);
    }

    /**
     * Get accounts for the current user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getAccounts(Request $request)
    {
        $user = $request->user();
        $accounts = $this->chatService->getUserAccounts($user->id);
        
        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    /**
     * Get collections for a specific account.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getCollections(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer',
        ]);

        $user = $request->user();
        $accountId = $request->input('account_id');

        $collections = $this->chatService->getAccountCollections($accountId, $user->id);

        if ($collections->isEmpty()) {
            return response()->json([
                'collections' => [],
                'error' => 'No collections found',
            ]);
        }

        return response()->json([
            'collections' => $collections->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'name' => $collection->name,
                ];
            }),
        ]);
    }

    /**
     * Get documents for a specific collection.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getDocuments(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|integer',
        ]);

        $user = $request->user();
        $collectionId = $request->input('collection_id');

        $documents = $this->chatService->getCollectionDocuments($collectionId, $user->id);

        if ($documents->isEmpty()) {
            return response()->json([
                'documents' => [],
                'error' => 'No documents found',
            ]);
        }

        return response()->json([
            'documents' => $documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'original_filename' => $document->original_filename,
                ];
            }),
        ]);
    }
} 