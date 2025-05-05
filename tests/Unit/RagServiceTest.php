<?php

namespace RagKit\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RagKit\RAG\RagService;
use RagKit\Tests\TestCase;
use RagKit\Tests\User;

class RagServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected $service;
    protected $mockAdapter;
    protected $user;
    
    public function setUp(): void
    {
        parent::setUp();
        
        $this->artisan('migrate');
        
        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('Users table not found, skipping tests.');
        }
        
 
        $this->service = new RagService();
        $this->mockAdapter = Mockery::mock('RagAdapter');
        $this->service->registerAdapter('test', $this->mockAdapter);
        

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }
    
    public function testCanRegisterAdapter()
    {
        $this->assertNotNull($this->service);
        $this->assertTrue(method_exists($this->service, 'getAdapter'));
    }
    
    public function testCanCreateUserAccount()
    {
        $this->mockAdapter->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['id' => 'test_account_123']);
        
        $account = $this->service->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->assertNotNull($account);
        $this->assertEquals('test_account_123', $account->account_id);
    }
    
    public function testCanCreateCollection()
    {
        $this->mockAdapter->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['id' => 'test_account_123']);
        
        $account = $this->service->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->mockAdapter->shouldReceive('createCollection')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['collection_id' => 'test_collection_123']);
        
        $collection = $this->service->createCollection($account, 'Test Collection');
        $this->assertEquals('test_collection_123', $collection->collection_id);
    }
    
    public function testCanUploadDocument()
    {
        $this->mockAdapter->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['id' => 'test_account_123']);
        
        $account = $this->service->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->mockAdapter->shouldReceive('createCollection')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['collection_id' => 'test_collection_123']);
        
        $collection = $this->service->createCollection($account, 'Test Collection');
        
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        $filePath = $file->path();
        $fileName = 'document.pdf';
        
        $this->mockAdapter->shouldReceive('uploadDocument')
            ->once()
            ->with('test_collection_123', Mockery::type('string'), 'document.pdf')
            ->andReturn(['doc_name' => 'test_document_123']);
            
        $this->mockAdapter->shouldReceive('getDocumentOutlineFaq')
            ->once()
            ->with('test_collection_123', 'test_document_123')
            ->andReturn([
                'outlines' => [['title' => 'Section 1', 'level' => 1]],
                'faqs' => [['question' => 'Test Q?', 'answer' => 'Test A']]
            ]);
        
        // Upload document and get initial state
        $document = $this->service->uploadDocument(
            $collection, 
            $filePath, 
            $fileName
        );
        
        $this->assertNotNull($document);
        $this->assertEquals('queued', $document->status);
        $this->assertEquals('Document queued for processing', $document->status_message);
        
        // Process the upload job synchronously
        $job = new \RagKit\Jobs\ProcessRagDocumentUpload($document);
        $job->handle($this->service);
        
        // Refresh document from database
        $document->refresh();
        
        // Verify final state after processing
        $this->assertEquals('completed', $document->status);
        $this->assertEquals('test_document_123', $document->document_id);
        $this->assertNotEmpty($document->outlines);
        $this->assertNotEmpty($document->faqs);
    }
    
    public function testCanAskQuestion()
    {
        $this->mockAdapter->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['id' => 'test_account_123']);
        
        $account = $this->service->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->mockAdapter->shouldReceive('createCollection')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['collection_id' => 'test_collection_123']);
        
        $collection = $this->service->createCollection($account, 'Test Collection');
        
        $this->mockAdapter->shouldReceive('ask')
            ->once()
            ->with('test_collection_123', 'What is the test question?', null, [])
            ->andReturn([
                'answer' => 'This is a test answer',
                'references' => [['document_id' => 'doc123', 'text' => 'Source text']]
            ]);
        
        $response = $this->service->ask($collection, 'What is the test question?');
        $this->assertEquals('This is a test answer', $response['answer']);
    }
    
    public function testCanGetDocumentOutlineAndFaq()
    {
        $this->mockAdapter->shouldReceive('createAccount')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['id' => 'test_account_123']);
        
        $account = $this->service->createUserAccount(
            $this->user, 
            'Test Account', 
            'test'
        );
        
        $this->mockAdapter->shouldReceive('createCollection')
            ->once()
            ->with(Mockery::type('array'))
            ->andReturn(['collection_id' => 'test_collection_123']);
        
        $collection = $this->service->createCollection($account, 'Test Collection');
        
        $file = UploadedFile::fake()->create('document.pdf', 1000);
        $filePath = $file->path();
        $fileName = 'document.pdf';
        
        $this->mockAdapter->shouldReceive('uploadDocument')
            ->once()
            ->with('test_collection_123', Mockery::type('string'), 'document.pdf')
            ->andReturn(['doc_name' => 'test_document_123']);
        
        // Upload document and get initial state
        $document = $this->service->uploadDocument(
            $collection, 
            $filePath, 
            $fileName
        );
        
        // Process the upload job synchronously
        $job = new \RagKit\Jobs\ProcessRagDocumentUpload($document);
        $job->handle($this->service);
        
        // Refresh document from database
        $document->refresh();
        
        $this->mockAdapter->shouldReceive('getDocumentOutlineFaq')
            ->once()
            ->with('test_collection_123', 'test_document_123')
            ->andReturn([
                'outlines' => [['title' => 'Section 1', 'level' => 1]],
                'faqs' => [['question' => 'Test Q?', 'answer' => 'Test A']]
            ]);
        
        $result = $this->service->getDocumentOutlineFaq($document);
        
        $this->assertArrayHasKey('outlines', $result);
        $this->assertArrayHasKey('faqs', $result);
        $this->assertEquals([['title' => 'Section 1', 'level' => 1]], $result['outlines']);
        $this->assertEquals([['question' => 'Test Q?', 'answer' => 'Test A']], $result['faqs']);
    }
} 