<?php

namespace RagKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RagKit\Contracts\DocumentServiceInterface;
use RagKit\Models\RagCollection;

class RagDocumentController extends Controller
{
    /**
     * The document service instance.
     *
     * @var \RagKit\Contracts\DocumentServiceInterface
     */
    protected $documentService;

    /**
     * Create a new RagDocumentController instance.
     *
     * @param  \RagKit\Contracts\DocumentServiceInterface  $documentService
     * @return void
     */
    public function __construct(DocumentServiceInterface $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Display a listing of the documents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $provider = $request->input('provider', config('ragkit.default'));
        
        $documents = $this->documentService->getDocumentsForUser($user->id, $provider);
        
        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Store a newly created document in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:' . config('ragkit.uploads.max_file_size', 10240),
            'collection_id' => 'required|integer',
        ]);
        
        $file = $request->file('file');
        $collectionId = $request->input('collection_id');
        $user = $request->user();
        
        $collection = RagCollection::where('id', $collectionId)
            ->whereHas('account', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('is_active', true);
            })
            ->first();
            
        if (!$collection) {
            return response()->json([
                'error' => 'Collection not found or access denied',
            ], 403);
        }
        
        $document = $this->documentService->uploadDocument(
            $collection,
            $file->getRealPath(),
            $file->getClientOriginalName(),
            [
                'uploaded_by' => $user->id,
                'content_type' => $file->getMimeType(),
                'source' => 'web_upload',
            ]
        );
        
        if (!$document) {
            return response()->json([
                'error' => 'Failed to upload document',
            ], 500);
        }
        
        return response()->json([
            'document' => $document,
            'message' => 'Document uploaded successfully',
        ]);
    }

    /**
     * Display the specified document.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, string $uuid)
    {
        $user = $request->user();
        $document = $this->documentService->getDocumentByUuid($uuid, $user->id);
        
        return response()->json([
            'document' => $document,
        ]);
    }

    /**
     * Remove the specified document from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, string $uuid)
    {
        $user = $request->user();
        $document = $this->documentService->getDocumentByUuid($uuid, $user->id);
        
        $this->documentService->deleteDocument($document);
        
        return response()->json([
            'message' => 'Document deleted successfully',
        ]);
    }

    /**
     * Get document outline and FAQs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $uuid
     * @return \Illuminate\Http\Response
     */
    public function getOutlineFaq(Request $request, string $uuid)
    {
        $user = $request->user();
        $document = $this->documentService->getDocumentByUuid($uuid, $user->id);
        
        $result = $this->documentService->getDocumentOutlineFaq($document);
        
        return response()->json($result);
    }
} 