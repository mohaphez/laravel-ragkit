# RagKit - Retrieval-Augmented Generation for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mohaphez/laravel-ragkit.svg?style=flat-square)](https://packagist.org/packages/mohaphez/laravel-ragkit)
[![Total Downloads](https://img.shields.io/packagist/dt/mohaphez/laravel-ragkit.svg?style=flat-square)](https://packagist.org/packages/mohaphez/laravel-ragkit)
[![Tests](https://img.shields.io/github/actions/workflow/status/mohaphez/laravel-ragkit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/mohaphez/laravel-ragkit/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/mohaphez/laravel-ragkit.svg?style=flat-square)](https://packagist.org/packages/mohaphez/laravel-ragkit)

RagKit is a Laravel package that provides a clean, reusable implementation of Retrieval-Augmented Generation (RAG) systems with support for multiple drivers.

## 📚 Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Data Structure](#data-structure)
- [Usage](#usage)
- [Console Commands](#console-commands)
- [API Endpoints](#api-endpoints)
- [Creating Custom Drivers](#creating-custom-drivers)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

### Core Features
- **Multi-Provider Support**: Built-in support for multiple RAG service providers (currently ChatBees)
- **Hierarchical Data Structure**: Organized account-collection structure for better document management
- **Document Management**: 
  - Asynchronous document upload and processing
  - Background job processing for document uploads
  - Support for multiple file formats (PDF, DOCX, DOC, TXT)
  - Document status tracking and error handling
- **RAG Operations**:
  - Document retrieval and question answering
  - Chat-based interaction with document knowledge
  - Document outlines and FAQs extraction
- **Laravel Integration**:
  - Seamless integration with existing Laravel applications
  - Built-in authentication and middleware support
  - Eloquent model relationships
  - Event-driven architecture

## Installation

You can install the package via composer:

```bash
composer require mohaphez/laravel-ragkit
```

After installing, publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="RagKit\RagKitServiceProvider" --tag="ragkit-config"
php artisan vendor:publish --provider="RagKit\RagKitServiceProvider" --tag="ragkit-migrations"
```

Then run the migrations:

```bash
php artisan migrate
```

## Configuration

### Provider Configuration
Configure your RAG service provider credentials in your `.env` file:

```
RAGKIT_DEFAULT_PROVIDER=chatbees
RAGKIT_CHATBEES_API_KEY=your-api-key
RAGKIT_CHATBEES_ACCOUNT_ID=your-account-id
```

### Storage Settings
```php
'storage' => [
    'disk' => env('RAGKIT_STORAGE_DISK', 'local'),
    'path' => env('RAGKIT_STORAGE_PATH', 'rag'),
],
```

### Upload Settings
Configure upload processing in `config/ragkit.php`:

```php
'upload' => [
    'enable_upload_listener' => true,
    'upload_handler_class' => \RagKit\Handlers\DefaultUploadHandler::class,
    'queue' => 'default',
    'max_retry_attempts' => 3,
    'retry_backoff' => [10, 60, 180], // seconds between retries
],
```

### Route Configuration
```php
'routes' => [
    'prefix' => 'rag',
    'middleware' => ['web', 'auth'],
],
```

## Data Structure

RagKit uses a hierarchical data structure:

1. **User Level**: Multiple RAG accounts per user
2. **Account Level**: Provider-specific accounts with custom settings
3. **Collection Level**: Logical grouping of related documents
4. **Document Level**: Individual documents with metadata, status tracking, and processing state

This structure allows for better organization and separation of concerns.

## Using the HasRagAccounts Trait

Add the `HasRagAccounts` trait to your User model:

```php
use RagKit\Traits\HasRagAccounts;

class User extends Authenticatable
{
    use HasRagAccounts;
    
    // ...rest of your User model
}
```

This will add the following relationships to your User model:
- `ragAccounts()` - A relationship to get all the user's RAG accounts
- `ragCollections()` - A relationship to get all collections across accounts
- `allRagDocuments()` - A relationship to get all documents across collections

## Usage

### Creating a RAG Account

```php
use RagKit\Facades\RagKit;

// Create a RAG account for a user
$account = RagKit::createUserAccount(
    $user,
    'My Research Account',
    'chatbees',
    [
        'description' => 'Account for research papers',
    ]
);
```

### Creating a Collection

```php
// Create a collection in the account
$collection = RagKit::createCollection(
    $account,
    'Research Papers',
    [
        'namespace_name' => 'public',
        'description' => 'Academic research papers on machine learning',
    ]
);
```

### Uploading Documents

```php
// Upload a document to a collection (triggers background processing)
$document = RagKit::uploadDocument(
    $collection,
    $filePath,
    $fileName,
    [
        'source' => 'web_upload',
        'category' => 'knowledge_base',
    ]
);

// Check document status
$status = $document->status; // created, queued, uploading, processing, completed, failed, retry
$message = $document->status_message;
```

### Document Processing Flow

1. Document is uploaded and stored locally
2. `DocumentUploaded` event is fired
3. `HandleDocumentUpload` listener processes the event
4. `DefaultUploadHandler` queues the document for processing
5. `ProcessRagDocumentUpload` job:
   - Uploads document to RAG provider
   - Updates document status
   - Retrieves outlines and FAQs
   - Handles retries on failure

### Document Status States
- `created`: Initial state when document is stored locally
- `queued`: Document is queued for processing
- `uploading`: Document is being uploaded to provider
- `processing`: Document is being processed by provider
- `completed`: Document processing is complete
- `failed`: Document processing failed
- `retry`: Document processing failed and will be retried

### Asking Questions

```php
// Ask a question using a specific collection
$result = RagKit::ask(
    $collection,
    'What is retrieval-augmented generation?',
    null, // Optional document ID to filter by
    []    // Chat history
);

// Access the answer and sources
$answer = $result['answer'];
$references = $result['references'];
```

### Chat Conversations

```php
// Start or continue a chat conversation within a collection
$history = [
    ['role' => 'user', 'content' => 'What is RAG?'],
    ['role' => 'assistant', 'content' => 'RAG stands for Retrieval-Augmented Generation...'],
];

$result = RagKit::ask(
    $collection,
    'Can you provide an example?',
    null,
    $history
);
```

## Console Commands

### Importing Documents

You can import multiple documents at once using the `ragkit:import` command:

```bash
php artisan ragkit:import /path/to/documents 1 --recursive --extensions=pdf,docx
```

The command accepts the following arguments and options:

- `directory`: Path to the directory containing files to import
- `collection_id`: ID of the collection to import documents into
- `--recursive`: (Optional) Import files recursively from subdirectories
- `--extensions`: (Optional) Comma-separated list of file extensions to import (defaults to pdf,docx,doc,txt)

### Listing Collections

List all RAG collections with their details:

```bash
php artisan ragkit:collections 
```

The command accepts the following options:

- `--account_id`: (Optional) Filter collections by account ID
- `--provider`: (Optional) Filter collections by provider (e.g., 'chatbees')
- `--user_id`: (Optional) Filter collections by user ID

## API Endpoints

All routes are prefixed with `/rag` and protected by `web` and `auth` middleware.

### Account Management
- **GET** `/rag/accounts`
  - Lists all RAG accounts for authenticated user
  - Response: Array of account objects

### Collection Management
- **GET** `/rag/collections`
  - Lists collections for specified account
  - Query Parameters:
    - `account_id`: Required
  - Response: Array of collection objects

### Document Operations
- **GET** `/rag/documents`
  - Lists documents in a collection
  - Query Parameters:
    - `collection_id`: Required
  - Response: Paginated document objects

- **POST** `/rag/documents/upload`
  - Uploads new document (triggers background processing)
  - Headers:
    - `Content-Type: multipart/form-data`
  - Body:
    - `file`: Document file
    - `collection_id`: Collection ID
    - `metadata`: Optional JSON metadata
  - Response: Document object with initial status

- **GET** `/rag/documents/{uuid}`
  - Retrieves document details including processing status
  - Response: Document object with status and metadata

- **DELETE** `/rag/documents/{uuid}`
  - Deletes a document
  - Response: Success status

- **GET** `/rag/documents/{uuid}/outline-faq`
  - Gets document outline and FAQs
  - Response: Object containing outline and FAQs

### Chat Interaction
- **POST** `/rag/chat`
  - Sends chat message
  - Body:
    - `collection_id`: Collection ID
    - `message`: User message
    - `history`: Optional chat history
  - Response: Chat response with references

## Creating Custom Drivers

You can create and register custom RAG service drivers by implementing the `RagServiceAdapterInterface` and registering it with the service:

```php
use RagKit\Contracts\RagServiceAdapterInterface;
use RagKit\Facades\RagKit;

class CustomRagAdapter implements RagServiceAdapterInterface
{
    // Implement interface methods
}

// Register adapter in AppServiceProvider
RagKit::registerAdapter('custom_provider', new CustomRagAdapter());
```

## Testing

Run the package tests with PHPUnit:

```bash
composer test
```

### Test Coverage
- **Unit Tests**:
  - Account management
  - Collection operations
  - Document handling and background processing
  - Chat functionality
  
- **Feature Tests**:
  - API endpoints
  - Console commands
  - Service integrations
  - Authentication flows

## Contributing

### Development Setup
1. Fork the repository
2. Create feature branch: `feature/your-feature-name`
3. Clone repository
4. Install dependencies:
   ```bash
   composer install
   ```
5. Copy `.env.example` to `.env`
6. Configure RAG provider credentials
7. Run migrations:
   ```bash
   php artisan migrate
   ```

### Coding Standards
- PSR-12 coding standard
- Laravel best practices
- PHPDoc blocks for methods

### Pull Request Process
1. Ensure tests pass: `composer test`
2. Update documentation if needed
3. Follow commit message convention:
   - feat: New feature
   - fix: Bug fix
   - docs: Documentation
   - test: Test updates
   - refactor: Code refactoring

### Branch Strategy
- `main`: Production-ready code
- `develop`: Development branch
- Feature branches: `feature/*`
- Bugfix branches: `fix/*`

### Code Review
- All PRs require review
- Must pass CI/CD checks
- Must maintain test coverage
- Must follow coding standards

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md). 