<?php

namespace RagKit\Facades;

use Illuminate\Support\Facades\Facade;
use RagKit\Contracts\RagServiceInterface;

/**
 * @method static \RagKit\Contracts\RagServiceInterface registerAdapter(string $provider, object $adapter)
 * @method static object|null getAdapter(?string $provider = null)
 * @method static \RagKit\Models\UserRagAccount createUserRagAccount(mixed $user, string $provider, array $accountData)
 * @method static \RagKit\Models\RagDocument|null uploadDocument(mixed $user, string $filePath, string $fileName, array $metadata = [], ?string $provider = null)
 * @method static array ask(mixed $user, string $question, ?string $documentId = null, array $historyMessages = [], ?string $provider = null, ?int $accountId = null)
 * @method static array getDocumentOutlineFaq(mixed $user, string $documentId, ?string $provider = null)
 * 
 * @see \RagKit\RAG\RagService
 */
class RagKit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ragkit';
    }
}