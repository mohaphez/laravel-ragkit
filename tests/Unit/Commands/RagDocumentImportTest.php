<?php

namespace RagKit\Tests\Unit\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Mockery;
use RagKit\Console\Commands\RagDocumentImport;
use RagKit\Tests\TestCase;
use RagKit\Tests\User;
use RagKit\Models\RagCollection;
use RagKit\Models\RagDocument;
use RagKit\Contracts\RagServiceInterface;
use RagKit\Models\RagAccount;

class RagDocumentImportTest extends TestCase
{
    use RefreshDatabase;

    protected $tempDir;
    protected $user;
    protected $account;
    protected $collection;
    protected $mockAdapter;
    protected $mockService;
    protected $command;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        // Create mock account and collection
        $this->account = RagAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Test Account',
            'provider' => 'test',
            'is_active' => true,
            'account_id' => 'test_account_123',
        ]);
        
        $this->collection = RagCollection::create([
            'rag_account_id' => $this->account->id,
            'name' => 'Test Collection',
            'collection_id' => 'test_collection_123',
            'is_active' => true,
        ]);
        
        // Create a temporary directory with test files
        $this->tempDir = sys_get_temp_dir() . '/ragkit_test_' . time();
        if (!is_dir($this->tempDir)) {
            File::makeDirectory($this->tempDir, 0755, true);
            File::makeDirectory($this->tempDir . '/subdir', 0755, true);
            
            // Create some test files
            File::put($this->tempDir . '/document1.pdf', 'Test PDF content');
            File::put($this->tempDir . '/document2.txt', 'Test text content');
            File::put($this->tempDir . '/document3.docx', 'Test DOCX content');
            File::put($this->tempDir . '/subdir/document4.pdf', 'Nested PDF content');
        }
        
        // Create the mock service and bind it to the container
        $this->mockService = Mockery::mock(RagServiceInterface::class);
        $this->app->instance(RagServiceInterface::class, $this->mockService);
    }
    
    protected function tearDown(): void
    {
        // Clean up the temporary directory
        File::deleteDirectory($this->tempDir, true);
        
        parent::tearDown();
    }

    public function testCanImportDocumentsFromDirectory()
    {
        $this->mockService->shouldReceive('uploadDocument')
            ->atLeast(3) // Expecting at least 3 documents to be uploaded (excluding subdirectories)
            ->with(
                Mockery::type(RagCollection::class),
                Mockery::type('string'),
                Mockery::type('string'),
                Mockery::any()
            )
            ->andReturn(new RagDocument([
                'id' => 1,
                'document_id' => 'test_document_123',
                'status' => 'completed'
            ]));
        
        $this->artisan('ragkit:import', [
            'directory' => $this->tempDir,
            'collection_id' => $this->collection->id,
            '--recursive' => false,
        ])->assertSuccessful();
    }
    
    public function testCanImportDocumentsRecursively()
    {
        $this->mockService->shouldReceive('uploadDocument')
            ->atLeast(4) // Expecting at least 4 documents with recursive option
            ->with(
                Mockery::type(RagCollection::class),
                Mockery::type('string'),
                Mockery::type('string'),
                Mockery::any()
            )
            ->andReturn(new RagDocument([
                'id' => 1,
                'document_id' => 'test_document_123',
                'status' => 'completed'
            ]));
        
        $this->artisan('ragkit:import', [
            'directory' => $this->tempDir,
            'collection_id' => $this->collection->id,
            '--recursive' => true,
        ])->assertSuccessful();
    }
    
    public function testHandlesInvalidDirectory()
    {
        $nonExistentDir = $this->tempDir . '/nonexistent';
        
        $this->artisan('ragkit:import', [
            'directory' => $nonExistentDir,
            'collection_id' => $this->collection->id,
        ])
        ->expectsOutput('Directory not found: ' . $nonExistentDir)
        ->assertExitCode(1);
    }
    
    public function testHandlesInvalidCollection()
    {
        $this->artisan('ragkit:import', [
            'directory' => $this->tempDir,
            'collection_id' => 9999, // Invalid collection ID
        ])
        ->expectsOutput('Collection not found with ID: 9999')
        ->assertExitCode(1);
    }
} 