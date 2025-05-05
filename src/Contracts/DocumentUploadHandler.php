<?php

namespace RagKit\Contracts;

use RagKit\Models\RagDocument;

interface DocumentUploadHandler
{
    /**
     * Handle the document upload event.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return void
     */
    public function handle(RagDocument $document): void;
} 