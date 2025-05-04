# RagKit - Retrieval-Augmented Generation for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ragkit/ragkit.svg?style=flat-square)](https://packagist.org/packages/ragkit/ragkit)
[![Total Downloads](https://img.shields.io/packagist/dt/ragkit/ragkit.svg?style=flat-square)](https://packagist.org/packages/ragkit/ragkit)
[![Tests](https://img.shields.io/github/actions/workflow/status/ragkit/ragkit/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ragkit/ragkit/actions/workflows/run-tests.yml)
[![License](https://img.shields.io/packagist/l/ragkit/ragkit.svg?style=flat-square)](https://packagist.org/packages/ragkit/ragkit)

RagKit is a Laravel package that provides a clean, reusable implementation of Retrieval-Augmented Generation (RAG) systems with support for multiple drivers.

## 📚 Documentation

For detailed documentation, please see [DOCUMENTATION.md](DOCUMENTATION.md).

### Quick Links
- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Basic Usage](#usage)
- [Console Commands](#console-commands)
- [Contributing](#contributing)
- [License](#license)

## Features

- Support for multiple RAG service providers (currently supports ChatBees)
- Hierarchical account-collection structure for better organization
- Simple API for uploading and managing documents
- Console commands for bulk document import and collection management
- Easy integration with existing Laravel applications
- Document retrieval and question answering
- Chat-based interaction with document knowledge
- Document outlines and FAQs extraction

## Installation

You can install the package via composer:

```bash
composer require ragkit/ragkit
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

Configure your RAG service provider credentials in your `.env` file:

```
RAGKIT_DEFAULT_PROVIDER=chatbees
RAGKIT_CHATBEES_API_KEY=your-api-key
RAGKIT_CHATBEES_ACCOUNT_ID=your-account-id
```

## Data Structure

RagKit uses a hierarchical data structure:

1. **User** - A user can have multiple RAG accounts
2. **Account** - Each account belongs to a user and represents a connection to a specific RAG provider
3. **Collection** - Each account can have multiple collections, which group related documents
4. **Document** - Documents are stored in collections

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
// Upload a document to a collection
$document = RagKit::uploadDocument(
    $collection,
    $filePath,
    $fileName,
    [
        'source' => 'web_upload',
        'category' => 'knowledge_base',
    ]
);
```

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

## Routes

The package provides several routes for managing accounts, collections, documents, and handling chat:

- `GET /rag/accounts` - Get all accounts for the current user
- `GET /rag/collections` - Get collections for a specific account
- `GET /rag/documents` - Get documents for a specific collection
- `POST /rag/chat` - Send a chat message to a collection
- `POST /rag/documents/upload` - Upload a new document to a collection
- `GET /rag/documents/{uuid}` - View a specific document
- `DELETE /rag/documents/{uuid}` - Delete a document
- `GET /rag/documents/{uuid}/outline-faq` - Get document outlines and FAQs

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

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md). 