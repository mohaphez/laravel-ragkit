# RagKit Documentation

## 📦 Features

### Core Features
- **Multi-Provider Support**: Built-in support for multiple RAG service providers (currently ChatBees)
- **Hierarchical Data Structure**: Organized account-collection structure for better document management
- **Document Management**: 
  - Upload and manage documents through simple API
  - Bulk document import via console commands
  - Support for multiple file formats (PDF, DOCX, DOC, TXT)
- **RAG Operations**:
  - Document retrieval and question answering
  - Chat-based interaction with document knowledge
  - Document outlines and FAQs extraction
- **Laravel Integration**:
  - Seamless integration with existing Laravel applications
  - Built-in authentication and middleware support
  - Eloquent model relationships

### Data Organization
- **User Level**: Multiple RAG accounts per user
- **Account Level**: Provider-specific accounts with custom settings
- **Collection Level**: Logical grouping of related documents
- **Document Level**: Individual documents with metadata and content

## ⚙️ Configurations

Configuration file: `config/ragkit.php`

### Provider Configuration
```php
'default' => env('RAGKIT_DEFAULT_PROVIDER', 'chatbees'),

'drivers' => [
    'chatbees' => true,
],

'connections' => [
    'chatbees' => [
        'api_key' => env('RAGKIT_CHATBEES_API_KEY', ''),
        'account_id' => env('RAGKIT_CHATBEES_ACCOUNT_ID', ''),
        'base_url' => env('RAGKIT_CHATBEES_BASE_URL', null),
    ],
],
```

### Storage Settings
```php
'storage' => [
    'disk' => env('RAGKIT_STORAGE_DISK', 'local'),
    'path' => env('RAGKIT_STORAGE_PATH', 'rag'),
],
```

### Route Configuration
```php
'routes' => [
    'prefix' => 'rag',
    'middleware' => ['web', 'auth'],
],
```

### Upload Settings
```php
'uploads' => [
    'max_file_size' => env('RAGKIT_MAX_FILE_SIZE', 10 * 1024), // 10MB
    'allowed_extensions' => ['pdf', 'docx', 'doc', 'txt'],
],
```

## 🛠️ Services

### ChatService
Responsible for handling chat-based interactions with documents.

```php
use RagKit\Services\ChatService;

// Ask a question
$result = $chatService->ask($collection, $question, $documentId = null, $history = []);

// Continue chat conversation
$result = $chatService->chat($collection, $message, $history);
```

### DocumentService
Manages document operations including upload, retrieval, and deletion.

```php
use RagKit\Services\DocumentService;

// Upload document
$document = $documentService->upload($collection, $file, $fileName, $metadata = []);

// Get document content
$content = $documentService->getContent($document);

// Extract outline and FAQs
$outline = $documentService->getOutline($document);
$faqs = $documentService->getFAQs($document);
```

## 🌐 Endpoints (API)

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
  - Uploads new document
  - Headers:
    - `Content-Type: multipart/form-data`
  - Body:
    - `file`: Document file
    - `collection_id`: Collection ID
    - `metadata`: Optional JSON metadata
  - Response: Created document object

- **GET** `/rag/documents/{uuid}`
  - Retrieves document details
  - Response: Document object with metadata

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

## ✅ Tests

The project includes comprehensive test coverage:

### Running Tests
```bash
composer test
```

### Test Coverage
- **Unit Tests**:
  - Account management
  - Collection operations
  - Document handling
  - Chat functionality
  
- **Feature Tests**:
  - API endpoints
  - Console commands
  - Service integrations
  - Authentication flows

## 🤝 Collaboration

### Contributing
1. Fork the repository
2. Create feature branch: `feature/your-feature-name`
3. Follow coding standards:
   - PSR-12 coding standard
   - Laravel best practices
   - PHPDoc blocks for methods
   
### Development Setup
1. Clone repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env`
4. Configure RAG provider credentials
5. Run migrations:
   ```bash
   php artisan migrate
   ```

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