<?php

namespace RagKit\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RagKit\Contracts\DocumentServiceInterface;
use RagKit\Tests\TestCase;
use RagKit\Tests\User;
use RagKit\Models\RagDocument;
use RagKit\Models\RagCollection;

class RagDocumentControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $user;
    protected $account;
    protected $collection;
    protected $document;
    protected $mockDocumentService;
    protected $mockAdapter;
    
    protected function defineWebRoutes($router)
    {
        require __DIR__ . '/../../routes/web.php';
    }
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up the mock document service
        $this->mockDocumentService = Mockery::mock(DocumentServiceInterface::class);
        $this->app->instance(DocumentServiceInterface::class, $this->mockDocumentService);
        
        // Register the test adapter
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
        
        // Set up mock responses for account and collection creation
        $this->mockAdapter->shouldReceive('createAccount')
            ->andReturn(['id' => 'test_account_123']);
            
        // Create test account and collection
        $this->account = app('ragkit')->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->mockAdapter->shouldReceive('createCollection')
            ->andReturn(['collection_id' => 'test_collection_123']);
            
        $this->collection = app('ragkit')->createCollection($this->account, 'Test Collection');
    }
    
    public function testCanUploadDocument()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        
        $document = new RagDocument([
            'uuid' => 'test-uuid',
            'document_id' => 'test-doc-123',
            'original_filename' => 'document.pdf',
            'status' => 'completed'
        ]);
        
        $this->mockDocumentService->shouldReceive('uploadDocument')
            ->once()
            ->with(
                Mockery::type(RagCollection::class),
                Mockery::type('string'),
                'document.pdf',
                Mockery::type('array')
            )
            ->andReturn($document);
        
        $response = $this->actingAs($this->user)
            ->post(route('ragkit.documents.store'), [
                'file' => $file,
                'collection_id' => $this->collection->id
            ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'document' => [
                    'uuid' => 'test-uuid',
                    'document_id' => 'test-doc-123',
                    'original_filename' => 'document.pdf',
                    'status' => 'completed'
                ],
                'message' => 'Document uploaded successfully'
            ]);
    }
    
    public function testCanShowDocumentDetails()
    {
        $document = new RagDocument([
            'uuid' => 'test-uuid',
            'document_id' => 'test-doc-123',
            'original_filename' => 'document.pdf',
            'status' => 'completed'
        ]);
        
        $this->mockDocumentService->shouldReceive('getDocumentByUuid')
            ->once()
            ->with('test-uuid', $this->user->id)
            ->andReturn($document);
        
        $response = $this->actingAs($this->user)
            ->get(route('ragkit.documents.show', ['uuid' => 'test-uuid']));
        
        $response->assertStatus(200)
            ->assertJson([
                'document' => [
                    'uuid' => 'test-uuid',
                    'document_id' => 'test-doc-123',
                    'original_filename' => 'document.pdf',
                    'status' => 'completed'
                ]
            ]);
    }
    
    public function testCanDeleteDocument()
    {
        $document = new RagDocument([
            'uuid' => 'test-uuid',
            'document_id' => 'test-doc-123',
            'original_filename' => 'document.pdf',
            'status' => 'completed'
        ]);
        
        $this->mockDocumentService->shouldReceive('getDocumentByUuid')
            ->once()
            ->with('test-uuid', $this->user->id)
            ->andReturn($document);
            
        $this->mockDocumentService->shouldReceive('deleteDocument')
            ->once()
            ->with(Mockery::type(RagDocument::class))
            ->andReturn(true);
        
        $response = $this->actingAs($this->user)
            ->delete(route('ragkit.documents.destroy', ['uuid' => 'test-uuid']));
        
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Document deleted successfully'
            ]);
    }
    
    public function testCanGetDocumentOutlineAndFaq()
    {
        $document = new RagDocument([
            'uuid' => 'test-uuid',
            'document_id' => 'test-doc-123',
            'original_filename' => 'document.pdf',
            'status' => 'completed'
        ]);
        
        $this->mockDocumentService->shouldReceive('getDocumentByUuid')
            ->once()
            ->with('test-uuid', $this->user->id)
            ->andReturn($document);
            
        $this->mockDocumentService->shouldReceive('getDocumentOutlineFaq')
            ->once()
            ->with(Mockery::type(RagDocument::class))
            ->andReturn([
                'outlines' => [
                    ['title' => 'Section 1', 'content' => 'Content 1'],
                    ['title' => 'Section 2', 'content' => 'Content 2']
                ],
                'faqs' => [
                    ['question' => 'Q1', 'answer' => 'A1'],
                    ['question' => 'Q2', 'answer' => 'A2']
                ]
            ]);
        
        $response = $this->actingAs($this->user)
            ->get(route('ragkit.documents.outline-faq', ['uuid' => 'test-uuid']));
        
        $response->assertStatus(200)
            ->assertJson([
                'outlines' => [
                    ['title' => 'Section 1', 'content' => 'Content 1'],
                    ['title' => 'Section 2', 'content' => 'Content 2']
                ],
                'faqs' => [
                    ['question' => 'Q1', 'answer' => 'A1'],
                    ['question' => 'Q2', 'answer' => 'A2']
                ]
            ]);
    }
} 