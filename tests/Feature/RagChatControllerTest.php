<?php

namespace RagKit\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RagKit\Tests\TestCase;
use RagKit\Tests\User;
use RagKit\Models\RagAccount;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;
use Illuminate\Support\Str;

class RagChatControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $account;
    protected $collection;
    protected $document;
    protected $mockAdapter;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up the mock adapter
        $this->mockAdapter = Mockery::mock('RagAdapter');
        app('ragkit')->registerAdapter('test', $this->mockAdapter);
        
        // Define routes without middleware
        config(['ragkit.routes.middleware' => []]);
        
        $this->defineWebRoutes($this->app);
        
        // Create a user, account, and collection for testing
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password')
        ]);
        
        // Create test account
        $this->account = RagAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Test Account',
            'provider' => 'test',
            'is_active' => true,
            'account_id' => 'test_account_123'
        ]);
        
        // Create test collection
        $this->collection = RagCollection::create([
            'rag_account_id' => $this->account->id,
            'name' => 'Test Collection',
            'collection_id' => 'test_collection_123',
            'is_active' => true
        ]);
        
        // Create a test document
        $this->document = RagDocument::create([
            'rag_collection_id' => $this->collection->id,
            'uuid' => Str::uuid(),
            'document_id' => 'test_document_123',
            'original_filename' => 'document.pdf',
            'status' => 'completed'
        ]);
    }
    
    protected function defineWebRoutes($router)
    {
        require __DIR__ . '/../../routes/web.php';
    }
    
    public function testCanGetAccounts()
    {
        $response = $this->actingAs($this->user)
            ->get(route('ragkit.accounts'));
        
        $response->assertStatus(200)
            ->assertJson([
                'accounts' => [
                    [
                        'id' => $this->account->id,
                        'name' => 'Test Account',
                        'provider' => 'test'
                    ]
                ]
            ]);
    }
    
    public function testCanGetCollections()
    {
        $response = $this->actingAs($this->user)
            ->get(route('ragkit.collections', ['account_id' => $this->account->id]));
        
        $response->assertStatus(200)
            ->assertJson([
                'collections' => [
                    [
                        'id' => $this->collection->id,
                        'name' => 'Test Collection'
                    ]
                ]
            ]);
    }
    
    public function testCanGetDocuments()
    {
        $response = $this->actingAs($this->user)
            ->get(route('ragkit.documents', ['collection_id' => $this->collection->id]));
        
        $response->assertStatus(200)
            ->assertJson([
                'documents' => [
                    [
                        'id' => $this->document->id,
                        'original_filename' => 'document.pdf'
                    ]
                ]
            ]);
    }
    
    public function testCanChatWithCollection()
    {
        $this->mockAdapter->shouldReceive('ask')
            ->andReturn([
                'answer' => 'This is a test answer',
                'sources' => [['document_id' => 'doc123', 'text' => 'Source text']]
            ]);
        
        $response = $this->actingAs($this->user)
            ->post(route('ragkit.chat'), [
                'message' => 'Test question',
                'collection_id' => $this->collection->id
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'This is a test answer',
                'sources' => [
                    [
                        'document_id' => 'doc123',
                        'text' => 'Source text'
                    ]
                ]
            ]);
    }
    
    public function testCanChatWithSpecificDocument()
    {
        $this->mockAdapter->shouldReceive('ask')
            ->with(
                $this->collection->collection_id,
                'Test document question',
                $this->document->document_id,
                []
            )
            ->andReturn([
                'answer' => 'This is a document-specific answer',
                'references' => [['document_id' => $this->document->document_id, 'text' => 'Source text']]
            ]);
        
        $response = $this->actingAs($this->user)
            ->post(route('ragkit.chat'), [
                'message' => 'Test document question',
                'document_id' => $this->document->document_id,
                'collection_id' => $this->collection->id
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'This is a document-specific answer',
                'references' => [
                    [
                        'document_id' => $this->document->document_id,
                        'text' => 'Source text'
                    ]
                ]
            ]);
    }
    
    public function testCanContinueChatConversation()
    {
        // First chat to create a conversation
        $this->mockAdapter->shouldReceive('ask')
            ->once()
            ->with(
                $this->collection->collection_id,
                'Test question',
                null,
                []
            )
            ->andReturn([
                'answer' => 'This is a test answer',
                'references' => [['document_id' => 'doc123', 'text' => 'Source text']]
            ]);
        
        $this->actingAs($this->user)
            ->post(route('ragkit.chat'), [
                'message' => 'Test question',
                'collection_id' => $this->collection->id
            ]);
        
        // Now continue the conversation
        $this->mockAdapter->shouldReceive('chat')
            ->once()
            ->with(
                $this->collection->collection_id,
                'Follow up question',
                [
                    [
                        'role' => 'user',
                        'content' => 'Test question'
                    ],
                    [
                        'role' => 'assistant',
                        'content' => 'This is a test answer'
                    ]
                ],
                null
            )
            ->andReturn([
                'answer' => 'This is a followup answer',
                'references' => [['document_id' => 'doc123', 'text' => 'Updated source text']]
            ]);
        
        $response = $this->actingAs($this->user)
            ->post(route('ragkit.chat'), [
                'message' => 'Follow up question',
                'collection_id' => $this->collection->id,
                'conversation_id' => 'test_conversation_123',
                'history' => [
                    [
                        'role' => 'user',
                        'content' => 'Test question'
                    ],
                    [
                        'role' => 'assistant',
                        'content' => 'This is a test answer'
                    ]
                ]
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'This is a followup answer',
                'references' => [
                    [
                        'document_id' => 'doc123',
                        'text' => 'Updated source text'
                    ]
                ]
            ]);
    }
} 